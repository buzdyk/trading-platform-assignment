<script setup lang="ts">
import { useToastStore } from '@/stores/toast'

const toast = useToastStore()
</script>

<template>
  <div class="fixed bottom-4 right-4 z-50 space-y-2">
    <TransitionGroup name="toast">
      <div
        v-for="t in toast.toasts"
        :key="t.id"
        :class="[
          'flex items-center gap-2 rounded-lg px-4 py-3 text-sm font-medium text-white shadow-lg',
          t.type === 'success' ? 'bg-green-600' : 'bg-red-600',
        ]"
      >
        <span v-if="t.type === 'success'">&#10003;</span>
        <span v-else>&#10007;</span>
        {{ t.message }}
        <button @click="toast.remove(t.id)" class="ml-2 opacity-70 hover:opacity-100">
          &times;
        </button>
      </div>
    </TransitionGroup>
  </div>
</template>

<style scoped>
.toast-enter-active,
.toast-leave-active {
  transition: all 0.3s ease;
}

.toast-enter-from {
  opacity: 0;
  transform: translateX(100%);
}

.toast-leave-to {
  opacity: 0;
  transform: translateX(100%);
}
</style>
