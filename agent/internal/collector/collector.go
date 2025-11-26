package collector

import (
	"time"

	"github.com/shirou/gopsutil/v3/cpu"
	"github.com/shirou/gopsutil/v3/disk"
	"github.com/shirou/gopsutil/v3/host"
	"github.com/shirou/gopsutil/v3/load"
	"github.com/shirou/gopsutil/v3/mem"
	"github.com/shirou/gopsutil/v3/net"
)

type Collector struct{}

func New() *Collector {
	return &Collector{}
}

// CollectCPU 采集 CPU 指标
func (c *Collector) CollectCPU() (map[string]interface{}, error) {
	percent, err := cpu.Percent(time.Second, false)
	if err != nil {
		return nil, err
	}
	
	counts, _ := cpu.Counts(true) // 逻辑核心
	physical, _ := cpu.Counts(false) // 物理核心

	return map[string]interface{}{
		"usage_percent": percent[0],
		"logical_cores": counts,
		"physical_cores": physical,
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
// 注意：gopsutil 返回的是累计值，我们需要计算速率
// 这里先返回累计值，速率计算可以在 Agent 主逻辑里做，或者后端做（但后端做不准）
// 暂时简化：只返回累计值，速率字段留空或由后端计算
func (c *Collector) CollectNetwork() (map[string]interface{}, error) {
	counters, err := net.IOCounters(false) // false 表示汇总
	if err != nil {
		return nil, err
	}

	if len(counters) == 0 {
		return nil, nil
	}

	stat := counters[0]
	return map[string]interface{}{
		"interface":        "all",
		"bytes_sent_total": stat.BytesSent,
		"bytes_recv_total": stat.BytesRecv,
		// 速率需要在两次采集之间计算，这里暂不处理
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
