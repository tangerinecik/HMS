<template>
  <form @submit.prevent="handleSubmit" class="room-type-form">
    <div class="form-grid">
      <!-- Name Field -->
      <div class="form-group">
        <label for="name" class="form-label">
          <span class="label-icon">🏷️</span>
          Room Type Name
        </label>
        <input
          id="name"
          v-model="form.name"
          type="text"
          class="form-input"
          :class="{ 'input-error': errors.name }"
          placeholder="e.g., Deluxe Suite, Standard Room"
          required
          :disabled="loading"
        />
        <span v-if="errors.name" class="error-text">{{ errors.name }}</span>
      </div>

      <!-- Capacity Field -->
      <div class="form-group">
        <label for="capacity" class="form-label">
          <span class="label-icon">👥</span>
          Guest Capacity
        </label>
        <select
          id="capacity"
          v-model="form.capacity"
          class="form-select"
          :class="{ 'input-error': errors.capacity }"
          required
          :disabled="loading"
        >
          <option value="">Select capacity</option>
          <option value="1">1 Guest</option>
          <option value="2">2 Guests</option>
          <option value="3">3 Guests</option>
          <option value="4">4 Guests</option>
        </select>
        <span v-if="errors.capacity" class="error-text">{{ errors.capacity }}</span>
      </div>

      <!-- Location Field -->
      <div class="form-group">
        <label for="location" class="form-label">
          <span class="label-icon">📍</span>
          Location Type
        </label>
        <div class="radio-group">
          <label class="radio-option">
            <input
              v-model="form.location"
              type="radio"
              value="hotel"
              :disabled="loading"
            />
            <span class="radio-custom"></span>
            <span class="radio-label">
              <span class="radio-icon">🏨</span>
              Hotel
            </span>
          </label>
          <label class="radio-option">
            <input
              v-model="form.location"
              type="radio"
              value="cabin"
              :disabled="loading"
            />
            <span class="radio-custom"></span>
            <span class="radio-label">
              <span class="radio-icon">🏕️</span>
              Cabin
            </span>
          </label>
        </div>
        <span v-if="errors.location" class="error-text">{{ errors.location }}</span>
      </div>

      <!-- Price Field -->
      <div class="form-group">
        <label for="price_night" class="form-label">
          <span class="label-icon">💰</span>
          Price per Night
        </label>
        <div class="input-group">
          <span class="input-prefix">$</span>
          <input
            id="price_night"
            v-model="form.price_night"
            type="number"
            step="0.01"
            min="0"
            class="form-input"
            :class="{ 'input-error': errors.price_night }"
            placeholder="0.00"
            required
            :disabled="loading"
          />
        </div>
        <span v-if="errors.price_night" class="error-text">{{ errors.price_night }}</span>
      </div>
    </div>

    <!-- Form Actions -->
    <div class="form-actions">
      <GameButton
        type="button"
        variant="ghost"
        @click="$emit('cancel')"
        :disabled="loading"
      >
        Cancel
      </GameButton>
      <GameButton
        type="submit"
        variant="primary"
        :loading="loading"
      >
        {{ roomType ? 'Update' : 'Create' }} Room Type
      </GameButton>
    </div>
  </form>
</template>

<script setup lang="ts">
import { reactive, computed, watch } from 'vue'
import type { RoomType } from '@/stores/roomTypes'
import GameButton from '@/components/GameButton.vue'

interface Props {
  roomType?: RoomType | null
  loading?: boolean
}

interface Emits {
  (e: 'submit', formData: any): void
  (e: 'cancel'): void
}

const props = withDefaults(defineProps<Props>(), {
  roomType: null,
  loading: false
})

const emit = defineEmits<Emits>()

// Form state
const form = reactive({
  name: '',
  capacity: '',
  location: '',
  price_night: ''
})

// Form errors
const errors = reactive({
  name: '',
  capacity: '',
  location: '',
  price_night: ''
})

const clearErrors = () => {
  errors.name = ''
  errors.capacity = ''
  errors.location = ''
  errors.price_night = ''
}

// Watch for roomType changes to populate form
watch(() => props.roomType, (newRoomType) => {
  if (newRoomType) {
    form.name = newRoomType.name
    form.capacity = newRoomType.capacity.toString()
    form.location = newRoomType.location
    form.price_night = newRoomType.price_night.toString()
  } else {
    // Reset form for create mode
    form.name = ''
    form.capacity = ''
    form.location = ''
    form.price_night = ''
  }
  // Clear errors when roomType changes
  clearErrors()
}, { immediate: true })

