<script setup>
import { ref, watch, onMounted } from "vue";
import { useRouter } from "vue-router";
import { usePatientStore } from "@/stores/patient";
import debounce from "lodash/debounce";
import FlexibleTable from '@/components/FlexibleTable.vue'  // Ajusta la ruta
import chart from '@images/cards/chart-success.png'
const patientStore = usePatientStore();
const router = useRouter();

/* Áreas / Especialidades */
const areas = ref(["Cardiología", "Pediatría", "Traumatología", "General"]);

/* Filtros rápidos */
const quickFilters = ref({ identifier: "", first_name: "", last_name: "" });
const isLoading = ref(false);

/* Función de fetch pacientes */
const fetchFilteredPatients = async () => {
    isLoading.value = true;
    try {
        await patientStore.fetchPatients({ ...quickFilters.value });
    } finally {
        isLoading.value = false;
    }
};

/* Función de fetch métricas */
const fetchMetrics = async () => {
    try {
        const data = await patientStore.fetchMetrics();
    } catch (err) {
        console.error("Error al obtener métricas:", err);
    }
};

/* Debounce: espera 2s desde la última escritura */
const debouncedFetch = debounce(fetchFilteredPatients, 2000);

/* Vigilar cambios en inputs y disparar debounce */
watch(quickFilters, () => {
    debouncedFetch();
}, { deep: true });

/* Navegación paciente */
const openPatientArea = (patientId) => {
    router.push({ name: "AreaView", params: { id: patientId } });
};

/* Al montar el componente */
onMounted(async () => {
    await fetchFilteredPatients();
    await fetchMetrics();
});
</script>

<template>

    <!-- HEADER -->
    <VRow align="center" justify="space-between" >
        <!-- Título y descripción -->
        <VCol cols="12" md="8">
            <div class="d-flex flex-column">
                <h1 class="text-h4 font-weight-bold mb-1">Panel de Control de Recepción</h1>
                <p class="text-body-2 text-medium-emphasis mb-0">
                    Bienvenida, gestiona tus tareas del día.
                </p>
            </div>
        </VCol>

        <!-- Botón Crear Paciente -->
        <VCol cols="12" md="4" class="d-flex justify-end">
            <VBtn color="primary"  elevation="2" class="ml-4" @click="router.push({ name: 'patients-create' })">
                Crear Paciente
            </VBtn>
        </VCol>
    </VRow>


    <!-- MÉTRICAS DINÁMICAS -->
    <VRow class="mb-6" dense>
        <VCol cols="12" sm="6" md="3">
            <CardStatisticsVertical v-bind="{
                title: 'Pacientes Totales',
                image: chart,
                stats: String(patientStore.metrics.totalPatients),
                change: 72.80,
            }" />
        </VCol>
        <VCol cols="12" sm="6" md="3">
            <CardStatisticsVertical v-bind="{
                title: 'Pacientes con Encuentros',
                image: chart,
                stats: String(patientStore.metrics.patientsWithEncounters),
                change: 72.80,
            }" />
        </VCol>
        <VCol cols="12" sm="6" md="3">
            <CardStatisticsVertical v-bind="{
                title: 'Pacientes con Condiciones',
                image: chart,
                stats: String(patientStore.metrics.patientsWithConditions),
                change: 72.80,
            }" />
        </VCol>
        <VCol cols="12" sm="6" md="3">
            <CardStatisticsVertical v-bind="{
                title: 'Pacientes con Observaciones',
                image: chart,
                stats: String(patientStore.metrics.patientsWithObservations),
                change: 72.80,
            }" />
        </VCol>
    </VRow>

    <!-- FILTROS RÁPIDOS -->
    <VCard class="mb-4 pa-3 d-flex flex-wrap gap-4 justify-space-between align-center elevation-2">
        <VTextField v-model="quickFilters.identifier" label="Documento" placeholder="12345678"
            prepend-inner-icon="bx-id-card" density="comfortable" clearable style="min-width:180px; max-width:250px" />
        <VTextField v-model="quickFilters.first_name" label="Nombre" placeholder="John" prepend-inner-icon="bx-user"
            density="comfortable" clearable style="min-width:180px; max-width:250px" />
        <VTextField v-model="quickFilters.last_name" label="Apellidos" placeholder="Doe" prepend-inner-icon="bx-user"
            density="comfortable" clearable style="min-width:180px; max-width:250px" />
    </VCard>

    <!-- TABLA DE PACIENTES -->
    <VRow class="mb-8">
        <VCol cols="12">
            <FlexibleTable :store="patientStore" value="patients" method="fetchPatients"
                :columns="['Nombre', 'DNI', 'Sexo', 'Contacto', 'Acciones']" title="Sin pacientes"
                text="No hay pacientes registrados" icon="bx-user-x">
                <tr v-for="p in patientStore.patients" :key="p.id">
                    <td>{{ p.first_name }} {{ p.last_name }}</td>
                    <td>{{ p.identifier }}</td>
                    <td>{{ p.gender ?? '-' }}</td>
                    <td>{{ p.phone ?? '-' }}</td>
                    <td class="d-flex gap-2 align-center">
                        <VSelect :items="areas" label="Especialidad" dense hide-details style="width: 150px"
                            clearable />
                        <VBtn color="primary" variant="tonal" height="38" @click="openPatientArea(p.id)">Abrir
                        </VBtn>
                    </td>
                </tr>
            </FlexibleTable>
        </VCol>
    </VRow>
</template>