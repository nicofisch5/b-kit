<template>
  <div class="admin-page">
    <div class="admin-header">
      <h2>Organizations</h2>
      <button class="btn-primary" @click="showCreate = true">+ New Organization</button>
    </div>

    <div v-if="loading" class="loading">Loading…</div>
    <div v-else-if="error" class="error-msg">{{ error }}</div>

    <table v-else class="data-table">
      <thead>
        <tr>
          <th>Name</th>
          <th>Slug</th>
          <th>Created</th>
          <th>Actions</th>
        </tr>
      </thead>
      <tbody>
        <tr v-for="org in orgs" :key="org.id">
          <td>{{ org.name }}</td>
          <td><code>{{ org.slug }}</code></td>
          <td>{{ org.createdAt?.substring(0, 10) }}</td>
          <td class="actions">
            <router-link :to="`/admin/organizations/${org.id}`" class="btn-sm">Manage</router-link>
            <button class="btn-sm btn-danger" @click="deleteOrg(org)">Delete</button>
          </td>
        </tr>
        <tr v-if="orgs.length === 0">
          <td colspan="4" class="empty">No organizations yet.</td>
        </tr>
      </tbody>
    </table>

    <!-- Create modal -->
    <div v-if="showCreate" class="modal-overlay" @click.self="showCreate = false">
      <div class="modal-box">
        <h3>New Organization</h3>
        <div class="field">
          <label>Name</label>
          <input v-model="form.name" placeholder="Paris Basketball" />
        </div>
        <div class="field">
          <label>Slug</label>
          <input v-model="form.slug" placeholder="paris-basketball" />
        </div>
        <p v-if="formError" class="error-msg">{{ formError }}</p>
        <div class="modal-actions">
          <button class="btn-secondary" @click="showCreate = false">Cancel</button>
          <button class="btn-primary" @click="createOrg">Create</button>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, onMounted, watch } from 'vue'
import { apiClient } from '../../utils/apiClient'

const orgs      = ref([])
const loading   = ref(true)
const error     = ref('')
const showCreate = ref(false)
const formError  = ref('')
const form = ref({ name: '', slug: '' })

// Auto-generate slug from name
watch(() => form.value.name, (name) => {
  form.value.slug = name.toLowerCase().replace(/[^a-z0-9]+/g, '-').replace(/^-|-$/g, '')
})

async function load() {
  try {
    const r = await apiClient.get('/admin/organizations')
    orgs.value = r.data
  } catch (e) {
    error.value = e.message
  } finally {
    loading.value = false
  }
}

async function createOrg() {
  formError.value = ''
  try {
    const r = await apiClient.post('/admin/organizations', form.value)
    orgs.value.push(r.data)
    showCreate.value = false
    form.value = { name: '', slug: '' }
  } catch (e) {
    formError.value = e.message
  }
}

async function deleteOrg(org) {
  if (!confirm(`Delete "${org.name}"? This cannot be undone.`)) return
  try {
    await apiClient.delete(`/admin/organizations/${org.id}`)
    orgs.value = orgs.value.filter(o => o.id !== org.id)
  } catch (e) {
    alert(e.message)
  }
}

onMounted(load)
</script>

<style scoped>
.admin-page { max-width: 900px; margin: 0 auto; }
.admin-header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 1.5rem; }
.admin-header h2 { margin: 0; font-size: 1.5rem; }
.data-table { width: 100%; border-collapse: collapse; }
.data-table th, .data-table td { padding: 0.625rem 0.75rem; text-align: left; border-bottom: 1px solid #2a2a2a; }
.data-table th { color: #888; font-size: 0.8125rem; font-weight: 500; }
.empty { color: #666; font-style: italic; }
.actions { display: flex; gap: 0.5rem; }
.btn-primary { background: #ff6b00; border: none; border-radius: 6px; color: #fff; cursor: pointer; font-size: 0.875rem; font-weight: 600; padding: 0.5rem 1rem; }
.btn-secondary { background: #2a2a2a; border: none; border-radius: 6px; color: #ccc; cursor: pointer; font-size: 0.875rem; padding: 0.5rem 1rem; }
.btn-sm { background: #2a2a2a; border: none; border-radius: 4px; color: #ccc; cursor: pointer; font-size: 0.8125rem; padding: 0.3rem 0.625rem; text-decoration: none; display: inline-block; }
.btn-danger { background: #3a1010; color: #ff6b6b; }
.loading { color: #888; }
.error-msg { color: #ff6b6b; font-size: 0.875rem; }
.modal-overlay { position: fixed; inset: 0; background: rgba(0,0,0,0.7); display: flex; align-items: center; justify-content: center; z-index: 100; }
.modal-box { background: #1a1a1a; border: 1px solid #333; border-radius: 12px; padding: 1.5rem; width: 100%; max-width: 400px; }
.modal-box h3 { margin: 0 0 1rem; }
.field { display: flex; flex-direction: column; gap: 0.375rem; margin-bottom: 1rem; }
.field label { font-size: 0.8125rem; color: #aaa; }
.field input { background: #252525; border: 1px solid #333; border-radius: 6px; color: #fff; font-size: 0.9375rem; padding: 0.5rem 0.75rem; outline: none; }
.field input:focus { border-color: #ff6b00; }
.modal-actions { display: flex; justify-content: flex-end; gap: 0.75rem; margin-top: 1rem; }
</style>
