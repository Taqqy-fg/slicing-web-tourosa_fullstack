<script setup>
import { computed, ref } from 'vue'
import { useRouter } from 'vue-router'
import { useMutation, useQueryClient } from '@tanstack/vue-query'
import { useDashboardStore } from '../../stores/dashboardStore'
import { useDashboardData } from '../../composables/useDashboardData'
import { dashboardService } from '../../services/dashboardService'

const props = defineProps({
  orders: Array
})

const store = useDashboardStore()
const router = useRouter()
const queryClient = useQueryClient()
const { fmt, fmtDate, fmtShort, calc, statusMeta } = useDashboardData()

const orders = computed(() => props.orders ?? store.orders)
const sOrders = computed(() => orders.value.length)

const viewInvoice = (o) => { store.setActiveInvoice(o); router.push('/dashboard/invoice') }
const openDetail = (o) => { store.setActiveInvoice(o); router.push('/dashboard/order-detail') }
const editOrder = (o) => { store.loadEditForm(o); router.push('/dashboard/edit-order') }

const allOrders = computed(() => orders.value.map(o => {
  const c = calc(o); const m = statusMeta(o.status);
  return {
    no: o.no, group: o.group, dest: o.dest, pax: (o.pax || '-'), total: fmt(c.total),
    tripShort: o.depart ? fmtShort(o.depart) : '-',
    status: o.status, statusBg: m.bg, statusColor: m.color, date: fmtDate(o.date),
    raw: o,
    onView: () => viewInvoice(o), onDetail: () => openDetail(o), onEdit: () => editOrder(o)
  }
}))

const goNew = () => router.push('/dashboard/new-order')

const deleteModal = ref(false)
const deleteTarget = ref(null)
const deleteMut = useMutation({
  mutationFn: dashboardService.deleteOrder,
  onSuccess: () => {
    queryClient.invalidateQueries({ queryKey: ['dashboard'] })
    deleteModal.value = false
    deleteTarget.value = null
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
      <div style="padding:18px 22px;border-bottom:1px solid #eef0f3;display:flex;align-items:center;">
        <h3 style="font-size:16px;font-weight:700;color:#13233f;margin:0;">Semua Pesanan <span style="color:#9aa0ad;font-weight:600;">({{ sOrders }})</span></h3>
      </div>
      <div class="table-scroll">
        <div class="min-w-table">
          <div class="table-header-mobile" style="display:grid;grid-template-columns:1.4fr .95fr .85fr .5fr 1fr .75fr 1.8fr;gap:12px;padding:12px 22px;background:#fafbfc;font-size:11.5px;font-weight:700;color:#9aa0ad;text-transform:uppercase;letter-spacing:.04em;">
            <span>Grup</span><span>Destinasi</span><span>Tanggal</span><span>Pax</span><span>Total</span><span>Status</span><span style="text-align:right;">Aksi</span>
          </div>
          <div v-for="(o, idx) in allOrders" :key="idx" class="table-row-mobile" style="display:grid;grid-template-columns:1.4fr .95fr .85fr .5fr 1fr .75fr 1.8fr;gap:12px;padding:15px 22px;border-top:1px solid #f1f2f5;align-items:center;">
            <div class="col-full-mobile" style="min-width:0;"><div style="font-size:14px;font-weight:700;color:#13233f;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;">{{ o.group }}</div><div style="font-size:11px;color:#9aa0ad;font-family:'IBM Plex Mono',monospace;">{{ o.no }}</div></div>
            <span class="col-half-mobile" style="font-size:13.5px;color:#5d6a82;"><i class="ph ph-map-pin" style="font-size:13.5px;color:#5d6a82;vertical-align:middle;padding-right:4px;"></i>{{ o.dest }}</span>
            <span class="col-half-mobile" style="font-size:13px;color:#5d6a82;font-family:'IBM Plex Mono',monospace;"><i class="ph ph-calendar" style="font-size:13px;color:#5d6a82;vertical-align:middle;padding-right:4px;"></i>{{ o.tripShort }}</span>
            <span class="col-auto-mobile hide-mobile" style="font-size:13.5px;color:#5d6a82;font-family:'IBM Plex Mono',monospace;">{{ o.pax }}</span>
            <span class="col-half-mobile" style="font-size:13.5px;font-weight:700;color:#13233f;font-family:'IBM Plex Mono',monospace;">{{ o.total }}</span>
            <span class="col-half-mobile"><span :style="{ color: o.statusColor, background: o.statusBg }" style="font-size:11.5px;font-weight:700;padding:5px 10px;border-radius:7px;">{{ o.status }}</span></span>
            <span class="col-full-mobile" style="text-align:right;display:flex;gap:6px;justify-content:flex-end;">
              <button @click="o.onDetail" class="tr-btn" style="background:#15294f;color:#fff;border:none;font-size:12.5px;font-weight:700;padding:8px 14px;border-radius:8px;cursor:pointer;display:inline-flex;align-items:center;gap:6px;"><i class="ph ph-file-text" style="font-size:14px;color:#c39a4d;"></i>Detail</button>
              <button @click="o.onEdit" class="tr-btn" style="background:#eef3fb;color:#15294f;border:1px solid #d6e1f2;font-size:12.5px;font-weight:700;padding:8px 14px;border-radius:8px;cursor:pointer;display:inline-flex;align-items:center;gap:6px;"><i class="ph ph-pencil-simple" style="font-size:14px;color:#15294f;"></i>Edit</button>
              <button @click="confirmDelete(o.raw)" class="tr-btn" style="background:#fdf0ed;color:#c2603a;border:1px solid #f0d0c8;font-size:12.5px;font-weight:700;padding:8px 14px;border-radius:8px;cursor:pointer;display:inline-flex;align-items:center;gap:6px;"><i class="ph ph-trash" style="font-size:14px;"></i>Hapus</button>
            </span>
          </div>
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
