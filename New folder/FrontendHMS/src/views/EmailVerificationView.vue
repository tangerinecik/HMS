<template>
  <div class="container-fluid min-vh-100 d-flex align-items-center justify-content-center py-5">
    <div class="row w-100 justify-content-center">
      <div class="col-12 col-md-6 col-lg-4">
        <div class="card shadow">
          <div class="card-header bg-primary text-white text-center">
            <h2 class="mb-0">Email Verification</h2>
            <p class="mb-0 opacity-75">Verify your account</p>
          </div>
          <div class="card-body p-4 text-center">
            <!-- Loading state -->
            <div v-if="isVerifying" class="py-4">
              <div class="spinner-border text-primary mb-3" role="status" aria-hidden="true"></div>
              <p class="mb-0">Verifying your email...</p>
            </div>

            <!-- Success state -->
            <div v-else-if="verificationResult?.success" class="py-4">
              <div class="text-success mb-3">
                <i class="fas fa-check-circle fa-4x"></i>
              </div>
              <h4 class="text-success">Email Verified!</h4>
              <p class="mb-3">{{ verificationResult.message }}</p>
              <router-link to="/login" class="btn btn-primary">
                <i class="fas fa-sign-in-alt me-2"></i>
                Go to Login
              </router-link>
            </div>

            <!-- Error state -->
            <div v-else-if="verificationResult && !verificationResult.success" class="py-4">
              <div class="text-danger mb-3">
                <i class="fas fa-times-circle fa-4x"></i>
              </div>
              <h4 class="text-danger">Verification Failed</h4>
              <p class="mb-3">{{ verificationResult.message }}</p>
              
              <div class="d-grid gap-2">
                <button @click="resendVerification" class="btn btn-outline-primary" :disabled="isResending">
                  <span v-if="isResending" class="spinner-border spinner-border-sm me-2" role="status" aria-hidden="true"></span>
                  <i v-else class="fas fa-envelope me-2"></i>
                  {{ isResending ? 'Sending...' : 'Resend Verification Email' }}
                </button>
                <router-link to="/login" class="btn btn-secondary">
                  Back to Login
                </router-link>
              </div>

              <!-- Email input for resend -->
              <div v-if="showEmailInput" class="mt-3">
                <div class="input-group">
                  <input
                    v-model="email"
                    type="email"
                    class="form-control"
                    placeholder="Enter your email"
                    required
                  />
                  <button @click="sendVerificationEmail" class="btn btn-primary" :disabled="!email || isResending">
                    Send
                  </button>
                </div>
              </div>
            </div>            <!-- Invalid token state -->
            <div v-else class="py-4">
              <div class="text-warning mb-3">
                <i class="fas fa-exclamation-triangle fa-4x"></i>
              </div>
              <h4 class="text-warning">{{ email ? 'Email Verification Required' : 'Invalid Verification Link' }}</h4>
              <p class="mb-3">
                {{ email 
                  ? 'Please verify your email address to complete your account setup and login.' 
                  : 'The verification link is invalid or has expired.' 
                }}
              </p>
              
              <button @click="showEmailInput = true" class="btn btn-primary mb-2">
                <i class="fas fa-envelope me-2"></i>
                {{ email ? 'Send Verification Email' : 'Request New Verification Email' }}
              </button>
              
              <!-- Email input for resend -->
              <div v-if="showEmailInput" class="mt-3">
                <div class="input-group">
                  <input
                    v-model="email"
                    type="email"
                    class="form-control"
                    placeholder="Enter your email"
                    required
                    :readonly="!!$route.query.email"
                  />
                  <button @click="sendVerificationEmail" class="btn btn-primary" :disabled="!email || isResending">
                    <span v-if="isResending" class="spinner-border spinner-border-sm me-2" role="status" aria-hidden="true"></span>
                    Send
                  </button>
                </div>
                <small v-if="$route.query.email" class="text-muted">Email pre-filled from login attempt</small>
              </div>
            </div>

            <!-- Resend success message -->
            <div v-if="resendResult?.success" class="alert alert-success mt-3" role="alert">
              <i class="fas fa-check me-2"></i>
              {{ resendResult.message }}
            </div>

            <!-- Resend error message -->
            <div v-if="resendResult && !resendResult.success" class="alert alert-danger mt-3" role="alert">
              <i class="fas fa-exclamation-triangle me-2"></i>
              {{ resendResult.message }}
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
import { ref, onMounted } from 'vue'
import { useRoute } from 'vue-router'
import { useAuthStore } from '@/stores/auth'

const route = useRoute()
const authStore = useAuthStore()

const isVerifying = ref(false)
const isResending = ref(false)
const verificationResult = ref<{ success: boolean; message: string } | null>(null)
const resendResult = ref<{ success: boolean; message: string } | null>(null)
const showEmailInput = ref(false)
const email = ref('')

const verifyEmail = async (token: string) => {
  isVerifying.value = true
  verificationResult.value = await authStore.verifyEmail(token)
  isVerifying.value = false
}

const resendVerification = () => {
  showEmailInput.value = true
}

const sendVerificationEmail = async () => {
  if (!email.value) return
  
  isResending.value = true
  resendResult.value = await authStore.resendVerification(email.value)
  isResending.value = false
  
  if (resendResult.value.success) {
    showEmailInput.value = false
  }
}

onMounted(() => {
  const token = route.query.token as string
  const emailParam = route.query.email as string
  
  // Pre-fill email if provided in query params
  if (emailParam) {
    email.value = emailParam
  }
  
  if (token) {
    verifyEmail(token)
  } else if (!emailParam) {
    // No token and no email provided
    verificationResult.value = { success: false, message: 'No verification token provided' }
  } else {
    // Email provided but no token - show the resend interface
    showEmailInput.value = true
  }
})
</script>
