<script setup>
import { computed, ref } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import { useQuery } from '@tanstack/vue-query'
import { useDashboardStore } from '../stores/dashboardStore'
import { useAuthStore } from '../stores/authStore'
import { dashboardService } from '../services/dashboardService'
import { useDashboardData } from '../composables/useDashboardData'
import DashSidebar from '../components/dashboard/DashSidebar.vue'
import DashTopbar from '../components/dashboard/DashTopbar.vue'
const store = useDashboardStore()
const auth = useAuthStore()
const { fmtDate } = useDashboardData()
const route = useRoute()
const router = useRouter()
const { data } = useQuery({
  queryKey: ['dashboard'],
  queryFn: dashboardService.getDashboardData
})
const orders = computed(() => data.value?.orders || [])
const catalog = computed(() => data.value?.catalog || [])
const site = computed(() => {
  const s = data.value?.site || {}
  return {
    waNumber: s.waNumber || '6281200000000',
    email: s.email || 'halo@tourosa.id',
    address: s.address || 'Jakarta, Indonesia',
    tagline: s.tagline || 'Tiket pesawat, hotel, group tour, hingga gathering korporat.',
    stats: s.stats || [{ n: '12+', l: 'Tahun pengalaman' }, { n: '800+', l: 'Grup diberangkatkan' }, { n: '50+', l: 'Destinasi' }],
    clients: s.clients || []
  }
})
// Share fetched data to child views via store
store.setQueryData({ orders, catalog, site })
// Load user profile if not already loaded
if (!auth.user) {
  auth.fetchUser()
}
const isSidebarOpen = ref(false)
const mainScroll = ref(null)
const navDefs = [
  { key: 'overview', label: 'Ringkasan', icon: 'ph-squares-four', route: '/dashboard/overview' },
  { key: 'new-order', label: 'Buat Pesanan', icon: 'ph-note-pencil', route: '/dashboard/new-order' },
  { key: 'order-list', label: 'Daftar Pesanan', icon: 'ph-list-checks', route: '/dashboard/order-list' },
  { key: 'report', label: 'Laporan', icon: 'ph-chart-bar', route: '/dashboard/report' },
  { key: 'settings', label: 'Pengaturan', icon: 'ph-gear', route: '/dashboard/settings' },
]
// Map route key → expected route.name
const routeNameMap = {
  'overview': 'ViewOverview',
  'new-order': 'ViewNewOrder',
  'order-list': 'ViewOrderList',
  'report': 'ViewReport',
  'settings': 'ViewSettings',
}
const navItems = computed(() => {
  const items = navDefs.map(n => {
    const active = route.name === routeNameMap[n.key]
    return {
      key: n.key, label: n.label, icon: n.icon,
      onClick: () => { router.push(n.route); isSidebarOpen.value = false },
      bg: active ? 'rgba(195,154,77,.15)' : 'transparent',
      color: active ? '#f0d79a' : '#aab3c4'
    }
  })
  if (store.activeInvoice && route.name === 'ViewInvoice') {
    items.push({ key: 'invoice', label: 'Invoice', icon: 'ph-receipt', onClick: () => { }, bg: 'rgba(195,154,77,.15)', color: '#f0d79a' })
  }
  if (store.activeInvoice && route.name === 'ViewOrderDetail') {
    items.push({ key: 'order-detail', label: 'Detail Pesanan', icon: 'ph-file-text', onClick: () => { }, bg: 'rgba(195,154,77,.15)', color: '#f0d79a' })
  }
  return items
})
const pageMeta = {
  ViewOverview: ['Ringkasan', 'Pantau seluruh pesanan grup dalam satu layar.'],
  ViewNewOrder: ['Buat Pesanan', 'Input rincian perjalanan grup dan hasilkan invoice.'],
  ViewOrderList: ['Daftar Pesanan', 'Seluruh pemesanan grup yang tercatat.'],
  ViewOrderDetail: ['Detail Pesanan', 'Rincian pesanan & estimasi profit (internal).'],
  ViewReport: ['Laporan', 'Ringkasan pendapatan, modal, dan profit.'],
  ViewSettings: ['Pengaturan', 'Kelola konten website, kategori, dan vendor.'],
  ViewInvoice: ['Invoice', 'Pratinjau dan cetak invoice resmi.'],
}
const pm = computed(() => pageMeta[route.name] || pageMeta.ViewOverview)
const todayF = fmtDate(new Date().toISOString().slice(0, 10))
</script>
<template>
  <div class="dash-shell"
    style="display:flex;position:fixed;top:0;left:0;right:0;bottom:0;background:#f4f5f8;overflow:hidden;">
    <DashSidebar :nav-items="navItems" :is-open="isSidebarOpen" @close="isSidebarOpen = false" />
    <div class="sidebar-backdrop" :class="{ open: isSidebarOpen }" @click="isSidebarOpen = false"></div>
    <main class="dash-main" style="flex:1;min-width:0;display:flex;flex-direction:column;height:100%;">
      <DashTopbar :page-title="pm[0]" :page-sub="pm[1]" :today-f="todayF"
        :go-new="() => router.push('/dashboard/new-order')" @toggle-sidebar="isSidebarOpen = !isSidebarOpen" />
      <div ref="mainScroll" class="dash-scroll-area" style="flex:1;overflow-y:auto;position:relative;">
        <RouterView :orders="orders" :catalog="catalog" :site="site" />
      </div>
    </main>
  </div>
</template>