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
  },
})
