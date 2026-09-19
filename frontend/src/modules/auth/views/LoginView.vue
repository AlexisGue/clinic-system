<script setup>
import { computed, onMounted, reactive, ref } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import { useAuthStore } from '@/stores/auth'
import { authApi } from '@/modules/auth/api'

const REMEMBER_EMAIL_KEY = 'clinic.login.email'

const auth = useAuthStore()
const router = useRouter()
const route = useRoute()

const form = reactive({
  email: localStorage.getItem(REMEMBER_EMAIL_KEY) || '',
  password: '',
  remember: Boolean(localStorage.getItem(REMEMBER_EMAIL_KEY)),
})

const loading = ref(false)
const errors = ref({})
const generalError = ref(null)
const showPassword = ref(false)
const capsLockOn = ref(false)
const clinicName = ref('Clinic System')
const tagline = ref('Atención más ordenada: pacientes, agenda, consultas y recetas en un solo lugar.')
const serverWaking = ref(false)

const brandTitle = computed(() => clinicName.value || 'Clinic System')

/** Ping /up so Render starts waking while the user types credentials. */
async function wakeApi(timeoutMs = 90000) {
  const controller = new AbortController()
  const timer = setTimeout(() => controller.abort(), timeoutMs)
  try {
    const res = await fetch('/up', { signal: controller.signal, cache: 'no-store' })
    return res.ok
  } catch {
    return false
  } finally {
    clearTimeout(timer)
  }
}

onMounted(async () => {
  if (route.query.reason === 'session') {
    generalError.value = 'Tu sesión expiró. Inicia sesión de nuevo para continuar.'
  }

  serverWaking.value = true
  const awake = await wakeApi(25000)
  serverWaking.value = !awake
  if (!awake) {
    generalError.value = 'El servidor está despertando (plan free). Espera ~1 minuto e intenta entrar.'
    // Keep trying in background so login works when ready.
    wakeApi(120000).then((ok) => {
      serverWaking.value = !ok
      if (ok && generalError.value?.includes('despertando')) {
        generalError.value = null
      }
    })
  }

  try {
    const { data } = await Promise.race([
      authApi.branding(),
      new Promise((_, reject) => setTimeout(() => reject(new Error('timeout')), 4000)),
    ])
    clinicName.value = data.data.clinic_name || clinicName.value
    tagline.value = data.data.tagline || tagline.value
  } catch {
    /* branding is optional on login */
  }
})

function onPasswordKeyEvent(event) {
  if (typeof event.getModifierState === 'function') {
    capsLockOn.value = event.getModifierState('CapsLock')
  }
}

async function submit() {
  loading.value = true
  errors.value = {}
  generalError.value = null

  try {
    // If API still cold, wake it before CSRF/login.
    if (serverWaking.value) {
      const ok = await wakeApi(90000)
      serverWaking.value = !ok
      if (!ok) {
        generalError.value = 'El servidor sigue despertando. Espera un momento e intenta de nuevo.'
        return
      }
    }

    await auth.login({
      email: form.email,
      password: form.password,
      remember: form.remember,
    })

    if (form.remember) {
      localStorage.setItem(REMEMBER_EMAIL_KEY, form.email.trim())
    } else {
      localStorage.removeItem(REMEMBER_EMAIL_KEY)
    }

    const redirect = route.query.redirect
    router.push(typeof redirect === 'string' && redirect.startsWith('/') ? redirect : { name: 'dashboard' })
  } catch (error) {
    const status = error.response?.status
    const data = error.response?.data

    if (error.message === 'timeout' || error.code === 'ECONNABORTED' || error.name === 'AbortError') {
      serverWaking.value = true
      generalError.value = 'El servidor está despertando. Espera ~1 minuto e intenta de nuevo.'
    } else if (status === 422) {
      errors.value = data?.errors ?? {}
      const fieldMessage = errors.value.email?.[0] || errors.value.password?.[0]
      generalError.value = fieldMessage
        || data?.message
        || 'Correo o contraseña incorrectos. Verifica e intenta de nuevo.'
    } else if (status === 429) {
      generalError.value = 'Demasiados intentos. Espera un minuto e intenta de nuevo.'
    } else if (status === 419) {
      generalError.value = 'La sesión de seguridad expiró. Recarga la página e intenta de nuevo.'
    } else if (status >= 500) {
      generalError.value = 'Error del servidor. Si persiste, reinicia el servicio en Render y espera a que /up diga Application up.'
    } else {
      generalError.value = data?.message
        || 'No se pudo conectar con el servidor. Intenta más tarde.'
    }
  } finally {
    loading.value = false
  }
}
</script>

