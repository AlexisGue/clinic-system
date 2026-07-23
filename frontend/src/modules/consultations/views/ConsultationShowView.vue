<script setup>
import { computed, onMounted, reactive, ref } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import FormField from '@/components/ui/FormField.vue'
import BaseModal from '@/components/ui/BaseModal.vue'
import { consultationsApi } from '@/modules/consultations/api'
import { prescriptionsApi } from '@/modules/prescriptions/api'
import { paymentsApi } from '@/modules/payments/api'
import { catalogsApi } from '@/modules/catalogs/api'
import { useAuthStore } from '@/stores/auth'
import { formatDateTime, formatMoney } from '@/utils/format'

const auth = useAuthStore()
const route = useRoute()
const router = useRouter()

const consultation = ref(null)
const paymentMethods = ref([])
const medicineOptions = ref([])
const loading = ref(false)
const saving = ref(false)
const error = ref(null)
const errors = ref({})

const clinical = reactive({
  attended_at: '',
  chief_complaint: '',
  diagnosis: '',
  treatment: '',
  observations: '',
})

const vitals = reactive({
  weight_kg: '',
  height_cm: '',
  bp_systolic: '',
  bp_diastolic: '',
  heart_rate: '',
  temperature_c: '',
  spo2: '',
  notes: '',
})

const rxOpen = ref(false)
const payOpen = ref(false)
const rxForm = reactive({
  notes: '',
  items: [{ medicine_id: '', medicine_name: '', dosage: '', frequency: '', duration: '', instructions: '' }],
})
const payForm = reactive({
  amount: '',
  payment_method_id: '',
  reference: '',
  notes: '',
})

const consultationId = computed(() => Number(route.params.id))
const isDraft = computed(() => consultation.value?.status === 'draft')

function toLocalInput(iso) {
  if (!iso) return ''
  const d = new Date(iso)
  const pad = (n) => String(n).padStart(2, '0')
  return `${d.getFullYear()}-${pad(d.getMonth() + 1)}-${pad(d.getDate())}T${pad(d.getHours())}:${pad(d.getMinutes())}`
}

async function load() {
  loading.value = true
  error.value = null
  try {
    const { data } = await consultationsApi.get(consultationId.value)
    consultation.value = data.data
    Object.assign(clinical, {
      attended_at: toLocalInput(consultation.value.attended_at),
      chief_complaint: consultation.value.chief_complaint || '',
      diagnosis: consultation.value.diagnosis || '',
      treatment: consultation.value.treatment || '',
      observations: consultation.value.observations || '',
    })
  } catch (e) {
    error.value = e.response?.data?.message || 'No se pudo cargar la consulta.'
  } finally {
    loading.value = false
  }
}

async function saveClinical() {
  saving.value = true
  errors.value = {}
  try {
    await consultationsApi.update(consultationId.value, {
      attended_at: clinical.attended_at || null,
      chief_complaint: clinical.chief_complaint || null,
      diagnosis: clinical.diagnosis || null,
      treatment: clinical.treatment || null,
      observations: clinical.observations || null,
    })
    await load()
  } catch (e) {
    if (e.response?.status === 422) errors.value = e.response.data.errors || {}
    else error.value = e.response?.data?.message || 'No se pudo guardar.'
  } finally {
    saving.value = false
  }
}

async function saveVitals() {
  saving.value = true
  errors.value = {}
  try {
    const payload = {}
    for (const [k, v] of Object.entries(vitals)) {
      if (v !== '' && v !== null) payload[k] = v
    }
    await consultationsApi.vitals(consultationId.value, payload)
    Object.assign(vitals, {
      weight_kg: '', height_cm: '', bp_systolic: '', bp_diastolic: '',
      heart_rate: '', temperature_c: '', spo2: '', notes: '',
    })
    await load()
  } catch (e) {
    if (e.response?.status === 422) errors.value = e.response.data.errors || {}
    else alert(e.response?.data?.message || 'No se pudieron guardar los signos.')
  } finally {
    saving.value = false
  }
}

