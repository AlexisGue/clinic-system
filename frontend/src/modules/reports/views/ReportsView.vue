<script setup>
import { computed, onMounted, reactive, ref, watch } from 'vue'
import { reportsApi, saveBlob } from '@/modules/reports/api'
import { useAuthStore } from '@/stores/auth'
import { formatMoney } from '@/utils/format'

const auth = useAuthStore()

const allTabs = [
  { id: 'patients', label: 'Pacientes', path: '/reports/patients' },
  { id: 'appointments', label: 'Citas', path: '/reports/appointments' },
  { id: 'consultations', label: 'Consultas', path: '/reports/consultations', permission: 'consultations.view' },
  { id: 'doctors', label: 'Médicos', path: '/reports/doctors' },
  { id: 'payments', label: 'Pagos', path: '/reports/payments' },
]

const tabs = computed(() =>
  allTabs.filter((t) => !t.permission || auth.can(t.permission)),
)

const active = ref('patients')
const loading = ref(false)
const exporting = ref(false)
const error = ref(null)
const report = ref(null)

const filters = reactive({
  from: new Date(new Date().getFullYear(), new Date().getMonth(), 1).toISOString().slice(0, 10),
  to: new Date().toISOString().slice(0, 10),
})

const money = (n) => formatMoney(n, { currency: 'USD' })

async function load() {
  loading.value = true
  error.value = null
  try {
    const params = { from: filters.from, to: filters.to }
    const { data } = await reportsApi[active.value](params)
    report.value = data.data
  } catch (e) {
    error.value = e.response?.data?.message || 'No se pudo cargar el reporte.'
    report.value = null
  } finally {
    loading.value = false
  }
}

async function exportFile(format) {
  if (!auth.can('reports.export')) return
  exporting.value = true
  error.value = null
  try {
    const tab = tabs.value.find((t) => t.id === active.value)
    const blob = await reportsApi.download(tab.path, {
      format,
      from: filters.from,
      to: filters.to,
    })
    const ext = format === 'pdf' ? 'pdf' : 'csv'
    saveBlob(blob, `reporte-${active.value}-${filters.from}.${ext}`)
  } catch (e) {
    error.value = e.response?.data?.message || 'No se pudo exportar.'
  } finally {
    exporting.value = false
  }
}

watch(active, load)
onMounted(load)
</script>

