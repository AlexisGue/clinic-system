import api from '@/api/axios'

export const settingsApi = {
  get: () => api.get('/settings'),
  update: (payload) => api.put('/settings', payload),
}
