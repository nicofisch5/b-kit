# Architecture Documentation

## System Architecture

### Application Type
**Single Page Application (SPA)** with Progressive Web App capabilities

### Architecture Pattern
**Component-based architecture** with centralized reactive state management

```
┌─────────────────────────────────────────┐
│         Browser Environment             │
│  ┌───────────────────────────────────┐  │
│  │         Vue.js 3 App              │  │
│  │  ┌─────────────────────────────┐  │  │
│  │  │     App.vue (Root)          │  │  │
│  │  │  ┌─────────┐  ┌───────────┐ │  │  │
│  │  │  │Components│  │Game Store │ │  │  │
│  │  │  └────┬────┘  └─────┬─────┘ │  │  │
│  │  │       │             │        │  │  │
│  │  │       └─────────────┘        │  │  │
│  │  └─────────────────────────────┘  │  │
│  └───────────────────────────────────┘  │
│  ┌───────────────────────────────────┐  │
│  │      Browser APIs                 │  │
│  │  • localStorage (Data Persist)    │  │
│  │  • Service Worker (Offline)       │  │
│  │  • Cache API (PWA)                │  │
│  └───────────────────────────────────┘  │
└─────────────────────────────────────────┘
```

## State Management Architecture

### Reactive State Pattern

**File**: `src/store/gameStore.js`

```javascript
// Single source of truth
export const gameState = reactive({
  gameId, players[], quarters[],
  currentQuarter, oppositionScore, history[]
})

// Computed/Derived values
export function getTotalHomeScore()
export function getCurrentQuarter()

// Actions (mutations)
export function recordStat()
export function switchQuarter()
export function undoLastAction()
export function updateOppositionScore()
export function saveGame()
export function exportJSON()
export function exportCSV()
```

### State Flow Diagram

```
User Action
    ↓
Component Event Handler
    ↓
Store Action Function
    ↓
Mutate Reactive State
    ↓
Vue Reactivity Triggers
    ↓
Component Re-renders
    ↓
UI Updates
```

### Data Persistence Flow

```
State Change
    ↓
Watch Trigger (deep watch)
    ↓
Schedule Auto-save (30s debounce)
    ↓
JSON.stringify(gameState)
    ↓
localStorage.setItem()
    ↓
Saved to Browser Storage
```

## Component Architecture

### Component Responsibility Matrix

| Component | Responsibility | State Access | State Mutation |
|-----------|----------------|--------------|----------------|
| **App.vue** | Root orchestrator, modal control, notifications | Read | No |
| **ScoreDisplay.vue** | Display scores, manual opposition update | Read + Write opposition | Yes (opposition only) |
| **QuarterSelector.vue** | Quarter navigation, OT management | Read current quarter | Yes (via action) |
| **PlayerRoster.vue** | Display players, selection visual | Read players | No |
| **StatsControlPanel.vue** | Display stat buttons | None | No |
| **PlayerSelectionModal.vue** | Player selection, assist tracking | Read players | Yes (via action) |
| **ActionBar.vue** | Undo, save, export, reset | None | Yes (via actions) |

### Component Communication

```
App.vue (Parent)
    │
    ├── ScoreDisplay.vue
    │   • Uses: gameState directly
    │   • Emits: nothing
    │
    ├── QuarterSelector.vue
    │   • Uses: gameState directly
    │   • Emits: nothing
    │
    ├── PlayerRoster.vue
    │   • Props: selectedPlayerId
    │   • Emits: player-selected
    │
    ├── StatsControlPanel.vue
    │   • Props: none
    │   • Emits: stat-clicked
    │
    ├── PlayerSelectionModal.vue
    │   • Props: statType, statLabel
    │   • Emits: player-selected, cancel
    │   • Calls: recordStat() directly
    │
    └── ActionBar.vue
        • Props: none
        • Emits: undo, save, export
```

### Event Flow Example: Recording a 2PT Made

