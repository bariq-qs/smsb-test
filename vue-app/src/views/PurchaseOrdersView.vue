<script setup>
import { computed, onMounted, reactive, ref, watch } from 'vue'
import { useToast } from 'primevue/usetoast'
import { useConfirm } from 'primevue/useconfirm'
import DataTable from 'primevue/datatable'
import Column from 'primevue/column'
import Dialog from 'primevue/dialog'
import InputText from 'primevue/inputtext'
import InputNumber from 'primevue/inputnumber'
import Textarea from 'primevue/textarea'
import Select from 'primevue/select'
import DatePicker from 'primevue/datepicker'
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

const STATUS_OPTIONS = ['draft', 'submitted', 'approved', 'rejected', 'completed']

const purchaseOrders = ref([])
const supplierOptions = ref([])
const productOptions = ref([])
const loading = ref(false)
const search = ref('')
const statusFilter = ref(null)
const sortField = ref('order_date')
const sortOrder = ref(-1)

const dialogVisible = ref(false)
const editing = ref(null)
const attachmentFile = ref(null)
const form = reactive({
  supplier_id: null,
  po_number: '',
  order_date: new Date(),
  status: 'draft',
  is_urgent: false,
  notes: '',
})
const errors = ref({})

const newItem = reactive({ product_id: null, quantity: 1 })
const itemsTotal = computed(() =>
  (editing.value?.items ?? []).reduce((sum, item) => sum + Number(item.subtotal), 0)
)

async function fetchPurchaseOrders() {
  loading.value = true
  try {
    const { data } = await api.get('/api/purchase-orders', {
      params: {
        search: search.value || undefined,
        status: statusFilter.value || undefined,
        sort_by: sortField.value,
        sort_dir: sortOrder.value === 1 ? 'asc' : 'desc',
        per_page: 50,
      },
    })
    purchaseOrders.value = data.data
  } finally {
    loading.value = false
  }
}

async function fetchOptions() {
  const [suppliers, products] = await Promise.all([
    api.get('/api/supplier-options'),
    api.get('/api/product-options'),
  ])
  supplierOptions.value = suppliers.data
  productOptions.value = products.data
}

function resetForm() {
  form.supplier_id = null
  form.po_number = ''
  form.order_date = new Date()
  form.status = 'draft'
  form.is_urgent = false
  form.notes = ''
  attachmentFile.value = null
  errors.value = {}
}

function openCreate() {
  editing.value = null
  resetForm()
  dialogVisible.value = true
}

async function openEdit(po) {
  const { data } = await api.get(`/api/purchase-orders/${po.id}`)
  editing.value = data
  form.supplier_id = data.supplier_id
  form.po_number = data.po_number
  form.order_date = new Date(data.order_date)
  form.status = data.status
  form.is_urgent = data.is_urgent
  form.notes = data.notes?.remarks ?? ''
  attachmentFile.value = null
  errors.value = {}
  dialogVisible.value = true
}

function onFileChange(event) {
  attachmentFile.value = event.target.files[0] ?? null
}

function buildFormData() {
  const formData = new FormData()
  formData.append('supplier_id', form.supplier_id ?? '')
  formData.append('po_number', form.po_number)
  formData.append('order_date', form.order_date.toISOString())
  formData.append('status', form.status)
  formData.append('is_urgent', form.is_urgent ? '1' : '0')
  if (form.notes) formData.append('notes', form.notes)
  if (attachmentFile.value) formData.append('attachment', attachmentFile.value)
  return formData
}

async function save() {
  errors.value = {}
  try {
    if (editing.value) {
      const formData = buildFormData()
      formData.append('_method', 'PUT')
      const { data } = await api.post(`/api/purchase-orders/${editing.value.id}`, formData)
      toast.add({ severity: 'success', summary: 'Purchase order updated', life: 3000 })
      editing.value = { ...editing.value, ...data }
      attachmentFile.value = null
    } else {
      const { data } = await api.post('/api/purchase-orders', buildFormData())
      toast.add({ severity: 'success', summary: 'Purchase order created — add items below', life: 4000 })
      const { data: full } = await api.get(`/api/purchase-orders/${data.id}`)
      editing.value = full
    }
    fetchPurchaseOrders()
  } catch (e) {
    if (e.response?.status === 422) {
      errors.value = e.response.data.errors || {}
    } else {
      toast.add({ severity: 'error', summary: 'Something went wrong', life: 3000 })
    }
  }
}

