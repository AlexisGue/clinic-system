import api from '@/api/axios'

export const doctorsApi = {
  list: (params) => api.get('/doctors', { params }),
  options: () => api.get('/doctors/options'),
  get: (id) => api.get(`/doctors/${id}`),
  create: (payload) => api.post('/doctors', payload),
  update: (id, payload) => api.put(`/doctors/${id}`, payload),
  remove: (id) => api.delete(`/doctors/${id}`),
  syncSpecialties: (id, specialty_ids) => api.put(`/doctors/${id}/specialties`, { specialty_ids }),
  getSchedules: (id) => api.get(`/doctors/${id}/schedules`),
  putSchedules: (id, schedules) => api.put(`/doctors/${id}/schedules`, { schedules }),
}
