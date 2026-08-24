<script setup>
import { computed, ref, watch } from 'vue'
import { useRouter } from 'vue-router'
import { useMutation, useQueryClient } from '@tanstack/vue-query'
import { useVueTable, getCoreRowModel, getSortedRowModel, getFilteredRowModel, getPaginationRowModel } from '@tanstack/vue-table'
import { useDashboardStore } from '../../../stores/dashboardStore'
import { useDashboardData } from '../../../composables/useDashboardData'
import { dashboardService } from '../../../services/dashboardService'
import { useToast } from '../../../composables/useToast'
import DatePicker from '../../../components/DatePicker.vue'

const props = defineProps({
  orders: Array
})

const store = useDashboardStore()
const router = useRouter()
const queryClient = useQueryClient()
const { fmt, fmtNum, parseNum, fmtDate, fmtShort, calc, statusMeta } = useDashboardData()
const toast = useToast()

const orders = computed(() => props.orders ?? store.orders)

const viewInvoice = (o) => { store.setActiveInvoice(o); router.push('/orders/invoice/' + encodeURIComponent(o.no)) }
const openDetail = (o) => { store.setActiveInvoice(o); router.push('/orders/detail/' + encodeURIComponent(o.no)) }
const editOrder = (o) => { store.loadEditForm(o); router.push('/orders/edit/' + encodeURIComponent(o.no)) }

const rawData = computed(() => orders.value.map(o => {
  const c = calc(o); const m = statusMeta(o.status)
  return {
    no: o.no, group: o.group, dateInv: fmtDate(o.date), pax: (o.items || []).reduce((s, it) => s + (Number(it.qty) || 0), 0) || '-', total: fmt(c.grandTotal),
    totalRaw: c.grandTotal, status: o.status, statusBg: m.bg, statusColor: m.color,
    raw: o
  }
}))

const columns = [
  { accessorKey: 'group', header: 'Grup', enableSorting: true },
  { accessorKey: 'dateInv', header: 'Tanggal Invoice', enableSorting: true },
  { accessorKey: 'pax', header: 'Pax', enableSorting: false },
  { accessorKey: 'totalRaw', header: 'Total', enableSorting: true },
  { accessorKey: 'status', header: 'Status', enableSorting: true },
]

const search = ref('')
const sorting = ref([])
const pagination = ref({ pageIndex: 0, pageSize: 10 })

const table = useVueTable({
  get data() { return rawData.value },
  columns,
  getCoreRowModel: getCoreRowModel(),
  getSortedRowModel: getSortedRowModel(),
  getFilteredRowModel: getFilteredRowModel(),
  getPaginationRowModel: getPaginationRowModel(),
  state: {
    get sorting() { return sorting.value },
    get pagination() { return pagination.value },
    get globalFilter() { return search.value },
  },
  onSortingChange: (updater) => { sorting.value = typeof updater === 'function' ? updater(sorting.value) : updater },
  onPaginationChange: (updater) => { pagination.value = typeof updater === 'function' ? updater(pagination.value) : updater },
  onGlobalFilterChange: (updater) => { search.value = typeof updater === 'function' ? updater(search.value) : updater },
  globalFilterFn: (row, _columnId, filterValue) => {
    const s = String(filterValue).toLowerCase()
    return row.original.group.toLowerCase().includes(s) || row.original.no.toLowerCase().includes(s)
  },
})

const sortIcon = (col) => {
  const s = sorting.value.find(x => x.id === col)
  if (!s) return 'ph-arrows-down-up'
  return s.desc ? 'ph-arrow-down' : 'ph-arrow-up'
}

const goNew = () => router.push('/orders/new')

const deleteModal = ref(false)
const deleteTarget = ref(null)
const deleteMut = useMutation({
  mutationFn: dashboardService.deleteOrder,
  onSuccess: () => {
    queryClient.invalidateQueries({ queryKey: ['dashboard'] })
    deleteModal.value = false
    deleteTarget.value = null
    toast.success('Pesanan berhasil dihapus.')
  },
  onError: () => {
    toast.error('Gagal menghapus pesanan.')
  }
})

const confirmDelete = (o) => {
  deleteTarget.value = o
  deleteModal.value = true
}
const doDelete = () => {
  if (deleteTarget.value) {
    deleteMut.mutate(deleteTarget.value.no)
  }
}
const cancelDelete = () => {
  deleteModal.value = false
  deleteTarget.value = null
}

