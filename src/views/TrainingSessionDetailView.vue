<template>
  <div class="app-container">
    <div class="back-nav">
      <router-link :to="{ name: 'training-sessions', params: { orgSlug: $route.params.orgSlug } }" class="back-link">
        &larr; Training Sessions
      </router-link>
    </div>

    <div v-if="loading" class="loading-state">Loading…</div>

    <template v-else>
      <!-- Page header -->
      <div class="page-header">
        <h1 class="page-title">{{ isNew ? 'New Training Session' : formatDate(form.date) }}</h1>
        <div class="header-actions" v-if="!isNew">
          <button class="btn btn-secondary" @click="exportPdf" :disabled="exporting">
            {{ exporting ? 'Generating…' : '&#128196; Export PDF' }}
          </button>
          <button class="btn btn-danger" @click="confirmDelete">Delete</button>
        </div>
      </div>

      <div v-if="error" class="alert-error">{{ error }}</div>
      <div v-if="successMsg" class="alert-success">{{ successMsg }}</div>

      <!-- Section 1: Session metadata -->
      <div class="section">
        <h2 class="section-title">Session Details</h2>
        <form class="session-form" @submit.prevent="save">
          <div class="form-row">
            <div class="form-group">
              <label class="form-label">Date <span class="required">*</span></label>
              <input v-model="form.date" class="form-input" type="date" required />
            </div>
            <div class="form-group">
              <label class="form-label">Duration (min)</label>
              <input v-model.number="form.duration" class="form-input" type="number" min="1" placeholder="e.g. 90" />
            </div>
          </div>

          <div class="form-group">
            <label class="form-label">Goal</label>
            <textarea v-model="form.goal" class="form-textarea" rows="2" placeholder="What do you want to achieve in this session?"></textarea>
          </div>

          <div class="form-group">
            <label class="form-label">Comments</label>
            <textarea v-model="form.comments" class="form-textarea" rows="3" placeholder="Post-session notes, observations…"></textarea>
          </div>

          <div class="form-group">
            <label class="form-label">Cycle</label>
            <select v-model="form.cycleId" class="form-input">
              <option :value="null">— No cycle —</option>
              <option v-for="cycle in cycles" :key="cycle.id" :value="cycle.id">
                {{ cycle.name }}{{ cycle.startDate ? ' (' + cycle.startDate + ' → ' + (cycle.endDate ?? '?') + ')' : '' }}
              </option>
            </select>
          </div>

          <div v-if="cycleWarnings.length" class="alert-warn">
            <div v-for="w in cycleWarnings" :key="w">&#9888; {{ w }}</div>
          </div>

          <div class="form-actions">
            <button type="submit" class="btn btn-primary" :disabled="saving">
              {{ saving ? 'Saving…' : (isNew ? 'Create Session' : 'Save Changes') }}
            </button>
            <router-link :to="{ name: 'training-sessions', params: { orgSlug: $route.params.orgSlug } }" class="btn btn-secondary">
              Cancel
            </router-link>
          </div>
        </form>
      </div>

      <!-- Section 2: Drills (only when editing) -->
      <div class="section drills-section" v-if="!isNew">
        <div class="section-header">
          <h2 class="section-title">Drills in Session ({{ sessionDrills.length }})</h2>
        </div>

        <div v-if="drillsError" class="alert-error">{{ drillsError }}</div>

        <!-- Draggable drill list -->
        <div class="drills-list" v-if="sessionDrills.length">
          <div
            v-for="(item, index) in sessionDrills"
            :key="item.drillId + '-' + index"
            class="drill-row"
            draggable="true"
            @dragstart="onDragStart(index)"
            @dragover="onDragOver"
            @drop="onDrop(index)"
            :class="{ 'drag-over': dragIndex !== null && dragIndex !== index }"
          >
            <span class="drag-handle" title="Drag to reorder">&#8942;</span>
            <span class="code-badge">{{ item.drill.code }}</span>
            <span class="drill-name">{{ item.drill.name }}</span>
            <textarea
              v-model="item.note"
              class="note-input"
              rows="1"
              placeholder="Note for this drill…"
            ></textarea>
            <button type="button" class="btn btn-icon-danger" @click="removeDrillFromSession(index)">&#x2715;</button>
          </div>
        </div>

        <div v-else class="empty-state-sm">No drills added yet.</div>

        <!-- Save order button -->
        <button
          v-if="sessionDrills.length"
          class="btn btn-primary btn-sm save-order-btn"
          :disabled="savingDrills"
          @click="saveDrillOrder"
        >
          {{ savingDrills ? 'Saving…' : 'Save order & notes' }}
        </button>

        <!-- Add drill area -->
        <div class="add-drill-area">
          <h3 class="add-drill-title">Add Drill</h3>
          <input
            v-model="drillSearch"
            class="form-input"
            type="text"
            placeholder="Search drills by name or code…"
            @input="filterDrills"
          />
          <div v-if="drillSearch && filteredAvailableDrills.length" class="drill-search-results">
            <div
              v-for="drill in filteredAvailableDrills"
              :key="drill.id"
              class="drill-search-item"
              @click="addDrillToSession(drill)"
            >
              <span class="code-badge code-badge-sm">{{ drill.code }}</span>
              <span>{{ drill.name }}</span>
            </div>
          </div>
          <div v-else-if="drillSearch && !filteredAvailableDrills.length" class="no-results">
            No drills found.
          </div>
        </div>
      </div>
    </template>
  </div>
