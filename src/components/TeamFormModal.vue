<template>
  <div class="modal-backdrop" @click.self="$emit('cancel')">
    <div class="modal">
      <div class="modal-header">
        <h2>{{ isEdit ? 'Edit Team' : 'New Team' }}</h2>
        <button class="btn-close" @click="$emit('cancel')">✕</button>
      </div>

      <form @submit.prevent="submit" class="modal-body">
        <div class="form-group">
          <label>Team Name *</label>
          <input v-model="form.name" type="text" class="form-input" maxlength="100" required />
        </div>

        <div class="form-group">
          <label>Short Name * <span class="hint">(e.g. BKT, CHI)</span></label>
          <input v-model="form.shortName" type="text" class="form-input" maxlength="20" required />
        </div>

        <div class="form-group">
          <label>Category *</label>
          <select v-model="form.category" class="form-input" required>
            <option value="" disabled>Select category</option>
            <option v-for="cat in TEAM_CATEGORIES" :key="cat" :value="cat">{{ cat }}</option>
          </select>
        </div>

        <div class="form-group">
          <label>Color *</label>
          <div class="color-swatches">
            <button
              v-for="color in TEAM_COLORS"
              :key="color.value"
              type="button"
              class="color-swatch"
              :class="{ selected: form.color === color.value }"
              :style="{ background: color.value }"
              :title="color.label"
              @click="form.color = color.value"
            ></button>
          </div>
        </div>

        <div v-if="error" class="alert-error">{{ error }}</div>

        <div class="modal-footer">
          <button type="button" class="btn btn-secondary" @click="$emit('cancel')">Cancel</button>
          <button type="submit" class="btn btn-primary" :disabled="saving">
            {{ saving ? 'Saving…' : (isEdit ? 'Save Changes' : 'Create Team') }}
          </button>
        </div>
      </form>
    </div>
  </div>
</template>

<script setup>
import { ref, computed } from 'vue'
import { teamService } from '../utils/teamService'
import { TEAM_CATEGORIES, TEAM_COLORS } from '../utils/teamConstants'

const props = defineProps({
  initial: { type: Object, default: null },
})
const emit = defineEmits(['saved', 'cancel'])

const isEdit = computed(() => !!props.initial?.id)

const form = ref({
  name: props.initial?.name ?? '',
  shortName: props.initial?.shortName ?? '',
  category: props.initial?.category ?? '',
  color: props.initial?.color ?? TEAM_COLORS[5].value,
})

const saving = ref(false)
const error = ref(null)

async function submit() {
  if (!form.value.color) {
    error.value = 'Please select a color'
    return
  }
  saving.value = true
  error.value = null
  try {
    const saved = isEdit.value
      ? await teamService.update(props.initial.id, form.value)
      : await teamService.create(form.value)
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

.form-group { margin-bottom: var(--spacing-md); }

.form-group label {
  display: block;
  font-weight: 600;
  margin-bottom: var(--spacing-xs);
  font-size: 0.9rem;
}

.hint { font-weight: 400; color: var(--text-muted); font-size: 0.8rem; }

.form-input {
  width: 100%;
  padding: var(--spacing-sm) var(--spacing-md);
  border: 1px solid var(--border-color);
  border-radius: var(--radius-md);
  background: var(--bg-light);
  color: var(--text-light);
  font-size: 0.95rem;
}

.color-swatches {
  display: flex;
  flex-wrap: wrap;
  gap: var(--spacing-sm);
  margin-top: var(--spacing-xs);
}

.color-swatch {
  width: 32px;
  height: 32px;
  border-radius: 50%;
  border: 3px solid transparent;
  cursor: pointer;
  transition: transform var(--transition-fast), border-color var(--transition-fast);
}

.color-swatch:hover { transform: scale(1.15); }
.color-swatch.selected { border-color: var(--text-light); transform: scale(1.15); }

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

.btn-close {
  background: none;
  border: none;
  cursor: pointer;
  font-size: 1rem;
  color: var(--text-muted);
}

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
