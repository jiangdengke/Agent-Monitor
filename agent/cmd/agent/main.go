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

	// 3. 注册 Agent或加载已有ID
    // 自动获取主机信息填充注册数据
    hostInfo, _ := host.Info()
    
    // 尝试读取本地存储的 Agent ID
    agentIDFile := ".agent_id"
    if idBytes, err := os.ReadFile(agentIDFile); err == nil && len(idBytes) > 0 {
        agentID := string(idBytes)
        log.Printf("Loaded existing Agent ID: %s", agentID)
        cli.SetAgentID(agentID)
    } else {
        // 本地没有 ID，进行注册
        registerData := map[string]interface{}{
            "hostname": cfg.Agent.Hostname,
            "ip":       cfg.Agent.IP, 
            "os":       hostInfo.OS,
            "arch":     hostInfo.KernelArch,
            "version":  "1.0.0", // Agent 版本
        }
        
        if registerData["hostname"] == "" {
            registerData["hostname"] = hostInfo.Hostname
        }
        
        // 获取本机 IP (简单实现)
        if registerData["ip"] == "" || registerData["ip"] == "127.0.0.1" {
            // 尝试获取真实 IP，这里简单处理，如果配置没填且无法获取，使用本地回环或报错
            // 实际生产中应遍历网卡获取非回环 IP
            registerData["ip"] = "127.0.0.1" 
        }

        log.Println("Registering agent...")
        id, err := cli.Register(registerData)
        if err != nil {
            log.Fatalf("Failed to register agent: %v", err)
        }
        
        log.Printf("Agent registered successfully. ID: %s", id)
        cli.SetAgentID(id)
        
        // 保存 ID 到本地
        if err := os.WriteFile(agentIDFile, []byte(id), 0644); err != nil {
            log.Printf("Warning: Failed to save agent ID to file: %v", err)
        }
    } 

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

    // Disk IO
    if diskIOs, err := col.CollectDiskIO(); err == nil {
        for _, data := range diskIOs {
            metrics = append(metrics, map[string]interface{}{
                "type":      "disk_io",
                "data":      data,
                "timestamp": timestamp,
            })
        }
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
