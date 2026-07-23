<script setup>
import { onMounted, reactive, ref } from 'vue'
import { auditsApi } from '@/modules/audit/api'

const loading = ref(false)
const items = ref([])
const meta = ref({ current_page: 1, last_page: 1, total: 0 })
const filters = reactive({ event: '', page: 1 })
const error = ref(null)
const expanded = ref(null)

async function load() {
  loading.value = true
  error.value = null
  try {
    const { data } = await auditsApi.list({
      event: filters.event || undefined,
      page: filters.page,
    })
    items.value = data.data.items
    meta.value = data.data.meta
  } catch (e) {
    error.value = e.response?.data?.message || 'No se pudo cargar la auditoría.'
  } finally {
    loading.value = false
  }
}

function toggle(id) {
  expanded.value = expanded.value === id ? null : id
}

onMounted(load)
</script>

<template>
  <div>
    <div class="mb-6">
      <h1 class="text-xl font-semibold text-slate-900">Auditoría</h1>
      <p class="text-sm text-slate-500">Historial de cambios en el sistema (quién, qué, cuándo, desde dónde).</p>
    </div>

    <div class="mb-4 flex gap-2">
      <select
        v-model="filters.event"
        class="rounded-lg border border-slate-300 px-3 py-2 text-sm"
        @change="filters.page = 1; load()"
      >
        <option value="">Todos los eventos</option>
        <option value="created">created</option>
        <option value="updated">updated</option>
        <option value="deleted">deleted</option>
      </select>
    </div>

    <div v-if="error" class="mb-4 rounded-lg border border-rose-200 bg-rose-50 px-3 py-2 text-sm text-rose-700">
      {{ error }}
    </div>

    <div class="overflow-hidden rounded-2xl bg-white shadow-sm">
      <table class="min-w-full divide-y divide-slate-200 text-sm">
        <thead class="bg-slate-50 text-left text-xs font-semibold uppercase tracking-wide text-slate-500">
          <tr>
            <th class="px-4 py-3">Fecha</th>
            <th class="px-4 py-3">Usuario</th>
            <th class="px-4 py-3">Evento</th>
            <th class="px-4 py-3">Modelo</th>
            <th class="px-4 py-3">IP</th>
            <th class="px-4 py-3" />
          </tr>
        </thead>
        <tbody class="divide-y divide-slate-100">
          <tr v-if="loading">
            <td colspan="6" class="px-4 py-8 text-center text-slate-400">Cargando…</td>
          </tr>
          <tr v-else-if="!items.length">
            <td colspan="6" class="px-4 py-8 text-center text-slate-400">Sin registros todavía</td>
          </tr>
          <template v-for="row in items" :key="row.id">
            <tr class="hover:bg-slate-50/60">
              <td class="px-4 py-3 text-slate-600">{{ row.created_at }}</td>
              <td class="px-4 py-3 font-medium text-slate-800">{{ row.user_name || '—' }}</td>
              <td class="px-4 py-3">
                <span class="rounded-md bg-slate-100 px-2 py-0.5 text-xs font-medium text-slate-700">
                  {{ row.event }}
                </span>
              </td>
              <td class="px-4 py-3 text-slate-600">{{ row.auditable_type }} #{{ row.auditable_id }}</td>
              <td class="px-4 py-3 text-slate-500">{{ row.ip_address || '—' }}</td>
              <td class="px-4 py-3 text-right">
                <button class="text-brand-600 hover:underline" @click="toggle(row.id)">
                  {{ expanded === row.id ? 'Ocultar' : 'Detalle' }}
                </button>
              </td>
            </tr>
            <tr v-if="expanded === row.id">
              <td colspan="6" class="bg-slate-50 px-4 py-3">
                <div class="grid gap-4 sm:grid-cols-2">
                  <div>
                    <p class="mb-1 text-xs font-semibold uppercase text-slate-500">Antes</p>
                    <pre class="overflow-auto rounded-lg bg-white p-3 text-xs text-slate-700">{{ JSON.stringify(row.old_values, null, 2) }}</pre>
                  </div>
                  <div>
                    <p class="mb-1 text-xs font-semibold uppercase text-slate-500">Después</p>
                    <pre class="overflow-auto rounded-lg bg-white p-3 text-xs text-slate-700">{{ JSON.stringify(row.new_values, null, 2) }}</pre>
                  </div>
                </div>
              </td>
            </tr>
          </template>
        </tbody>
      </table>
    </div>

    <div v-if="meta.last_page > 1" class="mt-4 flex items-center justify-between text-sm text-slate-500">
      <span>{{ meta.total }} registros</span>
      <div class="flex gap-2">
        <button
          :disabled="meta.current_page <= 1"
          class="rounded border px-2 py-1 disabled:opacity-40"
          @click="filters.page--; load()"
        >
          Anterior
        </button>
        <span>Pág. {{ meta.current_page }} / {{ meta.last_page }}</span>
        <button
          :disabled="meta.current_page >= meta.last_page"
          class="rounded border px-2 py-1 disabled:opacity-40"
          @click="filters.page++; load()"
        >
          Siguiente
        </button>
      </div>
    </div>
  </div>
</template>
