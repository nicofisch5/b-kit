<template>
  <div class="placeholder-view">
    <div class="placeholder-content">
      <span class="placeholder-icon">&#128202;</span>
      <h2 class="placeholder-title">Cumulated Stats</h2>
      <p class="placeholder-desc">View aggregated statistics across multiple games and seasons.</p>
      <span class="placeholder-badge">Coming soon</span>
    </div>
  </div>
</template>

<script>
export default {
  name: 'StatsView'
}
</script>

<style scoped>
.placeholder-view {
  display: flex;
  justify-content: center;
  align-items: center;
  min-height: 40vh;
  padding: var(--spacing-xl);
}

.placeholder-content {
  text-align: center;
  padding: var(--spacing-xl);
  background-color: var(--bg-light);
  border-radius: var(--radius-lg);
  box-shadow: var(--shadow-md);
  max-width: 400px;
  width: 100%;
}

.placeholder-icon {
  font-size: 3rem;
  display: block;
  margin-bottom: var(--spacing-md);
}

.placeholder-title {
  font-size: 1.5rem;
  font-weight: 700;
  color: var(--primary-color);
  margin-bottom: var(--spacing-sm);
}

.placeholder-desc {
  color: var(--text-muted);
  margin-bottom: var(--spacing-md);
}

.placeholder-badge {
  display: inline-block;
  background-color: var(--warning-color);
  color: white;
  font-size: 0.85rem;
  font-weight: 700;
  padding: var(--spacing-xs) var(--spacing-md);
  border-radius: var(--radius-sm);
}

[data-theme="terminal"] .placeholder-content {
  border-radius: 0;
  box-shadow: none;
  border: 2px solid var(--border-color);
  background-color: var(--bg-color);
}

[data-theme="terminal"] .placeholder-title {
  color: var(--text-color);
}

[data-theme="terminal"] .placeholder-desc {
  color: var(--text-color);
}

[data-theme="terminal"] .placeholder-badge {
  border-radius: 0;
  background-color: var(--bg-color);
  color: var(--text-color);
  border: 1px solid var(--border-color);
}
</style>
