<script setup>
import { onMounted, reactive, ref, watch } from 'vue'
import { useToast } from 'primevue/usetoast'
import { useConfirm } from 'primevue/useconfirm'
import DataTable from 'primevue/datatable'
import Column from 'primevue/column'
import Dialog from 'primevue/dialog'
import InputText from 'primevue/inputtext'
import InputNumber from 'primevue/inputnumber'
import Select from 'primevue/select'
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

const products = ref([])
const supplierOptions = ref([])
const loading = ref(false)
const search = ref('')
const sortField = ref('name')
const sortOrder = ref(1)

const dialogVisible = ref(false)
const editing = ref(null)
const form = reactive({
  supplier_id: null,
  name: '',
  sku: '',
  price: 0,
  is_active: true,
  unit: '',
  weight_kg: null,
})
const errors = ref({})

async function fetchProducts() {
  loading.value = true
  try {
    const { data } = await api.get('/api/products', {
      params: {
        search: search.value || undefined,
        sort_by: sortField.value,
        sort_dir: sortOrder.value === 1 ? 'asc' : 'desc',
        per_page: 50,
      },
    })
    products.value = data.data
  } finally {
    loading.value = false
  }
}

async function fetchSupplierOptions() {
  const { data } = await api.get('/api/supplier-options')
  supplierOptions.value = data
}

function resetForm() {
  form.supplier_id = null
  form.name = ''
  form.sku = ''
  form.price = 0
  form.is_active = true
  form.unit = ''
  form.weight_kg = null
  errors.value = {}
}

function openCreate() {
  editing.value = null
  resetForm()
  dialogVisible.value = true
}

function openEdit(product) {
  editing.value = product
  form.supplier_id = product.supplier_id
  form.name = product.name
  form.sku = product.sku
  form.price = Number(product.price)
  form.is_active = product.is_active
  form.unit = product.attributes?.unit ?? ''
  form.weight_kg = product.attributes?.weight_kg ?? null
  errors.value = {}
  dialogVisible.value = true
}

async function save() {
  errors.value = {}
  const payload = {
    supplier_id: form.supplier_id,
    name: form.name,
    sku: form.sku,
    price: form.price,
    is_active: form.is_active,
    attributes: {
      unit: form.unit || null,
      weight_kg: form.weight_kg,
    },
  }

  try {
    if (editing.value) {
      await api.put(`/api/products/${editing.value.id}`, payload)
      toast.add({ severity: 'success', summary: 'Product updated', life: 3000 })
    } else {
      await api.post('/api/products', payload)
      toast.add({ severity: 'success', summary: 'Product created', life: 3000 })
    }
    dialogVisible.value = false
    fetchProducts()
  } catch (e) {
    if (e.response?.status === 422) {
      errors.value = e.response.data.errors || {}
    } else {
      toast.add({ severity: 'error', summary: 'Something went wrong', life: 3000 })
    }
  }
}

function confirmDelete(product) {
  confirm.require({
    message: `Delete product "${product.name}"?`,
    header: 'Confirm deletion',
    icon: 'pi pi-exclamation-triangle',
    acceptClass: 'p-button-danger',
    accept: async () => {
      try {
        await api.delete(`/api/products/${product.id}`)
        toast.add({ severity: 'success', summary: 'Product deleted', life: 3000 })
        fetchProducts()
      } catch (e) {
        toast.add({
          severity: 'error',
          summary: e.response?.data?.message || 'Unable to delete product',
          life: 3000,
        })
      }
    },
  })
}

function onSort(event) {
  sortField.value = event.sortField
  sortOrder.value = event.sortOrder
  fetchProducts()
}

let searchTimeout
watch(search, () => {
  clearTimeout(searchTimeout)
  searchTimeout = setTimeout(fetchProducts, 300)
})

onMounted(() => {
  fetchProducts()
  fetchSupplierOptions()
})
</script>

