import { createRouter, createWebHistory } from 'vue-router'

import LoginPage from '../views/LoginPage.vue'
import SALNForm from '@/views/SALNForm.vue'

const routes = [
  { path: '/', component: LoginPage },
  { path: '/saln', component: SALNForm },
]

const router = createRouter({
  history: createWebHistory(import.meta.env.BASE_URL),
  routes,
})

export default router