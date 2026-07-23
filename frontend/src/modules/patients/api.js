import api from '@/api/axios'

export const patientsApi = {
  list: (params) => api.get('/patients', { params }),
  options: () => api.get('/patients/options'),
  get: (id) => api.get(`/patients/${id}`),
  create: (payload) => api.post('/patients', payload),
  update: (id, payload) => api.put(`/patients/${id}`, payload),
  remove: (id) => api.delete(`/patients/${id}`),
  history: (id) => api.get(`/patients/${id}/history`),
  contacts: (id) => api.get(`/patients/${id}/contacts`),
  storeContact: (id, payload) => api.post(`/patients/${id}/contacts`, payload),
  updateContact: (id, contactId, payload) => api.put(`/patients/${id}/contacts/${contactId}`, payload),
  destroyContact: (id, contactId) => api.delete(`/patients/${id}/contacts/${contactId}`),
}
