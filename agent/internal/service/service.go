package service

import (
	"agent/internal/client"
	"agent/internal/collector"
	"agent/internal/config"
	"log"
	"os"
	"time"

	"github.com/kardianos/service"
	"github.com/shirou/gopsutil/v3/host"
)

// Program 实现 service.Interface
type Program struct {
	cfg    *config.Config
	cli    *client.Client
	col    *collector.Collector
	done   chan bool
}

// NewProgram 创建新的 Program 实例
func NewProgram(cfg *config.Config) *Program {
	return &Program{
		cfg:  cfg,
		done: make(chan bool),
	}
}

// Start 启动服务
func (p *Program) Start(s service.Service) error {
	log.Println("Agent service starting...")
	go p.run()
	return nil
}

// Stop 停止服务
func (p *Program) Stop(s service.Service) error {
	log.Println("Agent service stopping...")
	close(p.done)
	return nil
}

// run 运行主逻辑
func (p *Program) run() {
	// 初始化组件
	p.cli = client.New(p.cfg)
	p.col = collector.New()

	// 注册或加载已有 ID
	if err := p.registerAgent(); err != nil {
		log.Fatalf("Failed to register agent: %v", err)
	}

	// 启动定时器
	tickerMetric := time.NewTicker(time.Duration(p.cfg.Collector.Interval) * time.Second)
	tickerHeartbeat := time.NewTicker(time.Duration(p.cfg.Collector.HeartbeatInterval) * time.Second)
	defer tickerMetric.Stop()
	defer tickerHeartbeat.Stop()

	log.Println("Agent started")

	for {
		select {
		case <-p.done:
			return
		case <-tickerMetric.C:
			p.collectAndReport()
		case <-tickerHeartbeat.C:
			if err := p.cli.Heartbeat(); err != nil {
				log.Printf("Heartbeat failed: %v", err)
			} else {
				log.Println("Heartbeat sent")
			}
		}
	}
}

// registerAgent 注册探针
func (p *Program) registerAgent() error {
	hostInfo, _ := host.Info()

	// 尝试读取本地存储的 Agent ID
	agentIDFile := p.getAgentIDFile()
	if idBytes, err := os.ReadFile(agentIDFile); err == nil && len(idBytes) > 0 {
		agentID := string(idBytes)
		log.Printf("Loaded existing Agent ID: %s", agentID)
		p.cli.SetAgentID(agentID)
		return nil
	}

	// 本地没有 ID，进行注册
	registerData := map[string]interface{}{
		"hostname": p.cfg.Agent.Hostname,
		"ip":       p.cfg.Agent.IP,
		"os":       hostInfo.OS,
		"arch":     hostInfo.KernelArch,
		"version":  "1.0.0",
	}

	if registerData["hostname"] == "" {
		registerData["hostname"] = hostInfo.Hostname
	}

	if registerData["ip"] == "" || registerData["ip"] == "127.0.0.1" {
		registerData["ip"] = "127.0.0.1"
	}

	log.Println("Registering agent...")
	id, err := p.cli.Register(registerData)
	if err != nil {
		return err
	}

	log.Printf("Agent registered successfully. ID: %s", id)
	p.cli.SetAgentID(id)

	// 保存 ID 到本地
	if err := os.WriteFile(agentIDFile, []byte(id), 0644); err != nil {
		log.Printf("Warning: Failed to save agent ID to file: %v", err)
	}

	return nil
}

// getAgentIDFile 获取 Agent ID 文件路径
func (p *Program) getAgentIDFile() string {
	// 在 Windows 上使用程序所在目录
	// 在 Linux 上使用 /var/lib/agent/
	return ".agent_id"
}

// collectAndReport 采集并上报指标
func (p *Program) collectAndReport() {
	var metrics []interface{}
	timestamp := time.Now().UnixMilli()

	// CPU
	if data, err := p.col.CollectCPU(); err == nil {
		metrics = append(metrics, map[string]interface{}{
			"type":      "cpu",
			"data":      data,
			"timestamp": timestamp,
		})
	}

	// Memory
	if data, err := p.col.CollectMemory(); err == nil {
		metrics = append(metrics, map[string]interface{}{
			"type":      "memory",
			"data":      data,
			"timestamp": timestamp,
		})
	}

	// Disk
	if data, err := p.col.CollectDisk(); err == nil {
		metrics = append(metrics, map[string]interface{}{
			"type":      "disk",
			"data":      data,
			"timestamp": timestamp,
		})
	}

	// Disk IO
	if diskIOs, err := p.col.CollectDiskIO(); err == nil {
		for _, data := range diskIOs {
			metrics = append(metrics, map[string]interface{}{
				"type":      "disk_io",
				"data":      data,
				"timestamp": timestamp,
			})
		}
	}

	// Network
	if data, err := p.col.CollectNetwork(); err == nil {
		metrics = append(metrics, map[string]interface{}{
			"type":      "network",
			"data":      data,
			"timestamp": timestamp,
		})
	}

	// Load
	if data, err := p.col.CollectLoad(); err == nil {
		metrics = append(metrics, map[string]interface{}{
			"type":      "load",
			"data":      data,
			"timestamp": timestamp,
		})
	}

	// Host
	if data, err := p.col.CollectHost(); err == nil {
		metrics = append(metrics, map[string]interface{}{
			"type":      "host",
			"data":      data,
			"timestamp": timestamp,
		})
	}

	// GPU
	if gpus, err := p.col.CollectGPU(); err == nil {
		for _, data := range gpus {
			metrics = append(metrics, map[string]interface{}{
				"type":      "gpu",
				"data":      data,
				"timestamp": timestamp,
			})
		}
	}

	// Temperature
	if temps, err := p.col.CollectTemperature(); err == nil {
		for _, data := range temps {
			metrics = append(metrics, map[string]interface{}{
				"type":      "temperature",
				"data":      data,
				"timestamp": timestamp,
			})
		}
	}

	// Monitor Tasks
	if tasks, err := p.cli.GetMonitorTasks(); err == nil && len(tasks) > 0 {
		var monitorTasks []collector.MonitorTask
		for _, t := range tasks {
			monitorTasks = append(monitorTasks, collector.MonitorTask{
				ID:                 t.ID,
				Type:               t.Type,
				Target:             t.Target,
				Method:             t.Method,
				Headers:            t.Headers,
				Body:               t.Body,
				ExpectedStatusCode: t.ExpectedStatusCode,
				ExpectedContent:    t.ExpectedContent,
				Timeout:            t.Timeout,
			})
		}

		results := p.col.CollectMonitor(monitorTasks)
		for _, result := range results {
			metrics = append(metrics, map[string]interface{}{
				"type":      "monitor",
				"data":      result,
				"timestamp": timestamp,
			})
		}
	}

	if len(metrics) > 0 {
		if err := p.cli.ReportMetrics(metrics); err != nil {
			log.Printf("Report metrics failed: %v", err)
		} else {
			log.Printf("Reported %d metrics", len(metrics))
		}
	}
}

// GetServiceConfig 获取服务配置
func GetServiceConfig() *service.Config {
	return &service.Config{
		Name:        "monitor-agent",
		DisplayName: "Monitor Agent",
		Description: "System monitoring agent service",
	}
}
