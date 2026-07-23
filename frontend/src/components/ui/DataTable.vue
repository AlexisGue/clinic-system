<script setup>
defineProps({
  columns: { type: Array, required: true }, // [{ key, label, class? }]
  rows: { type: Array, default: () => [] },
  loading: { type: Boolean, default: false },
  meta: {
    type: Object,
    default: () => ({ current_page: 1, last_page: 1, total: 0 }),
  },
  emptyText: { type: String, default: 'Sin resultados' },
})

defineEmits(['page-change'])
</script>

<template>
  <div class="card-surface overflow-hidden">
    <div class="overflow-x-auto">
      <table class="min-w-full divide-y divide-slate-100 text-sm">
        <thead class="bg-slate-50/90 text-left text-[11px] font-semibold uppercase tracking-wider text-slate-500">
          <tr>
            <th
              v-for="col in columns"
              :key="col.key"
              class="px-4 py-3.5"
              :class="col.class"
            >
              {{ col.label }}
            </th>
          </tr>
        </thead>
        <tbody class="divide-y divide-slate-100">
          <tr v-if="loading">
            <td :colspan="columns.length" class="px-4 py-10 text-center text-slate-400">
              Cargando…
            </td>
          </tr>
          <tr v-else-if="!rows.length">
            <td :colspan="columns.length" class="px-4 py-10 text-center text-slate-400">
              {{ emptyText }}
            </td>
          </tr>
          <tr
            v-for="(row, index) in rows"
            :key="row.id ?? index"
            class="transition hover:bg-brand-50/40"
          >
            <td
              v-for="col in columns"
              :key="col.key"
              class="px-4 py-3.5"
              :class="col.class"
            >
              <slot :name="`cell-${col.key}`" :row="row">
                {{ row[col.key] }}
              </slot>
            </td>
          </tr>
        </tbody>
      </table>
    </div>

    <div
      v-if="meta.last_page > 1"
      class="flex items-center justify-between border-t border-slate-100 bg-slate-50/50 px-4 py-3 text-sm text-slate-500"
    >
      <span>{{ meta.total }} registros</span>
      <div class="flex items-center gap-2">
        <button
          :disabled="meta.current_page <= 1"
          class="btn-secondary !px-2.5 !py-1 text-xs disabled:opacity-40"
          @click="$emit('page-change', meta.current_page - 1)"
        >
          Anterior
        </button>
        <span class="text-xs">Pág. {{ meta.current_page }} / {{ meta.last_page }}</span>
        <button
          :disabled="meta.current_page >= meta.last_page"
          class="btn-secondary !px-2.5 !py-1 text-xs disabled:opacity-40"
          @click="$emit('page-change', meta.current_page + 1)"
        >
          Siguiente
        </button>
      </div>
    </div>
  </div>
</template>
