<script setup>
import { onMounted, reactive, ref } from 'vue'
import { useRouter } from 'vue-router'
import { useAuthStore } from '@/stores/auth'
import { usersApi } from '@/modules/users/api'

const auth = useAuthStore()
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
    const { data } = await usersApi.list({
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

async function remove(user) {
  if (!confirm(`¿Eliminar a ${user.name}?`)) return
  try {
    await usersApi.remove(user.id)
    await load()
  } catch (e) {
    alert(e.response?.data?.message || 'No se pudo eliminar.')
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
        <h1 class="text-xl font-semibold text-slate-900">Usuarios</h1>
        <p class="text-sm text-slate-500">Gestiona cuentas, roles y estado de acceso.</p>
      </div>
      <button
        v-can="'users.create'"
        class="rounded-lg bg-brand-600 px-4 py-2 text-sm font-medium text-white hover:bg-brand-700"
        @click="router.push({ name: 'users.create' })"
      >
        Nuevo usuario
      </button>
    </div>

    <div class="mb-4 flex gap-2">
      <input
        v-model="filters.search"
        type="search"
        placeholder="Buscar por nombre o correo…"
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
            <th class="px-4 py-3">Correo</th>
            <th class="px-4 py-3">Roles</th>
            <th class="px-4 py-3">Estado</th>
            <th class="px-4 py-3 text-right">Acciones</th>
          </tr>
        </thead>
        <tbody class="divide-y divide-slate-100">
          <tr v-if="loading">
            <td colspan="5" class="px-4 py-8 text-center text-slate-400">Cargando…</td>
          </tr>
          <tr v-else-if="!items.length">
            <td colspan="5" class="px-4 py-8 text-center text-slate-400">Sin resultados</td>
          </tr>
          <tr v-for="user in items" :key="user.id" class="hover:bg-slate-50/60">
            <td class="px-4 py-3 font-medium text-slate-800">{{ user.name }}</td>
            <td class="px-4 py-3 text-slate-600">{{ user.email }}</td>
            <td class="px-4 py-3">
              <span
                v-for="role in user.roles"
                :key="role"
                class="mr-1 inline-flex rounded-md bg-brand-50 px-2 py-0.5 text-xs font-medium text-brand-700"
              >
                {{ role }}
              </span>
            </td>
            <td class="px-4 py-3">
              <span
                class="inline-flex rounded-full px-2 py-0.5 text-xs font-medium"
                :class="user.is_active ? 'bg-emerald-50 text-emerald-700' : 'bg-slate-100 text-slate-500'"
              >
                {{ user.is_active ? 'Activo' : 'Inactivo' }}
              </span>
            </td>
            <td class="px-4 py-3 text-right">
              <button
                v-can="'users.update'"
                class="mr-2 text-brand-600 hover:underline"
                @click="router.push({ name: 'users.edit', params: { id: user.id } })"
              >
                Editar
              </button>
              <button
                v-if="auth.can('users.delete') && user.id !== auth.user?.id"
                class="text-rose-600 hover:underline"
                @click="remove(user)"
              >
                Eliminar
              </button>
            </td>
          </tr>
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
