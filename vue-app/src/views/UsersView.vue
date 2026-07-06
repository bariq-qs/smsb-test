<script setup>
import { onMounted, reactive, ref, watch } from 'vue'
import { useToast } from 'primevue/usetoast'
import { useConfirm } from 'primevue/useconfirm'
import DataTable from 'primevue/datatable'
import Column from 'primevue/column'
import Dialog from 'primevue/dialog'
import InputText from 'primevue/inputtext'
import Password from 'primevue/password'
import Select from 'primevue/select'
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

const users = ref([])
const roleOptions = ref([])
const loading = ref(false)
const search = ref('')
const sortField = ref('created_at')
const sortOrder = ref(-1)

const dialogVisible = ref(false)
const editing = ref(null)
const form = reactive({ name: '', email: '', password: '', role: null })
const errors = ref({})

async function fetchUsers() {
  loading.value = true
  try {
    const { data } = await api.get('/api/users', {
      params: {
        search: search.value || undefined,
        sort_by: sortField.value,
        sort_dir: sortOrder.value === 1 ? 'asc' : 'desc',
        per_page: 50,
      },
    })
    users.value = data.data
  } finally {
    loading.value = false
  }
}

async function fetchRoleOptions() {
  const { data } = await api.get('/api/role-options')
  roleOptions.value = data
}

function openCreate() {
  editing.value = null
  form.name = ''
  form.email = ''
  form.password = ''
  form.role = null
  errors.value = {}
  dialogVisible.value = true
}

function openEdit(user) {
  editing.value = user
  form.name = user.name
  form.email = user.email
  form.password = ''
  form.role = user.roles[0]?.name ?? null
  errors.value = {}
  dialogVisible.value = true
}

async function save() {
  errors.value = {}
  const payload = { ...form }
  if (editing.value && !payload.password) delete payload.password

  try {
    if (editing.value) {
      await api.put(`/api/users/${editing.value.id}`, payload)
      toast.add({ severity: 'success', summary: 'User updated', life: 3000 })
    } else {
      await api.post('/api/users', payload)
      toast.add({ severity: 'success', summary: 'User created', life: 3000 })
    }
    dialogVisible.value = false
    fetchUsers()
  } catch (e) {
    if (e.response?.status === 422) {
      errors.value = e.response.data.errors || {}
    } else {
      toast.add({ severity: 'error', summary: 'Something went wrong', life: 3000 })
    }
  }
}

function confirmDelete(user) {
  confirm.require({
    message: `Delete user "${user.name}"?`,
    header: 'Confirm deletion',
    icon: 'pi pi-exclamation-triangle',
    acceptClass: 'p-button-danger',
    accept: async () => {
      try {
        await api.delete(`/api/users/${user.id}`)
        toast.add({ severity: 'success', summary: 'User deleted', life: 3000 })
        fetchUsers()
      } catch (e) {
        toast.add({
          severity: 'error',
          summary: e.response?.data?.message || 'Unable to delete user',
          life: 3000,
        })
      }
    },
  })
}

function onSort(event) {
  sortField.value = event.sortField
  sortOrder.value = event.sortOrder
  fetchUsers()
}

let searchTimeout
watch(search, () => {
  clearTimeout(searchTimeout)
  searchTimeout = setTimeout(fetchUsers, 300)
})

onMounted(() => {
  fetchUsers()
  fetchRoleOptions()
})
</script>

<template>
  <main class="mx-auto max-w-5xl px-4 py-8">
    <div class="flex items-center justify-between">
      <h1 class="text-2xl font-bold text-slate-900 dark:text-white">Users</h1>
      <div class="flex gap-2">
        <Button label="Import" icon="pi pi-upload" severity="secondary" outlined @click="importDialogVisible = true" />
        <Button label="Export" icon="pi pi-download" severity="secondary" outlined @click="exportDialogVisible = true" />
        <Button label="New User" icon="pi pi-plus" @click="openCreate" />
      </div>
    </div>

    <div class="mt-6">
      <IconField>
        <InputIcon class="pi pi-search" />
        <InputText v-model="search" placeholder="Search users…" class="w-72" />
      </IconField>
    </div>

    <DataTable
      :value="users"
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
      <Column field="email" header="Email" sortable />
      <Column header="Role">
        <template #body="{ data }">
          {{ data.roles.map((r) => r.name).join(', ') || '—' }}
        </template>
      </Column>
      <Column header="" style="width: 8rem">
        <template #body="{ data }">
          <div class="flex gap-2">
            <Button icon="pi pi-pencil" severity="secondary" text @click="openEdit(data)" />
            <Button icon="pi pi-trash" severity="danger" text @click="confirmDelete(data)" />
          </div>
        </template>
      </Column>
    </DataTable>

    <Dialog v-model:visible="dialogVisible" modal :header="editing ? 'Edit User' : 'New User'" class="w-full max-w-md">
      <div class="flex flex-col gap-4">
        <div>
          <label for="user-name" class="mb-1 block text-sm font-medium">Name</label>
          <InputText id="user-name" v-model="form.name" class="w-full" :invalid="!!errors.name" />
          <small v-if="errors.name" class="text-red-500">{{ errors.name[0] }}</small>
        </div>
        <div>
          <label for="user-email" class="mb-1 block text-sm font-medium">Email</label>
          <InputText id="user-email" v-model="form.email" class="w-full" :invalid="!!errors.email" />
          <small v-if="errors.email" class="text-red-500">{{ errors.email[0] }}</small>
        </div>
        <div>
          <label for="user-password" class="mb-1 block text-sm font-medium">
            Password {{ editing ? '(leave blank to keep current)' : '' }}
          </label>
          <Password
            id="user-password"
            v-model="form.password"
            class="w-full"
            inputClass="w-full"
            :feedback="false"
            toggleMask
            :invalid="!!errors.password"
          />
          <small v-if="errors.password" class="text-red-500">{{ errors.password[0] }}</small>
        </div>
        <div>
          <label for="user-role" class="mb-1 block text-sm font-medium">Role</label>
          <Select
            id="user-role"
            v-model="form.role"
            :options="roleOptions"
            optionLabel="name"
            optionValue="name"
            class="w-full"
            placeholder="Select a role"
            :invalid="!!errors.role"
          />
          <small v-if="errors.role" class="text-red-500">{{ errors.role[0] }}</small>
        </div>
      </div>
      <template #footer>
        <Button label="Cancel" severity="secondary" text @click="dialogVisible = false" />
        <Button label="Save" @click="save" />
      </template>
    </Dialog>

    <ExportDialog v-model:visible="exportDialogVisible" model="users" />
    <ImportDialog v-model:visible="importDialogVisible" model="users" @imported="fetchUsers" />
  </main>
</template>
