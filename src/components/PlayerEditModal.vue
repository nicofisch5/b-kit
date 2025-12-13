<template>
  <div class="modal-overlay" @click.self="cancel">
    <div class="modal-content player-edit-modal">
      <div class="modal-header">
        <h2>Edit Player</h2>
        <button class="close-btn" @click="cancel">&times;</button>
      </div>

      <div class="modal-body">
        <div class="form-group">
          <label for="jersey-number">Jersey Number (0-99)</label>
          <input
            id="jersey-number"
            type="number"
            v-model.number="localJerseyNumber"
            min="0"
            max="99"
            class="form-input"
            ref="jerseyInput"
            @keydown.enter="save"
          />
        </div>

        <div class="form-group">
          <label for="player-name">Player Name (max 20 characters)</label>
          <input
            id="player-name"
            type="text"
            v-model="localPlayerName"
            maxlength="20"
            class="form-input"
            @keydown.enter="save"
          />
        </div>

        <div class="modal-actions">
          <button class="action-btn cancel-btn-modal" @click="cancel">Cancel</button>
          <button class="action-btn save-btn-modal" @click="save">Save Changes</button>
        </div>
      </div>
    </div>
  </div>
</template>

<script>
import { ref, onMounted } from 'vue'
import { updatePlayer } from '../store/gameStore'

export default {
  name: 'PlayerEditModal',
  props: {
    player: {
      type: Object,
      required: true
    }
  },
  emits: ['save', 'cancel'],
  setup(props, { emit }) {
    const localJerseyNumber = ref(props.player.jerseyNumber)
    const localPlayerName = ref(props.player.name)
    const jerseyInput = ref(null)

    onMounted(() => {
      // Focus on jersey number input when modal opens
      if (jerseyInput.value) {
        jerseyInput.value.focus()
        jerseyInput.value.select()
      }
    })

    function save() {
      // Validate jersey number
      if (localJerseyNumber.value < 0 || localJerseyNumber.value > 99) {
        alert('Jersey number must be between 0 and 99')
        return
      }

      // Validate player name
      if (!localPlayerName.value || localPlayerName.value.trim() === '') {
        alert('Player name cannot be empty')
        return
      }

      // Update player in store
      updatePlayer(props.player.playerId, {
        jerseyNumber: localJerseyNumber.value,
        name: localPlayerName.value.trim()
      })

      emit('save')
    }

    function cancel() {
      emit('cancel')
    }

    return {
      localJerseyNumber,
      localPlayerName,
      jerseyInput,
      save,
      cancel
    }
  }
}
</script>

<style scoped>
.player-edit-modal {
  max-width: 500px;
}

.form-group {
  margin-bottom: var(--spacing-lg);
}

.form-group label {
  display: block;
  margin-bottom: var(--spacing-sm);
  font-weight: 600;
  color: var(--text-light);
}

.form-input {
  width: 100%;
  padding: var(--spacing-md);
  border: 2px solid var(--border-color);
  border-radius: var(--radius-md);
  font-size: 1rem;
  background-color: var(--bg-light);
  color: var(--text-light);
  transition: border-color var(--transition-fast);
}

.form-input:focus {
  outline: none;
  border-color: var(--primary-color);
}

.modal-actions {
  display: flex;
  gap: var(--spacing-md);
  justify-content: flex-end;
  margin-top: var(--spacing-xl);
}

.cancel-btn-modal {
  background-color: var(--text-muted);
}

.cancel-btn-modal:hover {
  background-color: #5a6268;
}

.save-btn-modal {
  background-color: var(--success-color);
}

.save-btn-modal:hover {
  background-color: #25845d;
}
</style>
