import { createRouter, createWebHistory } from 'vue-router'

import LoginPage from '../views/LoginPage.vue'
import SALNForm from '@/views/SALNForm.vue'

const routes = [
  { path: '/', component: LoginPage },
  { path: '/login', component: LoginPage },
  { path: '/saln', component: SALNForm },
]

const router = createRouter({
  history: createWebHistory(import.meta.env.BASE_URL),
  routes,
})

const authRedirectTargets = new Set(['/', '/login', '/saln'])

router.beforeEach((to) => {
  if (!authRedirectTargets.has(to.path)) {
    return true
  }

  const token = localStorage.getItem('access_token')

  if (token && to.path !== '/saln') {
    return { path: '/saln', replace: true }
  }

  if (!token && to.path !== '/login') {
    return { path: '/login', replace: true }
  }

  return true
})

export default router
