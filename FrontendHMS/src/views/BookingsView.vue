<template>  <div class="bookings-container">
    <div class="container">
      <h1>My Bookings</h1>
      
      <!-- Loading State -->
      <LoadingSpinner v-if="bookingsStore.isLoading" message="Loading your bookings..." />
        <!-- No Bookings -->
      <div v-else-if="!bookingsStore.isLoading && bookings.length === 0" class="no-bookings">
        <div class="no-bookings-content">
          <div class="empty-icon">📅</div>
          <h3>No Bookings Found</h3>
          <p>You haven't made any bookings yet.</p>
          <GameButton variant="primary" @click="browseRooms">
            Browse Rooms
          </GameButton>
        </div>
      </div>
      
      <!-- Error State -->
      <div v-else-if="bookingsStore.hasError" class="error-message">
        <div class="error-content">
          <span class="error-icon">⚠️</span>
          <p>{{ bookingsStore.error }}</p>
          <GameButton variant="secondary" size="sm" @click="loadBookings">
            Try Again
          </GameButton>
        </div>
      </div>
      
      <!-- Bookings List -->
      <div v-else class="bookings-grid">
        <GameCard 
          v-for="booking in bookings" 
          :key="booking.id" 
          clickable 
          class="booking-card"
          @click="viewBookingDetails(booking)"
        >
          <template #default>
            <!-- Booking Header -->
            <div class="booking-header" :class="`status-${booking.status}`">
              <div class="booking-ref">{{ booking.ref_code }}</div>
              <div class="booking-status-badge" :class="booking.status">
                {{ getStatusLabel(booking.status) }}
              </div>
            </div>
            
            <!-- Booking Content -->
            <div class="booking-content">
              <div class="room-info">
                <h3 class="room-type">{{ booking.room_type_name }}</h3>
                <p class="room-location">
                  {{ booking.location === 'hotel' ? '🏨 Hotel Room' : '🏕️ Cabin' }}
                </p>
              </div>
              
              <div class="booking-details">
                <div class="detail-item">
                  <span class="detail-icon">📅</span>
                  <div class="detail-content">
                    <span class="detail-label">Check-in</span>
                    <span class="detail-value">{{ formatDate(booking.check_in) }}</span>
                  </div>
                </div>
                
                <div class="detail-item">
                  <span class="detail-icon">📆</span>
                  <div class="detail-content">
                    <span class="detail-label">Check-out</span>
                    <span class="detail-value">{{ formatDate(booking.check_out) }}</span>
                  </div>
                </div>
                
                <div class="detail-item">
                  <span class="detail-icon">👥</span>
                  <div class="detail-content">
                    <span class="detail-label">Guests</span>
                    <span class="detail-value">{{ booking.guests }} {{ booking.guests === 1 ? 'person' : 'people' }}</span>
                  </div>
                </div>
                
                <div class="detail-item">
                  <span class="detail-icon">🛏️</span>
                  <div class="detail-content">
                    <span class="detail-label">Nights</span>
                    <span class="detail-value">{{ booking.nights }}</span>
                  </div>
                </div>
              </div>
              
              <div class="booking-price">
                <span class="price-label">Total</span>
                <span class="price-value">€{{ booking.total_amount }}</span>
              </div>
            </div>
            
            <!-- Booking Footer -->
            <div class="booking-footer">
              <GameButton 
                v-if="canCancelBooking(booking)" 
                variant="danger" 
                size="sm"
                @click.stop="confirmCancellation(booking)"
              >
                Cancel Booking
              </GameButton>
              <GameButton 
                variant="primary" 
                size="sm"
              >
                View Details
              </GameButton>
            </div>
          </template>
        </GameCard>
      </div>
      
      <!-- Pagination (if needed) -->
      <div v-if="bookingsStore.pagination && bookingsStore.pagination.totalPages > 1" class="pagination">
        <GameButton 
          variant="ghost" 
          size="sm" 
          :disabled="!canGoPrevious" 
          @click="previousPage"
        >
          ← Previous
        </GameButton>
        
        <div class="page-info">
          Page {{ currentPage }} of {{ bookingsStore.pagination.totalPages }}
        </div>
        
        <GameButton 
          variant="ghost" 
          size="sm" 
          :disabled="!canGoNext" 
          @click="nextPage"
        >
          Next →
        </GameButton>
      </div>
    </div>
    
    <!-- Cancellation Confirmation Modal -->
    <GameModal v-model="showCancelModal" title="Cancel Booking">
      <div v-if="bookingToCancel" class="cancel-confirmation">
        <p>Are you sure you want to cancel your booking at <strong>{{ bookingToCancel.room_type_name }}</strong> 
          from <strong>{{ formatDate(bookingToCancel.check_in) }}</strong> to <strong>{{ formatDate(bookingToCancel.check_out) }}</strong>?</p>
          
        <p class="warning-text">This action cannot be undone. Once cancelled, the room will be made available for booking by other guests.</p>
        
        <div class="confirmation-actions">
          <GameButton variant="ghost" @click="showCancelModal = false">
            Keep Booking
          </GameButton>
          <GameButton 
            variant="danger" 
            @click="cancelBooking" 
            :loading="isCancelling"
            :disabled="isCancelling"
          >
            Yes, Cancel Booking
          </GameButton>
        </div>
      </div>
    </GameModal>
  </div>
