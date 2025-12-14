<script setup lang="ts">
import { ref } from 'vue'
import { useRouter } from 'vue-router'
import { useAuthStore } from '@/stores/auth'

const router = useRouter()
const authStore = useAuthStore()

const name = ref('')
const email = ref('')
const password = ref('')
const passwordConfirmation = ref('')
const errors = ref<string[]>([])
const loading = ref(false)

async function handleRegister() {
  try {
    loading.value = true
    errors.value = []
    await authStore.register({
      name: name.value,
      email: email.value,
      password: password.value,
      password_confirmation: passwordConfirmation.value,
    })
    router.push({ name: 'dashboard' })
  } catch (err: any) {
    if (err.response?.data?.errors) {
      errors.value = Object.values(err.response.data.errors).flat() as string[]
    } else {
      errors.value = [err.response?.data?.message || 'Registration failed']
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
        <h2 class="text-center text-3xl font-bold">Create account</h2>
      </div>

      <form @submit.prevent="handleRegister" class="mt-8 space-y-6">
        <div v-if="errors.length" class="rounded-md bg-red-50 p-4 text-sm text-red-600">
          <ul class="list-disc list-inside space-y-1">
            <li v-for="error in errors" :key="error">{{ error }}</li>
          </ul>
        </div>

        <div class="space-y-4">
          <div>
            <label for="name" class="block text-sm font-medium">Name</label>
            <input
              id="name"
              v-model="name"
              type="text"
              required
              class="mt-1 block w-full rounded-md border border-gray-300 px-3 py-2 focus:border-blue-500 focus:outline-none focus:ring-blue-500"
            />
          </div>

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

          <div>
            <label for="password_confirmation" class="block text-sm font-medium">
              Confirm Password
            </label>
            <input
              id="password_confirmation"
              v-model="passwordConfirmation"
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
          {{ loading ? 'Creating account...' : 'Create account' }}
        </button>

        <p class="text-center text-sm">
          Already have an account?
          <router-link to="/login" class="text-blue-600 hover:text-blue-700">
            Sign in
          </router-link>
        </p>
      </form>
    </div>
  </div>
</template>
