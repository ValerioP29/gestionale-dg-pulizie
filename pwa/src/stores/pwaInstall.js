import { defineStore } from 'pinia'

const DISMISS_KEY = 'pwa-install-banner-dismissed'

const isStandalone = () => {
  if (typeof window === 'undefined') return false

  return window.matchMedia('(display-mode: standalone)').matches || window.navigator.standalone
}

export const usePwaInstallStore = defineStore('pwaInstall', {
  state: () => ({
    deferredPrompt: null,
    installAvailable: false,
    installed: false,
    dismissed: false,
    initialized: false,
  }),
  getters: {
    canInstallPwa: (state) => state.installAvailable && !state.installed,
    showInstallBanner: (state) => state.installAvailable && !state.installed && !state.dismissed,
  },
  actions: {
    initialize() {
      if (this.initialized || typeof window === 'undefined') return

      this.initialized = true
      this.dismissed = localStorage.getItem(DISMISS_KEY) === 'true'
      this.installed = isStandalone()

      window.addEventListener('beforeinstallprompt', (event) => {
        event.preventDefault()
        this.deferredPrompt = event
        this.installAvailable = true
      })

      window.addEventListener('appinstalled', () => {
        this.installed = true
        this.installAvailable = false
        this.deferredPrompt = null
        localStorage.removeItem(DISMISS_KEY)
      })
    },
    async triggerInstall() {
      if (!this.deferredPrompt) return

      this.deferredPrompt.prompt()
      await this.deferredPrompt.userChoice

      this.deferredPrompt = null
      this.installAvailable = false
    },
    dismissBanner() {
      this.dismissed = true
      localStorage.setItem(DISMISS_KEY, 'true')
    },
  },
})
