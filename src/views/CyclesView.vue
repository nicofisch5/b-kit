<template>
  <div class="app-container">
    <div class="page-header">
      <h1 class="page-title">Cycles</h1>
      <button class="btn btn-primary" @click="goToNew">+ New Cycle</button>
    </div>

    <div v-if="error" class="alert-error">{{ error }}</div>
    <div v-if="loading" class="loading-state">Loading cycles…</div>

    <div v-else-if="cycles.length === 0" class="empty-state">
      <span class="empty-icon">&#128257;</span>
      <p>No cycles yet. Create your first training cycle!</p>
    </div>

    <div v-else class="cycles-list">
      <div
        v-for="cycle in cycles"
        :key="cycle.id"
        class="cycle-card"
        @click="goToCycle(cycle.id)"
      >
        <div class="cycle-name">{{ cycle.name }}</div>
        <div class="cycle-dates">
          <span v-if="cycle.startDate || cycle.endDate">
            {{ cycle.startDate ?? '?' }} → {{ cycle.endDate ?? '?' }}
          </span>
          <span v-else class="no-dates">No dates set</span>
        </div>
        <div class="cycle-description" v-if="cycle.description">
          {{ truncate(cycle.description, 120) }}
        </div>
        <div class="cycle-outcome" v-if="cycle.outcome">
          <span class="outcome-label">Outcome:</span> {{ truncate(cycle.outcome, 80) }}
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, onMounted } from 'vue'
import { useRouter, useRoute } from 'vue-router'
import { cycleService } from '../utils/cycleService'

const router = useRouter()
const route  = useRoute()

const cycles  = ref([])
const loading = ref(true)
const error   = ref(null)

async function load() {
  loading.value = true
  error.value = null
  try {
    cycles.value = await cycleService.list()
  } catch (e) {
    error.value = e.message
  } finally {
    loading.value = false
  }
}

function goToNew() {
  router.push({ name: 'cycle-detail', params: { orgSlug: route.params.orgSlug, cycleId: 'new' } })
}

function goToCycle(id) {
  router.push({ name: 'cycle-detail', params: { orgSlug: route.params.orgSlug, cycleId: id } })
}

function truncate(str, max) {
  if (!str) return ''
  return str.length > max ? str.slice(0, max) + '…' : str
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

.cycles-list {
  display: flex;
  flex-direction: column;
  gap: var(--spacing-md);
}

.cycle-card {
  background: var(--bg-card);
  border: 1px solid var(--border-color);
  border-radius: var(--radius-lg);
  padding: var(--spacing-md) var(--spacing-lg);
  cursor: pointer;
  transition: border-color 0.2s, box-shadow 0.2s;
  display: flex;
  flex-direction: column;
  gap: var(--spacing-xs);
}

.cycle-card:hover {
  border-color: var(--primary-color);
  box-shadow: var(--shadow-md);
}

.cycle-name {
  font-size: 1.1rem;
  font-weight: 700;
  color: var(--text-light);
}

.cycle-dates {
  font-size: 0.82rem;
  font-weight: 600;
  color: var(--primary-color);
  font-family: monospace;
}

.no-dates {
  color: var(--text-muted);
  font-style: italic;
  font-family: inherit;
}

.cycle-description {
  font-size: 0.88rem;
  color: var(--text-muted);
  line-height: 1.5;
}

.cycle-outcome {
  font-size: 0.85rem;
  color: var(--text-muted);
}

.outcome-label {
  font-weight: 600;
  color: var(--text-light);
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
  display: inline-flex;
  align-items: center;
}

.btn-primary { background: var(--primary-color); color: white; }
</style>
