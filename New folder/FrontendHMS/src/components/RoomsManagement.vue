<template>
  <div class="rooms-management">    <!-- Header with Add Button and Filters -->
    <div class="management-header">
      <div class="title-wrapper">
        <h2 class="section-title">🏠 Rooms Management</h2>
        <div v-if="selectedStatusFilter || selectedRoomTypeFilter" class="active-filters">            <span v-if="selectedStatusFilter" class="filter-tag">
            {{ getRoomStatusIcon(selectedStatusFilter as Room['status']) }} {{ getRoomStatusLabel(selectedStatusFilter as Room['status']) }}
            <button class="clear-filter" @click="clearStatusFilter">✕</button>
          </span>
          <span v-if="selectedRoomTypeFilter" class="filter-tag">
            {{ getSelectedRoomTypeName() }}
            <button class="clear-filter" @click="clearRoomTypeFilter">✕</button>
          </span>
          <button v-if="selectedStatusFilter || selectedRoomTypeFilter" 
                  class="clear-all-filters" 
                  @click="clearFilters">Clear All</button>
        </div>
      </div>
      <div class="header-actions">
        <select
          v-model="selectedRoomTypeFilter"
          class="filter-select"
          @change="applyFilter"
        >
          <option value="">All Room Types</option>
          <option 
            v-for="roomType in roomTypesStore.roomTypes" 
            :key="roomType.id" 
            :value="roomType.id"
          >
            {{ roomType.name }} ({{ roomType.location }})
          </option>
        </select>
        <select
          v-model="selectedStatusFilter"
          class="filter-select"
          @change="applyFilter"
        >
          <option value="">All Statuses</option>
          <option 
            v-for="status in availableStatuses" 
            :key="status" 
            :value="status"
          >
            {{ getRoomStatusIcon(status) }} {{ getRoomStatusLabel(status) }}
          </option>
        </select>
        <GameButton 
          variant="primary" 
          icon="<svg width='20' height='20' viewBox='0 0 24 24' fill='none' stroke='currentColor' stroke-width='2'><line x1='12' y1='5' x2='12' y2='19'></line><line x1='5' y1='12' x2='19' y2='12'></svg>"
          @click="openCreateModal"
        >
          Add Room
        </GameButton>
      </div>
    </div>

    <!-- Loading State -->
    <LoadingSpinner v-if="roomsStore.isLoading && roomsStore.rooms.length === 0" 
                    message="Loading rooms..." />

    <!-- Error State -->
    <div v-if="roomsStore.hasError" class="error-message">
      <div class="error-content">
        <span class="error-icon">⚠️</span>
        <p>{{ roomsStore.error }}</p>
        <GameButton variant="secondary" size="sm" @click="roomsStore.fetchRooms()">
          Try Again
        </GameButton>
      </div>
    </div>    <!-- Rooms Grid -->
    <div v-if="!roomsStore.isLoading || filteredRooms.length > 0" class="rooms-grid">
      <GameCard 
        v-for="room in filteredRooms" 
        :key="room.id"
        elevated
        clickable
        @click="selectRoom(room)"
      >        <template #header>
          <div class="room-header">
            <h3 class="room-number">Room {{ room.number }}</h3>
            <span class="location-badge" :class="`location-${getRoomTypeLocation(room)}`">
              {{ getRoomTypeLocation(room) === 'hotel' ? '🏨' : '🏕️' }} {{ getRoomTypeLocation(room) }}
            </span>
          </div>
        </template>        <div class="room-details">
          <div class="detail-item">
            <span class="detail-icon">📋</span>
            <span class="detail-label">Status:</span>
            <RoomStatusBadge :status="room.status" small />
          </div>
          
          <div class="detail-item">
            <span class="detail-icon">🏷️</span>
            <span class="detail-label">Type:</span>
            <span class="detail-value">{{ getRoomTypeName(room) }}</span>
          </div>
          
          <div class="detail-item">
            <span class="detail-icon">🏢</span>
            <span class="detail-label">Floor:</span>
            <span class="detail-value">{{ room.floor }}</span>
          </div>
          
          <div class="detail-item">
            <span class="detail-icon">👥</span>
            <span class="detail-label">Capacity:</span>
            <span class="detail-value">{{ getRoomTypeCapacity(room) }} {{ getRoomTypeCapacity(room) === 1 ? 'guest' : 'guests' }}</span>
          </div>
          
          <div class="detail-item">
            <span class="detail-icon">💰</span>
            <span class="detail-label">Price per night:</span>
            <span class="detail-value price">${{ getRoomTypePrice(room) }}</span>
          </div>
        </div>        <template #footer>
          <div class="room-actions">
            <div class="status-change">
              <select 
                :value="room.status"
                @change="updateRoomStatus(room, ($event.target as HTMLSelectElement).value as Room['status'])"
                class="status-select"
                @click.stop
              >
                <option 
                  v-for="status in availableStatuses" 
                  :key="status" 
                  :value="status"
                >
                  {{ getRoomStatusIcon(status) }} {{ getRoomStatusLabel(status) }}
                </option>
              </select>
            </div>
            <GameButton 
              variant="secondary" 
              size="sm"
              icon="<svg width='16' height='16' viewBox='0 0 24 24' fill='none' stroke='currentColor' stroke-width='2'><path d='m18 15-6-6-6 6'/></svg>"
              @click.stop="openEditModal(room)"
            >
              Edit
            </GameButton>
            <GameButton 
              variant="danger" 
              size="sm"
              icon="<svg width='16' height='16' viewBox='0 0 24 24' fill='none' stroke='currentColor' stroke-width='2'><polyline points='3,6 5,6 21,6'></polyline><path d='m19,6v14a2,2 0 0,1-2,2H7a2,2 0 0,1-2-2V6m3,0V4a2,2 0 0,1 2-2h4a2,2 0 0,1 2,2v2'></path></svg>"
              @click.stop="confirmDelete(room)"
            >
              Delete
            </GameButton>
          </div>
        </template>
      </GameCard>      <!-- Empty State -->
      <div v-if="filteredRooms.length === 0 && !roomsStore.isLoading" class="empty-state">
        <div class="empty-content">
          <div class="empty-icon">🏠</div>
          <h3>No Rooms Found</h3>          <p v-if="selectedRoomTypeFilter || selectedStatusFilter">
            No rooms match the selected filters.
            <span v-if="selectedRoomTypeFilter">Room type: {{ selectedRoomTypeFilter }}</span>
            <span v-if="selectedStatusFilter">Status: {{ getRoomStatusLabel(selectedStatusFilter as Room['status']) }}</span>
          </p>
          <p v-else-if="roomsStore.rooms.length === 0">
            Start by creating your first room. Make sure you have room types created first.
          </p>
          <GameButton 
            v-if="selectedRoomTypeFilter || selectedStatusFilter" 
            variant="secondary" 
            @click="clearFilters"
          >
            Clear Filters
          </GameButton>
          <GameButton 
            v-else-if="roomTypesStore.roomTypes.length > 0"
            variant="primary" 
            @click="openCreateModal"
          >
            Create First Room
          </GameButton>
          <GameButton 
            v-else
            variant="secondary" 
            @click="$emit('switch-tab', 'room-types')"
          >
            Create Room Types First
          </GameButton>
        </div>
      </div>
    </div>

    <!-- Pagination -->
    <div v-if="roomsStore.pagination && roomsStore.pagination.totalPages > 1" class="pagination">
      <GameButton 
        variant="ghost" 
        size="sm" 
        :disabled="!roomsStore.pagination.hasPrevious"
        @click="previousPage"
      >
        Previous
      </GameButton>
      
      <span class="pagination-info">
        Page {{ roomsStore.pagination.page }} of {{ roomsStore.pagination.totalPages }}
      </span>
      
      <GameButton 
        variant="ghost" 
        size="sm" 
        :disabled="!roomsStore.pagination.hasNext"
        @click="nextPage"
      >
        Next
      </GameButton>
    </div>

    <!-- Create/Edit Modal -->
    <GameModal v-model="showModal" :title="modalTitle">
      <RoomForm 
        :room="selectedRoom"
        :room-types="roomTypesStore.roomTypes"
        :loading="roomsStore.isLoading"
        @submit="handleFormSubmit"
        @cancel="closeModal"
      />
    </GameModal>

    <!-- Delete Confirmation Modal -->
    <GameModal v-model="showDeleteModal" title="Confirm Deletion">
      <div class="delete-confirmation">
        <div class="warning-icon">⚠️</div>
        <h3>Delete Room</h3>
        <p>Are you sure you want to delete <strong>Room {{ roomToDelete?.number }}</strong>?</p>
        <p class="warning-text">This action cannot be undone and will fail if there are active bookings for this room.</p>
      </div>
      
      <template #footer>
        <GameButton variant="ghost" @click="showDeleteModal = false">
          Cancel
        </GameButton>
        <GameButton 
          variant="danger" 
          :loading="roomsStore.isLoading"
          @click="handleDelete"
        >
          Delete
        </GameButton>
      </template>
    </GameModal>

    <!-- Success Message -->
    <div v-if="successMessage" class="success-message">
      <div class="success-content">
        <span class="success-icon">✅</span>
        <p>{{ successMessage }}</p>
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
import { ref, computed, onMounted, onUnmounted } from 'vue'
import { useRoomTypesStore } from '@/stores/roomTypes'
import { useRoomsStore } from '@/stores/rooms'
import type { Room } from '@/stores/rooms'
import GameCard from '@/components/GameCard.vue'
import GameButton from '@/components/GameButton.vue'
import GameModal from '@/components/GameModal.vue'
import LoadingSpinner from '@/components/LoadingSpinner.vue'
import RoomForm from '@/components/RoomForm.vue'
import RoomStatusBadge from '@/components/RoomStatusBadge.vue'
import { getAvailableStatuses, getRoomStatusIcon, getRoomStatusLabel } from '@/utils/roomStatus'

