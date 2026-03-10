<template>
  <div class="modal-backdrop" @click.self="$emit('cancel')">
    <div class="modal">
      <div class="modal-header">
        <h2>Add Team to Championship</h2>
        <button class="close-btn" @click="$emit('cancel')">✕</button>
      </div>
      <div class="modal-body">
        <div v-if="loading" class="loading-state">Loading teams…</div>
        <template v-else>
          <div v-if="available.length === 0" class="empty-state">
            All teams are already in this championship.
          </div>
          <template v-else>
            <ul class="option-list">
              <li
                v-for="t in available"
                :key="t.id"
                class="option-item"
                :class="{ selected: selected === t.id }"
                @click="selected = t.id"
              >
                <div class="team-info">
                  <span class="color-dot" :style="{ background: t.color }"></span>
                  <span class="option-name">{{ t.name }}</span>
                  <span class="option-meta">{{ t.category }}</span>
                </div>
              </li>
            </ul>

            <div v-if="selected" class="group-field">
              <label>Group name <span class="optional">(optional)</span></label>
              <input v-model="groupName" type="text" placeholder="e.g. Group A" maxlength="100" />
            </div>
          </template>
          <div v-if="error" class="field-error">{{ error }}</div>
        </template>
      </div>
      <div class="modal-footer">
        <button class="btn btn-secondary" @click="$emit('cancel')">Cancel</button>
        <button class="btn btn-primary" :disabled="!selected || saving" @click="save">
          {{ saving ? 'Adding…' : 'Add Team' }}
        </button>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, computed, onMounted } from 'vue'
import { teamService } from '../utils/teamService'
import { championshipService } from '../utils/seasonService'

const props = defineProps({
  champId: { type: String, required: true },
  existingTeamIds: { type: Array, default: () => [] },
})
const emit = defineEmits(['added', 'cancel'])

const all = ref([])
const loading = ref(true)
const selected = ref(null)
const groupName = ref('')
const saving = ref(false)
const error = ref(null)

const available = computed(() =>
  all.value.filter(t => !props.existingTeamIds.includes(t.id))
)

onMounted(async () => {
  try {
    all.value = await teamService.list()
  } finally {
    loading.value = false
  }
})

async function save() {
  saving.value = true
  error.value = null
  try {
    const result = await championshipService.addTeam(
      props.champId,
      selected.value,
      groupName.value.trim() || null
    )
    emit('added', result)
  } catch (e) {
    error.value = e.message
  } finally {
    saving.value = false
  }
}
</script>

<style scoped>
.modal-backdrop {
  position: fixed; inset: 0; background: rgba(0,0,0,0.5);
  display: flex; align-items: center; justify-content: center; z-index: 1000;
}
.modal {
  background: var(--bg-card); border-radius: var(--radius-lg);
  width: 100%; max-width: 480px; max-height: 80vh; display: flex; flex-direction: column;
  box-shadow: 0 8px 32px rgba(0,0,0,0.18);
}
.modal-header {
  display: flex; align-items: center; justify-content: space-between;
  padding: var(--spacing-md) var(--spacing-lg); border-bottom: 1px solid var(--border-color);
  flex-shrink: 0;
}
.modal-header h2 { font-size: 1.1rem; font-weight: 700; margin: 0; }
.close-btn { background: none; border: none; font-size: 1.2rem; cursor: pointer; color: var(--text-muted); }
.modal-body { padding: var(--spacing-lg); overflow-y: auto; flex: 1; display: flex; flex-direction: column; gap: var(--spacing-md); }
.modal-footer {
  padding: var(--spacing-md) var(--spacing-lg); border-top: 1px solid var(--border-color);
  display: flex; justify-content: flex-end; gap: var(--spacing-sm); flex-shrink: 0;
}
.option-list { list-style: none; margin: 0; padding: 0; display: flex; flex-direction: column; gap: var(--spacing-xs); }
.option-item {
  padding: var(--spacing-sm) var(--spacing-md);
  border: 2px solid var(--border-color); border-radius: var(--radius-md);
  cursor: pointer; transition: border-color 0.15s;
}
.option-item:hover { border-color: var(--primary-color); }
.option-item.selected { border-color: var(--primary-color); background: rgba(255,107,53,0.07); }
.team-info { display: flex; align-items: center; gap: var(--spacing-sm); }
.color-dot { width: 14px; height: 14px; border-radius: 50%; flex-shrink: 0; }
.option-name { font-weight: 600; flex: 1; }
.option-meta { font-size: 0.8rem; color: var(--text-muted); }
.group-field { display: flex; flex-direction: column; gap: var(--spacing-xs); }
.group-field label { font-weight: 600; font-size: 0.9rem; }
.group-field .optional { font-weight: 400; color: var(--text-muted); font-size: 0.85rem; }
.group-field input { padding: var(--spacing-sm); border: 1px solid var(--border-color); border-radius: var(--radius-md); background: var(--bg-light); color: var(--text-light); font-size: 1rem; }
.field-error { color: var(--error-color); font-size: 0.85rem; }
.loading-state, .empty-state { text-align: center; padding: var(--spacing-lg); color: var(--text-muted); }
.btn { padding: var(--spacing-sm) var(--spacing-md); border: none; border-radius: var(--radius-md); font-weight: 600; cursor: pointer; }
.btn:disabled { opacity: 0.6; cursor: not-allowed; }
.btn-primary { background: var(--primary-color); color: white; }
.btn-secondary { background: var(--bg-card); color: var(--text-light); border: 1px solid var(--border-color); }
</style>
