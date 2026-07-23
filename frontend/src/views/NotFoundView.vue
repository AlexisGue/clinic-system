<script setup>
</script>

<template>
  <div class="flex min-h-screen flex-col items-center justify-center gap-3 p-6">
    <p class="text-6xl font-bold text-slate-300">404</p>
    <p class="text-slate-500">La página que buscas no existe.</p>
    <RouterLink
      to="/"
      class="mt-2 rounded-lg bg-brand-600 px-4 py-2 text-sm font-medium text-white transition hover:bg-brand-700"
    >
      Volver al inicio
    </RouterLink>
  </div>
</template>
