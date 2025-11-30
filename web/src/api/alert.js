import request from './request'

export function getAlertConfigs(agentId) {
  return request.get(`/agents/${agentId}/alert-configs`)
}

export function getAlertConfig(id) {
  return request.get(`/alert-configs/${id}`)
}

export function createAlertConfig(data) {
  return request.post('/alert-configs', data)
}

export function updateAlertConfig(id, data) {
  return request.put(`/alert-configs/${id}`, data)
}

export function deleteAlertConfig(id) {
  return request.delete(`/alert-configs/${id}`)
}

export function getAlertRecords(params) {
  return request.get('/alert-records', { params })
}