</template>

<script setup lang="ts">
import { ref, computed, onMounted, onUnmounted } from 'vue'
import { useRouter } from 'vue-router'
import { useAuthStore } from '@/stores/auth'
import { useBookingsStore } from '@/stores/bookings'
import type { Booking } from '@/stores/bookings'
import GameCard from '@/components/GameCard.vue'
import GameButton from '@/components/GameButton.vue'
import GameModal from '@/components/GameModal.vue'
import LoadingSpinner from '@/components/LoadingSpinner.vue'

const router = useRouter()
const authStore = useAuthStore()
const bookingsStore = useBookingsStore()

// State
const bookings = computed(() => bookingsStore.bookings)
const currentPage = ref(1)
const showCancelModal = ref(false)
const bookingToCancel = ref<Booking | null>(null)
const isCancelling = ref(false)

// Computed properties
const canGoPrevious = computed(() => {
  return bookingsStore.pagination?.hasPrevious || false
})

const canGoNext = computed(() => {
  return bookingsStore.pagination?.hasNext || false
})

// Initialize bookings on mount
onMounted(() => {
  // Add CSS classes to ensure scrolling works
  document.body.classList.add('bookings-page')
  document.documentElement.classList.add('bookings-page')
  
  loadBookings()
})

// Clean up when component is unmounted
onUnmounted(() => {
  // Remove CSS classes when leaving the page
  document.body.classList.remove('bookings-page')
  document.documentElement.classList.remove('bookings-page')
})

// Functions
const formatDate = (dateString: string) => {
  return new Date(dateString).toLocaleDateString('en-US', {
    year: 'numeric',
    month: 'short',
    day: 'numeric'
  })
}

// Status formatting
const getStatusLabel = (status: string) => {
  const statusMap: Record<string, string> = {
    'confirmed': 'Confirmed',
    'checked_in': 'Checked In',
    'checked_out': 'Completed',
    'cancelled': 'Cancelled'
  }
  return statusMap[status] || status
}

const canCancelBooking = (booking: Booking) => {
  // Only confirmed bookings can be cancelled
  // And we can only cancel bookings that are not in the past
  const today = new Date()
  const checkIn = new Date(booking.check_in)
  
  // Can only cancel confirmed bookings that haven't started yet
  return booking.status === 'confirmed' && checkIn > today
}

// Page actions
const loadBookings = async () => {
  try {
    // Clear any previous errors
    bookingsStore.clearError()
    
    console.log('Fetching bookings for page:', currentPage.value)
    await bookingsStore.fetchMyBookings(currentPage.value)
    console.log('Bookings loaded:', bookings.value)
  } catch (error: any) {
    console.error('Failed to load bookings:', error)
    // Log error details
    if (error.response) {
      console.error('Error response:', error.response.data)
      console.error('Status code:', error.response.status)
    }
    // Don't need to set error manually as it's handled in the store
  }
}

const viewBookingDetails = (booking: Booking) => {
  // Navigate to booking details
  router.push(`/bookings/${booking.id}`)
}

const browseRooms = () => {
  router.push('/rooms')
}

