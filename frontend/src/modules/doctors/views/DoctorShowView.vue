<script setup>
import { computed, onMounted, reactive, ref } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import FormField from '@/components/ui/FormField.vue'
import { doctorsApi } from '@/modules/doctors/api'
import { catalogsApi } from '@/modules/catalogs/api'
import { useAuthStore } from '@/stores/auth'

const WEEKDAYS = [
  { value: 0, label: 'Domingo' },
  { value: 1, label: 'Lunes' },
  { value: 2, label: 'Martes' },
  { value: 3, label: 'Miércoles' },
  { value: 4, label: 'Jueves' },
  { value: 5, label: 'Viernes' },
  { value: 6, label: 'Sábado' },
]

const auth = useAuthStore()
const route = useRoute()
const router = useRouter()

const doctor = ref(null)
const specialtyOptions = ref([])
const selectedSpecialtyIds = ref([])
const schedules = ref([])
const loading = ref(false)
const saving = ref(false)
const error = ref(null)
const errors = ref({})

const form = reactive({
  name: '',
  email: '',
  license_number: '',
  bio: '',
  is_active: true,
})

const doctorId = computed(() => Number(route.params.id))

async function load() {
  loading.value = true
  error.value = null
  try {
    const [{ data }, opts, sched] = await Promise.all([
      doctorsApi.get(doctorId.value),
      catalogsApi.specialtyOptions(),
      doctorsApi.getSchedules(doctorId.value),
    ])
    doctor.value = data.data
    specialtyOptions.value = opts.data.data
    selectedSpecialtyIds.value = (doctor.value.specialties || []).map((s) => s.id)
    schedules.value = (sched.data.data || []).map((s) => ({
      weekday: s.weekday,
      start_time: (s.start_time || '').slice(0, 5),
      end_time: (s.end_time || '').slice(0, 5),
      slot_minutes: s.slot_minutes || 30,
      is_active: s.is_active !== false,
    }))
    Object.assign(form, {
      name: doctor.value.name || doctor.value.user?.name || '',
      email: doctor.value.email || doctor.value.user?.email || '',
      license_number: doctor.value.license_number || '',
      bio: doctor.value.bio || '',
      is_active: !!doctor.value.is_active,
    })
  } catch (e) {
    error.value = e.response?.data?.message || 'No se pudo cargar el médico.'
  } finally {
    loading.value = false
  }
}

function toggleSpecialty(id) {
  const idx = selectedSpecialtyIds.value.indexOf(id)
  if (idx >= 0) selectedSpecialtyIds.value.splice(idx, 1)
  else selectedSpecialtyIds.value.push(id)
}

function addSchedule() {
  schedules.value.push({
    weekday: 1,
    start_time: '09:00',
    end_time: '13:00',
    slot_minutes: 30,
    is_active: true,
  })
}

function removeSchedule(index) {
  schedules.value.splice(index, 1)
}

async function saveProfile() {
  if (!auth.can('doctors.update')) return
  saving.value = true
  errors.value = {}
  error.value = null
  try {
    await doctorsApi.update(doctorId.value, {
      name: form.name,
      email: form.email,
      license_number: form.license_number || null,
      bio: form.bio || null,
      is_active: form.is_active,
    })
    await doctorsApi.syncSpecialties(doctorId.value, selectedSpecialtyIds.value)
    await load()
  } catch (e) {
    if (e.response?.status === 422) errors.value = e.response.data.errors || {}
    else error.value = e.response?.data?.message || 'No se pudo guardar.'
  } finally {
    saving.value = false
  }
}

async function saveSchedules() {
  if (!auth.can('doctors.update')) return
  saving.value = true
  errors.value = {}
  error.value = null
  try {
    await doctorsApi.putSchedules(doctorId.value, schedules.value)
    await load()
  } catch (e) {
    if (e.response?.status === 422) errors.value = e.response.data.errors || {}
    else error.value = e.response?.data?.message || 'No se pudieron guardar los horarios.'
  } finally {
    saving.value = false
  }
}

onMounted(load)
</script>

