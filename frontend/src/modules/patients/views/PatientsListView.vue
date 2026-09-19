<script setup>
import { onMounted, reactive, ref } from 'vue'
import { useRouter } from 'vue-router'
import DataTable from '@/components/ui/DataTable.vue'
import FormField from '@/components/ui/FormField.vue'
import BaseModal from '@/components/ui/BaseModal.vue'
import ActionButton from '@/components/ui/ActionButton.vue'
import RowActions from '@/components/ui/RowActions.vue'
import { patientsApi } from '@/modules/patients/api'
import { useAuthStore } from '@/stores/auth'

const auth = useAuthStore()
const router = useRouter()

const items = ref([])
const meta = ref({ current_page: 1, last_page: 1, total: 0 })
const loading = ref(false)
const saving = ref(false)
const error = ref(null)
const errors = ref({})
const filters = reactive({ search: '', page: 1 })
const modalOpen = ref(false)
const editing = ref(null)

const empty = () => ({
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
const form = reactive(empty())

const columns = [
  { key: 'document_number', label: 'Documento' },
  { key: 'full_name', label: 'Nombre' },
  { key: 'phone', label: 'Teléfono' },
  { key: 'is_active', label: 'Estado' },
  { key: 'actions', label: '', class: 'text-right' },
]

async function load() {
  loading.value = true
  error.value = null
  try {
    const { data } = await patientsApi.list({
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
  editing.value = null
  Object.assign(form, empty())
  errors.value = {}
  modalOpen.value = true
}

function openEdit(row) {
  editing.value = row
  Object.assign(form, {
    document_type: row.document_type,
    document_number: row.document_number,
    first_name: row.first_name,
    last_name: row.last_name,
    birth_date: row.birth_date || '',
    gender: row.gender || '',
    email: row.email || '',
    phone: row.phone || '',
    address: row.address || '',
    blood_type: row.blood_type || '',
    allergies: row.allergies || '',
    notes: row.notes || '',
    is_active: row.is_active,
  })
  errors.value = {}
  modalOpen.value = true
}

async function save() {
  saving.value = true
  errors.value = {}
  error.value = null
  try {
    const payload = { ...form, birth_date: form.birth_date || null }
    if (editing.value) {
      await patientsApi.update(editing.value.id, payload)
    } else {
      await patientsApi.create(payload)
    }
    modalOpen.value = false
    await load()
  } catch (e) {
    if (e.response?.status === 422) errors.value = e.response.data.errors || {}
    else error.value = e.response?.data?.message || 'No se pudo guardar.'
  } finally {
    saving.value = false
  }
}

async function remove(row) {
  if (!confirm(`¿Eliminar a ${row.full_name}?`)) return
  try {
    await patientsApi.remove(row.id)
    await load()
  } catch (e) {
    alert(e.response?.data?.message || 'No se pudo eliminar.')
  }
}

onMounted(load)
</script>

<template>
  <div>
    <div class="mb-6 flex flex-wrap items-center justify-between gap-3">
      <div>
        <h1 class="page-title">Pacientes</h1>
        <p class="page-subtitle">Registro e información demográfica.</p>
      </div>
      <button v-if="auth.can('patients.create')" class="btn-primary" @click="openCreate">Nuevo paciente</button>
    </div>

    <div class="mb-4 flex flex-wrap gap-2">
      <input v-model="filters.search" type="search" placeholder="Buscar…" class="input-field max-w-xs" @keyup.enter="filters.page = 1; load()" />
      <button class="btn-secondary" @click="filters.page = 1; load()">Buscar</button>
    </div>

    <div v-if="error" class="mb-4 rounded-xl border border-rose-200 bg-rose-50 px-3 py-2 text-sm text-rose-700">{{ error }}</div>

    <DataTable :columns="columns" :rows="items" :loading="loading" :meta="meta" @page-change="(p) => { filters.page = p; load() }">
      <template #cell-full_name="{ row }">{{ row.full_name || `${row.first_name} ${row.last_name}` }}</template>
      <template #cell-is_active="{ row }">
        <span :class="row.is_active ? 'text-emerald-600' : 'text-slate-400'">{{ row.is_active ? 'Activo' : 'Inactivo' }}</span>
      </template>
      <template #cell-actions="{ row }">
        <RowActions>
          <ActionButton variant="view" label="Ver" @click="router.push({ name: 'patients.show', params: { id: row.id } })" />
          <ActionButton v-if="auth.can('patients.update')" variant="edit" label="Editar" @click="openEdit(row)" />
          <ActionButton v-if="auth.can('patients.delete')" variant="danger" label="Eliminar" @click="remove(row)" />
        </RowActions>
      </template>
    </DataTable>

    <BaseModal :open="modalOpen" :title="editing ? 'Editar paciente' : 'Nuevo paciente'" @close="modalOpen = false">
      <form class="space-y-3" @submit.prevent="save">
        <div class="grid gap-3 sm:grid-cols-2">
          <FormField label="Tipo doc." :error="errors.document_type?.[0]">
            <input v-model="form.document_type" class="input-field" />
          </FormField>
          <FormField label="Número" :error="errors.document_number?.[0]">
            <input v-model="form.document_number" required class="input-field" />
          </FormField>
          <FormField label="Nombre" :error="errors.first_name?.[0]">
            <input v-model="form.first_name" required class="input-field" />
          </FormField>
          <FormField label="Apellido" :error="errors.last_name?.[0]">
            <input v-model="form.last_name" required class="input-field" />
          </FormField>
          <FormField label="Nacimiento" :error="errors.birth_date?.[0]">
            <input v-model="form.birth_date" type="date" class="input-field" />
          </FormField>
          <FormField label="Teléfono" :error="errors.phone?.[0]">
            <input v-model="form.phone" class="input-field" />
          </FormField>
        </div>
        <FormField label="Correo" :error="errors.email?.[0]">
          <input v-model="form.email" type="email" class="input-field" />
        </FormField>
        <FormField label="Alergias" :error="errors.allergies?.[0]">
          <textarea v-model="form.allergies" rows="2" class="input-field" />
        </FormField>
        <label class="flex items-center gap-2 text-sm">
          <input v-model="form.is_active" type="checkbox" class="rounded border-slate-300" />
          Activo
        </label>
        <div class="flex justify-end gap-2 pt-2">
          <button type="button" class="btn-secondary" @click="modalOpen = false">Cancelar</button>
          <button type="submit" :disabled="saving" class="btn-primary">{{ saving ? 'Guardando…' : 'Guardar' }}</button>
        </div>
      </form>
    </BaseModal>
  </div>
</template>
