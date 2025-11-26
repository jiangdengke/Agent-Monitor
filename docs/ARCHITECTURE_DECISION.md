# 架构决策记录

## 日期：2025-11-26

## 决策：采用 HTTP POST 接口架构

### 背景

原计划使用 WebSocket 长连接架构，但考虑到 WebSocket 在部署、负载均衡和维护上的复杂性，以及 Laravel 本身对 HTTP API 的优秀支持，决定**改为使用 HTTP POST 接口**进行数据上报。

### 变更原因

1. **简化架构**：
   - 移除对 Swoole/Octane 的硬性依赖，普通 Nginx/Apache + FPM 环境即可运行。
   - 减少了长连接维护的开销和断线重连的复杂处理。
2. **生态集成**：
   - HTTP API 更容易对接现有的 API 网关、日志系统和监控工具。
   - Laravel 的 API 资源、中间件、验证器更适合处理 HTTP 请求。
3. **前端推送**：
   - 即使 Agent 使用 HTTP 上报，仍然可以通过 Laravel Reverb 实现 Server 到 Frontend 的 WebSocket 推送，不影响用户体验。

### 决策内容

#### Server 端（Laravel）
- ✅ **不使用** Swoole/Octane 处理长连接（除非为了 HTTP 性能优化）。
- ✅ 提供标准的 RESTful API。
- ✅ 使用 `MetricController` 接收指标数据。
- ✅ 接收到数据后，触发 `MetricsReceived` 事件，通过 Reverb 推送到前端。

#### Agent 端（Go）
- ✅ 使用 HTTP Client (`net/http` 或 `resty`)。
- ✅ 定期（如 60秒）打包采集到的指标，POST 发送到 Server。
- ✅ 心跳接口和指标接口分离。
- ✅ 获取监控任务配置改为轮询或在心跳响应中携带。

### 通信协议

**1. 注册 (POST /api/agent/register)**
```json
{
  "hostname": "server-01",
  "ip": "192.168.1.100",
  ...
}
```

**2. 心跳 (POST /api/agent/heartbeat)**
```json
{
  "agentId": "uuid..."
}
```

**3. 指标上报 (POST /api/agent/metrics)**
```json
{
  "agentId": "uuid...",
  "metrics": [
    {
      "type": "cpu",
      "data": { "usagePercent": 45.2, ... },
      "collectedAt": "2025-11-26T10:00:00Z"
    }
  ]
}
```

### 对比分析

| 特性 | WebSocket (旧方案) | HTTP POST (新方案) |
|------|-------------------|-------------------|
| **连接状态** | 有状态 (Stateful) | 无状态 (Stateless) |
| **部署难度** | 高 (需配置 WS 端口/反代) | 低 (标准 Web 服务) |
| **负载均衡** | 复杂 (需会话保持) | 简单 (轮询/随机) |
| **实时性** | 极高 (毫秒级) | 高 (取决于上报频率) |
| **资源消耗** | 维持 TCP 连接消耗内存 | 每次请求消耗 CPU |
| **适用性** | 极高频/双向通信 | 周期性上报 |

**最终选择**：**HTTP POST**

### 后续实施计划

1. **API 开发**：完善 `AgentController` 和 `MetricController`。
2. **文档更新**：更新 API 文档和集成指南。
3. **Go Agent**：开发基于 HTTP 的 Go Agent 客户端。

---

**状态**：✅ 已生效
**生效日期**：2025-11-26