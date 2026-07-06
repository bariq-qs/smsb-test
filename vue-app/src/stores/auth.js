import { defineStore } from 'pinia'
import api from '@/lib/api'

export const useAuthStore = defineStore('auth', {
  state: () => ({
    user: null,
    initialized: false,
  }),

  getters: {
    isAuthenticated: (state) => !!state.user,
    roles: (state) => state.user?.roles ?? [],
    permissions: (state) => state.user?.permissions ?? [],
  },

  actions: {
    hasRole(role) {
      return this.roles.includes(role)
    },

    hasPermission(permission) {
      return this.permissions.includes(permission)
    },

    async fetchUser() {
      try {
        const { data } = await api.get('/api/user')
        this.user = data.user
      } catch {
        this.user = null
      } finally {
        this.initialized = true
      }
    },

    async ensureInitialized() {
      if (!this.initialized) {
        await this.fetchUser()
      }
    },

    async login(email, password) {
      await api.get('/sanctum/csrf-cookie')
      await api.post('/api/login', { email, password })
      await this.fetchUser()
    },

    async logout() {
      await api.post('/api/logout')
      this.user = null
    },
  },
})
