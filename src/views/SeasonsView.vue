<template>
  <div class="app-container">
    <div class="page-header">
      <h1 class="page-title">Seasons & Championships</h1>
      <button
        v-if="activeTab === 'seasons'"
        class="btn btn-primary"
        @click="showSeasonForm = true"
      >+ New Season</button>
      <button
        v-else
        class="btn btn-primary"
        @click="showChampionshipForm = true"
      >+ New Championship</button>
    </div>

    <!-- Tabs -->
    <div class="tabs">
      <button
        class="tab"
        :class="{ active: activeTab === 'seasons' }"
        @click="activeTab = 'seasons'"
      >Seasons ({{ seasons.length }})</button>
      <button
        class="tab"
        :class="{ active: activeTab === 'championships' }"
        @click="activeTab = 'championships'"
      >Championships ({{ championships.length }})</button>
    </div>

    <div v-if="error" class="alert-error">{{ error }}</div>

    <!-- Seasons tab -->
    <div v-if="activeTab === 'seasons'">
      <div v-if="loadingSeasons" class="loading-state">Loading seasons…</div>
      <div v-else-if="seasons.length === 0" class="empty-state">
        <span class="empty-icon">📅</span>
        <p>No seasons yet. Create your first season!</p>
      </div>
      <div v-else class="item-list">
        <div
          v-for="s in seasons"
          :key="s.id"
          class="item-card"
          @click="goToSeason(s.id)"
        >
          <div class="item-main">
            <span class="item-name">{{ s.name }}</span>
            <div class="item-meta">
              <span class="meta-badge">{{ s.championshipCount }} championship{{ s.championshipCount !== 1 ? 's' : '' }}</span>
            </div>
          </div>
          <span class="item-arrow">›</span>
        </div>
      </div>
    </div>

    <!-- Championships tab -->
    <div v-if="activeTab === 'championships'">
      <div v-if="loadingChampionships" class="loading-state">Loading championships…</div>
      <div v-else-if="championships.length === 0" class="empty-state">
        <span class="empty-icon">🏆</span>
        <p>No championships yet. Create your first championship!</p>
      </div>
      <div v-else class="item-list">
        <div
          v-for="c in championships"
          :key="c.id"
          class="item-card"
          @click="goToChampionship(c.id)"
        >
          <div class="item-main">
            <span class="item-name">{{ c.name }}</span>
            <div class="item-meta">
              <span class="meta-badge">{{ c.teamCount }} team{{ c.teamCount !== 1 ? 's' : '' }}</span>
              <span class="meta-badge">{{ c.seasonCount }} season{{ c.seasonCount !== 1 ? 's' : '' }}</span>
            </div>
          </div>
          <span class="item-arrow">›</span>
        </div>
      </div>
    </div>

    <SeasonFormModal
      v-if="showSeasonForm"
      @saved="onSeasonCreated"
      @cancel="showSeasonForm = false"
    />

    <ChampionshipFormModal
      v-if="showChampionshipForm"
      @saved="onChampionshipCreated"
      @cancel="showChampionshipForm = false"
    />
  </div>
</template>

<script setup>
import { ref, onMounted } from 'vue'
import { useRouter, useRoute } from 'vue-router'
import { seasonService, championshipService } from '../utils/seasonService'
import SeasonFormModal from '../components/SeasonFormModal.vue'
import ChampionshipFormModal from '../components/ChampionshipFormModal.vue'

const router = useRouter()
const route  = useRoute()

const activeTab = ref('seasons')
const seasons = ref([])
const championships = ref([])
const loadingSeasons = ref(true)
const loadingChampionships = ref(true)
const error = ref(null)
const showSeasonForm = ref(false)
const showChampionshipForm = ref(false)

async function loadSeasons() {
  loadingSeasons.value = true
  try {
    seasons.value = await seasonService.list()
  } catch (e) {
    error.value = e.message
  } finally {
    loadingSeasons.value = false
  }
}

async function loadChampionships() {
  loadingChampionships.value = true
  try {
    championships.value = await championshipService.list()
  } catch (e) {
    error.value = e.message
  } finally {
    loadingChampionships.value = false
  }
}

function goToSeason(id) {
  router.push({ name: 'season-detail', params: { orgSlug: route.params.orgSlug, seasonId: id } })
}

function goToChampionship(id) {
  router.push({ name: 'championship-detail', params: { orgSlug: route.params.orgSlug, championshipId: id } })
}

function onSeasonCreated(s) {
  seasons.value.push(s)
  showSeasonForm.value = false
}

function onChampionshipCreated(c) {
  championships.value.push(c)
  showChampionshipForm.value = false
}

onMounted(() => {
  loadSeasons()
  loadChampionships()
})
</script>

<style scoped>
.page-header {
  display: flex; align-items: center; justify-content: space-between;
  margin-bottom: var(--spacing-lg); flex-wrap: wrap; gap: var(--spacing-sm);
}
.page-title { font-size: 1.75rem; font-weight: 700; color: var(--primary-color); }

.tabs {
  display: flex; border-bottom: 2px solid var(--border-color);
  margin-bottom: var(--spacing-lg); gap: 0;
}
.tab {
  padding: var(--spacing-sm) var(--spacing-lg);
  background: none; border: none; cursor: pointer;
  font-weight: 600; color: var(--text-muted);
  border-bottom: 3px solid transparent; margin-bottom: -2px;
  transition: color 0.15s, border-color 0.15s;
}
.tab.active { color: var(--primary-color); border-bottom-color: var(--primary-color); }
.tab:hover:not(.active) { color: var(--text-light); }

.item-list { display: flex; flex-direction: column; gap: var(--spacing-sm); }
.item-card {
  display: flex; align-items: center; justify-content: space-between;
  padding: var(--spacing-md) var(--spacing-lg);
  background: var(--bg-card); border: 1px solid var(--border-color);
  border-radius: var(--radius-md); cursor: pointer;
  transition: border-color 0.15s;
}
.item-card:hover { border-color: var(--primary-color); }
.item-main { display: flex; flex-direction: column; gap: var(--spacing-xs); }
.item-name { font-weight: 700; font-size: 1rem; }
.item-meta { display: flex; gap: var(--spacing-xs); flex-wrap: wrap; }
.meta-badge {
  font-size: 0.8rem; color: var(--text-muted);
  background: var(--bg-light); border: 1px solid var(--border-color);
  border-radius: var(--radius-sm); padding: 1px var(--spacing-sm);
}
.item-arrow { color: var(--text-muted); font-size: 1.4rem; }

.loading-state, .empty-state { text-align: center; padding: var(--spacing-xl); color: var(--text-muted); }
.empty-icon { font-size: 2.5rem; display: block; margin-bottom: var(--spacing-sm); }
.alert-error {
  background: #fdecea; color: var(--error-color);
  padding: var(--spacing-sm) var(--spacing-md);
  border-radius: var(--radius-md); margin-bottom: var(--spacing-md);
}
.btn { padding: var(--spacing-sm) var(--spacing-md); border: none; border-radius: var(--radius-md); font-weight: 600; cursor: pointer; }
.btn-primary { background: var(--primary-color); color: white; }
</style>
