<script setup lang="ts">
import { ref } from 'vue'
import { useRouter } from 'vue-router'
import { useAuthStore } from '@/stores/auth'

const router = useRouter()
const authStore = useAuthStore()

const email = ref('')
const password = ref('')
const error = ref('')
const loading = ref(false)

async function handleLogin() {
  try {
    loading.value = true
    error.value = ''
    await authStore.login({
      email: email.value,
      password: password.value,
    })
    router.push({ name: 'dashboard' })
  } catch (err: any) {
    // Display error message from backend
    if (err.response?.data?.message) {
      error.value = err.response.data.message
    } else if (err.response?.data?.errors) {
      // Handle Laravel validation errors
      const errors = Object.values(err.response.data.errors).flat()
      error.value = errors.join(', ')
    } else {
      error.value = 'Login failed. Please check your credentials.'
    }
  } finally {
    loading.value = false
  }
}
</script>

<template>
  <div class="flex min-h-screen items-center justify-center bg-gray-50">
    <div class="w-full max-w-md space-y-8 rounded-lg bg-white p-8 shadow">
      <div>
        <h2 class="text-center text-3xl font-bold">Sign in</h2>
      </div>

      <form @submit.prevent="handleLogin" class="mt-8 space-y-6">
        <div v-if="error" class="rounded-md bg-red-50 p-4 text-sm text-red-600">
          {{ error }}
        </div>

        <div class="space-y-4">
          <div>
            <label for="email" class="block text-sm font-medium">Email</label>
            <input
              id="email"
              v-model="email"
              type="email"
              required
              class="mt-1 block w-full rounded-md border border-gray-300 px-3 py-2 focus:border-blue-500 focus:outline-none focus:ring-blue-500"
            />
          </div>

          <div>
            <label for="password" class="block text-sm font-medium">Password</label>
            <input
              id="password"
              v-model="password"
              type="password"
              required
              class="mt-1 block w-full rounded-md border border-gray-300 px-3 py-2 focus:border-blue-500 focus:outline-none focus:ring-blue-500"
            />
          </div>
        </div>

        <button
          type="submit"
          :disabled="loading"
          class="w-full rounded-md bg-blue-600 px-4 py-2 text-white hover:bg-blue-700 disabled:opacity-50"
        >
          {{ loading ? 'Signing in...' : 'Sign in' }}
        </button>

        <p class="text-center text-sm">
          Don't have an account?
          <router-link to="/register" class="text-blue-600 hover:text-blue-700">
            Register
          </router-link>
        </p>

        <div class="rounded-md bg-gray-100 p-4 text-sm text-gray-600">
          <p class="font-medium mb-2">Test accounts:</p>
          <p>johndoe@example.com / password</p>
          <p>janedoe@example.com / password</p>
        </div>
      </form>
    </div>
  </div>
</template>