</template>

<script setup>
import { ref, computed, onMounted } from 'vue'
import { useRouter, useRoute } from 'vue-router'
import { trainingSessionService } from '../utils/trainingSessionService'
import { drillService } from '../utils/drillService'
import { cycleService } from '../utils/cycleService'

const router = useRouter()
const route = useRoute()

const sessionId = route.params.sessionId
const isNew = sessionId === 'new'

const loading = ref(!isNew)
const saving = ref(false)
const savingDrills = ref(false)
const exporting = ref(false)
const error = ref(null)
const successMsg = ref(null)
const drillsError = ref(null)

const form = ref({
  date: new Date().toISOString().slice(0, 10),
  goal: '',
  duration: '',
  comments: '',
  cycleId: null,
})

const cycles = ref([])
const cycleWarnings = ref([])

// Drills management (edit mode only)
const sessionDrills = ref([]) // [{ drillId, note, drill }]
const availableDrills = ref([])
const drillSearch = ref('')
const dragIndex = ref(null)

const filteredAvailableDrills = computed(() => {
  if (!drillSearch.value) return []
  const q = drillSearch.value.toLowerCase()
  return availableDrills.value.filter(d =>
    d.name.toLowerCase().includes(q) || d.code.toLowerCase().includes(q)
  )
})

async function load() {
  try {
    const session = await trainingSessionService.get(sessionId)
    form.value = {
      date: session.date,
      goal: session.goal || '',
      duration: session.duration ?? '',
      comments: session.comments || '',
      cycleId: session.cycleId || null,
    }
    sessionDrills.value = (session.drills || []).map(sd => ({
      id: sd.id,
      drillId: sd.drill.id,
      note: sd.note || '',
      drill: sd.drill,
    }))
  } catch (e) {
    error.value = e.message
  } finally {
    loading.value = false
  }
}

async function loadAvailableDrills() {
  try {
    availableDrills.value = await drillService.list()
  } catch {
    // non-fatal
  }
}

async function loadCycles() {
  try {
    cycles.value = await cycleService.list()
  } catch {
    // non-fatal
  }
}

async function save() {
  error.value = null
  successMsg.value = null
  saving.value = true

  const payload = {
    date: form.value.date,
    goal: form.value.goal || null,
    duration: form.value.duration !== '' ? Number(form.value.duration) : null,
    comments: form.value.comments || null,
    cycleId: form.value.cycleId || null,
  }

  try {
    if (isNew) {
      const res = await trainingSessionService.create(payload)
      cycleWarnings.value = res.warnings || []
      router.push({ name: 'training-session-detail', params: { orgSlug: route.params.orgSlug, sessionId: res.data.id } })
    } else {
      const res = await trainingSessionService.update(sessionId, payload)
      cycleWarnings.value = res.warnings || []
      successMsg.value = 'Session saved.'
    }
  } catch (e) {
    error.value = e.message
  } finally {
    saving.value = false
  }
}

async function exportPdf() {
  exporting.value = true
  error.value = null
  try {
    const blob = await trainingSessionService.exportPdf(sessionId)
    const url = URL.createObjectURL(blob)
    const a = document.createElement('a')
    a.href = url
    a.download = `training-session-${form.value.date}.pdf`
    document.body.appendChild(a)
    a.click()
    document.body.removeChild(a)
    URL.revokeObjectURL(url)
  } catch (e) {
    error.value = e.message
  } finally {
    exporting.value = false
  }
}

