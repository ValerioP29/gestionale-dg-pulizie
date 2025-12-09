import { createRouter, createWebHistory } from 'vue-router'
import { getToken } from '../utils/storage'
import Login from '../modules/auth/views/Login.vue'
import Home from '../modules/punch/views/Home.vue'
import Profile from '../modules/profile/views/Profile.vue'
import Payroll from '../modules/payroll/views/Payroll.vue'
import MainLayout from '../components/MainLayout.vue'

const DEFAULT_TITLE = 'DG Pulizie'

const routes = [
  { path: '/', redirect: '/login' },
  { path: '/login', name: 'login', component: Login, meta: { requiresAuth: false, title: 'Login' } },
  {
    path: '/',
    component: MainLayout,
    meta: { requiresAuth: true },
    children: [
      { path: 'home', name: 'home', component: Home, meta: { title: 'Home' } },
      { path: 'profile', name: 'profile', component: Profile, meta: { title: 'Profilo' } },
      { path: 'payroll', name: 'payroll', component: Payroll, meta: { title: 'Buste paga' } },
      // altre route future qui
    ],
  },
]

const router = createRouter({
  history: createWebHistory(),
  routes,
})

router.beforeEach((to, from, next) => {
  const token = getToken()

  if (to.path === '/login' && token) {
    return next('/home')
  }

  if (to.meta.requiresAuth !== false && !token && to.path !== '/login') {
    return next('/login')
  }

  return next()
})

router.afterEach((to) => {
  document.title = to.meta?.title || DEFAULT_TITLE
})

export default router