interface Emits {
  (e: 'switch-tab', tab: string): void
}

const emit = defineEmits<Emits>()

const roomTypesStore = useRoomTypesStore()
const roomsStore = useRoomsStore()

// Filter state
const selectedRoomTypeFilter = ref('')
const selectedStatusFilter = ref<Room['status'] | ''>('')

// Available statuses for filter
const availableStatuses = getAvailableStatuses()

// Modal states
const showModal = ref(false)
const showDeleteModal = ref(false)
const selectedRoom = ref<Room | null>(null)
const roomToDelete = ref<Room | null>(null)

// Success message state  
const successMessage = ref('')

const modalTitle = computed(() => 
  selectedRoom.value ? 'Edit Room' : 'Create Room'
)

const filteredRooms = computed(() => {
  let rooms = roomsStore.rooms
  
  // Filter by room type if selected
  if (selectedRoomTypeFilter.value) {
    const roomTypeId = parseInt(selectedRoomTypeFilter.value)
    rooms = rooms.filter(room => 
      room.room_type_id === roomTypeId || room.room_type?.id === roomTypeId
    )
  }
  
  // Filter by status if selected
  if (selectedStatusFilter.value) {
    rooms = rooms.filter(room => room.status === selectedStatusFilter.value)
  }
  
  return rooms
})

