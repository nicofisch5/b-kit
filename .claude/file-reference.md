# File Reference Guide

Quick reference for all project files, their purpose, and when to modify them.

## Core Application Files

### `src/App.vue`
**Purpose**: Root Vue component that orchestrates the entire application

**Contains**:
- Main layout structure
- Modal visibility state
- Notification system
- Event handlers for child components

**Modify when**:
- Adding new top-level components
- Changing overall layout
- Adding new modals or overlays
- Modifying notification system

**Key functions**:
- `handleStatClicked()` - Opens player selection modal
- `handlePlayerSelectedFromModal()` - Closes modal after selection
- `handleUndo()` - Triggers undo action
- `showNotification()` - Displays toast notifications

---

### `src/main.js`
**Purpose**: Application entry point

**Contains**:
- Vue app initialization
- CSS import
- App mounting

**Modify when**:
- Adding global plugins
- Adding router (if needed)
- Adding i18n (if needed)

**Rarely modified** - Only for major architectural changes

---

### `src/store/gameStore.js`
**Purpose**: **MOST IMPORTANT FILE** - All game logic and state management

**Contains**:
- Game state (reactive)
- StatType enum
- All game logic functions
- Data persistence
- Export functions

**Key exports**:
- `gameState` - Main reactive state
- `StatType` - Stat type enum
- `recordStat()` - Record a statistic
- `undoLastAction()` - Undo last action
- `switchQuarter()` - Change quarter
- `updateOppositionScore()` - Update opposition score
- `getTotalHomeScore()` - Calculate home score
- `saveGame()` - Save to localStorage
- `exportJSON()` - Export as JSON
- `exportCSV()` - Export as CSV
- `resetGame()` - Start new game

**Modify when**:
- Adding new statistics
- Changing scoring rules
- Modifying player roster
- Changing data structure
- Adding new game logic
- Modifying auto-save behavior
- Changing export format

**Critical sections**:
- `createDefaultPlayers()` - Initial player setup
- `recordStat()` - Main stat recording logic
- `undoLastAction()` - Undo implementation
- Auto-save watcher (lines after gameState definition)

---

## Component Files

### `src/components/ScoreDisplay.vue`
**Purpose**: Display home and opposition scores with manual opposition control

**Contains**:
- Home score display (computed from game state)
- Opposition score input with +/- buttons
- Score update handlers

**Modify when**:
- Changing score display format
- Adding new score-related features
- Modifying opposition score controls

**Dependencies**:
- `gameState` from store
- `getTotalHomeScore()` from store
- `updateOppositionScore()` from store

---

### `src/components/QuarterSelector.vue`
**Purpose**: Quarter selection buttons (Q1, Q2, Q3, Q4, OT)

**Contains**:
- Quarter button grid
- Active quarter highlighting
- Overtime addition logic

**Modify when**:
- Changing quarter display
- Modifying overtime behavior
- Adding new time periods

**Dependencies**:
- `gameState` from store
- `switchQuarter()` from store

---

### `src/components/PlayerRoster.vue`
**Purpose**: Display list of players with their stats

**Contains**:
- Player card list
- Player selection visual state
- Foul warning indicators

**Modify when**:
- Changing player card design
- Adding new player information
- Modifying foul warning thresholds

**Props**:
- `selectedPlayerId` - Currently selected player

**Emits**:
- `player-selected` - When player is clicked

**Dependencies**:
- `gameState` from store

---

### `src/components/StatsControlPanel.vue`
**Purpose**: Grid of statistic buttons

**Contains**:
- Scoring stats buttons (2PT, 3PT, FT)
- Rebounding stats buttons (Off Reb, Def Reb)
- Playmaking stats buttons (Assist, Steal, Block, Foul, Turnover)

**Modify when**:
- Adding new statistics
- Removing statistics
- Changing button layout
- Modifying button styles

**Emits**:
- `stat-clicked` - When any stat button is clicked

**Dependencies**:
- `StatType` from store

