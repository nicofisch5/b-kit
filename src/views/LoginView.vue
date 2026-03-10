<template>
  <div class="login-page">
    <div class="login-card">
      <h1 class="login-title">B-Strack</h1>
      <p class="login-subtitle">Basketball Stats Tracker</p>

      <form @submit.prevent="submit" class="login-form">
        <div class="field">
          <label>Email</label>
          <input
            v-model="email"
            type="email"
            autocomplete="email"
            placeholder="you@example.com"
            required
          />
        </div>

        <div class="field">
          <label>Password</label>
          <input
            v-model="password"
            type="password"
            autocomplete="current-password"
            placeholder="••••••••"
            required
          />
        </div>

        <p v-if="error" class="login-error">{{ error }}</p>

        <button type="submit" class="login-btn" :disabled="loading">
          {{ loading ? 'Signing in…' : 'Sign in' }}
        </button>
      </form>
    </div>
  </div>
</template>

<script setup>
import { ref } from 'vue'
import { useRouter } from 'vue-router'
import { authStore } from '../store/authStore'

const router = useRouter()
const email    = ref('')
const password = ref('')
const error    = ref('')
const loading  = ref(false)

async function submit() {
  error.value   = ''
  loading.value = true
  try {
    const res = await fetch('/api/v1/auth/login', {
      method: 'POST',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify({ email: email.value, password: password.value }),
    })
    const json = await res.json()
    if (!res.ok) {
      error.value = json.message ?? 'Invalid credentials'
      return
    }

    // Get user info from /me
    const meRes = await fetch('/api/v1/auth/me', {
      headers: { Authorization: `Bearer ${json.token}` },
    })
    const me = await meRes.json()

    authStore.setAuth(json.token, me.data)

    // Redirect
    if (authStore.isSuperAdmin) {
      router.push('/admin')
    } else {
      router.push(`/organization-${authStore.orgSlug}`)
    }
  } catch (e) {
    error.value = 'Network error. Please try again.'
  } finally {
    loading.value = false
  }
}
</script>

<style scoped>
.login-page {
  min-height: 100vh;
  display: flex;
  align-items: center;
  justify-content: center;
  background: #0a0a0a;
  padding: 1rem;
}

.login-card {
  background: #141414;
  border: 1px solid #2a2a2a;
  border-radius: 12px;
  padding: 2.5rem 2rem;
  width: 100%;
  max-width: 380px;
}

.login-title {
  font-size: 2rem;
  font-weight: 700;
  color: #ff6b00;
  margin: 0 0 0.25rem;
  text-align: center;
}

.login-subtitle {
  color: #888;
  font-size: 0.875rem;
  text-align: center;
  margin: 0 0 2rem;
}

.login-form {
  display: flex;
  flex-direction: column;
  gap: 1.25rem;
}

.field {
  display: flex;
  flex-direction: column;
  gap: 0.375rem;
}

.field label {
  font-size: 0.8125rem;
  color: #aaa;
  font-weight: 500;
}

.field input {
  background: #1e1e1e;
  border: 1px solid #333;
  border-radius: 8px;
  color: #fff;
  font-size: 0.9375rem;
  padding: 0.625rem 0.875rem;
  outline: none;
  transition: border-color 0.2s;
}

.field input:focus {
  border-color: #ff6b00;
}

.login-error {
  background: #3a1010;
  border: 1px solid #5a2020;
  border-radius: 6px;
  color: #ff6b6b;
  font-size: 0.875rem;
  padding: 0.5rem 0.75rem;
  margin: 0;
}

.login-btn {
  background: #ff6b00;
  border: none;
  border-radius: 8px;
  color: #fff;
  cursor: pointer;
  font-size: 0.9375rem;
  font-weight: 600;
  padding: 0.75rem;
  transition: background 0.2s;
}

.login-btn:hover:not(:disabled) {
  background: #e05a00;
}

.login-btn:disabled {
  opacity: 0.6;
  cursor: not-allowed;
}
</style>
