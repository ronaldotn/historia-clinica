<script setup>
import { ref, watch, onMounted } from 'vue'
import { listPractitioners } from '@/services/practitioners'

// Filtros y estado de tabla
const filters = ref({ name: '', specialty: '', active: undefined }) // importante: coincide con backend
const items = ref([]) // datos de tabla
const loading = ref(false) // loading tabla
const page = ref(1)
const count = ref(10)
const total = ref(0)

// Cargar datos con filtros y paginación
const load = async () => {
  loading.value = true // importante: UX loading
  try {
    const paginator = await listPractitioners({ ...filters.value, page: page.value, count: count.value }) // importante: servicio devuelve paginador
    items.value = paginator.data || [] // importante: items del paginador
    total.value = paginator.total || 0 // importante: total del paginador
  } catch (e) {
    // importante: evita errores no manejados en watcher/mounted
    items.value = []
    total.value = 0
  } finally {
    loading.value = false
  }
}

// Recargar cuando cambien filtros o paginación
// Debounce para no saturar el backend al tipear filtros
let tmr
watch([filters, page, count], () => {
  clearTimeout(tmr)
  tmr = setTimeout(() => load(), 400) // importante: debounce
}, { deep: true })

onMounted(() => load())
</script>

<template>
  <VContainer fluid>
    <VRow>
      <VCol cols="12">
        <VCard>
          <VCardTitle class="d-flex align-center justify-space-between">
            <span>Profesionales</span>
            <VBtn color="primary" :to="{ name: 'professionals-create' }">Registrar</VBtn> <!-- importante: navegación a create -->
          </VCardTitle>
          <VCardText>
            <VRow class="mb-4" align="end">
              <VCol cols="12" md="4">
                <VTextField v-model="filters.name" label="Nombre o Apellido" clearable />
              </VCol>
              <VCol cols="12" md="4">
                <VTextField v-model="filters.specialty" label="Especialidad" clearable />
              </VCol>
              <VCol cols="12" md="4">
                <VSelect
                  v-model="filters.active"
                  :items="[
                    { title: 'Todos', value: undefined },
                    { title: 'Activos', value: true },
                    { title: 'Inactivos', value: false },
                  ]"
                  label="Estado"
                />
              </VCol>
            </VRow>

            <VDataTable
              :items="items"
              :loading="loading"
              :items-per-page="count"
              v-model:page="page"  
              :headers="[
                { title: 'Nombres', value: 'first_name' },
                { title: 'Apellidos', value: 'last_name' },
                { title: 'Especialidad', value: 'specialty' },
                { title: 'Email', value: 'email' },
                { title: 'Estado', value: 'active' },
              ]"
              class="elevation-1"
            >
              <template #item.active="{ value }">
                <VChip :color="value ? 'success' : 'grey'" size="small">{{ value ? 'Activo' : 'Inactivo' }}</VChip>
              </template>

              <template #bottom>
                <div class="d-flex align-center justify-end pa-4 w-100 gap-4">
                  <VSelect
                    v-model="count"
                    :items="[5,10,15,25,50]"
                    label="Por página"
                    style="max-width: 140px"
                  />
                  <VPagination v-model="page" :length="Math.ceil((total||0) / (count||10))" /> <!-- importante: total de Laravel / items por página -->
                </div>
              </template>
            </VDataTable>
          </VCardText>
        </VCard>
      </VCol>
    </VRow>
  </VContainer>
</template>

<style scoped>
</style>
