# Changelog

All notable changes to B-Strack will be documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.0.0/),
and this project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

## [1.1.0] - 2025-12-13

### Added
- **App Footer with User Guide Link**: Added footer to main app with link to ONBOARDING.html
  - Displays version number
  - Direct link to user guide opens in new tab
  - Responsive design for mobile
  - Terminal theme support
  - Book icon (📖) for visual recognition

- **Onboarding Materials**: Created comprehensive `ONBOARDING.html` with QR code generator
  - Interactive HTML page for creating user guides
  - QR code generation for easy app access
  - Printable PDF format for distribution
  - Complete usage instructions and troubleshooting

- **PWA Update Notifications**: Users now get notified when new app version is available
  - New `UpdateNotification.vue` component
  - Non-intrusive notification banner
  - User control over update timing
  - Automatic update check every hour

- **PWA Improvement Recommendations**: New `PWA_IMPROVEMENTS.md` document
  - Comprehensive guide for enhancing PWA features
  - Prioritized improvement list
  - Implementation details and code examples
  - Testing checklist and resources

- **Documentation Maintenance System**: Added reminders in key files
  - Prominent reminders in `App.vue`, `gameStore.js`, and `vite.config.js`
  - Updated MD files with maintenance instructions
  - Ensures documentation stays current with implementation

- **CHANGELOG.md**: This file for tracking version history

### Changed
- PWA registration type changed from `autoUpdate` to `prompt` for better user control
- Updated `package.json` version to 1.1.0
- Enhanced `SETUP_GUIDE.md` with onboarding materials section
- Enhanced `Basketball_Stats_Tracker_Requirements.md` with user documentation section
- All documentation files now include maintenance reminders

### Documentation
- Updated all MD files with cross-references
- Added version control and last updated dates
- Improved document organization and structure

## [1.0.0] - 2025-12-12

### Initial Release
- Basketball statistics tracking for home team
- Quarter-by-quarter stat recording (Q1, Q2, Q3, Q4, OT)
- Player management (up to 12 players)
- Comprehensive stat types: 2PT, 3PT, FT, rebounds, assists, steals, blocks, fouls, turnovers
- Auto-save every 30 seconds
- Export game data as JSON and CSV
- Progressive Web App with offline support
- Responsive design for mobile, tablet, and desktop
- Dark/light theme toggle
- Box score view
- Quarter logs
- Undo functionality
- Player editing

---

## Version Numbering

- **Major (X.0.0)**: Breaking changes, major new features
- **Minor (1.X.0)**: New features, enhancements, backward compatible
- **Patch (1.0.X)**: Bug fixes, documentation updates

---

**Note:** Always update this file when releasing new versions. Include date, version number, and categorized changes (Added, Changed, Deprecated, Removed, Fixed, Security).
