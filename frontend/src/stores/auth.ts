import { defineStore } from 'pinia'
import { ref, computed } from 'vue'
import type { User } from '@/types/models'
import { api } from '@/api/client'

export const useAuthStore = defineStore('auth', () => {
  const user = ref<User | null>(null)
  const token = ref<string | null>(localStorage.getItem('auth_token'))

  const isAuthenticated = computed(() => !!token.value)

  async function login(credentials: { email: string; password: string }) {
    const response = await api.post('/login', credentials)
    user.value = response.data.user
    token.value = response.data.token
    localStorage.setItem('auth_token', response.data.token)
  }

  async function register(data: {
    name: string
    email: string
    password: string
    password_confirmation: string
  }) {
    const response = await api.post('/register', data)
    user.value = response.data.user
    token.value = response.data.token
    localStorage.setItem('auth_token', response.data.token)
  }

  async function logout() {
    try {
      await api.post('/logout')
    } finally {
      user.value = null
      token.value = null
      localStorage.removeItem('auth_token')
    }
  }

  function setUser(newUser: User) {
    user.value = newUser
  }

  return {
    user,
    token,
    isAuthenticated,
    login,
    register,
    logout,
    setUser,
  }
})
