import api from '@/api/axios'

export const auditsApi = {
  list: (params) => api.get('/audits', { params }),
}
