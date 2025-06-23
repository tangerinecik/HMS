<template>
  <div class="rooms-container">
    <div class="container " style=" margin-top: 5%; ">
      <h1>Our Rooms & Suites</h1>
      <p class="subtitle">Choose from our selection of comfortable and luxurious accommodations</p>
      <span></span>


      <!-- Loading State -->
      <LoadingSpinner v-if="roomTypesStore.isLoading && roomTypes.length === 0" 
                      message="Loading rooms..." />

      <!-- Error State -->
      <div v-if="roomTypesStore.hasError" class="error-message">
        <div class="error-content">
          <span class="error-icon">⚠️</span>
          <p>{{ roomTypesStore.error }}</p>
          <GameButton variant="secondary" size="sm" @click="loadRoomTypes">
            Try Again
          </GameButton>
        </div>
      </div>

      <!-- Rooms Grid -->
      <div v-if="!roomTypesStore.isLoading && filteredRoomTypes.length > 0" class="rooms-grid">
        <div v-for="roomType in filteredRoomTypes" :key="roomType.id" class="room-card">
          <div class="room-image">
            <div class="room-placeholder">{{ roomType.name }}</div>
            <div class="location-badge" :class="`location-${roomType.location}`">
              {{ roomType.location === 'hotel' ? '🏨' : '🏕️' }} {{ roomType.location }}
            </div>
          </div>
          <div class="room-content">
            <h3>{{ roomType.name }}</h3>
            <p class="room-description">
              Comfortable accommodations with modern amenities for your perfect stay.
            </p>
            
            <div class="room-features">
              <div class="feature-item">
                <span class="feature-icon">�</span>
                <span>{{ roomType.capacity }} {{ roomType.capacity === 1 ? 'guest' : 'guests' }}</span>
              </div>
              <div class="feature-item">
                <span class="feature-icon">�</span>
                <span>From €{{ roomType.price_night }}/night</span>
              </div>
              <div class="feature-item">
                <span class="feature-icon">�</span>
                <span>{{ roomType.location === 'hotel' ? 'Hotel Building' : 'Private Cabin' }}</span>
              </div>
            </div>
            
            
            <div class="room-actions">

              <GameButton 
                variant="secondary" 
                @click="viewDetails(roomType)"
                class="details-button"
              >
                View Details
              </GameButton>
            </div>
          </div>
        </div>
      </div>

      <!-- Empty State -->
      <div v-if="!roomTypesStore.isLoading && filteredRoomTypes.length === 0 && roomTypes.length > 0" class="empty-state">
        <div class="empty-content">
          <div class="empty-icon">🔍</div>
          <h3>No Rooms Match Your Search</h3>
          <p>Try adjusting your search criteria or dates.</p>
          <GameButton variant="secondary" @click="clearSearch">
            Clear Search
          </GameButton>
        </div>
      </div>

      <!-- No Rooms Available -->
      <div v-if="!roomTypesStore.isLoading && roomTypes.length === 0" class="empty-state">
        <div class="empty-content">
          <div class="empty-icon">🏨</div>
          <h3>No Rooms Available</h3>
          <p>We're currently updating our room listings. Please check back soon!</p>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
import { ref, computed, onMounted } from 'vue'
import { useRouter } from 'vue-router'
import { useAuthStore } from '@/stores/auth'
import { useRoomTypesStore } from '@/stores/roomTypes'
import { useRoomsStore } from '@/stores/rooms'
import { roomsAPI } from '@/services/api'
import type { RoomType } from '@/stores/roomTypes'
import GameCard from '@/components/GameCard.vue'
import GameButton from '@/components/GameButton.vue'
import LoadingSpinner from '@/components/LoadingSpinner.vue'

const router = useRouter()
const authStore = useAuthStore()
const roomTypesStore = useRoomTypesStore()
const roomsStore = useRoomsStore()

// Search form state
const searchForm = ref({
  checkIn: '',
  checkOut: '',
  guests: '2',
  location: ''
})

// Availability data for each room type
const availabilityData = ref<Record<number, { available: number; total: number }>>({})

