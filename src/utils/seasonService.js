import { apiClient } from './apiClient'

export const seasonService = {
  list: () => apiClient.get('/seasons').then(r => r.data),
  get: (id) => apiClient.get(`/seasons/${id}`).then(r => r.data),
  create: (name) => apiClient.post('/seasons', { name }).then(r => r.data),
  update: (id, name) => apiClient.put(`/seasons/${id}`, { name }).then(r => r.data),
  delete: (id) => apiClient.delete(`/seasons/${id}`).then(r => r.data),
  listChampionships: (id) => apiClient.get(`/seasons/${id}/championships`).then(r => r.data),
  addChampionship: (seasonId, championshipId) => apiClient.post(`/seasons/${seasonId}/championships`, { championshipId }).then(r => r.data),
  removeChampionship: (seasonId, champId) => apiClient.delete(`/seasons/${seasonId}/championships/${champId}`).then(r => r.data),
}

export const championshipService = {
  list: () => apiClient.get('/championships').then(r => r.data),
  get: (id) => apiClient.get(`/championships/${id}`).then(r => r.data),
  create: (name) => apiClient.post('/championships', { name }).then(r => r.data),
  update: (id, name) => apiClient.put(`/championships/${id}`, { name }).then(r => r.data),
  delete: (id) => apiClient.delete(`/championships/${id}`).then(r => r.data),
  listTeams: (id) => apiClient.get(`/championships/${id}/teams`).then(r => r.data),
  addTeam: (champId, teamId, groupName) => apiClient.post(`/championships/${champId}/teams`, { teamId, groupName }).then(r => r.data),
  updateTeam: (champId, teamId, groupName) => apiClient.put(`/championships/${champId}/teams/${teamId}`, { groupName }).then(r => r.data),
  removeTeam: (champId, teamId) => apiClient.delete(`/championships/${champId}/teams/${teamId}`).then(r => r.data),
  listGames: (id) => apiClient.get(`/championships/${id}/games`).then(r => r.data),
  linkGame: (champId, gameId) => apiClient.post(`/championships/${champId}/games`, { gameId }).then(r => r.data),
  unlinkGame: (champId, gameId) => apiClient.delete(`/championships/${champId}/games/${gameId}`).then(r => r.data),
}
