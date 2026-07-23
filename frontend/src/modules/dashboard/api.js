import api from '@/api/axios'

export const dashboardApi = {
  summary: (params) => api.get('/dashboard/summary', { params }),
}
