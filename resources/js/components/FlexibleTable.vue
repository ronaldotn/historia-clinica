<script setup>
import { ref, computed, watch, onMounted } from "vue";

/**
 * =====================================
 * 📘 FlexibleTable - Versión Mejorada 2025
 * =====================================
 *
 * Tabla genérica y reusable con:
 *  - Encabezados dinámicos
 *  - Estado de carga
 *  - Manejo de datos vacíos
 *  - Paginación integrada
 *  - Compatible con cualquier store Pinia
 *
 * 🔹 Props
 * | Prop    | Tipo   | Default | Descripción |
 * |---------|--------|---------|-------------|
 * | store   | Object | -       | Store Pinia con datos, meta y métodos fetch |
 * | value   | String | "model"| Nombre del array de datos en el store |
 * | columns | Array  | -       | Encabezados de tabla |
 * | title   | String | "Sin datos" | Texto cuando no hay registros |
 * | text    | String | "No hay información" | Texto descriptivo |
 * | icon    | String | "bx-info-circle" | Icono cuando no hay datos |
 * | method  | String | "fetch" | Método del store para cargar datos |
 */

const props = defineProps({
  store: { type: Object, required: true },
  value: { type: String, default: "model" },
  columns: { type: Array, required: true },
  title: { type: String, default: "Sin datos" },
  text: { type: String, default: "No hay información disponible" },
  icon: { type: String, default: "bx-info-circle" },
  method: { type: String, default: "fetch" },
});

// ---------------------------
// 🔹 Estados internos
// ---------------------------
const currentPage = ref(1);

// Computed para total de páginas según meta del store
const totalPages = computed(() => props.store.meta?.last_page || 1);

// Computed para acceder al array de datos dinámicamente
const displayData = computed(() => props.store[props.value] ?? []);

// ---------------------------
// 🔹 Función principal de carga
// ---------------------------
const loadData = async (page = 1, extraParams = {}) => {
  const fetchMethod = props.store[props.method];

  if (typeof fetchMethod === "function") {
    try {
      // Llamada async con page y posibles filtros
      await fetchMethod({ page, ...extraParams });
      currentPage.value = page;
    } catch (err) {
      console.error(`❌ Error ejecutando "${props.method}":`, err);
    }
  } else {
    console.error(`❌ El store no tiene el método "${props.method}"`);
  }
};

// ---------------------------
// 🔹 Cambiar página
// ---------------------------
const changePage = (page) => loadData(page);

// ---------------------------
// 🔹 Observador de filtros
// ---------------------------
watch(() => props.store.filters, () => loadData(1), { deep: true });

// ---------------------------
// 🔹 Mounted: carga inicial
// ---------------------------
onMounted(() => loadData(currentPage.value));
</script>

<template>
  <VCard>
    <!-- ==========================
         🔹 Tabla principal
    =========================== -->
    <VTable height="500" fixed-header class="table-custom">
      <thead>
        <tr>
          <th v-for="(col, index) in columns" :key="index">
            {{ col }}
          </th>
        </tr>
      </thead>

      <tbody>
        <!-- Estado: Loading -->
        <tr v-if="store.loading">
          <td :colspan="columns.length" class="text-center py-6">
            <VProgressLinear indeterminate color="primary" />
          </td>
        </tr>

        <!-- Estado: Sin datos -->
        <tr v-else-if="displayData.length === 0">
          <td :colspan="columns.length" class="text-center py-6">
            <div class="items-center ">
              <VIcon :icon="icon" size="36" class="text-gray-400" />
              <h3 class="text-lg font-semibold">{{ title }}</h3>
              <p class="text-gray-500">{{ text }}</p>
            </div>
          </td>
        </tr>

        <!-- Datos dinámicos -->
        <slot v-else></slot>
      </tbody>
    </VTable>

    <!-- Separador antes de paginación -->
    <VDivider thickness="2" class="divider-footer" />

    <!-- Footer: Paginación -->
    <VCardActions class="justify-center footer-pagination">
      <VPagination
        v-if="store.meta?.total > store.meta?.per_page"
        v-model="currentPage"
        :length="totalPages"
        @update:model-value="changePage"
        rounded
      />
    </VCardActions>
  </VCard>
</template>
<!-- <FlexibleTable
  :store="conditionStore"
  method="fetch"        //   Puede ser cualquier método: fetch, fetchAll, fetchPatients 
  value="model"         //  Nombre del array en el store 
  :columns="['ID', 'Paciente', 'Descripción', 'Código', 'Fecha']"
  title="No hay condiciones registradas"
  text="Por favor agregue nuevas condiciones"
  icon="bx-file"
/>
  <template #default>
    <tr v-for="item in conditionStore.model" :key="item.id">
      <td>{{ item.id }}</td>
      <td>{{ item.patient?.last_name || '-' }}</td>
      <td>{{ item.description || '-' }}</td>
      <td>{{ item.code || '-' }}</td>
      <td>{{ item.recorded_date }}</td>
    </tr>
  </template>
</FlexibleTable> -->