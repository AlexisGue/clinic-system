<script setup>
import { onMounted, reactive, ref } from 'vue'
import { useRouter } from 'vue-router'
import DataTable from '@/components/ui/DataTable.vue'
import FormField from '@/components/ui/FormField.vue'
import BaseModal from '@/components/ui/BaseModal.vue'
import ActionButton from '@/components/ui/ActionButton.vue'
import RowActions from '@/components/ui/RowActions.vue'
import { consultationsApi } from '@/modules/consultations/api'
import { doctorsApi } from '@/modules/doctors/api'
import { patientsApi } from '@/modules/patients/api'
import { useAuthStore } from '@/stores/auth'
import { formatDateTime } from '@/utils/format'

const auth = useAuthStore()
const router = useRouter()

const items = ref([])
const meta = ref({ current_page: 1, last_page: 1, total: 0 })
const doctors = ref([])
const patients = ref([])
const loading = ref(false)
const saving = ref(false)
const error = ref(null)
const errors = ref({})
const modalOpen = ref(false)

const filters = reactive({
  from: new Date(new Date().getFullYear(), new Date().getMonth(), 1).toISOString().slice(0, 10),
  to: new Date().toISOString().slice(0, 10),
  doctor_id: '',
  search: '',
  page: 1,
})

const form = reactive({
  patient_id: '',
  doctor_id: '',
  attended_at: '',
  chief_complaint: '',
})

const columns = [
  { key: 'folio', label: 'Folio' },
  { key: 'attended_at', label: 'Fecha' },
  { key: 'patient', label: 'Paciente' },
  { key: 'doctor', label: 'Médico' },
  { key: 'status', label: 'Estado' },
  { key: 'actions', label: '', class: 'text-right' },
]

async function load() {
  loading.value = true
  error.value = null
  try {
    const { data } = await consultationsApi.list({
      from: filters.from || undefined,
      to: filters.to || undefined,
      doctor_id: filters.doctor_id || undefined,
      search: filters.search || undefined,
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

function openCreate() {
  Object.assign(form, {
    patient_id: '',
    doctor_id: '',
    attended_at: new Date().toISOString().slice(0, 16),
    chief_complaint: '',
  })
  errors.value = {}
  modalOpen.value = true
}

async function save() {
  saving.value = true
  errors.value = {}
  try {
    const { data } = await consultationsApi.create({
      patient_id: Number(form.patient_id),
      doctor_id: Number(form.doctor_id),
      attended_at: form.attended_at || null,
      chief_complaint: form.chief_complaint || null,
    })
    modalOpen.value = false
    router.push({ name: 'consultations.show', params: { id: data.data.id } })
  } catch (e) {
    if (e.response?.status === 422) errors.value = e.response.data.errors || {}
    else error.value = e.response?.data?.message || 'No se pudo crear.'
  } finally {
    saving.value = false
  }
}

onMounted(async () => {
  try {
    const [d, p] = await Promise.all([doctorsApi.options(), patientsApi.options()])
    doctors.value = d.data.data
    patients.value = p.data.data
  } catch {
    /* ignore */
  }
  await load()
})
</script>

<template>
  <div>
    <div class="mb-6 flex flex-wrap items-center justify-between gap-3">
      <div>
        <h1 class="page-title">Consultas</h1>
        <p class="page-subtitle">Atenciones clínicas y signos vitales.</p>
      </div>
      <button v-if="auth.can('consultations.create')" class="btn-primary" @click="openCreate">Nueva consulta</button>
    </div>

    <div class="mb-4 flex flex-wrap gap-2">
      <input v-model="filters.from" type="date" class="input-field !w-auto" />
      <input v-model="filters.to" type="date" class="input-field !w-auto" />
      <select v-model="filters.doctor_id" class="input-field !w-auto min-w-[12rem]">
        <option value="">Todos los médicos</option>
        <option v-for="d in doctors" :key="d.id" :value="d.id">{{ d.name || d.user?.name }}</option>
      </select>
      <input v-model="filters.search" type="search" placeholder="Buscar…" class="input-field max-w-xs" @keyup.enter="filters.page = 1; load()" />
      <button class="btn-secondary" @click="filters.page = 1; load()">Filtrar</button>
    </div>

    <div v-if="error" class="mb-4 rounded-xl border border-rose-200 bg-rose-50 px-3 py-2 text-sm text-rose-700">{{ error }}</div>

    <DataTable :columns="columns" :rows="items" :loading="loading" :meta="meta" @page-change="(p) => { filters.page = p; load() }">
      <template #cell-attended_at="{ row }">{{ formatDateTime(row.attended_at) }}</template>
      <template #cell-patient="{ row }">{{ row.patient?.full_name || '—' }}</template>
      <template #cell-doctor="{ row }">{{ row.doctor?.name || '—' }}</template>
      <template #cell-actions="{ row }">
        <RowActions>
          <ActionButton variant="view" label="Ver" @click="router.push({ name: 'consultations.show', params: { id: row.id } })" />
        </RowActions>
      </template>
    </DataTable>

    <BaseModal :open="modalOpen" title="Nueva consulta" @close="modalOpen = false">
      <form class="space-y-3" @submit.prevent="save">
        <FormField label="Paciente" :error="errors.patient_id?.[0]">
          <select v-model="form.patient_id" required class="input-field">
            <option value="" disabled>Seleccionar…</option>
            <option v-for="p in patients" :key="p.id" :value="p.id">{{ p.full_name || `${p.first_name} ${p.last_name}` }}</option>
          </select>
        </FormField>
        <FormField label="Médico" :error="errors.doctor_id?.[0]">
          <select v-model="form.doctor_id" required class="input-field">
            <option value="" disabled>Seleccionar…</option>
            <option v-for="d in doctors" :key="d.id" :value="d.id">{{ d.name || d.user?.name }}</option>
          </select>
        </FormField>
        <FormField label="Fecha atención" :error="errors.attended_at?.[0]">
          <input v-model="form.attended_at" type="datetime-local" class="input-field" />
        </FormField>
        <FormField label="Motivo de consulta" :error="errors.chief_complaint?.[0]">
          <textarea v-model="form.chief_complaint" rows="2" class="input-field" />
        </FormField>
        <div class="flex justify-end gap-2 pt-2">
          <button type="button" class="btn-secondary" @click="modalOpen = false">Cancelar</button>
          <button type="submit" :disabled="saving" class="btn-primary">{{ saving ? 'Creando…' : 'Crear' }}</button>
        </div>
      </form>
    </BaseModal>
  </div>
</template>
