<template>
  <div class="room-details-container">
    <!-- Loading State -->
    <LoadingSpinner v-if="isLoading" message="Loading room details..." overlay />

    <div class="container" v-if="!isLoading && roomType">
      <div class="back-link" @click="goBack">
        <span class="back-icon">←</span> Back to All Rooms
      </div>

      <div class="room-details-card">
        <div class="room-header">
          <h1>{{ roomType.name }}</h1>
          <div class="location-badge" :class="`location-${roomType.location}`">
            {{ roomType.location === 'hotel' ? '🏨' : '🏕️' }} {{ roomType.location }}
          </div>
        </div>

        <div class="room-image">
          <div class="room-placeholder" :class="`theme-${roomType.location}`">
            {{ roomType.name }}
          </div>
        </div>
        
        <div class="room-features">
          <div class="feature-item">
            <span class="feature-icon">👥</span>
            <span>{{ roomType.capacity }} {{ roomType.capacity === 1 ? 'guest' : 'guests' }}</span>
          </div>
          <div class="feature-item">
            <span class="feature-icon">💰</span>
            <span>€{{ roomType.price_night }} per night</span>
          </div>
          <div class="feature-item">
            <span class="feature-icon">🏠</span>
            <span>{{ roomType.location === 'hotel' ? 'Hotel Building' : 'Private Cabin' }}</span>
          </div>
        </div>

        <div class="room-description">
          <h2>About this room</h2>
          <p>{{ roomDescription }}</p>
          <ul class="amenities-list">
            <li v-for="(amenity, index) in amenities" :key="index">
              <span class="amenity-icon">✓</span> {{ amenity }}
            </li>
          </ul>
        </div>
        
        <!-- Availability Search -->
        <div class="booking-form-container">
          <h2>Check Availability</h2>
          
          <form @submit.prevent="checkAvailability" class="booking-form">
            <div class="form-grid">
              <div class="form-group">
                <label for="checkIn" class="form-label">Check In</label>
                <input
                  type="date"
                  id="checkIn"
                  v-model="searchForm.checkIn"
                  :min="today"
                  class="form-input"
                  required
                />
              </div>
              
              <div class="form-group">
                <label for="checkOut" class="form-label">Check Out</label>
                <input
                  type="date"
                  id="checkOut"
                  v-model="searchForm.checkOut"
                  :min="minCheckOut"
                  class="form-input"
                  required
                />
              </div>
              
              <div class="form-group">
                <label for="guests" class="form-label">Guests</label>
                <select 
                  id="guests" 
                  v-model="searchForm.guests" 
                  class="form-select"
                  required
                >
                  <option 
                    v-for="n in roomType.capacity" 
                    :key="n" 
                    :value="n.toString()"
                  >
                    {{ n }} {{ n === 1 ? 'Guest' : 'Guests' }}
                  </option>
                </select>
              </div>
              
              <div class="form-submit">
                <GameButton 
                  type="submit" 
                  variant="primary" 
                  :loading="isCheckingAvailability"
                  block
                >
                  Check Availability
                </GameButton>
              </div>
            </div>
          </form>
          
          <!-- Availability Results -->
          <div v-if="showResults" class="availability-results">
            <h3 v-if="availableRooms.length > 0">Available Options</h3>
            <h3 v-else class="no-availability-title">No Rooms Available</h3>
            
            <template v-if="availableRooms.length > 0">
              <div v-for="room in availableRooms" :key="room.room_id" class="available-room">
                <div class="room-info">
                  <div class="room-number">Room {{ room.room_number }}</div>
                  <div class="room-price">Total: <span class="price">€{{ room.total_price }}</span></div>
                </div>
                <div class="room-book">
                  <GameButton v-if="authStore.isAuthenticated" variant="primary" @click="selectRoom(room)">
                    Book Now
                  </GameButton>
                  <GameButton v-else variant="primary" @click="redirectToLogin">
                    Login to Book
                  </GameButton>
                </div>
              </div>
            </template>
            
            <div v-if="availableRooms.length === 0 && !isCheckingAvailability" class="no-availability">
              <p>Sorry, there are no rooms available for your selected dates.</p>
              <p>Please try different dates or check our other room types.</p>
              <GameButton variant="secondary" @click="goToRooms">
                View All Rooms
              </GameButton>
            </div>
          </div>
        </div>
      </div>
    </div>
    
    <!-- Error State -->
    <div v-if="!isLoading && !roomType && error" class="error-container">
      <div class="error-content">
        <div class="error-icon">⚠️</div>
        <h2>Room Not Found</h2>
        <p>{{ error }}</p>
        <GameButton variant="primary" @click="goToRooms">
          View Available Rooms
        </GameButton>
      </div>
    </div>
    
    <!-- Booking Confirmation Modal -->
    <GameModal v-model="showConfirmModal" title="Confirm Your Booking">
      <div v-if="selectedRoom" class="booking-confirmation">
        <h3>Booking Summary</h3>
        
        <div class="confirmation-details">
          <div class="detail-row">
            <div class="detail-label">Room Type:</div>
            <div class="detail-value">{{ roomType?.name }}</div>
          </div>
          
          <div class="detail-row">
            <div class="detail-label">Room Number:</div>
            <div class="detail-value">{{ selectedRoom.room_number }}</div>
          </div>
          
          <div class="detail-row">
            <div class="detail-label">Check In:</div>
            <div class="detail-value">{{ formatDate(searchForm.checkIn) }}</div>
          </div>
          
          <div class="detail-row">
            <div class="detail-label">Check Out:</div>
            <div class="detail-value">{{ formatDate(searchForm.checkOut) }}</div>
          </div>
          
          <div class="detail-row">
            <div class="detail-label">Guests:</div>
            <div class="detail-value">{{ searchForm.guests }}</div>
          </div>
          
          <div class="detail-row">
            <div class="detail-label">Total Cost:</div>
            <div class="detail-value price">€{{ selectedRoom.total_price }}</div>
          </div>
        </div>
        
        <div class="special-requests">
          <label for="specialRequests" class="form-label">Special Requests (optional):</label>
          <textarea
            id="specialRequests"
            v-model="specialRequests"
            class="form-textarea"
            rows="3"
            maxlength="500"
            placeholder="Let us know if you have any special requests or needs..."
          ></textarea>
        </div>
        
        <div class="confirmation-actions">
          <GameButton variant="secondary" @click="showConfirmModal = false">
            Cancel
          </GameButton>
          <GameButton 
            variant="primary" 
            @click="confirmBooking" 
            :loading="isBooking"
            :disabled="isBooking"
          >
            Confirm Booking
          </GameButton>
        </div>
      </div>
    </GameModal>
    
    <!-- Success Modal -->
    <GameModal v-model="showSuccessModal" title="Booking Confirmed!">
      <div class="booking-success">
        <div class="success-icon">✓</div>
        <h3>Thank You for Your Booking!</h3>
        
        <div class="success-details">
          <p>Your booking has been confirmed with reference code: <strong>{{ bookingRefCode }}</strong></p>
          <p>A confirmation email has been sent to your registered email address.</p>
          <p>You can view and manage your booking in your account dashboard.</p>
        </div>
        
        <div class="success-actions">
          <GameButton variant="secondary" @click="goToRooms">
            Browse More Rooms
          </GameButton>
          <GameButton variant="primary" @click="viewBooking">
            View Booking
          </GameButton>
        </div>
      </div>
    </GameModal>
  </div>
