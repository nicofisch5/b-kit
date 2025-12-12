# Development Guide

## Quick Start for Developers

### First Time Setup

```bash
# 1. Navigate to project
cd /home/nico5/Desktop/Stats-Tracker

# 2. Install dependencies
npm install

# 3. Start dev server
npm run dev

# 4. Open in browser
# http://localhost:5173
```

## Development Workflow

### Daily Development

```bash
# Start dev server (with hot reload)
npm run dev

# Build for production
npm run build

# Preview production build locally
npm run preview
```

### Hot Module Replacement (HMR)

Vite provides instant HMR:
- Edit `.vue` files → Changes appear immediately
- Edit `.css` files → Styles update without refresh
- Edit `.js` files → Module reloads automatically

## Code Organization

### Directory Structure

```
src/
├── components/          # Vue components (UI pieces)
│   ├── ScoreDisplay.vue
│   ├── QuarterSelector.vue
│   ├── PlayerRoster.vue
│   ├── StatsControlPanel.vue
│   ├── PlayerSelectionModal.vue
│   └── ActionBar.vue
├── store/              # State management
│   └── gameStore.js    # All game logic here
├── assets/             # Static assets
│   └── main.css        # Global styles
├── App.vue             # Root component
└── main.js             # Entry point
```

### Code Style Guidelines

#### Vue Components

```vue
<template>
  <!-- Use semantic HTML -->
  <!-- Keep template logic minimal -->
  <!-- Use v-for with :key -->
</template>

<script>
// Use Composition API
import { ref, computed } from 'vue'

export default {
  name: 'ComponentName',
  props: {
    // Define with types
  },
  emits: ['event-name'],
  setup(props, { emit }) {
    // Component logic here
    return {
      // Only expose what template needs
    }
  }
}
</script>
```

#### Store Functions

```javascript
// Export reactive state
export const gameState = reactive({ /* ... */ })

// Export computed values
export function getCalculatedValue() {
  // Pure function, no side effects
  return computed value
}

// Export actions (can mutate state)
export function performAction() {
  // Mutate gameState
  // Call saveGame()
  // Return success/result
}
```

#### CSS

```css
/* Use CSS variables for consistency */
.component {
  color: var(--primary-color);
  padding: var(--spacing-md);
}

/* Use BEM-like naming for clarity */
.component__element--modifier {
  /* styles */
}

/* Mobile-first responsive design */
.component {
  /* base mobile styles */
}

@media (min-width: 768px) {
  /* tablet styles */
}
```

## Common Development Tasks

### 1. Adding a New Statistic

**Step 1**: Add to StatType enum
```javascript
// src/store/gameStore.js
export const StatType = {
  // ... existing stats
  NEW_STAT: 'NEW_STAT'
}
```

**Step 2**: Add scoring logic (if needed)
```javascript
// In recordStat() function
switch (statType) {
  // ... existing cases
  case StatType.NEW_STAT:
    // Update player totals if needed
    player.someTotal += 1
    break
}
```

**Step 3**: Add button to UI
```vue
<!-- src/components/StatsControlPanel.vue -->
<template>
  <div class="stats-section">
    <button @click="handleStatClick(StatType.NEW_STAT, 'New Stat')">
      New Stat
    </button>
  </div>
</template>
```

### 2. Modifying Player Roster

```javascript
// src/store/gameStore.js
function createDefaultPlayers() {
  const playerNames = [
    'Player A', 'Player B', /* ... */
  ]

  return playerNames.map((name, index) => ({
    playerId: generateUUID(),
    jerseyNumber: (index + 1) * 4,  // Modify numbering
    name: name,                       // Change names
    totalPoints: 0,
    totalFouls: 0,
    statistics: []
  }))
}
```

### 3. Changing Auto-Save Interval

```javascript
// src/store/gameStore.js
function scheduleAutoSave() {
  if (autoSaveTimer) clearTimeout(autoSaveTimer)
  autoSaveTimer = setTimeout(() => {
    saveGame()
  }, 30000) // Change this value (milliseconds)
}
```

### 4. Customizing Colors

