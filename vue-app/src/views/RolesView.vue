<script setup>
import { onMounted, reactive, ref, watch } from 'vue'
import { useToast } from 'primevue/usetoast'
import { useConfirm } from 'primevue/useconfirm'
import DataTable from 'primevue/datatable'
import Column from 'primevue/column'
import Dialog from 'primevue/dialog'
import InputText from 'primevue/inputtext'
import MultiSelect from 'primevue/multiselect'
import Button from 'primevue/button'
import IconField from 'primevue/iconfield'
import InputIcon from 'primevue/inputicon'
import api from '@/lib/api'
import ExportDialog from '@/components/ExportDialog.vue'
import ImportDialog from '@/components/ImportDialog.vue'

const toast = useToast()
const confirm = useConfirm()

const exportDialogVisible = ref(false)
const importDialogVisible = ref(false)

const roles = ref([])
const permissionOptions = ref([])
const loading = ref(false)
const search = ref('')
const sortField = ref('name')
const sortOrder = ref(1)

const dialogVisible = ref(false)
const editing = ref(null)
const form = reactive({ name: '', permissions: [] })
const errors = ref({})

async function fetchRoles() {
  loading.value = true
  try {
    const { data } = await api.get('/api/roles', {
      params: {
        search: search.value || undefined,
        sort_by: sortField.value,
        sort_dir: sortOrder.value === 1 ? 'asc' : 'desc',
        per_page: 50,
      },
    })
    roles.value = data.data
  } finally {
    loading.value = false
  }
}

async function fetchPermissionOptions() {
  const { data } = await api.get('/api/permission-options')
  permissionOptions.value = data
}

function openCreate() {
  editing.value = null
  form.name = ''
  form.permissions = []
  errors.value = {}
  dialogVisible.value = true
}

function openEdit(role) {
  editing.value = role
  form.name = role.name
  form.permissions = role.permissions.map((p) => p.name)
  errors.value = {}
  dialogVisible.value = true
}

async function save() {
  errors.value = {}
  try {
    if (editing.value) {
      await api.put(`/api/roles/${editing.value.id}`, form)
      toast.add({ severity: 'success', summary: 'Role updated', life: 3000 })
    } else {
      await api.post('/api/roles', form)
      toast.add({ severity: 'success', summary: 'Role created', life: 3000 })
    }
    dialogVisible.value = false
    fetchRoles()
  } catch (e) {
    if (e.response?.status === 422) {
      errors.value = e.response.data.errors || {}
    } else {
      toast.add({ severity: 'error', summary: 'Something went wrong', life: 3000 })
    }
  }
}

function confirmDelete(role) {
  confirm.require({
    message: `Delete role "${role.name}"?`,
    header: 'Confirm deletion',
    icon: 'pi pi-exclamation-triangle',
    acceptClass: 'p-button-danger',
    accept: async () => {
      try {
        await api.delete(`/api/roles/${role.id}`)
        toast.add({ severity: 'success', summary: 'Role deleted', life: 3000 })
        fetchRoles()
      } catch (e) {
        toast.add({
          severity: 'error',
          summary: e.response?.data?.message || 'Unable to delete role',
          life: 3000,
        })
      }
    },
  })
}

function onSort(event) {
  sortField.value = event.sortField
  sortOrder.value = event.sortOrder
  fetchRoles()
}

let searchTimeout
watch(search, () => {
  clearTimeout(searchTimeout)
  searchTimeout = setTimeout(fetchRoles, 300)
})

onMounted(() => {
  fetchRoles()
  fetchPermissionOptions()
})
</script>

<template>
  <main class="mx-auto max-w-5xl px-4 py-8">
    <div class="flex items-center justify-between">
      <h1 class="text-2xl font-bold text-slate-900 dark:text-white">Roles</h1>
      <div class="flex gap-2">
        <Button label="Import" icon="pi pi-upload" severity="secondary" outlined @click="importDialogVisible = true" />
        <Button label="Export" icon="pi pi-download" severity="secondary" outlined @click="exportDialogVisible = true" />
        <Button label="New Role" icon="pi pi-plus" @click="openCreate" />
      </div>
    </div>

    <div class="mt-6">
      <IconField>
        <InputIcon class="pi pi-search" />
        <InputText v-model="search" placeholder="Search roles…" class="w-72" />
      </IconField>
    </div>

    <DataTable
      :value="roles"
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
      <Column header="Permissions">
        <template #body="{ data }">
          <span class="text-sm text-slate-500">
            {{ data.permissions.map((p) => p.name).join(', ') || '—' }}
          </span>
        </template>
      </Column>
      <Column field="users_count" header="Users" sortable style="width: 6rem" />
      <Column header="" style="width: 8rem">
        <template #body="{ data }">
          <div class="flex gap-2">
            <Button icon="pi pi-pencil" severity="secondary" text @click="openEdit(data)" />
            <Button icon="pi pi-trash" severity="danger" text @click="confirmDelete(data)" />
          </div>
        </template>
      </Column>
    </DataTable>

    <Dialog v-model:visible="dialogVisible" modal :header="editing ? 'Edit Role' : 'New Role'" class="w-full max-w-md">
      <div class="flex flex-col gap-4">
        <div>
          <label for="role-name" class="mb-1 block text-sm font-medium">Name</label>
          <InputText id="role-name" v-model="form.name" class="w-full" :invalid="!!errors.name" />
          <small v-if="errors.name" class="text-red-500">{{ errors.name[0] }}</small>
        </div>
        <div>
          <label for="role-permissions" class="mb-1 block text-sm font-medium">Permissions</label>
          <MultiSelect
            id="role-permissions"
            v-model="form.permissions"
            :options="permissionOptions"
            optionLabel="name"
            optionValue="name"
            display="chip"
            class="w-full"
            placeholder="Select permissions"
          />
        </div>
      </div>
      <template #footer>
        <Button label="Cancel" severity="secondary" text @click="dialogVisible = false" />
        <Button label="Save" @click="save" />
      </template>
    </Dialog>

    <ExportDialog v-model:visible="exportDialogVisible" model="roles" />
    <ImportDialog v-model:visible="importDialogVisible" model="roles" @imported="fetchRoles" />
  </main>
</template>
