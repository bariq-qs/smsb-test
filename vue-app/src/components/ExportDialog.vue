<script setup>
import { ref, watch } from 'vue'
import { useToast } from 'primevue/usetoast'
import Dialog from 'primevue/dialog'
import Checkbox from 'primevue/checkbox'
import Button from 'primevue/button'
import ProgressBar from 'primevue/progressbar'
import api from '@/lib/api'

const props = defineProps({
  visible: { type: Boolean, default: false },
  model: { type: String, required: true },
})
const emit = defineEmits(['update:visible'])

const toast = useToast()

const availableFields = ref([])
const selectedFields = ref([])
const status = ref(null)
const downloadUrl = ref(null)
const submitting = ref(false)
let pollTimer = null

function fieldLabel(field) {
  return field
    .split('_')
    .map((w) => w[0].toUpperCase() + w.slice(1))
    .join(' ')
}

async function loadFields() {
  const { data } = await api.get('/api/export-fields', { params: { model: props.model } })
  availableFields.value = data.fields
  selectedFields.value = [...data.fields]
}

async function startExport() {
  if (!selectedFields.value.length) return
  submitting.value = true
  status.value = 'pending'
  downloadUrl.value = null

  try {
    const { data } = await api.post('/api/exports', {
      model: props.model,
      fields: selectedFields.value,
    })
    poll(data.id)
  } catch {
    status.value = 'failed'
    submitting.value = false
    toast.add({ severity: 'error', summary: 'Unable to start export', life: 3000 })
  }
}

function poll(exportId) {
  pollTimer = setInterval(async () => {
    const { data } = await api.get(`/api/exports/${exportId}`)
    status.value = data.status
    if (data.status === 'completed') {
      clearInterval(pollTimer)
      submitting.value = false
      downloadUrl.value = data.download_url
      toast.add({ severity: 'success', summary: 'Export ready', life: 3000 })
    } else if (data.status === 'failed') {
      clearInterval(pollTimer)
      submitting.value = false
      toast.add({ severity: 'error', summary: 'Export failed', life: 3000 })
    }
  }, 1200)
}

function close() {
  emit('update:visible', false)
}

watch(
  () => props.visible,
  (visible) => {
    if (visible) {
      status.value = null
      downloadUrl.value = null
      loadFields()
    } else if (pollTimer) {
      clearInterval(pollTimer)
    }
  }
)
</script>

<template>
  <Dialog
    :visible="visible"
    @update:visible="close"
    modal
    header="Export to Excel"
    class="w-full max-w-md"
  >
    <p class="mb-3 text-sm text-slate-500">Choose which columns to include in the export.</p>
    <div class="flex flex-col gap-2">
      <div v-for="field in availableFields" :key="field" class="flex items-center gap-2">
        <Checkbox v-model="selectedFields" :inputId="`field-${field}`" :value="field" />
        <label :for="`field-${field}`" class="text-sm">{{ fieldLabel(field) }}</label>
      </div>
    </div>

    <div v-if="status" class="mt-4">
      <p class="mb-1 text-sm text-slate-500 capitalize">{{ status }}…</p>
      <ProgressBar v-if="status === 'pending' || status === 'processing'" mode="indeterminate" style="height: 6px" />
      <a
        v-if="downloadUrl"
        :href="downloadUrl"
        target="_blank"
        class="mt-2 inline-flex items-center gap-2 rounded-md bg-emerald-600 px-3 py-1.5 text-sm font-medium text-white"
      >
        <i class="pi pi-download" /> Download file
      </a>
    </div>

    <template #footer>
      <Button label="Close" severity="secondary" text @click="close" />
      <Button
        label="Export"
        icon="pi pi-file-excel"
        :loading="submitting"
        :disabled="!selectedFields.length"
        @click="startExport"
      />
    </template>
  </Dialog>
</template>
