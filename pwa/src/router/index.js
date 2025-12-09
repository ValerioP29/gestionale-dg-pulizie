import { createRouter, createWebHistory } from 'vue-router'
import { getToken } from '../utils/storage'
import Login from '../modules/auth/views/Login.vue'
import Home from '../modules/punch/views/Home.vue'

const routes = [
  { path: '/', redirect: '/login' },
  { path: '/login', name: 'login', component: Login },
  { path: '/home', name: 'home', component: Home, meta: { requiresAuth: true } },
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

export default router
