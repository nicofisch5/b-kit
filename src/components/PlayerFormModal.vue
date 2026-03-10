<template>
  <div class="modal-backdrop" @click.self="$emit('cancel')">
    <div class="modal">
      <div class="modal-header">
        <h2>{{ isEdit ? 'Edit Player' : 'New Player' }}</h2>
        <button class="btn-close" @click="$emit('cancel')">✕</button>
      </div>

      <form @submit.prevent="submit" class="modal-body">
        <div class="form-row">
          <div class="form-group">
            <label>First Name *</label>
            <input v-model="form.firstname" type="text" class="form-input" maxlength="50" required />
          </div>
          <div class="form-group">
            <label>Last Name *</label>
            <input v-model="form.lastname" type="text" class="form-input" maxlength="50" required />
          </div>
        </div>

        <div class="form-row">
          <div class="form-group">
            <label>Date of Birth</label>
            <input v-model="form.dob" type="date" class="form-input" />
          </div>
          <div class="form-group">
            <label>Jersey Number</label>
            <input v-model.number="form.jerseyNumber" type="number" class="form-input" min="0" max="99" />
          </div>
        </div>

        <div v-if="error" class="alert-error">{{ error }}</div>

        <div class="modal-footer">
          <button type="button" class="btn btn-secondary" @click="$emit('cancel')">Cancel</button>
          <button type="submit" class="btn btn-primary" :disabled="saving">
            {{ saving ? 'Saving…' : (isEdit ? 'Save Changes' : 'Create Player') }}
          </button>
        </div>
      </form>
    </div>
  </div>
</template>

<script setup>
import { ref, computed } from 'vue'
import { playerService, teamService } from '../utils/teamService'

const props = defineProps({
  initial: { type: Object, default: null },
  autoAddTeamId: { type: String, default: null }, // auto-assign to team after creation
})
const emit = defineEmits(['saved', 'cancel'])

const isEdit = computed(() => !!props.initial?.id)

const form = ref({
  firstname: props.initial?.firstname ?? '',
  lastname: props.initial?.lastname ?? '',
  dob: props.initial?.dob ?? '',
  jerseyNumber: props.initial?.jerseyNumber ?? '',
})

const saving = ref(false)
const error = ref(null)

async function submit() {
  saving.value = true
  error.value = null
  try {
    const payload = {
      firstname: form.value.firstname,
      lastname: form.value.lastname,
      dob: form.value.dob || null,
      jerseyNumber: form.value.jerseyNumber !== '' ? Number(form.value.jerseyNumber) : null,
    }

    let saved
    if (isEdit.value) {
      saved = await playerService.update(props.initial.id, payload)
    } else {
      saved = await playerService.create(payload)
      // If creating from a team detail page, auto-add to that team
      if (props.autoAddTeamId) {
        await teamService.addPlayer(props.autoAddTeamId, saved.id)
      }
    }
    emit('saved', saved)
  } catch (e) {
    error.value = e.message
  } finally {
    saving.value = false
  }
}
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
  max-width: 480px;
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

.modal-body { padding: var(--spacing-lg); }

.form-row {
  display: grid;
  grid-template-columns: 1fr 1fr;
  gap: var(--spacing-md);
}

.form-group { margin-bottom: var(--spacing-md); }

.form-group label {
  display: block;
  font-weight: 600;
  margin-bottom: var(--spacing-xs);
  font-size: 0.9rem;
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

.modal-footer {
  display: flex;
  justify-content: flex-end;
  gap: var(--spacing-sm);
  margin-top: var(--spacing-lg);
}

.alert-error {
  background: #fdecea;
  color: var(--error-color);
  padding: var(--spacing-sm) var(--spacing-md);
  border-radius: var(--radius-md);
  margin-bottom: var(--spacing-md);
  font-size: 0.9rem;
}

.btn-close { background: none; border: none; cursor: pointer; font-size: 1rem; color: var(--text-muted); }

.btn {
  padding: var(--spacing-sm) var(--spacing-md);
  border: none;
  border-radius: var(--radius-md);
  font-weight: 600;
  cursor: pointer;
}

.btn:disabled { opacity: 0.6; cursor: not-allowed; }
.btn-primary { background: var(--primary-color); color: white; }
.btn-secondary { background: var(--bg-card); color: var(--text-light); border: 1px solid var(--border-color); }
</style>
