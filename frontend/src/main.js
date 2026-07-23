import { createApp } from 'vue'
import { createPinia } from 'pinia'

import App from './App.vue'
import router from './router'
import { useAuthStore } from '@/stores/auth'
import { canDirective } from '@/directives/can'
import './style.css'

const app = createApp(App)
const pinia = createPinia()

app.use(pinia)
app.use(router)

const auth = useAuthStore(pinia)
app.directive('can', canDirective(auth))

// Session expired on the server (401 from any request): clear local
// state and send the user back to the login screen.
window.addEventListener('auth:unauthenticated', () => {
  auth.forget()

  if (router.currentRoute.value.meta.requiresAuth) {
    router.push({ name: 'login', query: { reason: 'session' } })
  }
})

app.mount('#app')
