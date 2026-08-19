import { createRouter, createWebHistory } from 'vue-router'
import Home from '../views/Home.vue'
import Login from '../views/Login.vue'
import Dashboard from '../views/Dashboard.vue'
import ViewOverview from '../components/dashboard/ViewOverview.vue'
import ViewNewOrder from '../components/dashboard/ViewNewOrder.vue'
import ViewOrderList from '../components/dashboard/ViewOrderList.vue'
import ViewInvoice from '../components/dashboard/ViewInvoice.vue'
import ViewOrderDetail from '../components/dashboard/ViewOrderDetail.vue'
import ViewReport from '../components/dashboard/ViewReport.vue'
import ViewSettings from '../components/dashboard/ViewSettings.vue'
import ViewEditOrder from '../components/dashboard/ViewEditOrder.vue'

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
    path: '/dashboard',
    name: 'Dashboard',
    component: Dashboard,
    redirect: '/dashboard/overview',
    meta: { requiresAuth: true },
    children: [
      {
        path: 'overview',
        name: 'ViewOverview',
        component: ViewOverview
      },
      {
        path: 'new-order',
        name: 'ViewNewOrder',
        component: ViewNewOrder
      },
      {
        path: 'order-list',
        name: 'ViewOrderList',
        component: ViewOrderList
      },
      {
        path: 'invoice/:id(.*)',
        name: 'ViewInvoice',
        component: ViewInvoice
      },
      {
        path: 'order-detail/:id(.*)',
        name: 'ViewOrderDetail',
        component: ViewOrderDetail
      },
      {
        path: 'report',
        name: 'ViewReport',
        component: ViewReport
      },
      {
        path: 'settings',
        name: 'ViewSettings',
        component: ViewSettings
      },
      {
        path: 'edit-order/:id(.*)',
        name: 'ViewEditOrder',
        component: ViewEditOrder
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
