import { defineStore } from 'pinia'
import { apiGet, apiPost } from '../http'
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
          this.assignedSite = null;
          this.activeSession = null;
          return;
        }

        const json = await response.json();
        const payload = json.data;
        this.assignedSite = payload?.assigned_site ?? null;
        this.activeSession = payload?.session ?? null;

      } catch (error) {
        console.error('Errore nel caricamento della sessione corrente:', error);
        this.assignedSite = null;
        this.activeSession = null;
      }
    },

    async punch(type, { latitude, longitude, accuracy }) {
      try {
        const payload = {
          type,
          device_latitude: latitude,
          device_longitude: longitude,
          device_accuracy_m: accuracy,
        }

        const response = await apiPost(ENDPOINTS.punch, payload)

        if (!response.ok) {
          return { success: false, warnings: [] }
        }

        const json = await response.json()
        const data = json.data

        this.assignedSite = data?.assigned_site ?? null
        this.activeSession = data?.session ?? null

        return { success: true, warnings: data?.warnings ?? [] }
      } catch (error) {
        console.error('Errore durante la timbratura:', error)
        return { success: false, warnings: [] }
      }

      return { success: false, warnings: [] }
    },
  },
})