**Important**: Button classes (`made`, `miss`, `neutral`, `positive`, `negative`) control colors via CSS

---

### `src/components/PlayerSelectionModal.vue`
**Purpose**: Modal for selecting player when recording a stat

**Contains**:
- Player selection grid
- Assist selection logic (for made shots)
- Player availability checking (fouled out players)

**Modify when**:
- Changing modal layout
- Modifying player selection behavior
- Adding new selection options
- Changing assist tracking logic

**Props**:
- `statType` - Type of stat being recorded
- `statLabel` - Display label for stat

**Emits**:
- `player-selected` - When player(s) selected
- `cancel` - When modal is cancelled

**Dependencies**:
- `gameState` from store
- `StatType` from store
- `recordStat()` from store

**Critical logic**:
- Made shot detection → triggers assist prompt
- Fouled out player disabling
- Assist player filtering (excludes scoring player)

---

### `src/components/ActionBar.vue`
**Purpose**: Bottom action buttons (Undo, Save, Export, New Game)

**Contains**:
- Action buttons
- Export dropdown menu
- Reset confirmation

**Modify when**:
- Adding new actions
- Changing button layout
- Modifying export options

**Emits**:
- `undo` - Undo last action
- `save` - Save game
- `export` - Export game (with format parameter)

**Dependencies**:
- `resetGame()` from store

---

## Style Files

### `src/assets/main.css`
**Purpose**: Global styles and CSS variables

**Contains**:
- CSS variables (colors, spacing, etc.)
- Component styles
- Responsive breakpoints
- Accessibility styles

**Sections**:
1. Reset & Base Styles
2. CSS Variables (`:root`)
3. App Container
4. Score Display
5. Quarter Selector
6. Player Roster
7. Stats Control Panel
8. Action Bar
9. Player Selection Modal
10. Notifications
11. Responsive Design (@media queries)
12. Scrollbar Styling
13. Accessibility
14. Print Styles

**Modify when**:
- Changing colors/theme
- Adjusting spacing
- Modifying responsive breakpoints
- Changing button sizes
- Adding new component styles

**Important variables**:
```css
--primary-color: #ff6b35     /* Home team */
--secondary-color: #004e89   /* Opposition */
--success-color: #2d936c     /* Made shots */
--error-color: #c5283d       /* Missed shots */
--warning-color: #f4a261     /* Fouls */
```

---

## Configuration Files

### `package.json`
**Purpose**: Project dependencies and scripts

**Modify when**:
- Adding new dependencies
- Updating package versions
- Adding custom scripts
- Changing project metadata

**Key scripts**:
- `npm run dev` - Start dev server
- `npm run build` - Build for production
- `npm run preview` - Preview production build

---

### `vite.config.js`
**Purpose**: Vite build configuration and PWA settings

**Contains**:
- Vue plugin configuration
- PWA plugin configuration
- Manifest settings
- Service worker settings

**Modify when**:
- Changing PWA settings
- Modifying build behavior
- Adding new Vite plugins
- Changing output directory

**Critical sections**:
- `manifest` object - PWA manifest configuration
- `workbox` object - Service worker caching rules

---

### `index.html`
**Purpose**: HTML entry point

**Contains**:
- Meta tags for PWA
- App mount point
- Script imports

**Modify when**:
- Adding meta tags
- Changing page title
- Adding external scripts
- Modifying PWA meta tags

**Rarely modified** after initial setup

---

## Public Assets

### `public/manifest.json`
**Purpose**: PWA manifest file

**Contains**:
- App name and description
- Theme colors
- Display mode
- Icon definitions

**Modify when**:
- Changing app name
- Updating theme colors
- Adding new icon sizes
- Changing display mode

---

### `public/icon-*.png` (Not created yet)
**Purpose**: PWA icons

**Required sizes**:
- `icon-192x192.png` - Android
- `icon-512x512.png` - Android, splash screens
- `favicon.ico` - Browser tab
- `apple-touch-icon.png` - iOS home screen