```
1. User clicks "2PT Made" button
   ↓
2. StatsControlPanel emits 'stat-clicked'
   with { statType: 'TWO_PT_MADE', label: '2PT Made' }
   ↓
3. App.vue catches event → opens PlayerSelectionModal
   Sets: showPlayerModal = true, pendingStatType
   ↓
4. Modal displays player grid
   ↓
5. User clicks "Player A"
   ↓
6. Modal stores selectedScoringPlayerId → shows assist prompt
   ↓
7. User clicks "Player B" for assist (or "No Assist")
   ↓
8. Modal calls: recordStat(playerAId, 'TWO_PT_MADE', playerBId)
   ↓
9. recordStat() in gameStore:
   • Creates StatEvent with UUID
   • Updates playerA.totalPoints += 2
   • Adds to playerA.statistics[]
   • Adds to currentQuarter.statistics[]
   • Records assist for playerB (recursive call)
   • Adds to gameState.history[]
   • Calls saveGame()
   ↓
10. Vue reactivity triggers updates:
    • PlayerRoster shows new points
    • ScoreDisplay shows new total
    ↓
11. Modal emits 'player-selected' → App closes modal
    ↓
12. App shows success notification
```

## Data Architecture

### localStorage Schema

**Key**: `basketballGame`

**Value**: JSON string of complete game state

```json
{
  "gameId": "uuid",
  "homeTeam": "Home Team",
  "oppositionTeam": "Opposition",
  "date": "2025-12-12T10:30:00.000Z",
  "oppositionScore": 25,
  "currentQuarter": "Q2",
  "overtimeCount": 0,
  "quarters": [
    {
      "quarterId": "uuid",
      "quarterName": "Q1",
      "statistics": [
        {
          "eventId": "uuid",
          "playerId": "uuid",
          "quarterId": "uuid",
          "timestamp": "2025-12-12T10:35:00.000Z",
          "statType": "TWO_PT_MADE",
          "value": 1
        }
      ]
    }
  ],
  "players": [
    {
      "playerId": "uuid",
      "jerseyNumber": 4,
      "name": "Player A",
      "totalPoints": 12,
      "totalFouls": 2,
      "statistics": [/* all player stats */]
    }
  ],
  "history": [
    {
      "event": {/* stat event */},
      "playerId": "uuid",
      "assistEvent": {/* optional */},
      "assistPlayerId": "uuid"
    }
  ]
}
```

### Data Relationships

```
Game (1)
  ├── Quarters (many)
  │   └── Statistics (many) → references Player
  └── Players (many)
      └── Statistics (many) → references Quarter

History (1) → array of undoable actions
  └── References both Player and Quarter
```

### UUID Generation Strategy

All entities use UUID v4 for unique identification:
- Game ID
- Player IDs
- Quarter IDs
- Stat Event IDs

Generated via simple UUID function (no external dependency).

## PWA Architecture

### Service Worker Strategy

**Plugin**: `vite-plugin-pwa`

**Strategy**: Auto-update with workbox

```
App Load
  ↓
Check for Service Worker
  ↓
Register Service Worker (if not exists)
  ↓
Cache static assets (js, css, html, images)
  ↓
Listen for updates
  ↓
Auto-update on new version
  ↓
Offline: Serve from cache
Online: Fetch and update cache
```

### Caching Strategy

1. **Static Assets**: CacheFirst
   - HTML, JS, CSS, images
   - Served from cache immediately
   - Updated in background

2. **External Resources**: CacheFirst
   - Google Fonts
   - 1 year expiration
   - Max 10 entries

3. **Data**: localStorage (not Service Worker)
   - Game state
   - Not cached by SW

### Offline Capabilities

✅ **Works Offline**:
- View all UI components
- Record statistics
- Switch quarters
- Undo actions
- View current game data
- Export to JSON/CSV

❌ **Requires Online** (if added):
- Backend sync
- Cloud storage
- External resources (first load)

## Performance Architecture

### Optimization Strategies

1. **Virtual DOM**: Vue 3's optimized VDOM
2. **Reactive Dependencies**: Fine-grained reactivity
3. **Component Lazy Loading**: Not needed (small app)
4. **CSS**: No framework overhead
5. **Bundle Size**: Minimal dependencies

### Performance Targets (from requirements)

- Stat recording: < 100ms ✅
- Score updates: < 200ms ✅
- Quarter switching: < 300ms ✅
- Auto-save: Every 30 seconds ✅

### Bundle Analysis

