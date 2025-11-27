package collector

import (
	"context"
	"crypto/tls"
	"encoding/csv"
	"fmt"
	"io"
	"net"
	"net/http"
	"os/exec"
	"runtime"
	"strconv"
	"strings"
	"sync"
	"time"

	"github.com/shirou/gopsutil/v3/cpu"
	"github.com/shirou/gopsutil/v3/disk"
	"github.com/shirou/gopsutil/v3/host"
	"github.com/shirou/gopsutil/v3/load"
	"github.com/shirou/gopsutil/v3/mem"
	gnet "github.com/shirou/gopsutil/v3/net"
)

// gpuStaticInfo GPU 静态信息
type gpuStaticInfo struct {
	Index       int
	Name        string
	MemoryTotal uint64
}

// networkStats 网络统计缓存
type networkStats struct {
	BytesSent uint64
	BytesRecv uint64
	Timestamp time.Time
}

// diskIOStats 磁盘 IO 统计缓存
type diskIOStats struct {
	ReadBytes  uint64
	WriteBytes uint64
	Timestamp  time.Time
}

type Collector struct {
	gpuStaticData     map[int]*gpuStaticInfo
	gpuStaticInitOnce sync.Once
	gpuMu             sync.RWMutex

	// 网络速率计算
	lastNetworkStats *networkStats
	networkMu        sync.Mutex

	// 磁盘 IO 速率计算
	lastDiskIOStats map[string]*diskIOStats
	diskIOMu        sync.Mutex
}

func New() *Collector {
	return &Collector{
		gpuStaticData:   make(map[int]*gpuStaticInfo),
		lastDiskIOStats: make(map[string]*diskIOStats),
	}
}

// CollectCPU 采集 CPU 指标
func (c *Collector) CollectCPU() (map[string]interface{}, error) {
	percent, err := cpu.Percent(time.Second, false)
	if err != nil {
		return nil, err
	}

	counts, _ := cpu.Counts(true)  // 逻辑核心
	physical, _ := cpu.Counts(false) // 物理核心

	// 获取 CPU 型号名称
	var modelName string
	infos, err := cpu.Info()
	if err == nil && len(infos) > 0 {
		modelName = infos[0].ModelName
	}

	return map[string]interface{}{
		"usage_percent":  percent[0],
		"logical_cores":  counts,
		"physical_cores": physical,
		"model_name":     modelName,
	}, nil
}

// CollectMemory 采集内存指标
func (c *Collector) CollectMemory() (map[string]interface{}, error) {
	v, err := mem.VirtualMemory()
	if err != nil {
		return nil, err
	}
	
	s, _ := mem.SwapMemory()

	return map[string]interface{}{
		"total":         v.Total,
		"used":          v.Used,
		"free":          v.Free,
		"usage_percent": v.UsedPercent,
		"swap_total":    s.Total,
		"swap_used":     s.Used,
		"swap_free":     s.Free,
	}, nil
}

// CollectDisk 采集磁盘指标 (根目录)
func (c *Collector) CollectDisk() (map[string]interface{}, error) {
	// parts, err := disk.Partitions(false)
	// if err != nil {
	// 	return nil, err
	// }

	// 这里简单起见，只采集根目录 /，或者遍历所有 ext4/xfs 分区
	// 实际生产中可能需要过滤 snap, docker 等
	usage, err := disk.Usage("/")
	if err != nil {
		return nil, err
	}

	return map[string]interface{}{
		"mount_point":   "/",
		"total":         usage.Total,
		"used":          usage.Used,
		"free":          usage.Free,
		"usage_percent": usage.UsedPercent,
	}, nil
}

