<script setup>
import { computed, onMounted, reactive, ref } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import { rolesApi } from '@/modules/users/api'

const route = useRoute()
const router = useRouter()
const isEdit = computed(() => Boolean(route.params.id))

const form = reactive({
  name: '',
  permissions: [],
})

const allPermissions = ref([])
const loading = ref(false)
const saving = ref(false)
const errors = ref({})
const generalError = ref(null)
const isAdminRole = ref(false)

const grouped = computed(() => {
  const map = {}
  for (const p of allPermissions.value) {
    const [group] = p.name.split('.')
    if (!map[group]) map[group] = []
    map[group].push(p.name)
  }
  return map
})

async function load() {
  loading.value = true
  try {
    const permsRes = await rolesApi.permissions()
    allPermissions.value = permsRes.data.data

    if (isEdit.value) {
      const { data } = await rolesApi.get(route.params.id)
      const role = data.data
      form.name = role.name
      form.permissions = [...(role.permissions || [])]
      isAdminRole.value = role.name === 'admin'
    }
  } catch (e) {
    generalError.value = e.response?.data?.message || 'No se pudo cargar el formulario.'
  } finally {
    loading.value = false
  }
}

function toggle(permission) {
  const idx = form.permissions.indexOf(permission)
  if (idx >= 0) form.permissions.splice(idx, 1)
  else form.permissions.push(permission)
}

function toggleGroup(groupPerms) {
  const allSelected = groupPerms.every((p) => form.permissions.includes(p))
  if (allSelected) {
    form.permissions = form.permissions.filter((p) => !groupPerms.includes(p))
  } else {
    for (const p of groupPerms) {
      if (!form.permissions.includes(p)) form.permissions.push(p)
    }
  }
}

async function submit() {
  saving.value = true
  errors.value = {}
  generalError.value = null

  try {
    const payload = {
      name: form.name,
      permissions: form.permissions,
    }
    if (isEdit.value) {
      await rolesApi.update(route.params.id, payload)
    } else {
      await rolesApi.create(payload)
    }
    router.push({ name: 'roles.index' })
  } catch (e) {
    if (e.response?.status === 422) {
      errors.value = e.response.data.errors || {}
    } else {
      generalError.value = e.response?.data?.message || 'No se pudo guardar.'
    }
  } finally {
    saving.value = false
  }
}

onMounted(load)
</script>

<template>
  <div class="mx-auto max-w-3xl">
    <h1 class="mb-1 text-xl font-semibold text-slate-900">
      {{ isEdit ? 'Editar rol' : 'Nuevo rol' }}
    </h1>
    <p class="mb-6 text-sm text-slate-500">Marca los permisos que tendrá este rol.</p>

    <form
      v-if="!loading"
      class="space-y-5 rounded-2xl bg-white p-6 shadow-sm"
      novalidate
      @submit.prevent="submit"
    >
      <div
        v-if="generalError"
        class="rounded-lg border border-rose-200 bg-rose-50 px-3 py-2 text-sm text-rose-700"
      >
        {{ generalError }}
      </div>

      <div>
        <label class="mb-1 block text-sm font-medium text-slate-700">Nombre del rol</label>
        <input
          v-model="form.name"
          type="text"
          :disabled="isAdminRole"
          placeholder="ej. supervisor"
          class="w-full max-w-sm rounded-lg border border-slate-300 px-3 py-2 text-sm outline-none focus:border-brand-500 focus:ring-2 focus:ring-brand-200 disabled:bg-slate-50"
        />
        <p v-if="errors.name" class="mt-1 text-xs text-rose-600">{{ errors.name[0] }}</p>
      </div>

      <div class="space-y-4">
        <div
          v-for="(perms, group) in grouped"
          :key="group"
          class="rounded-xl border border-slate-200 p-4"
        >
          <div class="mb-3 flex items-center justify-between">
            <h2 class="text-sm font-semibold capitalize text-slate-800">{{ group }}</h2>
            <button
              type="button"
              class="text-xs font-medium text-brand-600 hover:underline"
              @click="toggleGroup(perms)"
            >
              Marcar / desmarcar
            </button>
          </div>
          <div class="grid gap-2 sm:grid-cols-2">
            <label
              v-for="permission in perms"
              :key="permission"
              class="flex items-center gap-2 text-sm text-slate-700"
            >
              <input
                type="checkbox"
                class="rounded border-slate-300 text-brand-600"
                :checked="form.permissions.includes(permission)"
                @change="toggle(permission)"
              />
              {{ permission }}
            </label>
          </div>
        </div>
      </div>

      <div class="flex justify-end gap-2 pt-2">
        <button
          type="button"
          class="rounded-lg border border-slate-300 px-4 py-2 text-sm font-medium text-slate-700 hover:bg-slate-50"
          @click="router.push({ name: 'roles.index' })"
        >
          Cancelar
        </button>
        <button
          type="submit"
          :disabled="saving"
          class="rounded-lg bg-brand-600 px-4 py-2 text-sm font-medium text-white hover:bg-brand-700 disabled:opacity-60"
        >
          {{ saving ? 'Guardando…' : 'Guardar' }}
        </button>
      </div>
    </form>

    <p v-else class="text-sm text-slate-400">Cargando…</p>
  </div>
</template>
