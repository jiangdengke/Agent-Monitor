package client

import (
	"agent/internal/config"
	"fmt"
	"log"
	"time"

	"github.com/go-resty/resty/v2"
)

type Client struct {
	client *resty.Client
	conf   *config.Config
	agentID string
}

func New(cfg *config.Config) *Client {
	c := resty.New()
	c.SetBaseURL(cfg.Server.URL)
	c.SetHeader("X-API-Key", cfg.Server.APIKey)
	c.SetHeader("Content-Type", "application/json")
	c.SetTimeout(10 * time.Second)

	return &Client{
		client: c,
		conf:   cfg,
	}
}

func (c *Client) SetAgentID(id string) {
	c.agentID = id
}

// Register 注册探针
func (c *Client) Register(info map[string]interface{}) (string, error) {
	// 定义响应结构
	type RegisterResponse struct {
		Code int `json:"code"`
		Data struct {
			ID string `json:"id"`
		} `json:"data"`
		Message string `json:"message"`
	}

	var result RegisterResponse

	resp, err := c.client.R().
		SetBody(info).
		SetResult(&result).
		Post("/agents/register")

	if err != nil {
		return "", err
	}

	if resp.IsError() {
		return "", fmt.Errorf("register failed: %s", resp.String())
	}

	log.Printf("Register response: code=%d message=%s", result.Code, result.Message)

	// 假设后端返回的 Code 200 或 200001 表示成功
	// 具体 Code 请参考 ResponseCodeEnum，通常成功是 200 或 业务码
	if result.Data.ID != "" {
		c.SetAgentID(result.Data.ID)
		return result.Data.ID, nil
	}

	return "", nil
}

// Heartbeat 发送心跳
func (c *Client) Heartbeat() error {
	if c.agentID == "" {
		return fmt.Errorf("agent id not set")
	}

	resp, err := c.client.R().
		Post(fmt.Sprintf("/agents/%s/heartbeat", c.agentID))

	if err != nil {
		return err
	}

	if resp.IsError() {
		return fmt.Errorf("heartbeat failed: %s", resp.String())
	}

	return nil
}

// ReportMetrics 上报指标
func (c *Client) ReportMetrics(metrics []interface{}) error {
	if c.agentID == "" {
		return fmt.Errorf("agent id not set")
	}

	payload := map[string]interface{}{
		"agent_id": c.agentID,
		"metrics":  metrics,
	}

	resp, err := c.client.R().
		SetBody(payload).
		Post("/agents/metrics")

	if err != nil {
		return err
	}

	if resp.IsError() {
		return fmt.Errorf("report metrics failed: %s", resp.String())
	}

	return nil
}

// MonitorTask 监控任务 (与 collector 中的定义保持一致)
type MonitorTask struct {
	ID                 string            `json:"id"`
	Type               string            `json:"type"`
	Target             string            `json:"target"`
	Method             string            `json:"method,omitempty"`
	Headers            map[string]string `json:"headers,omitempty"`
	Body               string            `json:"body,omitempty"`
	ExpectedStatusCode int               `json:"expected_status_code,omitempty"`
	ExpectedContent    string            `json:"expected_content,omitempty"`
	Timeout            int               `json:"timeout,omitempty"`
}

// GetMonitorTasks 获取监控任务列表
func (c *Client) GetMonitorTasks() ([]MonitorTask, error) {
	if c.agentID == "" {
		return nil, fmt.Errorf("agent id not set")
	}

	type Response struct {
		Code int `json:"code"`
		Data struct {
			Tasks []MonitorTask `json:"tasks"`
		} `json:"data"`
		Message string `json:"message"`
	}

	var result Response

	resp, err := c.client.R().
		SetResult(&result).
		Get(fmt.Sprintf("/agents/%s/monitors", c.agentID))

	if err != nil {
		return nil, err
	}

	if resp.IsError() {
		return nil, fmt.Errorf("get monitor tasks failed: %s", resp.String())
	}

	return result.Data.Tasks, nil
}

// GetAgentID 获取 Agent ID
func (c *Client) GetAgentID() string {
	return c.agentID
}
