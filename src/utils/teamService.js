import { apiClient } from './apiClient'

// ── Teams ──────────────────────────────────────────────
export const teamService = {
  list: () => apiClient.get('/teams').then(r => r.data),
  get: (id) => apiClient.get(`/teams/${id}`).then(r => r.data),
  create: (data) => apiClient.post('/teams', data).then(r => r.data),
  update: (id, data) => apiClient.put(`/teams/${id}`, data).then(r => r.data),
  delete: (id) => apiClient.delete(`/teams/${id}`).then(r => r.data),

  listPlayers: (teamId) => apiClient.get(`/teams/${teamId}/players`).then(r => r.data),
  addPlayer: (teamId, playerId) => apiClient.post(`/teams/${teamId}/players`, { playerId }).then(r => r.data),
  removePlayer: (teamId, playerId) => apiClient.delete(`/teams/${teamId}/players/${playerId}`).then(r => r.data),
}

// ── Players ────────────────────────────────────────────
export const playerService = {
  list: ({ teamId, category, search } = {}) => {
    const params = new URLSearchParams()
    if (teamId) params.set('teamId', teamId)
    if (category) params.set('category', category)
    if (search) params.set('search', search)
    const qs = params.toString()
    return apiClient.get(`/players${qs ? '?' + qs : ''}`).then(r => r.data)
  },
  get: (id) => apiClient.get(`/players/${id}`).then(r => r.data),
  create: (data) => apiClient.post('/players', data).then(r => r.data),
  update: (id, data) => apiClient.put(`/players/${id}`, data).then(r => r.data),
  delete: (id) => apiClient.delete(`/players/${id}`).then(r => r.data),
}
