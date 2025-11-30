import request from './request'

export function getApiKeyList(params) {
  return request.get('/api-keys', { params })
}

export function getApiKey(id) {
  return request.get(`/api-keys/${id}`)
}

export function createApiKey(data) {
  return request.post('/api-keys', data)
}

export function updateApiKey(id, data) {
  return request.put(`/api-keys/${id}`, data)
}

export function deleteApiKey(id) {
  return request.delete(`/api-keys/${id}`)
}

export function enableApiKey(id) {
  return request.post(`/api-keys/${id}/enable`)
}

export function disableApiKey(id) {
  return request.post(`/api-keys/${id}/disable`)
}

export function regenerateApiKey(id) {
  return request.post(`/api-keys/${id}/regenerate`)
}
