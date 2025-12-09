import { defineStore } from 'pinia'
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
  },
});