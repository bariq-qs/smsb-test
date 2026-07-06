<script setup>
import { RouterLink, useRouter } from 'vue-router'
import { useAuthStore } from '@/stores/auth'

const auth = useAuthStore()
const router = useRouter()

const links = [
  { to: { name: 'dashboard' }, label: 'Dashboard' },
  { to: { name: 'roles' }, label: 'Roles' },
  { to: { name: 'users' }, label: 'Users', role: 'Administrator' },
  { to: { name: 'suppliers' }, label: 'Suppliers' },
  { to: { name: 'products' }, label: 'Products' },
  { to: { name: 'purchase-orders' }, label: 'Purchase Orders' },
]

async function handleLogout() {
  await auth.logout()
  router.push({ name: 'login' })
}
</script>

<template>
  <header class="border-b border-slate-200 bg-white dark:border-slate-800 dark:bg-slate-900">
    <nav class="mx-auto flex max-w-6xl flex-wrap items-center justify-between gap-4 px-4 py-3">
      <div class="flex flex-wrap items-center gap-1">
        <template v-for="link in links" :key="link.label">
          <RouterLink
            v-if="!link.role || auth.hasRole(link.role)"
            :to="link.to"
            class="rounded-md px-3 py-2 text-sm font-medium text-slate-600 hover:bg-slate-100 dark:text-slate-300 dark:hover:bg-slate-800"
            active-class="bg-slate-900 text-white hover:bg-slate-900 dark:bg-slate-100 dark:text-slate-900"
          >
            {{ link.label }}
          </RouterLink>
        </template>
      </div>

      <div class="flex items-center gap-3">
        <span class="text-sm text-slate-500 dark:text-slate-400">
          {{ auth.user?.name }} &middot; {{ auth.roles.join(', ') }}
        </span>
        <button
          type="button"
          class="rounded-md border border-slate-300 px-3 py-1.5 text-sm font-medium text-slate-700 hover:bg-slate-100 dark:border-slate-700 dark:text-slate-200 dark:hover:bg-slate-800"
          @click="handleLogout"
        >
          Log out
        </button>
      </div>
    </nav>
  </header>
</template>
