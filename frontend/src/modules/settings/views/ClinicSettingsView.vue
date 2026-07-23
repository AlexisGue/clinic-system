<script setup>
import { computed, onMounted, reactive, ref, watch } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import FormField from '@/components/ui/FormField.vue'
import { settingsApi } from '@/modules/settings/api'
import { useAuthStore } from '@/stores/auth'
import SpecialtiesView from '@/modules/catalogs/views/SpecialtiesView.vue'
import MedicinesView from '@/modules/catalogs/views/MedicinesView.vue'

const auth = useAuthStore()
const route = useRoute()
const router = useRouter()

const loading = ref(false)
const saving = ref(false)
const error = ref(null)
const success = ref(null)
const errors = ref({})

const tab = ref(route.query.tab || 'clinic')

const tabs = computed(() => {
  const list = [{ id: 'clinic', label: 'Clínica', show: auth.can('settings.view') }]
  if (auth.can('specialties.view')) list.push({ id: 'specialties', label: 'Especialidades', show: true })
  if (auth.can('medicines.view')) list.push({ id: 'medicines', label: 'Medicamentos', show: true })
  if (auth.can('roles.view') || auth.can('audits.view')) {
    list.push({ id: 'system', label: 'Sistema', show: true })
  }
  return list.filter((t) => t.show)
})

watch(tab, (id) => {
  router.replace({ query: { ...route.query, tab: id } })
})

const form = reactive({
  clinic_name: '',
  tax_id: '',
  address: '',
  phone: '',
  email: '',
  currency: 'USD',
  appointment_default_duration: 30,
  ticket_footer: '',
  require_payment_to_complete: false,
})

async function load() {
  loading.value = true
  error.value = null
  try {
    const { data } = await settingsApi.get()
    const s = data.data.settings || {}
    Object.assign(form, {
      clinic_name: s.clinic_name || '',
      tax_id: s.tax_id || '',
      address: s.address || '',
      phone: s.phone || '',
      email: s.email || '',
      currency: s.currency || 'USD',
      appointment_default_duration: Number(s.appointment_default_duration || 30),
      ticket_footer: s.ticket_footer || '',
      require_payment_to_complete:
        s.require_payment_to_complete === '1'
        || s.require_payment_to_complete === true
        || s.require_payment_to_complete === 'true',
    })
  } catch (e) {
    error.value = e.response?.data?.message || 'No se pudo cargar la configuración.'
  } finally {
    loading.value = false
  }
}

async function save() {
  if (!auth.can('settings.update')) return
  saving.value = true
  errors.value = {}
  error.value = null
  success.value = null
  try {
    await settingsApi.update({
      clinic_name: form.clinic_name || null,
      tax_id: form.tax_id || null,
      address: form.address || null,
      phone: form.phone || null,
      email: form.email || null,
      currency: form.currency,
      appointment_default_duration: Number(form.appointment_default_duration),
      ticket_footer: form.ticket_footer || null,
      require_payment_to_complete: !!form.require_payment_to_complete,
    })
    success.value = 'Configuración guardada.'
    await load()
  } catch (e) {
    if (e.response?.status === 422) errors.value = e.response.data.errors || {}
    else error.value = e.response?.data?.message || 'No se pudo guardar.'
  } finally {
    saving.value = false
  }
}

onMounted(load)
</script>

