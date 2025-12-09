import { createRouter, createWebHistory } from 'vue-router'
import Login from '../modules/auth/views/Login.vue'
import Home from '../modules/punch/views/Home.vue'

const routes = [
  { path: '/', redirect: '/login' },
  { path: '/login', name: 'login', component: Login },
  { path: '/home', name: 'home', component: Home },
]

const router = createRouter({
  history: createWebHistory(),
  routes,
})

export default router;