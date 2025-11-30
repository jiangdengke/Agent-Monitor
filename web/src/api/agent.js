import request from './request'

export function getAgentList(params) {
  return request.get('/agents', { params })
}

export function getAgentDetail(id) {
  return request.get(`/agents/${id}`)
}

export function updateAgent(id, data) {
  return request.put(`/agents/${id}`, data)
}

export function getAgentStatistics() {
  return request.get('/agents/statistics')
}

export function getAgentMetrics(agentId, params) {
  return request.get(`/agents/${agentId}/metrics`, { params })
}

export function getAgentLatestMetrics(agentId, type) {
  const params = type ? { type } : {}
  return request.get(`/agents/${agentId}/metrics/latest`, { params })
}
