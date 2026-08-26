import { createRouter, createWebHistory } from 'vue-router'
import Home from '../views/Home.vue'
import Login from '../views/Login.vue'
import Dashboard from '../views/Dashboard.vue'
import ViewOverview from '../components/dashboard/overview/ViewOverview.vue'
import ViewNewOrder from '../components/dashboard/orders/ViewNewOrder.vue'
import ViewOrderList from '../components/dashboard/orders/ViewOrderList.vue'
import ViewInvoice from '../components/dashboard/orders/ViewInvoice.vue'
import ViewOrderDetail from '../components/dashboard/orders/ViewOrderDetail.vue'
import ViewReport from '../components/dashboard/reports/ViewReport.vue'
import ViewSettings from '../components/dashboard/settings/ViewSettings.vue'
import ViewAdmin from '../components/dashboard/admin/ViewAdmin.vue'
import ViewRoles from '../components/dashboard/roles/ViewRoles.vue'
import ViewEditOrder from '../components/dashboard/orders/ViewEditOrder.vue'
import { useAuthStore } from '../stores/authStore'

const routes = [
  {
    path: '/',
    name: 'Home',
    component: Home
  },
  {
    path: '/login',
    name: 'Login',
    component: Login,
    meta: { guest: true }
  },
  {
    path: '/',
    name: 'Dashboard',
    component: Dashboard,
    meta: { requiresAuth: true },
    children: [
      {
        path: 'dashboard',
        name: 'ViewOverview',
        component: ViewOverview,
        meta: { permission: 'dashboard.view' }
      },
      {
        path: 'orders',
        name: 'ViewOrderList',
        component: ViewOrderList,
        meta: { permission: 'orders.view' }
      },
      {
        path: 'orders/new',
        name: 'ViewNewOrder',
        component: ViewNewOrder,
        meta: { permission: 'orders.create' }
      },
      {
        path: 'orders/invoice/:id(.*)',
        name: 'ViewInvoice',
        component: ViewInvoice,
        meta: { permission: 'orders.view' }
      },
      {
        path: 'orders/detail/:id(.*)',
        name: 'ViewOrderDetail',
        component: ViewOrderDetail,
        meta: { permission: 'orders.view' }
      },
      {
        path: 'orders/edit/:id(.*)',
        name: 'ViewEditOrder',
        component: ViewEditOrder,
        meta: { permission: 'orders.update' }
      },
      {
        path: 'reports',
        name: 'ViewReport',
        component: ViewReport,
        meta: { permission: 'reports.view' }
      },
      {
        path: 'admins',
        name: 'ViewAdmin',
        component: ViewAdmin,
        meta: { permission: 'admins.view' }
      },
      {
        path: 'roles',
        name: 'ViewRoles',
        component: ViewRoles,
        meta: { permission: 'roles.view' }
      },
      {
        path: 'settings',
        name: 'ViewSettings',
        component: ViewSettings,
        meta: { permission: 'settings.view' }
      },
      {
        path: '',
        redirect: '/dashboard'
      }
    ]
  }
]

const router = createRouter({
  history: createWebHistory(),
  routes,
  scrollBehavior(to, from, savedPosition) {
    if (to.hash) {
      return { el: to.hash, behavior: 'smooth' }
    }
    return { top: 0 }
  }
})

router.beforeEach(async (to, from, next) => {
  const token = localStorage.getItem('auth_token') || sessionStorage.getItem('auth_token')

  if (to.meta.requiresAuth && !token) {
    return next({ name: 'Login' })
  }

  if (to.meta.guest && token) {
    return next({ name: 'Dashboard' })
  }

  // Permission guard
  if (to.meta.permission && token) {
    const auth = useAuthStore()

    // Fetch user data if not loaded yet (page refresh)
    if (!auth.user) {
      await auth.fetchUser()
    }

    if (auth.user && !auth.hasPermission(to.meta.permission)) {
      return next({ name: 'ViewOverview' })
    }
  }

  next()
})

const routeTitles = {
  Home: 'Tourosa Travel',
  Login: 'Masuk — Tourosa Travel',
  ViewOverview: 'Dashboard — Tourosa Travel',
  ViewNewOrder: 'Buat Pesanan — Tourosa Travel',
  ViewOrderList: 'Daftar Pesanan — Tourosa Travel',
  ViewOrderDetail: 'Detail Pesanan — Tourosa Travel',
  ViewInvoice: 'Invoice — Tourosa Travel',
  ViewEditOrder: 'Edit Pesanan — Tourosa Travel',
  ViewReport: 'Laporan — Tourosa Travel',
  ViewAdmin: 'Admin — Tourosa Travel',
  ViewRoles: 'Roles & Permissions — Tourosa Travel',
  ViewSettings: 'Pengaturan — Tourosa Travel',
}

router.afterEach((to) => {
  document.title = routeTitles[to.name] || 'Tourosa Travel'
})

export default router