const previousPage = () => {
  if (canGoPrevious.value) {
    currentPage.value--
    loadBookings()
  }
}

const nextPage = () => {
  if (canGoNext.value) {
    currentPage.value++
    loadBookings()
  }
}

// Cancellation
const confirmCancellation = (booking: Booking) => {
  bookingToCancel.value = booking
  showCancelModal.value = true
}

const cancelBooking = async () => {
  if (!bookingToCancel.value) return
  
  isCancelling.value = true
  
  try {
    // Clear any previous errors
    bookingsStore.clearError()
    
    await bookingsStore.cancelBooking(bookingToCancel.value.id)
    showCancelModal.value = false
    // Reload the bookings list
    await loadBookings()
  } catch (error: any) {
    console.error('Failed to cancel booking:', error)
    alert(error.response?.data?.message || 'Failed to cancel booking. Please try again.')
  } finally {
    isCancelling.value = false
  }
}
</script>

<style scoped>
.bookings-container {
  min-height: 100vh;
  padding: 2rem 0;
  background: var(--background-gradient);
  overflow-y: auto;
}

/* Ensure proper scrolling */
body.bookings-page {
  overflow-y: auto !important;
  height: auto !important;
  min-height: 100vh !important;
}

html.bookings-page {
  overflow-y: auto !important;
  height: auto !important;
}

.bookings-page .app-container {
  min-height: auto !important;
  height: auto !important;
}

.bookings-page main {
  overflow-y: auto !important;
  height: auto !important;
}

.container {
  max-width: 1100px;
  margin: 0 auto;
  padding: 0 2rem;
}

h1 {
  margin-bottom: 2rem;
  color: var(--text-color);
  text-align: center;
  font-size: 2.2rem;
  font-weight: 700;
  position: relative;
  display: inline-block;
  left: 50%;
  transform: translateX(-50%);
}

h1::after {
  content: "";
  position: absolute;
  width: 60%;
  height: 4px;
  background: var(--primary-gradient);
  left: 20%;
  bottom: -10px;
  border-radius: 2px;
}

/* No Bookings State */
.no-bookings {
  text-align: center;
  padding: 3rem;
  background: var(--card-bg);
  border: 1px solid var(--border-color);
  border-radius: 12px;
  box-shadow: var(--shadow-md);
}

.no-bookings-content {
  max-width: 400px;
  margin: 0 auto;
}

.empty-icon {
  font-size: 4rem;
  margin-bottom: 1rem;
  opacity: 0.6;
}

.no-bookings h3 {
  margin-bottom: 1rem;
  color: var(--text-color);
  font-size: 1.5rem;
}

.no-bookings p {
  margin-bottom: 2rem;
  color: var(--text-secondary);
  font-size: 1rem;
}

/* Error State */
.error-message {
  text-align: center;
  padding: 2rem;
  background: var(--card-bg);
  border: 1px solid var(--danger-color);
  border-radius: 12px;
  box-shadow: var(--shadow-md);
}

.error-content {
  max-width: 400px;
  margin: 0 auto;
}

.error-icon {
  font-size: 2rem;
  margin-bottom: 1rem;
  display: block;
}

.error-message p {
  color: var(--danger-color);
  margin-bottom: 1rem;
}

/* Bookings Grid */
.bookings-grid {
  display: grid;
  grid-template-columns: repeat(auto-fill, minmax(400px, 1fr));
  gap: 1.5rem;
  margin-bottom: 2rem;
}

.booking-card {
  background: var(--card-bg);
  border: 1px solid var(--border-color);
  border-radius: 12px;
  overflow: hidden;
  transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
  box-shadow: var(--shadow-md);
}

.booking-card:hover {
  transform: translateY(-4px);
  box-shadow: var(--shadow-lg);
  border-color: var(--primary-color);
}

/* Booking Header */
.booking-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
  padding: 1.5rem 1.5rem 1rem;
  border-bottom: 1px solid var(--border-color);
}

.booking-ref {
  font-family: 'Inter', monospace;
  font-weight: 600;
  color: var(--text-color);
  font-size: 1.1rem;
}

.booking-status-badge {
  padding: 0.25rem 0.75rem;
  border-radius: 20px;
  font-size: 0.85rem;
  font-weight: 500;
  text-transform: capitalize;
}

