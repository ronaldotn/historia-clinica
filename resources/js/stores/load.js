import { ref, computed } from "vue";
import { defineStore } from "pinia";

/**
 * 🔹 useAppStore: Gestiona el contador de peticiones activas.
 */
export const useAppStore = defineStore("app", () => {
    // Contador de cuántas peticiones HTTP están en curso.
    const loadingCount = ref(0); 

    // True si el contador es > 0.
    const isLoading = computed(() => loadingCount.value > 0);

    const startLoading = () => {
        loadingCount.value++;
    };

    const stopLoading = () => {
        if (loadingCount.value > 0) {
            loadingCount.value--;
        }
    };

    return { isLoading, startLoading, stopLoading };
});