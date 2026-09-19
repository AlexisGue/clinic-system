import { defineStore } from 'pinia'
import { authApi } from '@/modules/auth/api'

function withTimeout(promise, ms) {
  return new Promise((resolve, reject) => {
    const timer = setTimeout(() => reject(new Error('timeout')), ms)
    promise.then(
      (value) => {
        clearTimeout(timer)
        resolve(value)
      },
      (err) => {
        clearTimeout(timer)
        reject(err)
      },
    )
  })
}

/** True when the browser likely has a Sanctum/Laravel session cookie. */
function hasSessionHint() {
  return document.cookie.split(';').some((part) => {
    const name = part.trim().split('=')[0]
    return name === 'XSRF-TOKEN' || name.endsWith('-session') || name.endsWith('_session')
  })
}

/**
 * Global auth state. The session credential itself lives in an
 * HttpOnly cookie managed by the browser: this store only keeps
 * the user profile + permission names, never anything secret.
 */
export const useAuthStore = defineStore('auth', {
  state: () => ({
    user: null,
    initialized: false,
  }),

  getters: {
    isAuthenticated: (state) => state.user !== null,
    permissions: (state) => state.user?.permissions ?? [],
    roles: (state) => state.user?.roles ?? [],
  },

  actions: {
    /**
     * Restores the session on app boot (page reload). Silent by
     * design: a 401 just means "not logged in".
     * Timeout avoids infinite blank screen when the API is cold (Render free).
     */
    async init(timeoutMs = 8000) {
      if (this.initialized) return

      // No cookie ⇒ definitely a guest. Skip /auth/me to avoid a noisy 401 in DevTools.
      if (!hasSessionHint()) {
        this.user = null
        this.initialized = true
        return
      }

      try {
        const { data } = await withTimeout(authApi.me(), timeoutMs)
        this.user = data.data
      } catch {
        this.user = null
      } finally {
        this.initialized = true
      }
    },

    async login(credentials) {
      // Cold starts on free hosting can take ~60s; keep trying.
      await withTimeout(authApi.csrf(), 90000)
      const { data } = await withTimeout(authApi.login(credentials), 90000)
      this.user = data.data
    },

    async logout() {
      try {
        await authApi.logout()
      } finally {
        this.user = null
      }
    },

    /** Called by the Axios interceptor when the session expires. */
    forget() {
      this.user = null
    },

    /**
     * UX-only check. Real enforcement always happens on the API
     * (Policies). Accepts a string or an array (ANY match).
     */
    can(permission) {
      if (!this.user) return false
      const list = Array.isArray(permission) ? permission : [permission]
      return list.some((p) => this.permissions.includes(p))
    },

    hasRole(role) {
      if (!this.user) return false
      const list = Array.isArray(role) ? role : [role]
      return list.some((r) => this.roles.includes(r))
    },
  },
})
