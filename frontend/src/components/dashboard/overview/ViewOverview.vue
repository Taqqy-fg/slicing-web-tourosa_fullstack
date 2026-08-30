<script setup>
import { ref, computed, onMounted, nextTick } from 'vue'
import { useRouter } from 'vue-router'
import CountUp from '../../CountUp.vue'
import { useDashboardStore } from '../../../stores/dashboardStore'
import { useAuthStore } from '../../../stores/authStore'
import { useDashboardData } from '../../../composables/useDashboardData'
import VueApexCharts from 'vue3-apexcharts'
import moment from 'moment'
import 'daterangepicker/daterangepicker.css'

const drpInput = ref(null)

const props = defineProps({
  orders: Array
})
const store = useDashboardStore()
const auth = useAuthStore()
const router = useRouter()
const { fmt, fmtNum, fmtDate, calc, statusMeta } = useDashboardData()

const dateRange = ref({ start: null, end: null })

const orders = computed(() => {
  let list = props.orders ?? store.orders
  if (dateRange.value.start && dateRange.value.end) {
    const start = moment(dateRange.value.start).startOf('day')
    const end = moment(dateRange.value.end).endOf('day')
    list = list.filter(o => {
      const d = moment(o.date)
      return d.isSameOrAfter(start) && d.isSameOrBefore(end)
    })
  }
  return list
})

const sOrders = computed(() => orders.value.length)
const orderPax = (o) => (o.items || []).reduce((s, it) => s + (Number(it.qty) || 0), 0) || Number(o.pax) || 0
const sPax = computed(() => orders.value.reduce((a, o) => a + orderPax(o), 0).toLocaleString('id-ID'))
const calcs = computed(() => orders.value.map(o => calc(o)))
const sRevenue = computed(() => fmt(calcs.value.reduce((a, c) => a + c.grandTotal, 0)))
const sActive = computed(() => orders.value.filter(o => o.status !== 'Lunas').length)
const sProfit = computed(() => fmt(calcs.value.reduce((a, c) => a + c.profit, 0)))

const openDetail = (o) => { store.setActiveInvoice(o); router.push('/orders/detail/' + encodeURIComponent(o.no)) }

const toRow = (o) => {
  const c = calc(o); const m = statusMeta(o.status);
  return {
    no: o.no, group: o.group, dateInv: fmtDate(o.date), pax: orderPax(o) || '-', total: fmt(c.grandTotal),
    status: o.status, statusBg: m.bg, statusColor: m.color,
    onDetail: () => openDetail(o)
  }
}
const recentOrders = computed(() => orders.value.slice(0, 4).map(toRow))

const lunasCount = computed(() => orders.value.filter(o => o.status === 'Lunas').length)
const pendCount = computed(() => orders.value.filter(o => o.status === 'Belum Lunas').length)
const totCount = computed(() => Math.max(1, sOrders.value))
const statusBars = computed(() => [
  { label: 'Lunas', count: lunasCount.value, width: Math.round(lunasCount.value / totCount.value * 100) + '%', color: '#1f7a5c' },
  { label: 'Belum Lunas', count: pendCount.value, width: Math.round(pendCount.value / totCount.value * 100) + '%', color: '#c2603a' },
])

const goList = () => router.push('/orders')

// Chart specific filters
const chartYear = ref('All')
const availableYears = computed(() => {
  const years = new Set(orders.value.map(o => moment(o.date).year()))
  return Array.from(years).sort((a,b) => b - a)
})

// Area Chart Options
const areaChartSeries = computed(() => {
  const grouped = {}
  orders.value.forEach(o => {
    const d = o.date
    if (chartYear.value === 'All' || moment(d).year().toString() === chartYear.value) {
      if (!grouped[d]) grouped[d] = { revenue: 0, cost: 0 }
      const c = calc(o)
      grouped[d].revenue += c.grandTotal
      grouped[d].cost += c.totalCost
    }
  })
  const sortedDates = Object.keys(grouped).sort()
  return [
    {
      name: 'Pendapatan',
      data: sortedDates.map(d => ({ x: d, y: Math.round(grouped[d].revenue) }))
    },
    {
      name: 'Total Modal (HPP)',
      data: sortedDates.map(d => ({ x: d, y: Math.round(grouped[d].cost) }))
    }
  ]
})

const areaChartOptions = {
  chart: { type: 'area', toolbar: { show: true }, height: 300, fontFamily: 'Plus Jakarta Sans, sans-serif' },
  dataLabels: { enabled: false },
  stroke: { curve: 'smooth', width: 4 },
  xaxis: { type: 'datetime', labels: { style: { colors: '#8a93a5' } }, axisBorder: { show: false }, axisTicks: { show: false } },
  yaxis: {
    labels: {
      style: { colors: '#8a93a5' },
      formatter: (val) => 'Rp' + new Intl.NumberFormat('id-ID', { notation: 'compact' }).format(val)
    }
  },
  grid: { borderColor: '#f1f2f5', strokeDashArray: 4 },
  tooltip: { x: { format: 'dd MMM yyyy' } },
  colors: ['rgb(195, 154, 77)', 'rgb(21, 41, 79)']
}

