<template>
  <div class="profile-container">
    <div class="container">
      <h1>My Profile</h1>
      
      <div class="profile-content">
        <div class="profile-card">
          <div class="profile-header">
            <div class="avatar">
              {{ userInitials }}
            </div>
            <div class="user-info">
              <h2>{{ authStore.fullName }}</h2>
              <p class="user-role">{{ authStore.user?.role }}</p>
              <p class="user-email">{{ authStore.user?.email }}</p>
            </div>
          </div>
          
          <div class="profile-form">
            <h3>Update Profile Information</h3>
            
            <form @submit.prevent="updateProfile" class="form">
              <div class="form-row">
                <div class="form-group">
                  <label for="firstName">First Name:</label>
                  <input
                    id="firstName"
                    v-model="form.firstName"
                    type="text"
                    required
                    :disabled="isLoading"
                    class="form-input"
                  />
                </div>
                
                <div class="form-group">
                  <label for="lastName">Last Name:</label>
                  <input
                    id="lastName"
                    v-model="form.lastName"
                    type="text"
                    required
                    :disabled="isLoading"
                    class="form-input"
                  />
                </div>
              </div>

              <div class="form-group">
                <label for="phone">Phone:</label>
                <input
                  id="phone"
                  v-model="form.phone"
                  type="tel"
                  :disabled="isLoading"
                  class="form-input"
                />
              </div>

              <div v-if="updateError" class="error-message">
                {{ updateError }}
              </div>

              <div v-if="updateSuccess" class="success-message">
                Profile updated successfully!
              </div>

              <button 
                type="submit" 
                :disabled="isLoading || !hasChanges"
                class="btn btn-primary"
              >
                {{ isLoading ? 'Updating...' : 'Update Profile' }}
              </button>
            </form>
          </div>
        </div>
        
        <div class="password-card">
          <h3>Change Password</h3>
          
          <form @submit.prevent="changePassword" class="form">
            <div class="form-group">
              <label for="oldPassword">Current Password:</label>
              <input
                id="oldPassword"
                v-model="passwordForm.oldPassword"
                type="password"
                required
                :disabled="isPasswordLoading"
                class="form-input"
              />
            </div>

            <div class="form-group">
              <label for="newPassword">New Password:</label>
              <input
                id="newPassword"
                v-model="passwordForm.newPassword"
                type="password"
                required
                minlength="8"
                :disabled="isPasswordLoading"
                class="form-input"
              />
              <small class="form-hint">Password must be at least 8 characters long</small>
            </div>

            <div class="form-group">
              <label for="confirmNewPassword">Confirm New Password:</label>
              <input
                id="confirmNewPassword"
                v-model="passwordForm.confirmNewPassword"
                type="password"
                required
                :disabled="isPasswordLoading"
                class="form-input"
                :class="{ 'error': passwordMismatch }"
              />
              <small v-if="passwordMismatch" class="form-error">Passwords do not match</small>
            </div>

            <div v-if="passwordError" class="error-message">
              {{ passwordError }}
            </div>

            <div v-if="passwordSuccess" class="success-message">
              Password changed successfully!
            </div>

            <button 
              type="submit" 
              :disabled="isPasswordLoading || passwordMismatch || !isPasswordFormValid"
              class="btn btn-primary"
            >
              {{ isPasswordLoading ? 'Changing...' : 'Change Password' }}
            </button>
          </form>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
import { reactive, computed, ref, onMounted } from 'vue'
import { useAuthStore } from '@/stores/auth'
import api from '@/services/api'

const authStore = useAuthStore()

const isLoading = ref(false)
const isPasswordLoading = ref(false)
const updateError = ref('')
const updateSuccess = ref(false)
const passwordError = ref('')
const passwordSuccess = ref(false)

const form = reactive({
  firstName: '',
  lastName: '',
  phone: ''
})

const passwordForm = reactive({
  oldPassword: '',
  newPassword: '',
  confirmNewPassword: ''
})

const userInitials = computed(() => {
  if (!authStore.user) return '?'
  return `${authStore.user.first_name[0]}${authStore.user.last_name[0]}`.toUpperCase()
})

const hasChanges = computed(() => {
  if (!authStore.user) return false
  return form.firstName !== authStore.user.first_name ||
         form.lastName !== authStore.user.last_name ||
         form.phone !== (authStore.user.phone || '')
})

const passwordMismatch = computed(() => {
  return passwordForm.confirmNewPassword.length > 0 && 
         passwordForm.newPassword !== passwordForm.confirmNewPassword
})

const isPasswordFormValid = computed(() => {
  return passwordForm.oldPassword &&
         passwordForm.newPassword &&
         passwordForm.newPassword.length >= 8 &&
         !passwordMismatch.value
})

const initializeForm = () => {
  if (authStore.user) {
    form.firstName = authStore.user.first_name
    form.lastName = authStore.user.last_name
    form.phone = authStore.user.phone || ''
  }
}

