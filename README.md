# Basketball Stats Tracker PWA

A Progressive Web App for tracking basketball game statistics in real-time. Built with Vue.js 3 and designed for mobile, tablet, and desktop devices.

## Features

### Core Functionality
- **Real-time Score Tracking**: Automatic score calculation for home team based on recorded stats
- **Quarter Management**: Full support for Q1, Q2, Q3, Q4, and unlimited overtime periods
- **Player Statistics**: Track comprehensive stats for up to 12 players
- **Offline Support**: PWA capabilities allow the app to work without internet connection
- **Auto-Save**: Game data automatically saved to localStorage every 30 seconds
- **Undo Functionality**: Revert the last recorded action
- **Data Export**: Export game statistics to JSON or CSV formats

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
