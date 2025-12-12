# Basketball Stats Tracker - Technical and Functional Document

## 1. Executive Summary

This document defines the technical and functional requirements for a Basketball Stats Tracker application designed to record and manage real-time statistics for a home team during basketball games. The application provides an intuitive interface for tracking player performance metrics across quarters and overtime periods.

---

## 2. Functional Overview

### 2.1 Purpose
The Basketball Stats Tracker enables coaches, statisticians, or team staff to record comprehensive game statistics for the home team in real-time, with data organized by quarter and player.

### 2.2 Scope
- Single team tracking (Home team only)
- Multi-quarter support (Q1, Q2, Q3, Q4, OT)
- Per-player statistics recording
- Real-time score calculation
- Comprehensive basketball metrics tracking

---

## 3. User Interface Requirements

### 3.1 Main Display Components

#### 3.1.1 Score Display
- **Home Team Score**: Large, prominent display showing current total points
- **Opposition Score**: Display for opponent's current score (manually updated)
- Visual distinction between home and visitor scores
- Real-time score updates based on recorded statistics

#### 3.1.2 Quarter Selection
- **Quarter Buttons**: Q1, Q2, Q3, Q4, OT
- Active quarter indicator (highlighted/selected state)
- Ability to switch between quarters to view/edit statistics
- Quarter-specific data storage and retrieval

