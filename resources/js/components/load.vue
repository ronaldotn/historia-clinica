<script setup>
import { useAppStore } from "@/stores/load"; // Importamos el store global

// Inicializamos el store
const appStore = useAppStore();
</script>

<template>
  <VProgressLinear
    v-if="appStore.isLoading"
    indeterminate
    color="primary"
    height="3" 
    absolute 
    bottom 
  />
  </template>