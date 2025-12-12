<template>
  <div class="player-roster">
    <div class="roster-header">
      <h2 class="roster-title">Players</h2>
      <button class="toggle-section-btn" @click="isExpanded = !isExpanded" :title="isExpanded ? 'Hide players' : 'Show players'">
        {{ isExpanded ? '▼' : '▶' }}
      </button>
    </div>
    <div v-show="isExpanded" class="player-list">
      <div
        v-for="player in players"
        :key="player.playerId"
        class="player-card"
        :class="{
          selected: selectedPlayerId === player.playerId,
          'foul-warning': player.totalFouls >= 4,
          'fouled-out': player.totalFouls >= 5
        }"
        @click="selectPlayer(player.playerId)"
      >
        <button class="delete-icon-btn" @click.stop="confirmDeletePlayer(player)" title="Delete player">
          <i class="material-icons">delete</i>
        </button>
        <button class="edit-icon-btn" @click.stop="openEditModal(player)" title="Edit player">
          <i class="material-icons">edit</i>
        </button>
        <div class="player-number">#{{ player.jerseyNumber }}</div>
        <div class="player-info">
          <div class="player-name">{{ player.name }}</div>
          <div class="player-stats">
            <span class="stat-item">
              <span class="stat-label">Pts:</span>
              <span class="stat-value">{{ player.totalPoints }}</span>
            </span>
            <span class="stat-item">
              <span class="stat-label">Fouls:</span>
              <span class="stat-value" :class="{ 'high-fouls': player.totalFouls >= 4 }">
                {{ player.totalFouls }}
              </span>
            </span>
          </div>
        </div>
      </div>
    </div>

    <PlayerEditModal
      v-if="showEditModal"
      :player="editingPlayer"
      @save="handleSave"
      @cancel="handleCancel"
    />
  </div>
</template>

<script>
import { ref, computed } from 'vue'
import { gameState, deletePlayer } from '../store/gameStore'
import PlayerEditModal from './PlayerEditModal.vue'

export default {
  name: 'PlayerRoster',
  components: {
    PlayerEditModal
  },
  props: {
    selectedPlayerId: {
      type: String,
      default: null
    }
  },
  emits: ['player-selected'],
  setup(props, { emit }) {
    const players = computed(() => gameState.players)
    const showEditModal = ref(false)
    const editingPlayer = ref(null)
    const isExpanded = ref(true)

    function selectPlayer(playerId) {
      // Toggle selection: if already selected, deselect
      if (props.selectedPlayerId === playerId) {
        emit('player-selected', null)
      } else {
        emit('player-selected', playerId)
      }
    }

    function openEditModal(player) {
      editingPlayer.value = player
      showEditModal.value = true
    }

    function handleSave() {
      showEditModal.value = false
      editingPlayer.value = null
    }

    function handleCancel() {
      showEditModal.value = false
      editingPlayer.value = null
    }

    function confirmDeletePlayer(player) {
      if (confirm(`Are you sure you want to delete ${player.name} (#${player.jerseyNumber})? This action cannot be undone.`)) {
        const result = deletePlayer(player.playerId)
        if (!result.success) {
          alert(result.message)
        }
      }
    }

    return {
      players,
      selectPlayer,
      showEditModal,
      editingPlayer,
      openEditModal,
      handleSave,
      handleCancel,
      confirmDeletePlayer,
      isExpanded
    }
  }
}
</script>