</template>

<script setup lang="ts">
import { ref, computed, onMounted, reactive } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import { useRoomTypesStore } from '@/stores/roomTypes'
import { useBookingsStore } from '@/stores/bookings'
import { useAuthStore } from '@/stores/auth'
import type { RoomType } from '@/stores/roomTypes'
import type { RoomAvailability, BookingCreate } from '@/stores/bookings'
import LoadingSpinner from '@/components/LoadingSpinner.vue'
import GameButton from '@/components/GameButton.vue'
import GameModal from '@/components/GameModal.vue'

const route = useRoute()
const router = useRouter()
const roomTypesStore = useRoomTypesStore()
const bookingsStore = useBookingsStore()
const authStore = useAuthStore()

const isLoading = ref(true)
const error = ref<string | null>(null)
const roomType = ref<RoomType | null>(null)
const isCheckingAvailability = computed(() => bookingsStore.isCheckingAvailability)

// Booking and availability data
const searchForm = ref({
  checkIn: '',
  checkOut: '',
  guests: '2'
})

const selectedRoom = ref<RoomAvailability | null>(null)
const specialRequests = ref('')
const isBooking = ref(false)
const bookingRefCode = ref('')

// Modal states
const showResults = ref(false)
const showConfirmModal = ref(false)
const showSuccessModal = ref(false)

