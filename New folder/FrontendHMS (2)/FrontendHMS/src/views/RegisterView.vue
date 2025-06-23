<template>
  <div class="container-fluid min-vh-100 d-flex align-items-center justify-content-center py-5">
    <div class="row w-100 justify-content-center">
      <div class="col-12 col-md-8 col-lg-6">
        <div class="card shadow">
          <div class="card-header bg-primary text-white text-center">
            <h2 class="mb-0">Register for HMS</h2>
            <p class="mb-0 opacity-75">Hotel Management System</p>
          </div>
          <div class="card-body p-4">
            <form @submit.prevent="handleRegister">
              <div class="row">
                <div class="col-md-6 mb-3">
                  <label for="firstName" class="form-label">First Name</label>
                  <div class="input-group">
                    <span class="input-group-text">
                      <i class="fas fa-user"></i>
                    </span>
                    <input
                      id="firstName"
                      v-model="form.firstName"
                      type="text"
                      required
                      :disabled="authStore.isLoading"
                      class="form-control"
                      placeholder="Enter your first name"
                    />
                  </div>
                </div>
                
                <div class="col-md-6 mb-3">
                  <label for="lastName" class="form-label">Last Name</label>
                  <div class="input-group">
                    <span class="input-group-text">
                      <i class="fas fa-user"></i>
                    </span>
                    <input
                      id="lastName"
                      v-model="form.lastName"
                      type="text"
                      required
                      :disabled="authStore.isLoading"
                      class="form-control"
                      placeholder="Enter your last name"
                    />
                  </div>
                </div>
              </div>

              <div class="mb-3">
                <label for="email" class="form-label">Email Address</label>
                <div class="input-group">
                  <span class="input-group-text">
                    <i class="fas fa-envelope"></i>
                  </span>
                  <input
                    id="email"
                    v-model="form.email"
                    type="email"
                    required
                    :disabled="authStore.isLoading"
                    class="form-control"
                    placeholder="Enter your email"
                  />
                </div>
              </div>

              <div class="mb-3">
                <label for="phone" class="form-label">Phone Number <span class="text-muted">(optional)</span></label>
                <div class="input-group">
                  <span class="input-group-text">
                    <i class="fas fa-phone"></i>
                  </span>
                  <input
                    id="phone"
                    v-model="form.phone"
                    type="tel"
                    :disabled="authStore.isLoading"
                    class="form-control"
                    placeholder="Enter your phone number"
                  />
                </div>
              </div>

              <div class="row">
                <div class="col-md-6 mb-3">
                  <label for="password" class="form-label">Password</label>
                  <div class="input-group">
                    <span class="input-group-text">
                      <i class="fas fa-lock"></i>
                    </span>
                    <input
                      id="password"
                      v-model="form.password"
                      type="password"
                      required
                      minlength="8"
                      :disabled="authStore.isLoading"
                      class="form-control"
                      placeholder="Enter your password"
                    />
                  </div>
                  <small class="form-text text-muted">Password must be at least 8 characters long</small>
                </div>

                <div class="col-md-6 mb-3">
                  <label for="confirmPassword" class="form-label">Confirm Password</label>
                  <div class="input-group">
                    <span class="input-group-text">
                      <i class="fas fa-lock"></i>
                    </span>
                    <input
                      id="confirmPassword"
                      v-model="form.confirmPassword"
                      type="password"
                      required
                      :disabled="authStore.isLoading"
                      class="form-control"
                      :class="{ 'is-invalid': passwordMismatch }"
                      placeholder="Confirm your password"
                    />
                  </div>
                  <div v-if="passwordMismatch" class="invalid-feedback">
                    Passwords do not match
                  </div>
                </div>
              </div>

              <div v-if="authStore.error" class="alert alert-danger" role="alert">
                <i class="fas fa-exclamation-triangle me-2"></i>
                {{ authStore.error }}
              </div>

              <button 
                type="submit" 
                :disabled="authStore.isLoading || passwordMismatch || !isFormValid"
                class="btn btn-primary w-100 py-2"
              >
                <span v-if="authStore.isLoading" class="spinner-border spinner-border-sm me-2" role="status" aria-hidden="true"></span>
                <i v-else class="fas fa-user-plus me-2"></i>
                {{ authStore.isLoading ? 'Creating Account...' : 'Create Account' }}
              </button>
            </form>

            <hr class="my-4">
            
            <div class="text-center">
              <p class="mb-0">
                Already have an account? 
                <router-link to="/login" class="text-decoration-none">
                  <strong>Login here</strong>
                </router-link>
              </p>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
import { reactive, computed, onMounted } from 'vue'
import { useRouter } from 'vue-router'
import { useAuthStore } from '@/stores/auth'

const router = useRouter()
const authStore = useAuthStore()

const form = reactive({
  email: '',
  password: '',
  confirmPassword: '',
  firstName: '',
  lastName: '',
  phone: ''
})

const passwordMismatch = computed(() => {
  return form.confirmPassword.length > 0 && form.password !== form.confirmPassword
})

const isFormValid = computed(() => {
  return form.email && 
         form.password && 
         form.firstName && 
         form.lastName && 
         form.password.length >= 8 && 
         !passwordMismatch.value
})

const handleRegister = async () => {
  if (!isFormValid.value) return
  
  authStore.clearError()
  
  const result = await authStore.register({
    email: form.email,
    password: form.password,
    first_name: form.firstName,
    last_name: form.lastName,
    phone: form.phone || undefined,
    role: 'customer' // Default role for registration
  })

  if (result.success) {
    // Redirect to registration success page with email parameter
    router.push({
      path: '/registration-success',
      query: { email: form.email }
    })
  }
}

onMounted(() => {
  // Clear any previous errors
  authStore.clearError()
  
  // If already logged in, redirect to home
  if (authStore.isAuthenticated) {
    router.push('/')
  }
})
</script>

<style scoped>
/* No custom styles needed - using Bootstrap classes */
</style>
