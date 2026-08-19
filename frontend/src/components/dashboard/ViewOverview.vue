<script setup>
import { computed } from 'vue'
import { useRouter } from 'vue-router'
import CountUp from '../CountUp.vue'
import { useDashboardStore } from '../../stores/dashboardStore'
import { useDashboardData } from '../../composables/useDashboardData'

const props = defineProps({
  orders: Array
})
const store = useDashboardStore()
const router = useRouter()
const { fmt, fmtNum, calc, statusMeta } = useDashboardData()

const orders = computed(() => props.orders ?? store.orders)

const sOrders = computed(() => orders.value.length)
const sPax = computed(() => { const n = orders.value.reduce((a, o) => a + (Number(o.pax) || 0), 0); return n.toLocaleString('id-ID') })
const calcs = computed(() => orders.value.map(o => calc(o)))
const sRevenue = computed(() => fmt(calcs.value.reduce((a, c) => a + c.total, 0)))
const sActive = computed(() => orders.value.filter(o => o.status !== 'Lunas').length)

const openDetail = (o) => { store.setActiveInvoice(o); router.push('/dashboard/order-detail/' + encodeURIComponent(o.no)) }

const toRow = (o) => {
  const c = calc(o); const m = statusMeta(o.status);
  return {
    no: o.no, group: o.group, dest: o.dest, pax: (o.pax || '-'), total: fmt(c.total),
    status: o.status, statusBg: m.bg, statusColor: m.color,
    onDetail: () => openDetail(o)
  }
}
const recentOrders = computed(() => orders.value.slice(0, 4).map(toRow))

const lunasCount = computed(() => orders.value.filter(o => o.status === 'Lunas').length)
const dpCount = computed(() => orders.value.filter(o => o.status === 'DP').length)
const pendCount = computed(() => orders.value.filter(o => o.status !== 'Lunas' && o.status !== 'DP').length)
const totCount = computed(() => Math.max(1, sOrders.value))
const statusBars = computed(() => [
  { label: 'Lunas', count: lunasCount.value, width: Math.round(lunasCount.value / totCount.value * 100) + '%', color: '#1f7a5c' },
  { label: 'DP / Berjalan', count: dpCount.value, width: Math.round(dpCount.value / totCount.value * 100) + '%', color: '#c39a4d' },
  { label: 'Pending', count: pendCount.value, width: Math.round(pendCount.value / totCount.value * 100) + '%', color: '#9aa0ad' },
])

const goList = () => router.push('/dashboard/order-list')
const goNew = () => router.push('/dashboard/new-order')
</script>