<template>
  <div>
    <div class="mb-6">
      <h1 class="page-title">Configuración</h1>
      <p class="page-subtitle">Datos de la clínica, catálogos y acceso al sistema.</p>
    </div>

    <div class="mb-5 flex flex-wrap gap-2">
      <button
        v-for="t in tabs"
        :key="t.id"
        type="button"
        class="tab-btn"
        :class="{ 'tab-btn-active': tab === t.id }"
        @click="tab = t.id"
      >
        {{ t.label }}
      </button>
    </div>

    <div v-if="error" class="mb-4 rounded-xl border border-rose-200 bg-rose-50 px-3 py-2 text-sm text-rose-700">{{ error }}</div>
    <div v-if="success" class="mb-4 rounded-xl border border-emerald-200 bg-emerald-50 px-3 py-2 text-sm text-emerald-700">{{ success }}</div>

    <template v-if="tab === 'clinic'">
      <div v-if="loading" class="text-sm text-slate-500">Cargando…</div>
      <form v-else class="card-surface max-w-2xl space-y-3 p-5" @submit.prevent="save">
        <FormField label="Nombre de la clínica" :error="errors.clinic_name?.[0]">
          <input v-model="form.clinic_name" class="input-field" :disabled="!auth.can('settings.update')" />
        </FormField>
        <FormField label="RFC / Tax ID" :error="errors.tax_id?.[0]">
          <input v-model="form.tax_id" class="input-field" :disabled="!auth.can('settings.update')" />
        </FormField>
        <FormField label="Dirección" :error="errors.address?.[0]">
          <textarea v-model="form.address" rows="2" class="input-field" :disabled="!auth.can('settings.update')" />
        </FormField>
        <div class="grid gap-3 sm:grid-cols-2">
          <FormField label="Teléfono" :error="errors.phone?.[0]">
            <input v-model="form.phone" class="input-field" :disabled="!auth.can('settings.update')" />
          </FormField>
          <FormField label="Correo" :error="errors.email?.[0]">
            <input v-model="form.email" type="email" class="input-field" :disabled="!auth.can('settings.update')" />
          </FormField>
        </div>
        <div class="grid gap-3 sm:grid-cols-2">
          <FormField label="Moneda" :error="errors.currency?.[0]">
            <select v-model="form.currency" class="input-field" :disabled="!auth.can('settings.update')">
              <option value="USD">USD</option>
              <option value="MXN">MXN</option>
            </select>
          </FormField>
          <FormField label="Duración cita (min)" :error="errors.appointment_default_duration?.[0]">
            <input v-model.number="form.appointment_default_duration" type="number" min="5" max="240" class="input-field" :disabled="!auth.can('settings.update')" />
          </FormField>
        </div>
        <FormField label="Pie de ticket / PDF" :error="errors.ticket_footer?.[0]">
          <textarea v-model="form.ticket_footer" rows="2" class="input-field" :disabled="!auth.can('settings.update')" />
        </FormField>
        <label class="flex items-center gap-2 text-sm">
          <input v-model="form.require_payment_to_complete" type="checkbox" class="rounded border-slate-300" :disabled="!auth.can('settings.update')" />
          Exigir pago para finalizar consulta
        </label>
        <div v-if="auth.can('settings.update')" class="flex justify-end pt-2">
          <button type="submit" :disabled="saving" class="btn-primary">{{ saving ? 'Guardando…' : 'Guardar' }}</button>
        </div>
      </form>
    </template>

    <div v-else-if="tab === 'specialties'">
      <SpecialtiesView embedded />
    </div>

    <div v-else-if="tab === 'medicines'">
      <MedicinesView embedded />
    </div>

    <div v-else-if="tab === 'system'" class="grid gap-3 sm:grid-cols-2 max-w-2xl">
      <RouterLink
        v-if="auth.can('roles.view')"
        :to="{ name: 'roles.index' }"
        class="card-surface p-5 transition hover:border-brand-300 hover:shadow-md"
      >
        <p class="font-display font-semibold text-slate-900">Roles y permisos</p>
        <p class="mt-1 text-sm text-slate-500">Define qué puede hacer cada tipo de usuario.</p>
      </RouterLink>
      <RouterLink
        v-if="auth.can('audits.view')"
        :to="{ name: 'audits.index' }"
        class="card-surface p-5 transition hover:border-brand-300 hover:shadow-md"
      >
        <p class="font-display font-semibold text-slate-900">Auditoría</p>
        <p class="mt-1 text-sm text-slate-500">Historial de cambios importantes en el sistema.</p>
      </RouterLink>
      <RouterLink
        v-if="auth.can('users.view')"
        :to="{ name: 'users.index' }"
        class="card-surface p-5 transition hover:border-brand-300 hover:shadow-md"
      >
        <p class="font-display font-semibold text-slate-900">Usuarios</p>
        <p class="mt-1 text-sm text-slate-500">Altas, bajas y asignación de roles.</p>
      </RouterLink>
    </div>
  </div>
</template>