async function finalize() {
  if (!confirm('¿Finalizar esta consulta?')) return
  saving.value = true
  try {
    await consultationsApi.finalize(consultationId.value)
    await load()
  } catch (e) {
    alert(e.response?.data?.message || 'No se pudo finalizar.')
  } finally {
    saving.value = false
  }
}

function openRx() {
  rxForm.notes = ''
  rxForm.items = [{ medicine_id: '', medicine_name: '', dosage: '', frequency: '', duration: '', instructions: '' }]
  errors.value = {}
  rxOpen.value = true
}

function addRxItem() {
  rxForm.items.push({ medicine_id: '', medicine_name: '', dosage: '', frequency: '', duration: '', instructions: '' })
}

function onMedicinePick(item) {
  const med = medicineOptions.value.find((m) => m.id === Number(item.medicine_id))
  if (med) item.medicine_name = med.name
}

async function saveRx() {
  saving.value = true
  errors.value = {}
  try {
    const { data } = await prescriptionsApi.create({
      consultation_id: consultationId.value,
      notes: rxForm.notes || null,
      items: rxForm.items.map((i) => ({
        medicine_id: i.medicine_id ? Number(i.medicine_id) : null,
        medicine_name: i.medicine_name || undefined,
        dosage: i.dosage || null,
        frequency: i.frequency || null,
        duration: i.duration || null,
        instructions: i.instructions || null,
      })),
    })
    rxOpen.value = false
    router.push({ name: 'prescriptions.show', params: { id: data.data.id } })
  } catch (e) {
    if (e.response?.status === 422) errors.value = e.response.data.errors || {}
    else alert(e.response?.data?.message || 'No se pudo crear la receta.')
  } finally {
    saving.value = false
  }
}

function openPay() {
  Object.assign(payForm, { amount: '', payment_method_id: '', reference: '', notes: '' })
  errors.value = {}
  payOpen.value = true
}

async function savePay() {
  saving.value = true
  errors.value = {}
  try {
    await paymentsApi.create({
      consultation_id: consultationId.value,
      amount: Number(payForm.amount),
      payment_method_id: Number(payForm.payment_method_id),
      reference: payForm.reference || null,
      notes: payForm.notes || null,
    })
    payOpen.value = false
    alert('Pago registrado.')
  } catch (e) {
    if (e.response?.status === 422) errors.value = e.response.data.errors || {}
    else alert(e.response?.data?.message || 'No se pudo registrar el pago.')
  } finally {
    saving.value = false
  }
}

onMounted(async () => {
  try {
    const [pm, meds] = await Promise.all([catalogsApi.paymentMethods(), catalogsApi.medicineOptions()])
    paymentMethods.value = pm.data.data
    medicineOptions.value = meds.data.data
  } catch {
    /* ignore */
  }
  await load()
})
</script>