```css
/* src/assets/main.css */
:root {
  --primary-color: #ff6b35;      /* Home team */
  --secondary-color: #004e89;    /* Opposition */
  --success-color: #2d936c;      /* Made shots */
  --error-color: #c5283d;        /* Missed shots */
  --warning-color: #f4a261;      /* Fouls */
  /* ... modify as needed */
}
```

### 5. Adding a New Component

**Step 1**: Create component file
```bash
touch src/components/NewComponent.vue
```

**Step 2**: Define component
```vue
<template>
  <div class="new-component">
    <!-- Component template -->
  </div>
</template>

<script>
export default {
  name: 'NewComponent',
  setup() {
    // Component logic
  }
}
</script>
```

**Step 3**: Import in App.vue
```vue
<script>
import NewComponent from './components/NewComponent.vue'

export default {
  components: {
    NewComponent
  }
}
</script>
```

**Step 4**: Use in template
```vue
<template>
  <NewComponent />
</template>
```

### 6. Modifying Export Format

**JSON Export** (in gameStore.js):
```javascript
export function exportJSON() {
  // Modify data structure before export
  const exportData = {
    ...gameState,
    customField: 'value'
  }

  const data = JSON.stringify(exportData, null, 2)
  // ... rest of export logic
}
```

**CSV Export** (in gameStore.js):
```javascript
export function exportCSV() {
  // Modify CSV headers
  let csv = 'Player,Jersey,Points,Fouls,NewColumn\n'

  // Modify CSV rows
  gameState.players.forEach(player => {
    csv += `${player.name},${player.jerseyNumber},`
    csv += `${player.totalPoints},${player.totalFouls},`
    csv += `${newValue}\n`
  })

  // ... rest of export logic
}
```

## Debugging

### Vue DevTools

Install Vue DevTools browser extension:
- Chrome: https://chrome.google.com/webstore
- Firefox: https://addons.mozilla.org/firefox

Features:
- Inspect component tree
- View reactive state
- Track events
- Performance profiling

### Console Logging

```javascript
// In any component or store function
console.log('Debug info:', variable)

// In recordStat function
export function recordStat(playerId, statType) {
  console.log('Recording stat:', { playerId, statType })
  // ... rest of function
}
```

### localStorage Inspection

```javascript
// Browser console
localStorage.getItem('basketballGame')

// Pretty print
JSON.parse(localStorage.getItem('basketballGame'))

// Clear data
localStorage.removeItem('basketballGame')
// Then refresh page for fresh start
```

### Vite Dev Server Issues

```bash
# Clear cache and restart
rm -rf node_modules/.vite
npm run dev

# Check port conflicts
lsof -ti:5173

# Use different port
npm run dev -- --port 3000
```

## Testing Locally

### Manual Testing Checklist

#### Basic Functionality
- [ ] Record 2PT Made → Score increases by 2
- [ ] Record 3PT Made → Score increases by 3
- [ ] Record FT Made → Score increases by 1
- [ ] Record Foul → Foul count increases
- [ ] Record 4th foul → Warning appears
- [ ] Record 5th foul → Player marked as fouled out
- [ ] Record Assist → Assist recorded to player
- [ ] Click Undo → Last action reverted

#### Quarter Management
- [ ] Click Q2 → Quarter switches to Q2
- [ ] Click Q3 → Quarter switches to Q3
- [ ] Click Q4 → Quarter switches to Q4
- [ ] Click + OT → OT quarter added
- [ ] Click + OT again → OT2 added
- [ ] Stats recorded in correct quarter

#### Data Persistence
- [ ] Record some stats
- [ ] Close browser tab
- [ ] Reopen app → Data persists
- [ ] Clear localStorage → App resets

#### Export
- [ ] Click Export → Menu opens
- [ ] Export as JSON → File downloads
- [ ] Export as CSV → File downloads
- [ ] Open CSV in Excel → Data formatted correctly

#### Responsive Design
- [ ] Test on desktop (> 1024px)
- [ ] Test on tablet (768px - 1024px)
- [ ] Test on mobile (< 768px)
- [ ] Test landscape mode on mobile
- [ ] All buttons are tappable (min 44x44px)

#### PWA
- [ ] Open DevTools → Application → Service Workers
- [ ] Verify service worker registered
- [ ] Go offline (DevTools → Network → Offline)
- [ ] App still works offline
- [ ] Test PWA installation prompt

