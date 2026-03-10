import { reactive, computed } from 'vue'

const TOKEN_KEY = 'bkit_token'
const USER_KEY  = 'bkit_user'

const state = reactive({
  token: localStorage.getItem(TOKEN_KEY) ?? null,
  user:  JSON.parse(localStorage.getItem(USER_KEY) ?? 'null'),
})

export const authStore = {
  // ── State ──────────────────────────────────────────
  get token()    { return state.token },
  get user()     { return state.user },
  get isLoggedIn() { return !!state.token },
  get role()     { return state.user?.role ?? null },
  get orgSlug()  { return state.user?.organizationSlug ?? null },
  get orgName()  { return state.user?.organizationName ?? null },
  get isSuperAdmin() { return state.user?.role === 'ROLE_SUPER_ADMIN' },
  get isAdmin()  { return state.user?.role === 'ROLE_ADMIN' || state.user?.role === 'ROLE_SUPER_ADMIN' },

  // ── Actions ─────────────────────────────────────────
  setAuth(token, user) {
    state.token = token
    state.user  = user
    localStorage.setItem(TOKEN_KEY, token)
    localStorage.setItem(USER_KEY, JSON.stringify(user))
  },

  logout() {
    state.token = null
    state.user  = null
    localStorage.removeItem(TOKEN_KEY)
    localStorage.removeItem(USER_KEY)
  },
}
