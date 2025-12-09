import { defineStore } from 'pinia'
import { apiGet } from '../http'
import { ENDPOINTS } from '../endpoints'

export const useSessionStore = defineStore('session', {
  state: () => ({
    assignedSite: null,
    activeSession: null,
  }),
  actions: {
    async loadCurrent() {
      try {
        const response = await apiGet(ENDPOINTS.current)

        if (!response.ok) {
          this.assignedSite = null
          this.activeSession = null
          return
        }

        const data = await response.json()
        this.assignedSite = data?.assigned_site ?? null
        this.activeSession = data?.session ?? null
      } catch (error) {
        console.error('Errore nel caricamento della sessione corrente:', error)
        this.assignedSite = null
        this.activeSession = null
      }
    },
  },
})
