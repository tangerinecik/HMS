import { defineStore } from 'pinia'
import { authAPI, setAuthToken } from '@/services/api'
import router from '@/router'

export interface User {
  id: number
  email: string
  first_name: string
  last_name: string
  phone?: string
  role: 'customer' | 'employee' | 'admin'
  is_active: boolean
  created_at: string
}

export interface LoginCredentials {
  email: string
  password: string
}

export interface RegisterCredentials {
  email: string
  password: string
  first_name: string
  last_name: string
  phone?: string
  role?: 'customer' | 'employee' | 'admin'
}

export interface AuthResponse {
  token: string
  user: User
  message: string
}

export const useAuthStore = defineStore('auth', {
  state: () => ({
    user: null as User | null,
    token: localStorage.getItem('token') || null,
    isLoading: false,
    error: null as string | null,
  }),

  getters: {
    isAuthenticated: (state) => !!state.token && !!state.user,
    isAdmin: (state) => state.user?.role === 'admin',
    isEmployee: (state) => state.user?.role === 'employee' || state.user?.role === 'admin',
    fullName: (state) => state.user ? `${state.user.first_name} ${state.user.last_name}` : '',
  },

  actions: {
    // Update auth token in API headers
    updateAuthToken(token: string | null) {
      setAuthToken(token)
      
      if (token) {
        this.token = token
        localStorage.setItem('token', token)
      } else {
        this.token = null
        localStorage.removeItem('token')
      }
    },    async login(credentials: LoginCredentials) {
      this.isLoading = true
      this.error = null

      try {
        const response = await authAPI.login(credentials)
        const { token, user, message } = response.data

        this.updateAuthToken(token)
        this.user = user
        
        return { success: true, message }
      } catch (error: any) {
        const errorMessage = error.response?.data?.message || 'Login failed'
        this.error = errorMessage
        
        // Check if user needs email verification
        const isUnverifiedError = errorMessage.includes('verify your email')
        
        return { 
          success: false, 
          message: this.error,
          needsEmailVerification: isUnverifiedError,
          email: isUnverifiedError ? credentials.email : undefined
        }
      } finally {
        this.isLoading = false
      }
    },async register(credentials: RegisterCredentials) {
      this.isLoading = true
      this.error = null

      try {
        const response = await authAPI.register(credentials)
        const { message } = response.data

        // Registration successful but user needs to verify email
        // Don't set token/user yet - they need to verify first
        return { success: true, message }
      } catch (error: any) {
        this.error = error.response?.data?.error || 'Registration failed'
        return { success: false, message: this.error }
      } finally {
        this.isLoading = false
      }
    },    async logout() {
      try {
        await authAPI.logout()
      } catch (error) {
        // Ignore logout errors
      } finally {
        // Clear local state regardless
        this.user = null
        this.updateAuthToken(null)
        this.error = null
        
        // Redirect to home page after logout
        const currentRoute = router.currentRoute.value
        if (currentRoute.path !== '/') {
          router.push('/')
        }
      }
    },

    async fetchProfile() {
      if (!this.token) return

      this.isLoading = true
      try {
        const response = await authAPI.getProfile()
        this.user = response.data
      } catch (error: any) {
        this.error = error.response?.data?.message || 'Failed to fetch profile'
        // If profile fetch fails, likely token is invalid
        await this.logout()
      } finally {
        this.isLoading = false
      }
    },

    async refreshToken() {
      if (!this.token) return false

      try {
        const response = await authAPI.refreshToken()
        const { token, user } = response.data

        this.updateAuthToken(token)
        this.user = user

        return true
      } catch (error) {
        await this.logout()
        return false
      }
    },    // Initialize auth state on app start
    async initialize() {
      // Setup unauthorized event handler
      window.addEventListener('auth:unauthorized', () => this.logout())
      
      if (this.token) {
        // Ensure token is set in API headers before any requests
        setAuthToken(this.token)
        try {
          await this.fetchProfile()
        } catch (error) {
          console.error('Failed to fetch profile during initialization:', error)
          // If fetch profile fails, try to refresh the token once
          try {
            const refreshed = await this.refreshToken()
            if (!refreshed) {
              // If refresh fails, logout to clear invalid token
              await this.logout()
            }
          } catch (refreshError) {
            console.error('Token refresh failed:', refreshError)
            await this.logout()
          }
        }
      }
    },

    clearError() {
      this.error = null
    },

    async verifyEmail(token: string) {
      this.isLoading = true
      this.error = null

      try {
        const response = await authAPI.verifyEmail(token)
        const { message } = response.data

        return { success: true, message }
      } catch (error: any) {
        this.error = error.response?.data?.error || 'Email verification failed'
        return { success: false, message: this.error }
      } finally {
        this.isLoading = false
      }
    },

    async resendVerification(email: string) {
      this.isLoading = true
      this.error = null

      try {
        const response = await authAPI.resendVerification(email)
        const { message } = response.data

        return { success: true, message }
      } catch (error: any) {
        this.error = error.response?.data?.error || 'Failed to resend verification email'
        return { success: false, message: this.error }
      } finally {
        this.isLoading = false
      }
    },
  }
})
