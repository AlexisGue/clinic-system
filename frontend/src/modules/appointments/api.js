import api from '@/api/axios'

export const appointmentsApi = {
  list: (params) => api.get('/appointments', { params }),
  calendar: (params) => api.get('/appointments/calendar', { params }),
  get: (id) => api.get(`/appointments/${id}`),
  create: (payload) => api.post('/appointments', payload),
  reschedule: (id, payload) => api.post(`/appointments/${id}/reschedule`, payload),
  cancel: (id, payload) => api.post(`/appointments/${id}/cancel`, payload),
  status: (id, payload) => api.post(`/appointments/${id}/status`, payload),
}
