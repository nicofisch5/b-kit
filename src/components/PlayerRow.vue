<template>
  <div class="player-row" @click="$emit('click')">
    <div class="player-avatar">{{ initials }}</div>
    <div class="player-info">
      <span class="player-name">{{ player.lastname }}, {{ player.firstname }}</span>
      <div class="player-meta">
        <span v-if="player.jerseyNumber != null" class="jersey">#{{ player.jerseyNumber }}</span>
        <span v-if="player.dob" class="dob">{{ age }} y.o.</span>
        <template v-if="showTeams">
          <span
            v-for="team in player.teams"
            :key="team.id"
            class="team-chip"
            :style="{ background: team.color + '22', borderColor: team.color, color: team.color }"
          >{{ team.name }}</span>
        </template>
      </div>
    </div>
    <button
      v-if="$emit && hasRemove"
      class="btn-remove"
      @click.stop="$emit('remove')"
      title="Remove"
    >✕</button>
  </div>
</template>

<script setup>
import { computed } from 'vue'

const props = defineProps({
  player: { type: Object, required: true },
  showTeams: { type: Boolean, default: false },
})

const emit = defineEmits(['click', 'remove'])
const hasRemove = computed(() => emit !== undefined)

const initials = computed(() =>
  ((props.player.firstname?.[0] ?? '') + (props.player.lastname?.[0] ?? '')).toUpperCase()
)

const age = computed(() => {
  if (!props.player.dob) return null
  const birth = new Date(props.player.dob)
  const diff = Date.now() - birth.getTime()
  return Math.floor(diff / (1000 * 60 * 60 * 24 * 365.25))
})
</script>

<style scoped>
.player-row {
  display: flex;
  align-items: center;
  gap: var(--spacing-sm);
  padding: var(--spacing-sm) var(--spacing-md);
  background: var(--bg-light);
  border: 1px solid var(--border-color);
  border-radius: var(--radius-md);
  cursor: pointer;
  transition: background var(--transition-fast);
}

.player-row:hover {
  background: var(--bg-card);
}

.player-avatar {
  width: 36px;
  height: 36px;
  border-radius: 50%;
  background: var(--primary-color);
  color: white;
  font-size: 0.8rem;
  font-weight: 700;
  display: flex;
  align-items: center;
  justify-content: center;
  flex-shrink: 0;
}

.player-info {
  flex: 1;
  min-width: 0;
}

.player-name {
  font-weight: 600;
  display: block;
  margin-bottom: 2px;
}

.player-meta {
  display: flex;
  flex-wrap: wrap;
  align-items: center;
  gap: var(--spacing-xs);
  font-size: 0.8rem;
  color: var(--text-muted);
}

.jersey {
  font-weight: 700;
  color: var(--secondary-color);
}

.team-chip {
  padding: 1px var(--spacing-xs);
  border-radius: var(--radius-sm);
  font-size: 0.7rem;
  font-weight: 600;
  border: 1px solid;
}

.btn-remove {
  background: none;
  border: none;
  cursor: pointer;
  color: var(--text-muted);
  font-size: 0.9rem;
  padding: 4px;
  border-radius: var(--radius-sm);
  flex-shrink: 0;
}

.btn-remove:hover { color: var(--error-color); }
</style>
