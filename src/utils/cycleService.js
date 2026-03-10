import { apiClient } from './apiClient'

export const cycleService = {
  list: () => apiClient.get('/cycles').then(r => r.data),
  get: (id) => apiClient.get(`/cycles/${id}`).then(r => r.data),
  create: (body) => apiClient.post('/cycles', body).then(r => r),
  update: (id, body) => apiClient.put(`/cycles/${id}`, body).then(r => r.data),
  delete: (id) => apiClient.delete(`/cycles/${id}`),
}
