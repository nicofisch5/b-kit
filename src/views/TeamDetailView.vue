<template>
  <div class="app-container">
    <!-- Back nav -->
    <div class="back-nav">
      <router-link :to="{ name: 'teams', params: { orgSlug: $route.params.orgSlug } }" class="back-link">← Teams</router-link>
    </div>

    <div v-if="loading" class="loading-state">Loading…</div>
    <div v-else-if="!team" class="error-state">Team not found.</div>

    <template v-else>
      <!-- Team header -->
      <div class="team-header">
        <div class="team-color-badge" :style="{ background: team.color }"></div>
        <div>
          <h1 class="page-title">{{ team.name }}</h1>
          <span class="category-tag">{{ team.category }}</span>
          <span class="short-name-tag">{{ team.shortName }}</span>
        </div>
        <div class="header-actions">
          <button class="btn btn-secondary" @click="showEditForm = true">Edit Team</button>
          <button class="btn btn-danger" @click="confirmDelete">Delete</button>
        </div>
      </div>

      <!-- Edit form -->
      <TeamFormModal
        v-if="showEditForm"
        :initial="team"
        @saved="onTeamUpdated"
        @cancel="showEditForm = false"
      />

      <!-- Players section -->
      <div class="section">
        <div class="section-header">
          <h2 class="section-title">Players ({{ players.length }})</h2>
          <div class="section-actions">
            <button class="btn btn-secondary" @click="showAddPlayerModal = true">+ Existing player</button>
            <button class="btn btn-primary" @click="showCreatePlayerModal = true">+ New player</button>
          </div>
        </div>

        <div v-if="players.length === 0" class="empty-state">No players in this team yet.</div>

        <div v-else class="player-list">
          <PlayerRow
            v-for="player in players"
            :key="player.id"
            :player="player"
            @click="goToPlayer(player.id)"
            @remove="removePlayer(player.id)"
          />
        </div>
      </div>
    </template>

    <!-- Add existing player modal -->
    <AddPlayerToTeamModal
      v-if="showAddPlayerModal"
      :team-id="teamId"
      :existing-player-ids="existingPlayerIds"
      @added="onPlayerAdded"
      @cancel="showAddPlayerModal = false"
    />

    <!-- Create new player modal -->
    <PlayerFormModal
      v-if="showCreatePlayerModal"
      :auto-add-team-id="teamId"
      @saved="onPlayerCreated"
      @cancel="showCreatePlayerModal = false"
    />
  </div>
</template>

<script setup>
import { ref, computed, onMounted } from 'vue'
import { useRouter, useRoute } from 'vue-router'
import { teamService } from '../utils/teamService'
import TeamFormModal from '../components/TeamFormModal.vue'
import PlayerRow from '../components/PlayerRow.vue'
import AddPlayerToTeamModal from '../components/AddPlayerToTeamModal.vue'
import PlayerFormModal from '../components/PlayerFormModal.vue'

const route = useRoute()
const router = useRouter()

const teamId = route.params.teamId
const team = ref(null)
const players = ref([])
const loading = ref(true)
const showEditForm = ref(false)
const showAddPlayerModal = ref(false)
const showCreatePlayerModal = ref(false)

const existingPlayerIds = computed(() => players.value.map(p => p.id))

async function load() {
  loading.value = true
  try {
    const [teamData, playerData] = await Promise.all([
      teamService.get(teamId),
      teamService.listPlayers(teamId),
    ])
    team.value = teamData
    players.value = playerData
  } catch (e) {
    team.value = null
  } finally {
    loading.value = false
  }
}

function goToPlayer(id) {
  router.push({ name: 'player-detail', params: { orgSlug: route.params.orgSlug, playerId: id } })
}

function onTeamUpdated(updated) {
  team.value = { ...team.value, ...updated }
  showEditForm.value = false
}

async function removePlayer(playerId) {
  if (!confirm('Remove this player from the team?')) return
  await teamService.removePlayer(teamId, playerId)
  players.value = players.value.filter(p => p.id !== playerId)
}

function onPlayerAdded(player) {
  players.value.push(player)
  showAddPlayerModal.value = false
}

function onPlayerCreated(player) {
  players.value.push(player)
  showCreatePlayerModal.value = false
}

async function confirmDelete() {
  if (!confirm(`Delete team "${team.value.name}"? This cannot be undone.`)) return
  await teamService.delete(teamId)
  router.push({ name: 'teams', params: { orgSlug: route.params.orgSlug } })
}

onMounted(load)
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

.team-header {
  display: flex;
  align-items: center;
  gap: var(--spacing-md);
  margin-bottom: var(--spacing-xl);
  flex-wrap: wrap;
}

.team-color-badge {
  width: 48px;
  height: 48px;
  border-radius: 50%;
  flex-shrink: 0;
}

.page-title {
  font-size: 1.75rem;
  font-weight: 700;
  color: var(--primary-color);
  margin-bottom: var(--spacing-xs);
}

.category-tag,
.short-name-tag {
  display: inline-block;
  padding: 2px var(--spacing-sm);
  border-radius: var(--radius-sm);
  font-size: 0.8rem;
  font-weight: 600;
  margin-right: var(--spacing-xs);
  background: var(--bg-card);
  border: 1px solid var(--border-color);
  color: var(--text-muted);
}

.header-actions {
  margin-left: auto;
  display: flex;
  gap: var(--spacing-sm);
}

.section {
  margin-top: var(--spacing-lg);
}

.section-header {
  display: flex;
  align-items: center;
  justify-content: space-between;
  margin-bottom: var(--spacing-md);
  flex-wrap: wrap;
  gap: var(--spacing-sm);
}

.section-title {
  font-size: 1.1rem;
  font-weight: 700;
}

.section-actions {
  display: flex;
  gap: var(--spacing-sm);
}

.player-list {
  display: flex;
  flex-direction: column;
  gap: var(--spacing-sm);
}

.empty-state {
  color: var(--text-muted);
  text-align: center;
  padding: var(--spacing-xl);
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

.btn-primary { background: var(--primary-color); color: white; }
.btn-secondary { background: var(--bg-card); color: var(--text-light); border: 1px solid var(--border-color); }
.btn-danger { background: var(--error-color); color: white; }
</style>
