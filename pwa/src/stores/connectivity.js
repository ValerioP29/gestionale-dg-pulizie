import { defineStore } from 'pinia'

const defaultOnline = typeof navigator !== 'undefined' ? navigator.onLine : true

export const useConnectivityStore = defineStore('connectivity', {
  state: () => ({
    isOnline: defaultOnline,
    _isTracking: false,
  }),
  actions: {
    startTracking() {
      if (this._isTracking) return

      const updateStatus = () => {
        this.isOnline = typeof navigator !== 'undefined' ? navigator.onLine : true
      }

      window.addEventListener('online', updateStatus)
      window.addEventListener('offline', updateStatus)

      updateStatus()
      this._isTracking = true
    },
  },
})
