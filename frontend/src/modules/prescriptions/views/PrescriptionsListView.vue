<script setup>
import { onMounted, reactive, ref } from 'vue'
import { useRouter } from 'vue-router'
import DataTable from '@/components/ui/DataTable.vue'
import ActionButton from '@/components/ui/ActionButton.vue'
import RowActions from '@/components/ui/RowActions.vue'
import { prescriptionsApi } from '@/modules/prescriptions/api'
import { useAuthStore } from '@/stores/auth'
import { formatDateTime } from '@/utils/format'

const auth = useAuthStore()
const router = useRouter()

const items = ref([])
const meta = ref({ current_page: 1, last_page: 1, total: 0 })
const loading = ref(false)
const error = ref(null)
const filters = reactive({ search: '', status: '', page: 1 })

const columns = [
  { key: 'folio', label: 'Folio' },
  { key: 'issued_at', label: 'Emisión' },
  { key: 'patient', label: 'Paciente' },
  { key: 'doctor', label: 'Médico' },
  { key: 'status', label: 'Estado' },
  { key: 'actions', label: '', class: 'text-right' },
]

async function load() {
  loading.value = true
  error.value = null
  try {
    const { data } = await prescriptionsApi.list({
      search: filters.search || undefined,
      status: filters.status || undefined,
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

onMounted(load)
</script>

<template>
  <div>
    <div class="mb-6">
      <h1 class="page-title">Recetas</h1>
      <p class="page-subtitle">Prescripciones emitidas y PDF.</p>
    </div>

    <div class="mb-4 flex flex-wrap gap-2">
      <input v-model="filters.search" type="search" placeholder="Buscar…" class="input-field max-w-xs" @keyup.enter="filters.page = 1; load()" />
      <select v-model="filters.status" class="input-field !w-auto">
        <option value="">Todos</option>
        <option value="active">Activa</option>
        <option value="cancelled">Cancelada</option>
      </select>
      <button class="btn-secondary" @click="filters.page = 1; load()">Buscar</button>
    </div>

    <div v-if="error" class="mb-4 rounded-xl border border-rose-200 bg-rose-50 px-3 py-2 text-sm text-rose-700">{{ error }}</div>

    <DataTable :columns="columns" :rows="items" :loading="loading" :meta="meta" @page-change="(p) => { filters.page = p; load() }">
      <template #cell-issued_at="{ row }">{{ formatDateTime(row.issued_at) }}</template>
      <template #cell-patient="{ row }">{{ row.patient?.full_name || '—' }}</template>
      <template #cell-doctor="{ row }">{{ row.doctor?.name || '—' }}</template>
      <template #cell-actions="{ row }">
        <RowActions>
          <ActionButton variant="view" label="Ver" @click="router.push({ name: 'prescriptions.show', params: { id: row.id } })" />
        </RowActions>
      </template>
    </DataTable>
  </div>
</template>
