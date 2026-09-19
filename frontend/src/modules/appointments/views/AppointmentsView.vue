<script setup>
import { onMounted, reactive, ref } from 'vue'
import DataTable from '@/components/ui/DataTable.vue'
import FormField from '@/components/ui/FormField.vue'
import BaseModal from '@/components/ui/BaseModal.vue'
import ActionButton from '@/components/ui/ActionButton.vue'
import RowActions from '@/components/ui/RowActions.vue'
import { appointmentsApi } from '@/modules/appointments/api'
import { doctorsApi } from '@/modules/doctors/api'
import { patientsApi } from '@/modules/patients/api'
import { catalogsApi } from '@/modules/catalogs/api'
import { useAuthStore } from '@/stores/auth'
import { formatDateTime } from '@/utils/format'

const auth = useAuthStore()

const items = ref([])
const meta = ref({ current_page: 1, last_page: 1, total: 0 })
const doctors = ref([])
const patients = ref([])
const specialties = ref([])
const loading = ref(false)
const saving = ref(false)
const error = ref(null)
const optionsError = ref(null)
const formError = ref(null)
const errors = ref({})

const today = new Date().toISOString().slice(0, 10)
const filters = reactive({
  from: today,
  to: today,
  doctor_id: '',
  page: 1,
})

const createOpen = ref(false)
const cancelOpen = ref(false)
const rescheduleOpen = ref(false)
const selected = ref(null)

const createForm = reactive({
  patient_id: '',
  doctor_id: '',
  specialty_id: '',
  starts_at: '',
  ends_at: '',
  reason: '',
})

const cancelForm = reactive({ reason: '' })
const rescheduleForm = reactive({ starts_at: '', ends_at: '' })

const statusLabels = {
  scheduled: 'Programada',
  confirmed: 'Confirmada',
  in_progress: 'En consulta',
  completed: 'Completada',
  cancelled: 'Cancelada',
  no_show: 'No asistió',
}

const columns = [
  { key: 'folio', label: 'Folio' },
  { key: 'starts_at', label: 'Inicio' },
  { key: 'patient', label: 'Paciente' },
  { key: 'doctor', label: 'Médico' },
  { key: 'status', label: 'Estado' },
  { key: 'actions', label: '', class: 'text-right' },
]

function toLocalInput(d) {
  const pad = (n) => String(n).padStart(2, '0')
  return `${d.getFullYear()}-${pad(d.getMonth() + 1)}-${pad(d.getDate())}T${pad(d.getHours())}:${pad(d.getMinutes())}`
}

/** Siguiente bloque de 30 min a partir de ahora (evita chocar con 09:00 por defecto). */
function defaultSlot() {
  const start = new Date()
  start.setSeconds(0, 0)
  const mins = start.getMinutes()
  const add = mins % 30 === 0 ? 30 : 30 - (mins % 30)
  start.setMinutes(mins + add)
  const end = new Date(start.getTime() + 30 * 60000)
  return { starts_at: toLocalInput(start), ends_at: toLocalInput(end) }
}

function applyApiError(e) {
  const data = e.response?.data
  if (e.response?.status === 422) {
    errors.value = data?.errors || {}
    formError.value = Object.keys(errors.value).length
      ? null
      : (data?.message || 'No se pudo completar la operación.')
    return
  }
  formError.value = data?.message || 'Ocurrió un error inesperado.'
}

async function loadOptions() {
  const [d, p, s] = await Promise.all([
    doctorsApi.options(),
    patientsApi.options(),
    catalogsApi.specialtyOptions(),
  ])
  doctors.value = d.data.data
  patients.value = p.data.data
  specialties.value = s.data.data
}

async function load() {
  loading.value = true
  error.value = null
  try {
    const { data } = await appointmentsApi.list({
      from: filters.from || undefined,
      to: filters.to || undefined,
      doctor_id: filters.doctor_id || undefined,
      page: filters.page,
    })
    items.value = data.data.items
    meta.value = data.data.meta
  } catch (e) {
    error.value = e.response?.data?.message || 'No se pudo cargar.'
  } finally {
    loading.value = false
  }
}

function applyFilters() {
  if (filters.from && filters.to && filters.from > filters.to) {
    ;[filters.from, filters.to] = [filters.to, filters.from]
  }
  filters.page = 1
  load()
}

