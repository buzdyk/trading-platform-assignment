<script setup lang="ts">
import { ref, provide } from 'vue'
import type { ActionButton } from '@/types/action-button'
import { useEcho } from '@/composables/useEcho'

useEcho() // Auto-subscribes via watcher when user is available

const actionButton = ref<ActionButton | null>(null)

provide('setActionButton', (button: ActionButton | null) => {
  actionButton.value = button
})
</script>

<template>
  <main class="mx-auto max-w-7xl px-4 py-8 sm:px-6 lg:px-8">
    <div class="mb-6 flex items-center justify-between">
      <h2 class="text-2xl font-bold">Dashboard</h2>
      <button
        v-if="actionButton"
        @click="actionButton.onClick"
        class="rounded-md bg-blue-600 px-4 py-2 text-white hover:bg-blue-700"
      >
        {{ actionButton.label }}
      </button>
    </div>

    <div class="flex gap-6">
      <!-- Left Navigation Menu -->
      <nav class="w-48">
        <ul class="space-y-1">
          <li>
            <RouterLink
              to="/dashboard/wallet"
              class="block rounded px-3 py-2 text-gray-700 hover:bg-gray-100"
              active-class="bg-gray-100 font-bold text-gray-900"
            >
              Wallet
            </RouterLink>
          </li>
          <li>
            <RouterLink
              to="/dashboard/orders"
              class="block rounded px-3 py-2 text-gray-700 hover:bg-gray-100"
              active-class="bg-gray-100 font-bold text-gray-900"
            >
              Orders
            </RouterLink>
          </li>
        </ul>
      </nav>

      <!-- Main Content Area -->
      <div class="flex-1">
        <RouterView />
      </div>
    </div>
  </main>
</template>
