<template>
  <div class="modal-backdrop" @click.self="$emit('cancel')">
    <div class="modal">
      <div class="modal-header">
        <h2>{{ initial ? 'Edit Season' : 'New Season' }}</h2>
        <button class="close-btn" @click="$emit('cancel')">✕</button>
      </div>
      <div class="modal-body">
        <div class="field">
          <label>Name</label>
          <input v-model="name" type="text" placeholder="e.g. 2025-2026" maxlength="100" @keyup.enter="save" autofocus />
        </div>
        <div v-if="error" class="field-error">{{ error }}</div>
      </div>
      <div class="modal-footer">
        <button class="btn btn-secondary" @click="$emit('cancel')">Cancel</button>
        <button class="btn btn-primary" :disabled="saving" @click="save">
          {{ saving ? 'Saving…' : 'Save' }}
        </button>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref } from 'vue'
import { seasonService } from '../utils/seasonService'

const props = defineProps({
  initial: { type: Object, default: null },
})
const emit = defineEmits(['saved', 'cancel'])

const name = ref(props.initial?.name ?? '')
const saving = ref(false)
const error = ref(null)

async function save() {
  if (!name.value.trim()) { error.value = 'Name is required'; return }
  saving.value = true
  error.value = null
  try {
    const result = props.initial
      ? await seasonService.update(props.initial.id, name.value.trim())
      : await seasonService.create(name.value.trim())
    emit('saved', result)
  } catch (e) {
    error.value = e.message
  } finally {
    saving.value = false
  }
}
</script>

<style scoped>
.modal-backdrop {
  position: fixed; inset: 0;
  background: rgba(0,0,0,0.5);
  display: flex; align-items: center; justify-content: center;
  z-index: 1000;
}
.modal {
  background: var(--bg-card);
  border-radius: var(--radius-lg);
  width: 100%; max-width: 420px;
  box-shadow: 0 8px 32px rgba(0,0,0,0.18);
}
.modal-header {
  display: flex; align-items: center; justify-content: space-between;
  padding: var(--spacing-md) var(--spacing-lg);
  border-bottom: 1px solid var(--border-color);
}
.modal-header h2 { font-size: 1.1rem; font-weight: 700; margin: 0; }
.close-btn { background: none; border: none; font-size: 1.2rem; cursor: pointer; color: var(--text-muted); }
.modal-body { padding: var(--spacing-lg); display: flex; flex-direction: column; gap: var(--spacing-md); }
.modal-footer { padding: var(--spacing-md) var(--spacing-lg); border-top: 1px solid var(--border-color); display: flex; justify-content: flex-end; gap: var(--spacing-sm); }
.field { display: flex; flex-direction: column; gap: var(--spacing-xs); }
.field label { font-weight: 600; font-size: 0.9rem; }
.field input { padding: var(--spacing-sm); border: 1px solid var(--border-color); border-radius: var(--radius-md); background: var(--bg-light); color: var(--text-light); font-size: 1rem; }
.field-error { color: var(--error-color); font-size: 0.85rem; }
.btn { padding: var(--spacing-sm) var(--spacing-md); border: none; border-radius: var(--radius-md); font-weight: 600; cursor: pointer; }
.btn:disabled { opacity: 0.6; cursor: not-allowed; }
.btn-primary { background: var(--primary-color); color: white; }
.btn-secondary { background: var(--bg-card); color: var(--text-light); border: 1px solid var(--border-color); }
</style>
