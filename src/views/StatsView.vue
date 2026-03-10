<template>
  <div class="app-container">
    <h1 class="page-title">Cumulated Stats</h1>

    <!-- Tab switcher -->
    <div class="tabs">
      <button :class="['tab', { active: tab === 'players' }]" @click="switchTab('players')">Players</button>
      <button :class="['tab', { active: tab === 'teams' }]" @click="switchTab('teams')">Teams</button>
    </div>

    <!-- Filters -->
    <div class="filters">
      <div v-if="tab === 'players'" class="filter-group">
        <label>Team</label>
        <select v-model="selectedTeamId" @change="fetch">
          <option value="">All teams</option>
          <option v-for="t in teams" :key="t.id" :value="t.id">{{ t.name }}</option>
        </select>
      </div>
      <div class="filter-group">
        <label>Championship</label>
        <select v-model="selectedChampId" @change="fetch">
          <option value="">All championships</option>
          <option v-for="c in championships" :key="c.id" :value="c.id">{{ c.name }}</option>
        </select>
      </div>
    </div>

    <!-- Loading / empty -->
    <div v-if="loading" class="loading-state">Loading…</div>
    <div v-else-if="error" class="error-state">{{ error }}</div>
    <div v-else-if="rows.length === 0" class="empty-state">No stats recorded yet.</div>

    <!-- Player stats table -->
    <div v-else-if="tab === 'players'" class="table-wrapper">
      <table class="stats-table">
        <thead>
          <tr>
            <th class="col-name" @click="sort('playerName')">Player <SortIcon :col="'playerName'" :active="sortCol" :dir="sortDir" /></th>
            <th @click="sort('gamesPlayed')">GP <SortIcon :col="'gamesPlayed'" :active="sortCol" :dir="sortDir" /></th>
            <th @click="sort('points')">PTS <SortIcon :col="'points'" :active="sortCol" :dir="sortDir" /></th>
            <th @click="sort('rebounds')">REB <SortIcon :col="'rebounds'" :active="sortCol" :dir="sortDir" /></th>
            <th @click="sort('assists')">AST <SortIcon :col="'assists'" :active="sortCol" :dir="sortDir" /></th>
            <th @click="sort('steals')">STL <SortIcon :col="'steals'" :active="sortCol" :dir="sortDir" /></th>
            <th @click="sort('blocks')">BLK <SortIcon :col="'blocks'" :active="sortCol" :dir="sortDir" /></th>
            <th @click="sort('fouls')">PF <SortIcon :col="'fouls'" :active="sortCol" :dir="sortDir" /></th>
            <th @click="sort('turnovers')">TO <SortIcon :col="'turnovers'" :active="sortCol" :dir="sortDir" /></th>
            <th @click="sort('twoPtMade')">2PM <SortIcon :col="'twoPtMade'" :active="sortCol" :dir="sortDir" /></th>
            <th @click="sort('threePtMade')">3PM <SortIcon :col="'threePtMade'" :active="sortCol" :dir="sortDir" /></th>
            <th @click="sort('ftMade')">FTM <SortIcon :col="'ftMade'" :active="sortCol" :dir="sortDir" /></th>
          </tr>
        </thead>
        <tbody>
          <tr v-for="r in sorted" :key="r.playerId">
            <td class="col-name">{{ r.playerName }}</td>
            <td>{{ r.gamesPlayed }}</td>
            <td class="highlight">{{ r.points }}</td>
            <td>{{ r.rebounds }}</td>
            <td>{{ r.assists }}</td>
            <td>{{ r.steals }}</td>
            <td>{{ r.blocks }}</td>
            <td>{{ r.fouls }}</td>
            <td>{{ r.turnovers }}</td>
            <td>{{ r.twoPtMade }}/{{ r.twoPtMade + r.twoPtMiss }}</td>
            <td>{{ r.threePtMade }}/{{ r.threePtMade + r.threePtMiss }}</td>
            <td>{{ r.ftMade }}/{{ r.ftMade + r.ftMiss }}</td>
          </tr>
        </tbody>
      </table>
    </div>

    <!-- Team stats table -->
    <div v-else class="table-wrapper">
      <table class="stats-table">
        <thead>
          <tr>
            <th class="col-name" @click="sort('teamName')">Team <SortIcon :col="'teamName'" :active="sortCol" :dir="sortDir" /></th>
            <th @click="sort('gamesPlayed')">GP <SortIcon :col="'gamesPlayed'" :active="sortCol" :dir="sortDir" /></th>
            <th @click="sort('points')">PTS <SortIcon :col="'points'" :active="sortCol" :dir="sortDir" /></th>
            <th @click="sort('rebounds')">REB <SortIcon :col="'rebounds'" :active="sortCol" :dir="sortDir" /></th>
            <th @click="sort('assists')">AST <SortIcon :col="'assists'" :active="sortCol" :dir="sortDir" /></th>
            <th @click="sort('steals')">STL <SortIcon :col="'steals'" :active="sortCol" :dir="sortDir" /></th>
            <th @click="sort('blocks')">BLK <SortIcon :col="'blocks'" :active="sortCol" :dir="sortDir" /></th>
            <th @click="sort('fouls')">PF <SortIcon :col="'fouls'" :active="sortCol" :dir="sortDir" /></th>
            <th @click="sort('turnovers')">TO <SortIcon :col="'turnovers'" :active="sortCol" :dir="sortDir" /></th>
            <th @click="sort('twoPtMade')">2PM <SortIcon :col="'twoPtMade'" :active="sortCol" :dir="sortDir" /></th>
            <th @click="sort('threePtMade')">3PM <SortIcon :col="'threePtMade'" :active="sortCol" :dir="sortDir" /></th>
            <th @click="sort('ftMade')">FTM <SortIcon :col="'ftMade'" :active="sortCol" :dir="sortDir" /></th>
          </tr>
        </thead>
        <tbody>
          <tr v-for="r in sorted" :key="r.teamId">
            <td class="col-name">
              <span v-if="r.teamColor" class="team-dot" :style="{ background: r.teamColor }"></span>
              {{ r.teamName }}
            </td>
            <td>{{ r.gamesPlayed }}</td>
            <td class="highlight">{{ r.points }}</td>
            <td>{{ r.rebounds }}</td>
            <td>{{ r.assists }}</td>
            <td>{{ r.steals }}</td>
            <td>{{ r.blocks }}</td>
            <td>{{ r.fouls }}</td>
            <td>{{ r.turnovers }}</td>
            <td>{{ r.twoPtMade }}/{{ r.twoPtMade + r.twoPtMiss }}</td>
            <td>{{ r.threePtMade }}/{{ r.threePtMade + r.threePtMiss }}</td>
            <td>{{ r.ftMade }}/{{ r.ftMade + r.ftMiss }}</td>
          </tr>
        </tbody>
      </table>
    </div>
  </div>
