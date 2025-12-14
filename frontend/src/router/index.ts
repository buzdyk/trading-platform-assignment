import { createRouter, createWebHistory } from 'vue-router'
import type { NavigationGuardNext, RouteLocationNormalized } from 'vue-router'

// Route guards
const requireAuth = (to: RouteLocationNormalized, from: RouteLocationNormalized, next: NavigationGuardNext) => {
  const token = localStorage.getItem('auth_token')
  token ? next() : next({ name: 'login' })
}

const guestOnly = (to: RouteLocationNormalized, from: RouteLocationNormalized, next: NavigationGuardNext) => {
  const token = localStorage.getItem('auth_token')
  token ? next({ name: 'dashboard' }) : next()
}

const router = createRouter({
  history: createWebHistory(import.meta.env.BASE_URL),
  routes: [
    {
      path: '/',
      component: () => import('@/layouts/MainLayout.vue'),
      children: [
        // Public routes
        {
          path: '',
          name: 'home',
          component: () => import('@/views/HomeView.vue'),
        },

        // Guest-only routes (login, register)
        {
          path: '',
          beforeEnter: guestOnly,
          children: [
            {
              path: 'login',
              name: 'login',
              component: () => import('@/views/auth/LoginView.vue'),
            },
            {
              path: 'register',
              name: 'register',
              component: () => import('@/views/auth/RegisterView.vue'),
            },
          ],
        },

        // Protected routes
        {
          path: '',
          beforeEnter: requireAuth,
          children: [
            {
              path: 'dashboard',
              name: 'dashboard',
              component: () => import('@/views/dashboard/DashboardView.vue'),
              redirect: { name: 'dashboard-today' },
              children: [
                {
                  path: 'today',
                  name: 'dashboard-today',
                  component: () => import('@/views/dashboard/TodayView.vue'),
                },
                {
                  path: 'month',
                  name: 'dashboard-month',
                  component: () => import('@/views/dashboard/MonthView.vue'),
                },
              ],
            },
          ],
        },
      ],
    },
  ],
})

export default router
