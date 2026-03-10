<template>
  <div class="app-container">
    <div class="back-nav">
      <router-link :to="{ name: 'seasons', params: { orgSlug: $route.params.orgSlug } }" class="back-link">← Seasons & Championships</router-link>
    </div>

    <div v-if="loading" class="loading-state">Loading…</div>
    <div v-else-if="!championship" class="error-state">Championship not found.</div>

    <template v-else>
      <div class="champ-header">
        <div>
          <h1 class="page-title">{{ championship.name }}</h1>
          <div class="header-meta">
            <span class="meta-badge">{{ championship.teams?.length ?? 0 }} teams</span>
            <span class="meta-badge">{{ championship.seasons?.length ?? 0 }} seasons</span>
            <span class="meta-badge">{{ championship.gameCount ?? 0 }} games</span>
          </div>
        </div>
        <div class="header-actions">
          <button class="btn btn-secondary" @click="showEditForm = true">Edit</button>
          <button class="btn btn-danger" @click="confirmDelete">Delete</button>
        </div>
      </div>

      <ChampionshipFormModal
        v-if="showEditForm"
        :initial="championship"
        @saved="onUpdated"
        @cancel="showEditForm = false"
      />

      <!-- Teams section -->
      <div class="section">
        <div class="section-header">
          <h2 class="section-title">Teams ({{ teams.length }})</h2>
          <button class="btn btn-primary" @click="showAddTeamModal = true">+ Add Team</button>
        </div>

        <div v-if="teams.length === 0" class="empty-state">No teams yet.</div>
        <div v-else class="team-list">
          <div v-for="t in teams" :key="t.id" class="team-row">
            <span class="color-dot" :style="{ background: t.color }"></span>
            <div class="team-info" @click="goToTeam(t.id)">
              <span class="team-name">{{ t.name }}</span>
              <span class="team-meta">{{ t.category }}</span>
              <span v-if="t.groupName" class="group-tag">{{ t.groupName }}</span>
            </div>
            <div class="team-actions">
              <button class="btn-icon" @click="editGroup(t)" title="Set group">✏️</button>
              <button class="btn-icon btn-remove" @click="removeTeam(t.id)" title="Remove">✕</button>
            </div>
          </div>
        </div>
      </div>

      <!-- Seasons section -->
      <div class="section">
        <div class="section-header">
          <h2 class="section-title">Seasons ({{ seasons.length }})</h2>
        </div>
        <div v-if="seasons.length === 0" class="empty-state">Not linked to any season yet.</div>
        <div v-else class="item-list">
          <div v-for="s in seasons" :key="s.id" class="item-card link-card" @click="goToSeason(s.id)">
            <span class="item-name">{{ s.name }}</span>
            <span class="item-arrow">›</span>
          </div>
        </div>
      </div>

      <!-- Games section -->
      <div class="section">
        <div class="section-header">
          <h2 class="section-title">Games ({{ games.length }})</h2>
          <button class="btn btn-primary" @click="showLinkModal = true">+ Link Game</button>
        </div>
        <div v-if="gamesLoading" class="empty-state">Loading…</div>
        <div v-else-if="games.length === 0" class="empty-state">No games linked yet.</div>
        <div v-else class="item-list">
          <div v-for="g in games" :key="g.id" class="game-row">
            <div class="game-info" @click="goToGame()">
              <span class="game-teams">{{ g.homeTeam }} vs {{ g.oppositionTeam }}</span>
              <span class="game-date">{{ formatDate(g.date) }}</span>
            </div>
            <div class="game-actions">
              <span class="status-badge" :class="g.status">{{ g.status === 'completed' ? 'Final' : 'In progress' }}</span>
              <button class="btn-icon btn-remove" @click="unlink(g.id)" title="Unlink">✕</button>
            </div>
          </div>
        </div>
      </div>
    </template>

    <!-- Link game modal -->
    <div v-if="showLinkModal" class="modal-backdrop" @click.self="showLinkModal = false">
      <div class="modal">
        <div class="modal-header">
          <h2>Link a Game</h2>
          <button class="close-btn" @click="showLinkModal = false">✕</button>
        </div>
        <div class="modal-body">
          <div v-if="availableGames.length === 0" class="empty-state">No unlinked games available.</div>
          <div v-else class="item-list">
            <div
              v-for="g in availableGames"
              :key="g.id"
              class="item-card link-card"
              @click="linkGame(g.id)"
            >
              <div>
                <div class="item-name">{{ g.homeTeam }} vs {{ g.oppositionTeam }}</div>
                <div class="game-date">{{ formatDate(g.date) }}</div>
              </div>
              <span class="item-arrow">+</span>
            </div>
          </div>
        </div>
      </div>
    </div>

    <AddTeamToChampionshipModal
      v-if="showAddTeamModal"
      :champ-id="championshipId"
      :existing-team-ids="teams.map(t => t.id)"
      @added="onTeamAdded"
      @cancel="showAddTeamModal = false"
    />

    <!-- Edit group name inline modal -->
    <div v-if="editingTeam" class="modal-backdrop" @click.self="editingTeam = null">
      <div class="modal">
        <div class="modal-header">
          <h2>Set Group — {{ editingTeam.name }}</h2>
          <button class="close-btn" @click="editingTeam = null">✕</button>
        </div>
        <div class="modal-body">
          <div class="field">
            <label>Group name <span class="optional">(leave blank to remove)</span></label>
            <input v-model="editGroupName" type="text" placeholder="e.g. Group A" maxlength="100" @keyup.enter="saveGroup" autofocus />
          </div>
        </div>
        <div class="modal-footer">
          <button class="btn btn-secondary" @click="editingTeam = null">Cancel</button>
          <button class="btn btn-primary" @click="saveGroup">Save</button>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, computed, onMounted } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import { championshipService } from '../utils/seasonService'
