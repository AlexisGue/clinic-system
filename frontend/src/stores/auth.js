import { defineStore } from 'pinia'
import { authApi } from '@/modules/auth/api'

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
     */
    async init() {
      if (this.initialized) return

      try {
        const { data } = await authApi.me()
        this.user = data.data
      } catch {
        this.user = null
      } finally {
        this.initialized = true
      }
    },

    async login(credentials) {
      await authApi.csrf()
      const { data } = await authApi.login(credentials)
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
