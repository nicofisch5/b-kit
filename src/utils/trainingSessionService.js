import { apiClient } from './apiClient'
import { authStore } from '../store/authStore'

export const trainingSessionService = {
  list: () => apiClient.get('/training-sessions').then(r => r.data),
  get: (id) => apiClient.get(`/training-sessions/${id}`).then(r => r.data),
  create: (body) => apiClient.post('/training-sessions', body).then(r => r.data),
  update: (id, body) => apiClient.put(`/training-sessions/${id}`, body),
  delete: (id) => apiClient.delete(`/training-sessions/${id}`),
  updateDrills: (id, drills) => apiClient.put(`/training-sessions/${id}/drills`, { drills }).then(r => r.data),

  exportPdf: async (id) => {
    const res = await fetch(`/api/v1/training-sessions/${id}/pdf`, {
      headers: { 'Authorization': `Bearer ${authStore.token}` },
    })
    if (!res.ok) throw new Error('PDF export failed')
    return res.blob()
  },
}