async function confirmDelete() {
  if (!confirm('Delete this training session? This cannot be undone.')) return
  try {
    await trainingSessionService.delete(sessionId)
    router.push({ name: 'training-sessions', params: { orgSlug: route.params.orgSlug } })
  } catch (e) {
    error.value = e.message
  }
}

async function saveDrillOrder() {
  drillsError.value = null
  savingDrills.value = true
  try {
    const drills = sessionDrills.value.map(item => ({
      drillId: item.drillId,
      note: item.note || null,
    }))
    const updated = await trainingSessionService.updateDrills(sessionId, drills)
    sessionDrills.value = (updated.drills || []).map(sd => ({
      id: sd.id,
      drillId: sd.drill.id,
      note: sd.note || '',
      drill: sd.drill,
    }))
  } catch (e) {
    drillsError.value = e.message
  } finally {
    savingDrills.value = false
  }
}

function addDrillToSession(drill) {
  sessionDrills.value = [...sessionDrills.value, { drillId: drill.id, note: '', drill: drill }]
  drillSearch.value = ''
}

function removeDrillFromSession(index) {
  sessionDrills.value = sessionDrills.value.filter((_, i) => i !== index)
}

function filterDrills() {
  // computed handles filtering
}

// Drag-and-drop
function onDragStart(index) {
  dragIndex.value = index
}

function onDragOver(e) {
  e.preventDefault()
}

function onDrop(targetIndex) {
  if (dragIndex.value === null || dragIndex.value === targetIndex) return
  const items = [...sessionDrills.value]
  const [moved] = items.splice(dragIndex.value, 1)
  items.splice(targetIndex, 0, moved)
  sessionDrills.value = items
  dragIndex.value = null
}

function formatDate(dateStr) {
  if (!dateStr) return 'Training Session'
  const d = new Date(dateStr + 'T00:00:00')
  return d.toLocaleDateString(undefined, { weekday: 'long', year: 'numeric', month: 'long', day: 'numeric' })
}

onMounted(() => {
  loadCycles()
  if (!isNew) {
    load()
    loadAvailableDrills()
  }
})
</script>

<style scoped>
.back-nav {
  margin-bottom: var(--spacing-md);
}

.back-link {
  color: var(--text-muted);
  text-decoration: none;
  font-weight: 600;
}

.back-link:hover {
  color: var(--primary-color);
}

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

.header-actions {
  display: flex;
  gap: var(--spacing-sm);
}

.section {
  margin-bottom: var(--spacing-xl);
}

.section-header {
  display: flex;
  align-items: center;
  justify-content: space-between;
  margin-bottom: var(--spacing-md);
}

.section-title {
  font-size: 1.1rem;
  font-weight: 700;
  color: var(--text-light);
  margin-bottom: var(--spacing-md);
}

.drills-section {
  border-top: 1px solid var(--border-color);
  padding-top: var(--spacing-lg);
}

.session-form {
  max-width: 640px;
  display: flex;
  flex-direction: column;
  gap: var(--spacing-md);
}

.form-row {
  display: grid;
  grid-template-columns: repeat(auto-fill, minmax(180px, 1fr));
  gap: var(--spacing-md);
}

.form-group {
  display: flex;
  flex-direction: column;
  gap: var(--spacing-xs);
}

.form-label {
  font-size: 0.85rem;
  font-weight: 600;
  color: var(--text-muted);
}

.required {
  color: var(--error-color);
}

.form-input {
  padding: var(--spacing-sm) var(--spacing-md);
  background: var(--bg-card);
  border: 1px solid var(--border-color);
  border-radius: var(--radius-md);
  color: var(--text-light);
  font-size: 0.9rem;
}

.form-input:focus {
  outline: none;
  border-color: var(--primary-color);
}

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

.form-textarea:focus {
  outline: none;
  border-color: var(--primary-color);
}

.form-actions {
  display: flex;
  gap: var(--spacing-sm);
  padding-top: var(--spacing-sm);
}

/* Drills list */
.drills-list {
  display: flex;
  flex-direction: column;
  gap: var(--spacing-sm);
  margin-bottom: var(--spacing-md);
}

