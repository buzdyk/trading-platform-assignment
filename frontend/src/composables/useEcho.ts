import Echo from 'laravel-echo'
import Pusher from 'pusher-js'
import { ref, onUnmounted } from 'vue'
import { useTradingStore } from '@/stores/trading'
import type { Order } from '@/types/models'

declare global {
  interface Window {
    Pusher: typeof Pusher
    Echo: Echo<'pusher'>
  }
}

let echoInstance: Echo<'pusher'> | null = null
let isSubscribed = false

function getEcho(): Echo<'pusher'> {
  if (echoInstance) {
    return echoInstance
  }

  window.Pusher = Pusher

  echoInstance = new Echo({
    broadcaster: 'pusher',
    key: import.meta.env.VITE_PUSHER_APP_KEY,
    wsHost: import.meta.env.VITE_PUSHER_HOST,
    wsPort: import.meta.env.VITE_PUSHER_PORT,
    wssPort: import.meta.env.VITE_PUSHER_PORT,
    forceTLS: import.meta.env.VITE_PUSHER_SCHEME === 'https',
    disableStats: true,
    enabledTransports: ['ws', 'wss'],
    cluster: 'mt1',
    authorizer: (channel: { name: string }) => ({
      authorize: (socketId: string, callback: (error: Error | null, data: { auth: string } | null) => void) => {
        fetch(`${import.meta.env.VITE_API_URL}/broadcasting/auth`, {
          method: 'POST',
          headers: {
            'Content-Type': 'application/json',
            'Authorization': `Bearer ${localStorage.getItem('auth_token')}`,
          },
          body: JSON.stringify({ socket_id: socketId, channel_name: channel.name }),
        })
          .then(response => response.json())
          .then(data => callback(null, data))
          .catch(error => callback(error, null))
      },
    }),
  })

  return echoInstance
}

export function useEcho() {
  const tradingStore = useTradingStore()
  const connected = ref(false)

  function subscribe(): void {
    if (isSubscribed) {
      return
    }

    const echo = getEcho()
    const channel = echo.private('orders')

    channel.listen('.OrderUpdated', (data: { order: Order }) => {
      tradingStore.updateOrder(data.order)
      tradingStore.fetchProfile()
    })

    isSubscribed = true
    connected.value = true
  }

  function unsubscribe(): void {
    if (!isSubscribed || !echoInstance) {
      return
    }

    echoInstance.leave('private-orders')
    isSubscribed = false
    connected.value = false
  }

  // Auto-subscribe on mount
  subscribe()

  onUnmounted(() => {
    unsubscribe()
  })

  return {
    connected,
    subscribe,
    unsubscribe,
  }
}
