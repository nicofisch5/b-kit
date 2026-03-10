# Basketball Stats Tracker PWA

A Progressive Web App for tracking basketball game statistics in real-time. Built with Vue.js 3 and designed for mobile, tablet, and desktop devices.

## Features

### Core Functionality
- **Real-time Score Tracking**: Automatic score calculation for home team based on recorded stats
- **Quarter Management**: Full support for Q1, Q2, Q3, Q4, and unlimited overtime periods
- **Player Statistics**: Track comprehensive stats for up to 12 players (minimum 5 players)
- **Offline Support**: PWA capabilities allow the app to work without internet connection
- **Auto-Save**: Game data automatically saved to localStorage every 30 seconds
- **Undo Functionality**: Revert the last recorded action
- **Data Export**: Export game statistics to JSON or CSV formats with gameId in filename
- **Data Import**: Import previously exported JSON game files to restore game state
- **Box Score**: Comprehensive statistics table with professional box score format
- **Player Management**: Edit player names/jersey numbers, delete players (minimum 5 required)
- **Theme Toggle**: Switch between Modern (colorful) and Terminal (monospace, grey borders) themes

### Statistics Tracked
- **Scoring**: 2PT Made/Miss, 3PT Made/Miss, FT Made/Miss
- **Rebounds**: Offensive and Defensive
- **Playmaking**: Assists, Steals, Blocks
- **Other**: Fouls, Turnovers

### Special Features
- **Assist Tracking**: When recording a made field goal or free throw, optionally record an assist
- **Foul Warnings**: Visual indicators when players approach foul limits (5 fouls)
- **Responsive Design**: Optimized for mobile phones, tablets, and desktop computers
- **Dark Theme**: Eye-friendly dark color scheme
- **Touch-Optimized**: Large buttons (minimum 44x44px) for easy tapping

## Prerequisites

- Node.js (v16 or higher)
- npm (v8 or higher)

## Installation

1. **Navigate to the project directory**:
   ```bash
   cd /home/nico5/Desktop/Stats-Tracker
   ```

2. **Install dependencies**:
   ```bash
   npm install
   ```

## Development

To run the application in development mode:

```bash
npm run dev
```

The app will be available at `http://localhost:5173`

## Build for Production

To build the application for production:

```bash
npm run build
```

The built files will be in the `dist` directory.

To preview the production build:

```bash
npm run preview
```

## Project Structure

```
Stats-Tracker/
├── public/                      # Static assets
├── src/
│   ├── assets/
│   │   └── main.css            # Global styles
│   ├── components/
│   │   ├── ScoreDisplay.vue    # Score display component
│   │   ├── QuarterSelector.vue # Quarter selection component
│   │   ├── PlayerRoster.vue    # Player roster component
│   │   ├── StatsControlPanel.vue # Stats buttons panel
│   │   ├── PlayerSelectionModal.vue # Player selection modal
│   │   └── ActionBar.vue       # Action buttons (undo, save, export)
│   ├── store/
│   │   └── gameStore.js        # Game state management
│   ├── App.vue                 # Main application component
│   └── main.js                 # Application entry point
├── index.html                  # HTML entry point
├── package.json                # Project dependencies
├── vite.config.js              # Vite configuration
└── README.md                   # This file
```

## Usage Guide

### Starting a Game

1. Open the application
2. The app initializes with 12 default players (Player A through Player L)
3. The current quarter is set to Q1
4. Opposition score is set to 0

### Recording Statistics

1. **Click a stat button** (e.g., "2PT Made", "Assist", "Foul")
2. **Select the player** from the modal popup
3. **For made shots** (2PT, 3PT, FT):
   - After selecting the scoring player, you'll be prompted to optionally record an assist
   - Click a second player to record an assist, or click "No Assist" to skip
4. The stat is recorded instantly and the modal closes

### Managing Quarters

- Click any quarter button (Q1, Q2, Q3, Q4) to switch quarters
- Click "+ OT" to add an overtime period
- Multiple overtime periods are supported (OT, OT2, OT3, etc.)
- Stats are tracked per quarter but totals remain cumulative

