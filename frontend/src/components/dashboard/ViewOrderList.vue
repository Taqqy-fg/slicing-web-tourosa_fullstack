<script setup>
import { computed } from 'vue'
import { useRouter } from 'vue-router'
import { useDashboardStore } from '../../stores/dashboardStore'
import { useDashboardData } from '../../composables/useDashboardData'

const props = defineProps({
  orders: Array
})

const store = useDashboardStore()
const router = useRouter()
const { fmt, fmtDate, fmtShort, calc, statusMeta } = useDashboardData()

const orders = computed(() => props.orders ?? store.orders)
const sOrders = computed(() => orders.value.length)

const viewInvoice = (o) => { store.setActiveInvoice(o); router.push('/dashboard/invoice') }
const openDetail = (o) => { store.setActiveInvoice(o); router.push('/dashboard/order-detail') }

const allOrders = computed(() => orders.value.map(o => {
  const c = calc(o); const m = statusMeta(o.status);
  return {
    no: o.no, group: o.group, dest: o.dest, pax: (o.pax || '-'), total: fmt(c.total),
    tripShort: o.depart ? fmtShort(o.depart) : '-',
    status: o.status, statusBg: m.bg, statusColor: m.color, date: fmtDate(o.date),
    onView: () => viewInvoice(o), onDetail: () => openDetail(o)
  }
}))

const goNew = () => router.push('/dashboard/new-order')
</script>

<template>
  <div class="p-mobile" style="padding:30px 32px;">
    <div style="background:#fff;border:1px solid #e8e9ee;border-radius:16px;overflow:hidden;display:flex;flex-direction:column;">
      <div style="padding:18px 22px;border-bottom:1px solid #eef0f3;display:flex;align-items:center;">
        <h3 style="font-size:16px;font-weight:700;color:#13233f;margin:0;">Semua Pesanan <span style="color:#9aa0ad;font-weight:600;">({{ sOrders }})</span></h3>
      </div>
      <div class="table-scroll">
        <div class="min-w-table">
          <div class="table-header-mobile" style="display:grid;grid-template-columns:1.4fr .95fr .85fr .5fr 1fr .75fr 1.5fr;gap:12px;padding:12px 22px;background:#fafbfc;font-size:11.5px;font-weight:700;color:#9aa0ad;text-transform:uppercase;letter-spacing:.04em;">
            <span>Grup</span><span>Destinasi</span><span>Tanggal</span><span>Pax</span><span>Total</span><span>Status</span><span style="text-align:right;">Aksi</span>
          </div>
          <div v-for="(o, idx) in allOrders" :key="idx" class="table-row-mobile" style="display:grid;grid-template-columns:1.4fr .95fr .85fr .5fr 1fr .75fr 1.5fr;gap:12px;padding:15px 22px;border-top:1px solid #f1f2f5;align-items:center;">
            <div class="col-full-mobile" style="min-width:0;"><div style="font-size:14px;font-weight:700;color:#13233f;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;">{{ o.group }}</div><div style="font-size:11px;color:#9aa0ad;font-family:'IBM Plex Mono',monospace;">{{ o.no }}</div></div>
            <span class="col-half-mobile" style="font-size:13.5px;color:#5d6a82;">📍 {{ o.dest }}</span>
            <span class="col-half-mobile" style="font-size:13px;color:#5d6a82;font-family:'IBM Plex Mono',monospace;">🗓 {{ o.tripShort }}</span>
            <span class="col-auto-mobile hide-mobile" style="font-size:13.5px;color:#5d6a82;font-family:'IBM Plex Mono',monospace;">{{ o.pax }}</span>
            <span class="col-half-mobile" style="font-size:13.5px;font-weight:700;color:#13233f;font-family:'IBM Plex Mono',monospace;">{{ o.total }}</span>
            <span class="col-half-mobile"><span :style="{ color: o.statusColor, background: o.statusBg }" style="font-size:11.5px;font-weight:700;padding:5px 10px;border-radius:7px;">{{ o.status }}</span></span>
            <span class="col-full-mobile" style="text-align:right;display:flex;gap:6px;justify-content:flex-end;">
              <button @click="o.onDetail" class="tr-btn" style="background:#15294f;color:#fff;border:none;font-size:12.5px;font-weight:700;padding:8px 16px;border-radius:8px;cursor:pointer;display:inline-flex;align-items:center;gap:6px;"><i class="ph ph-file-text" style="font-size:14px;color:#c39a4d;"></i>Detail</button>
            </span>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>