// CollectNetwork 采集网络指标 (所有网卡汇总)
func (c *Collector) CollectNetwork() (map[string]interface{}, error) {
	counters, err := gnet.IOCounters(false) // false 表示汇总
	if err != nil {
		return nil, err
	}

	if len(counters) == 0 {
		return nil, nil
	}

	stat := counters[0]
	now := time.Now()

	c.networkMu.Lock()
	defer c.networkMu.Unlock()

	var bytesSentRate, bytesRecvRate int64

	// 计算速率
	if c.lastNetworkStats != nil {
		elapsed := now.Sub(c.lastNetworkStats.Timestamp).Seconds()
		if elapsed > 0 {
			bytesSentRate = int64(float64(stat.BytesSent-c.lastNetworkStats.BytesSent) / elapsed)
			bytesRecvRate = int64(float64(stat.BytesRecv-c.lastNetworkStats.BytesRecv) / elapsed)

			// 防止负数（系统重启等情况）
			if bytesSentRate < 0 {
				bytesSentRate = 0
			}
			if bytesRecvRate < 0 {
				bytesRecvRate = 0
			}
		}
	}

	// 更新缓存
	c.lastNetworkStats = &networkStats{
		BytesSent: stat.BytesSent,
		BytesRecv: stat.BytesRecv,
		Timestamp: now,
	}

	return map[string]interface{}{
		"interface":        "all",
		"bytes_sent_total": stat.BytesSent,
		"bytes_recv_total": stat.BytesRecv,
		"bytes_sent_rate":  bytesSentRate,
		"bytes_recv_rate":  bytesRecvRate,
	}, nil
}

// CollectLoad 采集负载
func (c *Collector) CollectLoad() (map[string]interface{}, error) {
	avg, err := load.Avg()
	if err != nil {
		return nil, err
	}

	return map[string]interface{}{
		"load1":  avg.Load1,
		"load5":  avg.Load5,
		"load15": avg.Load15,
	}, nil
}

// CollectHost 采集主机信息
func (c *Collector) CollectHost() (map[string]interface{}, error) {
	info, err := host.Info()
	if err != nil {
		return nil, err
	}

	return map[string]interface{}{
		"os":               info.OS,
		"platform":         info.Platform,
		"platform_version": info.PlatformVersion,
		"kernel_version":   info.KernelVersion,
		"kernel_arch":      info.KernelArch,
		"uptime":           info.Uptime,
		"boot_time":        info.BootTime,
		"procs":            info.Procs,
	}, nil
}

// CollectDiskIO 采集磁盘IO
func (c *Collector) CollectDiskIO() ([]map[string]interface{}, error) {
	ioStats, err := disk.IOCounters()
	if err != nil {
		return nil, err
	}

	now := time.Now()
	c.diskIOMu.Lock()
	defer c.diskIOMu.Unlock()

	var results []map[string]interface{}
	for _, stat := range ioStats {
		var readBytesRate, writeBytesRate int64

		// 计算速率
		if lastStats, ok := c.lastDiskIOStats[stat.Name]; ok {
			elapsed := now.Sub(lastStats.Timestamp).Seconds()
			if elapsed > 0 {
				readBytesRate = int64(float64(stat.ReadBytes-lastStats.ReadBytes) / elapsed)
				writeBytesRate = int64(float64(stat.WriteBytes-lastStats.WriteBytes) / elapsed)

				// 防止负数
				if readBytesRate < 0 {
					readBytesRate = 0
				}
				if writeBytesRate < 0 {
					writeBytesRate = 0
				}
			}
		}

		// 更新缓存
		c.lastDiskIOStats[stat.Name] = &diskIOStats{
			ReadBytes:  stat.ReadBytes,
			WriteBytes: stat.WriteBytes,
			Timestamp:  now,
		}

		results = append(results, map[string]interface{}{
			"device":           stat.Name,
			"read_bytes":       stat.ReadBytes,
			"write_bytes":      stat.WriteBytes,
			"read_count":       stat.ReadCount,
			"write_count":      stat.WriteCount,
			"read_time":        stat.ReadTime,
			"write_time":       stat.WriteTime,
			"io_time":          stat.IoTime,
			"iops_in_progress": stat.IopsInProgress,
			"read_bytes_rate":  readBytesRate,
			"write_bytes_rate": writeBytesRate,
		})
	}
	return results, nil
}

