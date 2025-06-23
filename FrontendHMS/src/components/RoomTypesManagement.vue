<template>
  <div class="room-types-management">
    <!-- Header with Add Button -->
    <div class="management-header">
      <h2 class="section-title">🏷️ Room Types Management</h2>
      <GameButton 
        variant="primary" 
        icon="<svg width='20' height='20' viewBox='0 0 24 24' fill='none' stroke='currentColor' stroke-width='2'><line x1='12' y1='5' x2='12' y2='19'></line><line x1='5' y1='12' x2='19' y2='12'></svg>"
        @click="openCreateModal"
      >
        Add Room Type
      </GameButton>
    </div>

    <!-- Loading State -->
    <LoadingSpinner v-if="roomTypesStore.isLoading && roomTypesStore.roomTypes.length === 0" 
                    message="Loading room types..." />

    <!-- Error State -->
    <div v-if="roomTypesStore.hasError" class="error-message">
      <div class="error-content">
        <span class="error-icon">⚠️</span>
        <p>{{ roomTypesStore.error }}</p>
        <GameButton variant="secondary" size="sm" @click="roomTypesStore.fetchRoomTypes()">
          Try Again
        </GameButton>
      </div>
    </div>    <!-- Success Message -->
    <div v-if="successMessage" class="success-message">
      <div class="success-content">
        <span class="success-icon">✅</span>
        <p>{{ successMessage }}</p>
      </div>
    </div>
    
    <!-- Form Error Message -->
    <div v-if="errorMessage" class="error-message">
      <div class="error-content">
        <span class="error-icon">⚠️</span>
        <p>{{ errorMessage }}</p>
      </div>
    </div>

    <!-- Room Types Grid -->
    <div v-if="!roomTypesStore.isLoading || roomTypesStore.roomTypes.length > 0" class="room-types-grid">
      <GameCard 
        v-for="roomType in roomTypesStore.roomTypes" 
        :key="roomType.id"
        elevated
        clickable
        @click="selectRoomType(roomType)"
      >
        <template #header>
          <div class="room-type-header">
            <h3 class="room-type-name">{{ roomType.name }}</h3>
            <span class="location-badge" :class="`location-${roomType.location}`">
              {{ roomType.location === 'hotel' ? '🏨' : '🏕️' }} {{ roomType.location }}
            </span>
          </div>
        </template>

        <div class="room-type-details">
          <div class="detail-item">
            <span class="detail-icon">👥</span>
            <span class="detail-label">Capacity:</span>
            <span class="detail-value">{{ roomType.capacity }} {{ roomType.capacity === 1 ? 'guest' : 'guests' }}</span>
          </div>
          
          <div class="detail-item">
            <span class="detail-icon">💰</span>
            <span class="detail-label">Price per night:</span>
            <span class="detail-value price">${{ roomType.price_night }}</span>
          </div>
        </div>

        <template #footer>
          <div class="room-type-actions">
            <GameButton 
              variant="secondary" 
              size="sm"
              icon="<svg width='16' height='16' viewBox='0 0 24 24' fill='none' stroke='currentColor' stroke-width='2'><path d='m18 15-6-6-6 6'/></svg>"
              @click.stop="openEditModal(roomType)"
            >
              Edit
            </GameButton>
            <GameButton 
              variant="danger" 
              size="sm"
              icon="<svg width='16' height='16' viewBox='0 0 24 24' fill='none' stroke='currentColor' stroke-width='2'><polyline points='3,6 5,6 21,6'></polyline><path d='m19,6v14a2,2 0 0,1-2,2H7a2,2 0 0,1-2-2V6m3,0V4a2,2 0 0,1 2-2h4a2,2 0 0,1 2,2v2'></path></svg>"
              @click.stop="confirmDelete(roomType)"
            >
              Delete
            </GameButton>
          </div>
        </template>
      </GameCard>

      <!-- Empty State -->
      <div v-if="roomTypesStore.roomTypes.length === 0" class="empty-state">
        <div class="empty-content">
          <div class="empty-icon">🏷️</div>
          <h3>No Room Types Yet</h3>
          <p>Start by creating your first room type to organize your accommodations.</p>
          <GameButton variant="primary" @click="openCreateModal">
            Create First Room Type
          </GameButton>
        </div>
      </div>
    </div>

    <!-- Pagination -->
    <div v-if="roomTypesStore.pagination && roomTypesStore.pagination.totalPages > 1" class="pagination">
      <GameButton 
        variant="ghost" 
        size="sm" 
        :disabled="!roomTypesStore.pagination.hasPrevious"
        @click="previousPage"
      >
        Previous
      </GameButton>
      
      <span class="pagination-info">
        Page {{ roomTypesStore.pagination.page }} of {{ roomTypesStore.pagination.totalPages }}
      </span>
      
      <GameButton 
        variant="ghost" 
        size="sm" 
        :disabled="!roomTypesStore.pagination.hasNext"
        @click="nextPage"
      >
        Next
      </GameButton>
    </div>

    <!-- Create/Edit Modal -->
    <GameModal v-model="showModal" :title="modalTitle">
      <RoomTypeForm 
        :room-type="selectedRoomType"
        :loading="roomTypesStore.isLoading"
        @submit="handleFormSubmit"
        @cancel="closeModal"
      />
    </GameModal>

    <!-- Delete Confirmation Modal -->
    <GameModal v-model="showDeleteModal" title="Confirm Deletion">
      <div class="delete-confirmation">
        <div class="warning-icon">⚠️</div>
        <h3>Delete Room Type</h3>
        <p>Are you sure you want to delete <strong>{{ roomTypeToDelete?.name }}</strong>?</p>
        <p class="warning-text">This action cannot be undone and will fail if there are rooms using this type.</p>
      </div>
      
      <template #footer>
        <GameButton variant="ghost" @click="showDeleteModal = false">
          Cancel
        </GameButton>
        <GameButton 
          variant="danger" 
          :loading="roomTypesStore.isLoading"
          @click="handleDelete"
        >
          Delete
        </GameButton>
      </template>
    </GameModal>
  </div>