<template>
  <div>
    <div class="mb-6">
      <button type="button" class="mb-2 text-sm text-brand-700 hover:underline" @click="router.push({ name: 'doctors.index' })">
        ← Médicos
      </button>
      <h1 class="page-title">{{ doctor?.name || doctor?.user?.name || 'Médico' }}</h1>
      <p class="page-subtitle">Especialidades y horarios semanales.</p>
    </div>

    <div v-if="error" class="mb-4 rounded-xl border border-rose-200 bg-rose-50 px-3 py-2 text-sm text-rose-700">{{ error }}</div>
    <div v-if="loading && !doctor" class="text-sm text-slate-500">Cargando…</div>

    <template v-else-if="doctor">
      <form class="card-surface mb-6 max-w-3xl space-y-3 p-5" @submit.prevent="saveProfile">
        <h2 class="font-display text-sm font-semibold text-slate-800">Datos</h2>
        <div class="grid gap-3 sm:grid-cols-2">
          <FormField label="Nombre" :error="errors.name?.[0]">
            <input v-model="form.name" required class="input-field" :disabled="!auth.can('doctors.update')" />
          </FormField>
          <FormField label="Correo" :error="errors.email?.[0]">
            <input v-model="form.email" type="email" required class="input-field" :disabled="!auth.can('doctors.update')" />
          </FormField>
          <FormField label="Cédula" :error="errors.license_number?.[0]">
            <input v-model="form.license_number" class="input-field" :disabled="!auth.can('doctors.update')" />
          </FormField>
        </div>
        <FormField label="Bio" :error="errors.bio?.[0]">
          <textarea v-model="form.bio" rows="2" class="input-field" :disabled="!auth.can('doctors.update')" />
        </FormField>
        <label class="flex items-center gap-2 text-sm">
          <input v-model="form.is_active" type="checkbox" class="rounded border-slate-300" :disabled="!auth.can('doctors.update')" />
          Activo
        </label>

        <div>
          <p class="mb-2 text-sm font-medium text-slate-700">Especialidades</p>
          <div class="flex flex-wrap gap-2">
            <label
              v-for="s in specialtyOptions"
              :key="s.id"
              class="flex cursor-pointer items-center gap-2 rounded-lg border border-slate-200 px-3 py-1.5 text-sm"
              :class="selectedSpecialtyIds.includes(s.id) ? 'border-brand-400 bg-brand-50 text-brand-800' : 'bg-white'"
            >
              <input
                type="checkbox"
                class="rounded border-slate-300"
                :checked="selectedSpecialtyIds.includes(s.id)"
                :disabled="!auth.can('doctors.update')"
                @change="toggleSpecialty(s.id)"
              />
              {{ s.name }}
            </label>
          </div>
          <p v-if="errors.specialty_ids" class="mt-1 text-xs text-rose-600">{{ errors.specialty_ids[0] }}</p>
        </div>

        <div v-if="auth.can('doctors.update')" class="flex justify-end pt-2">
          <button type="submit" :disabled="saving" class="btn-primary">{{ saving ? 'Guardando…' : 'Guardar perfil' }}</button>
        </div>
      </form>

      <div class="card-surface max-w-4xl p-5">
        <div class="mb-4 flex flex-wrap items-center justify-between gap-2">
          <h2 class="font-display text-sm font-semibold text-slate-800">Horarios semanales</h2>
          <button v-if="auth.can('doctors.update')" type="button" class="btn-secondary" @click="addSchedule">Agregar bloque</button>
        </div>

        <div class="space-y-3">
          <div
            v-for="(row, index) in schedules"
            :key="index"
            class="grid gap-2 rounded-xl border border-slate-100 bg-slate-50/60 p-3 sm:grid-cols-6"
          >
            <FormField label="Día">
              <select v-model.number="row.weekday" class="input-field" :disabled="!auth.can('doctors.update')">
                <option v-for="d in WEEKDAYS" :key="d.value" :value="d.value">{{ d.label }}</option>
              </select>
            </FormField>
            <FormField label="Inicio">
              <input v-model="row.start_time" type="time" class="input-field" :disabled="!auth.can('doctors.update')" />
            </FormField>
            <FormField label="Fin">
              <input v-model="row.end_time" type="time" class="input-field" :disabled="!auth.can('doctors.update')" />
            </FormField>
            <FormField label="Slot (min)">
              <input v-model.number="row.slot_minutes" type="number" min="5" max="240" class="input-field" :disabled="!auth.can('doctors.update')" />
            </FormField>
            <FormField label="Activo">
              <label class="flex h-[42px] items-center gap-2 text-sm">
                <input v-model="row.is_active" type="checkbox" class="rounded border-slate-300" :disabled="!auth.can('doctors.update')" />
                Sí
              </label>
            </FormField>
            <div class="flex items-end">
              <button
                v-if="auth.can('doctors.update')"
                type="button"
                class="btn-secondary w-full text-rose-600"
                @click="removeSchedule(index)"
              >
                Quitar
              </button>
            </div>
          </div>
          <p v-if="!schedules.length" class="text-sm text-slate-400">Sin horarios configurados.</p>
        </div>

        <div v-if="auth.can('doctors.update')" class="mt-4 flex justify-end">
          <button type="button" :disabled="saving" class="btn-primary" @click="saveSchedules">
            {{ saving ? 'Guardando…' : 'Guardar horarios' }}
          </button>
        </div>
      </div>
    </template>
  </div>
</template>
