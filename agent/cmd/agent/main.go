package main

import (
	"agent/internal/config"
	svc "agent/internal/service"
	"fmt"
	"log"
	"os"

	"github.com/kardianos/service"
	"github.com/spf13/cobra"
)

var (
	configFile string
	cfg        *config.Config
	prg        *svc.Program
	s          service.Service
)

func main() {
	rootCmd := &cobra.Command{
		Use:   "agent",
		Short: "Monitor Agent - 系统监控探针",
		Long:  `Monitor Agent 是一个系统监控探针，用于采集服务器指标并上报到监控平台。`,
		PersistentPreRunE: func(cmd *cobra.Command, args []string) error {
			// 跳过 version 命令的配置加载
			if cmd.Name() == "version" {
				return nil
			}

			var err error
			cfg, err = config.Load(configFile)
			if err != nil {
				return fmt.Errorf("failed to load config: %w", err)
			}

			prg = svc.NewProgram(cfg)
			svcConfig := svc.GetServiceConfig()

			s, err = service.New(prg, svcConfig)
			if err != nil {
				return fmt.Errorf("failed to create service: %w", err)
			}

			return nil
		},
	}

	rootCmd.PersistentFlags().StringVarP(&configFile, "config", "c", "config.yaml", "配置文件路径")

	// run 命令 - 前台运行
	runCmd := &cobra.Command{
		Use:   "run",
		Short: "前台运行 Agent",
		Long:  `在前台运行 Agent，适用于调试和开发。`,
		RunE: func(cmd *cobra.Command, args []string) error {
			return s.Run()
		},
	}

	// install 命令 - 安装服务
	installCmd := &cobra.Command{
		Use:   "install",
		Short: "安装 Agent 为系统服务",
		Long:  `将 Agent 安装为系统服务，支持 systemd (Linux)、launchd (macOS) 和 Windows 服务。`,
		RunE: func(cmd *cobra.Command, args []string) error {
			err := s.Install()
			if err != nil {
				return fmt.Errorf("安装服务失败: %w", err)
			}
			fmt.Println("✓ 服务安装成功")
			fmt.Println("  使用 'agent start' 启动服务")
			return nil
		},
	}

	// uninstall 命令 - 卸载服务
	uninstallCmd := &cobra.Command{
		Use:   "uninstall",
		Short: "卸载 Agent 系统服务",
		Long:  `从系统中卸载 Agent 服务。`,
		RunE: func(cmd *cobra.Command, args []string) error {
			err := s.Uninstall()
			if err != nil {
				return fmt.Errorf("卸载服务失败: %w", err)
			}
			fmt.Println("✓ 服务卸载成功")
			return nil
		},
	}

	// start 命令 - 启动服务
	startCmd := &cobra.Command{
		Use:   "start",
		Short: "启动 Agent 服务",
		Long:  `启动已安装的 Agent 系统服务。`,
		RunE: func(cmd *cobra.Command, args []string) error {
			err := s.Start()
			if err != nil {
				return fmt.Errorf("启动服务失败: %w", err)
			}
			fmt.Println("✓ 服务启动成功")
			return nil
		},
	}

	// stop 命令 - 停止服务
	stopCmd := &cobra.Command{
		Use:   "stop",
		Short: "停止 Agent 服务",
		Long:  `停止正在运行的 Agent 系统服务。`,
		RunE: func(cmd *cobra.Command, args []string) error {
			err := s.Stop()
			if err != nil {
				return fmt.Errorf("停止服务失败: %w", err)
			}
			fmt.Println("✓ 服务停止成功")
			return nil
		},
	}

	// restart 命令 - 重启服务
	restartCmd := &cobra.Command{
		Use:   "restart",
		Short: "重启 Agent 服务",
		Long:  `重启 Agent 系统服务。`,
		RunE: func(cmd *cobra.Command, args []string) error {
			err := s.Restart()
			if err != nil {
				return fmt.Errorf("重启服务失败: %w", err)
			}
			fmt.Println("✓ 服务重启成功")
			return nil
		},
	}

	// status 命令 - 查看服务状态
	statusCmd := &cobra.Command{
		Use:   "status",
		Short: "查看 Agent 服务状态",
		Long:  `查看 Agent 系统服务的运行状态。`,
		RunE: func(cmd *cobra.Command, args []string) error {
			status, err := s.Status()
			if err != nil {
				return fmt.Errorf("获取服务状态失败: %w", err)
			}

			statusText := map[service.Status]string{
				service.StatusRunning: "运行中",
				service.StatusStopped: "已停止",
				service.StatusUnknown: "未知",
			}

			fmt.Printf("服务状态: %s\n", statusText[status])
			return nil
		},
	}

	// version 命令 - 显示版本
	versionCmd := &cobra.Command{
		Use:   "version",
		Short: "显示版本信息",
		Run: func(cmd *cobra.Command, args []string) {
			fmt.Println("Monitor Agent v1.0.0")
			fmt.Println("Build: 2024-11-27")
		},
	}

	// 添加子命令
	rootCmd.AddCommand(runCmd)
	rootCmd.AddCommand(installCmd)
	rootCmd.AddCommand(uninstallCmd)
	rootCmd.AddCommand(startCmd)
	rootCmd.AddCommand(stopCmd)
	rootCmd.AddCommand(restartCmd)
	rootCmd.AddCommand(statusCmd)
	rootCmd.AddCommand(versionCmd)

	// 设置默认命令为 run
	if len(os.Args) == 1 {
		os.Args = append(os.Args, "run")
	}

	if err := rootCmd.Execute(); err != nil {
		log.Fatal(err)
	}
}
