<template>
  <nav class="nav-bar">
    <div class="nav-links">
      <router-link to="/" class="nav-link" exact-active-class="nav-link-active">
        <span class="nav-link-icon">&#127968;</span>
        <span class="nav-link-text">Home</span>
      </router-link>
      <router-link to="/game" class="nav-link" active-class="nav-link-active">
        <span class="nav-link-icon">&#127936;</span>
        <span class="nav-link-text">Game</span>
      </router-link>
      <router-link to="/teams" class="nav-link" active-class="nav-link-active">
        <span class="nav-link-icon">&#128101;</span>
        <span class="nav-link-text">Teams</span>
      </router-link>
      <router-link to="/seasons" class="nav-link" active-class="nav-link-active">
        <span class="nav-link-icon">&#127942;</span>
        <span class="nav-link-text">Seasons</span>
      </router-link>
      <router-link to="/stats" class="nav-link" active-class="nav-link-active">
        <span class="nav-link-icon">&#128202;</span>
        <span class="nav-link-text">Stats</span>
      </router-link>
    </div>
  </nav>
</template>

<script>
export default {
  name: 'NavBar'
}
</script>

<style scoped>
.nav-bar {
  background-color: var(--bg-light);
  border-radius: var(--radius-lg);
  box-shadow: var(--shadow-md);
  margin-bottom: var(--spacing-lg);
  overflow-x: auto;
  -webkit-overflow-scrolling: touch;
}

.nav-links {
  display: flex;
  justify-content: center;
  gap: var(--spacing-xs);
  padding: var(--spacing-sm);
  min-width: max-content;
}

.nav-link {
  display: flex;
  align-items: center;
  gap: var(--spacing-xs);
  padding: var(--spacing-sm) var(--spacing-md);
  border-radius: var(--radius-md);
  text-decoration: none;
  color: var(--text-muted);
  font-weight: 600;
  font-size: 0.9rem;
  transition: all var(--transition-fast);
  white-space: nowrap;
  border: 2px solid transparent;
}

.nav-link:hover {
  color: var(--primary-color);
  background-color: rgba(255, 107, 53, 0.1);
}

.nav-link-active {
  color: white;
  background-color: var(--primary-color);
  border-color: var(--primary-color);
}

.nav-link-active:hover {
  color: white;
  background-color: var(--primary-color);
  opacity: 0.9;
}

.nav-link-icon {
  font-size: 1.1rem;
}

.nav-link-text {
  font-size: 0.85rem;
}

/* Terminal theme */
[data-theme="terminal"] .nav-bar {
  border-radius: 0;
  box-shadow: none;
  border: 2px solid var(--border-color);
  background-color: var(--bg-color);
}

[data-theme="terminal"] .nav-link {
  border-radius: 0;
  color: var(--text-color);
  border: 2px solid transparent;
  font-family: 'IBM Plex Mono', 'Courier New', Courier, monospace;
}

[data-theme="terminal"] .nav-link:hover {
  background-color: var(--highlight-bg);
  color: var(--highlight-text);
}

[data-theme="terminal"] .nav-link-active {
  background-color: var(--highlight-bg);
  color: var(--highlight-text);
  border-color: var(--border-color);
}

[data-theme="terminal"] .nav-link-active:hover {
  background-color: var(--highlight-bg);
  color: var(--highlight-text);
}

/* Mobile */
@media (max-width: 768px) {
  .nav-bar {
    margin-bottom: var(--spacing-md);
  }

  .nav-links {
    justify-content: flex-start;
    padding: var(--spacing-xs);
  }

  .nav-link {
    padding: var(--spacing-xs) var(--spacing-sm);
    font-size: 0.8rem;
  }

  .nav-link-text {
    font-size: 0.75rem;
  }
}
</style>