// ===== Pembayaran =====
const payModal = ref(false)
const payTarget = ref(null)
const payForm = ref({ paymentDate: new Date().toISOString().slice(0, 10), amount: '', comment: '' })
const payProof = ref(null)
const dragOver = ref(false)
const fileInputRef = ref(null)

const payCalc = computed(() => {
  if (!payTarget.value) return { grandTotal: 0, paid: 0, sisa: 0 }
  const c = calc(payTarget.value)
  const paid = (payTarget.value.payments || []).reduce((s, p) => s + (Number(p.amount) || 0), 0)
  return { grandTotal: c.grandTotal, paid, sisa: Math.max(0, c.grandTotal - paid) }
})

const payAmountFmt = computed(() => fmtNum(payForm.value.amount))
const onPayAmount = (e) => { payForm.value.amount = parseNum(e.target.value) }
const willLunas = computed(() => {
  const amt = Number(payForm.value.amount) || 0
  return amt > 0 && amt >= payCalc.value.sisa
})
const sisaAfterPay = computed(() => Math.max(0, payCalc.value.sisa - (Number(payForm.value.amount) || 0)))

const openPay = (o) => {
  payTarget.value = o
  payForm.value = { paymentDate: new Date().toISOString().slice(0, 10), amount: '', comment: '' }
  payProof.value = null
  dragOver.value = false
  payModal.value = true
}
const cancelPay = () => {
  payModal.value = false
  payTarget.value = null
  payProof.value = null
  dragOver.value = false
}

const fillSisa = () => { payForm.value.amount = payCalc.value.sisa }

const onPickFile = (e) => {
  const f = e.target.files && e.target.files[0]
  if (f) payProof.value = f
  e.target.value = ''
}
const onDropFile = (e) => {
  dragOver.value = false
  const f = e.dataTransfer.files && e.dataTransfer.files[0]
  if (!f) return
  if (!['image/jpeg', 'image/png', 'application/pdf'].includes(f.type)) {
    toast.error('Format file harus JPG, PNG, atau PDF.')
    return
  }
  if (f.size > 5 * 1024 * 1024) {
    toast.error('Ukuran file maksimal 5MB.')
    return
  }
  payProof.value = f
}
const removeProof = () => { payProof.value = null }

const proofPreviewUrl = ref('')
watch(payProof, (f) => {
  if (proofPreviewUrl.value) {
    URL.revokeObjectURL(proofPreviewUrl.value)
    proofPreviewUrl.value = ''
  }
  if (f && f.type && f.type.startsWith('image/')) {
    proofPreviewUrl.value = URL.createObjectURL(f)
  }
})

const fmtBytes = (b) => b < 1024*1024 ? (b/1024).toFixed(0) + ' KB' : (b/1024/1024).toFixed(2) + ' MB'

const submitPayment = () => {
  if (!payTarget.value) return
  if (!payForm.value.paymentDate) { toast.error('Tanggal pembayaran wajib diisi.'); return }
  const amt = Number(payForm.value.amount)
  if (!amt || amt <= 0) { toast.error('Jumlah pembayaran harus lebih dari 0.'); return }

  const fd = new FormData()
  fd.append('payment_date', payForm.value.paymentDate)
  fd.append('amount', amt)
  fd.append('comment', payForm.value.comment || '')
  if (payProof.value) fd.append('proof_file', payProof.value)

  payMut.mutate({ invoiceNo: payTarget.value.no, formData: fd })
}

const payMut = useMutation({
  mutationFn: dashboardService.createOrderPayment,
  onSuccess: (res) => {
    queryClient.invalidateQueries({ queryKey: ['dashboard'] })
    cancelPay()
    if (res?.status === 'Lunas') toast.success('Pembayaran tercatat. Status pesanan kini Lunas.')
    else toast.success('Pembayaran dicatat. Sisa: Rp ' + new Intl.NumberFormat('id-ID').format(Math.round(res?.grandTotal - res?.totalPaid || 0)))
  },
  onError: () => {
    toast.error('Gagal mencatat pembayaran.')
  }
})
</script>