import { apiClient } from '../utils/apiClient'
import ChampionshipFormModal from '../components/ChampionshipFormModal.vue'
import AddTeamToChampionshipModal from '../components/AddTeamToChampionshipModal.vue'

const route = useRoute()
const router = useRouter()
const championshipId = route.params.championshipId

const championship = ref(null)
const teams = ref([])
const seasons = ref([])
const games = ref([])
const allOrgGames = ref([])
const loading = ref(true)
const gamesLoading = ref(false)
const showEditForm = ref(false)
const showAddTeamModal = ref(false)
const showLinkModal = ref(false)
const editingTeam = ref(null)
const editGroupName = ref('')

const linkedGameIds = computed(() => new Set(games.value.map(g => g.id)))
const availableGames = computed(() => allOrgGames.value.filter(g => !linkedGameIds.value.has(g.id)))

function formatDate(dateStr) {
  const d = new Date(dateStr)
  return d.toLocaleDateString(undefined, { year: 'numeric', month: 'short', day: 'numeric' })
}

async function load() {
  loading.value = true
  try {
    const detail = await championshipService.get(championshipId)
    championship.value = detail
    teams.value = detail.teams ?? []
    seasons.value = detail.seasons ?? []
  } catch {
    championship.value = null
  } finally {
    loading.value = false
  }
}

async function loadGames() {
  gamesLoading.value = true
  try {
    const [linked, all] = await Promise.all([
      championshipService.listGames(championshipId),
      apiClient.get('/games?limit=200').then(r => r.data),
    ])
    games.value = linked
    allOrgGames.value = all
  } catch {
    // non-fatal
  } finally {
    gamesLoading.value = false
  }
}

async function linkGame(gameId) {
  try {
    const g = await championshipService.linkGame(championshipId, gameId)
    games.value.push(g)
    showLinkModal.value = false
  } catch (e) {
    alert(e.message)
  }
}

async function unlink(gameId) {
  if (!confirm('Unlink this game from the championship?')) return
  await championshipService.unlinkGame(championshipId, gameId)
  games.value = games.value.filter(g => g.id !== gameId)
}

