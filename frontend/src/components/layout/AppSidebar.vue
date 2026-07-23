<script setup>
import { computed } from 'vue'
import { useAuthStore } from '@/stores/auth'

defineProps({
  open: { type: Boolean, default: false },
})

defineEmits(['close'])

const auth = useAuthStore()

const primaryNav = computed(() =>
  [
    {
      name: 'Dashboard',
      to: { name: 'dashboard' },
      permission: 'dashboard.view',
      icon: 'M3 12l9-9 9 9M5 10v10a1 1 0 001 1h4v-6h4v6h4a1 1 0 001-1V10',
    },
    {
      name: 'Pacientes',
      to: { name: 'patients.index' },
      permission: 'patients.view',
      icon: 'M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0z',
    },
    {
      name: 'Médicos',
      to: { name: 'doctors.index' },
      permission: 'doctors.view',
      icon: 'M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z',
    },
    {
      name: 'Citas',
      to: { name: 'appointments.index' },
      permission: 'appointments.view',
      icon: 'M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z',
    },
    {
      name: 'Consultas',
      to: { name: 'consultations.index' },
      permission: 'consultations.view',
      icon: 'M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z',
    },
    {
      name: 'Recetas',
      to: { name: 'prescriptions.index' },
      permission: 'prescriptions.view',
      icon: 'M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-6 9l2 2 4-4',
    },
    {
      name: 'Reportes',
      to: { name: 'reports.index' },
      permission: 'reports.view',
      icon: 'M9 17v-2m3 2v-4m3 4v-6m2 10H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z',
    },
    {
      name: 'Configuración',
      to: { name: 'settings.index' },
      permission: 'settings.view',
      icon: 'M12 6V4m0 2a2 2 0 100 4m0-4a2 2 0 110 4m-6 8a2 2 0 100-4m0 4a2 2 0 110-4m0 4v2m0-6V4m6 6v10m6-2a2 2 0 100-4m0 4a2 2 0 110-4m0 4v2m0-6V4',
    },
  ].filter((item) => !item.permission || auth.can(item.permission)),
)

const adminNav = computed(() =>
  [
    {
      name: 'Usuarios',
      to: { name: 'users.index' },
      permission: 'users.view',
      icon: 'M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z',
    },
  ].filter((item) => !item.permission || auth.can(item.permission)),
)
</script>

<template>
  <div
    v-if="open"
    class="fixed inset-0 z-20 bg-ink-950/50 backdrop-blur-sm lg:hidden"
    @click="$emit('close')"
  />

  <aside
    class="fixed inset-y-0 left-0 z-30 flex w-64 -translate-x-full flex-col border-r border-white/5 bg-gradient-to-b from-ink-900 to-ink-950 transition-transform duration-300 lg:static lg:translate-x-0"
    :class="{ 'translate-x-0': open }"
  >
    <div class="flex h-16 shrink-0 items-center gap-3 border-b border-white/5 px-5">
      <div class="flex h-9 w-9 items-center justify-center rounded-xl bg-brand-600 font-display text-sm font-bold text-white shadow-md shadow-brand-900/30">
        <svg class="h-5 w-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
          <path stroke-linecap="round" stroke-linejoin="round" d="M4.318 6.318a4.5 4.5 0 000 6.364L12 20.364l7.682-7.682a4.5 4.5 0 00-6.364-6.364L12 7.636l-1.318-1.318a4.5 4.5 0 00-6.364 0z" />
        </svg>
      </div>
      <div>
        <p class="font-display text-sm font-semibold text-white">Clinic</p>
        <p class="text-[11px] font-medium uppercase tracking-wider text-brand-300">System</p>
      </div>
    </div>

    <nav class="flex-1 space-y-0.5 overflow-y-auto p-3">
      <p class="mb-2 px-3 text-[10px] font-semibold uppercase tracking-wider text-slate-500">Clínica</p>
      <RouterLink
        v-for="item in primaryNav"
        :key="item.name"
        :to="item.to"
        class="nav-link"
        active-class="nav-link-active"
        @click="$emit('close')"
      >
        <svg class="h-5 w-5 shrink-0 opacity-80" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24">
          <path stroke-linecap="round" stroke-linejoin="round" :d="item.icon" />
        </svg>
        {{ item.name }}
      </RouterLink>

      <template v-if="adminNav.length">
        <p class="mb-2 mt-5 px-3 text-[10px] font-semibold uppercase tracking-wider text-slate-500">Administración</p>
        <RouterLink
          v-for="item in adminNav"
          :key="item.name"
          :to="item.to"
          class="nav-link"
          active-class="nav-link-active"
          @click="$emit('close')"
        >
          <svg class="h-5 w-5 shrink-0 opacity-80" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" :d="item.icon" />
          </svg>
          {{ item.name }}
        </RouterLink>
      </template>
    </nav>

    <div class="border-t border-white/5 p-4">
      <p class="text-[11px] leading-relaxed text-slate-500">
        Pacientes · Citas · Consultas
      </p>
    </div>
  </aside>
</template>