<template>
  <div>
    <div class="mb-6 flex flex-wrap items-center justify-between gap-3">
      <div>
        <h1 class="page-title">Reportes</h1>
        <p class="page-subtitle">Pacientes, citas, consultas, médicos y pagos (PDF / CSV).</p>
      </div>
      <div v-if="auth.can('reports.export')" class="flex gap-2">
        <button type="button" :disabled="exporting || loading" class="btn-secondary" @click="exportFile('csv')">
          Excel (CSV)
        </button>
        <button type="button" :disabled="exporting || loading" class="btn-primary" @click="exportFile('pdf')">
          PDF
        </button>
      </div>
    </div>

    <div class="mb-4 flex flex-wrap gap-2 border-b border-slate-200 pb-3">
      <button
        v-for="tab in tabs"
        :key="tab.id"
        type="button"
        class="rounded-lg px-3 py-1.5 text-sm font-medium"
        :class="active === tab.id ? 'bg-brand-50 text-brand-700' : 'text-slate-600 hover:bg-slate-50'"
        @click="active = tab.id"
      >
        {{ tab.label }}
      </button>
    </div>

    <div class="mb-4 flex flex-wrap gap-2">
      <input v-model="filters.from" type="date" class="input-field !w-auto" />
      <input v-model="filters.to" type="date" class="input-field !w-auto" />
      <button type="button" class="btn-secondary" @click="load">Filtrar</button>
    </div>

    <div v-if="error" class="mb-4 rounded-xl border border-rose-200 bg-rose-50 px-3 py-2 text-sm text-rose-700">{{ error }}</div>
    <div v-if="loading" class="text-sm text-slate-500">Cargando…</div>

    <template v-else-if="report">
      <div class="mb-4 flex flex-wrap gap-4 text-sm text-slate-600">
        <span v-if="report.period">Periodo: {{ report.period.from }} — {{ report.period.to }}</span>
        <span v-if="report.summary?.count !== undefined">Registros: <strong>{{ report.summary.count }}</strong></span>
        <span v-if="report.summary?.total !== undefined">Total: <strong>{{ money(report.summary.total) }}</strong></span>
      </div>

      <div class="overflow-x-auto rounded-2xl border border-slate-200 bg-white">
        <table v-if="active === 'patients'" class="min-w-full divide-y divide-slate-200 text-sm">
          <thead class="bg-slate-50 text-left text-xs uppercase text-slate-500">
            <tr>
              <th class="px-3 py-2">Documento</th>
              <th class="px-3 py-2">Nombre</th>
              <th class="px-3 py-2">Teléfono</th>
              <th class="px-3 py-2">Email</th>
              <th class="px-3 py-2">Alta</th>
              <th class="px-3 py-2">Activo</th>
            </tr>
          </thead>
          <tbody class="divide-y divide-slate-100">
            <tr v-for="(row, i) in report.rows" :key="i">
              <td class="px-3 py-2">{{ row.document_number }}</td>
              <td class="px-3 py-2 font-medium">{{ row.full_name }}</td>
              <td class="px-3 py-2">{{ row.phone }}</td>
              <td class="px-3 py-2">{{ row.email }}</td>
              <td class="px-3 py-2">{{ row.created_at }}</td>
              <td class="px-3 py-2">{{ row.is_active }}</td>
            </tr>
            <tr v-if="!report.rows.length"><td colspan="6" class="px-3 py-8 text-center text-slate-400">Sin datos</td></tr>
          </tbody>
        </table>

        <table v-else-if="active === 'appointments'" class="min-w-full divide-y divide-slate-200 text-sm">
          <thead class="bg-slate-50 text-left text-xs uppercase text-slate-500">
            <tr>
              <th class="px-3 py-2">Folio</th>
              <th class="px-3 py-2">Fecha</th>
              <th class="px-3 py-2">Paciente</th>
              <th class="px-3 py-2">Médico</th>
              <th class="px-3 py-2">Estado</th>
            </tr>
          </thead>
          <tbody class="divide-y divide-slate-100">
            <tr v-for="(row, i) in report.rows" :key="i">
              <td class="px-3 py-2 font-medium">{{ row.folio }}</td>
              <td class="px-3 py-2">{{ row.starts_at }}</td>
              <td class="px-3 py-2">{{ row.patient }}</td>
              <td class="px-3 py-2">{{ row.doctor }}</td>
              <td class="px-3 py-2">{{ row.status }}</td>
            </tr>
            <tr v-if="!report.rows.length"><td colspan="5" class="px-3 py-8 text-center text-slate-400">Sin datos</td></tr>
          </tbody>
        </table>

        <table v-else-if="active === 'consultations'" class="min-w-full divide-y divide-slate-200 text-sm">
          <thead class="bg-slate-50 text-left text-xs uppercase text-slate-500">
            <tr>
              <th class="px-3 py-2">Folio</th>
              <th class="px-3 py-2">Fecha</th>
              <th class="px-3 py-2">Paciente</th>
              <th class="px-3 py-2">Médico</th>
              <th class="px-3 py-2">Estado</th>
              <th class="px-3 py-2">Diagnóstico</th>
            </tr>
          </thead>
          <tbody class="divide-y divide-slate-100">
            <tr v-for="(row, i) in report.rows" :key="i">
              <td class="px-3 py-2 font-medium">{{ row.folio }}</td>
              <td class="px-3 py-2">{{ row.attended_at }}</td>
              <td class="px-3 py-2">{{ row.patient }}</td>
              <td class="px-3 py-2">{{ row.doctor }}</td>
              <td class="px-3 py-2">{{ row.status }}</td>
              <td class="px-3 py-2">{{ row.diagnosis }}</td>
            </tr>
            <tr v-if="!report.rows.length"><td colspan="6" class="px-3 py-8 text-center text-slate-400">Sin datos</td></tr>
          </tbody>
        </table>

        <table v-else-if="active === 'doctors'" class="min-w-full divide-y divide-slate-200 text-sm">
          <thead class="bg-slate-50 text-left text-xs uppercase text-slate-500">
            <tr>
              <th class="px-3 py-2">Nombre</th>
              <th class="px-3 py-2">Email</th>
              <th class="px-3 py-2">Cédula</th>
              <th class="px-3 py-2">Especialidades</th>
              <th class="px-3 py-2">Citas</th>
              <th class="px-3 py-2">Activo</th>
            </tr>
          </thead>
          <tbody class="divide-y divide-slate-100">
            <tr v-for="(row, i) in report.rows" :key="i">
              <td class="px-3 py-2 font-medium">{{ row.name }}</td>
              <td class="px-3 py-2">{{ row.email }}</td>
              <td class="px-3 py-2">{{ row.license_number }}</td>
              <td class="px-3 py-2">{{ row.specialties }}</td>
              <td class="px-3 py-2">{{ row.appointments }}</td>
              <td class="px-3 py-2">{{ row.is_active }}</td>
            </tr>
            <tr v-if="!report.rows.length"><td colspan="6" class="px-3 py-8 text-center text-slate-400">Sin datos</td></tr>
          </tbody>
        </table>

        <table v-else class="min-w-full divide-y divide-slate-200 text-sm">
          <thead class="bg-slate-50 text-left text-xs uppercase text-slate-500">
            <tr>
              <th class="px-3 py-2">Fecha</th>
              <th class="px-3 py-2 text-right">Monto</th>
              <th class="px-3 py-2">Moneda</th>
              <th class="px-3 py-2">Método</th>
              <th class="px-3 py-2">Referencia</th>
              <th class="px-3 py-2">Registró</th>
            </tr>
          </thead>
          <tbody class="divide-y divide-slate-100">
            <tr v-for="(row, i) in report.rows" :key="i">
              <td class="px-3 py-2">{{ row.paid_at }}</td>
              <td class="px-3 py-2 text-right">{{ money(row.amount) }}</td>
              <td class="px-3 py-2">{{ row.currency }}</td>
              <td class="px-3 py-2">{{ row.method }}</td>
              <td class="px-3 py-2">{{ row.reference }}</td>
              <td class="px-3 py-2">{{ row.recorded_by }}</td>
            </tr>
            <tr v-if="!report.rows.length"><td colspan="6" class="px-3 py-8 text-center text-slate-400">Sin datos</td></tr>
          </tbody>
        </table>
      </div>
    </template>
  </div>
</template>