// Computed values
const today = computed(() => {
  const date = new Date()
  return date.toISOString().split('T')[0]
})

const minCheckOut = computed(() => {
  if (!searchForm.value.checkIn) return today.value
  const checkInDate = new Date(searchForm.value.checkIn)
  checkInDate.setDate(checkInDate.getDate() + 1)
  return checkInDate.toISOString().split('T')[0]
})

const roomTypes = computed(() => roomTypesStore.roomTypes)

const filteredRoomTypes = computed(() => {
  let filtered = roomTypes.value

  // Filter by location if selected
  if (searchForm.value.location) {
    filtered = filtered.filter(rt => rt.location === searchForm.value.location)
  }

  // Filter by capacity (guests)
  const guests = parseInt(searchForm.value.guests)
  filtered = filtered.filter(rt => rt.capacity >= guests)

  return filtered
})

// Methods
const loadRoomTypes = async () => {
  await roomTypesStore.fetchRoomTypes()
  // Also load rooms to get availability info
  await roomsStore.fetchRooms(1, 100) // Get all rooms for availability calculation
  calculateAvailability()
}

const searchRooms = async () => {
  await calculateAvailability()
}

const calculateAvailability = async () => {
  if (!searchForm.value.checkIn || !searchForm.value.checkOut) {
    // If no dates selected, use room status as availability
    const availabilityMap: Record<number, { available: number; total: number }> = {}
    
    roomTypes.value.forEach(roomType => {
      const roomsOfType = roomsStore.rooms.filter(room => 
        (room.room_type?.id || room.room_type_id) === roomType.id
      )
      
      const total = roomsOfType.length
      const available = roomsOfType.filter(room => room.status === 'available').length
      
      availabilityMap[roomType.id] = { available, total }
    })
    
    availabilityData.value = availabilityMap
    return
  }

  // Check availability for specific dates using API
  try {
    const availabilityMap: Record<number, { available: number; total: number }> = {}
    
    for (const roomType of roomTypes.value) {
      try {
        const response = await roomsAPI.checkAvailability(
          searchForm.value.checkIn,
          searchForm.value.checkOut,
          roomType.id
        )
        
        if (response.data) {
          availabilityMap[roomType.id] = {
            available: response.data.available || 0,
            total: response.data.total || 0
          }
        }
      } catch (error) {
        console.error(`Error checking availability for room type ${roomType.id}:`, error)
        // Fallback to status-based availability
        const roomsOfType = roomsStore.rooms.filter(room => 
          (room.room_type?.id || room.room_type_id) === roomType.id
        )
        availabilityMap[roomType.id] = {
          available: roomsOfType.filter(room => room.status === 'available').length,
          total: roomsOfType.length
        }
      }
    }
    
    availabilityData.value = availabilityMap
  } catch (error) {
    console.error('Error calculating availability:', error)
    // Fallback to status-based calculation
    calculateAvailabilityFromStatus()
  }
}

const calculateAvailabilityFromStatus = () => {
  const availabilityMap: Record<number, { available: number; total: number }> = {}
  
  roomTypes.value.forEach(roomType => {
    const roomsOfType = roomsStore.rooms.filter(room => 
      (room.room_type?.id || room.room_type_id) === roomType.id
    )
    
    const total = roomsOfType.length
    const available = roomsOfType.filter(room => room.status === 'available').length
    
    availabilityMap[roomType.id] = { available, total }
  })
  
  availabilityData.value = availabilityMap
}

const isRoomAvailable = (roomTypeId: number): boolean => {
  const availability = availabilityData.value[roomTypeId]
  return availability ? availability.available > 0 : false
}

const getBookButtonText = (roomTypeId: number): string => {
  if (!authStore.isAuthenticated) {
    return 'Login to Book'
  }
  
  if (!searchForm.value.checkIn || !searchForm.value.checkOut) {
    return 'Select Dates to Book'
  }
  
  if (!isRoomAvailable(roomTypeId)) {
    return 'No Rooms Available'
  }
  
  return 'Book Now'
}

