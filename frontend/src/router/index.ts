import { createRouter, createWebHistory } from 'vue-router'
import type { NavigationGuardNext, RouteLocationNormalized } from 'vue-router'

// Route guards
const requireAuth = (_to: RouteLocationNormalized, _from: RouteLocationNormalized, next: NavigationGuardNext) => {
  const token = localStorage.getItem('auth_token')
  token ? next() : next({ name: 'login' })
}

const guestOnly = (_to: RouteLocationNormalized, _from: RouteLocationNormalized, next: NavigationGuardNext) => {
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
              redirect: { name: 'wallet' },
              children: [
                {
                  path: 'wallet',
                  name: 'wallet',
                  component: () => import('@/views/dashboard/WalletView.vue'),
                },
                {
                  path: 'orders',
                  name: 'orders',
                  component: () => import('@/views/dashboard/OrdersView.vue'),
                },
                {
                  path: 'trade',
                  name: 'trade',
                  component: () => import('@/views/dashboard/TradeView.vue'),
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