### Updating Opposition Score

- Use the **+** and **-** buttons to adjust the opposition score
- Or directly type a number in the input field

### Action Bar Functions

- **Undo Last**: Reverts the most recent stat recording
- **Save Game**: Manually saves the current game to localStorage
- **Export Stats**:
  - Click to open export menu
  - Choose "Export as JSON" for full game data
  - Choose "Export as CSV" for spreadsheet-compatible format
- **New Game**: Resets all data and starts a fresh game (requires confirmation)

### Player Information

Each player card shows:
- Jersey number
- Player name
- Current fouls (highlighted in orange when ≥4)
- Current points

Players with 5+ fouls are marked as "fouled out" and cannot be selected for made shots.

## Data Persistence

- **Automatic**: Game data is auto-saved to browser localStorage every 30 seconds
- **On Action**: Data is also saved after each stat recording
- **Recovery**: If the app is closed unexpectedly, data can be recovered from localStorage
- **Storage Location**: Browser's localStorage (persists until manually cleared)

## PWA Features

### Installation

The app can be installed as a Progressive Web App:

1. Open the app in a supported browser (Chrome, Edge, Safari)
2. Look for the "Install" or "Add to Home Screen" prompt
3. Click to install the app on your device
4. The app will appear as a standalone application

### Offline Capability

- The app works offline after the first visit
- All game data is stored locally
- Service worker caches application files

## Browser Compatibility

- **Desktop**: Chrome, Firefox, Safari, Edge (latest 2 versions)
- **Mobile**: Chrome (Android), Safari (iOS), Edge (Android)
- **Tablet**: Same as mobile browsers

## Responsive Breakpoints

- **Desktop**: > 1024px
- **Tablet**: 768px - 1024px
- **Mobile**: < 768px
- **Small Mobile**: < 480px
- **Landscape Mobile**: < 896px (landscape orientation)

## Keyboard Navigation

The application supports keyboard navigation:
- **Tab**: Navigate between interactive elements
- **Enter/Space**: Activate buttons
- **Escape**: Close modals (when implemented)

## Data Export Formats

### JSON Export
Complete game data including:
- Game ID, date, team names
- All player data with statistics
- Quarter-by-quarter breakdown
- Full event history

### CSV Export
Spreadsheet-compatible format with:
- Player summary (name, number, total points, total fouls)
- Per-quarter statistics for each stat type
- Easy to import into Excel, Google Sheets, etc.

## Troubleshooting

### App not loading
- Clear browser cache and reload
- Check console for errors (F12 → Console tab)
- Ensure JavaScript is enabled

### Data not saving
- Check if localStorage is enabled in browser
- Verify sufficient storage space available
- Try manual save using "Save Game" button

### PWA not installing
- Ensure you're using HTTPS or localhost
- Check browser compatibility
- Look for install prompt in browser address bar

### Stats not recording
- Ensure a quarter is selected
- Check if player is fouled out (for made shots)
- Try refreshing the page

## Performance Considerations

- **Response Time**: Stat recording registers within 100ms
- **Score Updates**: Reflect within 200ms
- **Quarter Switching**: Completes within 300ms
- **Auto-save**: Executes every 30 seconds
- **Max Players**: Supports up to 12 players per roster
- **Unlimited Stats**: No limit on stat entries per game

## Customization

### Modifying Players

To change player names or jersey numbers, edit the `createDefaultPlayers()` function in `src/store/gameStore.js`:

```javascript
function createDefaultPlayers() {
  const playerNames = ['Player A', 'Player B', /* ... */]
  return playerNames.map((name, index) => ({
    // Modify jersey numbers here
    jerseyNumber: (index + 1) * 4,
    // ...
  }))
}
```

### Changing Colors

To modify the color scheme, edit CSS variables in `src/assets/main.css`:

```css
:root {
  --primary-color: #ff6b35;  /* Home team color */
  --secondary-color: #004e89; /* Opposition color */
  /* ... other colors ... */
}
```

### Adding New Stats

To add new statistics:
1. Add to `StatType` enum in `src/store/gameStore.js`
2. Update the recording logic in `recordStat()` function
3. Add buttons to `StatsControlPanel.vue`

