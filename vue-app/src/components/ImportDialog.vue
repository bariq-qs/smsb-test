<script setup>
import { ref, watch } from 'vue'
import { useToast } from 'primevue/usetoast'
import Dialog from 'primevue/dialog'
import Button from 'primevue/button'
import ProgressBar from 'primevue/progressbar'
import Message from 'primevue/message'
import api from '@/lib/api'

const props = defineProps({
  visible: { type: Boolean, default: false },
  model: { type: String, required: true },
})
const emit = defineEmits(['update:visible', 'imported'])

const toast = useToast()

const file = ref(null)
const status = ref(null)
const result = ref(null)
const submitting = ref(false)
let pollTimer = null

function onFileChange(event) {
  file.value = event.target.files[0] ?? null
}

async function startImport() {
  if (!file.value) return
  submitting.value = true
  status.value = 'pending'
  result.value = null

  const formData = new FormData()
  formData.append('model', props.model)
  formData.append('file', file.value)

  try {
    const { data } = await api.post('/api/imports', formData)
    poll(data.id)
  } catch (e) {
    status.value = 'failed'
    submitting.value = false
    toast.add({ severity: 'error', summary: e.response?.data?.message || 'Unable to start import', life: 3000 })
  }
}

function poll(importId) {
  pollTimer = setInterval(async () => {
    const { data } = await api.get(`/api/imports/${importId}`)
    status.value = data.status
    if (data.status === 'completed') {
      clearInterval(pollTimer)
      submitting.value = false
      result.value = data
      toast.add({ severity: 'success', summary: 'Import complete', life: 3000 })
      emit('imported')
    } else if (data.status === 'failed') {
      clearInterval(pollTimer)
      submitting.value = false
      result.value = data
      toast.add({ severity: 'error', summary: 'Import failed', life: 3000 })
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
      result.value = null
      file.value = null
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
    header="Import from Excel"
    class="w-full max-w-md"
  >
    <p class="mb-3 text-sm text-slate-500">
      Upload an .xlsx or .csv file. Recognized columns will be matched automatically —
      unrecognized columns are ignored.
    </p>
    <input
      type="file"
      accept=".xlsx,.xls,.csv"
      class="block w-full text-sm text-slate-600 file:mr-3 file:rounded-md file:border-0 file:bg-slate-900 file:px-3 file:py-1.5 file:text-sm file:font-medium file:text-white dark:text-slate-300"
      @change="onFileChange"
    />

    <div v-if="status" class="mt-4">
      <p class="mb-1 text-sm text-slate-500 capitalize">{{ status }}…</p>
      <ProgressBar v-if="status === 'pending' || status === 'processing'" mode="indeterminate" style="height: 6px" />

      <div v-if="result" class="mt-3 space-y-2">
        <Message severity="success" :closable="false">
          Created {{ result.created_count }}, updated {{ result.updated_count }}.
        </Message>
        <Message v-if="result.errors?.length" severity="warn" :closable="false">
          <ul class="list-disc pl-4 text-xs">
            <li v-for="(error, i) in result.errors" :key="i">{{ error }}</li>
          </ul>
        </Message>
      </div>
    </div>

    <template #footer>
      <Button label="Close" severity="secondary" text @click="close" />
      <Button label="Import" icon="pi pi-upload" :loading="submitting" :disabled="!file" @click="startImport" />
    </template>
  </Dialog>
</template>
