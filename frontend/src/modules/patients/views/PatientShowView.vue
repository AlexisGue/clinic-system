<script setup>
import { computed, onMounted, reactive, ref, watch } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import FormField from '@/components/ui/FormField.vue'
import BaseModal from '@/components/ui/BaseModal.vue'
import { patientsApi } from '@/modules/patients/api'
import { useAuthStore } from '@/stores/auth'
import { formatDate, formatDateTime } from '@/utils/format'

const auth = useAuthStore()
const route = useRoute()
const router = useRouter()

const tab = ref('datos')
const patient = ref(null)
const contacts = ref([])
const history = ref({ appointments: [], consultations: [], prescriptions: [] })
const loading = ref(false)
const saving = ref(false)
const error = ref(null)
const errors = ref({})

const form = reactive({
  document_type: 'ID',
  document_number: '',
  first_name: '',
  last_name: '',
  birth_date: '',
  gender: '',
  email: '',
  phone: '',
  address: '',
  blood_type: '',
  allergies: '',
  notes: '',
  is_active: true,
})

const contactModal = ref(false)
const editingContact = ref(null)
const contactForm = reactive({
  name: '',
  relationship: '',
  phone: '',
  email: '',
  is_emergency: false,
})

const patientId = computed(() => Number(route.params.id))

async function loadPatient() {
  loading.value = true
  error.value = null
  try {
    const { data } = await patientsApi.get(patientId.value)
    patient.value = data.data
    Object.assign(form, {
      document_type: patient.value.document_type || 'ID',
      document_number: patient.value.document_number || '',
      first_name: patient.value.first_name || '',
      last_name: patient.value.last_name || '',
      birth_date: patient.value.birth_date || '',
      gender: patient.value.gender || '',
      email: patient.value.email || '',
      phone: patient.value.phone || '',
      address: patient.value.address || '',
      blood_type: patient.value.blood_type || '',
      allergies: patient.value.allergies || '',
      notes: patient.value.notes || '',
      is_active: !!patient.value.is_active,
    })
  } catch (e) {
    error.value = e.response?.data?.message || 'No se pudo cargar el paciente.'
  } finally {
    loading.value = false
  }
}

async function loadContacts() {
  const { data } = await patientsApi.contacts(patientId.value)
  contacts.value = data.data
}

async function loadHistory() {
  const { data } = await patientsApi.history(patientId.value)
  history.value = data.data
}

async function savePatient() {
  if (!auth.can('patients.update')) return
  saving.value = true
  errors.value = {}
  error.value = null
  try {
    await patientsApi.update(patientId.value, {
      ...form,
      birth_date: form.birth_date || null,
    })
    await loadPatient()
  } catch (e) {
    if (e.response?.status === 422) errors.value = e.response.data.errors || {}
    else error.value = e.response?.data?.message || 'No se pudo guardar.'
  } finally {
    saving.value = false
  }
}

function openContactCreate() {
  editingContact.value = null
  Object.assign(contactForm, { name: '', relationship: '', phone: '', email: '', is_emergency: false })
  errors.value = {}
  contactModal.value = true
}

function openContactEdit(row) {
  editingContact.value = row
  Object.assign(contactForm, {
    name: row.name || '',
    relationship: row.relationship || '',
    phone: row.phone || '',
    email: row.email || '',
    is_emergency: !!row.is_emergency,
  })
  errors.value = {}
  contactModal.value = true
}

async function saveContact() {
  saving.value = true
  errors.value = {}
  try {
    if (editingContact.value) {
      await patientsApi.updateContact(patientId.value, editingContact.value.id, { ...contactForm })
    } else {
      await patientsApi.storeContact(patientId.value, { ...contactForm })
    }
    contactModal.value = false
    await loadContacts()
  } catch (e) {
    if (e.response?.status === 422) errors.value = e.response.data.errors || {}
    else alert(e.response?.data?.message || 'No se pudo guardar el contacto.')
  } finally {
    saving.value = false
  }
}

async function removeContact(row) {
  if (!confirm(`¿Eliminar contacto ${row.name}?`)) return
  try {
    await patientsApi.destroyContact(patientId.value, row.id)
    await loadContacts()
  } catch (e) {
    alert(e.response?.data?.message || 'No se pudo eliminar.')
  }
}

watch(tab, async (t) => {
  try {
    if (t === 'contactos') await loadContacts()
    if (t === 'historial') await loadHistory()
  } catch (e) {
    error.value = e.response?.data?.message || 'No se pudo cargar.'
  }
})

onMounted(loadPatient)
</script>

