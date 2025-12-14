<script setup lang="ts">
import { computed } from 'vue'

const today = computed(() => {
  const date = new Date()
  return date.toLocaleDateString('en-US', {
    weekday: 'long',
    month: 'long',
    day: 'numeric'
  })
})
</script>

<template>
  <div class="text-base font-semibold text-gray-900">
    {{ today }}
  </div>
</template>
