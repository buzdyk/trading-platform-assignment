<script setup lang="ts">
//
</script>

<template>
  <div class="flex min-h-screen items-center justify-center">
    <div class="text-center">
      <h1 class="text-4xl font-bold">Trading Platform</h1>
      <p class="mt-4 text-gray-600">Trade crypto with limit orders</p>
      <div class="mt-8 space-x-4">
        <router-link
          to="/login"
          class="rounded-md bg-blue-600 px-4 py-2 text-white hover:bg-blue-700"
        >
          Login
        </router-link>
        <router-link
          to="/register"
          class="rounded-md border border-gray-300 px-4 py-2 hover:bg-gray-50"
        >
          Register
        </router-link>
      </div>
    </div>
  </div>
</template>
