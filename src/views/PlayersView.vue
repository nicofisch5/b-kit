<template>
  <div class="app-container">
    <div class="page-header">
      <div class="page-header-left">
        <router-link :to="{ name: 'teams', params: { orgSlug: $route.params.orgSlug } }" class="back-link">← Teams</router-link>
        <h1 class="page-title">Players</h1>
      </div>
      <button class="btn btn-primary" @click="showForm = true">+ New Player</button>
    </div>

    <!-- Filters -->
    <div class="filters">
      <input
        v-model="search"
        type="text"
        class="filter-input"
        placeholder="Search by name…"
        @input="debouncedLoad"
      />
      <select v-model="filterCategory" class="filter-select" @change="load">
        <option value="">All categories</option>
        <option v-for="cat in TEAM_CATEGORIES" :key="cat" :value="cat">{{ cat }}</option>
      </select>
      <select v-model="filterTeam" class="filter-select" @change="load">
        <option value="">All teams</option>
        <option v-for="team in teams" :key="team.id" :value="team.id">{{ team.name }}</option>
      </select>
    </div>

    <!-- Error -->
    <div v-if="error" class="alert-error">{{ error }}</div>

    <!-- Loading -->
    <div v-if="loading" class="loading-state">Loading players…</div>

    <!-- Empty -->
    <div v-else-if="players.length === 0" class="empty-state">
      <span class="empty-icon">👤</span>
      <p>No players found.</p>
    </div>

    <!-- List -->
    <div v-else class="player-list">
      <PlayerRow
        v-for="player in players"
        :key="player.id"
        :player="player"
        :show-teams="true"
        @click="goToPlayer(player.id)"
      />
    </div>

    <!-- Create modal -->
    <PlayerFormModal
      v-if="showForm"
      @saved="onPlayerSaved"
      @cancel="showForm = false"
    />
  </div>
</template>

<script setup>
import { ref, onMounted } from 'vue'
import { useRouter, useRoute } from 'vue-router'
import { playerService, teamService } from '../utils/teamService'
import { TEAM_CATEGORIES } from '../utils/teamConstants'
import PlayerRow from '../components/PlayerRow.vue'
import PlayerFormModal from '../components/PlayerFormModal.vue'

const router = useRouter()
const route  = useRoute()

const players = ref([])
const teams = ref([])
const loading = ref(true)
const error = ref(null)
const showForm = ref(false)

const search = ref('')
const filterCategory = ref('')
const filterTeam = ref('')

let debounceTimer = null
function debouncedLoad() {
  clearTimeout(debounceTimer)
  debounceTimer = setTimeout(load, 300)
}

async function load() {
  loading.value = true
  error.value = null
  try {
    players.value = await playerService.list({
      teamId: filterTeam.value || undefined,
      category: filterCategory.value || undefined,
      search: search.value || undefined,
    })
  } catch (e) {
    error.value = e.message
  } finally {
    loading.value = false
  }
}

function goToPlayer(id) {
  router.push({ name: 'player-detail', params: { orgSlug: route.params.orgSlug, playerId: id } })
}

function onPlayerSaved(player) {
  players.value.unshift(player)
  showForm.value = false
}

onMounted(async () => {
  teams.value = await teamService.list().catch(() => [])
  await load()
})
</script>

<style scoped>
.page-header {
  display: flex;
  align-items: flex-start;
  justify-content: space-between;
  margin-bottom: var(--spacing-lg);
  flex-wrap: wrap;
  gap: var(--spacing-sm);
}

.page-header-left {
  display: flex;
  flex-direction: column;
  gap: var(--spacing-xs);
}

.page-title {
  font-size: 1.75rem;
  font-weight: 700;
  color: var(--primary-color);
}

.back-link {
  color: var(--text-muted);
  text-decoration: none;
  font-weight: 600;
  font-size: 0.9rem;
}

.back-link:hover { color: var(--primary-color); }

.filters {
  display: flex;
  gap: var(--spacing-sm);
  margin-bottom: var(--spacing-lg);
  flex-wrap: wrap;
}

.filter-input,
.filter-select {
  padding: var(--spacing-sm) var(--spacing-md);
  border: 1px solid var(--border-color);
  border-radius: var(--radius-md);
  background: var(--bg-light);
  color: var(--text-light);
  font-size: 0.9rem;
}

.filter-input { flex: 1; min-width: 200px; }

.player-list {
  display: flex;
  flex-direction: column;
  gap: var(--spacing-sm);
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
  background: #fdecea;
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
}

.btn-primary { background: var(--primary-color); color: white; }
</style>
