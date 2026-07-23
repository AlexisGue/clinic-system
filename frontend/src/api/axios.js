import axios from 'axios'

/**
 * Single Axios instance for the whole app.
 *
 * withCredentials + withXSRFToken enable Sanctum SPA mode: the session
 * cookie (HttpOnly) and the X-XSRF-TOKEN header travel automatically,
 * so no token is ever stored in localStorage (XSS-safe).
 */
const api = axios.create({
  baseURL: `${import.meta.env.VITE_API_URL}/api/v1`,
  withCredentials: true,
  withXSRFToken: true,
  headers: {
    Accept: 'application/json',
  },
})

/**
 * Fetches the CSRF cookie required by Sanctum before any mutating
 * request (login, POST, PUT, DELETE...). Called once before login.
 */
export function csrfCookie() {
  return axios.get(`${import.meta.env.VITE_API_URL}/sanctum/csrf-cookie`, {
    withCredentials: true,
  })
}

// Centralized error handling: every module gets consistent behavior
// without repeating try/catch logic (DRY).
api.interceptors.response.use(
  (response) => response,
  (error) => {
    const status = error.response?.status

    if (status === 401) {
      // Session expired or not authenticated: reset local state.
      window.dispatchEvent(new CustomEvent('auth:unauthenticated'))
    }

    return Promise.reject(error)
  },
)

export default api
