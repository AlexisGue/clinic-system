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
  // silentAuth: expected 401 when guest — don't treat as session expiry.
  me: () => api.get('/auth/me', { silentAuth: true }),
}
