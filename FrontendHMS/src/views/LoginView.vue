<template>
  <div class="container-fluid min-vh-100 d-flex align-items-center justify-content-center py-5">
    <div class="row w-100 justify-content-center">
      <div class="col-12 col-md-6 col-lg-4">
        <div class="card shadow">
          <div class="card-header bg-primary text-white text-center">
            <h2 class="mb-0">Login to HMS</h2>
            <p class="mb-0 opacity-75">Hotel Management System</p>
          </div>
          <div class="card-body p-4">
            <form @submit.prevent="handleLogin">
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
                    :disabled="authStore.isLoading"
                    class="form-control"
                    placeholder="Enter your password"
                  />
                </div>
              </div>              <div v-if="authStore.error" class="alert alert-danger" role="alert">
                <i class="fas fa-exclamation-triangle me-2"></i>
                {{ authStore.error }}
                
                <!-- Show resend verification option if email is not verified -->
                <div v-if="showResendVerification" class="mt-2">
                  <button 
                    @click="redirectToVerification" 
                    class="btn btn-outline-primary btn-sm"
                  >
                    <i class="fas fa-envelope me-1"></i>
                    Go to Email Verification
                  </button>
                </div>
              </div>

              <button 
                type="submit" 
                :disabled="authStore.isLoading"
                class="btn btn-primary w-100 py-2"
              >
                <span v-if="authStore.isLoading" class="spinner-border spinner-border-sm me-2" role="status" aria-hidden="true"></span>
                <i v-else class="fas fa-sign-in-alt me-2"></i>
                {{ authStore.isLoading ? 'Logging in...' : 'Login' }}
              </button>
            </form>

            <hr class="my-4">
            
            <div class="text-center">
              <p class="mb-0">
                Don't have an account? 
                <router-link to="/register" class="text-decoration-none">
                  <strong>Register here</strong>
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
import { reactive, ref, onMounted } from 'vue'
import { useRouter } from 'vue-router'
import { useAuthStore } from '@/stores/auth'

const router = useRouter()
const authStore = useAuthStore()

const form = reactive({
  email: '',
  password: ''
})

const showResendVerification = ref(false)
const userEmail = ref('')

const handleLogin = async () => {
  authStore.clearError()
  showResendVerification.value = false
  
  const result = await authStore.login({
    email: form.email,
    password: form.password
  })

  if (result.success) {
    router.push('/')
  } else if (result.needsEmailVerification) {
    // Show option to go to email verification
    showResendVerification.value = true
    userEmail.value = result.email || form.email
  }
}

const redirectToVerification = () => {
  // Go to email verification view and pass the email as a query parameter
  router.push({
    name: 'verify-email',
    query: { email: userEmail.value }
  })
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