// Available rooms after checking availability
const availableRooms = computed(() => bookingsStore.availableRooms)

// Computed values for date inputs
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

// Placeholder data for now (will come from API later)
const roomDescription = computed(() => {
  if (roomType.value?.location === 'hotel') {
    return 'Our stylish hotel rooms offer comfort and convenience for your stay. Each room is designed with modern amenities and tasteful decor to ensure a relaxing experience. Enjoy the stunning views and proximity to all resort facilities.'
  } else {
    return 'Experience privacy and tranquility in our standalone cabins. Each cabin features a cozy interior with rustic charm and modern comforts. Perfect for those seeking a peaceful retreat with nature just outside your door.'
  }
})

const amenities = computed(() => {
  const commonAmenities = [
    'Free Wi-Fi',
    'Air conditioning',
    'Flat-screen TV',
    'Daily housekeeping',
    'In-room safe'
  ]
  
  const hotelAmenities = [
    'Room service',
    'Minibar',
    'Premium toiletries'
  ]
  
  const cabinAmenities = [
    'Private entrance',
    'Kitchenette',
    'Patio with view'
  ]
  
  return roomType.value?.location === 'hotel'
    ? [...commonAmenities, ...hotelAmenities]
    : [...commonAmenities, ...cabinAmenities]
})

// Methods
const loadRoomType = async (id: number) => {
  isLoading.value = true
  error.value = null
  
  try {
    // Clear any previous errors
    roomTypesStore.clearError()
    await roomTypesStore.fetchRoomType(id)
    
    roomType.value = roomTypesStore.currentRoomType
    
    if (!roomType.value) {
      error.value = 'Room type not found. Please check the URL and try again.'
      console.error('Room type not found in store after fetching')
      return
    }
    
    console.log('Room type loaded:', roomType.value)
  } catch (err: any) {
    error.value = err.response?.data?.message || err.message || 'Failed to load room details'
    console.error('Error loading room type:', err)
  } finally {
    isLoading.value = false
  }
}

const checkAvailability = async () => {
  if (!roomType.value) return
  
  try {
    // Clear any previous errors
    bookingsStore.clearError()
    
    // Check availability through the store
    await bookingsStore.checkAvailability({
      check_in: searchForm.value.checkIn,
      check_out: searchForm.value.checkOut,
      guests: parseInt(searchForm.value.guests),
      room_type_id: roomType.value.id
    })
    
    // Always show results - either available rooms or no availability message
    showResults.value = true
    
    // Log the results for debugging
    console.log('Available rooms after check:', bookingsStore.availableRooms)
  } catch (err: any) {
    console.error('Error checking availability:', err)
    // Still show results even on error to display proper messaging
    showResults.value = true
    // Show error message to the user
    alert(err.response?.data?.message || 'Failed to check availability. Please try again.')
  }
}

const selectRoom = (room: RoomAvailability) => {
  selectedRoom.value = room
  showConfirmModal.value = true
}

