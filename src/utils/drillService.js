import { apiClient } from './apiClient'

export const drillService = {
  list: (params = {}) => {
    const qs = new URLSearchParams(params).toString()
    return apiClient.get(`/drills${qs ? '?' + qs : ''}`).then(r => r.data)
  },
  get: (id) => apiClient.get(`/drills/${id}`).then(r => r.data),
  create: (body) => apiClient.post('/drills', body).then(r => r.data),
  update: (id, body) => apiClient.put(`/drills/${id}`, body).then(r => r.data),
  delete: (id) => apiClient.delete(`/drills/${id}`),
}
