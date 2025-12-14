<script setup lang="ts">
import { onMounted } from 'vue'
import { useTradingStore } from '@/stores/trading'

const trading = useTradingStore()

onMounted(async () => {
  await trading.fetchProfile()
})
</script>

<template>
  <div class="space-y-6">
    <!-- Balance Card -->
    <div class="rounded-lg bg-white p-6 shadow">
      <h3 class="text-sm font-medium text-gray-500">USD Balance</h3>
      <p class="mt-2 text-3xl font-bold text-gray-900">
        ${{ Number(trading.balance).toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 }) }}
      </p>
    </div>

    <!-- Assets Table -->
    <div class="rounded-lg bg-white shadow">
      <div class="border-b border-gray-200 px-6 py-4">
        <h3 class="text-lg font-medium text-gray-900">Assets</h3>
      </div>
      <div class="overflow-x-auto">
        <table class="min-w-full divide-y divide-gray-200">
          <thead class="bg-gray-50">
            <tr>
              <th class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500">
                Symbol
              </th>
              <th class="px-6 py-3 text-right text-xs font-medium uppercase tracking-wider text-gray-500">
                Available
              </th>
              <th class="px-6 py-3 text-right text-xs font-medium uppercase tracking-wider text-gray-500">
                Locked
              </th>
              <th class="px-6 py-3 text-right text-xs font-medium uppercase tracking-wider text-gray-500">
                Total
              </th>
            </tr>
          </thead>
          <tbody class="divide-y divide-gray-200 bg-white">
            <tr v-for="asset in trading.assets" :key="asset.symbol">
              <td class="whitespace-nowrap px-6 py-4 text-sm font-medium text-gray-900">
                {{ asset.symbol }}
              </td>
              <td class="whitespace-nowrap px-6 py-4 text-right text-sm text-gray-500">
                {{ Number(asset.amount).toFixed(8) }}
              </td>
              <td class="whitespace-nowrap px-6 py-4 text-right text-sm text-gray-500">
                {{ Number(asset.locked_amount).toFixed(8) }}
              </td>
              <td class="whitespace-nowrap px-6 py-4 text-right text-sm font-medium text-gray-900">
                {{ (Number(asset.amount) + Number(asset.locked_amount)).toFixed(8) }}
              </td>
            </tr>
            <tr v-if="trading.assets.length === 0">
              <td colspan="4" class="px-6 py-4 text-center text-sm text-gray-500">
                No assets yet
              </td>
            </tr>
          </tbody>
        </table>
      </div>
    </div>
  </div>
</template>
