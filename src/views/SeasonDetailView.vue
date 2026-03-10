<template>
  <div class="app-container">
    <div class="back-nav">
      <router-link :to="{ name: 'seasons', params: { orgSlug: $route.params.orgSlug } }" class="back-link">← Seasons & Championships</router-link>
    </div>

    <div v-if="loading" class="loading-state">Loading…</div>
    <div v-else-if="!season" class="error-state">Season not found.</div>

    <template v-else>
      <div class="season-header">
        <div>
          <h1 class="page-title">{{ season.name }}</h1>
          <span class="meta-tag">Created {{ formatDate(season.createdAt) }}</span>
        </div>
        <div class="header-actions">
          <button class="btn btn-secondary" @click="showEditForm = true">Edit</button>
          <button class="btn btn-danger" @click="confirmDelete">Delete</button>
        </div>
      </div>

      <SeasonFormModal
        v-if="showEditForm"
        :initial="season"
        @saved="onUpdated"
        @cancel="showEditForm = false"
      />

      <!-- Championships section -->
      <div class="section">
        <div class="section-header">
          <h2 class="section-title">Championships ({{ championships.length }})</h2>
          <button class="btn btn-primary" @click="showAddModal = true">+ Add Championship</button>
        </div>

        <div v-if="championships.length === 0" class="empty-state">
          No championships linked yet.
        </div>
        <div v-else class="item-list">
          <div v-for="c in championships" :key="c.id" class="item-card">
            <div class="item-info" @click="goToChampionship(c.id)">
              <span class="item-name">{{ c.name }}</span>
              <span class="item-meta">{{ c.teamCount }} team{{ c.teamCount !== 1 ? 's' : '' }}</span>
            </div>
            <button class="btn-icon btn-remove" @click="removeChampionship(c.id)" title="Unlink">✕</button>
          </div>
        </div>
      </div>
    </template>

    <AddChampionshipToSeasonModal
      v-if="showAddModal"
      :season-id="seasonId"
      :existing-championship-ids="championships.map(c => c.id)"
      @added="onChampionshipAdded"
      @cancel="showAddModal = false"
    />
  </div>
</template>

<script setup>
import { ref, onMounted } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import { seasonService } from '../utils/seasonService'
import SeasonFormModal from '../components/SeasonFormModal.vue'
import AddChampionshipToSeasonModal from '../components/AddChampionshipToSeasonModal.vue'

const route = useRoute()
const router = useRouter()
const seasonId = route.params.seasonId

const season = ref(null)
const championships = ref([])
const loading = ref(true)
const showEditForm = ref(false)
const showAddModal = ref(false)

async function load() {
  loading.value = true
  try {
    const [s, cs] = await Promise.all([
      seasonService.get(seasonId),
      seasonService.listChampionships(seasonId),
    ])
    season.value = s
    championships.value = cs
  } catch {
    season.value = null
  } finally {
    loading.value = false
  }
}

function onUpdated(updated) {
  season.value = { ...season.value, ...updated }
  showEditForm.value = false
}

function onChampionshipAdded(c) {
  championships.value.push(c)
  showAddModal.value = false
}

async function removeChampionship(champId) {
  if (!confirm('Unlink this championship from the season?')) return
  await seasonService.removeChampionship(seasonId, champId)
  championships.value = championships.value.filter(c => c.id !== champId)
}

async function confirmDelete() {
  if (!confirm(`Delete season "${season.value.name}"? This cannot be undone.`)) return
  await seasonService.delete(seasonId)
  router.push({ name: 'seasons', params: { orgSlug: route.params.orgSlug } })
}

function goToChampionship(id) {
  router.push({ name: 'championship-detail', params: { orgSlug: route.params.orgSlug, championshipId: id } })
}

function formatDate(dt) {
  return dt ? dt.slice(0, 10) : ''
}

onMounted(load)
</script>

<style scoped>
.back-nav { margin-bottom: var(--spacing-md); }
.back-link { color: var(--text-muted); text-decoration: none; font-weight: 600; }
.back-link:hover { color: var(--primary-color); }

.season-header {
  display: flex; align-items: flex-start; justify-content: space-between;
  margin-bottom: var(--spacing-xl); flex-wrap: wrap; gap: var(--spacing-md);
}
.page-title { font-size: 1.75rem; font-weight: 700; color: var(--primary-color); margin-bottom: var(--spacing-xs); }
.meta-tag { font-size: 0.85rem; color: var(--text-muted); }
.header-actions { display: flex; gap: var(--spacing-sm); }

.section { margin-top: var(--spacing-lg); }
.section-header {
  display: flex; align-items: center; justify-content: space-between;
  margin-bottom: var(--spacing-md); flex-wrap: wrap; gap: var(--spacing-sm);
}
.section-title { font-size: 1.1rem; font-weight: 700; }

.item-list { display: flex; flex-direction: column; gap: var(--spacing-sm); }
.item-card {
  display: flex; align-items: center; justify-content: space-between;
  padding: var(--spacing-sm) var(--spacing-md);
  background: var(--bg-card); border: 1px solid var(--border-color);
  border-radius: var(--radius-md);
}
.item-info { display: flex; align-items: center; gap: var(--spacing-md); flex: 1; cursor: pointer; }
.item-info:hover .item-name { color: var(--primary-color); }
.item-name { font-weight: 600; }
.item-meta { font-size: 0.85rem; color: var(--text-muted); }

.btn-icon { background: none; border: none; cursor: pointer; font-size: 1rem; padding: var(--spacing-xs); border-radius: var(--radius-sm); }
.btn-remove { color: var(--text-muted); }
.btn-remove:hover { color: var(--error-color); }

.empty-state { text-align: center; padding: var(--spacing-xl); color: var(--text-muted); }
.loading-state { text-align: center; padding: var(--spacing-xl); color: var(--text-muted); }
.error-state { text-align: center; padding: var(--spacing-xl); color: var(--error-color); }

.btn { padding: var(--spacing-sm) var(--spacing-md); border: none; border-radius: var(--radius-md); font-weight: 600; cursor: pointer; text-decoration: none; display: inline-flex; align-items: center; }
.btn-primary { background: var(--primary-color); color: white; }
.btn-secondary { background: var(--bg-card); color: var(--text-light); border: 1px solid var(--border-color); }
.btn-danger { background: var(--error-color); color: white; }
</style>
