<script setup>
import { onMounted, reactive } from 'vue'
import DataTable from '@/components/ui/DataTable.vue'
import FormField from '@/components/ui/FormField.vue'
import BaseModal from '@/components/ui/BaseModal.vue'
import { useCatalog } from '@/composables/useCatalog'
import { useAuthStore } from '@/stores/auth'

defineProps({
  embedded: { type: Boolean, default: false },
})

const auth = useAuthStore()
const catalog = useCatalog('medicines')
const form = reactive({ name: '', presentation: '', concentration: '', is_active: true })

const columns = [
  { key: 'name', label: 'Nombre' },
  { key: 'presentation', label: 'Presentación' },
  { key: 'concentration', label: 'Concentración' },
  { key: 'is_active', label: 'Estado' },
  { key: 'actions', label: '', class: 'text-right' },
]

function openCreate() {
  Object.assign(form, catalog.openCreate({ name: '', presentation: '', concentration: '', is_active: true }))
}

function openEdit(row) {
  Object.assign(form, catalog.openEdit(row), {
    name: row.name,
    presentation: row.presentation || '',
    concentration: row.concentration || '',
    is_active: row.is_active,
  })
}

async function save() {
  await catalog.save({
    name: form.name,
    presentation: form.presentation || null,
    concentration: form.concentration || null,
    is_active: form.is_active,
  })
}

onMounted(catalog.load)
</script>

<template>
  <div>
    <div class="mb-6 flex flex-wrap items-center justify-between gap-3">
      <div v-if="!embedded">
        <h1 class="page-title">Medicamentos</h1>
        <p class="page-subtitle">Catálogo para recetas médicas.</p>
      </div>
      <div v-else class="text-sm font-medium text-slate-600">Gestiona el catálogo de medicamentos.</div>
      <button v-if="auth.can('medicines.create')" class="btn-primary" @click="openCreate">Nuevo medicamento</button>
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
      <template #cell-presentation="{ row }">{{ row.presentation || '—' }}</template>
      <template #cell-concentration="{ row }">{{ row.concentration || '—' }}</template>
      <template #cell-is_active="{ row }">
        <span :class="row.is_active ? 'text-emerald-600' : 'text-slate-400'">{{ row.is_active ? 'Activo' : 'Inactivo' }}</span>
      </template>
      <template #cell-actions="{ row }">
        <button v-if="auth.can('medicines.update')" class="mr-2 text-brand-700 hover:underline" @click="openEdit(row)">Editar</button>
        <button v-if="auth.can('medicines.delete')" class="text-rose-600 hover:underline" @click="catalog.remove(row, row.name)">Eliminar</button>
      </template>
    </DataTable>

    <BaseModal :open="catalog.modalOpen" :title="catalog.editing ? 'Editar medicamento' : 'Nuevo medicamento'" @close="catalog.closeModal()">
      <form class="space-y-3" @submit.prevent="save">
        <FormField label="Nombre" :error="catalog.errors.name?.[0]">
          <input v-model="form.name" required class="input-field" />
        </FormField>
        <FormField label="Presentación" :error="catalog.errors.presentation?.[0]">
          <input v-model="form.presentation" class="input-field" />
        </FormField>
        <FormField label="Concentración" :error="catalog.errors.concentration?.[0]">
          <input v-model="form.concentration" class="input-field" />
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
