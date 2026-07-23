<script setup>
defineProps({
  label: { type: String, required: true },
  error: { type: [String, Array], default: null },
  htmlFor: { type: String, default: null },
})

const message = (error) => (Array.isArray(error) ? error[0] : error)
</script>

<template>
  <div>
    <label
      v-if="label"
      :for="htmlFor"
      class="mb-1.5 block text-sm font-medium text-slate-700"
    >
      {{ label }}
    </label>
    <slot />
    <p v-if="error" class="mt-1 text-xs text-rose-600">{{ message(error) }}</p>
  </div>
</template>
