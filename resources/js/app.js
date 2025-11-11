import { createApp } from 'vue'
import App from './App.vue'
import apiClient from './services/apiClient'
import 'flowbite'
import '../css/app.css'

const app = createApp(App)
app.config.globalProperties.$http = apiClient
app.mount('#app')
