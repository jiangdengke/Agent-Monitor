import request from './request'

export function getMonitorList(params) {
  return request.get('/monitors', { params })
}

export function getMonitor(id) {
  return request.get(`/monitors/${id}`)
}

export function createMonitor(data) {
  return request.post('/monitors', data)
}

export function updateMonitor(id, data) {
  return request.put(`/monitors/${id}`, data)
}

export function deleteMonitor(id) {
  return request.delete(`/monitors/${id}`)
}

export function getMonitorStats(id, agentId) {
  const params = agentId ? { agent_id: agentId } : {}
  return request.get(`/monitors/${id}/stats`, { params })
}

export function getMonitorHistory(id, params) {
  return request.get(`/monitors/${id}/history`, { params })
}

export function getMonitorOverview() {
  return request.get('/monitors/overview')
}