// Handle filter events from parent component
const handleFilterEvent = (event: any) => {
  if (event.detail) {
    const { status, roomTypeId } = event.detail;
    
    if (status) {
      selectedStatusFilter.value = status;
    }
    
    if (roomTypeId) {
      selectedRoomTypeFilter.value = roomTypeId;
    }
    
    // Switch to the rooms tab and apply filters
    applyFilter();
  }
}

onMounted(async () => {
  // Load room types if not already loaded
  if (roomTypesStore.roomTypes.length === 0) {
    await roomTypesStore.fetchRoomTypes()
  }
  
  // Load rooms if not already loaded
  if (roomsStore.rooms.length === 0) {
    await roomsStore.fetchRooms()
  }
  
  // Listen for filter events from parent component
  window.addEventListener('filter-rooms', handleFilterEvent);
})

onUnmounted(() => {
  // Remove event listener
  window.removeEventListener('filter-rooms', handleFilterEvent);
})

// Filter actions
const applyFilter = async () => {
  const roomTypeId = selectedRoomTypeFilter.value ? parseInt(selectedRoomTypeFilter.value) : undefined
  
  // Always fetch all rooms first, then filter client-side for better UX
  // This ensures filtering works correctly and we have all data
  await roomsStore.fetchRooms(1, 100) // Fetch more rooms for better filtering
  
  // Client-side filtering is handled by the filteredRooms computed property
  // This ensures both room type and status filters work together properly
}

