<template>
  <div class="player-roster">
    <h2 class="roster-title">Players</h2>
    <div class="player-list">
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
import { gameState } from '../store/gameStore'
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

    return {
      players,
      selectPlayer,
      showEditModal,
      editingPlayer,
      openEditModal,
      handleSave,
      handleCancel
    }
  }
}
</script>
