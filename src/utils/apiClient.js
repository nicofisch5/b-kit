import { authStore } from '../store/authStore'
import router from '../router'

const BASE = '/api/v1'

// Track in-flight GET requests so stale ones can be aborted when superseded
const pending = new Map()

async function request(method, path, body = undefined) {
  const key = `${method}:${path}`

  // Abort any previous in-flight request to the same GET endpoint
  if (method === 'GET' && pending.has(key)) {
    pending.get(key).abort()
  }

  const controller = new AbortController()
  if (method === 'GET') {
    pending.set(key, controller)
  }

  const headers = { 'Content-Type': 'application/json' }
  if (authStore.token) {
    headers['Authorization'] = `Bearer ${authStore.token}`
  }

  try {
    const res = await fetch(`${BASE}${path}`, {
      method,
      headers,
      body: body !== undefined ? JSON.stringify(body) : undefined,
      signal: controller.signal,
    })

    if (method === 'GET') pending.delete(key)

    if (res.status === 401) {
      authStore.logout()
      router.push('/login')
      throw new Error('Unauthenticated')
    }

    if (res.status === 204) return null

    const json = await res.json()
    if (!res.ok) {
      throw new Error(json.error ?? `HTTP ${res.status}`)
    }
    return json
  } catch (e) {
    if (method === 'GET') pending.delete(key)
    if (e.name === 'AbortError') throw new Error('Request superseded')
    throw e
  }
}

export const apiClient = {
  get:    (path)        => request('GET',    path),
  post:   (path, body)  => request('POST',   path, body),
  put:    (path, body)  => request('PUT',    path, body),
  delete: (path)        => request('DELETE', path),
}