// initGPUStatic 初始化 GPU 静态数据 (只执行一次)
func (c *Collector) initGPUStatic() {
	c.gpuStaticInitOnce.Do(func() {
		// 检查 nvidia-smi 是否可用
		_, err := exec.LookPath("nvidia-smi")
		if err != nil {
			return
		}

		// 查询静态信息: index, name, memory.total
		cmd := exec.Command("nvidia-smi",
			"--query-gpu=index,name,memory.total",
			"--format=csv,noheader,nounits")

		output, err := cmd.Output()
		if err != nil {
			return
		}

		// 解析 CSV 输出
		reader := csv.NewReader(strings.NewReader(string(output)))
		reader.TrimLeadingSpace = true
		records, err := reader.ReadAll()
		if err != nil {
			return
		}

		c.gpuMu.Lock()
		defer c.gpuMu.Unlock()

		for _, record := range records {
			if len(record) < 3 {
				continue
			}

			index, _ := strconv.Atoi(record[0])
			memoryTotal, _ := strconv.ParseUint(record[2], 10, 64)

			c.gpuStaticData[index] = &gpuStaticInfo{
				Index:       index,
				Name:        strings.TrimSpace(record[1]),
				MemoryTotal: memoryTotal * 1024 * 1024, // 转换为字节
			}
		}
	})
}

// CollectGPU 采集 GPU 数据
func (c *Collector) CollectGPU() ([]map[string]interface{}, error) {
	c.initGPUStatic()

	// 如果没有检测到 GPU，返回空数组
	c.gpuMu.RLock()
	if len(c.gpuStaticData) == 0 {
		c.gpuMu.RUnlock()
		return []map[string]interface{}{}, nil
	}
	c.gpuMu.RUnlock()

	// 采集动态数据
	cmd := exec.Command("nvidia-smi",
		"--query-gpu=index,temperature.gpu,utilization.gpu,memory.used,memory.free,power.draw,fan.speed",
		"--format=csv,noheader,nounits")

	output, err := cmd.Output()
	if err != nil {
		return []map[string]interface{}{}, nil
	}

	// 解析 CSV 输出
	reader := csv.NewReader(strings.NewReader(string(output)))
	reader.TrimLeadingSpace = true
	records, err := reader.ReadAll()
	if err != nil {
		return []map[string]interface{}{}, nil
	}

	c.gpuMu.RLock()
	defer c.gpuMu.RUnlock()

	var results []map[string]interface{}
	for _, record := range records {
		if len(record) < 7 {
			continue
		}

		index, _ := strconv.Atoi(record[0])
		temperature, _ := strconv.ParseFloat(record[1], 64)
		utilization, _ := strconv.ParseFloat(record[2], 64)
		memoryUsed, _ := strconv.ParseUint(record[3], 10, 64)
		memoryFree, _ := strconv.ParseUint(record[4], 10, 64)
		powerDraw, _ := strconv.ParseFloat(record[5], 64)
		fanSpeed, _ := strconv.ParseFloat(record[6], 64)

		// 获取静态信息
		staticInfo := c.gpuStaticData[index]
		if staticInfo == nil {
			continue
		}

		results = append(results, map[string]interface{}{
			"index":        staticInfo.Index,
			"name":         staticInfo.Name,
			"memory_total": staticInfo.MemoryTotal,
			"temperature":  temperature,
			"utilization":  utilization,
			"memory_used":  memoryUsed * 1024 * 1024,
			"memory_free":  memoryFree * 1024 * 1024,
			"power_draw":   powerDraw,
			"fan_speed":    fanSpeed,
		})
	}

	return results, nil
}

// CollectTemperature 采集温度数据
func (c *Collector) CollectTemperature() ([]map[string]interface{}, error) {
	// 优先使用 gopsutil 的温度采集
	temps, err := host.SensorsTemperatures()
	if err == nil && len(temps) > 0 {
		var results []map[string]interface{}
		for _, t := range temps {
			// 过滤无效数据
			if t.Temperature <= 0 || t.Temperature > 150 {
				continue
			}
			results = append(results, map[string]interface{}{
				"sensor_key":   t.SensorKey,
				"sensor_label": t.SensorKey,
				"temperature":  t.Temperature,
			})
		}
		if len(results) > 0 {
			return results, nil
		}
	}

	// 回退到命令行方式
	switch runtime.GOOS {
	case "linux":
		return c.collectTemperatureLinux()
	case "darwin":
		return c.collectTemperatureDarwin()
	default:
		return []map[string]interface{}{}, nil
	}
}