</template>

<script setup lang="ts">
import { ref, computed } from 'vue'
import { useRoomTypesStore } from '@/stores/roomTypes'
import type { RoomType } from '@/stores/roomTypes'
import GameCard from '@/components/GameCard.vue'
import GameButton from '@/components/GameButton.vue'
import GameModal from '@/components/GameModal.vue'
import LoadingSpinner from '@/components/LoadingSpinner.vue'
import RoomTypeForm from '@/components/RoomTypeForm.vue'

const roomTypesStore = useRoomTypesStore()

// Modal states
const showModal = ref(false)
const showDeleteModal = ref(false)
const selectedRoomType = ref<RoomType | null>(null)
const roomTypeToDelete = ref<RoomType | null>(null)

// Message states
const successMessage = ref('')
const errorMessage = ref('')

const modalTitle = computed(() => 
  selectedRoomType.value ? 'Edit Room Type' : 'Create Room Type'
)

// Modal actions
const openCreateModal = () => {
  selectedRoomType.value = null
  showModal.value = true
}

const openEditModal = (roomType: RoomType) => {
  selectedRoomType.value = roomType
  showModal.value = true
}

const closeModal = () => {
  showModal.value = false
  selectedRoomType.value = null
}

const selectRoomType = (roomType: RoomType) => {
  // Could open a detail view or select for bulk operations
  console.log('Selected room type:', roomType)
}

// Show success message
const showSuccess = (message: string) => {
  successMessage.value = message
  setTimeout(() => {
    successMessage.value = ''
  }, 3000)
}

// Form submission
const handleFormSubmit = async (formData: any) => {
  let result
  
  try {
    if (selectedRoomType.value) {
      result = await roomTypesStore.updateRoomType(selectedRoomType.value.id, formData)
    } else {
      result = await roomTypesStore.createRoomType(formData)
    }
    
    if (result.success) {
      closeModal()
      showSuccess('Room type saved successfully!')
    } else {
      // Display error message
      const errMsg = result.error || 'Failed to save room type'
      errorMessage.value = errMsg
      
      // If it's a permissions error, provide clearer guidance
      if (errMsg.includes('permission') || errMsg.includes('Insufficient')) {
        errorMessage.value = 'Permission error: You may need to log out and log back in to refresh your session.'
      }
      
      // Clear error message after a few seconds
      setTimeout(() => {
        errorMessage.value = '';
      }, 5000);
    }
  } catch (error: any) {
    console.error('Error in handling form submission:', error)
    errorMessage.value = 'An unexpected error occurred. Please try again.'
    
    // Clear error message after a few seconds
    setTimeout(() => {
      errorMessage.value = '';
    }, 5000);
  }
}

// Delete actions
const confirmDelete = (roomType: RoomType) => {
  roomTypeToDelete.value = roomType
  showDeleteModal.value = true
}

const handleDelete = async () => {
  if (roomTypeToDelete.value) {
    const result = await roomTypesStore.deleteRoomType(roomTypeToDelete.value.id)
    if (result.success) {
      showDeleteModal.value = false
      roomTypeToDelete.value = null
      showSuccess('Room type deleted successfully!')
    }
  }
}

