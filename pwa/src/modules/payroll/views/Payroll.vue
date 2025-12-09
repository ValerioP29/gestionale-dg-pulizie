<script setup>
import { computed, onMounted } from 'vue'
import { usePayrollStore } from '../../../stores/payroll'
import { showError, showSuccess } from '../../../utils/toast'

const payrollStore = usePayrollStore()

const payrolls = computed(() => payrollStore.payrolls)
const loading = computed(() => payrollStore.loading)
const error = computed(() => payrollStore.error)

onMounted(() => {
  payrollStore.loadPayrolls()
})

const downloadPayroll = async (payroll) => {
  const result = await payrollStore.downloadPayroll(payroll.id, payroll.file_name)

  if (result.success) {
    showSuccess('Download avviato')
  } else {
    showError(result.message || 'Errore durante il download')
  }
}
</script>

<template>
  <section class="space-y-4">
    <div class="rounded-xl border border-slate-200 bg-white p-4 shadow-sm">
      <h2 class="text-lg font-semibold text-slate-900">Buste paga</h2>
      <p class="text-sm text-slate-600">
        Consulta e scarica i tuoi cedolini. I dati provengono dalle API protette e sono filtrati sul tuo profilo
        dipendente.
      </p>
    </div>

    <div v-if="loading" class="flex items-center gap-2 rounded-xl border border-slate-200 bg-white p-4 shadow-sm">
      <span class="h-4 w-4 animate-spin rounded-full border-2 border-slate-300 border-t-slate-900" />
      <p class="text-sm text-slate-700">Caricamento delle buste paga in corso...</p>
    </div>

    <div v-else-if="error" class="rounded-xl border border-red-200 bg-red-50 p-4 text-red-800 shadow-sm">
      <p class="text-sm font-semibold">{{ error }}</p>
      <button
        type="button"
        class="mt-3 rounded-lg border border-red-300 px-3 py-2 text-xs font-semibold text-red-800 transition hover:bg-red-100"
        @click="payrollStore.loadPayrolls()"
      >
        Riprova
      </button>
    </div>

    <div
      v-else-if="!payrolls.length"
      class="rounded-xl border border-slate-200 bg-white p-4 text-slate-700 shadow-sm"
    >
      Nessuna busta paga disponibile al momento.
    </div>

    <div v-else class="space-y-3">
      <article
        v-for="payroll in payrolls"
        :key="payroll.id"
        class="flex items-center justify-between rounded-xl border border-slate-200 bg-white p-4 shadow-sm"
      >
        <div>
          <p class="text-sm font-semibold text-slate-900">{{ payroll.period }}</p>
          <p class="text-xs text-slate-600">{{ payroll.amount || 'Importo non disponibile' }}</p>
          <p v-if="payroll.file_name" class="text-xs text-slate-500">{{ payroll.file_name }}</p>
        </div>

        <button
          type="button"
          class="rounded-lg border border-slate-300 px-3 py-2 text-xs font-semibold text-slate-800 transition hover:bg-slate-100"
          @click="downloadPayroll(payroll)"
        >
          Scarica PDF
        </button>
      </article>
    </div>
  </section>
</template>
