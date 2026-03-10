<template>
  <div class="app-container">
    <div class="back-nav">
      <router-link :to="{ name: 'drills', params: { orgSlug: $route.params.orgSlug } }" class="back-link">
        &larr; Drills
      </router-link>
    </div>

    <div v-if="loading" class="loading-state">Loading…</div>

    <template v-else>
      <div class="page-header">
        <h1 class="page-title">{{ isNew ? 'New Drill' : (form.name || 'Edit Drill') }}</h1>
        <div class="header-actions" v-if="!isNew">
          <button class="btn btn-danger" @click="confirmDelete">Delete</button>
        </div>
      </div>

      <div v-if="error" class="alert-error">{{ error }}</div>
      <div v-if="successMsg" class="alert-success">{{ successMsg }}</div>

      <form class="drill-form" @submit.prevent="save">
        <!-- Row: code + visibility -->
        <div class="form-row">
          <div class="form-group">
            <label class="form-label">Code <span class="required">*</span></label>
            <input v-model="form.code" class="form-input" type="text" placeholder="e.g. DR-001" required />
          </div>
          <div class="form-group">
            <label class="form-label">Visibility</label>
            <select v-model="form.visibility" class="form-input">
              <option value="org">Org (shared with team)</option>
              <option value="personal">Personal (only me)</option>
            </select>
          </div>
        </div>

        <!-- Name -->
        <div class="form-group">
          <label class="form-label">Name <span class="required">*</span></label>
          <input v-model="form.name" class="form-input" type="text" placeholder="Drill name" required />
        </div>

        <!-- Row: duration + minimumPlayers + equipment -->
        <div class="form-row">
          <div class="form-group">
            <label class="form-label">Duration (min)</label>
            <input v-model.number="form.duration" class="form-input" type="number" min="1" placeholder="e.g. 10" />
          </div>
          <div class="form-group">
            <label class="form-label">Min. players</label>
            <input v-model.number="form.minimumPlayers" class="form-input" type="number" min="1" placeholder="e.g. 4" />
          </div>
          <div class="form-group">
            <label class="form-label">Equipment</label>
            <input v-model="form.equipment" class="form-input" type="text" placeholder="Cones, balls…" />
          </div>
        </div>

        <!-- Setup -->
        <div class="form-group">
          <label class="form-label">Setup</label>
          <textarea v-model="form.setup" class="form-textarea" rows="3" placeholder="How to set up the drill…"></textarea>
        </div>

        <!-- Execution -->
        <div class="form-group">
          <label class="form-label">Execution</label>
          <textarea v-model="form.execution" class="form-textarea" rows="4" placeholder="How to run the drill…"></textarea>
        </div>

        <!-- Rotation -->
        <div class="form-group">
          <label class="form-label">Rotation</label>
          <textarea v-model="form.rotation" class="form-textarea" rows="3" placeholder="Rotation instructions…"></textarea>
        </div>

        <!-- Evolution -->
        <div class="form-group">
          <label class="form-label">Evolution / Progressions</label>
          <textarea v-model="form.evolution" class="form-textarea" rows="3" placeholder="How to progress or vary the drill…"></textarea>
        </div>

        <!-- Tags -->
        <div class="form-group">
          <label class="form-label">Tags</label>
          <div class="tag-input-area">
            <div class="tag-chips">
              <span v-for="(tag, i) in form.tags" :key="i" class="tag-chip">
                {{ tag }}
                <button type="button" class="tag-remove" @click="removeTag(i)">&#x2715;</button>
              </span>
            </div>
            <input
              v-model="tagInput"
              class="form-input tag-input"
              type="text"
              placeholder="Add tag, press Enter or comma"
              @keydown.enter.prevent="addTag"
              @keydown.comma.prevent="addTag"
              @blur="addTag"
            />
          </div>
        </div>

        <!-- Links -->
        <div class="form-group">
          <label class="form-label">Links</label>
          <div class="links-list">
            <div v-for="(link, i) in form.links" :key="i" class="link-row">
              <input v-model="link.title" class="form-input" type="text" placeholder="Label" />
              <input v-model="link.url" class="form-input" type="url" placeholder="https://…" />
              <button type="button" class="btn btn-icon-danger" @click="removeLink(i)">&#x2715;</button>
            </div>
          </div>
          <button type="button" class="btn btn-secondary btn-sm" @click="addLink">+ Add link</button>
        </div>

        <!-- Submit -->
        <div class="form-actions">
          <button type="submit" class="btn btn-primary" :disabled="saving">
            {{ saving ? 'Saving…' : (isNew ? 'Create Drill' : 'Save Changes') }}
          </button>
          <router-link :to="{ name: 'drills', params: { orgSlug: $route.params.orgSlug } }" class="btn btn-secondary">
            Cancel
          </router-link>
        </div>
      </form>
    </template>
  </div>
</template>

<script setup>
import { ref, computed, onMounted } from 'vue'
import { useRouter, useRoute } from 'vue-router'
import { drillService } from '../utils/drillService'

const router = useRouter()
const route = useRoute()

const drillId = route.params.drillId
const isNew = drillId === 'new'