const confirmBooking = async () => {
  if (!selectedRoom.value) {
    console.error('No room selected for booking')
    return
  }
  
  isBooking.value = true
  
  try {
    // Clear any previous errors
    bookingsStore.clearError()
    
    // Get current user's ID from auth store
    const currentUserId = authStore.user?.id
    
    if (!currentUserId) {
      console.error('User ID not found in auth store')
      alert('You need to be logged in to book a room. Please log in and try again.')
      router.push('/login')
      return
    }
    
    const bookingData: BookingCreate = {
      room_id: selectedRoom.value.room_id,
      check_in: searchForm.value.checkIn,
      check_out: searchForm.value.checkOut,
      guests: parseInt(searchForm.value.guests),
      special_requests: specialRequests.value || undefined,
      customer_id: currentUserId
    }
    
    // Validate the booking data before sending to API
    const validationError = bookingsStore.validateBookingData(bookingData)
    if (validationError) {
      alert(validationError)
      return
    }
    
    // Double check if the room is still available before booking
    try {
      await bookingsStore.checkAvailability({
        check_in: searchForm.value.checkIn,
        check_out: searchForm.value.checkOut,
        guests: parseInt(searchForm.value.guests),
        room_type_id: roomType.value?.id
      })
      
      // Check if our specific room is still in the available rooms list
      const isStillAvailable = bookingsStore.availableRooms.some(
        room => room.room_id === selectedRoom.value?.room_id
      )
      
      if (!isStillAvailable) {
        alert('This room is no longer available. Please select another room or different dates.')
        showConfirmModal.value = false
        showResults.value = true
        return
      }
    } catch (error) {
      console.error('Error re-checking availability:', error)
      // Continue with booking attempt even if re-checking fails
    }
    
    console.log('Creating booking with data:', bookingData)
    const booking = await bookingsStore.createBooking(bookingData)
    
    if (booking && booking.ref_code) {
      bookingRefCode.value = booking.ref_code
      showConfirmModal.value = false
      showSuccessModal.value = true
      console.log('Booking created successfully:', booking)
    } else {
      console.error('Booking created but no reference code returned')
      alert('Booking was successful but we could not retrieve your booking reference. Please check your bookings page.')
      router.push('/bookings')
    }  } catch (err: any) {
    console.error('Error creating booking:', err)
    // Check for specific error types
    if (err.response?.data?.error === "Customer ID is required") {
      alert('You need to be logged in to book a room. Please log in and try again.')
      router.push('/login')
    } else if (err.response?.status === 500) {
      // Server error
      alert('There was a server error processing your booking. Our team has been notified. Please try again later.')
      console.error('Server error details:', err.response?.data)
    } else if (err.response?.status === 409) {
      // Conflict error - usually means room is no longer available
      alert('This room is no longer available for the selected dates. Please try different dates or another room.')
      showResults.value = false
      checkAvailability() // Refresh availability
    } else {
      // Other errors
      const errorMessage = err.response?.data?.error || 
                        err.response?.data?.message || 
                        'Failed to create booking. Please try again.'
      alert(`Booking error: ${errorMessage}`)
    }
  } finally {
    isBooking.value = false
  }
}

const formatDate = (dateString: string): string => {
  return new Date(dateString).toLocaleDateString('en-US', {
    weekday: 'short',
    day: 'numeric',
    month: 'short',
    year: 'numeric'
  })
}

const redirectToLogin = () => {
  router.push({ 
    name: 'login', 
    query: { 
      redirect: route.fullPath 
    }
  })
}

const goBack = () => {
  router.back()
}

const goToRooms = () => {
  router.push({ name: 'rooms' })
}

const viewBooking = () => {
  router.push({ name: 'bookings' })
}

