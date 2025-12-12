# Quick Reference Card

Ultra-condensed reference for instant context loading.

## Project Type
**Vue.js 3 PWA** - B-Strack: Basketball stats tracker for home team only

## Status
✅ **Production Ready** - All features complete
🆕 **Updated**: 2025-12-12 - Major UI/UX improvements

## Tech Stack
- Vue 3 (Composition API)
- Vite 5
- localStorage
- PWA (vite-plugin-pwa)
- CSS3 (no framework)

## Key Files
- **Brain**: `src/store/gameStore.js` (all game logic)
- **UI Root**: `src/App.vue`
- **Styles**: `src/assets/main.css`
- **Components**: `src/components/*.vue`

## Data Model
```
gameState {
  players[12] { id, jersey, name, points, fouls, statistics[] }
  quarters[] { id, name, statistics[] }
  currentQuarter, oppositionScore, history[]
}
```

## Core Features
1. Record stats via modal player selection (pre-selects if player chosen)
2. Made shots → optional assist tracking
3. Auto-score calculation (2PT=2, 3PT=3, FT=1)
4. Foul warnings at 5 fouls
5. Quarter switching (Q1-Q4 + unlimited OT)
6. Quarter logs with individual action revert
7. Undo last action
8. Editable player names and jersey numbers (click to edit)
9. Auto-save every 30s to localStorage
10. Export JSON/CSV

## Statistics Tracked
✅ 2PT/3PT/FT (Made/Miss)
✅ Off/Def Rebounds
✅ Assist, Steal, Block
✅ Foul, Turnover

❌ NOT tracked: Time, Putback, Take Charge, Court Position

## User Flow
1. Click stat button
2. Select player from modal
3. (If made shot) Select assist or skip
4. Done - score updates automatically

## Quick Commands
```bash
npm install          # Setup
npm run dev          # Develop
npm run build        # Production
npm run preview      # Test production
```

## Quick Edits

### Change player names
`src/store/gameStore.js` → `createDefaultPlayers()`

### Change colors
`src/assets/main.css` → `:root` variables

### Add new stat
1. `src/store/gameStore.js` → add to `StatType`
2. `src/store/gameStore.js` → add logic in `recordStat()`
3. `src/components/StatsControlPanel.vue` → add button

### Change auto-save interval
`src/store/gameStore.js` → `scheduleAutoSave()` → change 30000

## Important Functions

### gameStore.js exports:
- `gameState` - Main reactive state
- `recordStat(playerId, statType, assistId?)` - Record action
- `undoLastAction()` - Undo
- `switchQuarter(name)` - Change quarter
- `getTotalHomeScore()` - Calculate score
- `saveGame()` - Manual save
- `exportJSON()` / `exportCSV()` - Export

## Color Scheme (Light Theme)
```css
--primary-color: #ff6b35    (Home/Orange)
--secondary-color: #004e89  (Opposition/Blue)
--success-color: #2d936c    (Made/Green)
--error-color: #c5283d      (Miss/Red)
--warning-color: #f4a261    (Fouls/Orange)
--bg-dark: #f5f5f5          (Light gray bg)
--bg-light: #ffffff         (White bg)
--text-light: #2c3e50       (Dark text)
```

## Responsive Breakpoints
- Desktop: >1024px
- Tablet: 768-1024px
- Mobile: <768px

## Storage
- **Where**: Browser localStorage
- **Key**: `basketballGame`
- **Format**: JSON string of gameState

## PWA
- Offline capable
- Installable
- Auto-updating service worker
- Icons needed: 192x192, 512x512, favicon, apple-touch-icon

## Deployment
1. `npm run build`
2. Upload `dist/` to static hosting (Netlify/Vercel/etc)
3. Done

## Troubleshooting

### App won't start
```bash
rm -rf node_modules package-lock.json
npm install
```

### Data corrupted
```javascript
// In browser console:
localStorage.removeItem('basketballGame')
// Refresh page
```

### Changes not appearing
- Hard refresh: Ctrl+Shift+R
- Clear cache
- Restart dev server

## Architecture Pattern
**Reactive State Pattern**
```
User Action → Component Handler → Store Function →
Mutate State → Vue Reactivity → UI Update
```

## Component Tree
```
App
├── ScoreDisplay
├── QuarterSelector
├── PlayerRoster (editable names/numbers)
├── StatsControlPanel
├── QuarterLogs (NEW - current quarter actions with revert)
├── PlayerSelectionModal (conditional, with pre-selection)
└── ActionBar
```

## State Access
- All components read from `gameState` directly
- Only store functions mutate state
- Vue reactivity handles UI updates automatically

## Critical Business Rules
1. Fouled out players (5+ fouls) can't score
2. Assist only on made shots (2PT/3PT/FT)
3. Stats tied to specific quarters
4. Totals cumulative across quarters
5. Undo reverts both stat and assist (if present)

## Performance Targets (Met)
- Stat record: <100ms ✅
- Score update: <200ms ✅
- Quarter switch: <300ms ✅

## Documentation Structure
```
.claude/
├── project-context.md    (Overview, status, decisions)
├── architecture.md       (Technical architecture, data flow)
├── development-guide.md  (How-to guides, workflows)
├── file-reference.md     (What each file does)
└── quick-reference.md    (This file)
```

## When User Asks...

**"What is this project?"**
→ Read `project-context.md`

**"How does it work?"**
→ Read `architecture.md`

**"How do I change X?"**
→ Read `development-guide.md` or `file-reference.md`

**"Quick overview"**
→ Read this file

## Development Workflow
1. Read relevant `.claude/` files for context
2. Make changes
3. Test with `npm run dev`
4. Build with `npm run build`
5. Preview with `npm run preview`

## Common Questions

**Q: How to add a player?**
A: Edit `createDefaultPlayers()` in gameStore.js

**Q: How to change team name?**
A: Edit `loadGame()` in gameStore.js → `homeTeam` value

**Q: How to add new quarter type?**
A: Quarters are dynamic - just switch to it (e.g., OT3, OT4)

**Q: Where is data stored?**
A: Browser localStorage, key: `basketballGame`

**Q: Can I use this offline?**
A: Yes, PWA works offline after first visit

**Q: How to reset game?**
A: Click "New Game" button or clear localStorage

## Files to Check First

### For functionality issues:
1. `src/store/gameStore.js` (99% of logic here)
2. `src/components/PlayerSelectionModal.vue` (modal behavior)
3. `src/App.vue` (orchestration)

### For style issues:
1. `src/assets/main.css` (all styles)
2. Component `.vue` files (component-specific)

### For build/config issues:
1. `vite.config.js` (build config)
2. `package.json` (dependencies)

## One-Liners

- **Game logic**: All in gameStore.js
- **UI**: Vue 3 components
- **Styles**: CSS variables in main.css
- **Data**: localStorage JSON
- **State**: Reactive with Vue 3
- **PWA**: vite-plugin-pwa (auto)
- **Deploy**: Build dist/, upload anywhere

## Remember

🎯 **Keep it simple** - No over-engineering
📦 **Single source of truth** - gameStore.js
🎨 **CSS variables** - Easy theming
💾 **Auto-save** - Data safe every 30s
📱 **Mobile-first** - Responsive design
⚡ **Fast** - All targets met

---

**Last Updated**: 2025-12-12
**Version**: 1.1 - Major updates:
- Changed app name to "B-Strack"
- Switched to light theme
- Added editable player names/numbers
- Added quarter logs with revert
- Pre-selection in modal when player selected
- Opposition score display matches home height