<template>
  <div class="relative flex min-h-screen overflow-hidden">
    <div class="pointer-events-none absolute inset-0">
      <div class="absolute -left-24 top-0 h-[28rem] w-[28rem] rounded-full bg-sky-400/20 blur-3xl" />
      <div class="absolute -right-16 bottom-0 h-[24rem] w-[24rem] rounded-full bg-brand-600/15 blur-3xl" />
      <div
        class="absolute inset-0 opacity-40"
        style="background-image: radial-gradient(circle at 1px 1px, rgb(37 99 235 / 0.12) 1px, transparent 0); background-size: 26px 26px;"
      />
    </div>

    <section class="relative hidden w-[48%] flex-col justify-between p-10 text-white lg:flex xl:w-[52%]">
      <div class="absolute inset-4 overflow-hidden rounded-[2rem] bg-ink-950">
        <div class="absolute inset-0 bg-gradient-to-br from-brand-700 via-sky-900 to-ink-950" />
        <div class="absolute -right-16 top-16 h-72 w-72 rounded-full bg-sky-400/20 blur-3xl animate-float" />
        <div class="absolute -left-10 bottom-8 h-56 w-56 rounded-full bg-brand-400/15 blur-2xl" />
        <div class="absolute right-16 top-1/2 -translate-y-1/2 opacity-[0.07]">
          <div class="relative h-48 w-48">
            <div class="absolute left-1/2 top-0 h-full w-14 -translate-x-1/2 rounded-full bg-white" />
            <div class="absolute left-0 top-1/2 h-14 w-full -translate-y-1/2 rounded-full bg-white" />
          </div>
        </div>
      </div>

      <div class="relative z-10 animate-fade-in-slow">
        <div class="mb-6 flex h-12 w-12 items-center justify-center rounded-2xl bg-white/10 ring-1 ring-white/20">
          <svg class="h-6 w-6 text-sky-200" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" d="M4.318 6.318a4.5 4.5 0 000 6.364L12 20.364l7.682-7.682a4.5 4.5 0 00-6.364-6.364L12 7.636l-1.318-1.318a4.5 4.5 0 00-6.364 0z" />
          </svg>
        </div>
        <p class="font-display text-4xl font-semibold tracking-tight xl:text-5xl">
          {{ brandTitle }}
        </p>
        <p class="mt-5 max-w-md text-base leading-relaxed text-sky-100/90">
          {{ tagline }}
        </p>
      </div>

      <div class="relative z-10 space-y-3 animate-fade-in-slow" style="animation-delay: 120ms">
        <div class="flex items-center gap-3 rounded-2xl bg-white/5 px-4 py-3 ring-1 ring-white/10 backdrop-blur-sm">
          <span class="flex h-9 w-9 items-center justify-center rounded-xl bg-sky-400/20 text-sky-100">
            <svg class="h-4 w-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" />
            </svg>
          </span>
          <div>
            <p class="text-sm font-medium">Agenda clínica</p>
            <p class="text-xs text-white/55">Citas sin solapes y horarios por médico</p>
          </div>
        </div>
        <div class="flex items-center gap-3 rounded-2xl bg-white/5 px-4 py-3 ring-1 ring-white/10 backdrop-blur-sm">
          <span class="flex h-9 w-9 items-center justify-center rounded-xl bg-sky-400/20 text-sky-100">
            <svg class="h-4 w-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
            </svg>
          </span>
          <div>
            <p class="text-sm font-medium">Expediente y recetas</p>
            <p class="text-xs text-white/55">Diagnóstico, signos vitales y PDF listos para imprimir</p>
          </div>
        </div>
      </div>
    </section>

    <section class="relative z-10 flex flex-1 items-center justify-center p-6 sm:p-10">
      <div class="w-full max-w-[420px] animate-fade-in">
        <div class="mb-8 lg:hidden">
          <div class="mb-4 flex h-12 w-12 items-center justify-center rounded-2xl bg-brand-600 text-white">
            <svg class="h-6 w-6" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" d="M4.318 6.318a4.5 4.5 0 000 6.364L12 20.364l7.682-7.682a4.5 4.5 0 00-6.364-6.364L12 7.636l-1.318-1.318a4.5 4.5 0 00-6.364 0z" />
            </svg>
          </div>
          <h1 class="font-display text-3xl font-semibold tracking-tight text-slate-900">{{ brandTitle }}</h1>
          <p class="mt-1 text-sm text-slate-500">Inicia sesión para continuar</p>
        </div>

        <div class="mb-8 hidden lg:block">
          <p class="text-sm font-medium text-brand-700">Bienvenido de nuevo</p>
          <h1 class="font-display mt-1 text-3xl font-semibold tracking-tight text-slate-900">Inicia sesión</h1>
          <p class="mt-1 text-sm text-slate-500">Accede con tu cuenta de {{ brandTitle }}.</p>
        </div>

        <form class="card-surface space-y-5 p-6 sm:p-8" novalidate @submit.prevent="submit">
          <div
            v-if="serverWaking"
            class="rounded-xl border border-amber-200 bg-amber-50 px-3.5 py-2.5 text-sm text-amber-800"
            role="status"
          >
            Despertando el servidor… esto puede tardar hasta 1 minuto en el plan gratuito.
          </div>

          <div
            v-if="generalError"
            class="rounded-xl border border-rose-200 bg-rose-50 px-3.5 py-2.5 text-sm text-rose-700"
            role="alert"
          >
            {{ generalError }}
          </div>

          <div>
            <label for="email" class="mb-1.5 block text-sm font-medium text-slate-700">Correo electrónico</label>
            <input
              id="email"
              v-model="form.email"
              type="email"
              autocomplete="username"
              autofocus
              required
              class="input-field"
              :class="{ 'border-rose-400 focus:border-rose-400 focus:ring-rose-200': errors.email }"
              placeholder="tucorreo@clinica.com"
            />
            <p v-if="errors.email && !generalError" class="mt-1.5 text-xs text-rose-600">{{ errors.email[0] }}</p>
          </div>

          <div>
            <label for="password" class="mb-1.5 block text-sm font-medium text-slate-700">Contraseña</label>
            <div class="relative">
              <input
                id="password"
                v-model="form.password"
                :type="showPassword ? 'text' : 'password'"
                autocomplete="current-password"
                required
                class="input-field pr-11"
                :class="{ 'border-rose-400 focus:border-rose-400 focus:ring-rose-200': errors.password }"
                placeholder="••••••••"
                @keydown="onPasswordKeyEvent"
                @keyup="onPasswordKeyEvent"
              />
              <button
                type="button"
                class="absolute inset-y-0 right-0 px-3 text-slate-400 transition hover:text-slate-600"
                :aria-label="showPassword ? 'Ocultar contraseña' : 'Mostrar contraseña'"
                @click="showPassword = !showPassword"
              >
                <svg v-if="!showPassword" class="h-5 w-5" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                  <path stroke-linecap="round" stroke-linejoin="round" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
                </svg>
                <svg v-else class="h-5 w-5" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" d="M13.875 18.825A10.05 10.05 0 0112 19c-4.478 0-8.268-2.943-9.543-7a9.97 9.97 0 011.563-3.029m5.858.908a3 3 0 114.243 4.243M9.878 9.878l4.242 4.242M9.88 9.88l-3.29-3.29m7.532 7.532l3.29 3.29M3 3l3.59 3.59m0 0A9.953 9.953 0 0112 5c4.478 0 8.268 2.943 9.543 7a10.025 10.025 0 01-4.132 5.411m0 0L21 21" />
                </svg>
              </button>
            </div>
            <p v-if="capsLockOn" class="mt-1.5 text-xs font-medium text-amber-600">Bloq Mayús está activado</p>
            <p v-if="errors.password && !generalError" class="mt-1.5 text-xs text-rose-600">{{ errors.password[0] }}</p>
          </div>

          <label class="flex cursor-pointer items-center gap-2 text-sm text-slate-600">
            <input v-model="form.remember" type="checkbox" class="rounded border-slate-300 text-brand-600 focus:ring-brand-500" />
            Recordarme en este equipo
          </label>

          <button type="submit" :disabled="loading" class="btn-primary w-full py-3">
            <svg v-if="loading" class="h-4 w-4 animate-spin" viewBox="0 0 24 24" fill="none">
              <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4" />
              <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v4a4 4 0 00-4 4H4z" />
            </svg>
            {{ loading ? 'Iniciando sesión…' : 'Entrar al sistema' }}
          </button>
        </form>

        <p class="mt-6 text-center text-xs text-slate-400">
          Acceso restringido · Acciones auditadas
        </p>
      </div>
    </section>
  </div>
</template>
