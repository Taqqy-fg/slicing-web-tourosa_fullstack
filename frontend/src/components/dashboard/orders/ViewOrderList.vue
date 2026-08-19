<script setup>
import { computed, ref } from 'vue'
import { useRouter } from 'vue-router'
import { useMutation, useQueryClient } from '@tanstack/vue-query'
import { useVueTable, getCoreRowModel, getSortedRowModel, getFilteredRowModel, getPaginationRowModel } from '@tanstack/vue-table'
import { useDashboardStore } from '../../../stores/dashboardStore'
import { useDashboardData } from '../../../composables/useDashboardData'
import { dashboardService } from '../../../services/dashboardService'
import { useToast } from '../../../composables/useToast'

const props = defineProps({
  orders: Array
})

const store = useDashboardStore()
const router = useRouter()
const queryClient = useQueryClient()
const { fmt, fmtDate, fmtShort, calc, statusMeta } = useDashboardData()
const toast = useToast()

const orders = computed(() => props.orders ?? store.orders)

const viewInvoice = (o) => { store.setActiveInvoice(o); router.push('/orders/invoice/' + encodeURIComponent(o.no)) }
const openDetail = (o) => { store.setActiveInvoice(o); router.push('/orders/detail/' + encodeURIComponent(o.no)) }
const editOrder = (o) => { store.loadEditForm(o); router.push('/orders/edit/' + encodeURIComponent(o.no)) }

const rawData = computed(() => orders.value.map(o => {
  const c = calc(o); const m = statusMeta(o.status)
  return {
    no: o.no, group: o.group, dest: o.dest, pax: o.pax || '-', total: fmt(c.total),
    totalRaw: c.total, tripShort: o.depart ? fmtShort(o.depart) : '-',
    status: o.status, statusBg: m.bg, statusColor: m.color, date: fmtDate(o.date),
    raw: o
  }
}))

const columns = [
  { accessorKey: 'group', header: 'Grup', enableSorting: true },
  { accessorKey: 'dest', header: 'Destinasi', enableSorting: true },
  { accessorKey: 'tripShort', header: 'Tanggal', enableSorting: true },
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
          <div class="table-header-mobile" style="display:grid;grid-template-columns:1.4fr .95fr .85fr .5fr 1fr .75fr 1.8fr;gap:12px;padding:12px 22px;background:#fafbfc;font-size:11.5px;font-weight:700;color:#9aa0ad;text-transform:uppercase;letter-spacing:.04em;">
            <span v-for="header in table.getHeaderGroups()[0].headers" :key="header.id"
              :style="{ cursor: header.column.getCanSort() ? 'pointer' : 'default' }"
              @click="header.column.getToggleSortingHandler()?.($event)">
              {{ header.column.columnDef.header }}
              <i v-if="header.column.getCanSort()" :class="['ph', sortIcon(header.column.id)]" style="font-size:12px;vertical-align:middle;margin-left:3px;"></i>
            </span>
            <span style="text-align:right;">Aksi</span>
          </div>

          <div v-for="row in table.getRowModel().rows" :key="row.id" class="table-row-mobile" style="display:grid;grid-template-columns:1.4fr .95fr .85fr .5fr 1fr .75fr 1.8fr;gap:12px;padding:15px 22px;border-top:1px solid #f1f2f5;align-items:center;">
            <div class="col-full-mobile" style="min-width:0;"><div style="font-size:14px;font-weight:700;color:#13233f;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;">{{ row.original.group }}</div><div style="font-size:11px;color:#9aa0ad;font-family:'IBM Plex Mono',monospace;">{{ row.original.no }}</div></div>
            <span class="col-half-mobile" style="font-size:13.5px;color:#5d6a82;"><i class="ph ph-map-pin" style="font-size:13.5px;color:#5d6a82;vertical-align:middle;padding-right:4px;"></i>{{ row.original.dest }}</span>
            <span class="col-half-mobile" style="font-size:13px;color:#5d6a82;font-family:'IBM Plex Mono',monospace;"><i class="ph ph-calendar" style="font-size:13px;color:#5d6a82;vertical-align:middle;padding-right:4px;"></i>{{ row.original.tripShort }}</span>
            <span class="col-auto-mobile hide-mobile" style="font-size:13.5px;color:#5d6a82;font-family:'IBM Plex Mono',monospace;">{{ row.original.pax }}</span>
            <span class="col-half-mobile" style="font-size:13.5px;font-weight:700;color:#13233f;font-family:'IBM Plex Mono',monospace;">{{ row.original.total }}</span>
            <span class="col-half-mobile"><span :style="{ color: row.original.statusColor, background: row.original.statusBg }" style="font-size:11.5px;font-weight:700;padding:5px 10px;border-radius:7px;">{{ row.original.status }}</span></span>
            <span class="col-full-mobile" style="text-align:right;display:flex;gap:6px;justify-content:flex-end;">
              <button @click="openDetail(row.original.raw)" class="tr-btn" style="background:#15294f;color:#fff;border:none;font-size:12.5px;font-weight:700;padding:8px 14px;border-radius:8px;cursor:pointer;display:inline-flex;align-items:center;gap:6px;"><i class="ph ph-file-text" style="font-size:14px;color:#c39a4d;"></i>Detail</button>
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
            <i class="ph ph-trash" style="font-size:15px;"></i>
            {{ deleteMut.isPending.value ? 'Menghapus...' : 'Hapus' }}
          </button>
        </div>
      </div>
    </div>
  </div>
</template>