const updateProfile = async () => {
  if (!authStore.user || !hasChanges.value) return
  
  isLoading.value = true
  updateError.value = ''
  updateSuccess.value = false
  
  try {
    const response = await api.put(`/users/${authStore.user.id}`, {
      first_name: form.firstName,
      last_name: form.lastName,
      phone: form.phone || null
    })
    
    // Update the user in the store
    if (response.data.user) {
      authStore.user = response.data.user
    }
    
    updateSuccess.value = true
    setTimeout(() => {
      updateSuccess.value = false
    }, 3000)
    
  } catch (error: any) {
    updateError.value = error.response?.data?.message || 'Failed to update profile'
  } finally {
    isLoading.value = false
  }
}

const changePassword = async () => {
  if (!authStore.user || !isPasswordFormValid.value) return
  
  isPasswordLoading.value = true
  passwordError.value = ''
  passwordSuccess.value = false
  
  try {
    await api.put(`/users/${authStore.user.id}/password`, {
      old_password: passwordForm.oldPassword,
      new_password: passwordForm.newPassword
    })
    
    passwordSuccess.value = true
    
    // Clear form
    passwordForm.oldPassword = ''
    passwordForm.newPassword = ''
    passwordForm.confirmNewPassword = ''
    
    setTimeout(() => {
      passwordSuccess.value = false
    }, 3000)
    
  } catch (error: any) {
    passwordError.value = error.response?.data?.message || 'Failed to change password'
  } finally {
    isPasswordLoading.value = false
  }
}

onMounted(() => {
  initializeForm()
})
</script>

<style scoped>
.profile-container {
  min-height: 80vh;
  padding: 2rem 0;
  background: var(--background-color);
}

.container {
  max-width: 800px;
  margin: 0 auto;
  padding: 0 2rem;
}

h1 {
  text-align: center;
  margin-bottom: 2rem;
  color: var(--text-color);
}

.profile-content {
  display: flex;
  flex-direction: column;
  gap: 2rem;
}

.profile-card, .password-card {
  background: var(--card-bg);
  border-radius: 15px;
  padding: 2rem;
  box-shadow: 0 5px 15px rgba(0,0,0,0.1);
  border: 2px solid var(--border-color);
}

.profile-header {
  display: flex;
  align-items: center;
  gap: 1.5rem;
  margin-bottom: 2rem;
  padding-bottom: 2rem;
  border-bottom: 1px solid var(--border-color);
}

.avatar {
  width: 80px;
  height: 80px;
  background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
  border-radius: 50%;
  display: flex;
  align-items: center;
  justify-content: center;
  color: white;
  font-size: 2rem;
  font-weight: bold;
}

.user-info h2 {
  margin: 0 0 0.5rem 0;
  color: var(--text-color);
}

.user-role {
  color: var(--primary-color);
  font-weight: 500;
  margin: 0 0 0.25rem 0;
  text-transform: capitalize;
}

.user-email {
  color: var(--text-secondary);
  margin: 0;
}

.profile-form h3, .password-card h3 {
  margin-bottom: 1.5rem;
  color: var(--text-color);
}

.form {
  display: flex;
  flex-direction: column;
  gap: 1rem;
}

.form-row {
  display: grid;
  grid-template-columns: 1fr 1fr;
  gap: 1rem;
}

.form-group {
  display: flex;
  flex-direction: column;
}

.form-group label {
  margin-bottom: 0.5rem;
  font-weight: 500;
  color: var(--text-color);
}

.form-input {
  padding: 0.75rem;
  border: 2px solid var(--border-color);
  border-radius: 8px;
  font-size: 1rem;
  background: var(--input-bg);
  color: var(--text-color);
  transition: border-color 0.3s ease;
}

.form-input:focus {
  outline: none;
  border-color: var(--primary-color);
  box-shadow: 0 0 0 2px rgba(102, 126, 234, 0.25);
}

.form-input:disabled {
  background-color: var(--surface-color);
  opacity: 0.6;
  cursor: not-allowed;
}

.form-input.error {
  border-color: var(--error-color);
}

.form-hint {
  color: var(--text-secondary);
  font-size: 0.85rem;
  margin-top: 0.25rem;
}

.form-error {
  color: var(--error-color);
  font-size: 0.85rem;
  margin-top: 0.25rem;
}

.btn {
  padding: 0.75rem 1.5rem;
  border: none;
  border-radius: 8px;
  font-weight: 600;
  cursor: pointer;
  transition: all 0.3s ease;
  margin-top: 0.5rem;
}

.btn-primary {
  background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
  color: white;
}

.btn-primary:hover:not(:disabled) {
  transform: translateY(-2px);
  box-shadow: 0 5px 15px rgba(102, 126, 234, 0.4);
}

.btn-primary:disabled {
  opacity: 0.6;
  cursor: not-allowed;
  transform: none;
}

.error-message {
  background-color: var(--error-bg);
  color: var(--error-color);
  padding: 0.75rem;
  border-radius: 8px;
  border: 1px solid var(--error-color);
  text-align: center;
}

.success-message {
  background-color: var(--success-bg);
  color: var(--success-color);
  padding: 0.75rem;
  border-radius: 8px;
  border: 1px solid var(--success-color);
  text-align: center;
}

/* Dark mode adjustments */
[data-theme="dark"] .profile-card,
[data-theme="dark"] .password-card {
  box-shadow: 0 5px 15px rgba(0,0,0,0.3);
}

@media (max-width: 768px) {
  .profile-header {
    flex-direction: column;
    text-align: center;
  }
  
  .form-row {
    grid-template-columns: 1fr;
  }
}
</style>