// Initialize data on mount
onMounted(async () => {
  const roomTypeId = parseInt(route.params.id as string)
  
  if (isNaN(roomTypeId)) {
    error.value = 'Invalid room type ID'
    isLoading.value = false
    return
  }
  
  await loadRoomType(roomTypeId)
  
  // Set initial guests to room capacity or 2, whichever is smaller
  if (roomType.value) {
    const capacity = Math.min(roomType.value.capacity, 4)
    searchForm.value.guests = Math.min(capacity, 2).toString()
    
    // Set form values from query parameters if they exist
    if (route.query.checkIn) {
      searchForm.value.checkIn = route.query.checkIn as string
    }
    
    if (route.query.checkOut) {
      searchForm.value.checkOut = route.query.checkOut as string
    }
    
    if (route.query.guests) {
      const guests = parseInt(route.query.guests as string)
      if (!isNaN(guests) && guests <= roomType.value.capacity) {
        searchForm.value.guests = guests.toString()
      }
    }
    
    // If we have check-in and check-out dates, automatically check availability
    if (searchForm.value.checkIn && searchForm.value.checkOut) {
      await checkAvailability()
    }
  }
})
</script>

<style scoped>
.room-details-container {
  min-height: 100vh;
  padding: 2rem 0;
  background-color: var(--background-color);
}

.container {
  max-width: 1100px;
  margin: 0 auto;
  padding: 0 1.5rem;
}

.back-link {
  display: inline-flex;
  align-items: center;
  margin-bottom: 1.5rem;
  color: var(--primary-color);
  cursor: pointer;
  font-weight: 500;
  transition: transform 0.2s ease;
}

.back-link:hover {
  transform: translateX(-4px);
}

.back-icon {
  margin-right: 0.5rem;
  font-size: 1.2rem;
}

.room-details-card {
  background: var(--card-bg);
  border-radius: 16px;
  overflow: hidden;
  box-shadow: 0 8px 24px rgba(0, 0, 0, 0.1);
  border: 2px solid var(--border-color);
}

.room-header {
  padding: 1.5rem 2rem;
  display: flex;
  align-items: center;
  justify-content: space-between;
  border-bottom: 2px solid var(--border-color);
  background: var(--surface-color);
}

.room-header h1 {
  margin: 0;
  font-size: 1.8rem;
  color: var(--text-color);
}

.location-badge {
  display: inline-flex;
  align-items: center;
  padding: 0.4rem 1rem;
  border-radius: 50px;
  font-weight: 500;
  font-size: 0.9rem;
}

