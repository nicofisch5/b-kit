<template>
  <div class="admin-page">
    <router-link to="/admin" class="back-link">← Organizations</router-link>

    <div v-if="loading" class="loading">Loading…</div>
    <div v-else-if="error" class="error-msg">{{ error }}</div>
    <template v-else>
      <div class="org-header">
        <div>
          <h2>{{ org.name }}</h2>
          <code class="slug">{{ org.slug }}</code>
        </div>
      </div>

      <!-- Users section -->
      <div class="section">
        <div class="section-header">
          <h3>Users</h3>
          <button class="btn-primary" @click="showCreate = true">+ Add User</button>
        </div>

        <table class="data-table">
          <thead>
            <tr><th>Email</th><th>Role</th><th>Created</th><th>Actions</th></tr>
          </thead>
          <tbody>
            <tr v-for="u in users" :key="u.id">
              <td>{{ u.email }}</td>
              <td><span class="role-badge" :class="u.role.toLowerCase().replace('role_', '')">{{ u.role.replace('ROLE_', '') }}</span></td>
              <td>{{ u.createdAt?.substring(0, 10) }}</td>
              <td class="actions">
                <button class="btn-sm btn-danger" @click="deleteUser(u)">Remove</button>
              </td>
            </tr>
            <tr v-if="users.length === 0">
              <td colspan="4" class="empty">No users yet.</td>
            </tr>
          </tbody>
        </table>
      </div>
    </template>

    <!-- Create user modal -->
    <div v-if="showCreate" class="modal-overlay" @click.self="showCreate = false">
      <div class="modal-box">
        <h3>Add User to {{ org?.name }}</h3>
        <div class="field">
          <label>Email</label>
          <input v-model="form.email" type="email" placeholder="coach@example.com" />
        </div>
        <div class="field">
          <label>Password</label>
          <input v-model="form.password" type="password" placeholder="••••••••" />
        </div>
        <div class="field">
          <label>Role</label>
          <select v-model="form.role">
            <option value="ROLE_COACH">Coach</option>
            <option value="ROLE_ADMIN">Admin</option>
          </select>
        </div>
        <p v-if="formError" class="error-msg">{{ formError }}</p>
        <div class="modal-actions">
          <button class="btn-secondary" @click="showCreate = false">Cancel</button>
          <button class="btn-primary" @click="createUser">Create</button>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, onMounted } from 'vue'
import { useRoute } from 'vue-router'
import { apiClient } from '../../utils/apiClient'

const route = useRoute()
const orgId = route.params.orgId

const org       = ref(null)
const users     = ref([])
const loading   = ref(true)
const error     = ref('')
const showCreate = ref(false)
const formError  = ref('')
const form = ref({ email: '', password: '', role: 'ROLE_COACH' })

async function load() {
  try {
    const [orgsRes, usersRes] = await Promise.all([
      apiClient.get('/admin/organizations'),
      apiClient.get(`/admin/organizations/${orgId}/users`),
    ])
    org.value   = orgsRes.data.find(o => o.id === orgId) ?? { name: orgId }
    users.value = usersRes.data
  } catch (e) {
    error.value = e.message
  } finally {
    loading.value = false
  }
}

async function createUser() {
  formError.value = ''
  try {
    const r = await apiClient.post(`/admin/organizations/${orgId}/users`, form.value)
    users.value.push(r.data)
    showCreate.value = false
    form.value = { email: '', password: '', role: 'ROLE_COACH' }
  } catch (e) {
    formError.value = e.message
  }
}

async function deleteUser(u) {
  if (!confirm(`Remove "${u.email}"?`)) return
  try {
    await apiClient.delete(`/admin/users/${u.id}`)
    users.value = users.value.filter(x => x.id !== u.id)
  } catch (e) {
    alert(e.message)
  }
}

onMounted(load)
</script>

<style scoped>
.admin-page { max-width: 900px; margin: 0 auto; }
.back-link { color: #888; font-size: 0.875rem; text-decoration: none; display: inline-block; margin-bottom: 1rem; }
.back-link:hover { color: #ff6b00; }
.org-header { display: flex; justify-content: space-between; align-items: flex-start; margin-bottom: 1.5rem; }
.org-header h2 { margin: 0 0 0.25rem; }
.slug { color: #888; font-size: 0.875rem; }
.section { margin-top: 2rem; }
.section-header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 1rem; }
.section-header h3 { margin: 0; }
.data-table { width: 100%; border-collapse: collapse; }
.data-table th, .data-table td { padding: 0.625rem 0.75rem; text-align: left; border-bottom: 1px solid #2a2a2a; }
.data-table th { color: #888; font-size: 0.8125rem; font-weight: 500; }
.empty { color: #666; font-style: italic; }
.actions { display: flex; gap: 0.5rem; }
.role-badge { font-size: 0.75rem; padding: 0.2rem 0.5rem; border-radius: 4px; font-weight: 600; }
.role-badge.admin { background: #1a3a1a; color: #6bff6b; }
.role-badge.coach { background: #1a1a3a; color: #6b9bff; }
.btn-primary { background: #ff6b00; border: none; border-radius: 6px; color: #fff; cursor: pointer; font-size: 0.875rem; font-weight: 600; padding: 0.5rem 1rem; }
.btn-secondary { background: #2a2a2a; border: none; border-radius: 6px; color: #ccc; cursor: pointer; font-size: 0.875rem; padding: 0.5rem 1rem; }
.btn-sm { background: #2a2a2a; border: none; border-radius: 4px; color: #ccc; cursor: pointer; font-size: 0.8125rem; padding: 0.3rem 0.625rem; }
.btn-danger { background: #3a1010; color: #ff6b6b; }
.loading { color: #888; }
.error-msg { color: #ff6b6b; font-size: 0.875rem; }
.modal-overlay { position: fixed; inset: 0; background: rgba(0,0,0,0.7); display: flex; align-items: center; justify-content: center; z-index: 100; }
.modal-box { background: #1a1a1a; border: 1px solid #333; border-radius: 12px; padding: 1.5rem; width: 100%; max-width: 400px; }
.modal-box h3 { margin: 0 0 1rem; }
.field { display: flex; flex-direction: column; gap: 0.375rem; margin-bottom: 1rem; }
.field label { font-size: 0.8125rem; color: #aaa; }
.field input, .field select { background: #252525; border: 1px solid #333; border-radius: 6px; color: #fff; font-size: 0.9375rem; padding: 0.5rem 0.75rem; outline: none; }
.field input:focus, .field select:focus { border-color: #ff6b00; }
.modal-actions { display: flex; justify-content: flex-end; gap: 0.75rem; margin-top: 1rem; }
</style>
