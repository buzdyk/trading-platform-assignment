import { defineStore } from 'pinia'
import { ref } from 'vue'
import type { Asset, Order, Symbol, Trade } from '@/types/models'
import { api } from '@/api/client'

export const useTradingStore = defineStore('trading', () => {
  const balance = ref<string>('0')
  const assets = ref<Asset[]>([])
  const orders = ref<Order[]>([])
  const symbols = ref<Symbol[]>([])
  const trades = ref<Trade[]>([])
  const loading = ref(false)

  async function fetchProfile(): Promise<void> {
    const response = await api.get('/profile')
    balance.value = response.data.balance
    assets.value = response.data.assets
  }

  async function fetchOrders(symbolId?: number): Promise<void> {
    const params = symbolId ? { symbol_id: symbolId } : {}
    const response = await api.get('/orders', { params })
    orders.value = response.data.data
  }

  async function fetchSymbols(): Promise<void> {
    const response = await api.get('/symbols')
    symbols.value = response.data.data
  }

  async function fetchTrades(): Promise<void> {
    const response = await api.get('/trades')
    trades.value = response.data.data
  }

  async function createOrder(data: {
    symbol_id: number
    side: 'buy' | 'sell'
    price: string
    amount: string
  }): Promise<Order> {
    const response = await api.post('/orders', data)
    await fetchOrders()
    await fetchProfile()
    return response.data.order
  }

  async function cancelOrder(orderId: number): Promise<void> {
    await api.post(`/orders/${orderId}/cancel`)
    await fetchOrders()
    await fetchProfile()
  }

  function updateOrder(order: Order): void {
    const index = orders.value.findIndex(o => o.id === order.id)
    if (order.status === 'open') {
      if (index > -1) {
        orders.value[index] = order
      } else {
        orders.value.push(order)
      }
    } else {
      // Remove filled/cancelled orders from open orders list
      if (index > -1) {
        orders.value.splice(index, 1)
      }
    }
  }

  return {
    balance,
    assets,
    orders,
    symbols,
    trades,
    loading,
    fetchProfile,
    fetchOrders,
    fetchSymbols,
    fetchTrades,
    createOrder,
    cancelOrder,
    updateOrder,
  }
})