const catColors = ['#15294f', '#c39a4d', '#1f7a5c', '#c2603a', '#5b6b8c', '#9a7320', '#7c89a3', '#a8a08c'];

const catBreakdown = computed(() => {
  const catMap = {};
  orders.value.forEach((o, i) => {
    if (chartYear.value === 'All' || moment(o.date).year().toString() === chartYear.value) {
      calcs.value[i].items.forEach(it => { catMap[it.cat] = (catMap[it.cat] || 0) + it.line; })
    }
  });
  const catTotal = Object.values(catMap).reduce((a, b) => a + b, 0) || 1;
  return Object.entries(catMap).sort((a, b) => b[1] - a[1]).map(([cat, amt], i) => ({
    cat, amountF: fmt(amt), pct: Math.round(amt / catTotal * 100) + '%', width: Math.round(amt / catTotal * 100) + '%',
    color: catColors[i % catColors.length],
    amt
  }))
})

// Donut chart for category breakdown
const donutChartSeries = computed(() => catBreakdown.value.map(c => c.amt))
const donutChartOptions = computed(() => ({
  chart: { type: 'donut', toolbar: { show: true }, fontFamily: 'Plus Jakarta Sans, sans-serif' },
  labels: catBreakdown.value.map(c => c.cat),
  colors: catColors,
  dataLabels: { enabled: false },
  stroke: { show: false },
  legend: { show: true, position: 'bottom' },
  tooltip: {
    y: { formatter: (val) => 'Rp' + new Intl.NumberFormat('id-ID', { notation: 'compact' }).format(val) }
  }
}))

onMounted(async () => {
  // Dynamic import ensures jQuery is on window BEFORE daterangepicker loads
  await import('daterangepicker')
  await nextTick()

  const el = drpInput.value
  if (!el) return

  window.$(el).daterangepicker({
    autoUpdateInput: false,
    locale: { cancelLabel: 'Clear', applyLabel: 'Terapkan', format: 'DD/MM/YYYY', customRangeLabel: 'Custom Range' },
    opens: 'left',
    ranges: {
      'Today': [moment(), moment()],
      'Yesterday': [moment().subtract(1, 'days'), moment().subtract(1, 'days')],
      'Last 7 Days': [moment().subtract(6, 'days'), moment()],
      'Last 30 Days': [moment().subtract(29, 'days'), moment()],
      'This Month': [moment().startOf('month'), moment().endOf('month')],
      'Last Month': [moment().subtract(1, 'month').startOf('month'), moment().subtract(1, 'month').endOf('month')]
    }
  })

  window.$(el).on('show.daterangepicker', function(ev, picker) {
    if (picker.container) picker.container.removeClass('is-custom')
  })
  window.$(el).on('hideCalendar.daterangepicker', function(ev, picker) {
    if (picker.container) picker.container.removeClass('is-custom')
  })
  window.$(el).on('showCalendar.daterangepicker', function(ev, picker) {
    if (picker.container) picker.container.addClass('is-custom')
  })
  window.$(el).on('apply.daterangepicker', function(ev, picker) {
    dateRange.value = { start: picker.startDate.toDate(), end: picker.endDate.toDate() }
    el.value = picker.startDate.format('DD/MM/YYYY') + ' - ' + picker.endDate.format('DD/MM/YYYY')
    if (picker.container) picker.container.toggleClass('is-custom', picker.chosenLabel !== 'Today')
  })

  window.$(el).on('cancel.daterangepicker', function() {
    el.value = ''
    dateRange.value = { start: null, end: null }
  })
})

</script>

