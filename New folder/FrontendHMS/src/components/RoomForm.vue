<template>
  <form @submit.prevent="handleSubmit" class="room-form">
    <div class="form-grid">
      <!-- Room Number Field -->
      <div class="form-group">
        <label for="number" class="form-label">
          <span class="label-icon">🏠</span>
          Room Number
        </label>
        <input
          id="number"
          v-model="form.number"
          type="text"
          class="form-input"
          :class="{ 'input-error': errors.number }"
          placeholder="e.g., 101, A-12, Suite-1"
          required
          :disabled="loading"
        />
        <span v-if="errors.number" class="error-text">{{ errors.number }}</span>
      </div>

      <!-- Room Type Field -->
      <div class="form-group">
        <label for="room_type_id" class="form-label">
          <span class="label-icon">🏷️</span>
          Room Type
        </label>
        <select
          id="room_type_id"
          v-model="form.room_type_id"
          class="form-select"
          :class="{ 'input-error': errors.room_type_id }"
          required
          :disabled="loading"
        >
          <option value="">Select a room type</option>
          <option 
            v-for="roomType in roomTypes" 
            :key="roomType.id" 
            :value="roomType.id"
          >
            {{ roomType.name }} - {{ roomType.location }} ({{ roomType.capacity }} guests, ${{ roomType.price_night }}/night)
          </option>
        </select>
        <span v-if="errors.room_type_id" class="error-text">{{ errors.room_type_id }}</span>
        <div v-if="!roomTypes.length" class="info-text">
          <span class="info-icon">ℹ️</span>
          No room types available. Please create room types first.
        </div>
      </div>      <!-- Floor Field -->
      <div class="form-group">
        <label for="floor" class="form-label">
          <span class="label-icon">🏢</span>
          Floor Number
        </label>
        <input
          id="floor"
          v-model="form.floor"
          type="number"
          min="0"
          max="50"
          class="form-input"
          :class="{ 'input-error': errors.floor }"
          placeholder="e.g., 1, 2, 3"
          required
          :disabled="loading"
        />
        <span v-if="errors.floor" class="error-text">{{ errors.floor }}</span>
        <div class="help-text">Floor 0 is ground floor/lobby level</div>
      </div>

      <!-- Status Field -->
      <div class="form-group">
        <label for="status" class="form-label">
          <span class="label-icon">📋</span>
          Room Status
        </label>
        <select
          id="status"
          v-model="form.status"
          class="form-select"
          :class="{ 'input-error': errors.status }"
          :disabled="loading"
        >
          <option 
            v-for="status in availableStatuses" 
            :key="status" 
            :value="status"
          >
            {{ getRoomStatusIcon(status) }} {{ getRoomStatusLabel(status) }}
          </option>
        </select>
        <span v-if="errors.status" class="error-text">{{ errors.status }}</span>
        <div class="help-text">
          {{ selectedStatusInfo?.description || 'Select room status' }}
        </div>
      </div>

      <!-- Quantity Field (only for creating new rooms) -->
      <div v-if="!room" class="form-group">
        <label for="quantity" class="form-label">
          <span class="label-icon">🔢</span>
          Number of Rooms to Create
        </label>
        <input
          id="quantity"
          v-model="form.quantity"
          type="number"
          min="1"
          max="50"
          class="form-input"
          :class="{ 'input-error': errors.quantity }"
          placeholder="1"
          :disabled="loading"
        />
        <span v-if="errors.quantity" class="error-text">{{ errors.quantity }}</span>
        <div class="help-text">
          Create multiple rooms with consecutive numbers. 
          <strong>Example:</strong> If you enter "101" and quantity "3", rooms 101, 102, 103 will be created.
        </div>
      </div>

      <!-- Room Type Preview -->
      <div v-if="selectedRoomType" class="room-type-preview">
        <h4 class="preview-title">
          <span class="preview-icon">👀</span>
          Room Type Details
        </h4>
        <div class="preview-content">
          <div class="preview-item">
            <span class="preview-label">Type:</span>
            <span class="preview-value">{{ selectedRoomType.name }}</span>
          </div>
          <div class="preview-item">
            <span class="preview-label">Location:</span>
            <span class="preview-value">
              {{ selectedRoomType.location === 'hotel' ? '🏨' : '🏕️' }} {{ selectedRoomType.location }}
            </span>
          </div>
          <div class="preview-item">
            <span class="preview-label">Capacity:</span>
            <span class="preview-value">{{ selectedRoomType.capacity }} guests</span>
          </div>
          <div class="preview-item">
            <span class="preview-label">Price:</span>
            <span class="preview-value price">${{ selectedRoomType.price_night }}/night</span>
          </div>
        </div>
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
        :disabled="!roomTypes.length"
      >
        {{ room ? 'Update' : 'Create' }} Room
      </GameButton>
    </div>
  </form>
</template>

<script setup lang="ts">
import { reactive, computed, watch } from 'vue'
import type { Room } from '@/stores/rooms'
import type { RoomType } from '@/stores/roomTypes'
import GameButton from '@/components/GameButton.vue'
import { getAvailableStatuses, getRoomStatusInfo, getRoomStatusIcon, getRoomStatusLabel } from '@/utils/roomStatus'

interface Props {
  room?: Room | null
  roomTypes: RoomType[]
  loading?: boolean
}

interface Emits {
  (e: 'submit', formData: any): void
  (e: 'cancel'): void
}

const props = withDefaults(defineProps<Props>(), {
  room: null,
  loading: false
})

