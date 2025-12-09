import { defineStore } from 'pinia'
import { apiGet, apiPost } from '../http'
import { ENDPOINTS } from '../endpoints'
import { addPunchToQueue, clearPunchFromQueue, getQueuedPunches } from '../utils/offlineQueue'
import { showSuccess, showWarning } from '../utils/toast'

let onlineListenerRegistered = false

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
      const payload = {
        type,
        device_latitude: latitude,
        device_longitude: longitude,
        device_accuracy_m: accuracy,
      }

      if (typeof navigator !== 'undefined' && navigator.onLine === false) {
        this.queuePunch(payload)
        return { success: true, queued: true }
      }

      try {
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
        this.queuePunch(payload)
        return { success: true, queued: true }
      }
    },

    queuePunch(payload) {
      addPunchToQueue({ ...payload, queued_at: new Date().toISOString() })
      showWarning('Sei offline: timbratura salvata e verrà sincronizzata appena torni online.')
    },

    async flushOfflinePunches() {
      const queuedPunches = getQueuedPunches()

      if (!queuedPunches.length) return { synced: 0 }

      let synced = 0

      for (const punch of queuedPunches) {
        try {
          const response = await apiPost(ENDPOINTS.punch, {
            type: punch.type,
            device_latitude: punch.device_latitude,
            device_longitude: punch.device_longitude,
            device_accuracy_m: punch.device_accuracy_m,
          })

          if (!response.ok) {
            console.error('Errore nella sincronizzazione della timbratura offline')
            continue
          }

          const json = await response.json()
          const data = json.data
          this.assignedSite = data?.assigned_site ?? this.assignedSite
          this.activeSession = data?.session ?? this.activeSession

          clearPunchFromQueue(punch.id)
          synced += 1
        } catch (error) {
          console.error('Errore di rete durante la sincronizzazione offline:', error)
          break
        }
      }

      if (synced > 0) {
        showSuccess('Timbrature offline sincronizzate')
      }

      return { synced }
    },

    setupOfflineSync() {
      if (onlineListenerRegistered) return

      const handleOnline = () => {
        this.flushOfflinePunches()
      }

      window.addEventListener('online', handleOnline)
      onlineListenerRegistered = true
    },
  },
})
