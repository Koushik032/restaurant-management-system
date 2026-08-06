import { defineStore } from 'pinia'
import api from '@/services/api'

const TOKEN_KEY = 'rms_access_token'
const USER_KEY = 'rms_user'

function getStoredUser() {
  const storedUser = localStorage.getItem(USER_KEY)

  if (!storedUser) {
    return null
  }

  try {
    return JSON.parse(storedUser)
  } catch {
    localStorage.removeItem(USER_KEY)
    return null
  }
}

export const useAuthStore = defineStore('auth', {
  state: () => ({
    token: localStorage.getItem(TOKEN_KEY),
    user: getStoredUser(),
    loading: false,
    initialized: false,
    error: null,
  }),

  getters: {
    isAuthenticated: (state) => Boolean(state.token && state.user),

    roleName: (state) => state.user?.role?.name ?? null,

    permissions: (state) => state.user?.permissions ?? [],

    isAdmin() {
      return this.roleName === 'admin'
    },

    isManager() {
      return this.roleName === 'manager'
    },

    isChef() {
      return this.roleName === 'chef'
    },
  },

  actions: {
    hasPermission(permission) {
      return this.permissions.includes(permission)
    },

    hasAnyPermission(requiredPermissions = []) {
      if (!requiredPermissions.length) {
        return true
      }

      return requiredPermissions.some((permission) =>
        this.hasPermission(permission),
      )
    },

    storeSession(token, user) {
      this.token = token
      this.user = user

      localStorage.setItem(TOKEN_KEY, token)
      localStorage.setItem(USER_KEY, JSON.stringify(user))
    },

    clearSession() {
      this.token = null
      this.user = null

      localStorage.removeItem(TOKEN_KEY)
      localStorage.removeItem(USER_KEY)
    },

    async login(credentials) {
      this.loading = true
      this.error = null

      try {
        const response = await api.post('/auth/login', {
          login: credentials.login,
          password: credentials.password,
          device_name: credentials.deviceName || 'RMS Vue Client',
        })

        const { access_token: accessToken, user } = response.data.data

        this.storeSession(accessToken, user)

        return {
          success: true,
          user,
        }
      } catch (error) {
        this.clearSession()

        this.error =
          error.response?.data?.message ||
          'Could not connect to the server. Make sure the Laravel API is running.'

        return {
          success: false,
          message: this.error,
          errors: error.response?.data?.errors ?? null,
        }
      } finally {
        this.loading = false
      }
    },

    async fetchUser() {
      if (!this.token) {
        this.initialized = true
        return false
      }

      try {
        const response = await api.get('/auth/me')
        const user = response.data.data.user

        this.user = user
        localStorage.setItem(USER_KEY, JSON.stringify(user))

        return true
      } catch {
        this.clearSession()
        return false
      } finally {
        this.initialized = true
      }
    },

    async logout() {
      this.loading = true

      try {
        if (this.token) {
          await api.post('/auth/logout')
        }
      } catch (error) {
        console.error('Logout request failed:', error)
      } finally {
        this.clearSession()
        this.loading = false
      }
    },

    async initializeAuth() {
      if (this.initialized) {
        return
      }

      if (!this.token) {
        this.clearSession()
        this.initialized = true
        return
      }

      await this.fetchUser()
    },

    getDefaultRoute() {
        return '/dashboard'
    },
  },
})