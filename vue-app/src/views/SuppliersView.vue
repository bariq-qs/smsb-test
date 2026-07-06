<script setup>
import { onMounted, reactive, ref, watch } from 'vue'
import { useToast } from 'primevue/usetoast'
import { useConfirm } from 'primevue/useconfirm'
import DataTable from 'primevue/datatable'
import Column from 'primevue/column'
import Dialog from 'primevue/dialog'
import InputText from 'primevue/inputtext'
import ToggleSwitch from 'primevue/toggleswitch'
import Tag from 'primevue/tag'
import Button from 'primevue/button'
import IconField from 'primevue/iconfield'
import InputIcon from 'primevue/inputicon'
import Divider from 'primevue/divider'
import api from '@/lib/api'
import AuditTrail from '@/components/AuditTrail.vue'
import ExportDialog from '@/components/ExportDialog.vue'
import ImportDialog from '@/components/ImportDialog.vue'

const toast = useToast()
const confirm = useConfirm()

const exportDialogVisible = ref(false)
const importDialogVisible = ref(false)

const suppliers = ref([])
const loading = ref(false)
const search = ref('')
const sortField = ref('name')
const sortOrder = ref(1)

const dialogVisible = ref(false)
const editing = ref(null)
const form = reactive({
  name: '',
  email: '',
  phone: '',
  is_active: true,
  bank_account: '',
  contact_person: '',
})
const errors = ref({})

async function fetchSuppliers() {
  loading.value = true
  try {
    const { data } = await api.get('/api/suppliers', {
      params: {
        search: search.value || undefined,
        sort_by: sortField.value,
        sort_dir: sortOrder.value === 1 ? 'asc' : 'desc',
        per_page: 50,
      },
    })
    suppliers.value = data.data
  } finally {
    loading.value = false
  }
}

function resetForm() {
  form.name = ''
  form.email = ''
  form.phone = ''
  form.is_active = true
  form.bank_account = ''
  form.contact_person = ''
  errors.value = {}
}

function openCreate() {
  editing.value = null
  resetForm()
  dialogVisible.value = true
}

function openEdit(supplier) {
  editing.value = supplier
  form.name = supplier.name
  form.email = supplier.email ?? ''
  form.phone = supplier.phone ?? ''
  form.is_active = supplier.is_active
  form.bank_account = supplier.metadata?.bank_account ?? ''
  form.contact_person = supplier.metadata?.contact_person ?? ''
  errors.value = {}
  dialogVisible.value = true
}

async function save() {
  errors.value = {}
  const payload = {
    name: form.name,
    email: form.email || null,
    phone: form.phone || null,
    is_active: form.is_active,
    metadata: {
      bank_account: form.bank_account || null,
      contact_person: form.contact_person || null,
    },
  }

  try {
    if (editing.value) {
      await api.put(`/api/suppliers/${editing.value.id}`, payload)
      toast.add({ severity: 'success', summary: 'Supplier updated', life: 3000 })
    } else {
      await api.post('/api/suppliers', payload)
      toast.add({ severity: 'success', summary: 'Supplier created', life: 3000 })
    }
    dialogVisible.value = false
    fetchSuppliers()
  } catch (e) {
    if (e.response?.status === 422) {
      errors.value = e.response.data.errors || {}
    } else {
      toast.add({ severity: 'error', summary: 'Something went wrong', life: 3000 })
    }
  }
}

function confirmDelete(supplier) {
  confirm.require({
    message: `Delete supplier "${supplier.name}"?`,
    header: 'Confirm deletion',
    icon: 'pi pi-exclamation-triangle',
    acceptClass: 'p-button-danger',
    accept: async () => {
      try {
        await api.delete(`/api/suppliers/${supplier.id}`)
        toast.add({ severity: 'success', summary: 'Supplier deleted', life: 3000 })
        fetchSuppliers()
      } catch (e) {
        toast.add({
          severity: 'error',
          summary: e.response?.data?.message || 'Unable to delete supplier',
          life: 3000,
        })
      }
    },
  })
}

function onSort(event) {
  sortField.value = event.sortField
  sortOrder.value = event.sortOrder
  fetchSuppliers()
}

let searchTimeout
watch(search, () => {
  clearTimeout(searchTimeout)
  searchTimeout = setTimeout(fetchSuppliers, 300)
})