### Testing on Real Devices

**Android (Chrome)**:
1. Build for production: `npm run build`
2. Deploy to test server or use `npm run preview`
3. Open on Android device
4. Test touch interactions
5. Test install prompt

**iOS (Safari)**:
1. Same build process
2. Open in Safari on iPhone/iPad
3. Test "Add to Home Screen"
4. Test standalone mode

**Desktop**:
1. Test in Chrome, Firefox, Safari, Edge
2. Test different screen sizes
3. Test keyboard navigation

## Performance Optimization

### Current Performance

All requirements met:
- ✅ Stat recording: < 100ms
- ✅ Score updates: < 200ms
- ✅ Quarter switching: < 300ms

### Monitoring Performance

```javascript
// Add timing to recordStat
export function recordStat(playerId, statType) {
  const startTime = performance.now()

  // ... function logic ...

  const endTime = performance.now()
  console.log(`recordStat took ${endTime - startTime}ms`)
}
```

### Bundle Size Analysis

```bash
# Build and analyze
npm run build

# Check dist/ folder size
du -sh dist/

# Detailed analysis (requires plugin)
npm install -D rollup-plugin-visualizer
# Add to vite.config.js
```

## Common Issues & Solutions

### Issue: App won't start

**Solution**:
```bash
# Delete node_modules and reinstall
rm -rf node_modules package-lock.json
npm install
npm run dev
```

### Issue: Changes not appearing

**Solution**:
- Hard refresh browser: Ctrl+Shift+R (or Cmd+Shift+R on Mac)
- Clear browser cache
- Restart dev server

### Issue: localStorage data corrupted

**Solution**:
```javascript
// In browser console
localStorage.removeItem('basketballGame')
// Refresh page
```

### Issue: PWA not updating

**Solution**:
- Unregister service worker in DevTools
- Clear cache
- Hard refresh
- Reinstall PWA

### Issue: Modal not closing

**Solution**:
- Check if `@cancel` event is emitted
- Check if `showPlayerModal` is set to false
- Check browser console for errors

## Git Workflow (if using version control)

```bash
# Daily workflow
git pull origin main
# ... make changes ...
git add .
git commit -m "Description of changes"
git push origin main

# Feature branches
git checkout -b feature/new-stat-type
# ... make changes ...
git commit -m "Add new stat type"
git push origin feature/new-stat-type
# Create pull request
```

### Commit Message Guidelines

```
feat: Add new statistic type for technical fouls
fix: Correct score calculation for overtime periods
style: Update button colors for better contrast
docs: Update README with deployment instructions
refactor: Simplify player selection logic
perf: Optimize auto-save debouncing
```

## Documentation Updates

When making changes, update:
- [ ] Code comments (if logic is complex)
- [ ] README.md (if user-facing features change)
- [ ] `.claude/` files (if architecture changes)
- [ ] SETUP_GUIDE.md (if setup process changes)

## Code Review Checklist

Before committing code:
- [ ] Code works in development
- [ ] Code works in production build
- [ ] No console errors
- [ ] Responsive on mobile/tablet/desktop
- [ ] Auto-save still works
- [ ] Export functions still work
- [ ] No breaking changes to existing features
- [ ] Code follows style guidelines
- [ ] Added comments for complex logic

## Resources

### Official Documentation
- Vue.js 3: https://vuejs.org/
- Vite: https://vitejs.dev/
- PWA: https://web.dev/progressive-web-apps/

### Learning Resources
- Vue Mastery: https://www.vuemastery.com/
- Vue School: https://vueschool.io/
- MDN Web Docs: https://developer.mozilla.org/

### Tools
- Vue DevTools: Browser extension for debugging
- VS Code: Recommended editor with Volar extension
- Chrome DevTools: For debugging PWA features

## Getting Help

### Within Project
1. Check `.claude/` documentation
2. Check README.md
3. Check code comments
4. Check console for errors

### External Resources
1. Vue.js Discord: https://discord.com/invite/vue
2. Stack Overflow: Tag with `vue.js` and `vite`
3. GitHub Issues: (if open source)

---

**Last Updated**: 2025-12-12