```
Production Build Size (approximate):
├── Vue.js runtime: ~50KB (gzipped)
├── App code: ~30KB (gzipped)
├── CSS: ~15KB (gzipped)
├── PWA assets: ~5KB
└── Total: ~100KB (gzipped)
```

## Security Architecture

### Data Security

**Client-Side Only Application**
- ✅ No backend = No server vulnerabilities
- ✅ No authentication = No credential theft
- ✅ No network calls = No XSS/CSRF attacks
- ⚠️ localStorage = Accessible to any script on domain
- ⚠️ No encryption = Data readable in browser

### Threat Model

**Low Risk Application** (local sports tracking)

Potential Issues:
1. **localStorage theft**: Mitigated by browser same-origin policy
2. **XSS attacks**: No user-generated content rendered as HTML
3. **Data loss**: Mitigated by auto-save and export functions

No sensitive data stored:
- No personal information
- No financial data
- No authentication credentials
- Only basketball statistics

## Build Architecture

### Build Pipeline

```
Source Files (src/)
    ↓
Vite Dev Server (Hot Module Replacement)
    ↓
Vue SFC Compiler (.vue → JS)
    ↓
JavaScript Bundler (Rollup via Vite)
    ↓
CSS Processing (PostCSS)
    ↓
PWA Plugin (SW generation)
    ↓
Asset Optimization
    ↓
dist/ (Production Build)
```

### Build Outputs

```
dist/
├── assets/
│   ├── index.[hash].js      # Main bundle
│   ├── index.[hash].css     # Styles
│   └── [images/fonts]       # Static assets
├── sw.js                     # Service worker
├── workbox-*.js              # Workbox runtime
├── manifest.json             # PWA manifest
├── index.html                # Entry HTML
└── [icon files]              # PWA icons
```

## Scalability Considerations

### Current Limits
- **Players**: 12 (configurable)
- **Stats per game**: Unlimited (localStorage limit ~5-10MB)
- **Quarters**: Unlimited overtimes
- **Games stored**: 1 (current design)

### Scale-Up Path (if needed)
1. Add IndexedDB for multiple games
2. Add backend API for cloud storage
3. Add user authentication
4. Add team management
5. Add season tracking
6. Add analytics dashboard

## Error Handling Architecture

### Error Boundaries

1. **localStorage failures**: Caught in saveGame(), returns false
2. **JSON parse errors**: Caught in loadGame(), creates new game
3. **Invalid state**: Defensive checks in recordStat()
4. **PWA errors**: Handled by service worker

### Recovery Strategy

```
Error Detected
    ↓
Log to Console
    ↓
Try to Save Current State
    ↓
Show User Notification (if applicable)
    ↓
Continue Operation (fail gracefully)
```

No crash recovery needed - localStorage persists across page refreshes.

## Testing Strategy (Recommended)

### Component Testing
- Use Vitest + Vue Test Utils
- Test user interactions
- Test state mutations

### E2E Testing
- Use Playwright or Cypress
- Test complete user flows
- Test PWA installation

### Manual Testing Checklist
- ✅ Record each stat type
- ✅ Switch quarters
- ✅ Undo actions
- ✅ Export data
- ✅ Refresh page (persistence)
- ✅ Test on mobile device
- ✅ Test offline mode

## Deployment Architecture

### Recommended Hosting

**Static Hosting (No Server Required)**

Options:
1. Netlify (recommended)
   - Auto-deploy from Git
   - CDN included
   - HTTPS automatic
   - Custom domain support

2. Vercel
   - Similar to Netlify
   - Excellent performance

3. GitHub Pages
   - Free for public repos
   - Custom domain support

4. Cloudflare Pages
   - Global CDN
   - Fast edge network

### Deployment Checklist

- [ ] Run `npm run build`
- [ ] Test `dist/` locally with `npm run preview`
- [ ] Add PWA icons (192x192, 512x512)
- [ ] Configure custom domain (optional)
- [ ] Test PWA installation on mobile
- [ ] Test offline functionality
- [ ] Verify localStorage persistence

## Monitoring & Analytics (Not Implemented)

### Potential Additions
- Google Analytics for usage tracking
- Sentry for error tracking
- Performance monitoring
- User feedback collection

Not included in current build (privacy-focused, minimal tracking).

---

**Last Updated**: 2025-12-12
**Architecture Version**: 1.0
