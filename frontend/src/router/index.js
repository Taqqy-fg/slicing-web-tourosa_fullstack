import { createRouter, createWebHistory } from 'vue-router'
import Home from '../views/Home.vue'
import Dashboard from '../views/Dashboard.vue'
import ViewOverview from '../components/dashboard/ViewOverview.vue'
import ViewNewOrder from '../components/dashboard/ViewNewOrder.vue'
import ViewOrderList from '../components/dashboard/ViewOrderList.vue'
import ViewInvoice from '../components/dashboard/ViewInvoice.vue'
import ViewOrderDetail from '../components/dashboard/ViewOrderDetail.vue'
import ViewReport from '../components/dashboard/ViewReport.vue'
import ViewSettings from '../components/dashboard/ViewSettings.vue'

const routes = [
  {
    path: '/',
    name: 'Home',
    component: Home
  },
  {
    path: '/dashboard',
    name: 'Dashboard',
    component: Dashboard,
    redirect: '/dashboard/overview',
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
        path: 'invoice',
        name: 'ViewInvoice',
        component: ViewInvoice
      },
      {
        path: 'order-detail',
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

export default router
