<script setup>
import { computed, onMounted, reactive, ref } from 'vue'
import { useRouter } from 'vue-router'
import { dashboardApi } from '@/modules/dashboard/api'
import { useAuthStore } from '@/stores/auth'
import { formatMoney, formatDateTime } from '@/utils/format'

const auth = useAuthStore()
const router = useRouter()

const loading = ref(false)
const error = ref(null)
const data = ref(null)

const filters = reactive({
  from: new Date(new Date().getFullYear(), new Date().getMonth(), 1).toISOString().slice(0, 10),
  to: new Date().toISOString().slice(0, 10),
})

const money = (n) => formatMoney(n, { currency: 'USD' })

const maxChart = computed(() => {
  const counts = data.value?.appointments_chart?.map((d) => d.count) || [0]
  return Math.max(...counts, 1)
})

async function load() {
  loading.value = true
  error.value = null
  try {
    const { data: res } = await dashboardApi.summary({
      from: filters.from,
      to: filters.to,
    })
    data.value = res.data
  } catch (e) {
    error.value = e.response?.data?.message || 'No se pudo cargar el dashboard.'
  } finally {
    loading.value = false
  }
}

onMounted(load)
</script>

<template>
  <div>
    <div class="mb-8 flex flex-wrap items-end justify-between gap-4">
      <div>
        <p class="text-xs font-semibold uppercase tracking-wider text-brand-700">Dashboard</p>
        <h1 class="page-title mt-1">Hola, {{ auth.user?.name }}</h1>
        <p class="page-subtitle">Resumen clínico de pacientes, citas y pagos.</p>
      </div>
      <div class="flex flex-wrap items-center gap-2">
        <input v-model="filters.from" type="date" class="input-field !w-auto" />
        <input v-model="filters.to" type="date" class="input-field !w-auto" />
        <button type="button" class="btn-secondary" @click="load">Actualizar</button>
        <button
          v-if="auth.can('reports.view')"
          type="button"
          class="btn-primary"
          @click="router.push({ name: 'reports.index' })"
        >
          Reportes
        </button>
      </div>
    </div>

    <div v-if="error" class="mb-4 rounded-xl border border-rose-200 bg-rose-50 px-3.5 py-2.5 text-sm text-rose-700">{{ error }}</div>
    <div v-if="loading && !data" class="text-sm text-slate-500">Cargando…</div>

    <template v-if="data">
      <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
        <div class="card-surface relative overflow-hidden p-5">
          <div class="absolute -right-4 -top-4 h-20 w-20 rounded-full bg-brand-100/80" />
          <p class="relative text-sm font-medium text-slate-500">Pacientes activos</p>
          <p class="relative mt-2 font-display text-2xl font-semibold text-slate-900">{{ data.kpis.patients_count }}</p>
        </div>
        <div class="card-surface relative overflow-hidden p-5">
          <div class="absolute -right-4 -top-4 h-20 w-20 rounded-full bg-sky-100/80" />
          <p class="relative text-sm font-medium text-slate-500">Citas de hoy</p>
          <p class="relative mt-2 font-display text-2xl font-semibold text-slate-900">{{ data.kpis.appointments_today }}</p>
        </div>
        <div class="card-surface relative overflow-hidden p-5">
          <div class="absolute -right-4 -top-4 h-20 w-20 rounded-full bg-emerald-100/80" />
          <p class="relative text-sm font-medium text-slate-500">Consultas del periodo</p>
          <p class="relative mt-2 font-display text-2xl font-semibold text-slate-900">{{ data.kpis.consultations_period }}</p>
          <p class="relative mt-1 text-xs text-slate-400">{{ data.period.from }} — {{ data.period.to }}</p>
        </div>
        <div class="card-surface relative overflow-hidden p-5">
          <div class="absolute -right-4 -top-4 h-20 w-20 rounded-full bg-amber-100/80" />
          <p class="relative text-sm font-medium text-slate-500">Pagos del periodo</p>
          <p class="relative mt-2 font-display text-2xl font-semibold text-slate-900">{{ money(data.kpis.payments_period_total) }}</p>
        </div>
      </div>

      <div class="mt-6 grid gap-6 lg:grid-cols-5">
        <div class="card-surface p-5 lg:col-span-3">
          <h2 class="mb-4 font-display text-sm font-semibold text-slate-800">Citas últimos 7 días</h2>
          <div class="flex h-44 items-end gap-2.5">
            <div
              v-for="day in data.appointments_chart"
              :key="day.date"
              class="flex flex-1 flex-col items-center gap-1.5"
            >
              <span class="text-[10px] text-slate-400">{{ day.count }}</span>
              <div
                class="w-full rounded-t-lg bg-gradient-to-t from-brand-700 to-brand-400 transition-all"
                :style="{ height: `${Math.max(4, (day.count / maxChart) * 100)}%` }"
                :title="`${day.date}: ${day.count}`"
              />
              <span class="text-[10px] font-medium text-slate-500">{{ day.label }}</span>
            </div>
          </div>
        </div>

        <div class="card-surface p-5 lg:col-span-2">
          <div class="mb-3 flex items-center justify-between">
            <h2 class="font-display text-sm font-semibold text-slate-800">Próximas citas</h2>
            <button
              v-if="auth.can('appointments.view')"
              type="button"
              class="text-xs font-semibold text-brand-700 hover:underline"
              @click="router.push({ name: 'appointments.index' })"
            >
              Ver agenda
            </button>
          </div>
          <ul v-if="data.upcoming_appointments?.length" class="space-y-2.5">
            <li
              v-for="a in data.upcoming_appointments"
              :key="a.id"
              class="rounded-xl bg-slate-50/80 px-3 py-2.5 text-sm"
            >
              <p class="font-medium text-slate-800">{{ a.patient }}</p>
              <p class="text-xs text-slate-400">{{ formatDateTime(a.starts_at) }} · {{ a.doctor || '—' }} · {{ a.status }}</p>
            </li>
          </ul>
          <p v-else class="text-sm text-slate-400">Sin citas próximas.</p>
        </div>
      </div>
    </template>
  </div>
</template>
