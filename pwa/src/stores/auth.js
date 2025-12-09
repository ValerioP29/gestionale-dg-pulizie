import { defineStore } from 'pinia'
import router from '../router'
import { apiPost } from '../http'
import { ENDPOINTS } from '../endpoints'
import { clearToken as clearStoredToken, getToken, setToken as persistToken } from '../utils/storage'

export const useAuthStore = defineStore('auth', {
  state: () => ({
    token: getToken(),
  }),
  actions: {
    setToken(token) {
      this.token = token
      persistToken(token)
    },
    clearToken() {
      this.token = null
      clearStoredToken()
    },
    async login(email, password) {
      try {
        const response = await apiPost(ENDPOINTS.login, { email, password })

        if (!response.ok) {
          return false
        }

        const data = await response.json()

        if (data?.token) {
          this.setToken(data.token)
          return true
        }

        return false
      } catch (error) {
        console.error('Errore di login:', error)
        return false
      }
    },
    async logout() {
      this.clearToken()
      await router.push('/login')
    },
  },
})
