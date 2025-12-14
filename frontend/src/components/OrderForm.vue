<script setup lang="ts">
import { onMounted, ref, computed } from 'vue'
import { useTradingStore } from '@/stores/trading'
import { useToastStore } from '@/stores/toast'
import type { AxiosError } from 'axios'

const trading = useTradingStore()
const toast = useToastStore()

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

const selectedSymbol = computed(() =>
  trading.symbols.find(s => s.id === form.value.symbol_id)
)

const preview = computed(() => {
  if (!form.value.price || !form.value.amount || !selectedSymbol.value) return ''
  const side = form.value.side.toUpperCase()
  const amount = parseFloat(form.value.amount)
  const price = parseFloat(form.value.price)
  return `${side} ${amount} ${selectedSymbol.value.code} @ $${price.toLocaleString()}`
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
    toast.success(`${form.value.side.toUpperCase()} order placed successfully`)
    form.value.price = ''
    form.value.amount = ''
  } catch (err) {
    const error = err as AxiosError<{ errors?: Record<string, string[]>; message?: string }>
    if (error.response?.data?.errors) {
      errors.value = error.response.data.errors
      toast.error(Object.values(error.response.data.errors).flat()[0] ?? 'Validation error')
    } else if (error.response?.data?.message) {
      toast.error(error.response.data.message)
    } else {
      toast.error('Failed to place order')
    }
  } finally {
    submitting.value = false
  }
}

function fill(symbolId: number, price: string, amount: string, side: 'buy' | 'sell'): void {
  form.value.symbol_id = symbolId
  form.value.price = price
  form.value.amount = amount
  form.value.side = side === 'buy' ? 'sell' : 'buy'
}

defineExpose({ fill })
</script>

<template>
  <form @submit.prevent="handleSubmit" class="rounded-lg bg-white p-4 shadow">
    <div class="grid grid-cols-5 items-end gap-4">
      <!-- Symbol -->
      <div>
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
      <div>
        <label class="block text-xs font-medium text-gray-500">Side</label>
        <div class="mt-1 flex gap-1">
          <button
            type="button"
            @click="form.side = 'buy'"
            :class="[
              'flex-1 rounded px-3 py-1.5 text-xs font-bold',
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
              'flex-1 rounded px-3 py-1.5 text-xs font-bold',
              form.side === 'sell'
                ? 'bg-red-600 text-white'
                : 'border border-gray-300 bg-gray-100 text-gray-500',
            ]"
          >
            SELL
          </button>
        </div>
      </div>

      <!-- Price -->
      <div>
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
      <div>
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
      <div>
        <label class="block text-xs font-medium text-gray-500">Total</label>
        <div class="mt-1 py-1.5 text-sm font-medium">
          ${{ total.toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 }) }}
        </div>
      </div>
    </div>

    <!-- Second row: Preview + Submit -->
    <div class="mt-3 flex items-center justify-end gap-3">
      <div v-if="preview" class="text-xs text-gray-500">
        {{ preview }}
      </div>
      <button
        type="submit"
        :disabled="submitting"
        :class="[
          'rounded-md px-4 py-1.5 text-sm font-medium text-white disabled:opacity-50',
          form.side === 'buy' ? 'bg-green-600 hover:bg-green-700' : 'bg-red-600 hover:bg-red-700',
        ]"
      >
        {{ submitting ? '...' : form.side === 'buy' ? 'Buy' : 'Sell' }}
      </button>
    </div>

    <!-- Errors -->
    <div v-if="Object.keys(errors).length" class="mt-2 text-xs text-red-600">
      {{ Object.values(errors).flat()[0] }}
    </div>
  </form>
</template>
