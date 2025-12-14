<script setup lang="ts">
import { onMounted } from 'vue'
import { RouterView } from 'vue-router'
import { useAuthStore } from '@/stores/auth'
import ToastContainer from '@/components/ToastContainer.vue'

const auth = useAuthStore()

onMounted(() => {
  auth.fetchUser()
})
</script>

<template>
  <RouterView />
  <ToastContainer />
</template>
