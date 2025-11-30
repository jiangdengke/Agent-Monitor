import request from './request'

export function getProperties() {
  return request.get('/properties')
}

export function getProperty(id) {
  return request.get(`/properties/${id}`)
}

export function setProperty(data) {
  return request.post('/properties', data)
}

export function setPropertiesBatch(items) {
  return request.post('/properties/batch', { items })
}

export function deleteProperty(id) {
  return request.delete(`/properties/${id}`)
}
