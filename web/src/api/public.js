import request from './request'

// 公共 API（无需认证）

export function getPublicAgentList(params) {
  return request.get('/public/agents', { params })
}

export function getPublicAgentStatistics() {
  return request.get('/public/agents/statistics')
}

export function getPublicAgentDetail(id) {
  return request.get(`/public/agents/${id}`)
}

export function getPublicAgentLatestMetrics(agentId, type) {
  const params = type ? { type } : {}
  return request.get(`/public/agents/${agentId}/metrics/latest`, { params })
}

export function getPublicMonitorList(params) {
  return request.get('/public/monitors', { params })
}

export function getPublicMonitorOverview() {
  return request.get('/public/monitors/overview')
}
