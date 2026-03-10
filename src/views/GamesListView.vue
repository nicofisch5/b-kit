<template>
  <div class="app-container">
    <div class="page-header">
      <h1 class="page-title">Games</h1>
      <button class="btn btn-primary" @click="showNewGameModal = true">+ Track New Game</button>
    </div>

    <!-- Filters -->
    <div class="filters">
      <select v-model="filterTeamId" class="filter-select">
        <option value="">All teams</option>
        <option v-for="t in teams" :key="t.id" :value="t.id">{{ t.name }}</option>
      </select>

      <input
        v-model="filterDate"
        type="date"
        class="filter-input"
        title="Filter by date"
      />

      <button v-if="filterTeamId || filterDate" class="btn btn-ghost" @click="clearFilters">
        Clear filters
      </button>
    </div>

    <div v-if="error" class="alert-error">{{ error }}</div>
    <div v-if="loading" class="loading-state">Loading games…</div>

    <template v-else>
      <div v-if="games.length === 0" class="empty-state">
        <span class="empty-icon">🏀</span>
        <p v-if="filterTeamId || filterDate">No games match the current filters.</p>
        <p v-else>No games recorded yet. Track your first game!</p>
      </div>

      <div v-else class="game-list">
        <div
          v-for="g in games"
          :key="g.id"
          class="game-card"
          :class="{ 'game-card--active': g.id === currentGameId }"
        >
          <div class="game-info">
            <div class="game-matchup">
              <span class="team-name">{{ g.homeTeam }}</span>
              <span class="vs">vs</span>
              <span class="team-name">{{ g.oppositionTeam }}</span>
            </div>
            <div class="game-meta">
              <span class="game-date">{{ formatDate(g.date) }}</span>
              <span v-if="g.teamId" class="team-badge">{{ teamNameMap[g.teamId] ?? '…' }}</span>
              <span class="status-badge" :class="`status-badge--${g.status}`">
                {{ g.status === 'in_progress' ? 'In progress' : 'Completed' }}
              </span>
            </div>
          </div>

          <div class="game-actions">
            <span v-if="g.id === currentGameId" class="active-label">Active</span>
            <button
              v-if="g.id === currentGameId"
              class="btn btn-primary btn-sm"
              @click="goToTracker"
            >Resume ›</button>
          </div>
        </div>
      </div>

      <!-- Pagination -->
      <div v-if="totalPages > 1" class="pagination">
        <button class="btn btn-ghost btn-sm" :disabled="page === 1" @click="changePage(page - 1)">‹ Prev</button>
        <span class="page-info">{{ page }} / {{ totalPages }}</span>
        <button class="btn btn-ghost btn-sm" :disabled="page === totalPages" @click="changePage(page + 1)">Next ›</button>
      </div>
    </template>

    <NewGameModal
      v-if="showNewGameModal"
      @confirm="handleNewGame"
      @cancel="showNewGameModal = false"
    />
  </div>
</template>

<script setup>
import { ref, computed, watch, onMounted } from 'vue'
import { useRouter } from 'vue-router'
import { teamService } from '../utils/teamService'
import { apiClient } from '../utils/apiClient'
import { startNewGame, gameState } from '../store/gameStore'
import { authStore } from '../store/authStore'
import NewGameModal from '../components/NewGameModal.vue'

const router = useRouter()

const games = ref([])
const teams = ref([])
const loading = ref(true)
const error = ref(null)
const filterTeamId = ref('')
const filterDate = ref('')
const page = ref(1)
const total = ref(0)
const LIMIT = 20

const currentGameId = computed(() => gameState.gameId)
const totalPages = computed(() => Math.ceil(total.value / LIMIT))

const teamNameMap = computed(() => {
  const map = {}
  for (const t of teams.value) map[t.id] = t.name
  return map
})

async function loadGames() {
  loading.value = true
  error.value = null
  try {
    const params = new URLSearchParams({ page: page.value, limit: LIMIT })
    if (filterTeamId.value) params.set('teamId', filterTeamId.value)
    if (filterDate.value) {
      params.set('dateFrom', filterDate.value + 'T00:00:00')
      params.set('dateTo', filterDate.value + 'T23:59:59')
    }
    const json = await apiClient.get(`/games?${params}`)
    games.value = json.data ?? []
    total.value = json.meta?.total ?? 0
  } catch (e) {
    error.value = e.message
  } finally {
    loading.value = false
  }
}

async function loadTeams() {
  try {
    teams.value = await teamService.list()
  } catch { /* non-blocking */ }
}

function clearFilters() {
  filterTeamId.value = ''
  filterDate.value = ''
}

function changePage(p) {
  page.value = p
}

function formatDate(dt) {
  if (!dt) return ''
  return new Date(dt).toLocaleDateString(undefined, { year: 'numeric', month: 'short', day: 'numeric' })
}

