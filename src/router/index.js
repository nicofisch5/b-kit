import { createRouter, createWebHistory } from 'vue-router'
import { authStore } from '../store/authStore'

const routes = [
  // ── Public ─────────────────────────────────────────
  {
    path: '/login',
    name: 'login',
    component: () => import('../views/LoginView.vue'),
    meta: { public: true },
  },

  // ── SuperAdmin ──────────────────────────────────────
  {
    path: '/admin',
    name: 'admin',
    component: () => import('../views/admin/AdminOrganizationsView.vue'),
    meta: { requireRole: 'ROLE_SUPER_ADMIN' },
  },
  {
    path: '/admin/organizations/:orgId',
    name: 'admin-org-detail',
    component: () => import('../views/admin/AdminOrgDetailView.vue'),
    meta: { requireRole: 'ROLE_SUPER_ADMIN' },
  },

  // ── Org Admin ───────────────────────────────────────
  {
    path: '/organization-:orgSlug/users',
    name: 'org-users',
    component: () => import('../views/OrgUsersView.vue'),
    meta: { requireRole: 'ROLE_ADMIN' },
  },

  // ── App (coach + admin) ─────────────────────────────
  {
    path: '/organization-:orgSlug',
    name: 'home',
    component: () => import('../views/HomeView.vue'),
  },
  {
    path: '/organization-:orgSlug/game',
    name: 'games',
    component: () => import('../views/GamesListView.vue'),
  },
  {
    path: '/organization-:orgSlug/game/tracker',
    name: 'game-tracker',
    component: () => import('../views/GameView.vue'),
  },
  {
    path: '/organization-:orgSlug/teams',
    name: 'teams',
    component: () => import('../views/TeamsView.vue'),
  },
  {
    path: '/organization-:orgSlug/teams/:teamId',
    name: 'team-detail',
    component: () => import('../views/TeamDetailView.vue'),
  },
  {
    path: '/organization-:orgSlug/players',
    name: 'players',
    component: () => import('../views/PlayersView.vue'),
  },
  {
    path: '/organization-:orgSlug/players/:playerId',
    name: 'player-detail',
    component: () => import('../views/PlayerDetailView.vue'),
  },
  {
    path: '/organization-:orgSlug/seasons',
    name: 'seasons',
    component: () => import('../views/SeasonsView.vue'),
  },
  {
    path: '/organization-:orgSlug/seasons/:seasonId',
    name: 'season-detail',
    component: () => import('../views/SeasonDetailView.vue'),
  },
  {
    path: '/organization-:orgSlug/championships/:championshipId',
    name: 'championship-detail',
    component: () => import('../views/ChampionshipDetailView.vue'),
  },
  {
    path: '/organization-:orgSlug/stats',
    name: 'stats',
    component: () => import('../views/StatsView.vue'),
  },
  {
    path: '/organization-:orgSlug/drills',
    name: 'drills',
    component: () => import('../views/DrillsView.vue'),
  },
  {
    path: '/organization-:orgSlug/drills/:drillId',
    name: 'drill-detail',
    component: () => import('../views/DrillDetailView.vue'),
  },
  {
    path: '/organization-:orgSlug/training-sessions',
    name: 'training-sessions',
    component: () => import('../views/TrainingSessionsView.vue'),
  },
  {
    path: '/organization-:orgSlug/training-sessions/:sessionId',
    name: 'training-session-detail',
    component: () => import('../views/TrainingSessionDetailView.vue'),
  },
  {
    path: '/organization-:orgSlug/cycles',
    name: 'cycles',
    component: () => import('../views/CyclesView.vue'),
  },
  {
    path: '/organization-:orgSlug/cycles/:cycleId',
    name: 'cycle-detail',
    component: () => import('../views/CycleDetailView.vue'),
  },

  // ── Redirects ────────────────────────────────────────
  { path: '/', redirect: () => redirectToHome() },
  { path: '/:pathMatch(.*)*', redirect: () => redirectToHome() },
]

function redirectToHome() {
  if (!authStore.isLoggedIn) return '/login'
  if (authStore.isSuperAdmin) return '/admin'
  const slug = authStore.orgSlug
  return slug ? `/organization-${slug}` : '/login'
}

const router = createRouter({
  history: createWebHistory(),
  routes,
})

router.beforeEach((to) => {
  // Public routes (login page)
  if (to.meta.public) return true

  // Not logged in → redirect to login
  if (!authStore.isLoggedIn) return '/login'

  // Role-specific route check
  const required = to.meta.requireRole
  if (required) {
    const roleOrder = ['ROLE_COACH', 'ROLE_ADMIN', 'ROLE_SUPER_ADMIN']
    const userLevel = roleOrder.indexOf(authStore.role)
    const requiredLevel = roleOrder.indexOf(required)
    if (userLevel < requiredLevel) return redirectToHome()
  }

  return true
})

export default router
