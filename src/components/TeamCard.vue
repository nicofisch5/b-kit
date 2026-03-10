<template>
  <div class="team-card" @click="$emit('click')">
    <div class="team-card-accent" :style="{ background: team.color }"></div>
    <div class="team-card-body">
      <div class="team-card-header">
        <span class="team-short-name" :style="{ color: team.color }">{{ team.shortName }}</span>
        <span class="team-category">{{ team.category }}</span>
      </div>
      <h3 class="team-name">{{ team.name }}</h3>
      <p class="team-player-count">{{ team.playerCount }} player{{ team.playerCount !== 1 ? 's' : '' }}</p>
    </div>
  </div>
</template>

<script setup>
defineProps({ team: { type: Object, required: true } })
defineEmits(['click'])
</script>

<style scoped>
.team-card {
  background: var(--bg-light);
  border: 1px solid var(--border-color);
  border-radius: var(--radius-lg);
  overflow: hidden;
  cursor: pointer;
  transition: box-shadow var(--transition-fast), transform var(--transition-fast);
  display: flex;
  flex-direction: column;
}

.team-card:hover {
  box-shadow: var(--shadow-md);
  transform: translateY(-2px);
}

.team-card-accent {
  height: 6px;
}

.team-card-body {
  padding: var(--spacing-md);
}

.team-card-header {
  display: flex;
  align-items: center;
  justify-content: space-between;
  margin-bottom: var(--spacing-xs);
}

.team-short-name {
  font-size: 0.85rem;
  font-weight: 700;
  text-transform: uppercase;
  letter-spacing: 0.05em;
}

.team-category {
  font-size: 0.75rem;
  font-weight: 600;
  color: var(--text-muted);
  background: var(--bg-card);
  border: 1px solid var(--border-color);
  padding: 1px var(--spacing-xs);
  border-radius: var(--radius-sm);
}

.team-name {
  font-size: 1.1rem;
  font-weight: 700;
  color: var(--text-light);
  margin-bottom: var(--spacing-xs);
}

.team-player-count {
  font-size: 0.85rem;
  color: var(--text-muted);
}
</style>