</template>

<script setup>
import { ref, computed, onMounted } from 'vue'
import { apiClient } from '../utils/apiClient'
import { teamService } from '../utils/teamService'
import { championshipService } from '../utils/seasonService'

const SortIcon = {
  props: ['col', 'active', 'dir'],
  template: `<span class="sort-icon">{{ col === active ? (dir === 'asc' ? '▲' : '▼') : '⇅' }}</span>`,
}

const tab = ref('players')
const teams = ref([])
const championships = ref([])
const rows = ref([])
const loading = ref(false)
const error = ref(null)
const selectedTeamId = ref('')
const selectedChampId = ref('')
const sortCol = ref('points')
const sortDir = ref('desc')

const sorted = computed(() => {
  const col = sortCol.value
  const dir = sortDir.value === 'asc' ? 1 : -1
  return [...rows.value].sort((a, b) => {
    const av = a[col]
    const bv = b[col]
    if (typeof av === 'string') return dir * av.localeCompare(bv)
    return dir * (av - bv)
  })
})

function sort(col) {
  if (sortCol.value === col) {
    sortDir.value = sortDir.value === 'asc' ? 'desc' : 'asc'
  } else {
    sortCol.value = col
    sortDir.value = col === 'playerName' || col === 'teamName' ? 'asc' : 'desc'
  }
}

