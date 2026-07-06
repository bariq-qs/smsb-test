<script setup>
import { onMounted, ref, watch } from 'vue'
import Timeline from 'primevue/timeline'
import api from '@/lib/api'

const props = defineProps({
  auditUrl: { type: String, required: true },
})

const audits = ref([])
const loading = ref(false)

const eventSeverity = {
  created: 'success',
  updated: 'info',
  deleted: 'danger',
  restored: 'warn',
}

function changedFields(audit) {
  const oldValues = audit.old_values || {}
  const newValues = audit.new_values || {}
  const fields = new Set([...Object.keys(oldValues), ...Object.keys(newValues)])
  return Array.from(fields).map((field) => ({
    field,
    from: oldValues[field],
    to: newValues[field],
  }))
}

function formatValue(value) {
  if (value === null || value === undefined) return '—'
  if (typeof value === 'object') return JSON.stringify(value)
  return String(value)
}

async function fetchAudits() {
  loading.value = true
  try {
    const { data } = await api.get(props.auditUrl)
    audits.value = data
  } catch {
    audits.value = []
  } finally {
    loading.value = false
  }
}

watch(() => props.auditUrl, fetchAudits)
onMounted(fetchAudits)

defineExpose({ refresh: fetchAudits })
</script>

<template>
  <div>
    <h3 class="mb-3 text-sm font-semibold uppercase tracking-wide text-slate-500 dark:text-slate-400">
      Audit trail
    </h3>

    <p v-if="loading" class="text-sm text-slate-400">Loading…</p>
    <p v-else-if="!audits.length" class="text-sm text-slate-400">No changes recorded yet.</p>

    <Timeline v-else :value="audits" class="w-full">
      <template #marker="{ item }">
        <span
          class="flex h-6 w-6 items-center justify-center rounded-full text-white"
          :class="{
            'bg-emerald-500': eventSeverity[item.event] === 'success',
            'bg-sky-500': eventSeverity[item.event] === 'info',
            'bg-red-500': eventSeverity[item.event] === 'danger',
            'bg-amber-500': eventSeverity[item.event] === 'warn',
          }"
        >
          <i class="pi pi-pencil text-xs" v-if="item.event === 'updated'" />
          <i class="pi pi-plus text-xs" v-else-if="item.event === 'created'" />
          <i class="pi pi-trash text-xs" v-else-if="item.event === 'deleted'" />
          <i class="pi pi-refresh text-xs" v-else />
        </span>
      </template>
      <template #content="{ item }">
        <div class="pb-4">
          <p class="text-sm font-medium text-slate-800 dark:text-slate-200">
            <span class="capitalize">{{ item.event }}</span> by {{ item.user?.name ?? 'System' }}
          </p>
          <p class="text-xs text-slate-400">{{ new Date(item.created_at).toLocaleString() }}</p>
          <ul v-if="changedFields(item).length" class="mt-2 space-y-1 text-sm">
            <li v-for="change in changedFields(item)" :key="change.field">
              <span class="font-medium text-slate-600 dark:text-slate-300">{{ change.field }}</span>:
              <span class="text-red-500 line-through">{{ formatValue(change.from) }}</span>
              →
              <span class="text-emerald-600 dark:text-emerald-400">{{ formatValue(change.to) }}</span>
            </li>
          </ul>
        </div>
      </template>
    </Timeline>
  </div>
</template>
