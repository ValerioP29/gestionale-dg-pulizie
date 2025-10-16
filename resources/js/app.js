import { createApp } from 'vue'
import App from './App.vue'
import axios from 'axios'
import 'flowbite'
import '../css/app.css'

axios.defaults.withCredentials = true
axios.defaults.baseURL = import.meta.env.VITE_API_URL || 'http://localhost'

const app = createApp(App)
app.config.globalProperties.$http = axios
app.mount('#app')
