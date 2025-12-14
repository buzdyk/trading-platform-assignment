<script setup lang="ts">
import { useRouter } from 'vue-router'
import { useAuthStore } from '@/stores/auth'
import TopNav from '@/components/TopNav.vue'

const router = useRouter()
const authStore = useAuthStore()

async function handleLogout() {
  await authStore.logout()
  router.push({ name: 'login' })
}
</script>

<template>
  <div class="min-h-screen bg-gray-50">
    <nav class="bg-white shadow">
      <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
        <div class="flex h-16 justify-between">
          <div class="flex items-center">
            <h1 class="text-xl font-bold">Trading Platform</h1>
          </div>
          <div v-if="authStore.isAuthenticated" class="flex items-center">
            <button
              @click="handleLogout"
              class="rounded-md bg-gray-100 px-4 py-2 text-sm hover:bg-gray-200"
            >
              Logout
            </button>
          </div>
        </div>
      </div>
    </nav>

    <TopNav v-if="authStore.isAuthenticated" />

    <router-view />
  </div>
</template>