// Pagination
const previousPage = () => {
  if (roomTypesStore.pagination?.hasPrevious) {
    roomTypesStore.fetchRoomTypes(roomTypesStore.pagination.page - 1)
  }
}

const nextPage = () => {
  if (roomTypesStore.pagination?.hasNext) {
    roomTypesStore.fetchRoomTypes(roomTypesStore.pagination.page + 1)
  }
}
</script>

<style scoped>
.room-types-management {
  width: 100%;
}

.management-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
  margin-bottom: 2rem;
  flex-wrap: wrap;
  gap: 1rem;
}

.section-title {
  font-size: 1.75rem;
  font-weight: 700;
  color: var(--text-color);
  margin: 0;
}

.room-types-grid {
  display: grid;
  grid-template-columns: repeat(auto-fill, minmax(320px, 1fr));
  gap: 1.5rem;
  margin-bottom: 2rem;
}

.room-type-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
  gap: 1rem;
}

.room-type-name {
  font-size: 1.25rem;
  font-weight: 600;
  margin: 0;
  color: white;
  flex: 1;
}

.location-badge {
  padding: 0.25rem 0.75rem;
  border-radius: 12px;
  font-size: 0.75rem;
  font-weight: 600;
  text-transform: uppercase;
  letter-spacing: 0.5px;
}

.location-hotel {
  background: rgba(34, 197, 94, 0.2);
  color: #22c55e;
  border: 1px solid #22c55e;
}

.location-cabin {
  background: rgba(245, 158, 11, 0.2);
  color: #f59e0b;
  border: 1px solid #f59e0b;
}

.room-type-details {
  display: flex;
  flex-direction: column;
  gap: 1rem;
}

.detail-item {
  display: flex;
  align-items: center;
  gap: 0.5rem;
}

.detail-icon {
  font-size: 1.25rem;
  width: 24px;
}

.detail-label {
  color: var(--text-muted);
  font-weight: 500;
}

.detail-value {
  font-weight: 600;
  color: var(--text-color);
  margin-left: auto;
}

.detail-value.price {
  color: var(--primary-color);
  font-size: 1.125rem;
}

.room-type-actions {
  display: flex;
  gap: 0.75rem;
  justify-content: flex-end;
}

.error-message {
  background: var(--card-bg);
  border: 2px solid #dc2626;
  border-radius: 16px;
  padding: 2rem;
  margin-bottom: 2rem;
}

.error-content {
  display: flex;
  flex-direction: column;
  align-items: center;
  gap: 1rem;
  text-align: center;
}

.error-icon {
  font-size: 3rem;
}

.success-message {
  background: var(--card-bg);
  border: 2px solid #22c55e;
  border-radius: 16px;
  padding: 1.5rem;
  margin-bottom: 2rem;
}

.success-content {
  display: flex;
  align-items: center;
  gap: 1rem;
  text-align: center;
  justify-content: center;
}

.success-icon {
  font-size: 2rem;
}

.success-content p {
  color: #22c55e;
  font-weight: 600;
  margin: 0;
}

.empty-state {
  grid-column: 1 / -1;
  display: flex;
  justify-content: center;
  align-items: center;
  min-height: 300px;
}

.empty-content {
  text-align: center;
  max-width: 400px;
}

.empty-icon {
  font-size: 4rem;
  margin-bottom: 1rem;
}

.empty-content h3 {
  font-size: 1.5rem;
  color: var(--text-color);
  margin-bottom: 0.5rem;
}

.empty-content p {
  color: var(--text-muted);
  margin-bottom: 2rem;
  line-height: 1.6;
}

.pagination {
  display: flex;
  justify-content: center;
  align-items: center;
  gap: 1rem;
  margin-top: 2rem;
}

.pagination-info {
  font-weight: 500;
  color: var(--text-muted);
}

.delete-confirmation {
  text-align: center;
  padding: 1rem;
}

.warning-icon {
  font-size: 3rem;
  margin-bottom: 1rem;
}

.delete-confirmation h3 {
  color: var(--text-color);
  margin-bottom: 1rem;
}

.delete-confirmation p {
  color: var(--text-muted);
  margin-bottom: 0.5rem;
}

.warning-text {
  color: #dc2626 !important;
  font-weight: 500;
  font-size: 0.875rem;
}

@media (max-width: 768px) {
  .management-header {
    flex-direction: column;
    align-items: stretch;
  }
  
  .room-types-grid {
    grid-template-columns: 1fr;
  }
  
  .room-type-header {
    flex-direction: column;
    align-items: flex-start;
    gap: 0.5rem;
  }
  
  .room-type-actions {
    justify-content: center;
  }
  
  .pagination {
    flex-direction: column;
    gap: 0.5rem;
  }
}
</style>
