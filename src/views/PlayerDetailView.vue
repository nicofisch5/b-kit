<template>
  <div class="app-container">
    <div class="back-nav">
      <router-link :to="{ name: 'players', params: { orgSlug: $route.params.orgSlug } }" class="back-link">← Players</router-link>
    </div>

    <div v-if="loading" class="loading-state">Loading…</div>
    <div v-else-if="!player" class="error-state">Player not found.</div>

    <template v-else>
      <div class="player-header">
        <div class="player-avatar">{{ initials }}</div>
        <div>
          <h1 class="page-title">{{ player.firstname }} {{ player.lastname }}</h1>
          <div class="player-meta">
            <span v-if="player.jerseyNumber != null">#{{ player.jerseyNumber }}</span>
            <span v-if="player.dob">Born {{ formatDob(player.dob) }}</span>
          </div>
          <div class="player-teams">
            <span
              v-for="team in player.teams"
              :key="team.id"
              class="team-chip"
              :style="{ background: team.color + '22', borderColor: team.color, color: team.color }"
            >{{ team.name }}</span>
          </div>
        </div>
        <div class="header-actions">
          <button class="btn btn-secondary" @click="showEditForm = true">Edit</button>
          <button class="btn btn-danger" @click="confirmDelete">Delete</button>
        </div>
      </div>

      <!-- Edit form -->
      <PlayerFormModal
        v-if="showEditForm"
        :initial="player"
        @saved="onPlayerUpdated"
        @cancel="showEditForm = false"
      />

      <!-- Teams section -->
      <div class="section">
        <div class="section-header">
          <h2 class="section-title">Teams ({{ player.teams.length }})</h2>
          <button class="btn btn-secondary" @click="showAddTeamModal = true">+ Add to team</button>
        </div>

        <div v-if="player.teams.length === 0" class="empty-state">Not assigned to any team.</div>

        <div v-else class="team-list">
          <div
            v-for="team in player.teams"
            :key="team.id"
            class="team-row"
          >
            <span class="team-dot" :style="{ background: team.color }"></span>
            <router-link :to="{ name: 'team-detail', params: { teamId: team.id } }" class="team-link">
              {{ team.name }}
            </router-link>
            <span class="category-badge">{{ team.category }}</span>
            <button class="btn-icon btn-remove" @click="removeFromTeam(team.id)" title="Remove from team">✕</button>
          </div>
        </div>
      </div>
    </template>

    <!-- Add to team modal -->
    <AddToTeamModal
      v-if="showAddTeamModal"
      :player-id="playerId"
      :existing-team-ids="existingTeamIds"
      @added="onAddedToTeam"
      @cancel="showAddTeamModal = false"
    />
  </div>
</template>

<script setup>
import { ref, computed, onMounted } from 'vue'
import { useRouter, useRoute } from 'vue-router'
import { playerService, teamService } from '../utils/teamService'
import PlayerFormModal from '../components/PlayerFormModal.vue'
import AddToTeamModal from '../components/AddToTeamModal.vue'

const route = useRoute()
const router = useRouter()
const playerId = route.params.playerId

const player = ref(null)
const loading = ref(true)
const showEditForm = ref(false)
const showAddTeamModal = ref(false)

const initials = computed(() => {
  if (!player.value) return '?'
  return (player.value.firstname[0] ?? '') + (player.value.lastname[0] ?? '')
})

const existingTeamIds = computed(() => (player.value?.teams ?? []).map(t => t.id))

function formatDob(dob) {
  return new Date(dob).toLocaleDateString(undefined, { year: 'numeric', month: 'short', day: 'numeric' })
}

async function load() {
  loading.value = true
  try {
    player.value = await playerService.get(playerId)
  } catch {
    player.value = null
  } finally {
    loading.value = false
  }
}

function onPlayerUpdated(updated) {
  player.value = { ...player.value, ...updated }
  showEditForm.value = false
}

async function removeFromTeam(teamId) {
  if (!confirm('Remove player from this team?')) return
  await teamService.removePlayer(teamId, playerId)
  player.value.teams = player.value.teams.filter(t => t.id !== teamId)
}