function onUpdated(updated) {
  championship.value = { ...championship.value, ...updated }
  showEditForm.value = false
}

function onTeamAdded(t) {
  teams.value.push(t)
  showAddTeamModal.value = false
}

async function removeTeam(teamId) {
  if (!confirm('Remove this team from the championship?')) return
  await championshipService.removeTeam(championshipId, teamId)
  teams.value = teams.value.filter(t => t.id !== teamId)
}

function editGroup(team) {
  editingTeam.value = team
  editGroupName.value = team.groupName ?? ''
}

async function saveGroup() {
  const teamId = editingTeam.value.id
  const gn = editGroupName.value.trim() || null
  await championshipService.updateTeam(championshipId, teamId, gn)
  const t = teams.value.find(t => t.id === teamId)
  if (t) t.groupName = gn
  editingTeam.value = null
}

async function confirmDelete() {
  if (!confirm(`Delete championship "${championship.value.name}"? This cannot be undone.`)) return
  await championshipService.delete(championshipId)
  router.push({ name: 'seasons', params: { orgSlug: route.params.orgSlug } })
}

function goToTeam(id) {
  router.push({ name: 'team-detail', params: { orgSlug: route.params.orgSlug, teamId: id } })
}

function goToSeason(id) {
  router.push({ name: 'season-detail', params: { orgSlug: route.params.orgSlug, seasonId: id } })
}

function goToGame() {
  router.push({ name: 'games', params: { orgSlug: route.params.orgSlug } })
}

onMounted(async () => {
  await load()
  await loadGames()
})
</script>

<style scoped>
.back-nav { margin-bottom: var(--spacing-md); }
.back-link { color: var(--text-muted); text-decoration: none; font-weight: 600; }
.back-link:hover { color: var(--primary-color); }

.champ-header {
  display: flex; align-items: flex-start; justify-content: space-between;
  margin-bottom: var(--spacing-xl); flex-wrap: wrap; gap: var(--spacing-md);
}
.page-title { font-size: 1.75rem; font-weight: 700; color: var(--primary-color); margin-bottom: var(--spacing-xs); }
.header-meta { display: flex; gap: var(--spacing-xs); flex-wrap: wrap; }
.meta-badge {
  font-size: 0.8rem; color: var(--text-muted);
  background: var(--bg-light); border: 1px solid var(--border-color);
  border-radius: var(--radius-sm); padding: 1px var(--spacing-sm);
}
.header-actions { display: flex; gap: var(--spacing-sm); }

.section { margin-top: var(--spacing-xl); }
.section-header {
  display: flex; align-items: center; justify-content: space-between;
  margin-bottom: var(--spacing-md); flex-wrap: wrap; gap: var(--spacing-sm);
}
.section-title { font-size: 1.1rem; font-weight: 700; }

.team-list { display: flex; flex-direction: column; gap: var(--spacing-xs); }
.team-row {
  display: flex; align-items: center; gap: var(--spacing-sm);
  padding: var(--spacing-sm) var(--spacing-md);
  background: var(--bg-card); border: 1px solid var(--border-color);
  border-radius: var(--radius-md);
}
.color-dot { width: 14px; height: 14px; border-radius: 50%; flex-shrink: 0; }
.team-info { display: flex; align-items: center; gap: var(--spacing-sm); flex: 1; cursor: pointer; }
.team-info:hover .team-name { color: var(--primary-color); }
.team-name { font-weight: 600; }
.team-meta { font-size: 0.85rem; color: var(--text-muted); }
.group-tag {
  font-size: 0.75rem; font-weight: 600;
  background: var(--secondary-color); color: white;
  border-radius: var(--radius-sm); padding: 1px var(--spacing-sm);
}
.team-actions { display: flex; gap: var(--spacing-xs); }
.btn-icon { background: none; border: none; cursor: pointer; font-size: 1rem; padding: var(--spacing-xs); border-radius: var(--radius-sm); }
.btn-remove { color: var(--text-muted); }
.btn-remove:hover { color: var(--error-color); }

