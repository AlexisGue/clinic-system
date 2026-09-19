import axios from 'axios'

/**
 * Single Axios instance for the whole app.
 *
 * withCredentials + withXSRFToken enable Sanctum SPA mode: the session
 * cookie (HttpOnly) and the X-XSRF-TOKEN header travel automatically,
 * so no token is ever stored in localStorage (XSS-safe).
 *
 * In production (Vercel), leave VITE_API_URL empty so requests go to the
 * same origin and vercel.json proxies /api + /sanctum to Render. That
 * keeps CSRF cookies readable (same-site). Locally, point to Laravel.
 */
const apiOrigin = String(import.meta.env.VITE_API_URL || '').replace(/\/$/, '')

const api = axios.create({
  baseURL: `${apiOrigin}/api/v1`,
  withCredentials: true,
  withXSRFToken: true,
  timeout: 90000,
  headers: {
    Accept: 'application/json',
  },
})

/**
 * Fetches the CSRF cookie required by Sanctum before any mutating
 * request (login, POST, PUT, DELETE...). Called once before login.
 */
export function csrfCookie() {
  return axios.get(`${apiOrigin}/sanctum/csrf-cookie`, {
    withCredentials: true,
    timeout: 90000,
  })
}

// Centralized error handling: every module gets consistent behavior
// without repeating try/catch logic (DRY).
api.interceptors.response.use(
  (response) => response,
  (error) => {
    const status = error.response?.status
    // Session probe (/auth/me) uses silentAuth — a 401 there just means "guest".
    const silent = error.config?.silentAuth === true

    if (status === 401 && !silent) {
      // Session expired or not authenticated: reset local state.
      window.dispatchEvent(new CustomEvent('auth:unauthenticated'))
    }

    return Promise.reject(error)
  },
)

export default api
