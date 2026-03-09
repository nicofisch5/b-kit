import { createRouter, createWebHistory } from 'vue-router'
import GameView from '../views/GameView.vue'

const routes = [
  {
    path: '/',
    name: 'home',
    component: () => import('../views/HomeView.vue')
  },
  {
    path: '/game',
    name: 'game',
    component: GameView
  },
  {
    path: '/teams',
    name: 'teams',
    component: () => import('../views/TeamsView.vue')
  },
  {
    path: '/seasons',
    name: 'seasons',
    component: () => import('../views/SeasonsView.vue')
  },
  {
    path: '/stats',
    name: 'stats',
    component: () => import('../views/StatsView.vue')
  }
]

const router = createRouter({
  history: createWebHistory(),
  routes
})

export default router