const validateForm = () => {
  clearErrors()
  let isValid = true

  // Name validation
  if (!form.name.trim()) {
    errors.name = 'Room type name is required'
    isValid = false
  } else if (form.name.trim().length < 2) {
    errors.name = 'Name must be at least 2 characters'
    isValid = false
  }

  // Capacity validation
  if (!form.capacity) {
    errors.capacity = 'Capacity is required'
    isValid = false
  } else {
    const capacity = parseInt(form.capacity)
    if (capacity < 1 || capacity > 4) {
      errors.capacity = 'Capacity must be between 1 and 4'
      isValid = false
    }
  }

  // Location validation
  if (!form.location) {
    errors.location = 'Location type is required'
    isValid = false
  } else if (!['hotel', 'cabin'].includes(form.location)) {
    errors.location = 'Invalid location type'
    isValid = false
  }

  // Price validation
  if (!form.price_night) {
    errors.price_night = 'Price is required'
    isValid = false
  } else {
    const price = parseFloat(form.price_night)
    if (isNaN(price) || price <= 0) {
      errors.price_night = 'Price must be greater than 0'
      isValid = false
    }
  }

  return isValid
}

const handleSubmit = () => {
  if (!validateForm()) {
    return
  }

  const formData = {
    name: form.name.trim(),
    capacity: parseInt(form.capacity),
    location: form.location,
    price_night: parseFloat(form.price_night)
  }

  emit('submit', formData)
}
</script>

<style scoped>
.room-type-form {
  max-width: 600px;
  width: 100%;
}

.form-grid {
  display: grid;
  gap: 1.5rem;
  margin-bottom: 2rem;
}

.form-group {
  display: flex;
  flex-direction: column;
}

.form-label {
  display: flex;
  align-items: center;
  gap: 0.5rem;
  font-weight: 600;
  color: var(--text-color);
  margin-bottom: 0.5rem;
  font-size: 0.875rem;
  text-transform: uppercase;
  letter-spacing: 0.5px;
}

.label-icon {
  font-size: 1rem;
}

.form-input,
.form-select {
  padding: 0.75rem 1rem;
  border: 2px solid var(--border-color);
  border-radius: 12px;
  font-size: 1rem;
  background: var(--input-bg);
  color: var(--text-color);
  transition: all 0.2s ease;
}

.form-input:focus,
.form-select:focus {
  outline: none;
  border-color: var(--primary-color);
  box-shadow: 0 0 0 3px rgba(102, 126, 234, 0.1);
}

.input-error {
  border-color: #dc2626 !important;
  box-shadow: 0 0 0 3px rgba(220, 38, 38, 0.1) !important;
}

.input-group {
  position: relative;
  display: flex;
  align-items: center;
}

.input-prefix {
  position: absolute;
  left: 1rem;
  color: var(--text-muted);
  font-weight: 600;
  z-index: 1;
}

.input-group .form-input {
  padding-left: 2.5rem;
}

.radio-group {
  display: flex;
  gap: 1rem;
  flex-wrap: wrap;
}

.radio-option {
  display: flex;
  align-items: center;
  gap: 0.75rem;
  padding: 1rem;
  border: 2px solid var(--border-color);
  border-radius: 12px;
  cursor: pointer;
  transition: all 0.2s ease;
  flex: 1;
  min-width: 120px;
}

.radio-option:hover {
  border-color: var(--primary-color);
  background: var(--surface-color);
}

.radio-option input[type="radio"] {
  display: none;
}

.radio-custom {
  width: 20px;
  height: 20px;
  border: 2px solid var(--border-color);
  border-radius: 50%;
  position: relative;
  transition: all 0.2s ease;
}

.radio-option input[type="radio"]:checked + .radio-custom {
  border-color: var(--primary-color);
  background: var(--primary-color);
}

.radio-option input[type="radio"]:checked + .radio-custom::after {
  content: '';
  position: absolute;
  top: 50%;
  left: 50%;
  transform: translate(-50%, -50%);
  width: 8px;
  height: 8px;
  background: white;
  border-radius: 50%;
}

.radio-label {
  display: flex;
  align-items: center;
  gap: 0.5rem;
  font-weight: 500;
  color: var(--text-color);
}

.radio-icon {
  font-size: 1.25rem;
}

.error-text {
  color: #dc2626;
  font-size: 0.875rem;
  margin-top: 0.25rem;
  font-weight: 500;
}

.form-actions {
  display: flex;
  gap: 1rem;
  justify-content: flex-end;
  padding-top: 1rem;
  border-top: 2px solid var(--border-color);
}

/* Dark mode adjustments */
[data-theme="dark"] .form-input,
[data-theme="dark"] .form-select {
  background: var(--input-bg-dark);
  border-color: var(--border-color-dark);
}

[data-theme="dark"] .radio-option {
  border-color: var(--border-color-dark);
}

[data-theme="dark"] .radio-option:hover {
  background: var(--surface-color-dark);
}

[data-theme="dark"] .radio-custom {
  border-color: var(--border-color-dark);
}

[data-theme="dark"] .form-actions {
  border-top-color: var(--border-color-dark);
}

@media (max-width: 768px) {
  .form-actions {
    flex-direction: column-reverse;
  }
  
  .radio-group {
    flex-direction: column;
  }
  
  .radio-option {
    min-width: auto;
  }
}
</style>
