<script setup>
defineProps({
  /** Optional helper text for screen readers when the group has a label. */
  label: { type: String, default: 'Acciones' },
})
</script>

<template>
  <div class="inline-flex flex-wrap items-center justify-end gap-1.5" role="group" :aria-label="label">
    <slot />
  </div>
</template>