## Backend (Symfony API + Docker)

The backend is a Symfony 7.1 REST API running in Docker. All commands below are run from the **repo root**.

### Services

| Service  | URL / Port              | Description            |
|----------|-------------------------|------------------------|
| API      | http://localhost:8080   | Nginx + PHP-FPM        |
| MariaDB  | localhost:3307          | Database               |
| Adminer  | http://localhost:8081   | DB web UI              |

Adminer login: server=`database`, user=`bkit`, password=`bkit_secret`.

### Start / Stop

```bash
# Start all services (detached)
docker-compose up -d

# Stop all services
docker-compose down

# Rebuild PHP image (after Dockerfile changes)
docker-compose up -d --build php

# View logs
docker-compose logs -f
docker-compose logs -f php
docker-compose logs -f nginx
docker-compose logs -f database
```

### Database Migrations

```bash
# Run pending migrations
docker-compose exec php php bin/console doctrine:migrations:migrate

# Check migration status
docker-compose exec php php bin/console doctrine:migrations:status

# Generate a new migration from entity changes
docker-compose exec php php bin/console doctrine:migrations:diff
```

### Useful Symfony Commands

```bash
# Open a shell in the PHP container
docker-compose exec php bash

# Clear Symfony cache
docker-compose exec php php bin/console cache:clear

# List all registered routes
docker-compose exec php php bin/console debug:router

# Seed SuperAdmin user (first-time setup)
docker-compose exec php php bin/console app:create-super-admin
```

### JWT Keys (one-time setup)

```bash
docker-compose exec php php bin/console lexik:jwt:generate-keypair
# Passphrase: bkit_jwt_passphrase
```

### Running Backend Tests (PHPUnit 12)

Tests run against an isolated `bkit_test` database.

**First-time test database setup:**

```bash
# Create the test database
docker-compose exec php bash -c \
  'mysql -h database -u bkit -pbkit_secret -e "CREATE DATABASE IF NOT EXISTS bkit_test CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;"'

# Clear test cache
docker-compose exec php bash -c 'APP_ENV=test php bin/console cache:clear'

# Run migrations on the test database
docker-compose exec php bash -c \
  'DATABASE_URL="mysql://bkit:bkit_secret@database:3306/bkit_test?serverVersion=10.11.0-MariaDB" \
   APP_ENV=test php bin/console doctrine:migrations:migrate --no-interaction'
```

**Running tests:**

```bash
# Run all tests
docker-compose exec php php vendor/bin/phpunit --testdox

# Run only unit tests
docker-compose exec php php vendor/bin/phpunit --testsuite unit --testdox

# Run only functional tests
docker-compose exec php php vendor/bin/phpunit --testsuite functional --testdox

# Run a specific test class
docker-compose exec php php vendor/bin/phpunit tests/Functional/PlayerControllerTest.php --testdox
```

### Running Frontend Tests (Vitest)

```bash
# Run all frontend unit tests once
npm test

# Run in watch mode (re-runs on file changes)
npm run test:watch
```

---

## Security

### Authentication

- **JWT (RS256)** — all API routes under `/api/v1` (except `/auth/login`) require a valid Bearer token.
  - Signed with an RSA private/public key pair. The frontend only holds the public key and cannot forge tokens.
  - Token TTL is configurable via the `JWT_TTL` environment variable.
  - The JWT payload carries `userId`, `role`, `organizationId`, and `organizationSlug` — no extra DB lookup needed per request.
- **Login throttling** — max 5 failed attempts per IP per 15 minutes (Symfony built-in `login_throttling`).
- **Password hashing** — `argon2id` via Symfony's `algorithm: auto` (PHP 8 default).
- **Stateless firewalls** — no session cookies, no CSRF surface.
- **Role hierarchy** — `ROLE_SUPER_ADMIN` > `ROLE_ADMIN` > `ROLE_COACH`. The `/admin/*` subtree is locked at the firewall level to `ROLE_SUPER_ADMIN` before controllers run.

### Authorization (inside controllers)