const formatDate = (dateString: string): string => {
  if (!dateString) return ''
  const date = new Date(dateString)
  return date.toLocaleDateString('en-US', { 
    month: 'short', 
    day: 'numeric',
    year: 'numeric'
  })
}

const clearSearch = async () => {
  searchForm.value = {
    checkIn: '',
    checkOut: '',
    guests: '2',
    location: ''
  }
  await calculateAvailability()
}

const selectRoom = (roomType: RoomType) => {
  if (!authStore.isAuthenticated) {
    router.push({
      path: '/login',
      query: { redirect: `/rooms/${roomType.id}` }
    })
    return
  }
  
  if (!searchForm.value.checkIn || !searchForm.value.checkOut) {
    alert('Please select check-in and check-out dates first.')
    return
  }
  
  if (!isRoomAvailable(roomType.id)) {
    alert('Sorry, no rooms of this type are available for the selected dates.')
    return
  }
  
  // Navigate to room details with search params
  router.push({
    path: `/rooms/${roomType.id}`,
    query: {
      checkIn: searchForm.value.checkIn,
      checkOut: searchForm.value.checkOut,
      guests: searchForm.value.guests
    }
  })
}

const viewDetails = (roomType: RoomType) => {
  // Navigate to room details page
  router.push(`/rooms/${roomType.id}`)
}

// Initialize data on mount
onMounted(async () => {
  await loadRoomTypes()
})
</script>

<style scoped>
.rooms-container {
  min-height: 80vh;
  padding: 2rem 0;
  background: var(--page-bg);
}

.container {
  max-width: 1200px;
  margin: 0 auto;
  padding: 0 2rem;
}

h1 {
  text-align: center;
  margin-bottom: 1rem;
  color: var(--text-color);
  font-size: 2.5rem;
  font-weight: 700;
}

.subtitle {
  text-align: center;
  margin-bottom: 3rem;
  color: var(--text-muted);
  font-size: 1.2rem;
}

/* Search Section */
.search-section {
  margin-bottom: 3rem;
}

.search-card {
  max-width: 800px;
  margin: 0 auto;
}

.search-header {
  text-align: center;
  margin-bottom: 2rem;
}

.search-header h3 {
  font-size: 1.5rem;
  font-weight: 600;
  color: var(--text-color);
  margin: 0 0 0.5rem 0;
}

.search-header p {
  color: var(--text-muted);
  margin: 0;
}

.search-form {
  display: flex;
  flex-direction: column;
  gap: 1.5rem;
}

.date-inputs {
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
  gap: 1rem;
}

.form-group {
  display: flex;
  flex-direction: column;
}

.form-group label {
  font-weight: 600;
  color: var(--text-color);
  margin-bottom: 0.5rem;
  font-size: 0.875rem;
}

.form-input,
.form-select {
  padding: 0.75rem;
  border: 2px solid var(--border-color);
  border-radius: 12px;
  background: var(--surface-color);
  color: var(--text-color);
  font-size: 1rem;
  transition: all 0.3s ease;
}

.form-input:focus,
.form-select:focus {
  outline: none;
  border-color: var(--primary-color);
  box-shadow: 0 0 0 3px rgba(102, 126, 234, 0.1);
}

.search-button {
  align-self: center;
  min-width: 200px;
}

/* Rooms Grid */
.rooms-grid {
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(350px, 1fr));
  gap: 2rem;
  margin-bottom: 2rem;
}

.room-card {
  background: var(--card-bg);
  border-radius: 20px;
  overflow: hidden;
  box-shadow: 0 10px 30px rgba(0,0,0,0.1);
  transition: transform 0.3s ease, box-shadow 0.3s ease;
  border: 1px solid var(--border-color);
}

.room-card:hover {
  transform: translateY(-5px);
  box-shadow: 0 20px 40px rgba(0,0,0,0.15);
}

.room-image {
  height: 200px;
  background: var(--primary-gradient);
  display: flex;
  align-items: center;
  justify-content: center;
  position: relative;
}

.room-placeholder {
  color: white;
  font-size: 1.5rem;
  font-weight: 600;
  text-shadow: 0 2px 4px rgba(0,0,0,0.3);
}