<template>
  <div class="p-mobile" style="padding:30px 32px;">
    <div class="stats-grid" style="display:grid;grid-template-columns:repeat(4,1fr);gap:18px;margin-bottom:26px;">
      <div style="background:#fff;border:1px solid #e8e9ee;border-radius:16px;padding:22px;">
        <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:14px;"><div style="width:38px;height:38px;border-radius:11px;background:#eef3fb;display:flex;align-items:center;justify-content:center;"><i class="ph-fill ph-shopping-bag-open" style="font-size:19px;color:#15294f;"></i></div></div>
        <div class="stat-number" style="font-size:30px;font-weight:800;color:#13233f;font-family:'IBM Plex Mono',monospace;line-height:1;"><CountUp :value="sOrders" /></div>
        <div class="stat-label" style="font-size:12px;color:#7a8499;font-weight:500;margin-top:6px;">Total pesanan</div>
      </div>
      <div style="background:#fff;border:1px solid #e8e9ee;border-radius:16px;padding:22px;">
        <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:14px;"><div style="width:38px;height:38px;border-radius:11px;background:#f6efe0;display:flex;align-items:center;justify-content:center;"><i class="ph-fill ph-users-three" style="font-size:19px;color:#c39a4d;"></i></div></div>
        <div class="stat-number" style="font-size:30px;font-weight:800;color:#13233f;font-family:'IBM Plex Mono',monospace;line-height:1;"><CountUp :value="sPax" /></div>
        <div class="stat-label" style="font-size:12px;color:#7a8499;font-weight:500;margin-top:6px;">Total peserta (pax)</div>
      </div>
      <div style="background:#fff;border:1px solid #e8e9ee;border-radius:16px;padding:22px;">
        <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:14px;"><div style="width:38px;height:38px;border-radius:11px;background:#e8f4ed;display:flex;align-items:center;justify-content:center;"><i class="ph-fill ph-wallet" style="font-size:19px;color:#1f7a5c;"></i></div></div>
        <div class="stat-number" style="font-size:22px;font-weight:800;color:#13233f;font-family:'IBM Plex Mono',monospace;line-height:1.1;"><CountUp :value="sRevenue" /></div>
        <div class="stat-label" style="font-size:12px;color:#7a8499;font-weight:500;margin-top:6px;">Nilai transaksi</div>
      </div>
      <div style="background:#fff;border:1px solid #e8e9ee;border-radius:16px;padding:22px;">
        <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:14px;"><div style="width:38px;height:38px;border-radius:11px;background:#fbeee4;display:flex;align-items:center;justify-content:center;"><i class="ph-fill ph-clock-countdown" style="font-size:19px;color:#c2603a;"></i></div></div>
        <div class="stat-number" style="font-size:30px;font-weight:800;color:#13233f;font-family:'IBM Plex Mono',monospace;line-height:1;"><CountUp :value="sActive" /></div>
        <div class="stat-label" style="font-size:12px;color:#7a8499;font-weight:500;margin-top:6px;">Pesanan berjalan</div>
      </div>
    </div>

    <div class="grid-cols-1-mobile" style="display:grid;grid-template-columns:1fr 320px;gap:18px;">
      <div style="background:#fff;border:1px solid #e8e9ee;border-radius:16px;overflow:hidden;display:flex;flex-direction:column;">
        <div style="padding:18px 22px;border-bottom:1px solid #eef0f3;display:flex;align-items:center;justify-content:space-between;">
          <h3 style="font-size:16px;font-weight:700;color:#13233f;margin:0;">Pesanan Terbaru</h3>
          <button @click="goList" class="tr-link" style="font-size:13px;font-weight:600;color:#c39a4d;background:none;border:none;cursor:pointer;">Lihat semua →</button>
        </div>
        <div class="table-scroll">
          <div class="min-w-table">
            <div class="table-header-mobile" style="display:grid;grid-template-columns:1.6fr 1fr .7fr 1fr .8fr;gap:12px;padding:11px 22px;background:#fafbfc;font-size:11.5px;font-weight:700;color:#9aa0ad;text-transform:uppercase;letter-spacing:.04em;">
              <span>Grup</span><span>Destinasi</span><span>Pax</span><span>Nilai</span><span>Status</span>
            </div>
            <div v-for="(o, idx) in recentOrders" :key="idx" @click="o.onDetail" class="tr-nav table-row-mobile" style="display:grid;grid-template-columns:1.6fr 1fr .7fr 1fr .8fr;gap:12px;padding:14px 22px;border-top:1px solid #f1f2f5;cursor:pointer;align-items:center;">
              <div class="col-full-mobile" style="min-width:0;"><div style="font-size:14px;font-weight:700;color:#13233f;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;">{{ o.group }}</div><div style="font-size:11px;color:#9aa0ad;font-family:'IBM Plex Mono',monospace;">{{ o.no }}</div></div>
              <span class="col-half-mobile" style="font-size:13.5px;color:#5d6a82;"><i class="ph ph-map-pin" style="font-size:13.5px;color:#5d6a82;vertical-align:middle;padding-right:4px;"></i>{{ o.dest }}</span>
              <span class="col-half-mobile" style="font-size:13.5px;color:#5d6a82;"><i class="ph ph-users" style="font-size:13.5px;color:#5d6a82;vertical-align:middle;padding-right:4px;"></i>{{ o.pax }}</span>
              <span class="col-half-mobile" style="font-size:13.5px;font-weight:700;color:#13233f;font-family:'IBM Plex Mono',monospace;">{{ o.total }}</span>
              <span class="col-half-mobile"><span :style="{ color: o.statusColor, background: o.statusBg }" style="font-size:11.5px;font-weight:700;padding:5px 10px;border-radius:7px;">{{ o.status }}</span></span>
            </div>
          </div>
        </div>
      </div>

      <div style="display:flex;flex-direction:column;gap:18px;">
        <div style="background:#fff;border:1px solid #e8e9ee;border-radius:16px;padding:20px;">
          <h3 style="font-size:15px;font-weight:700;color:#13233f;margin:0 0 16px;">Status Pembayaran</h3>
          <div v-for="(b, idx) in statusBars" :key="idx" style="margin-bottom:14px;">
            <div style="display:flex;justify-content:space-between;margin-bottom:6px;"><span style="font-size:13px;color:#5d6a82;font-weight:600;">{{ b.label }}</span><span style="font-size:13px;color:#13233f;font-weight:700;font-family:'IBM Plex Mono',monospace;">{{ b.count }}</span></div>
            <div style="height:7px;background:#f1f2f5;border-radius:5px;overflow:hidden;"><div :style="{ width: b.width, background: b.color, height: '100%', borderRadius: '5px' }"></div></div>
          </div>
        </div>
        <div style="background:linear-gradient(135deg,#15294f,#0d1b30);border-radius:16px;padding:22px;color:#fff;">
          <i class="ph-fill ph-note-pencil" style="font-size:26px;color:#c39a4d;"></i>
          <h3 style="font-size:16px;font-weight:700;margin:14px 0 6px;">Input pesanan baru</h3>
          <p style="font-size:13px;color:#aeb8cc;line-height:1.5;margin:0 0 16px;">Buat pesanan grup dan cetak invoice resmi dalam hitungan menit.</p>
          <button @click="goNew" class="tr-btn" style="background:#c39a4d;color:#13233f;font-size:13.5px;font-weight:700;padding:11px 18px;border-radius:10px;border:none;cursor:pointer;width:100%;">+ Buat Pesanan</button>
        </div>
      </div>
    </div>
  </div>
</template>
