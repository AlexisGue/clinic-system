import api from '@/api/axios'

export const paymentsApi = {
  list: (params) => api.get('/payments', { params }),
  create: (payload) => api.post('/payments', payload),
}
