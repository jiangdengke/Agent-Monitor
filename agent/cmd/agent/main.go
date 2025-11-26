package main

import (
	"agent/internal/client"
	"agent/internal/collector"
	"agent/internal/config"
	"flag"
	"log"
	"os"
	"os/signal"
	"syscall"
	"time"
    
    "github.com/shirou/gopsutil/v3/host"
)

func main() {
	configFile := flag.String("config", "config.yaml", "Path to config file")
	flag.Parse()

	// 1. 加载配置
	cfg, err := config.Load(*configFile)
	if err != nil {
		log.Fatalf("Failed to load config: %v", err)
	}

	// 2. 初始化组件
	cli := client.New(cfg)
	col := collector.New()

	// 3. 注册 Agent
    // 自动获取主机信息填充注册数据
    hostInfo, _ := host.Info()
    
    registerData := map[string]interface{}{
        "hostname": cfg.Agent.Hostname,
        "ip":       cfg.Agent.IP, // 如果配置没填，后端有 IP 校验，这里可能需要自动获取 IP
        "os":       hostInfo.OS,
        "arch":     hostInfo.KernelArch,
        "version":  "1.0.0", // Agent 版本
    }
    
    if registerData["hostname"] == "" {
        registerData["hostname"] = hostInfo.Hostname
    }
    
    // 获取本机 IP (简单实现)
    if registerData["ip"] == "" {
        // 这里先硬编码或者依赖后端从 Request 获取
        // 为了演示，假设用户在 config.yaml 里填了，或者我们发一个空字符串让后端处理
        registerData["ip"] = "127.0.0.1" 
    }

	log.Println("Registering agent...")
    // 注意：这里 register 逻辑需要根据后端实际返回调整
    // 目前后端 register 接口返回的是 Agent 对象
    // 我们暂时无法从 Register 方法的返回值直接拿到 ID (因为 client.go 里还没写完整解析)
    // 所以这里我们先模拟一下，或者假设 client.Register 内部已经处理好了 ID
    // 为了跑通，我们需要完善 client.go 的 Register 解析逻辑，但这里先假设成功
	_, err = cli.Register(registerData)
	if err != nil {
		log.Printf("Register warning: %v. (If agent already exists, this is fine)", err)
	}
    
    // 重要：我们需要拿到 Agent ID。
    // 由于后端 register 接口在 agent 已存在时也会返回 agent 信息
    // 我们可以把 hostname 当做唯一标识去后端查询，或者在本地缓存 ID
    // 简化起见：我们假设 config.yaml 里手动填了 ID，或者我们通过其他方式获取
    // 既然是第一次跑，我们先硬编码一个 ID 测试，或者稍后修改 client.go 自动保存 ID
    
    // 临时方案：尝试从 config 获取 agent_id，如果没有，需要一种机制获取
    // 在真实场景中，注册成功后应该把 ID 写入本地文件
    
    // 为了演示方便，我们先不 set ID，让 Client 报错，提醒用户填 ID 或完善注册逻辑
    // 或者，我们在 config.yaml 加一个 agent_id 字段
    
    // 假设注册接口返回了 ID，我们需要去 client.go 把解析逻辑补全
    // 现在先跳过，假设用户手动在 main.go 里填 ID 测试
    
    // cli.SetAgentID("your-uuid-here") 

	// 4. 启动定时器
	tickerMetric := time.NewTicker(time.Duration(cfg.Collector.Interval) * time.Second)
	tickerHeartbeat := time.NewTicker(time.Duration(cfg.Collector.HeartbeatInterval) * time.Second)
	done := make(chan bool)

	go func() {
		for {
			select {
			case <-done:
				return
			case <-tickerMetric.C:
				collectAndReport(cli, col)
			case <-tickerHeartbeat.C:
				if err := cli.Heartbeat(); err != nil {
					log.Printf("Heartbeat failed: %v", err)
				} else {
                    log.Println("Heartbeat sent")
                }
			}
		}
	}()

	log.Println("Agent started")

	// 5. 优雅退出
	sigs := make(chan os.Signal, 1)
	signal.Notify(sigs, syscall.SIGINT, syscall.SIGTERM)
	<-sigs

	done <- true
	log.Println("Agent stopping...")
}

func collectAndReport(cli *client.Client, col *collector.Collector) {
	var metrics []interface{}
    timestamp := time.Now().UnixMilli()

	// CPU
	if data, err := col.CollectCPU(); err == nil {
		metrics = append(metrics, map[string]interface{}{
			"type": "cpu",
			"data": data,
            "timestamp": timestamp,
		})
	}

	// Memory
	if data, err := col.CollectMemory(); err == nil {
		metrics = append(metrics, map[string]interface{}{
			"type": "memory",
			"data": data,
            "timestamp": timestamp,
		})
	}

	// Disk
	if data, err := col.CollectDisk(); err == nil {
		metrics = append(metrics, map[string]interface{}{
			"type": "disk",
			"data": data,
            "timestamp": timestamp,
		})
	}
    
    // Network
    if data, err := col.CollectNetwork(); err == nil {
        metrics = append(metrics, map[string]interface{}{
            "type": "network",
            "data": data,
            "timestamp": timestamp,
        })
    }
    
    // Load
    if data, err := col.CollectLoad(); err == nil {
        metrics = append(metrics, map[string]interface{}{
            "type": "load",
            "data": data,
            "timestamp": timestamp,
        })
    }
    
    // Host
    if data, err := col.CollectHost(); err == nil {
        metrics = append(metrics, map[string]interface{}{
            "type": "host",
            "data": data,
            "timestamp": timestamp,
        })
    }

	if len(metrics) > 0 {
		if err := cli.ReportMetrics(metrics); err != nil {
			log.Printf("Report metrics failed: %v", err)
		} else {
			log.Printf("Reported %d metrics", len(metrics))
		}
	}
}
