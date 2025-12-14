import { defineStore } from 'pinia'
import { ref } from 'vue'

export interface Toast {
  id: number
  message: string
  type: 'success' | 'error'
}

let nextId = 0

export const useToastStore = defineStore('toast', () => {
  const toasts = ref<Toast[]>([])

  function show(message: string, type: 'success' | 'error' = 'success', duration = 3000): void {
    const id = nextId++
    toasts.value.push({ id, message, type })

    setTimeout(() => {
      remove(id)
    }, duration)
  }

  function remove(id: number): void {
    const index = toasts.value.findIndex(t => t.id === id)
    if (index > -1) {
      toasts.value.splice(index, 1)
    }
  }

  function success(message: string): void {
    show(message, 'success')
  }

  function error(message: string): void {
    show(message, 'error')
  }

  return {
    toasts,
    show,
    remove,
    success,
    error,
  }
})
