<template>
  <div class="users-page">
    <div class="page-header">
      <h2>Team Management</h2>
      <button class="btn-primary" @click="showCreate = true">+ Add Coach</button>
    </div>

    <div v-if="loading" class="loading">Loading…</div>
    <div v-else-if="error" class="error-msg">{{ error }}</div>

    <table v-else class="data-table">
      <thead>
        <tr><th>Email</th><th>Role</th><th>Created</th><th>Actions</th></tr>
      </thead>
      <tbody>
        <tr v-for="u in users" :key="u.id">
          <td>{{ u.email }}</td>
          <td><span class="role-badge" :class="roleCss(u.role)">{{ u.role.replace('ROLE_', '') }}</span></td>
          <td>{{ u.createdAt?.substring(0, 10) }}</td>
          <td class="actions">
            <button class="btn-sm" @click="openAssign(u)">Assign</button>
            <button class="btn-sm btn-danger" @click="deleteUser(u)">Remove</button>
          </td>
        </tr>
        <tr v-if="users.length === 0">
          <td colspan="4" class="empty">No users yet. Add your first coach.</td>
        </tr>
      </tbody>
    </table>

    <!-- Create coach modal -->
    <div v-if="showCreate" class="modal-overlay" @click.self="showCreate = false">
      <div class="modal-box">
        <h3>Add Coach</h3>
        <div class="field">
          <label>Email</label>
          <input v-model="form.email" type="email" placeholder="coach@example.com" />
        </div>
        <div class="field">
          <label>Password</label>
          <input v-model="form.password" type="password" placeholder="••••••••" />
        </div>
        <p v-if="formError" class="error-msg">{{ formError }}</p>
        <div class="modal-actions">
          <button class="btn-secondary" @click="showCreate = false">Cancel</button>
          <button class="btn-primary" @click="createUser">Create</button>
        </div>
      </div>
    </div>

    <!-- Assign teams/championships modal -->
    <div v-if="assignTarget" class="modal-overlay" @click.self="assignTarget = null">
      <div class="modal-box modal-wide">
        <h3>Assignments — {{ assignTarget.email }}</h3>

        <div class="assign-section">
          <h4>Teams</h4>
          <div class="assign-list">
            <label v-for="t in allTeams" :key="t.id" class="assign-row">
              <input type="checkbox" :checked="assignedTeamIds.has(t.id)" @change="toggleTeam(t.id, $event.target.checked)" />
              {{ t.name }} <span class="cat">{{ t.category }}</span>
            </label>
          </div>
        </div>

        <div class="assign-section">
          <h4>Championships</h4>
          <div class="assign-list">
            <label v-for="c in allChamps" :key="c.id" class="assign-row">
              <input type="checkbox" :checked="assignedChampIds.has(c.id)" @change="toggleChamp(c.id, $event.target.checked)" />
              {{ c.name }}
            </label>
          </div>
        </div>

        <div class="modal-actions">
          <button class="btn-secondary" @click="assignTarget = null">Close</button>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, onMounted, computed } from 'vue'
import { apiClient } from '../utils/apiClient'
import { teamService } from '../utils/teamService'
import { championshipService } from '../utils/seasonService'

const users     = ref([])
const allTeams  = ref([])
const allChamps = ref([])
const loading   = ref(true)
const error     = ref('')
const showCreate = ref(false)
const formError  = ref('')
const form = ref({ email: '', password: '' })

const assignTarget     = ref(null)
const assignedTeamIds  = ref(new Set())
const assignedChampIds = ref(new Set())

function roleCss(role) {
  return role.toLowerCase().replace('role_', '')
}

async function load() {
  try {
    const [ur, tr, cr] = await Promise.all([
      apiClient.get('/org/users'),
      teamService.list(),
      championshipService.list(),
    ])
    users.value    = ur.data
    allTeams.value  = tr
    allChamps.value = cr
  } catch (e) {
    error.value = e.message
  } finally {
    loading.value = false
  }
}

async function createUser() {
  formError.value = ''
  try {
    const r = await apiClient.post('/org/users', form.value)
    users.value.push(r.data)
    showCreate.value = false
    form.value = { email: '', password: '' }
  } catch (e) {
    formError.value = e.message
  }
}

async function deleteUser(u) {
  if (!confirm(`Remove "${u.email}"?`)) return
  try {
    await apiClient.delete(`/org/users/${u.id}`)
    users.value = users.value.filter(x => x.id !== u.id)
  } catch (e) {
    alert(e.message)
  }
}

async function openAssign(u) {
  assignTarget.value = u
  const [tr, cr] = await Promise.all([
    apiClient.get(`/org/users/${u.id}/teams`),
    apiClient.get(`/org/users/${u.id}/championships`),
  ])
  assignedTeamIds.value  = new Set(tr.data.map(t => t.id))
  assignedChampIds.value = new Set(cr.data.map(c => c.id))
}

async function toggleTeam(teamId, checked) {
  const uid = assignTarget.value.id
  if (checked) {
    await apiClient.post(`/org/users/${uid}/teams`, { teamId })
    assignedTeamIds.value = new Set([...assignedTeamIds.value, teamId])
  } else {
    await apiClient.delete(`/org/users/${uid}/teams/${teamId}`)
    assignedTeamIds.value = new Set([...assignedTeamIds.value].filter(id => id !== teamId))
  }
}

async function toggleChamp(champId, checked) {
  const uid = assignTarget.value.id
  if (checked) {
    await apiClient.post(`/org/users/${uid}/championships`, { championshipId: champId })
    assignedChampIds.value = new Set([...assignedChampIds.value, champId])
  } else {
    await apiClient.delete(`/org/users/${uid}/championships/${champId}`)
    assignedChampIds.value = new Set([...assignedChampIds.value].filter(id => id !== champId))
  }
}

onMounted(load)
</script>

<style scoped>
.users-page { max-width: 900px; margin: 0 auto; }
.page-header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 1.5rem; }
.page-header h2 { margin: 0; }
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
.modal-box { background: #1a1a1a; border: 1px solid #333; border-radius: 12px; padding: 1.5rem; width: 100%; max-width: 420px; }
.modal-wide { max-width: 560px; }
.modal-box h3 { margin: 0 0 1rem; }
.field { display: flex; flex-direction: column; gap: 0.375rem; margin-bottom: 1rem; }
.field label { font-size: 0.8125rem; color: #aaa; }
.field input { background: #252525; border: 1px solid #333; border-radius: 6px; color: #fff; font-size: 0.9375rem; padding: 0.5rem 0.75rem; outline: none; }
.field input:focus { border-color: #ff6b00; }
.modal-actions { display: flex; justify-content: flex-end; gap: 0.75rem; margin-top: 1rem; }
.assign-section { margin-bottom: 1.25rem; }
.assign-section h4 { margin: 0 0 0.625rem; color: #aaa; font-size: 0.875rem; }
.assign-list { display: flex; flex-direction: column; gap: 0.375rem; max-height: 180px; overflow-y: auto; }
.assign-row { display: flex; align-items: center; gap: 0.5rem; cursor: pointer; font-size: 0.9rem; }
.cat { color: #666; font-size: 0.8rem; }
</style>
