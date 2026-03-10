import { describe, it, expect, vi, beforeEach, afterEach } from 'vitest'

// ── Mocks ─────────────────────────────────────────────────────────────────────

// Mock the authStore so apiClient can import it without a full Vue app
vi.mock('../../store/authStore', () => ({
  authStore: { token: 'test-token', logout: vi.fn() },
}))

// Mock the router so the 401 redirect doesn't crash
vi.mock('../../../router', () => ({
  default: { push: vi.fn() },
}))

// Import after mocks are set up
const { apiClient } = await import('../apiClient')

// ── Helpers ───────────────────────────────────────────────────────────────────

function makeResponse(data, status = 200) {
  return new Response(JSON.stringify(data), {
    status,
    headers: { 'Content-Type': 'application/json' },
  })
}

beforeEach(() => {
  vi.stubGlobal('fetch', vi.fn())
})

afterEach(() => {
  vi.restoreAllMocks()
})

// ── Tests ─────────────────────────────────────────────────────────────────────

describe('apiClient.get', () => {
  it('returns parsed JSON data on success', async () => {
    fetch.mockResolvedValueOnce(makeResponse({ data: [1, 2, 3] }))

    const result = await apiClient.get('/stats/players')
    expect(result).toEqual({ data: [1, 2, 3] })
  })

  it('throws when response is not ok', async () => {
    fetch.mockResolvedValueOnce(makeResponse({ error: 'Not found' }, 404))

    await expect(apiClient.get('/players/missing')).rejects.toThrow('Not found')
  })

  it('aborts a superseded GET request to the same path', async () => {
    let firstAborted = false

    // First fetch: hangs until its signal is aborted
    fetch.mockImplementationOnce((_url, { signal }) => {
      return new Promise((_resolve, reject) => {
        signal.addEventListener('abort', () => {
          firstAborted = true
          reject(new DOMException('Aborted', 'AbortError'))
        })
      })
    })

    // Second fetch: resolves immediately
    fetch.mockResolvedValueOnce(makeResponse({ data: 'fresh' }))

    const first  = apiClient.get('/stats/players?teamId=1')
    const second = apiClient.get('/stats/players?teamId=1')

    await expect(second).resolves.toEqual({ data: 'fresh' })
    await expect(first).rejects.toThrow('Request superseded')
    expect(firstAborted).toBe(true)
  })

  it('does NOT abort a pending GET for a DIFFERENT path', async () => {
    let pathAAborted = false

    fetch.mockImplementationOnce((_url, { signal }) => {
      return new Promise((resolve, reject) => {
        signal.addEventListener('abort', () => {
          pathAAborted = true
          reject(new DOMException('Aborted', 'AbortError'))
        })
        // Resolve normally after a tiny delay so second fetch can fire
        setTimeout(() => resolve(makeResponse({ data: 'A' })), 10)
      })
    })

    fetch.mockResolvedValueOnce(makeResponse({ data: 'B' }))

    const pathA = apiClient.get('/stats/players')
    const pathB = apiClient.get('/stats/teams')

    const [a, b] = await Promise.all([pathA, pathB])
    expect(a).toEqual({ data: 'A' })
    expect(b).toEqual({ data: 'B' })
    expect(pathAAborted).toBe(false)
  })
})

describe('apiClient.post', () => {
  it('sends JSON body and returns parsed response', async () => {
    fetch.mockResolvedValueOnce(makeResponse({ data: { id: '123' } }, 201))

    const result = await apiClient.post('/players', { firstname: 'John', lastname: 'Doe' })

    expect(result).toEqual({ data: { id: '123' } })

    const [url, options] = fetch.mock.calls[0]
    expect(url).toBe('/api/v1/players')
    expect(options.method).toBe('POST')
    expect(JSON.parse(options.body)).toEqual({ firstname: 'John', lastname: 'Doe' })
  })

  it('includes Authorization header when token is set', async () => {
    fetch.mockResolvedValueOnce(makeResponse({ data: {} }))

    await apiClient.post('/players', {})

    const [, options] = fetch.mock.calls[0]
    expect(options.headers['Authorization']).toBe('Bearer test-token')
  })
})

describe('apiClient.delete', () => {
  it('returns null for 204 No Content', async () => {
    fetch.mockResolvedValueOnce(new Response(null, { status: 204 }))

    const result = await apiClient.delete('/players/123')
    expect(result).toBeNull()
  })
})
