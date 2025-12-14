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
  submitting.value = true

  try {
    await trading.createOrder({
      symbol_id: form.value.symbol_id,
      side: form.value.side,
      price: form.value.price,
      amount: form.value.amount,
    })
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
  <form @submit.prevent="handleSubmit" class="rounded-lg bg-white p-4 shadow">
    <div class="flex items-end gap-3">
      <!-- Symbol -->
      <div class="w-40">
        <label class="block text-xs font-medium text-gray-500">Symbol</label>
        <select
          v-model="form.symbol_id"
          class="mt-1 block w-full rounded-md border border-gray-300 bg-white px-2 py-1.5 text-sm focus:border-blue-500 focus:outline-none"
        >
          <option v-for="symbol in trading.symbols" :key="symbol.id" :value="symbol.id">
            {{ symbol.code }}
          </option>
        </select>
      </div>

      <!-- Side Toggle -->
      <div class="flex gap-1">
        <button
          type="button"
          @click="form.side = 'buy'"
          :class="[
            'rounded px-3 py-1.5 text-xs font-bold',
            form.side === 'buy'
              ? 'bg-green-600 text-white'
              : 'border border-gray-300 bg-gray-100 text-gray-500',
          ]"
        >
          BUY
        </button>
        <button
          type="button"
          @click="form.side = 'sell'"
          :class="[
            'rounded px-3 py-1.5 text-xs font-bold',
            form.side === 'sell'
              ? 'bg-red-600 text-white'
              : 'border border-gray-300 bg-gray-100 text-gray-500',
          ]"
        >
          SELL
        </button>
      </div>

      <!-- Price -->
      <div class="w-32">
        <label class="block text-xs font-medium text-gray-500">Price</label>
        <input
          v-model="form.price"
          type="number"
          step="0.01"
          min="0"
          placeholder="0.00"
          class="mt-1 block w-full rounded-md border border-gray-300 px-2 py-1.5 text-sm focus:border-blue-500 focus:outline-none"
        />
      </div>

      <!-- Amount -->
      <div class="w-32">
        <label class="block text-xs font-medium text-gray-500">Amount</label>
        <input
          v-model="form.amount"
          type="number"
          step="0.00000001"
          min="0"
          placeholder="0.00"
          class="mt-1 block w-full rounded-md border border-gray-300 px-2 py-1.5 text-sm focus:border-blue-500 focus:outline-none"
        />
      </div>

      <!-- Total -->
      <div class="w-28 text-right">
        <label class="block text-xs font-medium text-gray-500">Total</label>
        <div class="mt-1 py-1.5 text-sm font-medium">
          ${{ total.toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 }) }}
        </div>
      </div>

      <!-- Submit -->
      <button
        type="submit"
        :disabled="submitting"
        class="rounded-md bg-blue-600 px-4 py-1.5 text-sm font-medium text-white hover:bg-blue-700 disabled:opacity-50"
      >
        {{ submitting ? '...' : 'Place' }}
      </button>
    </div>

    <!-- Errors -->
    <div v-if="Object.keys(errors).length" class="mt-2 text-xs text-red-600">
      {{ Object.values(errors).flat()[0] }}
    </div>
  </form>
</template>
