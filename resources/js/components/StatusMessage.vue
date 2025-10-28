<script setup>
const props = defineProps({
  loading: { type: Boolean, default: false },
  error: { type: Boolean, default: false },
  title: { type: String, default: "Sin datos" },
  text: { type: String, default: "No hay información disponible" },
  icon: { type: String, default: "bx-search-alt-2" },
});
</script>

<template>

    <!-- Loader -->
    <VProgressLinear
      v-if="loading"
      indeterminate
      color="primary"
      
    />

    <!-- Mensaje de error -->
    <div v-else-if="error" class="w-full text-center py-6">
      <VIcon v-if="icon" :icon="icon" size="36" class="text-red-500 mb-2" />
      <h3 class="font-semibold text-lg mb-1">{{ title }}</h3>
      <p class="text-gray-500">{{ text }}</p>
    </div>

    <!-- Contenido -->
    <div v-else>
      <slot></slot>
    </div>

</template>