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
const { fmt, calc, statusMeta } = useDashboardData()

const orders = computed(() => props.orders ?? store.orders)

const calcs = computed(() => orders.value.map(o => calc(o)))
const repRevenue = computed(() => calcs.value.reduce((a, c) => a + c.total, 0))
const repCost = computed(() => calcs.value.reduce((a, c) => a + c.totalCost, 0))
const repAfterDisc = computed(() => calcs.value.reduce((a, c) => a + (c.subtotal - c.discount), 0))
const repProfit = computed(() => calcs.value.reduce((a, c) => a + c.profit, 0))
const repMargin = computed(() => repAfterDisc.value ? Math.round(repProfit.value / repAfterDisc.value * 100) : 0)

const repRevenueF = computed(() => fmt(repRevenue.value))
const repCostF = computed(() => fmt(repCost.value))
const repProfitF = computed(() => fmt(repProfit.value))
const repMarginF = computed(() => repMargin.value + '%')

const maxP = computed(() => Math.max(1, ...calcs.value.map(c => c.profit)))
const openDetail = (o) => { store.setActiveInvoice(o); router.push('/dashboard/order-detail') }

const repOrders = computed(() => {
  return orders.value.map((o, i) => {
    const c = calcs.value[i]; const m = statusMeta(o.status);
    return {
      group: o.group, no: o.no, dest: o.dest, revenueF: fmt(c.total), costF: fmt(c.totalCost),
      profitF: fmt(c.profit), marginF: Math.round(c.marginPct) + '%', status: o.status, statusBg: m.bg, statusColor: m.color,
      width: Math.round(Math.max(0, c.profit) / maxP.value * 100) + '%', onDetail: () => openDetail(o)
    }
  })
})

const catBreakdown = computed(() => {
  const catMap = {};
  orders.value.forEach((o, i) => calcs.value[i].items.forEach(it => { catMap[it.cat] = (catMap[it.cat] || 0) + it.line; }));
  const catTotal = Object.values(catMap).reduce((a, b) => a + b, 0) || 1;
  const catColors = ['#15294f', '#c39a4d', '#1f7a5c', '#c2603a', '#5b6b8c', '#9a7320', '#7c89a3', '#a8a08c'];
  return Object.entries(catMap).sort((a, b) => b[1] - a[1]).map(([cat, amt], i) => ({
    cat, amountF: fmt(amt), pct: Math.round(amt / catTotal * 100) + '%', width: Math.round(amt / catTotal * 100) + '%',
    color: catColors[i % catColors.length]
  }))
})
</script>

