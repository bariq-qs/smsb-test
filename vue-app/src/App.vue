<script setup>
import { RouterView, useRoute } from 'vue-router'
import Toast from 'primevue/toast'
import ConfirmDialog from 'primevue/confirmdialog'
import AppNav from '@/components/AppNav.vue'

const route = useRoute()
</script>

<template>
  <div class="min-h-screen bg-slate-50 dark:bg-slate-950">
    <Toast />
    <ConfirmDialog />
    <AppNav v-if="route.meta.requiresAuth" />
    <RouterView />
  </div>
</template>
