import Axios from '@/composables/Axios'

// Lista de profesionales con filtros y paginación
export const listPractitioners = async (params = {}) => {
  // Normalizamos parámetros reconocidos por el backend
  const { name = '', specialty = '', active = undefined, page = 1, count = 10 } = params // importantes: backend espera name, specialty, active, count
  const res = await Axios.get('practitioners', {
    params: {
      name: name || undefined, // evita enviar vacío
      specialty: specialty || undefined,
      active: typeof active === 'boolean' ? active : undefined,
      page,
      count,
    },
  })
  return res.data.result // importante: API usa envoltorio sendResponse
}

// Obtener un profesional por id
export const getPractitioner = async id => {
  const res = await Axios.get(`practitioners/${id}`)
  return res.data.result
}

// Crear un nuevo profesional
export const createPractitioner = async payload => {
  // Campos esperados: first_name, last_name, specialty, identifier, email, phone, organization_id, active
  const res = await Axios.post('practitioners', payload)
  return res.data.result
}

// (Opcional) Verificación de unicidad en tiempo real
export const checkIdentifierUnique = async identifier => {
  // Nota: requiere soporte backend (ej: GET /api/practitioners?identifier=XYZ)
  const res = await Axios.get('practitioners', { params: { identifier, count: 1 } }) // importante: backend debe soportar este filtro
  const page = res.data.result
  return (page?.data?.length ?? 0) === 0
}
