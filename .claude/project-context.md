# B-Strack - Project Context

## Project Overview

**B-Strack** is a Progressive Web Application (PWA) for tracking basketball game statistics in real-time, designed for coaches, statisticians, and team staff. The app tracks comprehensive statistics for a home team during basketball games, organized by quarter and player.

## Key Information

- **Framework**: Vue.js 3 (Composition API)
- **Build Tool**: Vite 5
- **Type**: Progressive Web App (PWA)
- **Storage**: Browser localStorage
- **Target Devices**: Mobile, Tablet, Desktop
- **Language**: JavaScript (no TypeScript)
- **Styling**: CSS3 with CSS Variables (no framework)

## Current Status

✅ **PRODUCTION READY** - All features implemented and tested

### Completed Features
- ✅ Complete Vue.js 3 PWA implementation
- ✅ 12-player roster system with editable names/numbers
- ✅ Quarter management (Q1-Q4 + unlimited OT)
- ✅ Modal-based player selection for stats (with pre-selection)
- ✅ Assist tracking for made shots
- ✅ All required statistics tracking
- ✅ Automatic score calculation
- ✅ Foul tracking with warnings
- ✅ Quarter logs with individual action revert
- ✅ Undo functionality
- ✅ Auto-save every 30 seconds
- ✅ Export to JSON and CSV
- ✅ Fully responsive design (light theme)
- ✅ Offline PWA capabilities
- ✅ Complete documentation

## Core Business Logic

### Player Management
- 12 players per roster (configurable in gameStore.js)
- Players identified by: playerId (UUID), jerseyNumber, name
- Tracks: totalPoints, totalFouls, individual statistics

### Quarter System
- Base quarters: Q1, Q2, Q3, Q4
- Unlimited overtime periods: OT, OT2, OT3, etc.
- Stats associated with specific quarters
- Cumulative totals across all quarters

### Statistics Tracked
1. **Scoring**: 2PT Made/Miss, 3PT Made/Miss, FT Made/Miss
2. **Rebounds**: Offensive, Defensive
3. **Playmaking**: Assist, Steal, Block
4. **Negative**: Foul, Turnover

### Special Rules
- Made shots (2PT, 3PT, FT) can have optional assist
- Players with 5+ fouls marked as "fouled out"
- Fouled out players cannot be selected for made shots
- Foul warning displays at 4 fouls

### Data Flow
1. User clicks stat button → Modal opens
2. User selects player → For made shots, assist prompt appears
3. Stat recorded to:
   - Player statistics array
   - Quarter statistics array
   - Game history (for undo)
4. Automatic updates:
   - Player totals (points, fouls)
   - Team score
   - UI displays

## Technical Architecture

### State Management
- **Single reactive game state** in `gameStore.js`
- Vue 3 reactivity system (no Vuex/Pinia)
- All components import from central store
- Auto-save watcher on state changes

### Component Hierarchy
```
App.vue
├── ScoreDisplay.vue (home/opposition scores)
├── QuarterSelector.vue (quarter buttons)
├── Main Content
│   ├── PlayerRoster.vue (player list)
│   └── StatsControlPanel.vue (stat buttons)
├── PlayerSelectionModal.vue (modal popup)
└── ActionBar.vue (undo, save, export)
```

### Data Model
```
Game Object
├── gameId (UUID)
├── homeTeam (string)
├── oppositionTeam (string)
├── date (ISO 8601)
├── oppositionScore (integer)
├── currentQuarter (string)
├── overtimeCount (integer)
├── history (array) - for undo
├── quarters[] - Quarter Objects
│   ├── quarterId (UUID)
│   ├── quarterName (Q1/Q2/Q3/Q4/OT)
│   └── statistics[] - StatEvent Objects
└── players[] - Player Objects
    ├── playerId (UUID)
    ├── jerseyNumber (integer)
    ├── name (string)
    ├── totalPoints (integer)
    ├── totalFouls (integer)
    └── statistics[] - StatEvent Objects

StatEvent Object
├── eventId (UUID)
├── playerId (UUID reference)
├── quarterId (UUID reference)
├── timestamp (ISO 8601)
├── statType (enum)
└── value (integer/boolean)
```

## Important Constraints

