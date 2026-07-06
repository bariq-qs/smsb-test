<script setup>
import { onMounted, ref } from 'vue'
import { useAuthStore } from '@/stores/auth'
import api from '@/lib/api'

const auth = useAuthStore()
const stats = ref(null)
const loading = ref(true)

onMounted(async () => {
  try {
    const { data } = await api.get('/api/dashboard/stats')
    stats.value = data
  } catch {
    stats.value = null
  } finally {
    loading.value = false
  }
})

const cards = [
  { key: 'suppliers', label: 'Suppliers' },
  { key: 'products', label: 'Products' },
  { key: 'purchase_orders', label: 'Purchase Orders' },
  { key: 'users', label: 'Users' },
]
</script>

<template>
  <main class="mx-auto max-w-6xl px-4 py-8">
    <h1 class="text-2xl font-bold text-slate-900 dark:text-white">
      Welcome, {{ auth.user?.name }}
    </h1>
    <p class="mt-1 text-sm text-slate-500 dark:text-slate-400">
      Signed in as {{ auth.roles.join(', ') }}
    </p>

    <div class="mt-8 grid grid-cols-2 gap-4 sm:grid-cols-4">
      <div
        v-for="card in cards"
        :key="card.key"
        class="rounded-lg border border-slate-200 bg-white p-5 dark:border-slate-800 dark:bg-slate-900"
      >
        <p class="text-sm text-slate-500 dark:text-slate-400">{{ card.label }}</p>
        <p class="mt-2 text-3xl font-semibold text-slate-900 dark:text-white">
          <span v-if="loading">…</span>
          <span v-else>{{ stats?.[card.key] ?? '—' }}</span>
        </p>
      </div>
    </div>
  </main>
</template>