function openCreate() {
  const slot = defaultSlot()
  Object.assign(createForm, {
    patient_id: '',
    doctor_id: '',
    specialty_id: '',
    starts_at: slot.starts_at,
    ends_at: slot.ends_at,
    reason: '',
  })
  errors.value = {}
  formError.value = null
  createOpen.value = true
}

async function saveCreate() {
  saving.value = true
  errors.value = {}
  formError.value = null
  try {
    await appointmentsApi.create({
      patient_id: Number(createForm.patient_id),
      doctor_id: Number(createForm.doctor_id),
      specialty_id: createForm.specialty_id ? Number(createForm.specialty_id) : null,
      starts_at: createForm.starts_at,
      ends_at: createForm.ends_at,
      reason: createForm.reason || null,
    })
    createOpen.value = false
    await load()
  } catch (e) {
    applyApiError(e)
  } finally {
    saving.value = false
  }
}

function openCancel(row) {
  selected.value = row
  cancelForm.reason = ''
  errors.value = {}
  formError.value = null
  cancelOpen.value = true
}

async function saveCancel() {
  saving.value = true
  errors.value = {}
  formError.value = null
  try {
    await appointmentsApi.cancel(selected.value.id, { reason: cancelForm.reason })
    cancelOpen.value = false
    await load()
  } catch (e) {
    applyApiError(e)
  } finally {
    saving.value = false
  }
}

function openReschedule(row) {
  selected.value = row
  const start = row.starts_at ? new Date(row.starts_at) : new Date()
  const end = row.ends_at ? new Date(row.ends_at) : new Date(start.getTime() + 30 * 60000)
  rescheduleForm.starts_at = toLocalInput(start)
  rescheduleForm.ends_at = toLocalInput(end)
  errors.value = {}
  formError.value = null
  rescheduleOpen.value = true
}

async function saveReschedule() {
  saving.value = true
  errors.value = {}
  formError.value = null
  try {
    await appointmentsApi.reschedule(selected.value.id, {
      starts_at: rescheduleForm.starts_at,
      ends_at: rescheduleForm.ends_at,
    })
    rescheduleOpen.value = false
    await load()
  } catch (e) {
    applyApiError(e)
  } finally {
    saving.value = false
  }
}

onMounted(async () => {
  try {
    await loadOptions()
  } catch (e) {
    optionsError.value = e.response?.data?.message || 'No se pudieron cargar médicos/pacientes para filtros y formularios.'
  }
  await load()
})
</script>