function onAddedToTeam(team) {
  player.value.teams.push(team)
  showAddTeamModal.value = false
}

async function confirmDelete() {
  if (!confirm(`Delete player "${player.value.firstname} ${player.value.lastname}"?`)) return
  await playerService.delete(playerId)
  router.push({ name: 'players', params: { orgSlug: route.params.orgSlug } })
}

onMounted(load)
</script>

<style scoped>
.back-nav { margin-bottom: var(--spacing-md); }
.back-link { color: var(--text-muted); text-decoration: none; font-weight: 600; }
.back-link:hover { color: var(--primary-color); }

.player-header {
  display: flex;
  align-items: flex-start;
  gap: var(--spacing-md);
  margin-bottom: var(--spacing-xl);
  flex-wrap: wrap;
}

.player-avatar {
  width: 56px;
  height: 56px;
  border-radius: 50%;
  background: var(--primary-color);
  color: white;
  font-size: 1.25rem;
  font-weight: 700;
  display: flex;
  align-items: center;
  justify-content: center;
  flex-shrink: 0;
  text-transform: uppercase;
}

.page-title {
  font-size: 1.75rem;
  font-weight: 700;
  color: var(--primary-color);
  margin-bottom: var(--spacing-xs);
}

.player-meta {
  display: flex;
  gap: var(--spacing-md);
  color: var(--text-muted);
  font-size: 0.9rem;
  margin-bottom: var(--spacing-xs);
}

.player-teams {
  display: flex;
  flex-wrap: wrap;
  gap: var(--spacing-xs);
  margin-top: var(--spacing-xs);
}

.team-chip {
  padding: 2px var(--spacing-sm);
  border-radius: var(--radius-sm);
  font-size: 0.75rem;
  font-weight: 600;
  border: 1px solid;
}

.header-actions {
  margin-left: auto;
  display: flex;
  gap: var(--spacing-sm);
}

.section { margin-top: var(--spacing-xl); }

.section-header {
  display: flex;
  align-items: center;
  justify-content: space-between;
  margin-bottom: var(--spacing-md);
}

.section-title { font-size: 1.1rem; font-weight: 700; }

.team-list { display: flex; flex-direction: column; gap: var(--spacing-sm); }

.team-row {
  display: flex;
  align-items: center;
  gap: var(--spacing-sm);
  padding: var(--spacing-sm) var(--spacing-md);
  background: var(--bg-light);
  border-radius: var(--radius-md);
  border: 1px solid var(--border-color);
}

.team-dot {
  width: 12px;
  height: 12px;
  border-radius: 50%;
  flex-shrink: 0;
}

.team-link {
  font-weight: 600;
  color: var(--text-light);
  text-decoration: none;
  flex: 1;
}

.team-link:hover { color: var(--primary-color); }

.category-badge {
  font-size: 0.75rem;
  color: var(--text-muted);
  background: var(--bg-card);
  border: 1px solid var(--border-color);
  padding: 1px var(--spacing-xs);
  border-radius: var(--radius-sm);
}

.btn-icon {
  background: none;
  border: none;
  cursor: pointer;
  color: var(--text-muted);
  font-size: 0.9rem;
  padding: 4px;
  border-radius: var(--radius-sm);
}

.btn-icon:hover { color: var(--error-color); }

.btn-remove { margin-left: auto; }

.empty-state { color: var(--text-muted); padding: var(--spacing-md) 0; }
.loading-state { text-align: center; padding: var(--spacing-xl); color: var(--text-muted); }
.error-state { text-align: center; padding: var(--spacing-xl); color: var(--error-color); }

.btn {
  padding: var(--spacing-sm) var(--spacing-md);
  border: none;
  border-radius: var(--radius-md);
  font-weight: 600;
  cursor: pointer;
}

.btn-secondary { background: var(--bg-card); color: var(--text-light); border: 1px solid var(--border-color); }
.btn-danger { background: var(--error-color); color: white; }
</style>