.booking-status-badge.confirmed {
  background: var(--success-color);
  color: white;
}

.booking-status-badge.checked_in {
  background: var(--primary-color);
  color: white;
}

.booking-status-badge.checked_out {
  background: var(--text-muted);
  color: white;
}

.booking-status-badge.cancelled {
  background: var(--danger-color);
  color: white;
}

/* Booking Content */
.booking-content {
  padding: 1.5rem;
}

.room-info {
  margin-bottom: 1.5rem;
}

.room-type {
  font-size: 1.25rem;
  font-weight: 600;
  color: var(--text-color);
  margin: 0 0 0.5rem 0;
}

.room-location {
  color: var(--text-secondary);
  margin: 0;
  font-size: 0.95rem;
}

/* Booking Details */
.booking-details {
  display: grid;
  grid-template-columns: repeat(2, 1fr);
  gap: 1rem;
  margin-bottom: 1.5rem;
}

.detail-item {
  display: flex;
  align-items: center;
  gap: 0.75rem;
}

.detail-icon {
  font-size: 1.1rem;
  opacity: 0.7;
}

.detail-content {
  display: flex;
  flex-direction: column;
  gap: 0.25rem;
}

.detail-label {
  font-size: 0.8rem;
  color: var(--text-muted);
  font-weight: 500;
  text-transform: uppercase;
  letter-spacing: 0.5px;
}

.detail-value {
  font-size: 0.95rem;
  color: var(--text-color);
  font-weight: 500;
}

/* Booking Price */
.booking-price {
  display: flex;
  justify-content: space-between;
  align-items: center;
  padding: 1rem;
  background: var(--primary-bg);
  border-radius: 8px;
  margin-bottom: 1rem;
}

.price-label {
  font-size: 0.9rem;
  color: var(--text-secondary);
  font-weight: 500;
}

.price-value {
  font-size: 1.25rem;
  color: var(--primary-color);
  font-weight: 700;
}

/* Booking Footer */
.booking-footer {
  display: flex;
  justify-content: space-between;
  align-items: center;
  padding: 0 1.5rem 1.5rem;
  gap: 1rem;
}

/* Pagination */
.pagination {
  display: flex;
  justify-content: center;
  align-items: center;
  gap: 1rem;
  padding: 2rem 0;
}

.page-info {
  color: var(--text-secondary);
  font-weight: 500;
  min-width: 120px;
  text-align: center;
}

/* Cancellation Modal */
.cancel-confirmation {
  padding: 1rem 0;
}

.cancel-confirmation p {
  color: var(--text-color);
  margin-bottom: 1rem;
  line-height: 1.6;
}

.warning-text {
  color: var(--warning-color);
  font-size: 0.9rem;
  background: var(--warning-light);
  padding: 1rem;
  border-radius: 8px;
  border-left: 4px solid var(--warning-color);
  opacity: 0.9;
}

.confirmation-actions {
  display: flex;
  justify-content: flex-end;
  gap: 1rem;
  margin-top: 2rem;
}

/* Responsive Design */
@media (max-width: 768px) {
  .container {
    padding: 0 1rem;
  }
  
  .bookings-grid {
    grid-template-columns: 1fr;
    gap: 1rem;
  }
  
  .booking-header {
    flex-direction: column;
    align-items: flex-start;
    gap: 0.75rem;
  }
  
  .booking-details {
    grid-template-columns: 1fr;
    gap: 0.75rem;
  }
  
  .booking-footer {
    flex-direction: column;
    gap: 0.75rem;
  }
  
  .booking-footer > * {
    width: 100%;
  }
  
  .confirmation-actions {
    flex-direction: column;
    gap: 0.75rem;
  }
  
  .pagination {
    flex-direction: column;
    gap: 0.75rem;
  }
}

@media (max-width: 480px) {
  .bookings-container {
    padding: 1rem 0;
  }
  
  h1 {
    font-size: 1.8rem;
  }
  
  .booking-content {
    padding: 1rem;
  }
  
  .booking-header {
    padding: 1rem 1rem 0.75rem;
  }
  
  .booking-footer {
    padding: 0 1rem 1rem;
  }
}
</style>