### What NOT to Remove
- **Deflect stat** - Still in the requirements, don't remove from code
- **Jump Ball stat** - Still in the requirements, keep it
- **Forced Turnover stat** - Keep in store, just not in UI
- **Forced Rush Shot stat** - Keep in store, just not in UI

### Design Decisions
- **No player pre-selection**: Modal opens immediately on stat click
- **Assist is optional**: User can skip assist for made shots
- **No time tracking**: Per requirements, no game clock
- **No court visualization**: Per requirements, no court diagram
- **Home team only**: Only track home team statistics
- **Opposition score manual**: User manually updates opponent score

## Common Development Tasks

### Adding a New Stat Type
1. Add to `StatType` enum in `src/store/gameStore.js`
2. Add scoring logic in `recordStat()` function (if affects points/fouls)
3. Add button to `src/components/StatsControlPanel.vue`
4. Add to export CSV columns (if needed)

### Modifying Player Roster
- Edit `createDefaultPlayers()` in `src/store/gameStore.js`
- Change player names, jersey numbers, or count

### Changing Colors/Theme
- Edit CSS variables in `src/assets/main.css` under `:root`

### Modifying Auto-save Interval
- Edit timeout in `scheduleAutoSave()` in `src/store/gameStore.js`
- Currently: 30000ms (30 seconds)

## Known Limitations

1. **No backend**: All data stored in browser localStorage
2. **No multi-user**: Single-user application
3. **No authentication**: No user accounts
4. **No cloud sync**: Data stays on device
5. **Browser-dependent**: Data lost if localStorage cleared

## Future Enhancement Ideas (Not Implemented)

- Video integration for play review
- Advanced analytics and shooting charts
- Multi-game season tracking
- Cloud synchronization
- Real-time multi-user collaboration
- Automatic play-by-play generation
- Player performance trends

## Dependencies

### Production
- `vue@^3.4.15` - Core framework

### Development
- `vite@^5.0.11` - Build tool and dev server
- `@vitejs/plugin-vue@^5.0.3` - Vue plugin for Vite
- `vite-plugin-pwa@^0.17.5` - PWA functionality

## File Locations Quick Reference

| Purpose | File Path |
|---------|-----------|
| Game state & logic | `src/store/gameStore.js` |
| Main app component | `src/App.vue` |
| Global styles | `src/assets/main.css` |
| Player list | `src/components/PlayerRoster.vue` |
| Stat buttons | `src/components/StatsControlPanel.vue` |
| Player selection | `src/components/PlayerSelectionModal.vue` |
| Score display | `src/components/ScoreDisplay.vue` |
| Quarter selector | `src/components/QuarterSelector.vue` |
| Action buttons | `src/components/ActionBar.vue` |
| PWA config | `vite.config.js` |
| Requirements doc | `Basketball_Stats_Tracker_Requirements.md` |

## Quick Commands

```bash
# Development
npm install          # Install dependencies
npm run dev          # Start dev server (localhost:5173)

# Production
npm run build        # Build for production
npm run preview      # Preview production build

# Deployment
# Build creates files in dist/ directory
# Deploy dist/ to any static hosting
```

## Color Scheme (Light Theme)

- **Primary (Home)**: `#ff6b35` (Orange)
- **Secondary (Opposition)**: `#004e89` (Blue)
- **Success (Made shots)**: `#2d936c` (Green)
- **Error (Missed shots)**: `#c5283d` (Red)
- **Warning (Fouls)**: `#f4a261` (Orange-yellow)
- **Neutral**: `#457b9d` (Blue-gray)
- **Background Dark**: `#f5f5f5` (Light gray)
- **Background Light**: `#ffffff` (White)
- **Text Light**: `#2c3e50` (Dark gray/black)

## Browser Support

- Chrome/Edge: Latest 2 versions ✅
- Firefox: Latest 2 versions ✅
- Safari: Latest 2 versions ✅
- Mobile browsers: iOS Safari, Chrome Android ✅

## Last Updated

2025-12-12 - Version 1.1
- Changed app name to "B-Strack"
- Switched from dark to light theme
- Added editable player names and jersey numbers
- Added quarter logs section with individual action revert
- Implemented player pre-selection in modal
- Fixed opposition score display height to match home
- Updated all documentation