async function addItem() {
  if (!newItem.product_id || !newItem.quantity) return
  try {
    await api.post(`/api/purchase-orders/${editing.value.id}/items`, {
      product_id: newItem.product_id,
      quantity: newItem.quantity,
    })
    const { data } = await api.get(`/api/purchase-orders/${editing.value.id}`)
    editing.value = data
    newItem.product_id = null
    newItem.quantity = 1
    toast.add({ severity: 'success', summary: 'Item added', life: 2000 })
  } catch (e) {
    toast.add({ severity: 'error', summary: e.response?.data?.message || 'Unable to add item', life: 3000 })
  }
}

async function removeItem(item) {
  try {
    await api.delete(`/api/purchase-orders/${editing.value.id}/items/${item.id}`)
    const { data } = await api.get(`/api/purchase-orders/${editing.value.id}`)
    editing.value = data
  } catch {
    toast.add({ severity: 'error', summary: 'Unable to remove item', life: 3000 })
  }
}

function confirmDelete(po) {
  confirm.require({
    message: `Delete purchase order "${po.po_number}"?`,
    header: 'Confirm deletion',
    icon: 'pi pi-exclamation-triangle',
    acceptClass: 'p-button-danger',
    accept: async () => {
      try {
        await api.delete(`/api/purchase-orders/${po.id}`)
        toast.add({ severity: 'success', summary: 'Purchase order deleted', life: 3000 })
        fetchPurchaseOrders()
      } catch {
        toast.add({ severity: 'error', summary: 'Unable to delete purchase order', life: 3000 })
      }
    },
  })
}

function onSort(event) {
  sortField.value = event.sortField
  sortOrder.value = event.sortOrder
  fetchPurchaseOrders()
}

let searchTimeout
watch([search, statusFilter], () => {
  clearTimeout(searchTimeout)
  searchTimeout = setTimeout(fetchPurchaseOrders, 300)
})

onMounted(() => {
  fetchPurchaseOrders()
  fetchOptions()
})
</script>