#### 3.1.3 Player Roster Panel
- List of active players with the following information:
  - **Jersey Number**: Player identifier (e.g., #4, #8, #13)
  - **Player Name**: Anonymous designation (e.g., "Player A", "Player B")
  - **Current Fouls**: Running total of personal fouls
  - **Current Points**: Running total of points scored
- Visual indication of selected player for stat recording

### 3.2 Statistics Control Panel

A grid-based layout with buttons for recording various basketball statistics:

#### 3.2.1 Scoring Statistics
| Category | Actions |
|----------|---------|
| 2-Point Field Goals | 2 PT Made, 2 PT Miss |
| 3-Point Field Goals | 3 PT Made, 3 PT Miss |
| Free Throws | FT Made, FT Miss |

#### 3.2.2 Rebounding Statistics
| Category | Actions |
|----------|---------|
| Rebounds | Off Reb, Def Reb |

#### 3.2.3 Playmaking Statistics
| Category | Actions |
|----------|---------|
| Positive Actions | Assist, Steal, Block |
| Negative Actions | Fouls, Turnover |

---

## 4. Functional Requirements

### 4.1 Core Functionality

#### FR-001: Player Selection
- User shall be able to select a player from the roster
- Selected player shall be visually highlighted
- Only one player can be selected at a time

#### FR-002: Stat Recording
- User shall be able to record statistics by clicking corresponding buttons
- When click, a new window/popin displays to propose players buttons
- Each stat action shall be associated with the currently selected player
- Each stat action shall be associated with the current quarter
- Stats shall be recorded with timestamp

#### FR-003: Score Calculation
- Application shall automatically calculate player points based on:
  - 2 PT Made = +2 points
  - 3 PT Made = +3 points
  - FT Made = +1 point
- Application shall calculate team total score as sum of all player points
- Score shall update in real-time

#### FR-004: Foul Tracking
- Application shall track personal fouls per player
- Foul count shall increment with each "Foul" button press
- Visual warning when player reaches foul limits (e.g., 5 fouls)

#### FR-005: Quarter Management
- User shall be able to select current quarter (Q1, Q2, Q3, Q4, OT)
- Stats shall be associated with the active quarter
- User shall be able to view statistics for any quarter
- Multiple overtime periods shall be supported (OT1, OT2, etc.)

#### FR-006: Opposition Score Entry
- User shall be able to manually update opposition team score
- Opposition score shall be editable via input field or increment/decrement buttons

### 4.2 Data Persistence

#### FR-007: Session Storage
- All recorded statistics shall be stored during the session
- Data shall persist when switching between quarters
- Data will be store in one single game json file
- Application shall maintain full game history

#### FR-008: Data Export
- Application shall provide ability to export game statistics
- Export formats: CSV, JSON, or PDF report
- Export shall include all quarters and player statistics

#### FR-009: Undo Functionality
- Application shall support undo for the last recorded action
- Undo shall revert both player statistics and team score

---

## 5. Technical Requirements

### 5.1 Technology Stack

#### 5.1.1 Frontend
- **Framework**: Vue.js PWA
- **Styling**: CSS3, Bootstrap, or Tailwind CSS
- **Responsive Design**: Mobile and tablet support

#### 5.1.2 Backend (Optional)
- **Server**: Netlify functions
- **Storage**: Netlify BLOB

#### 5.1.3 Storage
- **Client-Side**: LocalStorage
- **Server-Side**: Netlify json BLOB

### 5.2 Data Model

#### 5.2.1 Game Object
```json
{
  "gameId": "string (UUID)",
  "homeTeam": "string",
  "oppositionTeam": "string",
  "date": "ISO 8601 datetime",
  "oppositionScore": "integer",
  "quarters": ["array of Quarter objects"],
  "players": ["array of Player objects"]
}
```

#### 5.2.2 Player Object
```json
{
  "playerId": "string (UUID)",
  "jerseyNumber": "integer",
  "name": "string",
  "totalPoints": "integer",
  "totalFouls": "integer",
  "statistics": ["array of PlayerStat objects"]
}
```

#### 5.2.3 Quarter Object
```json
{
  "quarterId": "string (UUID)",
  "quarterName": "string (Q1, Q2, Q3, Q4, OT, OT2, etc.)",
  "statistics": ["array of StatEvent objects"]
}
```

#### 5.2.4 StatEvent Object
```json
{
  "eventId": "string (UUID)",
  "playerId": "string (UUID reference)",
  "quarterId": "string (UUID reference)",
  "timestamp": "ISO 8601 datetime",
  "statType": "string (enum)",
  "value": "integer or boolean"
}
```

#### 5.2.5 Stat Types Enumeration
```
SCORING:
- TWO_PT_MADE
- TWO_PT_MISS
- THREE_PT_MADE
- THREE_PT_MISS
- FT_MADE
- FT_MISS

REBOUNDS:
- OFF_REB
- DEF_REB

PLAYMAKING:
- ASSIST
- STEAL
- BLOCK
- FOUL
- TURNOVER
```

### 5.3 Performance Requirements

#### TR-001: Response Time
- Stat recording action shall register within 100ms
- Score updates shall reflect within 200ms
- Quarter switching shall complete within 300ms

#### TR-002: Reliability
- Application shall handle concurrent stat entries
- Data shall be auto-saved every 30 seconds
- Application shall recover from unexpected closure

#### TR-003: Scalability
- Support for up to 12 players per roster
- Support for unlimited stat entries per game
- Support for multiple overtime periods

---

## 6. User Interaction Flows

### 6.1 Recording an action
1. User clicks a button
2. User selects player from roster - (if FT or FG, from there assist can also be tracked with a second display of players)
3. System records stat event with timestamp and quarter
4. System increments player stats
5. System updates team stats / score display
6. System provides visual feedback (animation, sound, or highlight)

### 6.3 Switching Quarters
1. User clicks quarter button (e.g., Q2)
2. System saves current quarter data
3. System loads selected quarter data
4. System updates display to show selected quarter statistics
5. System maintains running totals for points and fouls

### 6.4 Undo Last Action
1. User clicks "Undo" button
2. System retrieves last recorded action
3. System reverses the stat modification
4. System updates player and team statistics
5. System removes event from history

---

## 7. User Interface Layout

### 7.1 Screen Layout Structure

```
┌─────────────────────────────────────────────────────────────┐
│                    Header Section                           │
│  ┌──────────────┐              ┌──────────────┐            │
│  │ Home Team:   │              │ Opposition:  │            │
│  │    Score     │              │    Score     │            │
│  └──────────────┘              └──────────────┘            │
└─────────────────────────────────────────────────────────────┘
┌─────────────────────────────────────────────────────────────┐
│                 Quarter Selection Bar                       │
│     [ Q1 ]  [ Q2 ]  [ Q3 ]  [ Q4 ]  [ OT ]                 │
└─────────────────────────────────────────────────────────────┘
┌──────────────────────┬──────────────────────────────────────┐
│   Player Roster      │      Statistics Control Panel        │
│                      │                                      │
│  #4  Player A        │  ┌─────────┬─────────┬─────────┐   │
│  Fouls: 1  Pts: 5    │  │ 2PT Made│ 2PT Miss│ 3PT Made│   │
│                      │  └─────────┴─────────┴─────────┘   │
│  #8  Player B        │  ┌─────────┬─────────┬─────────┐   │
│  Fouls: 2  Pts: 12   │  │ 3PT Miss│ FT Made │ FT Miss │   │
│                      │  └─────────┴─────────┴─────────┘   │
│  #13 Player C        │  ┌─────────┬─────────┬─────────┐   │
│  Fouls: 0  Pts: 8    │  │ Off Reb │ Def Reb │ Assist  │   │
│                      │  └─────────┴─────────┴─────────┘   │
│  #21 Player D        │  ┌─────────┬─────────┬─────────┐   │
│  Fouls: 3  Pts: 0    │  │  Steal  │  Block  │ Deflect │   │
│                      │  └─────────┴─────────┴─────────┘   │
│  [...]               │  ┌─────────┬──────────┬────────┐   │
│                      │  │Turnover │  Foul    │ │   │
│                      │  └─────────┴──────────┴────────┘   │
└──────────────────────┴──────────────────────────────────────┘
┌─────────────────────────────────────────────────────────────┐
│              Action Bar                                     │
│  [ Undo Last ]  [ Save Game ]  [ Export Stats ]            │
└─────────────────────────────────────────────────────────────┘
```

### 7.2 Color Scheme Recommendations
- **Home Team**: Warm colors (orange, red, yellow accents)
- **Opposition**: Cool colors (blue, gray)
- **Active Quarter**: Green highlight
- **Made Shots**: Green buttons
- **Missed Shots**: Red buttons
- **Neutral Actions**: Blue buttons
- **Warning States**: Yellow/orange (high fouls)
- **Critical States**: Red (fouled out)

---

## 8. Non-Functional Requirements

### 8.1 Usability
- Interface shall be operable with touch or mouse
- Interface shall be operable on mobile, pad ans desktop
- Buttons shall be large enough for quick tapping (minimum 44x44px)
- Critical actions shall require confirmation
- Application shall be usable in landscape and portrait modes

### 8.2 Accessibility
- Application shall support keyboard navigation
- Color contrasts shall meet WCAG 2.1 AA standards
- Screen reader compatibility for player selection and stat recording
- Font sizes shall be adjustable

### 8.3 Browser Compatibility
- Support for Chrome, Firefox, Safari, Edge (latest 2 versions)
- Progressive Web App (PWA) capability for offline use
- Mobile browser optimization

---

## 10. Glossary

| Term | Definition |
|------|------------|
| **2PT** | Two-point field goal attempt |
| **3PT** | Three-point field goal attempt |
| **FT** | Free throw attempt |
| **Off Reb** | Offensive rebound |
| **Def Reb** | Defensive rebound |
| **Assist** | Pass leading directly to a made basket |
| **Steal** | Taking possession from opponent |
| **Block** | Deflecting opponent's shot attempt |
| **Deflect** | Touching the ball to disrupt opponent's play |
| **Turnover** | Loss of possession |
| **Forced Turnover** | Causing opponent to lose possession |
| **Jump Ball** | Possession contested between two players |
| **Forced Rush Shot** | Forcing opponent to take difficult shot |
| **OT** | Overtime period (5 minutes) |

---

## 12. Document Control

| Version | Date | Author | Changes |
|---------|------|--------|---------|
| 1.0 | 2025-12-12 | System | Initial document creation |

---

**Document Status**: Draft for Review
**Last Updated**: 2025-12-12
