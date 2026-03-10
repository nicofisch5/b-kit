<template>
  <div class="modal-backdrop" @click.self="$emit('cancel')">
    <div class="modal">
      <div class="modal-header">
        <h2>Add to Team</h2>
        <button class="btn-close" @click="$emit('cancel')">✕</button>
      </div>

      <div class="modal-body">
        <div v-if="loading" class="loading-state">Loading teams…</div>
        <div v-else-if="available.length === 0" class="empty-state">Player is already in all teams.</div>

        <div v-else class="team-list">
          <div
            v-for="team in available"
            :key="team.id"
            class="team-option"
            @click="select(team)"
          >
            <span class="team-dot" :style="{ background: team.color }"></span>
            <div class="team-info">
              <span class="team-name">{{ team.name }}</span>
              <span class="team-category">{{ team.category }}</span>
            </div>
          </div>
        </div>

        <div v-if="error" class="alert-error">{{ error }}</div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, computed, onMounted } from 'vue'
import { teamService } from '../utils/teamService'

const props = defineProps({
  playerId: { type: String, required: true },
  existingTeamIds: { type: Array, default: () => [] },
})
const emit = defineEmits(['added', 'cancel'])

const allTeams = ref([])
const loading = ref(true)
const error = ref(null)

const available = computed(() =>
  allTeams.value.filter(t => !props.existingTeamIds.includes(t.id))
)

async function select(team) {
  error.value = null
  try {
    await teamService.addPlayer(team.id, props.playerId)
    emit('added', team)
  } catch (e) {
    error.value = e.message
  }
}

onMounted(async () => {
  try {
    allTeams.value = await teamService.list()
  } catch (e) {
    error.value = e.message
  } finally {
    loading.value = false
  }
})
</script>

<style scoped>
.modal-backdrop {
  position: fixed;
  inset: 0;
  background: rgba(0, 0, 0, 0.5);
  display: flex;
  align-items: center;
  justify-content: center;
  z-index: 1000;
  padding: var(--spacing-md);
}

.modal {
  background: var(--bg-light);
  border-radius: var(--radius-lg);
  width: 100%;
  max-width: 400px;
  max-height: 70vh;
  display: flex;
  flex-direction: column;
  box-shadow: var(--shadow-lg);
}

.modal-header {
  display: flex;
  align-items: center;
  justify-content: space-between;
  padding: var(--spacing-md) var(--spacing-lg);
  border-bottom: 1px solid var(--border-color);
}

.modal-header h2 { font-size: 1.2rem; font-weight: 700; }

.modal-body {
  padding: var(--spacing-md);
  overflow-y: auto;
}

.team-list { display: flex; flex-direction: column; gap: var(--spacing-xs); }

.team-option {
  display: flex;
  align-items: center;
  gap: var(--spacing-sm);
  padding: var(--spacing-sm) var(--spacing-md);
  border: 1px solid var(--border-color);
  border-radius: var(--radius-md);
  cursor: pointer;
  transition: background var(--transition-fast);
}

.team-option:hover { background: var(--bg-card); }

.team-dot {
  width: 14px;
  height: 14px;
  border-radius: 50%;
  flex-shrink: 0;
}

.team-info { display: flex; align-items: center; gap: var(--spacing-sm); }
.team-name { font-weight: 600; }
.team-category { font-size: 0.8rem; color: var(--text-muted); }

.loading-state, .empty-state { text-align: center; color: var(--text-muted); padding: var(--spacing-md); }

.alert-error {
  background: #fdecea;
  color: var(--error-color);
  padding: var(--spacing-sm) var(--spacing-md);
  border-radius: var(--radius-md);
  font-size: 0.9rem;
  margin-top: var(--spacing-sm);
}

.btn-close { background: none; border: none; cursor: pointer; font-size: 1rem; color: var(--text-muted); }
</style>