const loading = ref(!isNew)
const saving = ref(false)
const error = ref(null)
const successMsg = ref(null)
const tagInput = ref('')

const form = ref({
  code: '',
  name: '',
  visibility: 'org',
  setup: '',
  execution: '',
  rotation: '',
  evolution: '',
  duration: '',
  equipment: '',
  minimumPlayers: '',
  tags: [],
  links: [],
})

async function load() {
  try {
    const drill = await drillService.get(drillId)
    form.value = {
      code: drill.code,
      name: drill.name,
      visibility: drill.visibility,
      setup: drill.setup || '',
      execution: drill.execution || '',
      rotation: drill.rotation || '',
      evolution: drill.evolution || '',
      duration: drill.duration ?? '',
      equipment: drill.equipment || '',
      minimumPlayers: drill.minimumPlayers ?? '',
      tags: drill.tags ? [...drill.tags] : [],
      links: drill.links ? drill.links.map(l => ({ ...l })) : [],
    }
  } catch (e) {
    error.value = e.message
  } finally {
    loading.value = false
  }
}

async function save() {
  error.value = null
  successMsg.value = null
  saving.value = true

  // flush any pending tag input
  if (tagInput.value.trim()) addTag()

  const payload = {
    code: form.value.code.trim(),
    name: form.value.name.trim(),
    visibility: form.value.visibility,
    setup: form.value.setup || null,
    execution: form.value.execution || null,
    rotation: form.value.rotation || null,
    evolution: form.value.evolution || null,
    duration: form.value.duration !== '' ? Number(form.value.duration) : null,
    equipment: form.value.equipment || null,
    minimumPlayers: form.value.minimumPlayers !== '' ? Number(form.value.minimumPlayers) : null,
    tags: form.value.tags,
    links: form.value.links.filter(l => l.url),
  }

  try {
    if (isNew) {
      await drillService.create(payload)
      router.push({ name: 'drills', params: { orgSlug: route.params.orgSlug } })
    } else {
      await drillService.update(drillId, payload)
      successMsg.value = 'Drill saved.'
    }
  } catch (e) {
    error.value = e.message
  } finally {
    saving.value = false
  }
}

async function confirmDelete() {
  if (!confirm(`Delete drill "${form.value.name}"? This cannot be undone.`)) return
  try {
    await drillService.delete(drillId)
    router.push({ name: 'drills', params: { orgSlug: route.params.orgSlug } })
  } catch (e) {
    error.value = e.message
  }
}

function addTag() {
  const raw = tagInput.value.replace(/,/g, '').trim()
  if (raw && !form.value.tags.includes(raw)) {
    form.value.tags = [...form.value.tags, raw]
  }
  tagInput.value = ''
}

function removeTag(index) {
  form.value.tags = form.value.tags.filter((_, i) => i !== index)
}

function addLink() {
  form.value.links = [...form.value.links, { title: '', url: '' }]
}

function removeLink(index) {
  form.value.links = form.value.links.filter((_, i) => i !== index)
}

onMounted(() => {
  if (!isNew) load()
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

.drill-form {
  max-width: 720px;
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

.tag-input-area {
  display: flex;
  flex-direction: column;
  gap: var(--spacing-sm);
  padding: var(--spacing-sm);
  background: var(--bg-card);
  border: 1px solid var(--border-color);
  border-radius: var(--radius-md);
}

.tag-chips {
  display: flex;
  flex-wrap: wrap;
  gap: 4px;
}

.tag-chip {
  background: var(--bg-light);
  border: 1px solid var(--border-color);
  color: var(--text-muted);
  font-size: 0.8rem;
  padding: 2px 8px;
  border-radius: 999px;
  display: flex;
  align-items: center;
  gap: 4px;
}

.tag-remove {
  background: none;
  border: none;
  cursor: pointer;
  color: var(--text-muted);
  font-size: 0.7rem;
  padding: 0;
  line-height: 1;
}

.tag-remove:hover {
  color: var(--error-color);
}

.tag-input {
  border: none;
  background: transparent;
  padding: 0;
}

.tag-input:focus {
  outline: none;
  border: none;
}

.links-list {
  display: flex;
  flex-direction: column;
  gap: var(--spacing-sm);
  margin-bottom: var(--spacing-sm);
}

.link-row {
  display: grid;
  grid-template-columns: 1fr 2fr auto;
  gap: var(--spacing-sm);
  align-items: center;
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
}

.btn-icon-danger:hover {
  color: var(--error-color);
  border-color: var(--error-color);
}

.form-actions {
  display: flex;
  gap: var(--spacing-sm);
  padding-top: var(--spacing-sm);
}

.alert-error {
  background: rgba(239, 68, 68, 0.1);
  color: var(--error-color);
  padding: var(--spacing-sm) var(--spacing-md);
  border-radius: var(--radius-md);
}

.alert-success {
  background: rgba(34, 197, 94, 0.1);
  color: #4ade80;
  padding: var(--spacing-sm) var(--spacing-md);
  border-radius: var(--radius-md);
}

.loading-state {
  text-align: center;
  padding: var(--spacing-xl);
  color: var(--text-muted);
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
.btn-sm { font-size: 0.8rem; padding: 4px var(--spacing-sm); }
</style>
