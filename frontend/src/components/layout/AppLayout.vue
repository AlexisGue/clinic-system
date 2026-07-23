<script setup>
import { ref } from 'vue'
import { useRouter } from 'vue-router'
import { useAuthStore } from '@/stores/auth'
import AppSidebar from './AppSidebar.vue'

const auth = useAuthStore()
const router = useRouter()
const sidebarOpen = ref(false)
const loggingOut = ref(false)

async function logout() {
  loggingOut.value = true
  try {
    await auth.logout()
  } finally {
    loggingOut.value = false
    router.push({ name: 'login' })
  }
}

const initials = () => {
  const name = auth.user?.name || '?'
  return name
    .split(/\s+/)
    .slice(0, 2)
    .map((p) => p[0]?.toUpperCase() || '')
    .join('')
}
</script>

<template>
  <div class="flex min-h-screen">
    <AppSidebar :open="sidebarOpen" @close="sidebarOpen = false" />

    <div class="flex min-w-0 flex-1 flex-col">
      <header class="sticky top-0 z-10 flex h-16 items-center justify-between border-b border-brand-100/80 bg-white/85 px-4 backdrop-blur-md lg:px-6">
        <div class="flex items-center gap-3">
          <button
            class="rounded-xl p-2 text-slate-500 transition hover:bg-brand-50 lg:hidden"
            aria-label="Abrir menú"
            @click="sidebarOpen = true"
          >
            <svg class="h-5 w-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
              <path stroke-linecap="round" d="M4 6h16M4 12h16M4 18h16" />
            </svg>
          </button>
          <div class="hidden sm:block">
            <p class="text-xs font-medium uppercase tracking-wider text-brand-600">Clínica</p>
            <p class="text-sm font-semibold text-slate-800">Panel de atención</p>
          </div>
        </div>

        <div class="flex items-center gap-3">
          <div class="hidden text-right sm:block">
            <p class="text-sm font-semibold text-slate-800">{{ auth.user?.name }}</p>
            <p class="text-xs text-slate-400">{{ auth.user?.email }}</p>
          </div>
          <div class="flex h-10 w-10 items-center justify-center rounded-xl bg-brand-100 font-display text-sm font-semibold text-brand-800">
            {{ initials() }}
          </div>
          <button
            :disabled="loggingOut"
            class="btn-secondary !px-3 !py-2 text-xs"
            @click="logout"
          >
            {{ loggingOut ? 'Saliendo…' : 'Salir' }}
          </button>
        </div>
      </header>

      <main class="flex-1 p-4 lg:p-8">
        <div class="page-shell mx-auto max-w-7xl">
          <RouterView />
        </div>
      </main>
    </div>
  </div>
</template>
