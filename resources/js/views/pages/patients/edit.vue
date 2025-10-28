<script setup>
import { ref, onMounted } from "vue";
import { useRouter, useRoute } from "vue-router";
import { usePatientStore } from "@/stores/patient";

const router = useRouter();
const route = useRoute();
const Store = usePatientStore();

// ============================================================
// ⚙️ ESTADOS REACTIVOS
// ============================================================
const loading = ref(false);
const error = ref(false);
const isLoading = ref(false);
const errors = ref({});
// Prefijo base para rutas (coincide con la ruta del router)
const routePrefix = "patients";
// Formulario inicial vacío (estructura garantizada)
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

// ============================================================
// 🚀 CARGAR DATOS EXISTENTES
// ============================================================
onMounted(async () => {
  loading.value = true;
  error.value = false;

  try {
    const status = await Store.show(route.params.id);

    if (status === 200 && Store.current) {
      form.value = {
        identifier: Store.current.identifier ?? "",
        first_name: Store.current.first_name ?? "",
        last_name: Store.current.last_name ?? "",
        date_of_birth: Store.current.date_of_birth?.split("T")[0] ?? "",
        gender: Store.current.gender ?? "",
        phone: Store.current.phone ?? "",
        email: Store.current.email ?? "",
        address: Store.current.address ?? "",
      };
    } else {
      error.value = true;
    }
  } catch (err) {
    console.error("❌ Error cargando registro:", err);
    error.value = true;
  } finally {
    loading.value = false;
  }
});

// ============================================================
// 💾 GUARDAR CAMBIOS
// ============================================================
const submitForm = async () => {
  isLoading.value = true;
  errors.value = {};

  try {
    const id = route.params.id;
    const response = await Store.update(id, form.value);
    if (response === 200 || response === 201) {
     router.push({ name: `${routePrefix}-index` });
    }
  } catch (err) {
    console.error("❌ Error actualizando registro:", err);
    if (err.response?.status === 422) {
      errors.value = err.response.data.errors;
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
    title="Registro no encontrado"
    text="No se encontró información del registro"
    icon="bx-user-x"
  >
    <!-- 🧭 CABECERA -->
    <VCard class="mb-4 elevation-2 rounded-lg">
      <VCardTitle class="d-flex justify-space-between align-center px-6 py-4">
        <h2 class="text-h5 font-weight-bold text-primary">Editar Registro</h2>
        <VBtn
          prepend-icon="bx-arrow-back"
          color="secondary"
          variant="tonal"
          @click="router.push({ name: 'components-index' })"
        >
          Volver
        </VBtn>
      </VCardTitle>
    </VCard>

    <!-- 📝 FORMULARIO -->
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
              <VBtn type="submit" color="primary" :loading="isLoading">Guardar</VBtn>
              <VBtn type="reset" color="secondary" variant="tonal">Reset</VBtn>
            </VCol>
          </VRow>
        </VForm>
      </VCardText>
    </VCard>
  </StatusMessage>
</template>