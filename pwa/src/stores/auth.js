import { defineStore } from 'pinia'
import router from '../router'
import { apiGet, apiPost } from '../http'
import { ENDPOINTS } from '../endpoints'
import { clearToken as clearStoredToken, getToken, setToken as persistToken } from '../utils/storage'

export const useAuthStore = defineStore('auth', {
  state: () => ({
    token: getToken(),
    user: null,
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
    setUser(user) {
      this.user = user
    },
    clearUser() {
      this.user = null
    },
    async login(email, password) {
      try {
        const response = await apiPost(ENDPOINTS.login, { email, password })

        if (!response.ok) {
          return false
        }

        const data = await response.json()

        if (!data?.token) {
          return false
        }

        this.setToken(data.token)
        this.setUser(data.user ?? null)

        return true
      } catch (error) {
        console.error('Errore di login:', error)
        return false
      }
    },
    async fetchUser() {
      if (!this.token) {
        this.clearUser()
        return null
      }

      const response = await apiGet(ENDPOINTS.me)

      if (!response.ok) {
        this.clearUser()
        throw new Error('Impossibile recuperare i dati utente')
      }

      const data = await response.json()
      this.setUser(data?.data ?? null)

      return this.user
    },
    async logout() {
      try {
        await apiPost(ENDPOINTS.logout)
      } catch (error) {
        console.error('Errore durante il logout', error)
      }

      this.clearToken()
      this.clearUser()
      await router.push('/login')
    },
  },
})