onMounted(fetchSuppliers)
</script>

<template>
  <main class="mx-auto max-w-6xl px-4 py-8">
    <div class="flex items-center justify-between">
      <h1 class="text-2xl font-bold text-slate-900 dark:text-white">Suppliers</h1>
      <div class="flex gap-2">
        <Button label="Import" icon="pi pi-upload" severity="secondary" outlined @click="importDialogVisible = true" />
        <Button label="Export" icon="pi pi-download" severity="secondary" outlined @click="exportDialogVisible = true" />
        <Button label="New Supplier" icon="pi pi-plus" @click="openCreate" />
      </div>
    </div>

    <div class="mt-6">
      <IconField>
        <InputIcon class="pi pi-search" />
        <InputText v-model="search" placeholder="Search suppliers…" class="w-72" />
      </IconField>
    </div>

    <DataTable
      :value="suppliers"
      :loading="loading"
      class="mt-4"
      sortMode="single"
      :sortField="sortField"
      :sortOrder="sortOrder"
      @sort="onSort"
      dataKey="id"
      stripedRows
    >
      <Column field="name" header="Name" sortable />
      <Column field="email" header="Email" />
      <Column field="phone" header="Phone" />
      <Column header="Status" style="width: 8rem">
        <template #body="{ data }">
          <Tag :severity="data.is_active ? 'success' : 'danger'" :value="data.is_active ? 'Active' : 'Inactive'" />
        </template>
      </Column>
      <Column field="products_count" header="Products" style="width: 7rem" />
      <Column field="purchase_orders_count" header="POs" style="width: 6rem" />
      <Column header="" style="width: 8rem">
        <template #body="{ data }">
          <div class="flex gap-2">
            <Button icon="pi pi-pencil" severity="secondary" text @click="openEdit(data)" />
            <Button icon="pi pi-trash" severity="danger" text @click="confirmDelete(data)" />
          </div>
        </template>
      </Column>
    </DataTable>

    <Dialog
      v-model:visible="dialogVisible"
      modal
      :header="editing ? 'Edit Supplier' : 'New Supplier'"
      class="w-full max-w-lg"
    >
      <div class="flex flex-col gap-4">
        <div>
          <label for="supplier-name" class="mb-1 block text-sm font-medium">Name</label>
          <InputText id="supplier-name" v-model="form.name" class="w-full" :invalid="!!errors.name" />
          <small v-if="errors.name" class="text-red-500">{{ errors.name[0] }}</small>
        </div>
        <div class="grid grid-cols-2 gap-4">
          <div>
            <label for="supplier-email" class="mb-1 block text-sm font-medium">Email</label>
            <InputText id="supplier-email" v-model="form.email" class="w-full" :invalid="!!errors.email" />
            <small v-if="errors.email" class="text-red-500">{{ errors.email[0] }}</small>
          </div>
          <div>
            <label for="supplier-phone" class="mb-1 block text-sm font-medium">Phone</label>
            <InputText id="supplier-phone" v-model="form.phone" class="w-full" />
          </div>
        </div>
        <div class="grid grid-cols-2 gap-4">
          <div>
            <label for="supplier-bank" class="mb-1 block text-sm font-medium">Bank Account</label>
            <InputText id="supplier-bank" v-model="form.bank_account" class="w-full" />
          </div>
          <div>
            <label for="supplier-contact" class="mb-1 block text-sm font-medium">Contact Person</label>
            <InputText id="supplier-contact" v-model="form.contact_person" class="w-full" />
          </div>
        </div>
        <div class="flex items-center gap-2">
          <ToggleSwitch v-model="form.is_active" inputId="supplier-active" />
          <label for="supplier-active" class="text-sm font-medium">Active</label>
        </div>
      </div>

      <template v-if="editing">
        <Divider />
        <AuditTrail :audit-url="`/api/suppliers/${editing.id}/audits`" />
      </template>

      <template #footer>
        <Button label="Cancel" severity="secondary" text @click="dialogVisible = false" />
        <Button label="Save" @click="save" />
      </template>
    </Dialog>

    <ExportDialog v-model:visible="exportDialogVisible" model="suppliers" />
    <ImportDialog v-model:visible="importDialogVisible" model="suppliers" @imported="fetchSuppliers" />
  </main>
</template>