// collectTemperatureLinux 在 Linux 上采集温度 (回退方案)
func (c *Collector) collectTemperatureLinux() ([]map[string]interface{}, error) {
	_, err := exec.LookPath("sensors")
	if err != nil {
		return []map[string]interface{}{}, nil
	}

	cmd := exec.Command("sensors", "-A")
	output, err := cmd.Output()
	if err != nil {
		return []map[string]interface{}{}, nil
	}

	var results []map[string]interface{}
	lines := strings.Split(string(output), "\n")
	currentSensor := ""

	for _, line := range lines {
		line = strings.TrimSpace(line)
		if line == "" {
			continue
		}

		if !strings.Contains(line, ":") {
			currentSensor = line
			continue
		}

		if strings.Contains(line, "°C") {
			parts := strings.Split(line, ":")
			if len(parts) < 2 {
				continue
			}

			label := strings.TrimSpace(parts[0])
			valueStr := strings.TrimSpace(parts[1])
			tempStr := strings.Split(valueStr, "°C")[0]
			tempStr = strings.TrimSpace(strings.TrimPrefix(tempStr, "+"))

			temp, err := strconv.ParseFloat(tempStr, 64)
			if err != nil {
				continue
			}

			sensorKey := currentSensor + "_" + label
			results = append(results, map[string]interface{}{
				"sensor_key":   sensorKey,
				"sensor_label": label,
				"temperature":  temp,
			})
		}
	}

	return results, nil
}

// collectTemperatureDarwin 在 macOS 上采集温度 (回退方案)
func (c *Collector) collectTemperatureDarwin() ([]map[string]interface{}, error) {
	_, err := exec.LookPath("osx-cpu-temp")
	if err != nil {
		return []map[string]interface{}{}, nil
	}

	cmd := exec.Command("osx-cpu-temp")
	output, err := cmd.Output()
	if err != nil {
		return []map[string]interface{}{}, nil
	}

	outputStr := strings.TrimSpace(string(output))
	tempStr := strings.Split(outputStr, "°C")[0]
	temp, err := strconv.ParseFloat(tempStr, 64)
	if err != nil {
		return []map[string]interface{}{}, nil
	}

	return []map[string]interface{}{
		{
			"sensor_key":   "CPU",
			"sensor_label": "CPU",
			"temperature":  temp,
		},
	}, nil
}

// MonitorTask 监控任务配置
type MonitorTask struct {
	ID                 string            `json:"id"`
	Type               string            `json:"type"` // http, tcp
	Target             string            `json:"target"`
	Method             string            `json:"method,omitempty"`              // HTTP method
	Headers            map[string]string `json:"headers,omitempty"`             // HTTP headers
	Body               string            `json:"body,omitempty"`                // HTTP body
	ExpectedStatusCode int               `json:"expected_status_code,omitempty"`
	ExpectedContent    string            `json:"expected_content,omitempty"`
	Timeout            int               `json:"timeout,omitempty"` // 超时时间（秒）
}

// MonitorResult 监控结果
type MonitorResult struct {
	MonitorID      string `json:"monitor_id"`
	Type           string `json:"type"`
	Target         string `json:"target"`
	Status         string `json:"status"` // up, down
	StatusCode     int    `json:"status_code,omitempty"`
	ResponseTime   int64  `json:"response_time"` // 毫秒
	Error          string `json:"error,omitempty"`
	Message        string `json:"message,omitempty"`
	ContentMatch   bool   `json:"content_match,omitempty"`
	CertExpiryTime int64  `json:"cert_expiry_time,omitempty"`
	CertDaysLeft   int    `json:"cert_days_left,omitempty"`
}

// CollectMonitor 执行监控任务
func (c *Collector) CollectMonitor(tasks []MonitorTask) []MonitorResult {
	if len(tasks) == 0 {
		return nil
	}

	results := make([]MonitorResult, 0, len(tasks))

	for _, task := range tasks {
		var result MonitorResult

		switch strings.ToLower(task.Type) {
		case "http", "https":
			result = c.checkHTTP(task)
		case "tcp":
			result = c.checkTCP(task)
		default:
			result = MonitorResult{
				MonitorID: task.ID,
				Type:      task.Type,
				Target:    task.Target,
				Status:    "down",
				Error:     fmt.Sprintf("unsupported monitor type: %s", task.Type),
			}
		}

		results = append(results, result)
	}

	return results
}

