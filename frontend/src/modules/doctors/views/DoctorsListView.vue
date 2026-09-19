<script setup>
import { onMounted, reactive, ref } from 'vue'
import { useRouter } from 'vue-router'
import DataTable from '@/components/ui/DataTable.vue'
import FormField from '@/components/ui/FormField.vue'
import BaseModal from '@/components/ui/BaseModal.vue'
import ActionButton from '@/components/ui/ActionButton.vue'
import RowActions from '@/components/ui/RowActions.vue'
import { doctorsApi } from '@/modules/doctors/api'
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
  name: '',
  email: '',
  password: '',
  license_number: '',
  bio: '',
  is_active: true,
})
const form = reactive(empty())

const columns = [
  { key: 'name', label: 'Nombre' },
  { key: 'email', label: 'Correo' },
  { key: 'license_number', label: 'Cédula' },
  { key: 'specialties', label: 'Especialidades' },
  { key: 'is_active', label: 'Estado' },
  { key: 'actions', label: '', class: 'text-right' },
]

async function load() {
  loading.value = true
  error.value = null
  try {
    const { data } = await doctorsApi.list({
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
    name: row.name || row.user?.name || '',
    email: row.email || row.user?.email || '',
    password: '',
    license_number: row.license_number || '',
    bio: row.bio || '',
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
    if (editing.value) {
      const payload = {
        name: form.name,
        email: form.email,
        license_number: form.license_number || null,
        bio: form.bio || null,
        is_active: form.is_active,
      }
      await doctorsApi.update(editing.value.id, payload)
    } else {
      await doctorsApi.create({
        name: form.name,
        email: form.email,
        password: form.password,
        license_number: form.license_number || null,
        bio: form.bio || null,
        is_active: form.is_active,
      })
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
  if (!confirm(`¿Eliminar a ${row.name || row.user?.name}?`)) return
  try {
    await doctorsApi.remove(row.id)
    await load()
  } catch (e) {
    alert(e.response?.data?.message || e.response?.data?.errors?.resource?.[0] || 'No se pudo eliminar.')
  }
}

onMounted(load)
</script>

<template>
  <div>
    <div class="mb-6 flex flex-wrap items-center justify-between gap-3">
      <div>
        <h1 class="page-title">Médicos</h1>
        <p class="page-subtitle">Personal médico, cédulas y especialidades.</p>
      </div>
      <button v-if="auth.can('doctors.create')" class="btn-primary" @click="openCreate">Nuevo médico</button>
    </div>

    <div class="mb-4 flex flex-wrap gap-2">
      <input v-model="filters.search" type="search" placeholder="Buscar…" class="input-field max-w-xs" @keyup.enter="filters.page = 1; load()" />
      <button class="btn-secondary" @click="filters.page = 1; load()">Buscar</button>
    </div>

    <div v-if="error" class="mb-4 rounded-xl border border-rose-200 bg-rose-50 px-3 py-2 text-sm text-rose-700">{{ error }}</div>

    <DataTable :columns="columns" :rows="items" :loading="loading" :meta="meta" @page-change="(p) => { filters.page = p; load() }">
      <template #cell-name="{ row }">{{ row.name || row.user?.name }}</template>
      <template #cell-email="{ row }">{{ row.email || row.user?.email }}</template>
      <template #cell-specialties="{ row }">
        {{ (row.specialties || []).map((s) => s.name).join(', ') || '—' }}
      </template>
      <template #cell-is_active="{ row }">
        <span :class="row.is_active ? 'text-emerald-600' : 'text-slate-400'">{{ row.is_active ? 'Activo' : 'Inactivo' }}</span>
      </template>
      <template #cell-actions="{ row }">
        <RowActions>
          <ActionButton variant="view" label="Ver" @click="router.push({ name: 'doctors.show', params: { id: row.id } })" />
          <ActionButton v-if="auth.can('doctors.update')" variant="edit" label="Editar" @click="openEdit(row)" />
          <ActionButton v-if="auth.can('doctors.delete')" variant="danger" label="Eliminar" @click="remove(row)" />
        </RowActions>
      </template>
    </DataTable>

    <BaseModal :open="modalOpen" :title="editing ? 'Editar médico' : 'Nuevo médico'" @close="modalOpen = false">
      <form class="space-y-3" @submit.prevent="save">
        <FormField label="Nombre" :error="errors.name?.[0]">
          <input v-model="form.name" required class="input-field" />
        </FormField>
        <FormField label="Correo" :error="errors.email?.[0]">
          <input v-model="form.email" type="email" required class="input-field" />
        </FormField>
        <FormField v-if="!editing" label="Contraseña" :error="errors.password?.[0]">
          <input v-model="form.password" type="password" required minlength="8" class="input-field" placeholder="Mín. 8 caracteres (obligatoria)" />
        </FormField>
        <FormField label="Cédula / licencia" :error="errors.license_number?.[0]">
          <input v-model="form.license_number" class="input-field" />
        </FormField>
        <FormField label="Bio" :error="errors.bio?.[0]">
          <textarea v-model="form.bio" rows="2" class="input-field" />
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
