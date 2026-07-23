import api, { csrfCookie } from '@/api/axios'

/**
 * Auth HTTP surface. Components / the store call these —
 * never Axios directly (keeps URLs and payloads in one place).
 */
export const authApi = {
  csrf: () => csrfCookie(),
  branding: () => api.get('/auth/branding'),
  login: (credentials) => api.post('/auth/login', credentials),
  logout: () => api.post('/auth/logout'),
  me: () => api.get('/auth/me'),
}
