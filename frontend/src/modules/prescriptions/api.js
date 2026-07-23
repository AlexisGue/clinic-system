import api from '@/api/axios'

export const prescriptionsApi = {
  list: (params) => api.get('/prescriptions', { params }),
  get: (id) => api.get(`/prescriptions/${id}`),
  create: (payload) => api.post('/prescriptions', payload),
  update: (id, payload) => api.put(`/prescriptions/${id}`, payload),
  cancel: (id) => api.post(`/prescriptions/${id}/cancel`),
  pdfUrl: (id) => `${import.meta.env.VITE_API_URL}/api/v1/prescriptions/${id}/pdf`,
}
