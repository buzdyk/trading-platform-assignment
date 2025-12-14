<script setup lang="ts">
</script>

<template>
  <div class="border-b border-gray-200 bg-white">
    <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
      <nav class="flex gap-6 pt-3">
        <RouterLink
          to="/dashboard"
          class="px-3 pb-3 pt-2 text-gray-700 hover:text-gray-900"
          active-class="border-b-2 border-black font-bold"
        >
          Dashboard
        </RouterLink>
      </nav>
    </div>
  </div>
</template>
