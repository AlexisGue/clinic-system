<script setup>
import { onMounted, reactive } from 'vue'
import DataTable from '@/components/ui/DataTable.vue'
import FormField from '@/components/ui/FormField.vue'
import BaseModal from '@/components/ui/BaseModal.vue'
import ActionButton from '@/components/ui/ActionButton.vue'
import RowActions from '@/components/ui/RowActions.vue'
import { useCatalog } from '@/composables/useCatalog'
import { useAuthStore } from '@/stores/auth'

defineProps({
  embedded: { type: Boolean, default: false },
})

const auth = useAuthStore()
const catalog = useCatalog('specialties')
const form = reactive({ name: '', slug: '', is_active: true })

const columns = [
  { key: 'name', label: 'Nombre' },
  { key: 'slug', label: 'Slug' },
  { key: 'is_active', label: 'Estado' },
  { key: 'actions', label: '', class: 'text-right' },
]

function openCreate() {
  Object.assign(form, catalog.openCreate({ name: '', slug: '', is_active: true }))
}

function openEdit(row) {
  Object.assign(form, catalog.openEdit(row), {
    name: row.name,
    slug: row.slug || '',
    is_active: row.is_active,
  })
}

async function save() {
  await catalog.save({
    name: form.name,
    slug: form.slug || null,
    is_active: form.is_active,
  })
}

onMounted(catalog.load)
</script>

<template>
  <div>
    <div class="mb-6 flex flex-wrap items-center justify-between gap-3">
      <div v-if="!embedded">
        <h1 class="page-title">Especialidades</h1>
        <p class="page-subtitle">Catálogo de especialidades médicas.</p>
      </div>
      <div v-else class="text-sm font-medium text-slate-600">Gestiona el catálogo de especialidades.</div>
      <button v-if="auth.can('specialties.create')" class="btn-primary" @click="openCreate">Nueva especialidad</button>
    </div>

    <div class="mb-4 flex flex-wrap gap-2">
      <input v-model="catalog.filters.search" type="search" placeholder="Buscar…" class="input-field max-w-xs" @keyup.enter="catalog.search()" />
      <button class="btn-secondary" @click="catalog.search()">Buscar</button>
    </div>

    <div v-if="catalog.error" class="mb-4 rounded-xl border border-rose-200 bg-rose-50 px-3 py-2 text-sm text-rose-700">{{ catalog.error }}</div>

    <DataTable
      :columns="columns"
      :rows="catalog.items"
      :loading="catalog.loading"
      :meta="catalog.meta"
      @page-change="catalog.setPage"
    >
      <template #cell-is_active="{ row }">
        <span :class="row.is_active ? 'text-emerald-600' : 'text-slate-400'">{{ row.is_active ? 'Activo' : 'Inactivo' }}</span>
      </template>
      <template #cell-actions="{ row }">
        <RowActions>
          <ActionButton v-if="auth.can('specialties.update')" variant="edit" label="Editar" @click="openEdit(row)" />
          <ActionButton v-if="auth.can('specialties.delete')" variant="danger" label="Eliminar" @click="catalog.remove(row, row.name)" />
        </RowActions>
      </template>
    </DataTable>

    <BaseModal :open="catalog.modalOpen" :title="catalog.editing ? 'Editar especialidad' : 'Nueva especialidad'" @close="catalog.closeModal()">
      <form class="space-y-3" @submit.prevent="save">
        <FormField label="Nombre" :error="catalog.errors.name?.[0]">
          <input v-model="form.name" required class="input-field" />
        </FormField>
        <FormField label="Slug" :error="catalog.errors.slug?.[0]">
          <input v-model="form.slug" class="input-field" placeholder="Opcional" />
        </FormField>
        <label class="flex items-center gap-2 text-sm">
          <input v-model="form.is_active" type="checkbox" class="rounded border-slate-300" />
          Activo
        </label>
        <div class="flex justify-end gap-2 pt-2">
          <button type="button" class="btn-secondary" @click="catalog.closeModal()">Cancelar</button>
          <button type="submit" :disabled="catalog.saving" class="btn-primary">{{ catalog.saving ? 'Guardando…' : 'Guardar' }}</button>
        </div>
      </form>
    </BaseModal>
  </div>
</template>