.location-hotel {
  background: linear-gradient(135deg, #56CCF2, #3490dc);
  color: white;
}

.location-cabin {
  background: linear-gradient(135deg, #11998e, #38ef7d);
  color: white;
}

.room-image {
  width: 100%;
  height: 400px;
  position: relative;
  overflow: hidden;
}

.room-placeholder {
  width: 100%;
  height: 100%;
  display: flex;
  align-items: center;
  justify-content: center;
  font-size: 2rem;
  font-weight: bold;
  color: white;
  text-shadow: 0 2px 4px rgba(0, 0, 0, 0.3);
}

.theme-hotel {
  background: linear-gradient(135deg, #56CCF2, #3490dc);
}

.theme-cabin {
  background: linear-gradient(135deg, #11998e, #38ef7d);
}

.room-features {
  display: flex;
  padding: 1.5rem 2rem;
  border-bottom: 2px solid var(--border-color);
  background: var(--surface-color);
  flex-wrap: wrap;
  gap: 1.5rem;
}

.feature-item {
  display: flex;
  align-items: center;
  font-size: 1rem;
  color: var(--text-color);
}

.feature-icon {
  margin-right: 0.5rem;
  font-size: 1.2rem;
}

.room-description {
  padding: 2rem;
  border-bottom: 2px solid var(--border-color);
}

.room-description h2 {
  margin-top: 0;
  margin-bottom: 1rem;
  color: var(--text-color);
  font-size: 1.5rem;
}

.room-description p {
  margin-bottom: 1.5rem;
  line-height: 1.6;
  color: var(--text-secondary);
}

.amenities-list {
  display: grid;
  grid-template-columns: repeat(auto-fill, minmax(250px, 1fr));
  gap: 0.75rem;
  list-style: none;
  padding: 0;
  margin: 0;
}

.amenities-list li {
  display: flex;
  align-items: center;
  color: var(--text-secondary);
}

.amenity-icon {
  margin-right: 0.5rem;
  color: var(--primary-color);
  font-weight: bold;
}

.booking-form-container {
  padding: 2rem;
}

.booking-form-container h2 {
  margin-top: 0;
  margin-bottom: 1.5rem;
  color: var(--text-color);
  font-size: 1.5rem;
}

.booking-form {
  margin-bottom: 2rem;
}

.form-grid {
  display: grid;
  grid-template-columns: repeat(auto-fill, minmax(200px, 1fr));
  gap: 1.5rem;
}

.form-group {
  position: relative;
}

.form-label {
  display: block;
  margin-bottom: 0.5rem;
  color: var(--text-color);
  font-weight: 500;
}

.form-input,
.form-select {
  width: 100%;
  padding: 0.75rem 1rem;
  border-radius: 8px;
  border: 2px solid var(--border-color);
  background: var(--input-bg);
  color: var(--text-color);
  font-size: 1rem;
  transition: border-color 0.2s ease, box-shadow 0.2s ease;
}

.form-input:focus,
.form-select:focus {
  border-color: var(--primary-color);
  box-shadow: 0 0 0 2px rgba(102, 126, 234, 0.25);
  outline: none;
}

.form-submit {
  grid-column: 1 / -1;
  margin-top: 0.5rem;
}

.availability-results {
  margin-top: 2rem;
}

.availability-results h3 {
  margin-bottom: 1rem;
  color: var(--text-color);
}

.no-availability-title {
  color: var(--error-color);
}

.available-room {
  display: flex;
  align-items: center;
  justify-content: space-between;
  padding: 1.25rem;
  border: 2px solid var(--border-color);
  border-radius: 12px;
  margin-bottom: 1rem;
  background: var(--surface-color);
  transition: transform 0.2s ease, box-shadow 0.2s ease;
}

.available-room:hover {
  transform: translateY(-4px);
  box-shadow: 0 10px 20px rgba(0, 0, 0, 0.1);
  border-color: var(--primary-color);
}

.room-info {
  display: flex;
  flex-direction: column;
  gap: 0.5rem;
}

.room-number {
  font-weight: 600;
  font-size: 1.1rem;
  color: var(--text-color);
}

.room-price {
  color: var(--text-secondary);
  font-size: 0.95rem;
}

.price {
  font-weight: 600;
  color: var(--primary-color);
  font-size: 1.1rem;
}

.no-availability {
  background: var(--warning-bg);
  padding: 1.5rem;
  border-radius: 12px;
  text-align: center;
}

.no-availability p {
  margin-bottom: 1rem;
  color: var(--warning-text);
}

.error-container {
  height: 70vh;
  display: flex;
  align-items: center;
  justify-content: center;
  padding: 2rem;
}

.error-content {
  text-align: center;
  max-width: 500px;
  padding: 2rem;
  background: var(--card-bg);
  border-radius: 16px;
  box-shadow: 0 8px 24px rgba(0, 0, 0, 0.1);
  border: 2px solid var(--error-color);
}

.error-icon {
  font-size: 3rem;
  margin-bottom: 1rem;
}

.error-content h2 {
  margin-bottom: 1rem;
  color: var(--error-color);
}

.error-content p {
  margin-bottom: 1.5rem;
  color: var(--text-secondary);
}

/* Booking Confirmation Modal */
.booking-confirmation {
  padding: 1rem 0;
}

.booking-confirmation h3 {
  margin-bottom: 1.5rem;
  color: var(--text-color);
  text-align: center;
}

.confirmation-details {
  margin-bottom: 1.5rem;
}

.detail-row {
  display: flex;
  margin-bottom: 0.75rem;
  padding-bottom: 0.75rem;
  border-bottom: 1px solid var(--border-color);
}

.detail-row:last-child {
  border-bottom: none;
  padding-bottom: 0;
}

.detail-label {
  width: 40%;
  font-weight: 500;
  color: var(--text-color);
}

.detail-value {
  width: 60%;
  color: var(--text-secondary);
}

.detail-value.price {
  font-weight: 600;
  color: var(--primary-color);
}

.special-requests {
  margin-bottom: 1.5rem;
}

.form-textarea {
  width: 100%;
  padding: 0.75rem 1rem;
  border-radius: 8px;
  border: 2px solid var(--border-color);
  background: var(--input-bg);
  color: var(--text-color);
  font-size: 1rem;
  resize: vertical;
  min-height: 100px;
  transition: border-color 0.2s ease, box-shadow 0.2s ease;
}

.form-textarea:focus {
  border-color: var(--primary-color);
  box-shadow: 0 0 0 2px rgba(102, 126, 234, 0.25);
  outline: none;
}

.confirmation-actions {
  display: flex;
  justify-content: space-between;
  gap: 1rem;
}

/* Success Modal */
.booking-success {
  text-align: center;
  padding: 1rem 0;
}

.success-icon {
  font-size: 3rem;
  color: var(--success-color);
  background: rgba(72, 187, 120, 0.1);
  width: 80px;
  height: 80px;
  border-radius: 50%;
  display: flex;
  align-items: center;
  justify-content: center;
  margin: 0 auto 1.5rem;
}

.booking-success h3 {
  margin-bottom: 1.5rem;
  color: var(--text-color);
}

.success-details {
  margin-bottom: 2rem;
}

.success-details p {
  margin-bottom: 0.75rem;
  color: var(--text-secondary);
}

.success-actions {
  display: flex;
  justify-content: center;
  gap: 1rem;
}

/* Dark mode adjustments */
[data-theme="dark"] .room-details-card {
  box-shadow: 0 8px 24px rgba(0, 0, 0, 0.25);
}

[data-theme="dark"] .available-room:hover {
  box-shadow: 0 10px 20px rgba(0, 0, 0, 0.3);
}

/* Responsive */
@media (max-width: 768px) {
  .room-header {
    flex-direction: column;
    align-items: flex-start;
    gap: 1rem;
  }
  
  .room-image {
    height: 250px;
  }
  
  .amenities-list {
    grid-template-columns: repeat(auto-fill, minmax(200px, 1fr));
  }
  
  .form-grid {
    grid-template-columns: 1fr;
  }
  
  .available-room {
    flex-direction: column;
    align-items: flex-start;
    gap: 1rem;
  }
  
  .room-book {
    width: 100%;
  }
  
  .detail-row {
    flex-direction: column;
  }
  
  .detail-label,
  .detail-value {
    width: 100%;
  }
  
  .detail-label {
    margin-bottom: 0.25rem;
  }
  
  .confirmation-actions,
  .success-actions {
    flex-direction: column-reverse;
  }
}

/* Beach theme - Light mode only */
[data-theme="light"] .room-details-container {
  background-color: #f7f9fc;
  background-image: linear-gradient(120deg, #fdfbfb 0%, #ebedee 100%);
}

/* Dark mode background */
[data-theme="dark"] .room-details-container {
  background-color: var(--background-color);
  background-image: none;
}

[data-theme="light"] .room-header {
  background: linear-gradient(135deg, #c2e9fb 0%, #a1c4fd 100%);
}

[data-theme="dark"] .room-header {
  background: var(--surface-color);
}

.theme-hotel {
  background: linear-gradient(to right, #4facfe 0%, #00f2fe 100%);
}

.theme-cabin {
  background: linear-gradient(to right, #00b09b, #96c93d);
}

/* Beach vibe color overrides */
:root {
  --primary-color: #4facfe;
  --primary-gradient: linear-gradient(135deg, #c2e9fb 0%, #a1c4fd 100%);
  --success-color: #38b2ac;
  --surface-color: #ffffff;
}

[data-theme="dark"] {
  --primary-color: #4facfe;
  --primary-gradient: linear-gradient(135deg, #243949 0%, #517fa4 100%);
  --success-color: #38b2ac;
  --surface-color: #2d3748;
}
</style>
