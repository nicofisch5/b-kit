<template>
  <div class="app-container">
    <div class="page-header">
      <h1 class="page-title">Training Sessions</h1>
      <button class="btn btn-primary" @click="goToNew">+ New Session</button>
    </div>

    <!-- Error -->
    <div v-if="error" class="alert-error">{{ error }}</div>

    <!-- Loading -->
    <div v-if="loading" class="loading-state">Loading sessions…</div>

    <!-- Empty -->
    <div v-else-if="!loading && sessions.length === 0" class="empty-state">
      <span class="empty-icon">&#128203;</span>
      <p>No training sessions yet. Create your first session!</p>
    </div>

    <!-- Sessions list -->
    <div v-else class="sessions-list">
      <div
        v-for="session in sessions"
        :key="session.id"
        class="session-card"
        @click="goToSession(session.id)"
      >
        <div class="session-date">{{ formatDate(session.date) }}</div>
        <div class="session-body">
          <div class="session-goal" v-if="session.goal">{{ truncate(session.goal, 100) }}</div>
          <div class="session-goal empty-goal" v-else>No goal set</div>
        </div>
        <div class="session-meta">
          <span v-if="session.cycleName" class="meta-item cycle-badge">&#128257; {{ session.cycleName }}</span>
          <span v-if="session.duration" class="meta-item">&#9201; {{ session.duration }} min</span>
          <span class="meta-item drill-count">&#128196; {{ session.drillCount }} drill{{ session.drillCount !== 1 ? 's' : '' }}</span>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, onMounted } from 'vue'
import { useRouter, useRoute } from 'vue-router'
import { trainingSessionService } from '../utils/trainingSessionService'

const router = useRouter()
const route = useRoute()

const sessions = ref([])
const loading = ref(true)
const error = ref(null)

async function load() {
  loading.value = true
  error.value = null
  try {
    sessions.value = await trainingSessionService.list()
  } catch (e) {
    error.value = e.message
  } finally {
    loading.value = false
  }
}

function goToNew() {
  router.push({ name: 'training-session-detail', params: { orgSlug: route.params.orgSlug, sessionId: 'new' } })
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

.sessions-list {
  display: flex;
  flex-direction: column;
  gap: var(--spacing-md);
}

.session-card {
  background: var(--bg-card);
  border: 1px solid var(--border-color);
  border-radius: var(--radius-lg);
  padding: var(--spacing-md);
  cursor: pointer;
  transition: border-color 0.2s, box-shadow 0.2s;
  display: grid;
  grid-template-columns: 140px 1fr auto;
  gap: var(--spacing-md);
  align-items: center;
}

.session-card:hover {
  border-color: var(--primary-color);
  box-shadow: var(--shadow-md);
}

.session-date {
  font-weight: 700;
  color: var(--primary-color);
  font-size: 0.9rem;
}

.session-body {
  overflow: hidden;
}

.session-goal {
  font-size: 0.9rem;
  color: var(--text-light);
  white-space: nowrap;
  overflow: hidden;
  text-overflow: ellipsis;
}

.empty-goal {
  color: var(--text-muted);
  font-style: italic;
}

.session-meta {
  display: flex;
  flex-direction: column;
  align-items: flex-end;
  gap: 4px;
}

.meta-item {
  font-size: 0.8rem;
  color: var(--text-muted);
  white-space: nowrap;
}

.drill-count {
  color: var(--primary-color);
}

.cycle-badge {
  color: #a78bfa;
  font-weight: 600;
  font-size: 0.78rem;
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

.btn-primary { background: var(--primary-color); color: white; }

@media (max-width: 600px) {
  .session-card {
    grid-template-columns: 1fr;
  }
  .session-meta {
    align-items: flex-start;
    flex-direction: row;
    gap: var(--spacing-sm);
  }
}
</style>