<template>
  <div class="p-mobile" style="padding:30px 32px;">
    <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:24px;">
      <div>
        <h2 style="font-size:20px;font-weight:700;color:#13233f;margin:0 0 4px;">Selamat Datang, {{ auth.user?.name || 'Admin' }}!</h2>
      </div>
      <div style="position:relative;">
        <input ref="drpInput" type="text" placeholder="Filter Rentang Tanggal..." style="padding:9px 36px 9px 14px;border:1px solid #d8dce4;border-radius:8px;font-size:13px;outline:none;width:240px;background:#fff;cursor:pointer;" readonly />
        <i class="ph ph-calendar-blank" style="position:absolute;right:12px;top:50%;transform:translateY(-50%);color:#8a93a5;font-size:16px;pointer-events:none;"></i>
      </div>
    </div>

    <div class="stats-grid" style="display:grid;grid-template-columns:repeat(2,1fr);gap:18px;margin-bottom:26px;">
      <div style="background:#fff;border:1px solid #e8e9ee;border-radius:16px;padding:22px;">
        <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:14px;"><div style="width:38px;height:38px;border-radius:11px;background:#eef3fb;display:flex;align-items:center;justify-content:center;"><i class="ph-fill ph-shopping-bag-open" style="font-size:19px;color:#15294f;"></i></div></div>
        <div class="stat-number" style="font-size:30px;font-weight:800;color:#13233f;font-family:'IBM Plex Mono',monospace;line-height:1;"><CountUp :value="sOrders" /></div>
        <div class="stat-label" style="font-size:12px;color:#7a8499;font-weight:500;margin-top:6px;">Total pesanan</div>
      </div>
      <div style="background:#fff;border:1px solid #e8e9ee;border-radius:16px;padding:22px;">
        <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:14px;"><div style="width:38px;height:38px;border-radius:11px;background:#e8f4ed;display:flex;align-items:center;justify-content:center;"><i class="ph-fill ph-trend-up" style="font-size:19px;color:#1f7a5c;"></i></div></div>
        <div class="stat-number" style="font-size:22px;font-weight:800;color:#1f7a5c;font-family:'IBM Plex Mono',monospace;line-height:1.1;"><CountUp :value="sProfit" /></div>
        <div class="stat-label" style="font-size:12px;color:#7a8499;font-weight:500;margin-top:6px;">Estimasi keuntungan</div>
      </div>
    </div>

    <div class="grid-cols-1-mobile" style="display:grid;grid-template-columns:1.5fr 1fr;gap:18px;margin-bottom:18px;">
      <div style="background:#fff;border:1px solid #e8e9ee;border-radius:16px;padding:22px;">
        <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:16px;">
          <h3 style="font-size:16px;font-weight:700;color:#13233f;margin:0;">Statistik Pendapatan</h3>
          <select v-model="chartYear" style="padding:4px 8px;border:1px solid #d8dce4;border-radius:6px;font-size:12px;outline:none;background:#fff;cursor:pointer;color:#5d6a82;font-family:inherit;">
            <option value="All">Semua Tahun</option>
            <option v-for="y in availableYears" :key="y" :value="y.toString()">{{ y }}</option>
          </select>
        </div>
        <VueApexCharts :options="areaChartOptions" :series="areaChartSeries" type="area" height="300" />
      </div>
      <div style="background:#fff;border:1px solid #e8e9ee;border-radius:16px;padding:22px;">
        <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:16px;">
          <h3 style="font-size:16px;font-weight:700;color:#13233f;margin:0;">Pendapatan per Kategori</h3>
          <select v-model="chartYear" style="padding:4px 8px;border:1px solid #d8dce4;border-radius:6px;font-size:12px;outline:none;background:#fff;cursor:pointer;color:#5d6a82;font-family:inherit;">
            <option value="All">Semua Tahun</option>
            <option v-for="y in availableYears" :key="y" :value="y.toString()">{{ y }}</option>
          </select>
        </div>
        <div style="display:flex; justify-content:center; align-items:center; height:300px;">
          <VueApexCharts :options="donutChartOptions" :series="donutChartSeries" type="donut" width="300" />
        </div>
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
              <span>Grup</span><span>Tanggal Invoice</span><span>Pax</span><span>Nilai</span><span>Status</span>
            </div>
            <div v-for="(o, idx) in recentOrders" :key="idx" @click="o.onDetail" class="tr-nav table-row-mobile" style="display:grid;grid-template-columns:1.6fr 1fr .7fr 1fr .8fr;gap:12px;padding:14px 22px;border-top:1px solid #f1f2f5;cursor:pointer;align-items:center;">
              <div class="col-full-mobile" style="min-width:0;"><div style="font-size:14px;font-weight:700;color:#13233f;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;">{{ o.group }}</div><div style="font-size:11px;color:#9aa0ad;font-family:'IBM Plex Mono',monospace;">{{ o.no }}</div></div>
              <span class="col-half-mobile" style="font-size:13px;color:#5d6a82;font-family:'IBM Plex Mono',monospace;"><i class="ph ph-calendar" style="font-size:13px;color:#5d6a82;vertical-align:middle;padding-right:4px;"></i>{{ o.dateInv }}</span>
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
        <!-- <div style="background:linear-gradient(135deg,#15294f,#0d1b30);border-radius:16px;padding:22px;color:#fff;">
          <i class="ph-fill ph-note-pencil" style="font-size:26px;color:#c39a4d;"></i>
          <h3 style="font-size:16px;font-weight:700;margin:14px 0 6px;">Input pesanan baru</h3>
          <p style="font-size:13px;color:#aeb8cc;line-height:1.5;margin:0 0 16px;">Buat pesanan grup dan cetak invoice resmi dalam hitungan menit.</p>
          <button @click="goNew" class="tr-btn" style="background:#c39a4d;color:#13233f;font-size:13.5px;font-weight:700;padding:11px 18px;border-radius:10px;border:none;cursor:pointer;width:100%;">+ Buat Pesanan</button>
        </div> -->
      </div>
    </div>
  </div>
</template>
