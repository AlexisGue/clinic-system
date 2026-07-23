<script setup>
import { computed, onMounted, ref } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import { prescriptionsApi } from '@/modules/prescriptions/api'
import { useAuthStore } from '@/stores/auth'
import { formatDateTime } from '@/utils/format'
import api from '@/api/axios'

const auth = useAuthStore()
const route = useRoute()
const router = useRouter()

const prescription = ref(null)
const loading = ref(false)
const working = ref(false)
const error = ref(null)

const prescriptionId = computed(() => Number(route.params.id))
const isActive = computed(() => prescription.value?.status === 'active')

async function load() {
  loading.value = true
  error.value = null
  try {
    const { data } = await prescriptionsApi.get(prescriptionId.value)
    prescription.value = data.data
  } catch (e) {
    error.value = e.response?.data?.message || 'No se pudo cargar la receta.'
  } finally {
    loading.value = false
  }
}

async function cancel() {
  if (!confirm('¿Cancelar esta receta?')) return
  working.value = true
  try {
    await prescriptionsApi.cancel(prescriptionId.value)
    await load()
  } catch (e) {
    alert(e.response?.data?.message || 'No se pudo cancelar.')
  } finally {
    working.value = false
  }
}

async function openPdf() {
  working.value = true
  try {
    const response = await api.get(`/prescriptions/${prescriptionId.value}/pdf`, { responseType: 'blob' })
    const url = URL.createObjectURL(response.data)
    window.open(url, '_blank')
    setTimeout(() => URL.revokeObjectURL(url), 60_000)
  } catch (e) {
    // Fallback: same-site cookie navigation
    try {
      window.open(prescriptionsApi.pdfUrl(prescriptionId.value), '_blank')
    } catch {
      alert(e.response?.data?.message || 'No se pudo abrir el PDF.')
    }
  } finally {
    working.value = false
  }
}

onMounted(load)
</script>

<template>
  <div>
    <div class="mb-6 flex flex-wrap items-start justify-between gap-3">
      <div>
        <button type="button" class="mb-2 text-sm text-brand-700 hover:underline" @click="router.push({ name: 'prescriptions.index' })">
          ← Recetas
        </button>
        <h1 class="page-title">{{ prescription?.folio || 'Receta' }}</h1>
        <p class="page-subtitle">
          {{ prescription?.patient?.full_name || '—' }} · {{ prescription?.doctor?.name || '—' }} ·
          {{ formatDateTime(prescription?.issued_at) }} · {{ prescription?.status }}
        </p>
      </div>
      <div class="flex flex-wrap gap-2">
        <button v-if="auth.can('prescriptions.export')" type="button" :disabled="working" class="btn-secondary" @click="openPdf">
          Abrir PDF
        </button>
        <button
          v-if="auth.can('prescriptions.cancel') && isActive"
          type="button"
          :disabled="working"
          class="btn-primary !bg-rose-600 hover:!bg-rose-700"
          @click="cancel"
        >
          Cancelar receta
        </button>
      </div>
    </div>

    <div v-if="error" class="mb-4 rounded-xl border border-rose-200 bg-rose-50 px-3 py-2 text-sm text-rose-700">{{ error }}</div>
    <div v-if="loading && !prescription" class="text-sm text-slate-500">Cargando…</div>

    <template v-else-if="prescription">
      <div v-if="prescription.notes" class="card-surface mb-4 p-4 text-sm text-slate-600">
        <p class="mb-1 text-xs font-semibold uppercase tracking-wider text-slate-400">Notas</p>
        {{ prescription.notes }}
      </div>

      <div class="card-surface overflow-hidden">
        <table class="min-w-full divide-y divide-slate-100 text-sm">
          <thead class="bg-slate-50/90 text-left text-[11px] font-semibold uppercase tracking-wider text-slate-500">
            <tr>
              <th class="px-4 py-3">Medicamento</th>
              <th class="px-4 py-3">Dosis</th>
              <th class="px-4 py-3">Frecuencia</th>
              <th class="px-4 py-3">Duración</th>
              <th class="px-4 py-3">Indicaciones</th>
            </tr>
          </thead>
          <tbody class="divide-y divide-slate-100">
            <tr v-for="item in (prescription.items || [])" :key="item.id">
              <td class="px-4 py-3 font-medium">{{ item.medicine_name }}</td>
              <td class="px-4 py-3">{{ item.dosage || '—' }}</td>
              <td class="px-4 py-3">{{ item.frequency || '—' }}</td>
              <td class="px-4 py-3">{{ item.duration || '—' }}</td>
              <td class="px-4 py-3">{{ item.instructions || '—' }}</td>
            </tr>
            <tr v-if="!(prescription.items || []).length">
              <td colspan="5" class="px-4 py-8 text-center text-slate-400">Sin ítems</td>
            </tr>
          </tbody>
        </table>
      </div>
    </template>
  </div>
</template>
