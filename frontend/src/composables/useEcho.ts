import Echo from 'laravel-echo'
import Pusher from 'pusher-js'
import { ref, onUnmounted } from 'vue'
import { useAuthStore } from '@/stores/auth'
import { useTradingStore } from '@/stores/trading'

declare global {
  interface Window {
    Pusher: typeof Pusher
    Echo: Echo<'pusher'>
  }
}

let echoInstance: Echo<'pusher'> | null = null

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
    authEndpoint: `${import.meta.env.VITE_API_URL?.replace('/api', '')}/broadcasting/auth`,
    auth: {
      headers: {
        Authorization: `Bearer ${localStorage.getItem('auth_token')}`,
      },
    },
  })

  return echoInstance
}

export function useEcho() {
  const authStore = useAuthStore()
  const tradingStore = useTradingStore()
  const connected = ref(false)

  function subscribe(): void {
    if (!authStore.user?.id) {
      return
    }

    const echo = getEcho()
    const channel = echo.private(`user.${authStore.user.id}`)

    channel.listen('OrderMatched', () => {
      tradingStore.fetchProfile()
      tradingStore.fetchOrders()
    })

    connected.value = true
  }

  function unsubscribe(): void {
    if (!authStore.user?.id || !echoInstance) {
      return
    }

    echoInstance.leave(`user.${authStore.user.id}`)
    connected.value = false
  }

  onUnmounted(() => {
    unsubscribe()
  })

  return {
    connected,
    subscribe,
    unsubscribe,
  }
}
