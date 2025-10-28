// stores/patient.js
import { ref } from "vue";
import { defineStore } from "pinia";
import Axios from "@/composables/Axios"; 
import { WarningToast, SuccessToast } from "@/composables/Toast";
import { useAppStore } from "@/stores/load"; // Carga global

export const usePatientStore = defineStore("patient", () => {
    // 🔹 Estado general
    const patients = ref([]); 
    const current = ref(null); 
    const loading = ref(false); 

    // 🔹 Información de paginación
    const meta = ref({}); 
    const links = ref([]); 

    // 🔹 Métricas de pacientes
    const metrics = ref({
        totalPatients: 0,
        patientsWithEncounters: 0,
        patientsWithConditions: 0,
        patientsWithObservations: 0,
    });

    const appStore = useAppStore(); // Carga global

    // ===============================
    // 🔹 Función – Obtener lista de pacientes
    // ===============================
    const fetchPatients = async (params = {}) => {
        loading.value = true;
        try {
            const { data } = await Axios.get("patients", { params });

            patients.value = data.result?.data || [];
            meta.value = {
                current_page: data.result?.current_page,
                last_page: data.result?.last_page,
                per_page: data.result?.per_page,
                total: data.result?.total
            };
            links.value = data.result?.links || [];
        } catch (err) {
            WarningToast(err.response?.data?.message || err.message);
        } finally {
            loading.value = false;
        }
    };

    // 🔹 Obtener métricas de pacientes
    const fetchMetrics = async () => {
        appStore.startLoading();
        try {
            const { data } = await Axios.get("metrics");
            metrics.value = data.result || metrics.value;
        } catch (err) {
            WarningToast(err.response?.data?.message || err.message);
        } finally {
            appStore.stopLoading();
        }
    };

    // 🔹 Obtener un paciente específico
    const showPatient = async (uuid) => {
        if (!uuid) return null;
        appStore.startLoading();
        try {
            const { data } = await Axios.get(`patients/${uuid}`);
            current.value = data.result || null;
            return current.value;
        } catch (err) {
            WarningToast(err.response?.data?.message || err.message);
            return null;
        } finally {
            appStore.stopLoading();
        }
    };

    // 🔹 Crear paciente
    const createPatient = async (payload) => {
        appStore.startLoading();
        try {
            const { data } = await Axios.post("patients", payload);
            SuccessToast(data.message || "Paciente creado correctamente");
            return data.code;
        } catch (err) {
            if (err.response?.status === 422) {
                WarningToast("Revisa los campos obligatorios");
                throw err;
            } else if (err.response?.status === 409) {
                WarningToast(err.response.data.message);
            } else {
                WarningToast(err.response?.data?.message || err.message);
            }
            return null;
        } finally {
            appStore.stopLoading();
        }
    };

    return {
        patients,
        current,
        loading,
        meta,
        links,
        metrics,
        fetchPatients,
        fetchMetrics,
        showPatient,
        createPatient,
    };
});