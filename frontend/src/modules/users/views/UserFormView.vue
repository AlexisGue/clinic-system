<script setup>
import { computed, onMounted, reactive, ref } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import { rolesApi, usersApi } from '@/modules/users/api'

const route = useRoute()
const router = useRouter()
const isEdit = computed(() => Boolean(route.params.id))

const form = reactive({
  name: '',
  email: '',
  password: '',
  password_confirmation: '',
  is_active: true,
  roles: [],
})

const roleOptions = ref([])
const loading = ref(false)
const saving = ref(false)
const errors = ref({})
const generalError = ref(null)

async function load() {
  loading.value = true
  try {
    const rolesRes = await rolesApi.options()
    roleOptions.value = rolesRes.data.data

    if (isEdit.value) {
      const { data } = await usersApi.get(route.params.id)
      const user = data.data
      form.name = user.name
      form.email = user.email
      form.is_active = user.is_active
      form.roles = [...(user.roles || [])]
    }
  } catch (e) {
    generalError.value = e.response?.data?.message || 'No se pudo cargar el formulario.'
  } finally {
    loading.value = false
  }
}

function toggleRole(name) {
  const idx = form.roles.indexOf(name)
  if (idx >= 0) form.roles.splice(idx, 1)
  else form.roles.push(name)
}

async function submit() {
  saving.value = true
  errors.value = {}
  generalError.value = null

  const payload = {
    name: form.name,
    email: form.email,
    is_active: form.is_active,
    roles: form.roles,
  }

  if (form.password) {
    payload.password = form.password
    payload.password_confirmation = form.password_confirmation
  }

  try {
    if (isEdit.value) {
      await usersApi.update(route.params.id, payload)
    } else {
      payload.password = form.password
      payload.password_confirmation = form.password_confirmation
      await usersApi.create(payload)
    }
    router.push({ name: 'users.index' })
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
  <div class="mx-auto max-w-xl">
    <h1 class="mb-1 text-xl font-semibold text-slate-900">
      {{ isEdit ? 'Editar usuario' : 'Nuevo usuario' }}
    </h1>
    <p class="mb-6 text-sm text-slate-500">Asigna roles para controlar el acceso al sistema.</p>

    <form
      v-if="!loading"
      class="space-y-4 rounded-2xl bg-white p-6 shadow-sm"
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
        <label class="mb-1 block text-sm font-medium text-slate-700">Nombre</label>
        <input v-model="form.name" type="text" class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm outline-none focus:border-brand-500 focus:ring-2 focus:ring-brand-200" />
        <p v-if="errors.name" class="mt-1 text-xs text-rose-600">{{ errors.name[0] }}</p>
      </div>

      <div>
        <label class="mb-1 block text-sm font-medium text-slate-700">Correo</label>
        <input v-model="form.email" type="email" class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm outline-none focus:border-brand-500 focus:ring-2 focus:ring-brand-200" />
        <p v-if="errors.email" class="mt-1 text-xs text-rose-600">{{ errors.email[0] }}</p>
      </div>

      <div class="grid gap-4 sm:grid-cols-2">
        <div>
          <label class="mb-1 block text-sm font-medium text-slate-700">
            Contraseña {{ isEdit ? '(opcional)' : '' }}
          </label>
          <input v-model="form.password" type="password" autocomplete="new-password" class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm outline-none focus:border-brand-500 focus:ring-2 focus:ring-brand-200" />
          <p v-if="errors.password" class="mt-1 text-xs text-rose-600">{{ errors.password[0] }}</p>
        </div>
        <div>
          <label class="mb-1 block text-sm font-medium text-slate-700">Confirmar</label>
          <input v-model="form.password_confirmation" type="password" autocomplete="new-password" class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm outline-none focus:border-brand-500 focus:ring-2 focus:ring-brand-200" />
        </div>
      </div>

      <label class="flex items-center gap-2 text-sm text-slate-700">
        <input v-model="form.is_active" type="checkbox" class="rounded border-slate-300 text-brand-600" />
        Cuenta activa
      </label>

      <div>
        <p class="mb-2 text-sm font-medium text-slate-700">Roles</p>
        <div class="flex flex-wrap gap-2">
          <button
            v-for="role in roleOptions"
            :key="role.id"
            type="button"
            class="rounded-lg border px-3 py-1.5 text-xs font-medium transition"
            :class="form.roles.includes(role.name)
              ? 'border-brand-600 bg-brand-50 text-brand-700'
              : 'border-slate-200 text-slate-600 hover:bg-slate-50'"
            @click="toggleRole(role.name)"
          >
            {{ role.name }}
          </button>
        </div>
        <p v-if="errors.roles" class="mt-1 text-xs text-rose-600">{{ errors.roles[0] }}</p>
      </div>

      <div class="flex justify-end gap-2 pt-2">
        <button
          type="button"
          class="rounded-lg border border-slate-300 px-4 py-2 text-sm font-medium text-slate-700 hover:bg-slate-50"
          @click="router.push({ name: 'users.index' })"
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
