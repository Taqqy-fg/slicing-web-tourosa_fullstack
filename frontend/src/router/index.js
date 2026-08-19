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
import ViewEditOrder from '../components/dashboard/orders/ViewEditOrder.vue'

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
        component: ViewOverview
      },
      {
        path: 'orders',
        name: 'ViewOrderList',
        component: ViewOrderList
      },
      {
        path: 'orders/new',
        name: 'ViewNewOrder',
        component: ViewNewOrder
      },
      {
        path: 'orders/invoice/:id(.*)',
        name: 'ViewInvoice',
        component: ViewInvoice
      },
      {
        path: 'orders/detail/:id(.*)',
        name: 'ViewOrderDetail',
        component: ViewOrderDetail
      },
      {
        path: 'orders/edit/:id(.*)',
        name: 'ViewEditOrder',
        component: ViewEditOrder
      },
      {
        path: 'reports',
        name: 'ViewReport',
        component: ViewReport
      },
      {
        path: 'settings',
        name: 'ViewSettings',
        component: ViewSettings
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

router.beforeEach((to, from, next) => {
  const token = localStorage.getItem('auth_token') || sessionStorage.getItem('auth_token')

  if (to.meta.requiresAuth && !token) {
    next({ name: 'Login' })
  } else if (to.meta.guest && token) {
    next({ name: 'Dashboard' })
  } else {
    next()
  }
})

export default router