<template>
  <main class="mx-auto max-w-6xl px-4 py-8">
    <div class="flex items-center justify-between">
      <h1 class="text-2xl font-bold text-slate-900 dark:text-white">Products</h1>
      <div class="flex gap-2">
        <Button label="Import" icon="pi pi-upload" severity="secondary" outlined @click="importDialogVisible = true" />
        <Button label="Export" icon="pi pi-download" severity="secondary" outlined @click="exportDialogVisible = true" />
        <Button label="New Product" icon="pi pi-plus" @click="openCreate" />
      </div>
    </div>

    <div class="mt-6">
      <IconField>
        <InputIcon class="pi pi-search" />
        <InputText v-model="search" placeholder="Search products…" class="w-72" />
      </IconField>
    </div>

    <DataTable
      :value="products"
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
      <Column field="sku" header="SKU" />
      <Column header="Supplier">
        <template #body="{ data }">{{ data.supplier?.name }}</template>
      </Column>
      <Column field="price" header="Price" sortable>
        <template #body="{ data }">{{ Number(data.price).toLocaleString(undefined, { style: 'currency', currency: 'USD' }) }}</template>
      </Column>
      <Column header="Status" style="width: 8rem">
        <template #body="{ data }">
          <Tag :severity="data.is_active ? 'success' : 'danger'" :value="data.is_active ? 'Active' : 'Inactive'" />
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

    <Dialog
      v-model:visible="dialogVisible"
      modal
      :header="editing ? 'Edit Product' : 'New Product'"
      class="w-full max-w-lg"
    >
      <div class="flex flex-col gap-4">
        <div>
          <label for="product-supplier" class="mb-1 block text-sm font-medium">Supplier</label>
          <Select
            id="product-supplier"
            v-model="form.supplier_id"
            :options="supplierOptions"
            optionLabel="name"
            optionValue="id"
            filter
            class="w-full"
            placeholder="Select a supplier"
            :invalid="!!errors.supplier_id"
          />
          <small v-if="errors.supplier_id" class="text-red-500">{{ errors.supplier_id[0] }}</small>
        </div>
        <div>
          <label for="product-name" class="mb-1 block text-sm font-medium">Name</label>
          <InputText id="product-name" v-model="form.name" class="w-full" :invalid="!!errors.name" />
          <small v-if="errors.name" class="text-red-500">{{ errors.name[0] }}</small>
        </div>
        <div class="grid grid-cols-2 gap-4">
          <div>
            <label for="product-sku" class="mb-1 block text-sm font-medium">SKU</label>
            <InputText id="product-sku" v-model="form.sku" class="w-full" :invalid="!!errors.sku" />
            <small v-if="errors.sku" class="text-red-500">{{ errors.sku[0] }}</small>
          </div>
          <div>
            <label for="product-price" class="mb-1 block text-sm font-medium">Price</label>
            <InputNumber
              id="product-price"
              v-model="form.price"
              mode="currency"
              currency="USD"
              class="w-full"
              :invalid="!!errors.price"
            />
            <small v-if="errors.price" class="text-red-500">{{ errors.price[0] }}</small>
          </div>
        </div>
        <div class="grid grid-cols-2 gap-4">
          <div>
            <label for="product-unit" class="mb-1 block text-sm font-medium">Unit</label>
            <InputText id="product-unit" v-model="form.unit" class="w-full" placeholder="pcs, box, kg…" />
          </div>
          <div>
            <label for="product-weight" class="mb-1 block text-sm font-medium">Weight (kg)</label>
            <InputNumber id="product-weight" v-model="form.weight_kg" class="w-full" :minFractionDigits="1" />
          </div>
        </div>
        <div class="flex items-center gap-2">
          <ToggleSwitch v-model="form.is_active" inputId="product-active" />
          <label for="product-active" class="text-sm font-medium">Active</label>
        </div>
      </div>

      <template v-if="editing">
        <Divider />
        <AuditTrail :audit-url="`/api/products/${editing.id}/audits`" />
      </template>

      <template #footer>
        <Button label="Cancel" severity="secondary" text @click="dialogVisible = false" />
        <Button label="Save" @click="save" />
      </template>
    </Dialog>

    <ExportDialog v-model:visible="exportDialogVisible" model="products" />
    <ImportDialog v-model:visible="importDialogVisible" model="products" @imported="fetchProducts" />
  </main>
</template>
