<template>
  <div class="modal-backdrop" @click.self="$emit('cancel')">
    <div class="modal">
      <div class="modal-header">
        <h2>Add Existing Player</h2>
        <button class="btn-close" @click="$emit('cancel')">✕</button>
      </div>

      <div class="modal-body">
        <input
          v-model="search"
          type="text"
          class="form-input"
          placeholder="Search by name…"
        />

        <div v-if="loading" class="loading-state">Loading…</div>
        <div v-else-if="filtered.length === 0" class="empty-state">No players found.</div>

        <div v-else class="player-list">
          <div
            v-for="player in filtered"
            :key="player.id"
            class="player-option"
            @click="select(player)"
          >
            <div class="player-avatar">{{ (player.firstname[0] + player.lastname[0]).toUpperCase() }}</div>
            <div class="player-info">
              <span class="player-name">{{ player.lastname }}, {{ player.firstname }}</span>
              <span v-if="player.jerseyNumber != null" class="jersey">#{{ player.jerseyNumber }}</span>
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
import { playerService, teamService } from '../utils/teamService'

const props = defineProps({
  teamId: { type: String, required: true },
  existingPlayerIds: { type: Array, default: () => [] },
})
const emit = defineEmits(['added', 'cancel'])

const allPlayers = ref([])
const loading = ref(true)
const error = ref(null)
const search = ref('')

const filtered = computed(() => {
  const s = search.value.toLowerCase()
  return allPlayers.value.filter(p => {
    if (props.existingPlayerIds.includes(p.id)) return false
    if (!s) return true
    return p.firstname.toLowerCase().includes(s) || p.lastname.toLowerCase().includes(s)
  })
})

async function select(player) {
  error.value = null
  try {
    await teamService.addPlayer(props.teamId, player.id)
    emit('added', player)
  } catch (e) {
    error.value = e.message
  }
}

onMounted(async () => {
  try {
    allPlayers.value = await playerService.list()
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
  max-width: 420px;
  max-height: 80vh;
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
  flex-shrink: 0;
}

.modal-header h2 { font-size: 1.2rem; font-weight: 700; }

.modal-body {
  padding: var(--spacing-md);
  overflow-y: auto;
  display: flex;
  flex-direction: column;
  gap: var(--spacing-sm);
}

.form-input {
  width: 100%;
  padding: var(--spacing-sm) var(--spacing-md);
  border: 1px solid var(--border-color);
  border-radius: var(--radius-md);
  background: var(--bg-light);
  color: var(--text-light);
  font-size: 0.95rem;
}

.player-list {
  display: flex;
  flex-direction: column;
  gap: var(--spacing-xs);
}

.player-option {
  display: flex;
  align-items: center;
  gap: var(--spacing-sm);
  padding: var(--spacing-sm) var(--spacing-md);
  border: 1px solid var(--border-color);
  border-radius: var(--radius-md);
  cursor: pointer;
  transition: background var(--transition-fast);
}

.player-option:hover { background: var(--bg-card); }

.player-avatar {
  width: 32px;
  height: 32px;
  border-radius: 50%;
  background: var(--primary-color);
  color: white;
  font-size: 0.75rem;
  font-weight: 700;
  display: flex;
  align-items: center;
  justify-content: center;
  flex-shrink: 0;
}

.player-info { display: flex; align-items: center; gap: var(--spacing-sm); }
.player-name { font-weight: 600; }
.jersey { color: var(--secondary-color); font-size: 0.85rem; font-weight: 700; }

.loading-state, .empty-state { text-align: center; color: var(--text-muted); padding: var(--spacing-md); }

.alert-error {
  background: #fdecea;
  color: var(--error-color);
  padding: var(--spacing-sm) var(--spacing-md);
  border-radius: var(--radius-md);
  font-size: 0.9rem;
}

.btn-close { background: none; border: none; cursor: pointer; font-size: 1rem; color: var(--text-muted); }
</style>