<template>
  <div>
    <div class="mb-6 flex flex-wrap items-start justify-between gap-3">
      <div>
        <button type="button" class="mb-2 text-sm text-brand-700 hover:underline" @click="router.push({ name: 'consultations.index' })">
          ← Consultas
        </button>
        <h1 class="page-title">{{ consultation?.folio || 'Consulta' }}</h1>
        <p class="page-subtitle">
          {{ consultation?.patient?.full_name || '—' }} · {{ consultation?.doctor?.name || '—' }} ·
          <span :class="isDraft ? 'text-amber-600' : 'text-emerald-600'">{{ consultation?.status }}</span>
        </p>
      </div>
      <div class="flex flex-wrap gap-2">
        <button v-if="auth.can('prescriptions.create')" type="button" class="btn-secondary" @click="openRx">Crear receta</button>
        <button v-if="auth.can('payments.create')" type="button" class="btn-secondary" @click="openPay">Registrar pago</button>
        <button
          v-if="auth.can('consultations.finalize') && isDraft"
          type="button"
          :disabled="saving"
          class="btn-primary"
          @click="finalize"
        >
          Finalizar
        </button>
      </div>
    </div>

    <div v-if="error" class="mb-4 rounded-xl border border-rose-200 bg-rose-50 px-3 py-2 text-sm text-rose-700">{{ error }}</div>
    <div v-if="loading && !consultation" class="text-sm text-slate-500">Cargando…</div>

    <template v-else-if="consultation">
      <form class="card-surface mb-6 max-w-3xl space-y-3 p-5" @submit.prevent="saveClinical">
        <h2 class="font-display text-sm font-semibold text-slate-800">Datos clínicos</h2>
        <FormField label="Fecha atención" :error="errors.attended_at?.[0]">
          <input v-model="clinical.attended_at" type="datetime-local" class="input-field" :disabled="!isDraft || !auth.can('consultations.update')" />
        </FormField>
        <FormField label="Motivo de consulta" :error="errors.chief_complaint?.[0]">
          <textarea v-model="clinical.chief_complaint" rows="2" class="input-field" :disabled="!isDraft || !auth.can('consultations.update')" />
        </FormField>
        <FormField label="Diagnóstico" :error="errors.diagnosis?.[0]">
          <textarea v-model="clinical.diagnosis" rows="2" class="input-field" :disabled="!isDraft || !auth.can('consultations.update')" />
        </FormField>
        <FormField label="Tratamiento" :error="errors.treatment?.[0]">
          <textarea v-model="clinical.treatment" rows="2" class="input-field" :disabled="!isDraft || !auth.can('consultations.update')" />
        </FormField>
        <FormField label="Observaciones" :error="errors.observations?.[0]">
          <textarea v-model="clinical.observations" rows="2" class="input-field" :disabled="!isDraft || !auth.can('consultations.update')" />
        </FormField>
        <div v-if="isDraft && auth.can('consultations.update')" class="flex justify-end">
          <button type="submit" :disabled="saving" class="btn-primary">{{ saving ? 'Guardando…' : 'Guardar' }}</button>
        </div>
      </form>

      <div class="mb-6 grid gap-6 lg:grid-cols-2">
        <form v-if="isDraft && auth.can('consultations.update')" class="card-surface space-y-3 p-5" @submit.prevent="saveVitals">
          <h2 class="font-display text-sm font-semibold text-slate-800">Registrar signos vitales</h2>
          <div class="grid gap-3 sm:grid-cols-2">
            <FormField label="Peso corporal (kg)"><input v-model="vitals.weight_kg" type="number" step="0.1" class="input-field" /></FormField>
            <FormField label="Estatura (cm)"><input v-model="vitals.height_cm" type="number" step="0.1" class="input-field" /></FormField>
            <FormField label="Presión arterial máxima (sistólica)"><input v-model="vitals.bp_systolic" type="number" class="input-field" /></FormField>
            <FormField label="Presión arterial mínima (diastólica)"><input v-model="vitals.bp_diastolic" type="number" class="input-field" /></FormField>
            <FormField label="Frecuencia cardiaca (latidos/min)"><input v-model="vitals.heart_rate" type="number" class="input-field" /></FormField>
            <FormField label="Temperatura corporal (°C)"><input v-model="vitals.temperature_c" type="number" step="0.1" class="input-field" /></FormField>
            <FormField label="Oxígeno en sangre SpO₂ (%)"><input v-model="vitals.spo2" type="number" class="input-field" /></FormField>
          </div>
          <FormField label="Observaciones"><input v-model="vitals.notes" class="input-field" /></FormField>
          <div class="flex justify-end">
            <button type="submit" :disabled="saving" class="btn-primary">Guardar signos</button>
          </div>
        </form>

        <div class="card-surface p-5">
          <h2 class="mb-3 font-display text-sm font-semibold text-slate-800">Historial de signos</h2>
          <ul class="space-y-2 text-sm">
            <li
              v-for="v in (consultation.vital_signs || [])"
              :key="v.id"
              class="rounded-xl bg-slate-50/80 px-3 py-2"
            >
              <p class="font-medium text-slate-700">{{ formatDateTime(v.recorded_at) }}</p>
              <p class="text-slate-500">
                {{ v.weight_kg != null ? `Peso ${v.weight_kg} kg` : '' }}
                {{ v.height_cm != null ? `· Estatura ${v.height_cm} cm` : '' }}
                {{ v.bp_systolic != null ? `· Presión ${v.bp_systolic}/${v.bp_diastolic}` : '' }}
                {{ v.heart_rate != null ? `· Pulso ${v.heart_rate}` : '' }}
                {{ v.temperature_c != null ? `· Temp. ${v.temperature_c}°C` : '' }}
                {{ v.spo2 != null ? `· Oxígeno ${v.spo2}%` : '' }}
              </p>
            </li>
            <li v-if="!(consultation.vital_signs || []).length" class="text-slate-400">Sin registros</li>
          </ul>
        </div>
      </div>
    </template>

    <BaseModal :open="rxOpen" title="Nueva receta" @close="rxOpen = false">
      <form class="max-h-[70vh] space-y-3 overflow-y-auto" @submit.prevent="saveRx">
        <div v-for="(item, idx) in rxForm.items" :key="idx" class="space-y-2 rounded-xl border border-slate-100 p-3">
          <FormField :label="`Medicamento ${idx + 1}`">
            <select v-model="item.medicine_id" class="input-field mb-2" @change="onMedicinePick(item)">
              <option value="">Otro / manual</option>
              <option v-for="m in medicineOptions" :key="m.id" :value="m.id">{{ m.name }}</option>
            </select>
            <input v-model="item.medicine_name" required class="input-field" placeholder="Nombre" />
          </FormField>
          <div class="grid gap-2 sm:grid-cols-3">
            <input v-model="item.dosage" class="input-field" placeholder="Dosis" />
            <input v-model="item.frequency" class="input-field" placeholder="Frecuencia" />
            <input v-model="item.duration" class="input-field" placeholder="Duración" />
          </div>
          <input v-model="item.instructions" class="input-field" placeholder="Indicaciones" />
        </div>
        <button type="button" class="btn-secondary" @click="addRxItem">+ Ítem</button>
        <FormField label="Notas"><textarea v-model="rxForm.notes" rows="2" class="input-field" /></FormField>
        <div class="flex justify-end gap-2">
          <button type="button" class="btn-secondary" @click="rxOpen = false">Cancelar</button>
          <button type="submit" :disabled="saving" class="btn-primary">Crear receta</button>
        </div>
      </form>
    </BaseModal>

    <BaseModal :open="payOpen" title="Registrar pago" @close="payOpen = false">
      <form class="space-y-3" @submit.prevent="savePay">
        <FormField label="Monto" :error="errors.amount?.[0]">
          <input v-model="payForm.amount" type="number" step="0.01" min="0.01" required class="input-field" />
        </FormField>
        <FormField label="Método" :error="errors.payment_method_id?.[0]">
          <select v-model="payForm.payment_method_id" required class="input-field">
            <option value="" disabled>Seleccionar…</option>
            <option v-for="m in paymentMethods" :key="m.id" :value="m.id">{{ m.name }}</option>
          </select>
        </FormField>
        <FormField label="Referencia" :error="errors.reference?.[0]">
          <input v-model="payForm.reference" class="input-field" />
        </FormField>
        <FormField label="Notas" :error="errors.notes?.[0]">
          <textarea v-model="payForm.notes" rows="2" class="input-field" />
        </FormField>
        <div class="flex justify-end gap-2">
          <button type="button" class="btn-secondary" @click="payOpen = false">Cancelar</button>
          <button type="submit" :disabled="saving" class="btn-primary">Registrar {{ payForm.amount ? formatMoney(payForm.amount) : '' }}</button>
        </div>
      </form>
    </BaseModal>
  </div>
</template>
