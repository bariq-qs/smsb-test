<script setup>
import { RouterLink } from 'vue-router'
</script>

<template>
  <div class="flex min-h-screen flex-col items-center justify-center px-4 text-center">
    <p class="text-sm font-semibold uppercase tracking-wide text-indigo-600 dark:text-indigo-400">
      SMSB Coding Test
    </p>
    <h1 class="mt-2 text-4xl font-bold text-slate-900 dark:text-white">
      Purchase Order Management
    </h1>
    <p class="mt-4 max-w-xl text-slate-600 dark:text-slate-400">
      A Laravel + Vue demo covering authentication and roles, related CRUD with soft
      deletes and file attachments, full audit trails, and queued Excel import/export.
    </p>
    <RouterLink
      :to="{ name: 'login' }"
      class="mt-8 rounded-md bg-slate-900 px-5 py-2.5 text-sm font-semibold text-white hover:bg-slate-700 dark:bg-white dark:text-slate-900 dark:hover:bg-slate-200"
    >
      Sign in
    </RouterLink>
  </div>
</template>