All controller actions use a shared `SecurityService` that enforces fine-grained access:

| Check | Behaviour |
|---|---|
| `getOrgFilter()` | List queries always scoped to the caller's `organization_id`. SuperAdmin sees all. |
| `assertSameOrg()` | Show / update / delete verify the resource belongs to the caller's org (403 otherwise). |
| `assertCanAccessTeam()` | Admins: same org. Coaches: must be explicitly assigned via `coach_team`. |
| `assertCanAccessChampionship()` | Admins: same org. Coaches: must be explicitly assigned via `coach_championship`. |
| `assertCanAccessGame()` | Admins: same org. Coaches: game's linked team or championship must be assigned. |

Coach team and championship IDs are cached per request to avoid redundant DB calls.

### Vue → Symfony Communication

- There is **no API key** identifying the Vue app as a client. API keys embedded in browser code are visible in DevTools and provide no real security — JWT per user is the correct model here.
- Every request from `apiClient.js` attaches `Authorization: Bearer <token>` from `authStore`.
- On any `401` response the token is cleared from memory and `localStorage`, and the router redirects to `/login`.
- In development, Vite proxies `/api/v1` → `http://localhost:8080`, so the browser makes same-origin requests and CORS is not involved.

### CORS (production)

- Configured via `nelmio/cors-bundle`. Only the origin in `CORS_ALLOW_ORIGIN` is allowed (regex-based — scope it to your Netlify domain in production).
- Allowed methods: `GET POST PUT DELETE OPTIONS`. Allowed headers: `Content-Type`, `Authorization`.
- CORS is enforced by the browser, not the server. Non-browser clients (curl, Postman) bypass it — JWT is the actual enforcement layer.

### Nginx Security Headers (API server)

| Header | Value |
|---|---|
| `X-Content-Type-Options` | `nosniff` |
| `X-Frame-Options` | `DENY` |
| `X-XSS-Protection` | `1; mode=block` |
| `Referrer-Policy` | `strict-origin-when-cross-origin` |
| `Permissions-Policy` | geolocation, microphone, camera all denied |

Direct `.php` file access returns 404 — only `index.php` through FastCGI is reachable.

### Known Gaps / Production Checklist

| Item | Notes |
|---|---|
| **HTTPS** | Not enforced at the Docker layer. TLS must be handled by your production reverse proxy or CDN. |
| **JWT in `localStorage`** | Accessible to JS. Acceptable for a PWA; an `HttpOnly` cookie would be stronger against XSS. |
| **No token revocation** | A JWT is valid until it expires — there is no server-side blacklist. |
| **`CORS_ALLOW_ORIGIN`** | Must be set to your exact production domain. A wildcard (`*`) breaks the CORS protection entirely. |

---

## Technical Stack

- **Framework**: Vue.js 3 (Composition API)
- **Build Tool**: Vite
- **PWA**: vite-plugin-pwa
- **Styling**: CSS3 with CSS Variables
- **Storage**: Browser localStorage
- **State Management**: Reactive state with Vue's reactivity system

## Requirements Compliance

This application implements all requirements specified in the Basketball_Stats_Tracker_Requirements.md document:

- ✅ Vue.js PWA framework
- ✅ LocalStorage for data persistence
- ✅ Support for 12 players
- ✅ Quarter selection (Q1, Q2, Q3, Q4, OT+)
- ✅ Modal-based player selection
- ✅ Assist tracking for made shots
- ✅ All required statistics
- ✅ Foul limit warnings (5 fouls)
- ✅ Automatic score calculation
- ✅ Undo functionality
- ✅ Auto-save every 30 seconds
- ✅ Export to JSON and CSV
- ✅ Responsive design (mobile, tablet, desktop)
- ✅ Touch-optimized (44x44px minimum buttons)

## License

MIT License - See LICENSE file for details

## Support

For issues, questions, or contributions, please refer to the project documentation or contact the development team.

## Version History

- **v1.0.0** (2025-12-12): Initial release
  - Complete PWA implementation
  - All core features implemented
  - Responsive design for all devices
  - Data persistence and export functionality

---

**Last Updated**: 2025-12-12