<template>
  <main class="mx-auto max-w-6xl px-4 py-8">
    <div class="flex items-center justify-between">
      <h1 class="text-2xl font-bold text-slate-900 dark:text-white">Purchase Orders</h1>
      <div class="flex gap-2">
        <Button label="Import" icon="pi pi-upload" severity="secondary" outlined @click="importDialogVisible = true" />
        <Button label="Export" icon="pi pi-download" severity="secondary" outlined @click="exportDialogVisible = true" />
        <Button label="New Purchase Order" icon="pi pi-plus" @click="openCreate" />
      </div>
    </div>

    <div class="mt-6 flex flex-wrap items-center gap-3">
      <IconField>
        <InputIcon class="pi pi-search" />
        <InputText v-model="search" placeholder="Search PO number…" class="w-72" />
      </IconField>
      <Select
        v-model="statusFilter"
        :options="STATUS_OPTIONS"
        showClear
        placeholder="Filter by status"
        aria-label="Filter by status"
        class="w-52"
      />
    </div>

    <DataTable
      :value="purchaseOrders"
      :loading="loading"
      class="mt-4"
      sortMode="single"
      :sortField="sortField"
      :sortOrder="sortOrder"
      @sort="onSort"
      dataKey="id"
      stripedRows
    >
      <Column field="po_number" header="PO Number" sortable />
      <Column header="Supplier">
        <template #body="{ data }">{{ data.supplier?.name }}</template>
      </Column>
      <Column field="order_date" header="Order Date" sortable>
        <template #body="{ data }">{{ new Date(data.order_date).toLocaleDateString() }}</template>
      </Column>
      <Column header="Status">
        <template #body="{ data }">
          <Tag :value="data.status" />
          <i v-if="data.is_urgent" class="pi pi-bolt ml-1 text-amber-500" title="Urgent" />
        </template>
      </Column>
      <Column header="Items" style="width: 6rem">
        <template #body="{ data }">{{ data.items_count }}</template>
      </Column>
      <Column header="Total" style="width: 9rem">
        <template #body="{ data }">
          {{ Number(data.items_sum_subtotal ?? 0).toLocaleString(undefined, { style: 'currency', currency: 'USD' }) }}
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
      :header="editing ? `Edit ${editing.po_number}` : 'New Purchase Order'"
      class="w-full max-w-3xl"
    >
      <div class="grid grid-cols-2 gap-4">
        <div>
          <label for="po-supplier" class="mb-1 block text-sm font-medium">Supplier</label>
          <Select
            id="po-supplier"
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
          <label for="po-number" class="mb-1 block text-sm font-medium">PO Number</label>
          <InputText id="po-number" v-model="form.po_number" class="w-full" :invalid="!!errors.po_number" />
          <small v-if="errors.po_number" class="text-red-500">{{ errors.po_number[0] }}</small>
        </div>
        <div>
          <label for="po-date" class="mb-1 block text-sm font-medium">Order Date</label>
          <DatePicker id="po-date" v-model="form.order_date" class="w-full" showIcon dateFormat="yy-mm-dd" />
        </div>
        <div>
          <label for="po-status" class="mb-1 block text-sm font-medium">Status</label>
          <Select id="po-status" v-model="form.status" :options="STATUS_OPTIONS" class="w-full" />
        </div>
        <div class="col-span-2">
          <label for="po-notes" class="mb-1 block text-sm font-medium">Notes</label>
          <Textarea id="po-notes" v-model="form.notes" class="w-full" rows="2" autoResize />
        </div>
        <div class="col-span-2">
          <label for="po-attachment" class="mb-1 block text-sm font-medium">
            Attachment (PDF, 100–500 KB)
          </label>
          <input
            id="po-attachment"
            type="file"
            accept="application/pdf"
            class="block w-full text-sm text-slate-600 file:mr-3 file:rounded-md file:border-0 file:bg-slate-900 file:px-3 file:py-1.5 file:text-sm file:font-medium file:text-white dark:text-slate-300"
            @change="onFileChange"
          />
          <small v-if="errors.attachment" class="text-red-500">{{ errors.attachment[0] }}</small>
          <p v-if="editing?.attachment_path && !attachmentFile" class="mt-1 text-xs text-slate-400">
            Current file:
            <a :href="`http://localhost:8000/storage/${editing.attachment_path}`" target="_blank" class="underline">
              view
            </a>
          </p>
        </div>
        <div class="col-span-2 flex items-center gap-2">
          <ToggleSwitch v-model="form.is_urgent" inputId="po-urgent" />
          <label for="po-urgent" class="text-sm font-medium">Urgent</label>
        </div>
      </div>

      <template v-if="editing">
        <Divider />
        <h3 class="mb-3 text-sm font-semibold uppercase tracking-wide text-slate-500 dark:text-slate-400">
          Line items
        </h3>
        <table class="w-full text-sm">
          <thead>
            <tr class="border-b border-slate-200 text-left text-slate-500 dark:border-slate-700">
              <th class="py-1">Product</th>
              <th class="py-1">Qty</th>
              <th class="py-1">Unit Price</th>
              <th class="py-1">Subtotal</th>
              <th class="py-1"></th>
            </tr>
          </thead>
          <tbody>
            <tr v-for="item in editing.items" :key="item.id" class="border-b border-slate-100 dark:border-slate-800">
              <td class="py-1.5">{{ item.product_name_snapshot }}</td>
              <td class="py-1.5">{{ item.quantity }}</td>
              <td class="py-1.5">{{ Number(item.unit_price_snapshot).toLocaleString(undefined, { style: 'currency', currency: 'USD' }) }}</td>
              <td class="py-1.5">{{ Number(item.subtotal).toLocaleString(undefined, { style: 'currency', currency: 'USD' }) }}</td>
              <td class="py-1.5 text-right">
                <Button icon="pi pi-trash" severity="danger" text size="small" @click="removeItem(item)" />
              </td>
            </tr>
            <tr v-if="!editing.items?.length">
              <td colspan="5" class="py-3 text-center text-slate-400">No items yet</td>
            </tr>
          </tbody>
          <tfoot v-if="editing.items?.length">
            <tr>
              <td colspan="3" class="pt-2 text-right font-medium">Total</td>
              <td colspan="2" class="pt-2 font-semibold">
                {{ itemsTotal.toLocaleString(undefined, { style: 'currency', currency: 'USD' }) }}
              </td>
            </tr>
          </tfoot>
        </table>

        <div class="mt-3 flex items-end gap-3">
          <div class="flex-1">
            <label for="item-product" class="mb-1 block text-xs font-medium">Product</label>
            <Select
              id="item-product"
              v-model="newItem.product_id"
              :options="productOptions"
              optionLabel="name"
              optionValue="id"
              filter
              class="w-full"
              placeholder="Select a product"
            />
          </div>
          <div class="w-28">
            <label for="item-qty" class="mb-1 block text-xs font-medium">Qty</label>
            <InputNumber id="item-qty" v-model="newItem.quantity" :min="1" class="w-full" />
          </div>
          <Button label="Add" icon="pi pi-plus" @click="addItem" />
        </div>

        <Divider />
        <AuditTrail :audit-url="`/api/purchase-orders/${editing.id}/audits`" />
      </template>

      <template #footer>
        <Button label="Close" severity="secondary" text @click="dialogVisible = false" />
        <Button label="Save" @click="save" />
      </template>
    </Dialog>

    <ExportDialog v-model:visible="exportDialogVisible" model="purchase-orders" />
    <ImportDialog v-model:visible="importDialogVisible" model="purchase-orders" @imported="fetchPurchaseOrders" />
  </main>
</template>
