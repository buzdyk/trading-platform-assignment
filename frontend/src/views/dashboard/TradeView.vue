<script setup lang="ts">
import { onMounted, ref, computed } from 'vue'
import { useTradingStore } from '@/stores/trading'
import type { AxiosError } from 'axios'

const trading = useTradingStore()

const form = ref({
  symbol_id: null as number | null,
  side: 'buy' as 'buy' | 'sell',
  price: '',
  amount: '',
})

const errors = ref<Record<string, string[]>>({})
const submitting = ref(false)
const success = ref(false)

const total = computed(() => {
  const price = parseFloat(form.value.price) || 0
  const amount = parseFloat(form.value.amount) || 0
  return price * amount
})

onMounted(async () => {
  await trading.fetchSymbols()
  const firstSymbol = trading.symbols[0]
  if (firstSymbol) {
    form.value.symbol_id = firstSymbol.id
  }
})

async function handleSubmit(): Promise<void> {
  if (!form.value.symbol_id) return

  errors.value = {}
  success.value = false
  submitting.value = true

  try {
    await trading.createOrder({
      symbol_id: form.value.symbol_id,
      side: form.value.side,
      price: form.value.price,
      amount: form.value.amount,
    })
    success.value = true
    form.value.price = ''
    form.value.amount = ''
  } catch (err) {
    const error = err as AxiosError<{ errors?: Record<string, string[]> }>
    if (error.response?.data?.errors) {
      errors.value = error.response.data.errors
    }
  } finally {
    submitting.value = false
  }
}
</script>

<template>
  <div class="mx-auto max-w-md">
    <div class="rounded-lg bg-white p-6 shadow">
      <h3 class="mb-6 text-lg font-medium text-gray-900">Place Order</h3>

      <!-- Success Message -->
      <div v-if="success" class="mb-4 rounded-md bg-green-50 p-4">
        <p class="text-sm text-green-800">Order placed successfully!</p>
      </div>

      <form @submit.prevent="handleSubmit" class="space-y-4">
        <!-- Symbol -->
        <div>
          <label class="block text-sm font-medium text-gray-700">Symbol</label>
          <select
            v-model="form.symbol_id"
            class="mt-1 block w-full rounded-md border border-gray-300 bg-white px-3 py-2 text-gray-900 shadow-sm focus:border-blue-500 focus:outline-none focus:ring-1 focus:ring-blue-500"
          >
            <option v-for="symbol in trading.symbols" :key="symbol.id" :value="symbol.id">
              {{ symbol.code }} - {{ symbol.name }}
            </option>
          </select>
          <p v-if="errors.symbol_id" class="mt-1 text-sm text-red-600">{{ errors.symbol_id[0] }}</p>
        </div>

        <!-- Side Toggle -->
        <div>
          <label class="block text-sm font-medium text-gray-700">Side</label>
          <div class="mt-1 grid grid-cols-2 gap-2">
            <button
              type="button"
              @click="form.side = 'buy'"
              :class="[
                'rounded-md px-4 py-3 text-sm font-bold transition-all',
                form.side === 'buy'
                  ? 'bg-green-600 text-white shadow-lg shadow-green-600/50'
                  : 'border border-gray-300 bg-gray-100 text-gray-600 hover:bg-gray-200',
              ]"
            >
              BUY
            </button>
            <button
              type="button"
              @click="form.side = 'sell'"
              :class="[
                'rounded-md px-4 py-3 text-sm font-bold transition-all',
                form.side === 'sell'
                  ? 'bg-red-600 text-white shadow-lg shadow-red-600/50'
                  : 'border border-gray-300 bg-gray-100 text-gray-600 hover:bg-gray-200',
              ]"
            >
              SELL
            </button>
          </div>
          <p v-if="errors.side" class="mt-1 text-sm text-red-600">{{ errors.side[0] }}</p>
        </div>

        <!-- Price -->
        <div>
          <label class="block text-sm font-medium text-gray-700">Price (USD)</label>
          <input
            v-model="form.price"
            type="number"
            step="0.01"
            min="0"
            class="mt-1 block w-full rounded-md border border-gray-300 bg-white px-3 py-2 text-gray-900 shadow-sm focus:border-blue-500 focus:outline-none focus:ring-1 focus:ring-blue-500"
            placeholder="0.00"
          />
          <p v-if="errors.price" class="mt-1 text-sm text-red-600">{{ errors.price[0] }}</p>
        </div>

        <!-- Amount -->
        <div>
          <label class="block text-sm font-medium text-gray-700">Amount</label>
          <input
            v-model="form.amount"
            type="number"
            step="0.00000001"
            min="0"
            class="mt-1 block w-full rounded-md border border-gray-300 bg-white px-3 py-2 text-gray-900 shadow-sm focus:border-blue-500 focus:outline-none focus:ring-1 focus:ring-blue-500"
            placeholder="0.00000000"
          />
          <p v-if="errors.amount" class="mt-1 text-sm text-red-600">{{ errors.amount[0] }}</p>
        </div>

        <!-- Total -->
        <div class="rounded-md bg-gray-50 p-3">
          <div class="flex justify-between text-sm">
            <span class="text-gray-500">Total</span>
            <span class="font-medium text-gray-900">
              ${{ total.toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 }) }}
            </span>
          </div>
        </div>

        <!-- Submit -->
        <button
          type="submit"
          :disabled="submitting"
          class="w-full cursor-pointer rounded-md bg-blue-600 px-4 py-2 text-sm font-medium text-white hover:bg-blue-700 disabled:opacity-50"
        >
          {{ submitting ? 'Placing Order...' : 'Place Order' }}
        </button>
      </form>
    </div>
  </div>
</template>