.location-badge {
  position: absolute;
  top: 1rem;
  right: 1rem;
  padding: 0.375rem 0.75rem;
  border-radius: 20px;
  font-size: 0.875rem;
  font-weight: 600;
  backdrop-filter: blur(10px);
  border: 1px solid rgba(255, 255, 255, 0.2);
}

.location-hotel {
  background: rgba(59, 130, 246, 0.9);
  color: white;
}

.location-cabin {
  background: rgba(34, 197, 94, 0.9);
  color: white;
}

.room-content {
  padding: 1.5rem;
}

.room-content h3 {
  margin-bottom: 0.5rem;
  color: var(--text-color);
  font-size: 1.5rem;
  font-weight: 600;
}

.room-description {
  color: var(--text-muted);
  margin-bottom: 1.5rem;
  line-height: 1.6;
}

.room-features {
  display: flex;
  gap: 1.5rem;
  margin-bottom: 1.5rem;
  flex-wrap: wrap;
}

.feature-item {
  display: flex;
  align-items: center;
  gap: 0.5rem;
  color: var(--text-color);
  font-weight: 500;
  font-size: 0.875rem;
}

.feature-icon {
  font-size: 1.2rem;
}

.availability-info {
  background: var(--surface-color);
  border-radius: 12px;
  padding: 1rem;
  margin-bottom: 1.5rem;
  border: 1px solid var(--border-color);
}

.availability-status {
  display: flex;
  flex-direction: column;
  gap: 0.25rem;
}

.available-rooms {
  color: var(--success-color, #10B981);
  font-weight: 600;
  font-size: 0.875rem;
}

.dates-info {
  color: var(--text-muted);
  font-size: 0.8rem;
}

.availability-placeholder {
  color: var(--text-muted);
  font-style: italic;
  font-size: 0.875rem;
}

.room-actions {
  display: flex;
  gap: 1rem;
  flex-wrap: wrap;
}

.book-button {
  flex: 1;
  min-width: 120px;
}

.details-button {
  flex: 1;
  min-width: 120px;
}

/* Error and Empty States */
.error-message,
.empty-state {
  background: var(--card-bg);
  border-radius: 16px;
  padding: 2rem;
  margin: 2rem auto;
  max-width: 500px;
  text-align: center;
  border: 1px solid var(--border-color);
}

.error-content,
.empty-content {
  display: flex;
  flex-direction: column;
  align-items: center;
  gap: 1rem;
}

.error-icon,
.empty-icon {
  font-size: 3rem;
}

.error-content h3,
.empty-content h3 {
  font-size: 1.25rem;
  font-weight: 600;
  color: var(--text-color);
  margin: 0;
}

.error-content p,
.empty-content p {
  color: var(--text-muted);
  margin: 0;
  line-height: 1.6;
}

/* Dark mode adjustments */
[data-theme="dark"] .room-card {
  background: var(--card-bg-dark);
  border-color: var(--border-color-dark);
}

[data-theme="dark"] .form-input,
[data-theme="dark"] .form-select {
  background: var(--surface-color-dark);
  border-color: var(--border-color-dark);
  color: var(--text-color-dark);
}

[data-theme="dark"] .availability-info {
  background: var(--surface-color-dark);
  border-color: var(--border-color-dark);
}

/* Responsive Design */
@media (max-width: 768px) {
  .container {
    padding: 0 1rem;
  }
  
  h1 {
    font-size: 2rem;
  }
  
  .subtitle {
    font-size: 1rem;
  }
  
  .date-inputs {
    grid-template-columns: 1fr;
  }
  
  .rooms-grid {
    grid-template-columns: 1fr;
    gap: 1.5rem;
  }
  
  .room-features {
    flex-direction: column;
    gap: 0.75rem;
  }
  
  .room-actions {
    flex-direction: column;
  }
  
  .search-button {
    min-width: auto;
    width: 100%;
  }
}

@media (max-width: 480px) {
  .room-content {
    padding: 1rem;
  }
  
  .search-card {
    margin: 0 -1rem;
  }
}
</style>
