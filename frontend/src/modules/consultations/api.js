import api from '@/api/axios'

export const consultationsApi = {
  list: (params) => api.get('/consultations', { params }),
  get: (id) => api.get(`/consultations/${id}`),
  create: (payload) => api.post('/consultations', payload),
  update: (id, payload) => api.put(`/consultations/${id}`, payload),
  finalize: (id) => api.post(`/consultations/${id}/finalize`),
  vitals: (id, payload) => api.post(`/consultations/${id}/vitals`, payload),
}