<template>
  <div>
    <div class="mb-6 flex flex-wrap items-center justify-between gap-3">
      <div>
        <button type="button" class="mb-2 text-sm text-brand-700 hover:underline" @click="router.push({ name: 'patients.index' })">
          ← Pacientes
        </button>
        <h1 class="page-title">{{ patient?.full_name || 'Paciente' }}</h1>
        <p class="page-subtitle">{{ patient?.document_type }} {{ patient?.document_number }}</p>
      </div>
    </div>

    <div class="mb-4 flex flex-wrap gap-2 border-b border-slate-200 pb-3">
      <button
        v-for="t in [
          { id: 'datos', label: 'Datos' },
          { id: 'contactos', label: 'Contactos' },
          { id: 'historial', label: 'Historial' },
        ]"
        :key="t.id"
        type="button"
        class="rounded-lg px-3 py-1.5 text-sm font-medium"
        :class="tab === t.id ? 'bg-brand-50 text-brand-700' : 'text-slate-600 hover:bg-slate-50'"
        @click="tab = t.id"
      >
        {{ t.label }}
      </button>
    </div>

    <div v-if="error" class="mb-4 rounded-xl border border-rose-200 bg-rose-50 px-3 py-2 text-sm text-rose-700">{{ error }}</div>
    <div v-if="loading && !patient" class="text-sm text-slate-500">Cargando…</div>

    <template v-else-if="patient">
      <form v-if="tab === 'datos'" class="card-surface max-w-3xl space-y-3 p-5" @submit.prevent="savePatient">
        <div class="grid gap-3 sm:grid-cols-2">
          <FormField label="Tipo doc." :error="errors.document_type?.[0]">
            <input v-model="form.document_type" class="input-field" :disabled="!auth.can('patients.update')" />
          </FormField>
          <FormField label="Número" :error="errors.document_number?.[0]">
            <input v-model="form.document_number" required class="input-field" :disabled="!auth.can('patients.update')" />
          </FormField>
          <FormField label="Nombre" :error="errors.first_name?.[0]">
            <input v-model="form.first_name" required class="input-field" :disabled="!auth.can('patients.update')" />
          </FormField>
          <FormField label="Apellido" :error="errors.last_name?.[0]">
            <input v-model="form.last_name" required class="input-field" :disabled="!auth.can('patients.update')" />
          </FormField>
          <FormField label="Nacimiento" :error="errors.birth_date?.[0]">
            <input v-model="form.birth_date" type="date" class="input-field" :disabled="!auth.can('patients.update')" />
          </FormField>
          <FormField label="Género" :error="errors.gender?.[0]">
            <input v-model="form.gender" class="input-field" :disabled="!auth.can('patients.update')" />
          </FormField>
          <FormField label="Teléfono" :error="errors.phone?.[0]">
            <input v-model="form.phone" class="input-field" :disabled="!auth.can('patients.update')" />
          </FormField>
          <FormField label="Tipo sangre" :error="errors.blood_type?.[0]">
            <input v-model="form.blood_type" class="input-field" :disabled="!auth.can('patients.update')" />
          </FormField>
        </div>
        <FormField label="Correo" :error="errors.email?.[0]">
          <input v-model="form.email" type="email" class="input-field" :disabled="!auth.can('patients.update')" />
        </FormField>
        <FormField label="Dirección" :error="errors.address?.[0]">
          <input v-model="form.address" class="input-field" :disabled="!auth.can('patients.update')" />
        </FormField>
        <FormField label="Alergias" :error="errors.allergies?.[0]">
          <textarea v-model="form.allergies" rows="2" class="input-field" :disabled="!auth.can('patients.update')" />
        </FormField>
        <FormField label="Notas" :error="errors.notes?.[0]">
          <textarea v-model="form.notes" rows="2" class="input-field" :disabled="!auth.can('patients.update')" />
        </FormField>
        <label class="flex items-center gap-2 text-sm">
          <input v-model="form.is_active" type="checkbox" class="rounded border-slate-300" :disabled="!auth.can('patients.update')" />
          Activo
        </label>
        <div v-if="auth.can('patients.update')" class="flex justify-end pt-2">
          <button type="submit" :disabled="saving" class="btn-primary">{{ saving ? 'Guardando…' : 'Guardar' }}</button>
        </div>
      </form>

      <div v-else-if="tab === 'contactos'">
        <div class="mb-4 flex justify-end">
          <button v-if="auth.can('patients.update')" type="button" class="btn-primary" @click="openContactCreate">
            Nuevo contacto
          </button>
        </div>
        <div class="card-surface overflow-hidden">
          <table class="min-w-full divide-y divide-slate-100 text-sm">
            <thead class="bg-slate-50/90 text-left text-[11px] font-semibold uppercase tracking-wider text-slate-500">
              <tr>
                <th class="px-4 py-3">Nombre</th>
                <th class="px-4 py-3">Relación</th>
                <th class="px-4 py-3">Teléfono</th>
                <th class="px-4 py-3">Emergencia</th>
                <th class="px-4 py-3 text-right">Acciones</th>
              </tr>
            </thead>
            <tbody class="divide-y divide-slate-100">
              <tr v-for="c in contacts" :key="c.id">
                <td class="px-4 py-3 font-medium">{{ c.name }}</td>
                <td class="px-4 py-3">{{ c.relationship || '—' }}</td>
                <td class="px-4 py-3">{{ c.phone || '—' }}</td>
                <td class="px-4 py-3">{{ c.is_emergency ? 'Sí' : 'No' }}</td>
                <td class="px-4 py-3 text-right">
                  <button v-if="auth.can('patients.update')" class="mr-2 text-brand-700 hover:underline" @click="openContactEdit(c)">Editar</button>
                  <button v-if="auth.can('patients.update')" class="text-rose-600 hover:underline" @click="removeContact(c)">Eliminar</button>
                </td>
              </tr>
              <tr v-if="!contacts.length">
                <td colspan="5" class="px-4 py-8 text-center text-slate-400">Sin contactos</td>
              </tr>
            </tbody>
          </table>
        </div>
      </div>

      <div v-else class="space-y-6">
        <div class="card-surface p-5">
          <h2 class="mb-3 font-display text-sm font-semibold text-slate-800">Citas</h2>
          <ul class="space-y-2 text-sm">
            <li v-for="a in history.appointments" :key="'a'+a.id" class="flex justify-between gap-3 rounded-xl bg-slate-50/80 px-3 py-2">
              <span>{{ a.folio }} · {{ formatDateTime(a.starts_at) }} · {{ a.doctor || '—' }}</span>
              <span class="text-slate-500">{{ a.status }}</span>
            </li>
            <li v-if="!history.appointments?.length" class="text-slate-400">Sin citas</li>
          </ul>
        </div>
        <div class="card-surface p-5">
          <h2 class="mb-3 font-display text-sm font-semibold text-slate-800">Consultas</h2>
          <ul class="space-y-2 text-sm">
            <li v-for="c in history.consultations" :key="'c'+c.id" class="flex justify-between gap-3 rounded-xl bg-slate-50/80 px-3 py-2">
              <span>
                <button
                  v-if="auth.can('consultations.view')"
                  class="text-brand-700 hover:underline"
                  @click="router.push({ name: 'consultations.show', params: { id: c.id } })"
                >{{ c.folio }}</button>
                <span v-else>{{ c.folio }}</span>
                · {{ formatDateTime(c.attended_at) }}
                <template v-if="c.diagnosis !== undefined"> · {{ c.diagnosis || 'Sin diagnóstico' }}</template>
              </span>
              <span class="text-slate-500">{{ c.status }}</span>
            </li>
            <li v-if="!history.consultations?.length" class="text-slate-400">Sin consultas</li>
          </ul>
        </div>
        <div class="card-surface p-5">
          <h2 class="mb-3 font-display text-sm font-semibold text-slate-800">Recetas</h2>
          <ul class="space-y-2 text-sm">
            <li v-for="p in history.prescriptions" :key="'p'+p.id" class="flex justify-between gap-3 rounded-xl bg-slate-50/80 px-3 py-2">
              <span>
                <button
                  v-if="auth.can('prescriptions.view')"
                  class="text-brand-700 hover:underline"
                  @click="router.push({ name: 'prescriptions.show', params: { id: p.id } })"
                >{{ p.folio }}</button>
                <span v-else>{{ p.folio }}</span>
                · {{ formatDate(p.issued_at) }}
              </span>
              <span class="text-slate-500">{{ p.status }}</span>
            </li>
            <li v-if="!history.prescriptions?.length" class="text-slate-400">Sin recetas</li>
          </ul>
        </div>
      </div>
    </template>

    <BaseModal :open="contactModal" :title="editingContact ? 'Editar contacto' : 'Nuevo contacto'" @close="contactModal = false">
      <form class="space-y-3" @submit.prevent="saveContact">
        <FormField label="Nombre" :error="errors.name?.[0]">
          <input v-model="contactForm.name" required class="input-field" />
        </FormField>
        <FormField label="Relación" :error="errors.relationship?.[0]">
          <input v-model="contactForm.relationship" class="input-field" />
        </FormField>
        <FormField label="Teléfono" :error="errors.phone?.[0]">
          <input v-model="contactForm.phone" class="input-field" />
        </FormField>
        <FormField label="Correo" :error="errors.email?.[0]">
          <input v-model="contactForm.email" type="email" class="input-field" />
        </FormField>
        <label class="flex items-center gap-2 text-sm">
          <input v-model="contactForm.is_emergency" type="checkbox" class="rounded border-slate-300" />
          Contacto de emergencia
        </label>
        <div class="flex justify-end gap-2 pt-2">
          <button type="button" class="btn-secondary" @click="contactModal = false">Cancelar</button>
          <button type="submit" :disabled="saving" class="btn-primary">{{ saving ? 'Guardando…' : 'Guardar' }}</button>
        </div>
      </form>
    </BaseModal>
  </div>
</template>
