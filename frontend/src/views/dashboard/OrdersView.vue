<script setup lang="ts">
import { onMounted, ref } from 'vue'
import { useTradingStore } from '@/stores/trading'
import { useAuthStore } from '@/stores/auth'
import OrderForm from '@/components/OrderForm.vue'

const trading = useTradingStore()
const auth = useAuthStore()
const cancelling = ref<number | null>(null)

onMounted(async () => {
  await trading.fetchOrders()
})

async function handleCancel(orderId: number): Promise<void> {
  cancelling.value = orderId
  try {
    await trading.cancelOrder(orderId)
  } finally {
    cancelling.value = null
  }
}
</script>

<template>
  <div class="space-y-4">
    <OrderForm />

    <div class="rounded-lg bg-white shadow">
      <div class="border-b border-gray-200 px-6 py-4">
        <h3 class="text-lg font-medium text-gray-900">Open Orders</h3>
      </div>
      <div class="overflow-x-auto">
        <table class="min-w-full divide-y divide-gray-200">
          <thead class="bg-gray-50">
            <tr>
              <th class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500">
                Symbol
              </th>
              <th class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500">
                Side
              </th>
              <th class="px-6 py-3 text-right text-xs font-medium uppercase tracking-wider text-gray-500">
                Price
              </th>
              <th class="px-6 py-3 text-right text-xs font-medium uppercase tracking-wider text-gray-500">
                Amount
              </th>
              <th class="px-6 py-3 text-right text-xs font-medium uppercase tracking-wider text-gray-500">
                Total
              </th>
              <th class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500">
                Created
              </th>
              <th class="px-6 py-3 text-right text-xs font-medium uppercase tracking-wider text-gray-500">
                Action
              </th>
            </tr>
          </thead>
          <tbody class="divide-y divide-gray-200 bg-white">
            <tr v-for="order in trading.orders" :key="order.id">
              <td class="whitespace-nowrap px-6 py-4 text-sm font-medium text-gray-900">
                {{ order.symbol }}
              </td>
              <td class="whitespace-nowrap px-6 py-4 text-sm">
                <span
                  :class="order.side === 'buy' ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800'"
                  class="inline-flex rounded-full px-2 text-xs font-semibold leading-5"
                >
                  {{ order.side.toUpperCase() }}
                </span>
              </td>
              <td class="whitespace-nowrap px-6 py-4 text-right text-sm text-gray-500">
                ${{ Number(order.price).toLocaleString('en-US', { minimumFractionDigits: 2 }) }}
              </td>
              <td class="whitespace-nowrap px-6 py-4 text-right text-sm text-gray-500">
                {{ Number(order.amount).toFixed(8) }}
              </td>
              <td class="whitespace-nowrap px-6 py-4 text-right text-sm font-medium text-gray-900">
                ${{ (Number(order.price) * Number(order.amount)).toLocaleString('en-US', { minimumFractionDigits: 2 }) }}
              </td>
              <td class="whitespace-nowrap px-6 py-4 text-sm text-gray-500">
                {{ new Date(order.created_at).toLocaleDateString() }}
              </td>
              <td class="whitespace-nowrap px-6 py-4 text-right text-sm">
                <button
                  v-if="order.user_id === auth.user?.id"
                  @click="handleCancel(order.id)"
                  :disabled="cancelling === order.id"
                  class="text-red-600 hover:text-red-900 disabled:opacity-50"
                >
                  {{ cancelling === order.id ? 'Cancelling...' : 'Cancel' }}
                </button>
              </td>
            </tr>
            <tr v-if="trading.orders.length === 0">
              <td colspan="7" class="px-6 py-4 text-center text-sm text-gray-500">
                No open orders
              </td>
            </tr>
          </tbody>
        </table>
      </div>
    </div>
  </div>
</template>