function goToTracker() {
  router.push({ name: 'game-tracker', params: { orgSlug: authStore.orgSlug } })
}

function handleNewGame({ homeTeam, oppositionTeam, date, teamId, rosterChoice, players }) {
  let resolvedPlayers
  if (rosterChoice === 'keep') {
    resolvedPlayers = gameState.players.map(p => ({
      playerId: p.playerId,
      jerseyNumber: p.jerseyNumber,
      name: p.name,
      totalPoints: 0,
      totalFouls: 0,
      statistics: [],
    }))
  } else {
    resolvedPlayers = players?.length ? players : undefined
  }
  startNewGame({ homeTeam, oppositionTeam, date, teamId, players: resolvedPlayers })
  showNewGameModal.value = false
  router.push({ name: 'game-tracker', params: { orgSlug: authStore.orgSlug } })
}

const showNewGameModal = ref(false)

// Reload when filters or page change
watch([filterTeamId, filterDate], () => {
  page.value = 1
  loadGames()
})
watch(page, loadGames)

onMounted(() => {
  loadTeams()
  loadGames()
})
</script>

<style scoped>
.page-header {
  display: flex; align-items: center; justify-content: space-between;
  margin-bottom: var(--spacing-lg); flex-wrap: wrap; gap: var(--spacing-sm);
}
.page-title { font-size: 1.75rem; font-weight: 700; color: var(--primary-color); }

.filters {
  display: flex; gap: var(--spacing-sm); flex-wrap: wrap;
  margin-bottom: var(--spacing-lg); align-items: center;
}
.filter-select,
.filter-input {
  padding: var(--spacing-sm) var(--spacing-md);
  border: 1px solid var(--border-color); border-radius: var(--radius-md);
  background: var(--bg-card); color: var(--text-light);
  font-size: 0.9rem; cursor: pointer;
}
.filter-select:focus, .filter-input:focus { outline: none; border-color: var(--primary-color); }

.game-list { display: flex; flex-direction: column; gap: var(--spacing-sm); }

.game-card {
  display: flex; align-items: center; justify-content: space-between;
  padding: var(--spacing-md) var(--spacing-lg);
  background: var(--bg-card); border: 1px solid var(--border-color);
  border-radius: var(--radius-md); gap: var(--spacing-md);
  transition: border-color 0.15s;
}
.game-card--active {
  border-color: var(--primary-color);
  background: rgba(255, 107, 53, 0.04);
}

.game-info { display: flex; flex-direction: column; gap: var(--spacing-xs); flex: 1; min-width: 0; }
.game-matchup { display: flex; align-items: center; gap: var(--spacing-sm); flex-wrap: wrap; }
.team-name { font-weight: 700; font-size: 1rem; }
.vs { color: var(--text-muted); font-size: 0.85rem; }
.game-meta { display: flex; align-items: center; gap: var(--spacing-sm); flex-wrap: wrap; }
.game-date { font-size: 0.85rem; color: var(--text-muted); }
.team-badge {
  font-size: 0.75rem; font-weight: 600;
  background: var(--secondary-color); color: white;
  border-radius: var(--radius-sm); padding: 1px var(--spacing-sm);
}
.status-badge {
  font-size: 0.75rem; font-weight: 600;
  border-radius: var(--radius-sm); padding: 1px var(--spacing-sm);
}
.status-badge--in_progress { background: #fff3cd; color: #856404; }
.status-badge--completed { background: #d1e7dd; color: #155724; }

.game-actions { display: flex; align-items: center; gap: var(--spacing-sm); flex-shrink: 0; }
.active-label { font-size: 0.8rem; font-weight: 700; color: var(--primary-color); }

.pagination {
  display: flex; align-items: center; justify-content: center; gap: var(--spacing-md);
  margin-top: var(--spacing-lg);
}
.page-info { font-size: 0.9rem; color: var(--text-muted); }

.loading-state, .empty-state { text-align: center; padding: var(--spacing-xl); color: var(--text-muted); }
.empty-icon { font-size: 2.5rem; display: block; margin-bottom: var(--spacing-sm); }
.alert-error {
  background: #fdecea; color: var(--error-color);
  padding: var(--spacing-sm) var(--spacing-md);
  border-radius: var(--radius-md); margin-bottom: var(--spacing-md);
}

.btn { padding: var(--spacing-sm) var(--spacing-md); border: none; border-radius: var(--radius-md); font-weight: 600; cursor: pointer; display: inline-flex; align-items: center; gap: var(--spacing-xs); text-decoration: none; }
.btn:disabled { opacity: 0.5; cursor: not-allowed; }
.btn-primary { background: var(--primary-color); color: white; }
.btn-ghost { background: var(--bg-card); color: var(--text-muted); border: 1px solid var(--border-color); }
.btn-ghost:not(:disabled):hover { color: var(--text-light); }
.btn-sm { padding: var(--spacing-xs) var(--spacing-sm); font-size: 0.85rem; }
</style>
