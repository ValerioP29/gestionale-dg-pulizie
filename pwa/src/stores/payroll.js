import { defineStore } from 'pinia'
import { apiGet } from '../http'
import { ENDPOINTS } from '../endpoints'

export const usePayrollStore = defineStore('payroll', {
  state: () => ({
    payrolls: [],
    loading: false,
    error: null,
  }),
  actions: {
    async loadPayrolls() {
      this.loading = true
      this.error = null

      try {
        const response = await apiGet(ENDPOINTS.payrollList)

        if (!response.ok) {
          this.payrolls = []
          this.error = 'Impossibile caricare le buste paga'
          return
        }

        const json = await response.json()
        this.payrolls = json?.data || []
      } catch (error) {
        console.error('Errore durante il caricamento delle buste paga:', error)
        this.payrolls = []
        this.error = 'Errore durante il caricamento delle buste paga'
      } finally {
        this.loading = false
      }
    },

    async downloadPayroll(id, filename) {
      try {
        const response = await apiGet(ENDPOINTS.payrollDownload(id))

        if (!response.ok) {
          return { success: false, message: 'Errore durante il download' }
        }

        const blob = await response.blob()
        const url = window.URL.createObjectURL(blob)

        const link = document.createElement('a')
        link.href = url
        link.download = filename || 'documento.pdf'
        link.click()

        window.URL.revokeObjectURL(url)

        return { success: true }
      } catch (error) {
        console.error('Errore durante il download della busta paga:', error)
        return { success: false, message: 'Errore durante il download' }
      }
    },
  },
})