<template>
  <div>
    <div class="mb-6 flex flex-wrap items-center justify-between gap-3">
      <div>
        <h1 class="page-title">Citas</h1>
        <p class="page-subtitle">Agenda, cancelación y reprogramación.</p>
      </div>
      <button v-if="auth.can('appointments.create')" class="btn-primary" @click="openCreate">Nueva cita</button>
    </div>

    <div class="mb-4 flex flex-wrap gap-2">
      <input v-model="filters.from" type="date" class="input-field !w-auto" />
      <input v-model="filters.to" type="date" class="input-field !w-auto" :min="filters.from || undefined" />
      <select v-model="filters.doctor_id" class="input-field !w-auto min-w-[12rem]">
        <option value="">Todos los médicos</option>
        <option v-for="d in doctors" :key="d.id" :value="d.id">{{ d.name || d.user?.name }}</option>
      </select>
      <button class="btn-secondary" @click="applyFilters">Filtrar</button>
    </div>

    <div v-if="optionsError" class="mb-4 rounded-xl border border-amber-200 bg-amber-50 px-3 py-2 text-sm text-amber-800">{{ optionsError }}</div>
    <div v-if="error" class="mb-4 rounded-xl border border-rose-200 bg-rose-50 px-3 py-2 text-sm text-rose-700">{{ error }}</div>

    <DataTable :columns="columns" :rows="items" :loading="loading" :meta="meta" @page-change="(p) => { filters.page = p; load() }">
      <template #cell-starts_at="{ row }">{{ formatDateTime(row.starts_at) }}</template>
      <template #cell-patient="{ row }">{{ row.patient?.full_name || '—' }}</template>
      <template #cell-doctor="{ row }">{{ row.doctor?.name || '—' }}</template>
      <template #cell-status="{ row }">{{ statusLabels[row.status] || row.status }}</template>
      <template #cell-actions="{ row }">
        <RowActions>
          <ActionButton
            v-if="auth.can('appointments.reschedule') && row.status !== 'cancelled'"
            variant="warn"
            label="Reprogramar"
            @click="openReschedule(row)"
          />
          <ActionButton
            v-if="auth.can('appointments.cancel') && row.status !== 'cancelled'"
            variant="danger"
            label="Cancelar"
            @click="openCancel(row)"
          />
        </RowActions>
      </template>
    </DataTable>

    <BaseModal :open="createOpen" title="Nueva cita" @close="createOpen = false">
      <form class="space-y-3" @submit.prevent="saveCreate">
        <div v-if="formError" class="rounded-xl border border-rose-200 bg-rose-50 px-3 py-2 text-sm text-rose-700">{{ formError }}</div>
        <FormField label="Paciente" :error="errors.patient_id?.[0]">
          <select v-model="createForm.patient_id" required class="input-field">
            <option value="" disabled>Seleccionar…</option>
            <option v-for="p in patients" :key="p.id" :value="p.id">{{ p.full_name || `${p.first_name} ${p.last_name}` }}</option>
          </select>
        </FormField>
        <FormField label="Médico" :error="errors.doctor_id?.[0]">
          <select v-model="createForm.doctor_id" required class="input-field">
            <option value="" disabled>Seleccionar…</option>
            <option v-for="d in doctors" :key="d.id" :value="d.id">{{ d.name || d.user?.name }}</option>
          </select>
        </FormField>
        <FormField label="Especialidad" :error="errors.specialty_id?.[0]">
          <select v-model="createForm.specialty_id" class="input-field">
            <option value="">—</option>
            <option v-for="s in specialties" :key="s.id" :value="s.id">{{ s.name }}</option>
          </select>
        </FormField>
        <div class="grid gap-3 sm:grid-cols-2">
          <FormField label="Inicio" :error="errors.starts_at?.[0]">
            <input v-model="createForm.starts_at" type="datetime-local" required class="input-field" />
          </FormField>
          <FormField label="Fin" :error="errors.ends_at?.[0]">
            <input v-model="createForm.ends_at" type="datetime-local" required class="input-field" />
          </FormField>
        </div>
        <FormField label="Motivo" :error="errors.reason?.[0]">
          <input v-model="createForm.reason" class="input-field" />
        </FormField>
        <div class="flex justify-end gap-2 pt-2">
          <button type="button" class="btn-secondary" @click="createOpen = false">Cerrar</button>
          <button type="submit" :disabled="saving" class="btn-primary">{{ saving ? 'Guardando…' : 'Crear' }}</button>
        </div>
      </form>
    </BaseModal>

    <BaseModal :open="cancelOpen" title="Cancelar cita" @close="cancelOpen = false">
      <form class="space-y-3" @submit.prevent="saveCancel">
        <div v-if="formError" class="rounded-xl border border-rose-200 bg-rose-50 px-3 py-2 text-sm text-rose-700">{{ formError }}</div>
        <FormField label="Motivo" :error="errors.reason?.[0]">
          <textarea v-model="cancelForm.reason" required rows="3" class="input-field" />
        </FormField>
        <div class="flex justify-end gap-2 pt-2">
          <button type="button" class="btn-secondary" @click="cancelOpen = false">Cerrar</button>
          <button type="submit" :disabled="saving" class="btn-primary">Confirmar cancelación</button>
        </div>
      </form>
    </BaseModal>

    <BaseModal :open="rescheduleOpen" title="Reprogramar cita" @close="rescheduleOpen = false">
      <form class="space-y-3" @submit.prevent="saveReschedule">
        <div v-if="formError" class="rounded-xl border border-rose-200 bg-rose-50 px-3 py-2 text-sm text-rose-700">{{ formError }}</div>
        <FormField label="Nuevo inicio" :error="errors.starts_at?.[0]">
          <input v-model="rescheduleForm.starts_at" type="datetime-local" required class="input-field" />
        </FormField>
        <FormField label="Nuevo fin" :error="errors.ends_at?.[0]">
          <input v-model="rescheduleForm.ends_at" type="datetime-local" required class="input-field" />
        </FormField>
        <div class="flex justify-end gap-2 pt-2">
          <button type="button" class="btn-secondary" @click="rescheduleOpen = false">Cerrar</button>
          <button type="submit" :disabled="saving" class="btn-primary">Reprogramar</button>
        </div>
      </form>
    </BaseModal>
  </div>
</template>
