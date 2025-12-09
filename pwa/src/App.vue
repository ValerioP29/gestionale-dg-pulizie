<script setup>
import { computed, onMounted } from 'vue'
import { useRoute, RouterView } from 'vue-router'
import { useConnectivityStore } from './stores/connectivity'

const route = useRoute()
const connectivityStore = useConnectivityStore()

const wrapperClasses = computed(() =>
  route.name === 'login' ? 'mx-auto max-w-md p-6' : ''
)

onMounted(() => {
  connectivityStore.startTracking()
})
</script>

<template>
  <div class="min-h-screen bg-slate-50 text-slate-900">
    <RouterView v-slot="{ Component }">
      <div :class="wrapperClasses">
        <component :is="Component" />
      </div>
    </RouterView>
  </div>
</template>
