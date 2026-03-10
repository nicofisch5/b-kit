<template>
  <div class="app-container">
    <div class="back-nav">
      <router-link :to="{ name: 'cycles', params: { orgSlug: $route.params.orgSlug } }" class="back-link">
        &larr; Cycles
      </router-link>
    </div>

    <div v-if="loading" class="loading-state">Loading…</div>

    <template v-else>
      <div class="page-header">
        <h1 class="page-title">{{ isNew ? 'New Cycle' : form.name }}</h1>
        <div class="header-actions" v-if="!isNew">
          <button class="btn btn-danger" @click="confirmDelete">Delete</button>
        </div>
      </div>

      <div v-if="error" class="alert-error">{{ error }}</div>
      <div v-if="successMsg" class="alert-success">{{ successMsg }}</div>

      <!-- Cycle form -->
      <div class="section">
        <form class="cycle-form" @submit.prevent="save">
          <div class="form-group">
            <label class="form-label">Name <span class="required">*</span></label>
            <input v-model="form.name" class="form-input" type="text" required placeholder="e.g. Pre-season 2026" />
          </div>

          <div class="form-group">
            <label class="form-label">Description</label>
            <textarea v-model="form.description" class="form-textarea" rows="3" placeholder="Goals, context, focus areas…"></textarea>
          </div>

          <div class="form-row">
            <div class="form-group">
              <label class="form-label">Start Date</label>
              <input v-model="form.startDate" class="form-input" type="date" />
            </div>
            <div class="form-group">
              <label class="form-label">End Date</label>
              <input v-model="form.endDate" class="form-input" type="date" />
            </div>
          </div>

          <div v-if="dateRangeError" class="alert-warn">{{ dateRangeError }}</div>

          <div class="form-group">
            <label class="form-label">Outcome</label>
            <textarea v-model="form.outcome" class="form-textarea" rows="3" placeholder="How did this cycle go? What was achieved?"></textarea>
          </div>

          <div class="form-actions">
            <button type="submit" class="btn btn-primary" :disabled="saving">
              {{ saving ? 'Saving…' : (isNew ? 'Create Cycle' : 'Save Changes') }}
            </button>
            <router-link :to="{ name: 'cycles', params: { orgSlug: $route.params.orgSlug } }" class="btn btn-secondary">
              Cancel
            </router-link>
          </div>
        </form>
      </div>

      <!-- Sessions in this cycle (edit mode only) -->
      <div class="section sessions-section" v-if="!isNew">
        <h2 class="section-title">Sessions in this Cycle ({{ sessions.length }})</h2>

        <div v-if="sessions.length === 0" class="empty-state-sm">No sessions linked to this cycle yet.</div>

        <div v-else class="session-list">
          <div
            v-for="session in sessions"
            :key="session.id"
            class="session-row"
            @click="goToSession(session.id)"
          >
            <span class="session-date">{{ formatDate(session.date) }}</span>
            <span class="session-goal">{{ session.goal ? truncate(session.goal, 80) : 'No goal' }}</span>
            <span class="session-meta">
              <span v-if="session.duration">{{ session.duration }} min</span>
              <span>{{ session.drillCount }} drill{{ session.drillCount !== 1 ? 's' : '' }}</span>
            </span>
          </div>
        </div>
      </div>
    </template>
  </div>
</template>

<script setup>
import { ref, computed, onMounted } from 'vue'
import { useRouter, useRoute } from 'vue-router'
import { cycleService } from '../utils/cycleService'

const router = useRouter()
const route  = useRoute()

const cycleId = route.params.cycleId
const isNew   = cycleId === 'new'

const loading    = ref(!isNew)
const saving     = ref(false)
const error      = ref(null)
const successMsg = ref(null)
const sessions   = ref([])

const form = ref({
  name:        '',
  description: '',
  startDate:   '',
  endDate:     '',
  outcome:     '',
})

const dateRangeError = computed(() => {
  if (form.value.startDate && form.value.endDate && form.value.endDate < form.value.startDate) {
    return 'End date must be after start date.'
  }
  return null
})

async function load() {
  try {
    const data = await cycleService.get(cycleId)
    form.value = {
      name:        data.name || '',
      description: data.description || '',
      startDate:   data.startDate || '',
      endDate:     data.endDate || '',
      outcome:     data.outcome || '',
    }
    sessions.value = data.sessions || []
  } catch (e) {
    error.value = e.message
  } finally {
    loading.value = false
  }
}

async function save() {
  if (dateRangeError.value) return
  error.value = null
  successMsg.value = null
  saving.value = true

  const payload = {
    name:        form.value.name,
    description: form.value.description || null,
    startDate:   form.value.startDate || null,
    endDate:     form.value.endDate || null,
    outcome:     form.value.outcome || null,
  }

  try {
    if (isNew) {
      const res = await cycleService.create(payload)
      router.push({ name: 'cycle-detail', params: { orgSlug: route.params.orgSlug, cycleId: res.data.id } })
    } else {
      await cycleService.update(cycleId, payload)
      successMsg.value = 'Cycle saved.'
    }
  } catch (e) {
    error.value = e.message
  } finally {
    saving.value = false
  }
}

async function confirmDelete() {
  if (!confirm(`Delete cycle "${form.value.name}"? Sessions linked to it will be unlinked.`)) return
  try {
    await cycleService.delete(cycleId)
    router.push({ name: 'cycles', params: { orgSlug: route.params.orgSlug } })
  } catch (e) {
    error.value = e.message
  }
}