const clearFilters = () => {
  selectedRoomTypeFilter.value = ''
  selectedStatusFilter.value = ''
  applyFilter()
}

const clearStatusFilter = () => {
  selectedStatusFilter.value = ''
  applyFilter()
}

const clearRoomTypeFilter = () => {
  selectedRoomTypeFilter.value = ''
  applyFilter()
}

const getSelectedRoomTypeName = () => {
  if (!selectedRoomTypeFilter.value) return ''
  
  const roomTypeId = parseInt(selectedRoomTypeFilter.value)
  const roomType = roomTypesStore.roomTypes.find(rt => rt.id === roomTypeId)
  return roomType ? `${roomType.name} (${roomType.location})` : ''
}

// Status update
const updateRoomStatus = async (room: Room, newStatus: Room['status']) => {
  const result = await roomsStore.updateRoomStatus(room.id, newStatus)
  
  if (result.success) {
    showSuccess(`Room ${room.number} status updated to ${getRoomStatusLabel(newStatus)}`)
    
    // Notify parent component (dashboard) to refresh stats
    const event = new CustomEvent('room-status-updated', {
      detail: { roomId: room.id, oldStatus: room.status, newStatus }
    })
    window.dispatchEvent(event)
  } else {
    // Error is already handled by the store, just show a general message
    console.error('Failed to update room status:', result.error)
  }
}

// Modal actions
const openCreateModal = () => {
  selectedRoom.value = null
  showModal.value = true
}

const openEditModal = (room: Room) => {
  selectedRoom.value = room
  showModal.value = true
}

const closeModal = () => {
  showModal.value = false
  selectedRoom.value = null
}

const selectRoom = (room: Room) => {
  // Could open a detail view or select for bulk operations
  console.log('Selected room:', room)
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
  
  if (selectedRoom.value) {
    // Editing existing room - no bulk creation
    result = await roomsStore.updateRoom(selectedRoom.value.id, {
      number: formData.number,
      room_type_id: formData.room_type_id,
      floor: formData.floor
    })
  } else {
    // Creating new room(s)
    if (formData.quantity && formData.quantity > 1) {
      // Bulk creation
      result = await roomsStore.createBulkRooms(formData)
    } else {
      // Single room creation
      result = await roomsStore.createRoom({
        number: formData.number,
        room_type_id: formData.room_type_id,
        floor: formData.floor
      })
    }
  }
  
  if (result.success) {
    closeModal()
    // Refresh the list to show updated data
    applyFilter()
    // Show success message
    showSuccess(selectedRoom.value ? 'Room updated successfully!' : 'Room created successfully!')
    
    // Notify parent component (dashboard) to refresh stats
    const event = new CustomEvent('rooms-changed', { 
      detail: { action: selectedRoom.value ? 'updated' : 'created' }
    })
    window.dispatchEvent(event)
  }
}

// Delete actions
const confirmDelete = (room: Room) => {
  roomToDelete.value = room
  showDeleteModal.value = true
}

const handleDelete = async () => {
  if (roomToDelete.value) {
    const result = await roomsStore.deleteRoom(roomToDelete.value.id)
    if (result.success) {
      showDeleteModal.value = false
      roomToDelete.value = null
      // Refresh the list
      applyFilter()
      // Show success message
      showSuccess('Room deleted successfully!')
      
      // Notify parent component (dashboard) to refresh stats
      const event = new CustomEvent('rooms-changed', { 
        detail: { action: 'deleted' }
      })
      window.dispatchEvent(event)
    }
  }
}

// Pagination
const previousPage = () => {
  if (roomsStore.pagination?.hasPrevious) {
    const roomTypeId = selectedRoomTypeFilter.value ? parseInt(selectedRoomTypeFilter.value) : undefined
    roomsStore.fetchRooms(roomsStore.pagination.page - 1, 10, roomTypeId)
  }
}

const nextPage = () => {
  if (roomsStore.pagination?.hasNext) {
    const roomTypeId = selectedRoomTypeFilter.value ? parseInt(selectedRoomTypeFilter.value) : undefined
    roomsStore.fetchRooms(roomsStore.pagination.page + 1, 10, roomTypeId)
  }
}