.drill-row {
  display: grid;
  grid-template-columns: 24px auto 1fr 2fr auto;
  gap: var(--spacing-sm);
  align-items: center;
  background: var(--bg-card);
  border: 1px solid var(--border-color);
  border-radius: var(--radius-md);
  padding: var(--spacing-sm) var(--spacing-md);
  cursor: grab;
  transition: border-color 0.15s;
}

.drill-row:active {
  cursor: grabbing;
}

.drill-row.drag-over {
  border-color: var(--primary-color);
  background: rgba(255, 107, 53, 0.05);
}

.drag-handle {
  color: var(--text-muted);
  font-size: 1.2rem;
  cursor: grab;
  user-select: none;
}

.code-badge {
  background: var(--primary-color);
  color: white;
  padding: 2px var(--spacing-sm);
  border-radius: var(--radius-sm);
  font-size: 0.72rem;
  font-weight: 700;
  font-family: monospace;
  white-space: nowrap;
}

.code-badge-sm {
  font-size: 0.68rem;
}

.drill-name {
  font-weight: 600;
  color: var(--text-light);
  font-size: 0.9rem;
}

.note-input {
  padding: var(--spacing-xs) var(--spacing-sm);
  background: var(--bg-light);
  border: 1px solid var(--border-color);
  border-radius: var(--radius-md);
  color: var(--text-muted);
  font-size: 0.8rem;
  resize: none;
  font-family: inherit;
}

.note-input:focus {
  outline: none;
  border-color: var(--primary-color);
  color: var(--text-light);
}

.btn-icon-danger {
  background: none;
  border: 1px solid var(--border-color);
  border-radius: var(--radius-md);
  color: var(--text-muted);
  cursor: pointer;
  padding: var(--spacing-xs) var(--spacing-sm);
  font-size: 0.8rem;
  transition: color 0.2s, border-color 0.2s;
  white-space: nowrap;
}

.btn-icon-danger:hover {
  color: var(--error-color);
  border-color: var(--error-color);
}

.save-order-btn {
  margin-bottom: var(--spacing-lg);
}

/* Add drill area */
.add-drill-area {
  background: var(--bg-card);
  border: 1px solid var(--border-color);
  border-radius: var(--radius-lg);
  padding: var(--spacing-md);
}

.add-drill-title {
  font-size: 0.9rem;
  font-weight: 700;
  color: var(--text-muted);
  margin-bottom: var(--spacing-sm);
}

.drill-search-results {
  margin-top: var(--spacing-sm);
  border: 1px solid var(--border-color);
  border-radius: var(--radius-md);
  overflow: hidden;
}

.drill-search-item {
  display: flex;
  align-items: center;
  gap: var(--spacing-sm);
  padding: var(--spacing-sm) var(--spacing-md);
  cursor: pointer;
  border-bottom: 1px solid var(--border-color);
  transition: background 0.15s;
}

.drill-search-item:last-child {
  border-bottom: none;
}

.drill-search-item:hover {
  background: rgba(255, 107, 53, 0.08);
}

.no-results {
  margin-top: var(--spacing-sm);
  color: var(--text-muted);
  font-size: 0.85rem;
}

.empty-state-sm {
  color: var(--text-muted);
  font-size: 0.9rem;
  padding: var(--spacing-md) 0;
  margin-bottom: var(--spacing-md);
}

.loading-state {
  text-align: center;
  padding: var(--spacing-xl);
  color: var(--text-muted);
}

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
  background: rgba(251, 191, 36, 0.12);
  color: #fbbf24;
  padding: var(--spacing-sm) var(--spacing-md);
  border-radius: var(--radius-md);
  border-left: 3px solid #fbbf24;
  font-size: 0.88rem;
  line-height: 1.6;
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

.btn:disabled {
  opacity: 0.6;
  cursor: not-allowed;
}

.btn-primary { background: var(--primary-color); color: white; }
.btn-secondary { background: var(--bg-card); color: var(--text-light); border: 1px solid var(--border-color); }
.btn-danger { background: var(--error-color); color: white; }
.btn-sm { font-size: 0.85rem; padding: 6px var(--spacing-md); }

@media (max-width: 600px) {
  .drill-row {
    grid-template-columns: 24px auto 1fr;
    grid-template-rows: auto auto;
  }

  .note-input {
    grid-column: 1 / -1;
  }
}
</style>
