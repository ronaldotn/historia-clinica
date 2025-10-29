<script setup>
import { ref, computed } from 'vue'
import { createPractitioner, checkIdentifierUnique } from '@/services/practitioners'
import { useRouter } from 'vue-router'

const router = useRouter()

// Modelo del formulario (coincide con backend)
const form = ref({
  first_name: '',
  last_name: '',
  identifier: '', // nro de colegiado
  specialty: '',
  email: '',
  phone: '',
  organization_id: null,
  active: true,
})

const loading = ref(false)
const v$ = ref({}) // errores simples

// Validación en tiempo real del colegiado (requiere soporte backend)
const identifierChecking = ref(false)
const identifierTaken = ref(false)
let identifierTmr
const onIdentifierInput = val => {
  clearTimeout(identifierTmr)
  if (!val) { identifierTaken.value = false; return }
  identifierTmr = setTimeout(async () => {
    identifierChecking.value = true // importante: UX mientras verifica
    try {
      identifierTaken.value = !(await checkIdentifierUnique(val)) // true si ya existe
    } catch (e) {
      identifierTaken.value = false // contingencia si backend no soporta filtro
    } finally {
      identifierChecking.value = false
    }
  }, 400) // importante: debounce para no saturar API
}

// Reglas mínimas del form
const canSubmit = computed(() => {
  return form.value.first_name && form.value.last_name && form.value.identifier && form.value.email && !identifierTaken.value
})

const submit = async () => {
  loading.value = true // importante: evita doble submit
  try {
    await createPractitioner(form.value)
    router.push({ name: 'professionals-index' }) // importante: navegar al listado tras crear
  } catch (e) {
    v$.value = e?.response?.data?.errors || {}
  } finally {
    loading.value = false
  }
}
</script>

<template>
  <VContainer>
    <VRow>
      <VCol cols="12" md="8" class="mx-auto">
        <VCard>
          <VCardTitle>Registrar Profesional</VCardTitle>
          <VCardText>
            <VForm @submit.prevent="submit">
              <VRow>
                <VCol cols="12" sm="6">
                  <VTextField v-model="form.first_name" label="Nombres" required :error-messages="v$.first_name" />
                </VCol>
                <VCol cols="12" sm="6">
                  <VTextField v-model="form.last_name" label="Apellidos" required :error-messages="v$.last_name" />
                </VCol>

                <VCol cols="12" sm="6">
                  <VTextField
                    v-model="form.identifier"
                    label="Nro de colegiado"
                    :loading="identifierChecking"
                    :error="identifierTaken || (v$.identifier && v$.identifier.length > 0)"
                    :error-messages="[...(v$.identifier || []), ...(identifierTaken ? ['Nro de colegiado ya registrado'] : [])]"
                    @update:model-value="onIdentifierInput"
                    required
                  />
                </VCol>

                <VCol cols="12" sm="6">
                  <VAutocomplete
                    v-model="form.specialty"
                    label="Especialidad"
                    :items="['Medicina General','Pediatría','Cardiología','Dermatología']"  
                    clearable
                    :error-messages="v$.specialty"
                  />
                </VCol>

                <VCol cols="12" sm="6">
                  <VTextField v-model="form.email" label="Email" type="email" required :error-messages="v$.email" />
                </VCol>
                <VCol cols="12" sm="6">
                  <VTextField v-model="form.phone" label="Teléfono" :error-messages="v$.phone" />
                </VCol>

                <VCol cols="12" sm="6">
                  <VTextField v-model.number="form.organization_id" label="Organización (ID)" type="number" :error-messages="v$.organization_id" />
                </VCol>
                <VCol cols="12" sm="6" class="d-flex align-center">
                  <VSwitch v-model="form.active" color="primary" inset label="Activo" />
                </VCol>

                <VCol cols="12" class="d-flex gap-3">
                  <VBtn type="submit" color="primary" :disabled="!canSubmit" :loading="loading">Guardar</VBtn>
                  <VBtn variant="tonal" @click="$router.back()">Cancelar</VBtn>
                </VCol>
              </VRow>
            </VForm>
          </VCardText>
        </VCard>
      </VCol>
    </VRow>
  </VContainer>
</template>

<style scoped>
</style>
