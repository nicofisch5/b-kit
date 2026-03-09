<template>
  <div class="home-view">
    <div class="home-welcome">
      <h2 class="home-title">Dashboard</h2>
      <p class="home-subtitle">Basketball Statistics Tracker</p>
    </div>

    <div class="home-nav-cards">
      <router-link to="/game" class="nav-card">
        <span class="nav-card-icon">&#127936;</span>
        <h3 class="nav-card-title">Game Tracker</h3>
        <p class="nav-card-desc">Track live game stats, scores, and player performance</p>
      </router-link>

      <router-link to="/teams" class="nav-card nav-card-soon">
        <span class="nav-card-icon">&#128101;</span>
        <h3 class="nav-card-title">Teams</h3>
        <p class="nav-card-desc">Manage your teams and rosters</p>
        <span class="nav-card-badge">Coming soon</span>
      </router-link>

      <router-link to="/seasons" class="nav-card nav-card-soon">
        <span class="nav-card-icon">&#127942;</span>
        <h3 class="nav-card-title">Seasons</h3>
        <p class="nav-card-desc">Organize games into seasons and championships</p>
        <span class="nav-card-badge">Coming soon</span>
      </router-link>

      <router-link to="/stats" class="nav-card nav-card-soon">
        <span class="nav-card-icon">&#128202;</span>
        <h3 class="nav-card-title">Cumulated Stats</h3>
        <p class="nav-card-desc">View aggregated statistics across games</p>
        <span class="nav-card-badge">Coming soon</span>
      </router-link>
    </div>
  </div>
</template>

<script>
export default {
  name: 'HomeView'
}
</script>

<style scoped>
.home-view {
  padding: var(--spacing-md) 0;
}

.home-welcome {
  text-align: center;
  margin-bottom: var(--spacing-xl);
}

.home-title {
  font-size: clamp(1.5rem, 4vw, 2rem);
  font-weight: 700;
  color: var(--primary-color);
  margin-bottom: var(--spacing-xs);
}

.home-subtitle {
  color: var(--text-muted);
  font-size: 1rem;
}

.home-nav-cards {
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(240px, 1fr));
  gap: var(--spacing-lg);
}

.nav-card {
  display: flex;
  flex-direction: column;
  align-items: center;
  text-align: center;
  padding: var(--spacing-xl) var(--spacing-lg);
  background-color: var(--bg-light);
  border-radius: var(--radius-lg);
  box-shadow: var(--shadow-md);
  text-decoration: none;
  color: var(--text-light);
  transition: all var(--transition-normal);
  position: relative;
  border: 2px solid transparent;
}

.nav-card:hover {
  transform: translateY(-4px);
  box-shadow: var(--shadow-lg);
  border-color: var(--primary-color);
}

.nav-card-icon {
  font-size: 2.5rem;
  margin-bottom: var(--spacing-md);
}

.nav-card-title {
  font-size: 1.2rem;
  font-weight: 700;
  margin-bottom: var(--spacing-sm);
  color: var(--primary-color);
}

.nav-card-desc {
  font-size: 0.9rem;
  color: var(--text-muted);
  line-height: 1.4;
}

.nav-card-badge {
  position: absolute;
  top: var(--spacing-sm);
  right: var(--spacing-sm);
  background-color: var(--warning-color);
  color: white;
  font-size: 0.7rem;
  font-weight: 700;
  padding: 2px 8px;
  border-radius: var(--radius-sm);
}

.nav-card-soon {
  opacity: 0.7;
}

.nav-card-soon:hover {
  opacity: 1;
}

/* Terminal theme */
[data-theme="terminal"] .home-title {
  color: var(--text-color);
}

[data-theme="terminal"] .home-subtitle {
  color: var(--text-color);
}

[data-theme="terminal"] .nav-card {
  border-radius: 0;
  box-shadow: none;
  border: 2px solid var(--border-color);
  background-color: var(--bg-color);
}

[data-theme="terminal"] .nav-card:hover {
  background-color: var(--highlight-bg);
  color: var(--highlight-text);
  transform: none;
  box-shadow: none;
}

[data-theme="terminal"] .nav-card:hover .nav-card-title,
[data-theme="terminal"] .nav-card:hover .nav-card-desc {
  color: var(--highlight-text);
}

[data-theme="terminal"] .nav-card-title {
  color: var(--text-color);
}

[data-theme="terminal"] .nav-card-desc {
  color: var(--text-color);
}

[data-theme="terminal"] .nav-card-badge {
  border-radius: 0;
  background-color: var(--bg-color);
  color: var(--text-color);
  border: 1px solid var(--border-color);
}

@media (max-width: 480px) {
  .home-nav-cards {
    grid-template-columns: 1fr;
  }
}
</style>