function switchTab(newTab) {
  tab.value = newTab
  sortCol.value = 'points'
  sortDir.value = 'desc'
  fetch()
}

async function fetch() {
  loading.value = true
  error.value = null
  try {
    const params = new URLSearchParams()
    if (tab.value === 'players' && selectedTeamId.value) params.set('teamId', selectedTeamId.value)
    if (selectedChampId.value) params.set('championshipId', selectedChampId.value)
    const qs = params.toString()
    const endpoint = tab.value === 'teams' ? '/stats/teams' : '/stats/players'
    const res = await apiClient.get(`${endpoint}${qs ? '?' + qs : ''}`)
    rows.value = res.data
  } catch (e) {
    error.value = e.message
  } finally {
    loading.value = false
  }
}

onMounted(async () => {
  const [t, c] = await Promise.all([
    teamService.list().catch(() => []),
    championshipService.list().catch(() => []),
  ])
  teams.value = t
  championships.value = c
  await fetch()
})
</script>

<style scoped>
.page-title {
  font-size: 1.75rem;
  font-weight: 700;
  color: var(--primary-color);
  margin-bottom: var(--spacing-lg);
}

.tabs {
  display: flex;
  gap: var(--spacing-xs);
  margin-bottom: var(--spacing-lg);
  border-bottom: 1px solid var(--border-color);
}

.tab {
  padding: var(--spacing-sm) var(--spacing-lg);
  background: none;
  border: none;
  border-bottom: 2px solid transparent;
  color: var(--text-muted);
  font-size: 0.95rem;
  font-weight: 600;
  cursor: pointer;
  margin-bottom: -1px;
  transition: color 0.15s, border-color 0.15s;
}

.tab:hover { color: var(--text-light); }
.tab.active { color: var(--primary-color); border-bottom-color: var(--primary-color); }

.filters {
  display: flex;
  gap: var(--spacing-md);
  margin-bottom: var(--spacing-lg);
  flex-wrap: wrap;
}

.filter-group {
  display: flex;
  flex-direction: column;
  gap: var(--spacing-xs);
  min-width: 180px;
}

.filter-group label {
  font-size: 0.85rem;
  font-weight: 600;
  color: var(--text-muted);
}

.filter-group select {
  padding: var(--spacing-sm) var(--spacing-md);
  border: 1px solid var(--border-color);
  border-radius: var(--radius-md);
  background: var(--bg-card);
  color: var(--text-light);
  font-size: 0.95rem;
}

.table-wrapper {
  overflow-x: auto;
  border: 1px solid var(--border-color);
  border-radius: var(--radius-md);
}

.stats-table {
  width: 100%;
  border-collapse: collapse;
  font-size: 0.9rem;
}

.stats-table th {
  padding: var(--spacing-sm) var(--spacing-md);
  text-align: center;
  font-weight: 700;
  font-size: 0.8rem;
  color: var(--text-muted);
  background: var(--bg-light);
  border-bottom: 1px solid var(--border-color);
  cursor: pointer;
  white-space: nowrap;
  user-select: none;
}

.stats-table th:hover { color: var(--primary-color); }
.stats-table th.col-name { text-align: left; }

.stats-table td {
  padding: var(--spacing-sm) var(--spacing-md);
  text-align: center;
  border-bottom: 1px solid var(--border-color);
}

.stats-table td.col-name {
  text-align: left;
  font-weight: 600;
  display: flex;
  align-items: center;
  gap: var(--spacing-sm);
}

.stats-table td.highlight { font-weight: 700; color: var(--primary-color); }

.stats-table tbody tr:last-child td { border-bottom: none; }
.stats-table tbody tr:hover { background: var(--bg-light); }

.sort-icon { margin-left: 4px; font-size: 0.7rem; opacity: 0.6; }

.team-dot {
  display: inline-block;
  width: 10px;
  height: 10px;
  border-radius: 50%;
  flex-shrink: 0;
}

.loading-state,
.empty-state { text-align: center; padding: var(--spacing-xl); color: var(--text-muted); }
.error-state  { text-align: center; padding: var(--spacing-xl); color: var(--error-color); }
</style>
