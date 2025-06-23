<template>
  <div class="container-fluid min-vh-100 d-flex align-items-center justify-content-center py-5">
    <div class="row w-100 justify-content-center">
      <div class="col-12 col-md-6 col-lg-5">
        <div class="card shadow">
          <div class="card-header bg-success text-white text-center">
            <h2 class="mb-0">Registration Successful!</h2>
          </div>
          <div class="card-body p-4 text-center">
            <div class="text-success mb-4">
              <i class="fas fa-envelope-circle-check fa-5x"></i>
            </div>
            
            <h4 class="mb-3">Check Your Email</h4>
            <p class="mb-4">
              We've sent a verification email to <strong>{{ email }}</strong>. 
              Please click the verification link in the email to activate your account.
            </p>

            <div class="alert alert-info" role="alert">
              <i class="fas fa-info-circle me-2"></i>
              <strong>Important:</strong> You must verify your email before you can log in.
            </div>

            <div class="d-grid gap-2 mt-4">
              <button 
                @click="resendVerification" 
                class="btn btn-outline-primary"
                :disabled="isResending || resendCooldown > 0"
              >
                <span v-if="isResending" class="spinner-border spinner-border-sm me-2" role="status" aria-hidden="true"></span>
                <i v-else class="fas fa-paper-plane me-2"></i>
                {{ isResending ? 'Sending...' : resendCooldown > 0 ? `Resend in ${resendCooldown}s` : 'Resend Verification Email' }}
              </button>
              
              <router-link to="/login" class="btn btn-secondary">
                <i class="fas fa-arrow-left me-2"></i>
                Back to Login
              </router-link>
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
import { ref, onMounted, onUnmounted } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import { useAuthStore } from '@/stores/auth'

const route = useRoute()
const router = useRouter()
const authStore = useAuthStore()

const email = ref('')
const isResending = ref(false)
const resendCooldown = ref(0)
const resendResult = ref<{ success: boolean; message: string } | null>(null)

let cooldownInterval: number | null = null

const resendVerification = async () => {
  if (!email.value || isResending.value || resendCooldown.value > 0) return
  
  isResending.value = true
  resendResult.value = await authStore.resendVerification(email.value)
  isResending.value = false
  
  if (resendResult.value.success) {
    // Start cooldown
    resendCooldown.value = 60 // 60 seconds cooldown
    cooldownInterval = setInterval(() => {
      resendCooldown.value--
      if (resendCooldown.value <= 0) {
        clearInterval(cooldownInterval!)
        cooldownInterval = null
      }
    }, 1000)
  }
}

onMounted(() => {
  // Get email from route query parameter
  email.value = route.query.email as string || ''
  
  if (!email.value) {
    // If no email provided, redirect to registration
    router.push('/register')
  }
})

onUnmounted(() => {
  if (cooldownInterval) {
    clearInterval(cooldownInterval)
  }
})
</script>