.item-list { display: flex; flex-direction: column; gap: var(--spacing-xs); }
.item-card {
  display: flex; align-items: center; justify-content: space-between;
  padding: var(--spacing-sm) var(--spacing-md);
  background: var(--bg-card); border: 1px solid var(--border-color);
  border-radius: var(--radius-md);
}
.link-card { cursor: pointer; }
.link-card:hover { border-color: var(--primary-color); }
.item-name { font-weight: 600; }
.item-arrow { color: var(--text-muted); font-size: 1.2rem; }

.empty-state { text-align: center; padding: var(--spacing-lg); color: var(--text-muted); }
.loading-state { text-align: center; padding: var(--spacing-xl); color: var(--text-muted); }
.error-state { text-align: center; padding: var(--spacing-xl); color: var(--error-color); }

/* Inline group edit modal */
.modal-backdrop {
  position: fixed; inset: 0; background: rgba(0,0,0,0.5);
  display: flex; align-items: center; justify-content: center; z-index: 1000;
}
.modal {
  background: var(--bg-card); border-radius: var(--radius-lg);
  width: 100%; max-width: 420px; box-shadow: 0 8px 32px rgba(0,0,0,0.18);
}
.modal-header {
  display: flex; align-items: center; justify-content: space-between;
  padding: var(--spacing-md) var(--spacing-lg); border-bottom: 1px solid var(--border-color);
}
.modal-header h2 { font-size: 1.1rem; font-weight: 700; margin: 0; }
.close-btn { background: none; border: none; font-size: 1.2rem; cursor: pointer; color: var(--text-muted); }
.modal-body { padding: var(--spacing-lg); max-height: 60vh; overflow-y: auto; }
.modal-footer { padding: var(--spacing-md) var(--spacing-lg); border-top: 1px solid var(--border-color); display: flex; justify-content: flex-end; gap: var(--spacing-sm); }
.field { display: flex; flex-direction: column; gap: var(--spacing-xs); }
.field label { font-weight: 600; font-size: 0.9rem; }
.field .optional { font-weight: 400; color: var(--text-muted); font-size: 0.85rem; }
.field input { padding: var(--spacing-sm); border: 1px solid var(--border-color); border-radius: var(--radius-md); background: var(--bg-light); color: var(--text-light); font-size: 1rem; }

.btn { padding: var(--spacing-sm) var(--spacing-md); border: none; border-radius: var(--radius-md); font-weight: 600; cursor: pointer; text-decoration: none; display: inline-flex; align-items: center; }
.btn-primary { background: var(--primary-color); color: white; }
.btn-secondary { background: var(--bg-card); color: var(--text-light); border: 1px solid var(--border-color); }
.btn-danger { background: var(--error-color); color: white; }

.game-row {
  display: flex; align-items: center; justify-content: space-between;
  padding: var(--spacing-sm) var(--spacing-md);
  background: var(--bg-card); border: 1px solid var(--border-color);
  border-radius: var(--radius-md); gap: var(--spacing-sm);
}
.game-info { display: flex; flex-direction: column; gap: 2px; flex: 1; cursor: pointer; }
.game-info:hover .game-teams { color: var(--primary-color); }
.game-teams { font-weight: 600; }
.game-date { font-size: 0.82rem; color: var(--text-muted); }
.game-actions { display: flex; align-items: center; gap: var(--spacing-sm); }
.status-badge {
  font-size: 0.75rem; font-weight: 700;
  padding: 2px var(--spacing-sm); border-radius: var(--radius-sm);
  background: var(--bg-light); border: 1px solid var(--border-color); color: var(--text-muted);
}
.status-badge.completed { background: #d4edda; border-color: #28a745; color: #155724; }
[data-theme="terminal"] .status-badge.completed { background: var(--bg-color); border-color: var(--primary-color); color: var(--primary-color); }
</style>
