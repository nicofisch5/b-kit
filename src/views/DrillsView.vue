<template>
  <div class="app-container">
    <div class="page-header">
      <h1 class="page-title">Drills</h1>
      <button class="btn btn-primary" @click="goToNew">+ New Drill</button>
    </div>

    <!-- Filters -->
    <div class="filters-bar">
      <input
        v-model="searchText"
        class="search-input"
        type="text"
        placeholder="Search by name or code…"
      />
      <input
        v-model="tagFilter"
        class="search-input"
        type="text"
        placeholder="Filter by tag…"
      />
    </div>

    <!-- Error -->
    <div v-if="error" class="alert-error">{{ error }}</div>

    <!-- Loading -->
    <div v-if="loading" class="loading-state">Loading drills…</div>

    <!-- Empty -->
    <div v-else-if="!loading && filtered.length === 0" class="empty-state">
      <span class="empty-icon">&#128196;</span>
      <p>No drills found. Create your first drill!</p>
    </div>

    <!-- Drill grid -->
    <div v-else class="drills-grid">
      <div
        v-for="drill in filtered"
        :key="drill.id"
        class="drill-card"
        @click="goToDrill(drill.id)"
      >
        <div class="drill-card-top">
          <span class="code-badge">{{ drill.code }}</span>
          <span class="visibility-badge" :class="drill.visibility === 'org' ? 'vis-org' : 'vis-personal'">
            {{ drill.visibility === 'org' ? 'Org' : 'Personal' }}
          </span>
        </div>
        <h3 class="drill-name">{{ drill.name }}</h3>
        <div class="drill-meta">
          <span v-if="drill.duration" class="meta-item">&#9201; {{ drill.duration }} min</span>
          <span v-if="drill.minimumPlayers" class="meta-item">&#128100; {{ drill.minimumPlayers }}+</span>
        </div>
        <div v-if="drill.tags && drill.tags.length" class="tag-chips">
          <span v-for="tag in drill.tags" :key="tag" class="tag-chip">{{ tag }}</span>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, computed, onMounted } from 'vue'
import { useRouter, useRoute } from 'vue-router'
import { drillService } from '../utils/drillService'

const router = useRouter()
const route = useRoute()

const drills = ref([])
const loading = ref(true)
const error = ref(null)
const searchText = ref('')
const tagFilter = ref('')

const filtered = computed(() => {
  return drills.value.filter(d => {
    const matchSearch = !searchText.value ||
      d.name.toLowerCase().includes(searchText.value.toLowerCase()) ||
      d.code.toLowerCase().includes(searchText.value.toLowerCase())
    const matchTag = !tagFilter.value ||
      (d.tags && d.tags.some(t => t.toLowerCase().includes(tagFilter.value.toLowerCase())))
    return matchSearch && matchTag
  })
})

async function load() {
  loading.value = true
  error.value = null
  try {
    drills.value = await drillService.list()
  } catch (e) {
    error.value = e.message
  } finally {
    loading.value = false
  }
}

function goToDrill(id) {
  router.push({ name: 'drill-detail', params: { orgSlug: route.params.orgSlug, drillId: id } })
}

function goToNew() {
  router.push({ name: 'drill-detail', params: { orgSlug: route.params.orgSlug, drillId: 'new' } })
}

onMounted(load)
</script>

<style scoped>
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

.filters-bar {
  display: flex;
  gap: var(--spacing-sm);
  margin-bottom: var(--spacing-lg);
  flex-wrap: wrap;
}

.search-input {
  flex: 1;
  min-width: 180px;
  padding: var(--spacing-sm) var(--spacing-md);
  background: var(--bg-card);
  border: 1px solid var(--border-color);
  border-radius: var(--radius-md);
  color: var(--text-light);
  font-size: 0.9rem;
}

.search-input::placeholder {
  color: var(--text-muted);
}

.drills-grid {
  display: grid;
  grid-template-columns: repeat(auto-fill, minmax(260px, 1fr));
  gap: var(--spacing-md);
}

.drill-card {
  background: var(--bg-card);
  border: 1px solid var(--border-color);
  border-radius: var(--radius-lg);
  padding: var(--spacing-md);
  cursor: pointer;
  transition: border-color 0.2s, box-shadow 0.2s;
}

.drill-card:hover {
  border-color: var(--primary-color);
  box-shadow: var(--shadow-md);
}

.drill-card-top {
  display: flex;
  align-items: center;
  gap: var(--spacing-sm);
  margin-bottom: var(--spacing-sm);
}

.code-badge {
  background: var(--primary-color);
  color: white;
  padding: 2px var(--spacing-sm);
  border-radius: var(--radius-sm);
  font-size: 0.75rem;
  font-weight: 700;
  font-family: monospace;
}

.visibility-badge {
  font-size: 0.7rem;
  font-weight: 600;
  padding: 2px 6px;
  border-radius: var(--radius-sm);
}

.vis-org {
  background: rgba(59, 130, 246, 0.15);
  color: #60a5fa;
}

.vis-personal {
  background: var(--bg-light);
  color: var(--text-muted);
  border: 1px solid var(--border-color);
}

.drill-name {
  font-size: 1rem;
  font-weight: 700;
  color: var(--text-light);
  margin-bottom: var(--spacing-sm);
}

.drill-meta {
  display: flex;
  gap: var(--spacing-sm);
  margin-bottom: var(--spacing-sm);
}

.meta-item {
  font-size: 0.8rem;
  color: var(--text-muted);
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
  font-size: 0.72rem;
  padding: 2px 8px;
  border-radius: 999px;
}

.loading-state,
.empty-state {
  text-align: center;
  padding: var(--spacing-xl);
  color: var(--text-muted);
}

.empty-icon {
  font-size: 2.5rem;
  display: block;
  margin-bottom: var(--spacing-sm);
}

.alert-error {
  background: rgba(239, 68, 68, 0.1);
  color: var(--error-color);
  padding: var(--spacing-sm) var(--spacing-md);
  border-radius: var(--radius-md);
  margin-bottom: var(--spacing-md);
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

.btn-primary {
  background: var(--primary-color);
  color: white;
}
</style>
