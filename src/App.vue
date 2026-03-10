<!--
  B-Strack - Basketball Statistics Tracker

  IMPORTANT - DOCUMENTATION MAINTENANCE:
  When making changes to this app, always update:
  - SETUP_GUIDE.md (developer documentation)
  - Basketball_Stats_Tracker_Requirements.md (technical specs)
  - ONBOARDING.html (user guide)

  These files should always reflect the current implementation.
-->
<template>
  <div id="app" class="app-container">
    <UpdateNotification />
    <AutoSaveIndicator />

    <header class="app-header">
      <img src="/b-Strack_logo-t.png" alt="B-Strack Logo" class="app-logo" />
      <h1 class="app-title">B-Strack</h1>
    </header>

    <NavBar v-if="showNav" />

    <router-view />

    <ThemeToggle />

    <footer class="app-footer">
      <a href="/ONBOARDING.html" target="_blank" class="footer-link">
        <span class="footer-icon">&#128214;</span>
        User Guide & Help
      </a>
      <span class="footer-version">v{{ appVersion }}</span>
    </footer>
  </div>
</template>

<script>
import ThemeToggle from './components/ThemeToggle.vue'
import UpdateNotification from './components/UpdateNotification.vue'
import AutoSaveIndicator from './components/AutoSaveIndicator.vue'
import NavBar from './components/NavBar.vue'
import { version } from '../package.json'
import { computed } from 'vue'
import { useRoute } from 'vue-router'

export default {
  name: 'App',
  components: {
    ThemeToggle,
    UpdateNotification,
    AutoSaveIndicator,
    NavBar
  },
  setup() {
    const appVersion = version
    const route = useRoute()
    const showNav = computed(() => !route.meta?.public)

    return {
      appVersion,
      showNav,
    }
  }
}
</script>