// checkHTTP 检查 HTTP/HTTPS 服务
func (c *Collector) checkHTTP(task MonitorTask) MonitorResult {
	result := MonitorResult{
		MonitorID: task.ID,
		Type:      task.Type,
		Target:    task.Target,
	}

	// 设置默认值
	method := task.Method
	if method == "" {
		method = "GET"
	}

	timeout := task.Timeout
	if timeout <= 0 {
		timeout = 60
	}

	expectedStatus := task.ExpectedStatusCode
	if expectedStatus == 0 {
		expectedStatus = 200
	}

	// 创建 HTTP 客户端
	httpClient := &http.Client{
		Transport: &http.Transport{
			TLSClientConfig: &tls.Config{
				InsecureSkipVerify: true, // 允许自签名证书
			},
			DisableKeepAlives: true,
		},
		CheckRedirect: func(req *http.Request, via []*http.Request) error {
			if len(via) >= 10 {
				return fmt.Errorf("stopped after 10 redirects")
			}
			return nil
		},
	}

	// 创建请求
	var bodyReader io.Reader
	if task.Body != "" {
		bodyReader = strings.NewReader(task.Body)
	}

	ctx, cancel := context.WithTimeout(context.Background(), time.Duration(timeout)*time.Second)
	defer cancel()

	req, err := http.NewRequestWithContext(ctx, method, task.Target, bodyReader)
	if err != nil {
		result.Status = "down"
		result.Error = fmt.Sprintf("create request failed: %v", err)
		return result
	}

	// 设置请求头
	if task.Headers != nil {
		for key, value := range task.Headers {
			req.Header.Set(key, value)
		}
	}

	// 发送请求并计时
	startTime := time.Now()
	resp, err := httpClient.Do(req)
	responseTime := time.Since(startTime).Milliseconds()
	result.ResponseTime = responseTime

	if err != nil {
		result.Status = "down"
		result.Error = fmt.Sprintf("request failed: %v", err)
		return result
	}
	defer resp.Body.Close()

	result.StatusCode = resp.StatusCode

	// 检查状态码
	if resp.StatusCode != expectedStatus {
		result.Status = "down"
		result.Error = fmt.Sprintf("status code mismatch: expected %d, got %d", expectedStatus, resp.StatusCode)
		result.Message = fmt.Sprintf("HTTP %d", resp.StatusCode)
		return result
	}

	// 检查响应内容
	if task.ExpectedContent != "" {
		body, err := io.ReadAll(resp.Body)
		if err != nil {
			result.Status = "down"
			result.Error = fmt.Sprintf("read response body failed: %v", err)
			return result
		}

		bodyStr := string(body)
		if !strings.Contains(bodyStr, task.ExpectedContent) {
			result.Status = "down"
			result.Error = fmt.Sprintf("content does not contain expected string: %s", task.ExpectedContent)
			result.ContentMatch = false
			return result
		}
		result.ContentMatch = true
	}

	// 获取 HTTPS 证书信息
	if resp.TLS != nil && len(resp.TLS.PeerCertificates) > 0 {
		cert := resp.TLS.PeerCertificates[0]
		expiryTime := cert.NotAfter
		result.CertExpiryTime = expiryTime.UnixMilli()
		daysLeft := int(time.Until(expiryTime).Hours() / 24)
		result.CertDaysLeft = daysLeft
	}

	// 检查成功
	result.Status = "up"
	result.Message = fmt.Sprintf("HTTP %d - %dms", resp.StatusCode, responseTime)
	return result
}

// checkTCP 检查 TCP 端口
func (c *Collector) checkTCP(task MonitorTask) MonitorResult {
	result := MonitorResult{
		MonitorID: task.ID,
		Type:      task.Type,
		Target:    task.Target,
	}

	timeout := task.Timeout
	if timeout <= 0 {
		timeout = 10
	}

	// 连接并计时
	startTime := time.Now()
	conn, err := net.DialTimeout("tcp", task.Target, time.Duration(timeout)*time.Second)
	responseTime := time.Since(startTime).Milliseconds()
	result.ResponseTime = responseTime

	if err != nil {
		result.Status = "down"
		result.Error = fmt.Sprintf("connection failed: %v", err)
		return result
	}
	defer conn.Close()

	// 连接成功
	result.Status = "up"
	result.Message = fmt.Sprintf("TCP connected - %dms", responseTime)
	return result
}

