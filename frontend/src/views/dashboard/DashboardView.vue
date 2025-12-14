<script setup lang="ts">
import { useEcho } from '@/composables/useEcho'

useEcho()
</script>

<template>
  <main class="mx-auto max-w-7xl px-4 py-8 sm:px-6 lg:px-8">
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