<template>
  <div class="p-mobile" style="padding:30px 32px;">
    <div style="background:#fff;border:1px solid #e8e9ee;border-radius:16px;overflow:hidden;display:flex;flex-direction:column;">
      <!-- header: title + search -->
      <div style="padding:18px 22px;border-bottom:1px solid #eef0f3;display:flex;align-items:center;justify-content:space-between;gap:12px;flex-wrap:wrap;">
        <h3 style="font-size:16px;font-weight:700;color:#13233f;margin:0;">Semua Pesanan <span style="color:#9aa0ad;font-weight:600;">({{ table.getFilteredRowModel().rows.length }})</span></h3>
        <div style="display:flex;align-items:center;gap:10px;">
          <div style="position:relative;">
            <i class="ph ph-magnifying-glass" style="position:absolute;left:11px;top:50%;transform:translateY(-50%);font-size:15px;color:#9aa0ad;pointer-events:none;"></i>
            <input v-model="search" placeholder="Cari grup / no. invoice..." style="padding:9px 12px 9px 34px;border:1px solid #d8dce4;border-radius:9px;font-size:13px;color:#1a2235;background:#fff;outline:none;width:220px;">
          </div>
        </div>
      </div>

      <!-- table -->
      <div class="table-scroll">
        <div class="min-w-table">
          <div class="table-header-mobile" style="display:grid;grid-template-columns:1.6fr 1fr .6fr 1fr .8fr 1.8fr;gap:12px;padding:12px 22px;background:#fafbfc;font-size:11.5px;font-weight:700;color:#9aa0ad;text-transform:uppercase;letter-spacing:.04em;">
            <span v-for="header in table.getHeaderGroups()[0].headers" :key="header.id"
              :style="{ cursor: header.column.getCanSort() ? 'pointer' : 'default' }"
              @click="header.column.getToggleSortingHandler()?.($event)">
              {{ header.column.columnDef.header }}
              <i v-if="header.column.getCanSort()" :class="['ph', sortIcon(header.column.id)]" style="font-size:12px;vertical-align:middle;margin-left:3px;"></i>
            </span>
            <span style="text-align:right;">Aksi</span>
          </div>

          <div v-for="row in table.getRowModel().rows" :key="row.id" class="table-row-mobile" style="display:grid;grid-template-columns:1.6fr 1fr .6fr 1fr .8fr 1.8fr;gap:12px;padding:15px 22px;border-top:1px solid #f1f2f5;align-items:center;">
            <div class="col-full-mobile" style="min-width:0;"><div style="font-size:14px;font-weight:700;color:#13233f;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;">{{ row.original.group }}</div><div style="font-size:11px;color:#9aa0ad;font-family:'IBM Plex Mono',monospace;">{{ row.original.no }}</div></div>
            <span class="col-half-mobile" style="font-size:13px;color:#5d6a82;font-family:'IBM Plex Mono',monospace;"><i class="ph ph-calendar" style="font-size:13px;color:#5d6a82;vertical-align:middle;padding-right:4px;"></i>{{ row.original.dateInv }}</span>
            <span class="col-auto-mobile hide-mobile" style="font-size:13.5px;color:#5d6a82;font-family:'IBM Plex Mono',monospace;">{{ row.original.pax }}</span>
            <span class="col-half-mobile" style="font-size:13.5px;font-weight:700;color:#13233f;font-family:'IBM Plex Mono',monospace;">{{ row.original.total }}</span>
            <span class="col-half-mobile"><span :style="{ color: row.original.statusColor, background: row.original.statusBg }" style="font-size:11.5px;font-weight:700;padding:5px 10px;border-radius:7px;">{{ row.original.status }}</span></span>
            <span class="col-full-mobile" style="text-align:right;display:flex;gap:6px;justify-content:flex-end;">
              <button @click="openDetail(row.original.raw)" class="tr-btn" style="background:#15294f;color:#fff;border:none;font-size:12.5px;font-weight:700;padding:8px 14px;border-radius:8px;cursor:pointer;display:inline-flex;align-items:center;gap:6px;"><i class="ph ph-file-text" style="font-size:14px;color:#c39a4d;"></i>Detail</button>
              <button @click="openPay(row.original.raw)" :disabled="row.original.status === 'Lunas'" class="tr-btn" style="background:#e6f4ec;color:#1f7a5c;border:1px solid #bfe3cf;font-size:12.5px;font-weight:700;padding:8px 14px;border-radius:8px;cursor:pointer;display:inline-flex;align-items:center;gap:6px;" :style="{ opacity: row.original.status === 'Lunas' ? 0.45 : 1, cursor: row.original.status === 'Lunas' ? 'not-allowed' : 'pointer' }"><i class="ph ph-money" style="font-size:14px;"></i>Bayar</button>
              <button @click="editOrder(row.original.raw)" class="tr-btn" style="background:#eef3fb;color:#15294f;border:1px solid #d6e1f2;font-size:12.5px;font-weight:700;padding:8px 14px;border-radius:8px;cursor:pointer;display:inline-flex;align-items:center;gap:6px;"><i class="ph ph-pencil-simple" style="font-size:14px;color:#15294f;"></i>Edit</button>
              <button @click="confirmDelete(row.original.raw)" class="tr-btn" style="background:#fdf0ed;color:#c2603a;border:1px solid #f0d0c8;font-size:12.5px;font-weight:700;padding:8px 14px;border-radius:8px;cursor:pointer;display:inline-flex;align-items:center;gap:6px;"><i class="ph ph-trash" style="font-size:14px;"></i>Hapus</button>
            </span>
          </div>

          <div v-if="table.getRowModel().rows.length === 0" style="padding:40px 22px;text-align:center;">
            <i class="ph ph-magnifying-glass" style="font-size:32px;color:#c2c8d4;display:block;margin-bottom:8px;"></i>
            <div style="font-size:14px;color:#9aa0ad;">Tidak ada pesanan yang cocok.</div>
          </div>
        </div>
      </div>

      <!-- pagination -->
      <div style="padding:14px 22px;border-top:1px solid #eef0f3;display:flex;align-items:center;justify-content:space-between;flex-wrap:wrap;gap:10px;">
        <span style="font-size:13px;color:#5d6a82;">
          Menampilkan {{ table.getRowModel().rows.length }} dari {{ table.getFilteredRowModel().rows.length }} pesanan
        </span>
        <div style="display:flex;align-items:center;gap:6px;">
          <button class="tr-btn" @click="table.previousPage()" :disabled="!table.getCanPreviousPage()" style="background:#fff;border:1px solid #d8dce4;border-radius:8px;padding:7px 12px;cursor:pointer;font-size:13px;color:#5d6a82;display:flex;align-items:center;gap:4px;" :style="{ opacity: table.getCanPreviousPage() ? 1 : 0.4 }">
            <i class="ph ph-caret-left" style="font-size:14px;"></i>
          </button>
          <template v-for="page in table.getPageCount()" :key="page">
            <button class="tr-btn" @click="table.setPageIndex(page - 1)"
              style="min-width:34px;height:34px;border-radius:8px;font-size:13px;font-weight:600;cursor:pointer;display:flex;align-items:center;justify-content:center;border:none;"
              :style="{
                background: table.getState().pagination.pageIndex === page - 1 ? '#15294f' : 'transparent',
                color: table.getState().pagination.pageIndex === page - 1 ? '#fff' : '#5d6a82'
              }">
              {{ page }}
            </button>
          </template>
          <button class="tr-btn" @click="table.nextPage()" :disabled="!table.getCanNextPage()" style="background:#fff;border:1px solid #d8dce4;border-radius:8px;padding:7px 12px;cursor:pointer;font-size:13px;color:#5d6a82;display:flex;align-items:center;gap:4px;" :style="{ opacity: table.getCanNextPage() ? 1 : 0.4 }">
            <i class="ph ph-caret-right" style="font-size:14px;"></i>
          </button>
        </div>
      </div>
    </div>

    <!-- Delete Confirmation Modal -->
    <div v-if="deleteModal" style="position:fixed;inset:0;z-index:200;display:flex;align-items:center;justify-content:center;">
      <div style="position:absolute;inset:0;background:rgba(0,0,0,.4);" @click="cancelDelete"></div>
      <div style="position:relative;background:#fff;border-radius:16px;padding:32px;max-width:420px;width:90%;box-shadow:0 20px 60px rgba(0,0,0,.2);">
        <div style="display:flex;align-items:center;gap:12px;margin-bottom:16px;">
          <div style="width:44px;height:44px;border-radius:12px;background:#fdf0ed;display:flex;align-items:center;justify-content:center;">
            <i class="ph ph-warning" style="font-size:22px;color:#c2603a;"></i>
          </div>
          <div>
            <h4 style="font-size:16px;font-weight:700;color:#13233f;margin:0;">Hapus Pesanan?</h4>
            <p style="font-size:13px;color:#8a93a5;margin:2px 0 0;">Tindakan ini tidak dapat dibatalkan.</p>
          </div>
        </div>
        <div v-if="deleteTarget" style="background:#fafbfc;border-radius:10px;padding:14px 16px;margin-bottom:20px;">
          <div style="font-size:14px;font-weight:700;color:#13233f;">{{ deleteTarget.group }}</div>
          <div style="font-size:12px;color:#9aa0ad;font-family:'IBM Plex Mono',monospace;margin-top:2px;">{{ deleteTarget.no }}</div>
        </div>
        <div style="display:flex;gap:10px;justify-content:flex-end;">
          <button @click="cancelDelete" class="tr-btn" style="background:#fff;color:#5f6b80;border:1px solid #e2e4ea;font-size:13.5px;font-weight:600;padding:10px 20px;border-radius:9px;cursor:pointer;">Batal</button>
          <button @click="doDelete" :disabled="deleteMut.isPending.value" class="tr-btn" style="background:#c2603a;color:#fff;border:none;font-size:13.5px;font-weight:700;padding:10px 20px;border-radius:9px;cursor:pointer;display:flex;align-items:center;gap:6px;">
            <i v-if="deleteMut.isPending.value" class="ph ph-circle-notch" style="font-size:16px;animation:spin 1s linear infinite;"></i>
            <i v-else class="ph ph-trash" style="font-size:15px;"></i>
            {{ deleteMut.isPending.value ? 'Menghapus...' : 'Hapus' }}
          </button>
        </div>
      </div>
    </div>

    <!-- Payment Modal -->
    <div v-if="payModal && payTarget" style="position:fixed;inset:0;z-index:200;display:flex;align-items:center;justify-content:center;padding:16px;">
      <div style="position:absolute;inset:0;background:rgba(13,27,48,.55);backdrop-filter:blur(3px);"></div>
      <div style="position:relative;background:#fff;border-radius:18px;width:100%;max-width:600px;max-height:calc(100vh - 32px);box-shadow:0 24px 70px rgba(13,27,48,.35);display:flex;flex-direction:column;overflow:hidden;">
        <!-- header -->
        <div style="background:linear-gradient(135deg,#15294f,#0d1b30);padding:15px 20px;display:flex;align-items:center;justify-content:space-between;gap:12px;flex-shrink:0;">
          <div style="display:flex;align-items:center;gap:12px;min-width:0;">
            <span style="width:38px;height:38px;border-radius:11px;background:rgba(195,154,77,.18);display:inline-flex;align-items:center;justify-content:center;flex-shrink:0;"><i class="ph ph-hand-coins" style="font-size:20px;color:#c39a4d;"></i></span>
            <div style="min-width:0;">
              <h4 style="font-size:15.5px;font-weight:800;color:#fff;margin:0;">Tambah Pembayaran</h4>
              <p style="font-size:11.5px;color:#aeb8cc;margin:2px 0 0;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;">Lunas otomatis bila total bayar mencapai grand total</p>
            </div>
          </div>
          <button @click="cancelPay" class="tr-btn" style="background:rgba(255,255,255,.1);border:none;cursor:pointer;color:#fff;padding:6px;border-radius:8px;flex-shrink:0;"><i class="ph ph-x" style="font-size:17px;"></i></button>
        </div>

        <!-- body -->
        <div style="padding:18px 22px 20px;display:flex;flex-direction:column;gap:14px;overflow-y:auto;min-height:0;flex:1 1 auto;"
          @dragover.prevent="dragOver = true"
          @dragleave.prevent="dragOver = false"
          @drop.prevent="onDropFile">
          <!-- info order -->
          <div style="border:1px solid #eef0f3;border-radius:12px;overflow:hidden;flex-shrink:0;">
            <div style="display:flex;align-items:center;justify-content:space-between;gap:10px;padding:10px 14px;border-bottom:1px solid #eef0f3;background:#fafbfc;">
              <div style="min-width:0;">
                <span style="font-size:13px;font-weight:700;color:#13233f;display:block;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;">{{ payTarget.group }}</span>
                <span style="font-size:11px;color:#9aa0ad;font-family:'IBM Plex Mono',monospace;margin-top:1px;display:block;">{{ payTarget.no }} · {{ payTarget.pic || '-' }}</span>
              </div>
              <span :style="{ color: statusMeta(payTarget.status).color, background: statusMeta(payTarget.status).bg }" style="font-size:10.5px;font-weight:700;padding:4px 10px;border-radius:7px;white-space:nowrap;flex-shrink:0;">{{ payTarget.status }}</span>
            </div>
            <div style="display:grid;grid-template-columns:repeat(3,1fr);">
              <div style="padding:9px 12px;text-align:center;">
                <div style="font-size:10px;font-weight:600;color:#9aa0ad;text-transform:uppercase;letter-spacing:.04em;margin-bottom:2px;">Grand Total</div>
                <div style="font-size:13px;font-weight:800;color:#13233f;font-family:'IBM Plex Mono',monospace;">{{ fmt(payCalc.grandTotal) }}</div>
              </div>
              <div style="padding:9px 12px;text-align:center;border-left:1px solid #eef0f3;border-right:1px solid #eef0f3;">
                <div style="font-size:10px;font-weight:600;color:#9aa0ad;text-transform:uppercase;letter-spacing:.04em;margin-bottom:2px;">Sudah Dibayar</div>
                <div style="font-size:13px;font-weight:800;color:#1f7a5c;font-family:'IBM Plex Mono',monospace;">{{ fmt(payCalc.paid) }}</div>
              </div>
              <div style="padding:9px 12px;text-align:center;">
                <div style="font-size:10px;font-weight:600;color:#9aa0ad;text-transform:uppercase;letter-spacing:.04em;margin-bottom:2px;">Sisa Tagihan</div>
                <div style="font-size:13px;font-weight:800;color:#c2603a;font-family:'IBM Plex Mono',monospace;">{{ fmt(payCalc.sisa) }}</div>
              </div>
            </div>
          </div>

          <!-- form -->
          <div class="grid-cols-1-mobile" style="display:grid;grid-template-columns:1fr 1.2fr;gap:14px;">
            <div>
              <label style="display:block;font-size:12px;font-weight:600;color:#5f6b80;margin-bottom:6px;">Tanggal Pembayaran <span style="color:#c2603a;">*</span></label>
              <DatePicker v-model="payForm.paymentDate" placeholder="Pilih tanggal..." />
            </div>
            <div>
              <label style="display:block;font-size:12px;font-weight:600;color:#5f6b80;margin-bottom:6px;">Jumlah Pembayaran <span style="color:#c2603a;">*</span></label>
              <div style="position:relative;">
                <span style="position:absolute;left:13px;top:50%;transform:translateY(-50%);font-size:12.5px;font-weight:700;color:#9aa0ad;font-family:'IBM Plex Mono',monospace;pointer-events:none;">Rp</span>
                <input :value="payAmountFmt" @input="onPayAmount" inputmode="numeric" placeholder="0" style="width:100%;padding:11px 13px 11px 36px;border:1px solid #d8dce4;border-radius:9px;font-size:14px;font-weight:700;color:#13233f;background:#fff;outline:none;text-align:right;font-family:'IBM Plex Mono',monospace;">
              </div>
              <button v-if="payCalc.sisa > 0" type="button" @click="fillSisa" class="tr-btn" style="margin-top:7px;background:#e6f4ec;color:#1f7a5c;border:1px solid #bfe3cf;font-size:11.5px;font-weight:700;padding:5px 11px;border-radius:8px;cursor:pointer;display:inline-flex;align-items:center;gap:5px;"><i class="ph ph-magic-wand" style="font-size:13px;"></i>Isi Sisa Tagihan ({{ fmt(payCalc.sisa) }})</button>
            </div>
          </div>

          <div v-if="Number(payForm.amount) > 0" :style="{ background: willLunas ? '#e6f4ec' : '#fbf1dc', borderRadius: '10px', padding: '10px 14px', display: 'flex', alignItems: 'center', gap: '8px' }">
            <i :class="['ph', willLunas ? 'ph-seal-check' : 'ph-hourglass-medium']" :style="{ fontSize: '16px', color: willLunas ? '#1f7a5c' : '#9a7320' }"></i>
            <span v-if="willLunas" style="font-size:12.5px;font-weight:700;color:#1f7a5c;">Pembayaran ini melunasi seluruh tagihan — status akan menjadi Lunas.</span>
            <span v-else style="font-size:12.5px;font-weight:600;color:#9a7320;">Sisa setelah pembayaran ini: <b style="font-family:'IBM Plex Mono',monospace;">{{ fmt(sisaAfterPay) }}</b></span>
          </div>

          <div>
            <label style="display:block;font-size:12px;font-weight:600;color:#5f6b80;margin-bottom:6px;">Bukti Pembayaran</label>
            <div v-if="!payProof"
              @click="fileInputRef?.click()"
              @dragover.prevent="dragOver = true"
              @dragleave.prevent="dragOver = false"
              @drop.prevent="onDropFile"
              :style="{ border: dragOver ? '2px dashed #15294f' : '2px dashed #d8dce4', background: dragOver ? '#eef3fb' : '#fafbfc' }"
              style="border-radius:12px;padding:16px;text-align:center;cursor:pointer;transition:all .15s;">
              <span style="width:38px;height:38px;border-radius:11px;background:#fff;border:1px solid #eef0f3;display:inline-flex;align-items:center;justify-content:center;margin-bottom:5px;"><i class="ph ph-cloud-arrow-up" style="font-size:20px;color:#15294f;"></i></span>
              <div style="font-size:12.5px;font-weight:600;color:#5f6b80;">Tarik &amp; lepas file di sini, atau <span style="color:#15294f;text-decoration:underline;">klik untuk memilih</span></div>
              <div style="font-size:11px;color:#9aa0ad;margin-top:2px;">Bukti transfer / screenshot · JPG, PNG, PDF · maks 5MB</div>
            </div>
            <div v-else style="display:flex;align-items:center;gap:13px;border:1px solid #d8dce4;border-radius:14px;padding:13px 15px;background:#fafbfc;">
              <img v-if="proofPreviewUrl" :src="proofPreviewUrl" alt="preview" style="width:52px;height:52px;border-radius:10px;object-fit:cover;flex-shrink:0;border:1px solid #eef0f3;">
              <span v-else style="width:52px;height:52px;border-radius:10px;background:#fdf0ed;display:inline-flex;align-items:center;justify-content:center;flex-shrink:0;"><i class="ph ph-file-pdf" style="font-size:25px;color:#c2603a;"></i></span>
              <div style="min-width:0;flex:1;">
                <div style="font-size:13px;font-weight:700;color:#13233f;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;">{{ payProof.name }}</div>
                <div style="font-size:11.5px;color:#9aa0ad;margin-top:2px;">{{ fmtBytes(payProof.size) }}</div>
              </div>
              <button @click="removeProof" class="tr-btn" style="background:#fdf0ed;border:1px solid #f0d0c8;color:#c2603a;font-size:12px;font-weight:700;padding:7px 12px;border-radius:8px;cursor:pointer;display:inline-flex;align-items:center;gap:5px;"><i class="ph ph-trash" style="font-size:13px;"></i>Hapus</button>
            </div>
            <input ref="fileInputRef" type="file" accept=".jpg,.jpeg,.png,.pdf,image/jpeg,image/png,application/pdf" style="display:none;" @change="onPickFile">
          </div>

          <div>
            <label style="display:block;font-size:12px;font-weight:600;color:#5f6b80;margin-bottom:6px;">Komentar</label>
            <textarea v-model="payForm.comment" rows="2" placeholder="cth. Transfer BCA a/n Tourosa, DP 50%" style="width:100%;padding:11px 13px;border:1px solid #d8dce4;border-radius:9px;font-size:13.5px;color:#1a2235;background:#fff;outline:none;resize:vertical;line-height:1.5;"></textarea>
          </div>

          <!-- footer -->
          <div style="display:flex;gap:10px;justify-content:flex-end;padding-top:12px;border-top:1px solid #eef0f3;">
            <button @click="cancelPay" class="tr-btn" style="background:#fff;color:#5f6b80;border:1px solid #e2e4ea;font-size:13px;font-weight:600;padding:9px 18px;border-radius:9px;cursor:pointer;">Batal</button>
            <button @click="submitPayment" :disabled="payMut.isPending.value" class="tr-btn" style="background:#15294f;color:#fff;border:none;font-size:13px;font-weight:700;padding:9px 18px;border-radius:9px;cursor:pointer;display:flex;align-items:center;gap:7px;">
              <i v-if="payMut.isPending.value" class="ph ph-circle-notch" style="font-size:15px;animation:spin 1s linear infinite;"></i>
              <i v-else class="ph ph-check-circle" style="font-size:15px;color:#7ed3a6;"></i>
              {{ payMut.isPending.value ? 'Menyimpan...' : 'Simpan Pembayaran' }}
            </button>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>
