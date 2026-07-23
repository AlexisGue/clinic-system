<script setup>
import { onMounted, reactive, ref } from 'vue'
import { useRouter } from 'vue-router'
import { rolesApi } from '@/modules/users/api'

const router = useRouter()
const loading = ref(false)
const items = ref([])
const meta = ref({ current_page: 1, last_page: 1, total: 0 })
const filters = reactive({ search: '', page: 1 })
const error = ref(null)

async function load() {
  loading.value = true
  error.value = null
  try {
    const { data } = await rolesApi.list({
      search: filters.search || undefined,
      page: filters.page,
    })
    items.value = data.data.items
    meta.value = data.data.meta
  } catch (e) {
    error.value = e.response?.data?.message || 'No se pudo cargar la lista.'
  } finally {
    loading.value = false
  }
}

async function remove(role) {
  if (!confirm(`¿Eliminar el rol ${role.name}?`)) return
  try {
    await rolesApi.remove(role.id)
    await load()
  } catch (e) {
    alert(e.response?.data?.message || e.response?.data?.errors?.role?.[0] || 'No se pudo eliminar.')
  }
}

function search() {
  filters.page = 1
  load()
}

onMounted(load)
</script>

<template>
  <div>
    <div class="mb-6 flex flex-wrap items-center justify-between gap-3">
      <div>
        <h1 class="text-xl font-semibold text-slate-900">Roles</h1>
        <p class="text-sm text-slate-500">Define qué puede hacer cada tipo de usuario.</p>
      </div>
      <button
        v-can="'roles.create'"
        class="rounded-lg bg-brand-600 px-4 py-2 text-sm font-medium text-white hover:bg-brand-700"
        @click="router.push({ name: 'roles.create' })"
      >
        Nuevo rol
      </button>
    </div>

    <div class="mb-4 flex gap-2">
      <input
        v-model="filters.search"
        type="search"
        placeholder="Buscar rol…"
        class="w-full max-w-sm rounded-lg border border-slate-300 px-3 py-2 text-sm outline-none focus:border-brand-500 focus:ring-2 focus:ring-brand-200"
        @keyup.enter="search"
      />
      <button
        class="rounded-lg border border-slate-300 bg-white px-3 py-2 text-sm font-medium text-slate-700 hover:bg-slate-50"
        @click="search"
      >
        Buscar
      </button>
    </div>

    <div v-if="error" class="mb-4 rounded-lg border border-rose-200 bg-rose-50 px-3 py-2 text-sm text-rose-700">
      {{ error }}
    </div>

    <div class="overflow-hidden rounded-2xl bg-white shadow-sm">
      <table class="min-w-full divide-y divide-slate-200 text-sm">
        <thead class="bg-slate-50 text-left text-xs font-semibold uppercase tracking-wide text-slate-500">
          <tr>
            <th class="px-4 py-3">Nombre</th>
            <th class="px-4 py-3">Permisos</th>
            <th class="px-4 py-3">Usuarios</th>
            <th class="px-4 py-3 text-right">Acciones</th>
          </tr>
        </thead>
        <tbody class="divide-y divide-slate-100">
          <tr v-if="loading">
            <td colspan="4" class="px-4 py-8 text-center text-slate-400">Cargando…</td>
          </tr>
          <tr v-else-if="!items.length">
            <td colspan="4" class="px-4 py-8 text-center text-slate-400">Sin resultados</td>
          </tr>
          <tr v-for="role in items" :key="role.id" class="hover:bg-slate-50/60">
            <td class="px-4 py-3 font-medium text-slate-800">{{ role.name }}</td>
            <td class="px-4 py-3 text-slate-500">{{ role.permissions?.length ?? 0 }}</td>
            <td class="px-4 py-3 text-slate-500">{{ role.users_count ?? 0 }}</td>
            <td class="px-4 py-3 text-right">
              <button
                v-can="'roles.update'"
                class="mr-2 text-brand-600 hover:underline"
                @click="router.push({ name: 'roles.edit', params: { id: role.id } })"
              >
                Editar
              </button>
              <button
                v-if="role.name !== 'admin'"
                v-can="'roles.delete'"
                class="text-rose-600 hover:underline"
                @click="remove(role)"
              >
                Eliminar
              </button>
            </td>
          </tr>
        </tbody>
      </table>
    </div>
  </div>
</template>