**Create when**: Deploying to production

---

## Documentation Files

### `README.md`
**Purpose**: Main project documentation

**Contains**:
- Features overview
- Installation instructions
- Usage guide
- Technical stack
- Troubleshooting

**Modify when**:
- Adding new features
- Updating setup process
- Adding troubleshooting tips
- Changing technical stack

---

### `SETUP_GUIDE.md`
**Purpose**: Quick setup instructions

**Contains**:
- Step-by-step setup
- Deployment options
- Common issues
- Customization tips

**Modify when**:
- Setup process changes
- Adding new deployment options

---

### `Basketball_Stats_Tracker_Requirements.md`
**Purpose**: Original requirements document

**Contains**:
- Functional requirements
- Technical requirements
- UI specifications
- Data model

**Modify when**:
- Requirements change
- New features requested
- Specifications updated

---

### `.claude/project-context.md`
**Purpose**: High-level project context for Claude

**Modify when**:
- Project scope changes
- Major features added/removed
- Architecture changes

---

### `.claude/architecture.md`
**Purpose**: Detailed architecture documentation

**Modify when**:
- Architecture changes
- Data model changes
- Component relationships change

---

### `.claude/development-guide.md`
**Purpose**: Development workflow and common tasks

**Modify when**:
- Development process changes
- Adding new common tasks
- Tools change

---

### `.claude/file-reference.md`
**Purpose**: This file - quick reference for all files

**Modify when**:
- New files added
- File purposes change

---

## Git Files

### `.gitignore`
**Purpose**: Files to exclude from Git

**Contains**:
- node_modules
- dist/
- Build artifacts
- Editor files

**Modify when**:
- Need to ignore additional files
- Adding new build outputs

---

## File Modification Frequency

### Frequently Modified
- `src/store/gameStore.js` - Game logic changes
- `src/assets/main.css` - Style updates
- `src/components/*.vue` - UI changes

### Occasionally Modified
- `src/App.vue` - Layout changes
- `vite.config.js` - Build settings
- `package.json` - Dependencies
- Documentation files

### Rarely Modified
- `src/main.js` - Only for major changes
- `index.html` - Set once at start
- `public/manifest.json` - Set once at start
- `.gitignore` - Set once at start

---

## File Dependencies Map

```
index.html
  └─ src/main.js
      └─ src/App.vue
          ├─ src/components/ScoreDisplay.vue → gameStore.js
          ├─ src/components/QuarterSelector.vue → gameStore.js
          ├─ src/components/PlayerRoster.vue → gameStore.js
          ├─ src/components/StatsControlPanel.vue → gameStore.js
          ├─ src/components/PlayerSelectionModal.vue → gameStore.js
          ├─ src/components/ActionBar.vue → gameStore.js
          └─ src/assets/main.css
```

All components depend on `gameStore.js` - it's the heart of the application.

---

## Quick File Lookup

**Need to change...**

| What | File to Edit |
|------|-------------|
| Player names | `src/store/gameStore.js` → `createDefaultPlayers()` |
| Colors | `src/assets/main.css` → `:root` variables |
| Add new stat | `src/store/gameStore.js` + `src/components/StatsControlPanel.vue` |
| Button layout | `src/components/StatsControlPanel.vue` |
| Score display | `src/components/ScoreDisplay.vue` |
| Quarter behavior | `src/components/QuarterSelector.vue` |
| Player card design | `src/components/PlayerRoster.vue` + `src/assets/main.css` |
| Modal behavior | `src/components/PlayerSelectionModal.vue` |
| Auto-save timing | `src/store/gameStore.js` → `scheduleAutoSave()` |
| Export format | `src/store/gameStore.js` → `exportJSON()` or `exportCSV()` |
| PWA settings | `vite.config.js` + `public/manifest.json` |
| App name | `package.json` + `index.html` + `public/manifest.json` |

---

**Last Updated**: 2025-12-12