function goToSession(id) {
  router.push({ name: 'training-session-detail', params: { orgSlug: route.params.orgSlug, sessionId: id } })
}

function formatDate(dateStr) {
  if (!dateStr) return ''
  const d = new Date(dateStr + 'T00:00:00')
  return d.toLocaleDateString(undefined, { weekday: 'short', year: 'numeric', month: 'short', day: 'numeric' })
}

function truncate(str, max) {
  return str && str.length > max ? str.slice(0, max) + '…' : str
}

onMounted(() => { if (!isNew) load() })
</script>

<style scoped>
.back-nav { margin-bottom: var(--spacing-md); }
.back-link { color: var(--text-muted); text-decoration: none; font-weight: 600; }
.back-link:hover { color: var(--primary-color); }

.page-header {
  display: flex;
  align-items: center;
  justify-content: space-between;
  margin-bottom: var(--spacing-lg);
  flex-wrap: wrap;
  gap: var(--spacing-sm);
}

.page-title {
  font-size: 1.75rem;
  font-weight: 700;
  color: var(--primary-color);
}

.header-actions { display: flex; gap: var(--spacing-sm); }

.section { margin-bottom: var(--spacing-xl); }

.sessions-section {
  border-top: 1px solid var(--border-color);
  padding-top: var(--spacing-lg);
}

.section-title {
  font-size: 1.1rem;
  font-weight: 700;
  color: var(--text-light);
  margin-bottom: var(--spacing-md);
}

.cycle-form {
  max-width: 640px;
  display: flex;
  flex-direction: column;
  gap: var(--spacing-md);
}

.form-row {
  display: grid;
  grid-template-columns: 1fr 1fr;
  gap: var(--spacing-md);
}

.form-group { display: flex; flex-direction: column; gap: var(--spacing-xs); }

.form-label { font-size: 0.85rem; font-weight: 600; color: var(--text-muted); }
.required { color: var(--error-color); }

.form-input {
  padding: var(--spacing-sm) var(--spacing-md);
  background: var(--bg-card);
  border: 1px solid var(--border-color);
  border-radius: var(--radius-md);
  color: var(--text-light);
  font-size: 0.9rem;
}

.form-input:focus { outline: none; border-color: var(--primary-color); }

.form-textarea {
  padding: var(--spacing-sm) var(--spacing-md);
  background: var(--bg-card);
  border: 1px solid var(--border-color);
  border-radius: var(--radius-md);
  color: var(--text-light);
  font-size: 0.9rem;
  resize: vertical;
  font-family: inherit;
}

.form-textarea:focus { outline: none; border-color: var(--primary-color); }

.form-actions { display: flex; gap: var(--spacing-sm); padding-top: var(--spacing-sm); }

/* Sessions list */
.session-list { display: flex; flex-direction: column; gap: var(--spacing-sm); }

.session-row {
  display: grid;
  grid-template-columns: 160px 1fr auto;
  gap: var(--spacing-md);
  align-items: center;
  background: var(--bg-card);
  border: 1px solid var(--border-color);
  border-radius: var(--radius-md);
  padding: var(--spacing-sm) var(--spacing-md);
  cursor: pointer;
  transition: border-color 0.15s;
}

.session-row:hover { border-color: var(--primary-color); }

.session-date { font-weight: 700; color: var(--primary-color); font-size: 0.85rem; }
.session-goal { font-size: 0.88rem; color: var(--text-light); overflow: hidden; text-overflow: ellipsis; white-space: nowrap; }
.session-meta { display: flex; flex-direction: column; align-items: flex-end; gap: 2px; font-size: 0.8rem; color: var(--text-muted); white-space: nowrap; }

.empty-state-sm { color: var(--text-muted); font-size: 0.9rem; padding: var(--spacing-md) 0; }
.loading-state { text-align: center; padding: var(--spacing-xl); color: var(--text-muted); }

.alert-error {
  background: rgba(239, 68, 68, 0.1);
  color: var(--error-color);
  padding: var(--spacing-sm) var(--spacing-md);
  border-radius: var(--radius-md);
  margin-bottom: var(--spacing-md);
}

.alert-success {
  background: rgba(34, 197, 94, 0.1);
  color: #4ade80;
  padding: var(--spacing-sm) var(--spacing-md);
  border-radius: var(--radius-md);
  margin-bottom: var(--spacing-md);
}

.alert-warn {
  background: rgba(251, 191, 36, 0.1);
  color: #fbbf24;
  padding: var(--spacing-sm) var(--spacing-md);
  border-radius: var(--radius-md);
}

.btn {
  padding: var(--spacing-sm) var(--spacing-md);
  border: none;
  border-radius: var(--radius-md);
  font-weight: 600;
  cursor: pointer;
  text-decoration: none;
  display: inline-flex;
  align-items: center;
}

.btn:disabled { opacity: 0.6; cursor: not-allowed; }
.btn-primary { background: var(--primary-color); color: white; }
.btn-secondary { background: var(--bg-card); color: var(--text-light); border: 1px solid var(--border-color); }
.btn-danger { background: var(--error-color); color: white; }

@media (max-width: 600px) {
  .form-row { grid-template-columns: 1fr; }
  .session-row { grid-template-columns: 1fr; }
}
</style>
