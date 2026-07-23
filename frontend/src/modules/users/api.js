import api from '@/api/axios'

export const usersApi = {
  list: (params) => api.get('/users', { params }),
  get: (id) => api.get(`/users/${id}`),
  create: (payload) => api.post('/users', payload),
  update: (id, payload) => api.put(`/users/${id}`, payload),
  remove: (id) => api.delete(`/users/${id}`),
}

export const rolesApi = {
  list: (params) => api.get('/roles', { params }),
  options: () => api.get('/roles/options'),
  permissions: () => api.get('/permissions'),
  get: (id) => api.get(`/roles/${id}`),
  create: (payload) => api.post('/roles', payload),
  update: (id, payload) => api.put(`/roles/${id}`, payload),
  remove: (id) => api.delete(`/roles/${id}`),
}
