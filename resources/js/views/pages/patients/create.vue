<script setup>
/* ============================================================
   🔹 IMPORTACIONES PRINCIPALES
============================================================ */
import { ref } from "vue";
import { useRouter } from "vue-router";
import { usePatientStore } from "@/stores/patient";
import StatusMessage from '@/components/StatusMessage.vue'  // Ajusta la ruta
/* ============================================================
   ⚙️ CONFIGURACIÓN DEL COMPONENTE
============================================================ */
const router = useRouter();
const Store = usePatientStore();

/* ============================================================
   🧾 FORMULARIO REACTIVO (ESTRUCTURA BASE)
   Se definen todos los campos esperados por Laravel para
   prevenir errores de "undefined" y facilitar el manejo del form.
============================================================ */
const form = ref({
  identifier: "",
  first_name: "",
  last_name: "",
  date_of_birth: "",
  gender: "",
  phone: "",
  email: "",
  address: "",
});

/* ============================================================
   🚨 ESTADOS Y ERRORES LOCALES
============================================================ */
const errors = ref({});
const isLoading = ref(false);
const loading = ref(false);
const error = ref(false);
const routePrefix = "patients";

/* ============================================================
   💾 FUNCIÓN: CREAR REGISTRO
============================================================ */
const submitForm = async () => {
  errors.value = {};
  isLoading.value = true;

  try {
    const response = await Store.createPatient(form.value);

    if (response == 200 || response == 201) {
      router.push({ name: `${routePrefix}-index` });
    }
  } catch (err) {
    console.error("❌ Error creando registro:", err);
    if (err.response?.status === 422) {
      errors.value = err.response.data.errors || {};
    } else {
      error.value = true;
    }
  } finally {
    isLoading.value = false;
  }
};
</script>

<template>
  <StatusMessage
    :loading="loading"
    :error="error"
    title="Error al crear registro"
    text="No se pudo completar la creación del registro"
    icon="bx-user-x"
  >
    <!-- ============================================================
         🧭 CABECERA DEL MÓDULO
    ============================================================ -->
    <VCard class="mb-4 elevation-2 rounded-lg">
      <VCardTitle class="d-flex justify-space-between align-center px-6 py-4">
        <h2 class="text-h5 font-weight-bold text-primary">Crear Paciente</h2>
        <VBtn
          prepend-icon="bx-arrow-back"
          color="secondary"
          variant="tonal"
          @click="router.push({ name: `${routePrefix}-index` })"
        >
          Volver
        </VBtn>
      </VCardTitle>
    </VCard>

    <!-- ============================================================
         📝 FORMULARIO PRINCIPAL
    ============================================================ -->
    <VCard class="elevation-1 rounded-lg">
      <VCardText>
        <VForm @submit.prevent="submitForm">
          <VRow>
            <VCol cols="12">
              <VTextField
                v-model="form.identifier"
                label="Identifier"
                placeholder="CI, pasaporte, seguro"
                required
                :error-messages="errors.identifier"
              />
            </VCol>

            <VCol cols="12" md="6">
              <VTextField
                v-model="form.first_name"
                label="First Name"
                required
                :error-messages="errors.first_name"
              />
            </VCol>

            <VCol cols="12" md="6">
              <VTextField
                v-model="form.last_name"
                label="Last Name"
                required
                :error-messages="errors.last_name"
              />
            </VCol>

            <VCol cols="12" md="6">
              <VTextField
                v-model="form.date_of_birth"
                label="Date of Birth"
                type="date"
                :error-messages="errors.date_of_birth"
              />
            </VCol>

            <VCol cols="12" md="6">
              <VSelect
                v-model="form.gender"
                :items="['male', 'female', 'other', 'unknown']"
                label="Gender"
                :error-messages="errors.gender"
              />
            </VCol>

            <VCol cols="12" md="6">
              <VTextField
                v-model="form.phone"
                label="Phone"
                placeholder="+591 7 123 4567"
                :error-messages="errors.phone"
              />
            </VCol>

            <VCol cols="12" md="6">
              <VTextField
                v-model="form.email"
                label="Email"
                type="email"
                placeholder="example@mail.com"
                :error-messages="errors.email"
              />
            </VCol>

            <VCol cols="12">
              <VTextField
                v-model="form.address"
                label="Address"
                placeholder="Street, city, country"
                rows="2"
                :error-messages="errors.address"
              />
            </VCol>

            <VCol cols="12" class="d-flex gap-4">
              <VBtn type="submit" color="primary" :loading="isLoading">
                Crear
              </VBtn>
              <VBtn type="reset" color="secondary" variant="tonal">
                Reset
              </VBtn>
            </VCol>
          </VRow>
        </VForm>
      </VCardText>
    </VCard>
  </StatusMessage>
</template>