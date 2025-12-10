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
    applyAuthPayload(payload) {
      if (!payload?.token) {
        throw new Error('Token non presente nella risposta')
      }

      const userPayload = payload?.user?.data ?? payload?.user ?? null

      this.setToken(payload.token)
      this.setUser(userPayload)

      return true
    },
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

        const data = await response.json()

        if (!response.ok) {
          return false
        }

        return this.applyAuthPayload(data)
      } catch (error) {
        console.error('Errore di login:', error)
        return false
      }
    },
    async registerEmployee(payload) {
      try {
        const response = await apiPost(ENDPOINTS.registerEmployee, payload)
        const data = await response.json()

        if (!response.ok) {
          throw new Error(data?.message || 'Registrazione non riuscita')
        }

        return this.applyAuthPayload(data)
      } catch (error) {
        console.error('Errore durante la registrazione:', error)
        throw error
      }
    },
    loginFromRegistration(token, user) {
      return this.applyAuthPayload({ token, user })
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