const emit = defineEmits<Emits>()

// Form state
const form = reactive({
  number: '',
  room_type_id: '',
  floor: '',
  status: 'available' as Room['status'],
  quantity: '1'
})

// Form errors
const errors = reactive({
  number: '',
  room_type_id: '',
  floor: '',
  status: '',
  quantity: ''
})

const clearErrors = () => {
  errors.number = ''
  errors.room_type_id = ''
  errors.floor = ''
  errors.status = ''
  errors.quantity = ''
}

// Available room statuses
const availableStatuses = getAvailableStatuses()

// Selected room type details
const selectedRoomType = computed(() => {
  if (!form.room_type_id) return null
  return props.roomTypes.find(rt => rt.id === parseInt(form.room_type_id)) || null
})

// Selected status info
const selectedStatusInfo = computed(() => {
  return form.status ? getRoomStatusInfo(form.status) : null
})

// Watch for room changes to populate form
watch(() => props.room, (newRoom) => {
  if (newRoom) {
    form.number = newRoom.number
    // Handle both nested and flat room type data
    form.room_type_id = (newRoom.room_type?.id || newRoom.room_type_id)?.toString() || ''
    form.floor = newRoom.floor.toString()
    form.status = newRoom.status || 'available'
  } else {
    // Reset form for create mode
    form.number = ''
    form.room_type_id = ''
    form.floor = ''
    form.status = 'available'
    form.quantity = '1'
  }
  // Clear errors when room changes
  clearErrors()
}, { immediate: true })

const validateForm = () => {
  clearErrors()
  let isValid = true

  // Room number validation
  if (!form.number.trim()) {
    errors.number = 'Room number is required'
    isValid = false
  } else if (form.number.trim().length < 1) {
    errors.number = 'Room number must be at least 1 character'
    isValid = false
  }

  // Room type validation
  if (!form.room_type_id) {
    errors.room_type_id = 'Room type is required'
    isValid = false
  } else {
    const roomTypeId = parseInt(form.room_type_id)
    if (!props.roomTypes.find(rt => rt.id === roomTypeId)) {
      errors.room_type_id = 'Invalid room type selected'
      isValid = false
    }
  }

  // Floor validation
  if (!form.floor) {
    errors.floor = 'Floor number is required'
    isValid = false
  } else {
    const floor = parseInt(form.floor)
    if (isNaN(floor) || floor < 0 || floor > 50) {
      errors.floor = 'Floor must be between 0 and 50'
      isValid = false
    }
  }
  // Quantity validation (only for creating new rooms)
  if (!props.room && parseInt(form.quantity) < 1) {
    errors.quantity = 'Quantity must be at least 1'
    isValid = false
  }

  return isValid
}

const handleSubmit = () => {
  if (!validateForm()) {
    return
  }

  const formData = {
    number: form.number.trim(),
    room_type_id: parseInt(form.room_type_id),
    floor: parseInt(form.floor),
    status: form.status,
    quantity: props.room ? 1 : parseInt(form.quantity)
  }

  emit('submit', formData)
}
</script>

<style scoped>
.room-form {
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

.error-text {
  color: #dc2626;
  font-size: 0.875rem;
  margin-top: 0.25rem;
  font-weight: 500;
}

.help-text {
  color: var(--text-muted);
  font-size: 0.75rem;
  margin-top: 0.25rem;
  font-style: italic;
}

.info-text {
  display: flex;
  align-items: center;
  gap: 0.5rem;
  color: var(--primary-color);
  font-size: 0.875rem;
  margin-top: 0.5rem;
  padding: 0.75rem;
  background: rgba(102, 126, 234, 0.1);
  border-radius: 8px;
  border: 1px solid rgba(102, 126, 234, 0.2);
}

.info-icon {
  font-size: 1rem;
}

.room-type-preview {
  padding: 1.5rem;
  background: var(--surface-color);
  border: 2px solid var(--border-color);
  border-radius: 16px;
  margin-top: 0.5rem;
}

.preview-title {
  display: flex;
  align-items: center;
  gap: 0.5rem;
  font-size: 1rem;
  font-weight: 600;
  color: var(--text-color);
  margin-bottom: 1rem;
}

.preview-icon {
  font-size: 1.25rem;
}

.preview-content {
  display: grid;
  gap: 0.75rem;
}

.preview-item {
  display: flex;
  justify-content: space-between;
  align-items: center;
  padding: 0.5rem 0;
  border-bottom: 1px solid var(--border-color);
}

.preview-item:last-child {
  border-bottom: none;
}

.preview-label {
  color: var(--text-muted);
  font-weight: 500;
}

.preview-value {
  font-weight: 600;
  color: var(--text-color);
}

.preview-value.price {
  color: var(--primary-color);
  font-size: 1.125rem;
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

[data-theme="dark"] .room-type-preview {
  background: var(--surface-color-dark);
  border-color: var(--border-color-dark);
}

[data-theme="dark"] .preview-item {
  border-bottom-color: var(--border-color-dark);
}

[data-theme="dark"] .form-actions {
  border-top-color: var(--border-color-dark);
}

[data-theme="dark"] .info-text {
  background: rgba(102, 126, 234, 0.15);
  border-color: rgba(102, 126, 234, 0.3);
}

@media (max-width: 768px) {
  .form-actions {
    flex-direction: column-reverse;
  }
  
  .preview-item {
    flex-direction: column;
    align-items: flex-start;
    gap: 0.25rem;
  }
}
</style>