// Helper functions to get room type data (handles both nested and flat formats)
const getRoomTypeLocation = (room: Room) => {
  return room.room_type?.location || room.location || 'hotel'
}

const getRoomTypeName = (room: Room) => {
  return room.room_type?.name || room.room_type_name || 'Unknown'
}

const getRoomTypeCapacity = (room: Room) => {
  return room.room_type?.capacity || room.capacity || 1
}

const getRoomTypePrice = (room: Room) => {
  return room.room_type?.price_night || room.price_night || 0
}
</script>

<style scoped>
.rooms-management {
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

.title-wrapper {
  display: flex;
  flex-direction: column;
  gap: 0.5rem;
}

.active-filters {
  display: flex;
  flex-wrap: wrap;
  gap: 0.5rem;
  align-items: center;
}

.filter-tag {
  display: inline-flex;
  align-items: center;
  background: var(--primary-gradient);
  color: white;
  padding: 0.25rem 0.75rem;
  border-radius: 20px;
  font-size: 0.875rem;
  box-shadow: 0 2px 5px rgba(0, 0, 0, 0.1);
}

.clear-filter {
  background: none;
  border: none;
  color: white;
  margin-left: 0.5rem;
  cursor: pointer;
  opacity: 0.7;
  transition: opacity 0.2s;
  padding: 0.1rem 0.3rem;
  border-radius: 50%;
}

.clear-filter:hover {
  opacity: 1;
  background: rgba(255, 255, 255, 0.2);
}

.clear-all-filters {
  background: none;
  border: 1px solid var(--border-color);
  border-radius: 20px;
  padding: 0.25rem 0.75rem;
  font-size: 0.875rem;
  margin-left: 0.5rem;
  color: var(--text-color);
  cursor: pointer;
  transition: all 0.2s;
}

.clear-all-filters:hover {
  background: var(--surface-color);
  border-color: var(--primary-color);
}

.section-title {
  font-size: 1.75rem;
  font-weight: 700;
  color: var(--text-color);
  margin: 0;
}

.header-actions {
  display: flex;
  align-items: center;
  gap: 1rem;
  flex-wrap: wrap;
}

.filter-select {
  padding: 0.5rem 1rem;
  border: 2px solid var(--border-color);
  border-radius: 8px;
  background: var(--input-bg);
  color: var(--text-color);
  font-size: 0.875rem;
  min-width: 180px;
}

.rooms-grid {
  display: grid;
  grid-template-columns: repeat(auto-fill, minmax(320px, 1fr));
  gap: 1.5rem;
  margin-bottom: 2rem;
}

.room-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
  gap: 1rem;
}

.room-number {
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

.room-details {
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

.room-actions {
  display: flex;
  gap: 0.75rem;
  justify-content: flex-end;
  align-items: center;
  flex-wrap: wrap;
}

.status-change {
  flex: 1;
  min-width: 150px;
}

.status-select {
  width: 100%;
  padding: 0.375rem 0.75rem;
  border: 1px solid var(--border-color);
  border-radius: 8px;
  background: var(--card-bg);
  color: var(--text-color);
  font-size: 0.875rem;
  cursor: pointer;
  transition: all 0.2s ease;
}

.status-select:hover {
  border-color: var(--primary-color);
}

.status-select:focus {
  outline: none;
  border-color: var(--primary-color);
  box-shadow: 0 0 0 2px rgba(102, 126, 234, 0.2);
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

/* Dark mode adjustments */
[data-theme="dark"] .filter-select {
  background: var(--input-bg-dark);
  border-color: var(--border-color-dark);
}

@media (max-width: 768px) {
  .management-header {
    flex-direction: column;
    align-items: stretch;
  }
  
  .header-actions {
    flex-direction: column;
  }
  
  .filter-select {
    min-width: auto;
  }
  
  .rooms-grid {
    grid-template-columns: 1fr;
  }
  
  .room-header {
    flex-direction: column;
    align-items: flex-start;
    gap: 0.5rem;
  }
  
  .room-actions {
    justify-content: center;
  }
  
  .pagination {
    flex-direction: column;
    gap: 0.5rem;
  }
}
</style>
