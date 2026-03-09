# B-Strack - Coach Companion Roadmap

Transforming B-Strack from a single-game stats tracker into a full-featured coach companion platform.

**Created:** 2026-03-09
**Status:** Planning

---

## Architecture Decision

### Backend: Symfony + MariaDB

**Decision date:** 2026-03-09

**Stack:**
- **Frontend:** Vue 3 (existing PWA, hosted on Netlify)
- **Backend API:** Symfony (PHP) with API Platform
- **Database:** MariaDB
- **Auth:** Symfony Security component (roles, voters, org-based access)
- **File storage:** S3-compatible object storage (play diagrams, drill media)
- **Hosting:** VPS (frontend stays on Netlify)

**Why this stack:**
- Relational data model fits perfectly (orgs > coaches > teams > players > games > stats, drills, plays, schedules)
- Full control over business logic, permissions, data sharing rules
- No vendor lock-in — own everything, can move anywhere
- Symfony experience already in place
- MariaDB handles the target scale (100+ organizations, 1500+ coaches) comfortably on a single modest VPS
- Extensible: queue workers, cron jobs, email, PDF generation, webhooks — no platform limits

**Rejected alternatives:**
- *Supabase:* Good for fast start, but complex business logic gets awkward in Edge Functions. Would hit limits as features grow.
- *Firebase:* NoSQL makes relational coaching data painful. Vendor lock-in. Pricing unpredictable at scale.
- *Netlify Blobs:* Already used for analytics, but no querying, no relations — not enough for app data.

**Storage strategy:**
- localStorage remains as offline cache / fallback for the game tracker
- Symfony API is the source of truth for all persistent data
- Sync: write to localStorage immediately, then sync to API in background
- Conflict resolution: last-write-wins (simple, sufficient without real-time requirements)

**Scalability notes:**
- 1500 coaches ≈ 100-200 concurrent users, ~50-100 req/s peak — a single 10EUR/mo VPS handles this
- Up to 5,000 coaches: single server + Redis for sessions/cache
- 5,000-50,000: CDN, Redis caching, separate DB server
- No real-time sync needed: coaches sync on refresh / app open (REST API, no websockets)

---

## Feature 1: Multi-Game Management

Track and manage multiple games with history and individual game data.

### Details

<!-- Add your detailed requirements here -->

---

## Feature 2: Multi-Team Management

Create and manage multiple teams, each with their own roster and settings.

### Details

<!-- Add your detailed requirements here -->

---

## Feature 3: Seasons & Championships

Organize games into seasons and championships, with team-championship relationships.

### Details

<!-- Add your detailed requirements here -->

---

## Feature 4: Cumulated Team Stats

Aggregate statistics across multiple games for team-level performance analysis.

### Details

<!-- Add your detailed requirements here -->

---

## Feature 5: Drills Library

Create, organize, and share basketball drills.

### Details

<!-- Add your detailed requirements here -->

---

## Feature 6: Training Session Planner

Plan training sessions by assembling drills, setting durations, and scheduling.

### Details

<!-- Add your detailed requirements here -->

---

## Feature 7: Play Designer

Visual play designer for drawing and sharing offensive/defensive plays.

### Details

<!-- Add your detailed requirements here -->

---

## Feature 8: Game Schedules

Manage game schedules, calendars, and upcoming matchups.

### Details

<!-- Add your detailed requirements here -->

---

## Feature 9: Organization & Coach Collaboration

Multiple coaches within the same organization can share data (teams, games, drills, plays).

### Details

- Role-based access: head coach, assistant coach, stats keeper, viewer
- Organization-level data ownership with per-coach permissions
- Invitation system for adding coaches to an organization

---

## Notes

- Real-time sync is NOT required. Coaches sync data on refresh / app open (REST API).
- Netlify remains the frontend host. Symfony API deployed separately.
- Netlify analytics (existing Netlify Functions + Blobs) stays independent from the Symfony backend.
- Authentication lives in Symfony — Netlify Identity is not used.