<template>
  <div class="p-mobile" style="padding:30px 32px;">
    <div class="stats-grid" style="display:grid;grid-template-columns:repeat(4,1fr);gap:18px;margin-bottom:22px;">
      <div style="background:#fff;border:1px solid #e8e9ee;border-radius:16px;padding:22px;">
        <div style="width:42px;height:42px;border-radius:11px;background:#eef3fb;display:flex;align-items:center;justify-content:center;margin-bottom:16px;"><i class="ph-fill ph-wallet" style="font-size:21px;color:#15294f;"></i></div>
        <div style="font-size:22px;font-weight:800;color:#13233f;font-family:'IBM Plex Mono',monospace;line-height:1.1;"><CountUp :value="repRevenueF" /></div>
        <div style="font-size:13px;color:#7a8499;font-weight:500;margin-top:7px;">Total pendapatan</div>
      </div>
      <div style="background:#fff;border:1px solid #e8e9ee;border-radius:16px;padding:22px;">
        <div style="width:42px;height:42px;border-radius:11px;background:#eef0f3;display:flex;align-items:center;justify-content:center;margin-bottom:16px;"><i class="ph-fill ph-bag" style="font-size:21px;color:#5d6a82;"></i></div>
        <div style="font-size:22px;font-weight:800;color:#13233f;font-family:'IBM Plex Mono',monospace;line-height:1.1;"><CountUp :value="repCostF" /></div>
        <div style="font-size:13px;color:#7a8499;font-weight:500;margin-top:7px;">Total modal (HPP)</div>
      </div>
      <div style="background:linear-gradient(135deg,#15294f,#0d1b30);border-radius:16px;padding:22px;">
        <div style="width:42px;height:42px;border-radius:11px;background:rgba(195,154,77,.2);display:flex;align-items:center;justify-content:center;margin-bottom:16px;"><i class="ph-fill ph-trend-up" style="font-size:21px;color:#c39a4d;"></i></div>
        <div style="font-size:22px;font-weight:800;color:#7ed3a6;font-family:'IBM Plex Mono',monospace;line-height:1.1;"><CountUp :value="repProfitF" /></div>
        <div style="font-size:13px;color:#aeb8cc;font-weight:500;margin-top:7px;">Estimasi profit</div>
      </div>
      <div style="background:#fff;border:1px solid #e8e9ee;border-radius:16px;padding:22px;">
        <div style="width:42px;height:42px;border-radius:11px;background:#e8f4ed;display:flex;align-items:center;justify-content:center;margin-bottom:16px;"><i class="ph-fill ph-percent" style="font-size:21px;color:#1f7a5c;"></i></div>
        <div style="font-size:22px;font-weight:800;color:#13233f;font-family:'IBM Plex Mono',monospace;line-height:1.1;"><CountUp :value="repMarginF" /></div>
        <div style="font-size:13px;color:#7a8499;font-weight:500;margin-top:7px;">Margin rata-rata</div>
      </div>
    </div>
    <div class="grid-cols-1-mobile" style="display:grid;grid-template-columns:1.25fr .9fr;gap:18px;">
      <div style="background:#fff;border:1px solid #e8e9ee;border-radius:16px;overflow:hidden;">
        <div style="padding:18px 22px;border-bottom:1px solid #eef0f3;"><h3 style="font-size:16px;font-weight:700;color:#13233f;margin:0;">Profit per Pesanan</h3></div>
        <div style="padding:8px 22px 16px;">
          <div v-for="(o, idx) in repOrders" :key="idx" @click="o.onDetail" class="tr-nav" style="padding:13px 10px;border-radius:10px;cursor:pointer;">
            <div style="display:flex;justify-content:space-between;align-items:baseline;margin-bottom:8px;gap:12px;">
              <div style="min-width:0;"><span style="font-size:14px;font-weight:700;color:#13233f;">{{ o.group }}</span><span style="font-size:11px;color:#9aa0ad;font-family:'IBM Plex Mono',monospace;margin-left:8px;">{{ o.no }}</span></div>
              <div style="text-align:right;white-space:nowrap;"><span style="font-size:14px;font-weight:800;color:#1f7a5c;font-family:'IBM Plex Mono',monospace;">{{ o.profitF }}</span><span style="font-size:11.5px;color:#9aa0ad;margin-left:7px;">{{ o.marginF }}</span></div>
            </div>
            <div style="height:8px;background:#f1f2f5;border-radius:5px;overflow:hidden;"><div :style="{ width: o.width, height: '100%', background: 'linear-gradient(90deg,#15294f,#c39a4d)', borderRadius: '5px' }"></div></div>
          </div>
        </div>
      </div>
      <div style="background:#fff;border:1px solid #e8e9ee;border-radius:16px;overflow:hidden;">
        <div style="padding:18px 22px;border-bottom:1px solid #eef0f3;"><h3 style="font-size:16px;font-weight:700;color:#13233f;margin:0;">Pendapatan per Kategori</h3></div>
        <div style="padding:18px 22px;">
          <div v-for="(c, idx) in catBreakdown" :key="idx" style="margin-bottom:16px;">
            <div style="display:flex;justify-content:space-between;margin-bottom:7px;gap:10px;"><span style="font-size:13px;color:#13233f;font-weight:600;display:flex;align-items:center;gap:8px;"><span style="width:9px;height:9px;border-radius:2px;display:inline-block;" :style="{ background: c.color }"></span>{{ c.cat }}</span><span style="font-size:12.5px;color:#5d6a82;font-weight:700;font-family:'IBM Plex Mono',monospace;">{{ c.amountF }}</span></div>
            <div style="height:7px;background:#f1f2f5;border-radius:5px;overflow:hidden;"><div :style="{ width: c.width, background: c.color, height: '100%', borderRadius: '5px' }"></div></div>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>
