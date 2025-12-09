<script setup>
import { ref } from 'vue'

const payrolls = ref([
  {
    id: 1,
    period: 'Gennaio 2024',
    amount: '€1.800,00',
    fileName: 'busta_paga_gennaio_2024.pdf',
  },
  {
    id: 2,
    period: 'Febbraio 2024',
    amount: '€1.820,00',
    fileName: 'busta_paga_febbraio_2024.pdf',
  },
  {
    id: 3,
    period: 'Marzo 2024',
    amount: '€1.790,00',
    fileName: 'busta_paga_marzo_2024.pdf',
  },
])

const downloadPayroll = (payroll) => {
  console.log('Download richiesto per', payroll.fileName)
}
</script>

<template>
  <section class="space-y-4">
    <div class="rounded-xl border border-slate-200 bg-white p-4 shadow-sm">
      <h2 class="text-lg font-semibold text-slate-900">Buste paga</h2>
      <p class="text-sm text-slate-600">
        Qui troverai tutte le tue buste paga. La struttura è pronta per collegarsi alle API reali nel prossimo step.
      </p>
    </div>

    <div class="space-y-3">
      <article
        v-for="payroll in payrolls"
        :key="payroll.id"
        class="flex items-center justify-between rounded-xl border border-slate-200 bg-white p-4 shadow-sm"
      >
        <div>
          <p class="text-sm font-semibold text-slate-900">{{ payroll.period }}</p>
          <p class="text-xs text-slate-600">{{ payroll.amount }}</p>
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
