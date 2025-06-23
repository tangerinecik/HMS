import './assets/main.css'

import { createApp } from 'vue'
import { createPinia } from 'pinia'

import App from './App.vue'
import router from './router'
import { useAuthStore } from './stores/auth'

const app = createApp(App)
const pinia = createPinia()

app.use(pinia)

// Initialize auth store before app mounts
const authStore = useAuthStore()
authStore.initialize().finally(() => {
  app.use(router)
  app.mount('#app')
})
