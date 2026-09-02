<script setup>
import { computed, ref, watch } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import { useQuery } from '@tanstack/vue-query'
import { useDashboardStore } from '../stores/dashboardStore'
import { useAuthStore } from '../stores/authStore'
import { dashboardService } from '../services/dashboardService'
import { useDashboardData } from '../composables/useDashboardData'
import DashSidebar from '../components/dashboard/layout/DashSidebar.vue'
import DashTopbar from '../components/dashboard/layout/DashTopbar.vue'
const store = useDashboardStore()
const auth = useAuthStore()
const { fmtDate } = useDashboardData()
const route = useRoute()
const router = useRouter()
const { data } = useQuery({
  queryKey: ['dashboard'],
  queryFn: dashboardService.getDashboardData
})


watch(data, (newVal) => {
  if (newVal) {
    store.orders = newVal.orders || []
    store.catalog = newVal.catalog || []
    store.orderInfos = newVal.order_infos || []
    store.testimonials = newVal.testimonials || []
    const s = newVal.site || {}
    store.site = {
      waNumber: s.waNumber || '6281200000000',
      email: s.email || 'halo@tourosa.id',
      address: s.address || 'Jakarta, Indonesia',
      tagline: s.tagline || 'Tiket pesawat, hotel, group tour, hingga gathering korporat.',
      stats: s.stats || [{ n: '12+', l: 'Tahun pengalaman' }, { n: '800+', l: 'Grup diberangkatkan' }, { n: '50+', l: 'Destinasi' }],
      clients: s.clients || [],
      bankAccounts: s.bankAccounts || []
    }
    // Resolve active invoice from route param or refresh existing
    const routeId = route.params.id ? decodeURIComponent(route.params.id) : null
    if (routeId) {
      if (route.name === 'ViewEditOrder') {
        store.findAndLoadEditForm(routeId)
      } else {
        store.findOrderById(routeId)
      }
    } else if (store.activeInvoice) {
      const match = store.orders.find(o => o.no === store.activeInvoice.no)
      if (match) store.setActiveInvoice(match)
    }
  }
}, { immediate: true })

const orders = computed(() => data.value?.orders || [])
const catalog = computed(() => data.value?.catalog || [])
const customers = computed(() => data.value?.customers || [])
const orderInfos = computed(() => data.value?.order_infos || [])
const testimonials = computed(() => data.value?.testimonials || [])
const site = computed(() => {
  const s = data.value?.site || {}
  return {
    waNumber: s.waNumber || '6281200000000',
    email: s.email || 'halo@tourosa.id',
    address: s.address || 'Jakarta, Indonesia',
    tagline: s.tagline || 'Tiket pesawat, hotel, group tour, hingga gathering korporat.',
    stats: s.stats || [{ n: '12+', l: 'Tahun pengalaman' }, { n: '800+', l: 'Grup diberangkatkan' }, { n: '50+', l: 'Destinasi' }],
    clients: s.clients || [],
    bankAccounts: s.bankAccounts || []
  }
})
// Share fetched data to child views via store
store.setQueryData({ orders, catalog, site, testimonials, customers, orderInfos })
// Load user profile if not already loaded
if (!auth.user) {
  auth.fetchUser()
}
const isSidebarOpen = ref(false)
const mainScroll = ref(null)
const allNavDefs = [
  { key: 'overview', label: 'Dashboard', icon: 'ph-squares-four', route: '/dashboard', permission: 'dashboard.view' },
  { key: 'order-list', label: 'Daftar Pesanan', icon: 'ph-list-checks', route: '/orders', permission: 'orders.view' },
  { key: 'order-info', label: 'Informasi Pesanan', icon: 'ph-users-three', route: '/orders/info', permission: 'orders.view' },
  { key: 'report', label: 'Laporan', icon: 'ph-chart-bar', route: '/reports', permission: 'reports.view' },
  { key: 'settings', label: 'Pengaturan', icon: 'ph-gear', route: '/settings', permission: 'settings.view' },
]
// Map route key → expected route.name
const routeNameMap = {
  'overview': 'ViewOverview',
  'new-order': 'ViewNewOrder',
  'order-list': 'ViewOrderList',
  'order-info': 'ViewOrderInfo',
  'report': 'ViewReport',
  'admin': 'ViewAdmin',
  'roles': 'ViewRoles',
  'settings': 'ViewSettings',
}
const navDefs = computed(() => {
  const items = allNavDefs.filter(n => !n.permission || auth.hasPermission(n.permission))

  // Admin menu — only if user can view admins
  if (auth.hasPermission('admins.view')) {
    items.splice(5, 0, { key: 'admin', label: 'Admin', icon: 'ph-users-three', route: '/admins' })
  }
  // Roles menu — only if user can view roles
  if (auth.hasPermission('roles.view')) {
    const insertIdx = auth.hasPermission('admins.view') ? 6 : 5
    items.splice(insertIdx, 0, { key: 'roles', label: 'Roles & Permissions', icon: 'ph-shield-star', route: '/roles' })
  }

  return items
})
const orderSubRoutes = ['ViewOrderDetail', 'ViewInvoice', 'ViewEditOrder']
const navItems = computed(() => {
  return navDefs.value.map(n => {
    const active = n.key === 'order-list'
      ? orderSubRoutes.includes(route.name) || route.name === routeNameMap[n.key]
      : route.name === routeNameMap[n.key]
    return {
      key: n.key, label: n.label, icon: n.icon,
      route: n.route,
      bg: active ? 'rgba(195,154,77,.15)' : 'transparent',
      color: active ? '#f0d79a' : '#aab3c4'
    }
  })
})
const pageMeta = {
  ViewOverview: ['Dashboard', 'Pantau seluruh pesanan grup dalam satu layar.'],
  ViewNewOrder: ['Buat Pesanan', 'Input rincian perjalanan grup dan hasilkan invoice.'],
  ViewOrderList: ['Daftar Pesanan', 'Seluruh pemesanan grup yang tercatat.'],
  ViewOrderInfo: ['Informasi Pesanan', 'Data informasi pemesan, grup, PIC, dan kontak.'],
  ViewOrderDetail: ['Detail Pesanan', 'Rincian pesanan & estimasi profit (internal).'],
  ViewReport: ['Laporan', 'Ringkasan pendapatan, modal, dan profit.'],
  ViewAdmin: ['Admin', 'Kelola akun admin & peran.'],
  ViewRoles: ['Roles & Permissions', 'Kelola role, hak akses, dan pengaturan peran admin.'],
  ViewSettings: ['Pengaturan', 'Kelola konten website, kategori, dan vendor.'],
  ViewInvoice: ['Invoice', 'Pratinjau dan cetak invoice resmi.'],
  ViewEditOrder: ['Edit Pesanan', 'Ubah rincian perjalanan grup.'],
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
      <DashTopbar
        :page-title="pm[0]"
        :page-sub="pm[1]"
        :today-f="todayF"
        :go-new="() => router.push('/orders/new')"
        @toggle-sidebar="isSidebarOpen = !isSidebarOpen"
      />

      <div ref="mainScroll" class="dash-scroll-area" style="flex:1;overflow-y:auto;position:relative;">
        <RouterView :orders="orders" :catalog="catalog" :site="site" />
      </div>
    </main>
  </div>
</template>