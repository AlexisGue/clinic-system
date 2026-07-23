<script setup>
defineProps({
  open: { type: Boolean, default: false },
  title: { type: String, default: '' },
})

defineEmits(['close'])
</script>

<template>
  <Teleport to="body">
    <div
      v-if="open"
      class="fixed inset-0 z-40 flex items-center justify-center p-4"
    >
      <div class="absolute inset-0 bg-ink-950/50 backdrop-blur-sm animate-fade-in" @click="$emit('close')" />
      <div class="relative z-10 w-full max-w-lg animate-fade-in rounded-2xl border border-slate-200/80 bg-white p-6 shadow-xl shadow-slate-900/10">
        <div class="mb-5 flex items-center justify-between gap-3">
          <h2 class="font-display text-lg font-semibold text-slate-900">{{ title }}</h2>
          <button
            type="button"
            class="rounded-xl p-1.5 text-slate-400 transition hover:bg-slate-100 hover:text-slate-600"
            aria-label="Cerrar"
            @click="$emit('close')"
          >
            <svg class="h-5 w-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" />
            </svg>
          </button>
        </div>
        <slot />
      </div>
    </div>
  </Teleport>
</template>
