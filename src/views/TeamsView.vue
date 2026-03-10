<template>
  <div class="app-container">
    <div class="page-header">
      <h1 class="page-title">Teams</h1>
      <div class="page-header-actions">
        <router-link :to="{ name: 'players', params: { orgSlug: $route.params.orgSlug } }" class="btn btn-secondary">All Players</router-link>
        <button class="btn btn-primary" @click="showForm = true">+ New Team</button>
      </div>
    </div>

    <!-- Error -->
    <div v-if="error" class="alert alert-error">{{ error }}</div>

    <!-- Loading -->
    <div v-if="loading" class="loading-state">Loading teams…</div>

    <!-- Empty -->
    <div v-else-if="!loading && teams.length === 0 && !showForm" class="empty-state">
      <span class="empty-icon">🏀</span>
      <p>No teams yet. Create your first team!</p>
    </div>

    <!-- Team list grouped by category -->
    <div v-else class="teams-grid">
      <template v-for="category in categoriesInUse" :key="category">
        <h2 class="category-heading">{{ category }}</h2>
        <div class="team-cards">
          <TeamCard
            v-for="team in teamsByCategory[category]"
            :key="team.id"
            :team="team"
            @click="goToTeam(team.id)"
          />
        </div>
      </template>
    </div>

    <!-- Create form modal -->
    <TeamFormModal
      v-if="showForm"
      @saved="onTeamSaved"
      @cancel="showForm = false"
    />
  </div>
</template>

<script setup>
import { ref, computed, onMounted } from 'vue'
import { useRouter, useRoute } from 'vue-router'
import { teamService } from '../utils/teamService'
import { TEAM_CATEGORIES } from '../utils/teamConstants'
import TeamCard from '../components/TeamCard.vue'
import TeamFormModal from '../components/TeamFormModal.vue'

const router = useRouter()
const route  = useRoute()

const teams = ref([])
const loading = ref(true)
const error = ref(null)
const showForm = ref(false)

const categoriesInUse = computed(() => {
  const set = new Set(teams.value.map(t => t.category))
  return TEAM_CATEGORIES.filter(c => set.has(c))
})

const teamsByCategory = computed(() => {
  const map = {}
  for (const team of teams.value) {
    if (!map[team.category]) map[team.category] = []
    map[team.category].push(team)
  }
  return map
})

async function load() {
  loading.value = true
  error.value = null
  try {
    teams.value = await teamService.list()
  } catch (e) {
    error.value = e.message
  } finally {
    loading.value = false
  }
}

function goToTeam(id) {
  router.push({ name: 'team-detail', params: { orgSlug: route.params.orgSlug, teamId: id } })
}

function onTeamSaved(team) {
  showForm.value = false
  teams.value.push(team)
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

.page-header-actions {
  display: flex;
  gap: var(--spacing-sm);
}

.category-heading {
  font-size: 1rem;
  font-weight: 700;
  color: var(--text-muted);
  text-transform: uppercase;
  letter-spacing: 0.05em;
  margin: var(--spacing-lg) 0 var(--spacing-sm);
}

.team-cards {
  display: grid;
  grid-template-columns: repeat(auto-fill, minmax(240px, 1fr));
  gap: var(--spacing-md);
  margin-bottom: var(--spacing-md);
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
  text-decoration: none;
  display: inline-flex;
  align-items: center;
}

.btn-primary {
  background: var(--primary-color);
  color: white;
}

.btn-secondary {
  background: var(--bg-card);
  color: var(--text-light);
  border: 1px solid var(--border-color);
}
</style>
