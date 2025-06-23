<template>
  <div class="booking-detail-container">
    <!-- Loading State -->
    <LoadingSpinner v-if="bookingsStore.isLoading" message="Loading booking details..." overlay />
    
    <div class="container" v-if="!bookingsStore.isLoading && booking">
      <div class="booking-navigation">
        <div class="back-link" @click="goBack">
          <span class="back-icon">←</span> Back to My Bookings
        </div>
      </div>
      
      <div class="booking-detail-card">
        <div class="booking-header">
          <div class="booking-ref">
            <h1>Booking {{ booking.ref_code }}</h1>
            <div class="booking-status-badge" :class="booking.status">
              {{ getStatusLabel(booking.status) }}
            </div>
          </div>
          <div class="booking-actions" v-if="canCancelBooking(booking)">
            <GameButton variant="danger" @click="confirmCancellation">
              Cancel Booking
            </GameButton>
          </div>
        </div>
        
        <div class="booking-content">
          <div class="detail-section">
            <h3 class="section-title">Room Details</h3>
            <div class="detail-grid">
              <div class="detail-item">
                <div class="detail-label">Room Type</div>
                <div class="detail-value">{{ booking.room_type_name }}</div>
              </div>
              <div class="detail-item">
                <div class="detail-label">Room Number</div>
                <div class="detail-value">{{ booking.room_number }}</div>
              </div>
              <div class="detail-item">
                <div class="detail-label">Floor</div>
                <div class="detail-value">{{ booking.floor }}</div>
              </div>
              <div class="detail-item">
                <div class="detail-label">Location</div>
                <div class="detail-value">
                  {{ booking.location === 'hotel' ? '🏨 Hotel Building' : '🏕️ Private Cabin' }}
                </div>
              </div>
            </div>
          </div>
          
          <div class="detail-section">
            <h3 class="section-title">Stay Information</h3>
            <div class="detail-grid">
              <div class="detail-item">
                <div class="detail-label">Check-in</div>
                <div class="detail-value">{{ formatDate(booking.check_in) }}</div>
              </div>
              <div class="detail-item">
                <div class="detail-label">Check-out</div>
                <div class="detail-value">{{ formatDate(booking.check_out) }}</div>
              </div>
              <div class="detail-item">
                <div class="detail-label">Nights</div>
                <div class="detail-value">{{ booking.nights }}</div>
              </div>
              <div class="detail-item">
                <div class="detail-label">Guests</div>
                <div class="detail-value">{{ booking.guests }}</div>
              </div>
            </div>
          </div>
          
          <div class="detail-section">
            <h3 class="section-title">Payment Details</h3>
            <div class="detail-grid">
              <div class="detail-item">
                <div class="detail-label">Price per night</div>
                <div class="detail-value">€{{ booking.price_night }}</div>
              </div>
              <div class="detail-item">
                <div class="detail-label">Total amount</div>
                <div class="detail-value total-price">€{{ booking.total_amount }}</div>
              </div>
              <div class="detail-item">
                <div class="detail-label">Payment status</div>
                <div class="detail-value">Pay at hotel</div>
              </div>
              <div class="detail-item">
                <div class="detail-label">Booking created</div>
                <div class="detail-value">{{ formatDateTime(booking.created_at) }}</div>
              </div>
            </div>
          </div>
          
          <div class="detail-section" v-if="booking.special_requests">
            <h3 class="section-title">Special Requests</h3>
            <div class="special-requests">
              <p>{{ booking.special_requests }}</p>
            </div>
          </div>
          
          <div class="booking-summary">
            <div class="summary-section">
              <div class="summary-icon">📅</div>
              <div class="summary-content">
                <div class="summary-title">Your Check-in</div>
                <div class="summary-value">{{ formatDate(booking.check_in) }}</div>
                <div class="summary-note">After 3:00 PM</div>
              </div>
            </div>
            
            <div class="summary-section">
              <div class="summary-icon">🔑</div>
              <div class="summary-content">
                <div class="summary-title">Your Check-out</div>
                <div class="summary-value">{{ formatDate(booking.check_out) }}</div>
                <div class="summary-note">Before 11:00 AM</div>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>
    
    <!-- Error State -->
    <div v-if="!bookingsStore.isLoading && !booking && bookingsStore.error" class="error-container">
      <div class="error-content">
        <div class="error-icon">⚠️</div>
        <h2>Booking Not Found</h2>
        <p>{{ bookingsStore.error }}</p>
        <GameButton variant="primary" @click="goToBookings">
          View My Bookings
        </GameButton>
      </div>
    </div>
    
    <!-- Cancellation Confirmation Modal -->
    <GameModal v-model="showCancelModal" title="Cancel Booking">
      <div class="cancel-confirmation">
        <p>Are you sure you want to cancel your booking at <strong>{{ booking?.room_type_name }}</strong> 
          from <strong>{{ booking ? formatDate(booking.check_in) : '' }}</strong> to <strong>{{ booking ? formatDate(booking.check_out) : '' }}</strong>?</p>
        
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
import { ref, computed, onMounted } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import { useBookingsStore } from '@/stores/bookings'
import type { Booking } from '@/stores/bookings'
import LoadingSpinner from '@/components/LoadingSpinner.vue'
import GameButton from '@/components/GameButton.vue'
import GameModal from '@/components/GameModal.vue'

const route = useRoute()
const router = useRouter()
const bookingsStore = useBookingsStore()

// State
const showCancelModal = ref(false)
const isCancelling = ref(false)

// Computed
const booking = computed(() => bookingsStore.currentBooking)

// Functions
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

const formatDate = (dateString: string) => {
  return new Date(dateString).toLocaleDateString('en-US', {
    weekday: 'short',
    day: 'numeric',
    month: 'long',
    year: 'numeric'
  })
}

const formatDateTime = (dateTimeString: string) => {
  return new Date(dateTimeString).toLocaleString('en-US', {
    year: 'numeric',
    month: 'short',
    day: 'numeric',
    hour: '2-digit',
    minute: '2-digit'
  })
}

const loadBookingDetails = async () => {
  const bookingId = parseInt(route.params.id as string)
  if (isNaN(bookingId)) {
    bookingsStore.error = 'Invalid booking ID'
    router.push('/bookings')
    return
  }
  
  try {
    // Clear any previous errors
    bookingsStore.clearError()
    await bookingsStore.fetchBooking(bookingId)
    
    if (!bookingsStore.currentBooking) {
      console.error('Booking not found after fetching')
    } else {
      console.log('Booking loaded successfully:', bookingsStore.currentBooking)
    }
  } catch (error: any) {
    console.error('Failed to load booking details:', error)
    // The store will already handle setting the error message
  }
}

const confirmCancellation = () => {
  showCancelModal.value = true
}

const cancelBooking = async () => {
  if (!booking.value) return
  
  isCancelling.value = true
  
  try {
    // Clear any previous errors
    bookingsStore.clearError()
    
    await bookingsStore.cancelBooking(booking.value.id)
    showCancelModal.value = false
    
    // Show success message
    alert('Your booking has been successfully cancelled.')
    router.push('/bookings')
  } catch (error: any) {
    console.error('Failed to cancel booking:', error)
    alert(error.response?.data?.message || 'Failed to cancel booking. Please try again.')
  } finally {
    isCancelling.value = false
  }
}

const goBack = () => {
  router.back()
}

const goToBookings = () => {
  router.push('/bookings')
}

// Initialize data on mount
onMounted(() => {
  loadBookingDetails()
})
</script>

<style scoped>
.booking-detail-container {
  min-height: 100vh;
  padding: 2rem 0;
  background-color: var(--background-color);
  background-image: linear-gradient(120deg, #fdfbfb 0%, #ebedee 100%);
}

.container {
  max-width: 1000px;
  margin: 0 auto;
  padding: 0 2rem;
}

.booking-navigation {
  margin-bottom: 2rem;
}

.back-link {
  display: inline-flex;
  align-items: center;
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

.booking-detail-card {
  background: var(--card-bg);
  border-radius: 16px;
  overflow: hidden;
  box-shadow: 0 8px 24px rgba(0, 0, 0, 0.1);
  border: 2px solid var(--border-color);
}

.booking-header {
  padding: 1.5rem 2rem;
  display: flex;
  align-items: center;
  justify-content: space-between;
  border-bottom: 2px solid var(--border-color);
  background: var(--primary-gradient);
  color: white;
}

.booking-ref {
  display: flex;
  align-items: center;
  gap: 1rem;
}

.booking-ref h1 {
  margin: 0;
  font-size: 1.8rem;
}

.booking-status-badge {
  display: inline-block;
  padding: 0.4rem 1rem;
  border-radius: 50px;
  font-weight: 600;
  font-size: 0.9rem;
  color: white;
  background: rgba(255, 255, 255, 0.2);
}

.booking-status-badge.confirmed {
  background: #38b2ac;
}

.booking-status-badge.checked_in {
  background: #4299e1;
}

.booking-status-badge.checked_out {
  background: #805ad5;
}

.booking-status-badge.cancelled {
  background: #e53e3e;
}

.booking-content {
  padding: 2rem;
}

.detail-section {
  margin-bottom: 2rem;
  padding-bottom: 2rem;
  border-bottom: 2px solid var(--border-color);
}

.detail-section:last-child {
  margin-bottom: 0;
  padding-bottom: 0;
  border-bottom: none;
}

.section-title {
  margin-top: 0;
  margin-bottom: 1.5rem;
  color: var(--text-color);
  font-size: 1.4rem;
  font-weight: 600;
  position: relative;
  padding-left: 1rem;
}

.section-title::before {
  content: "";
  position: absolute;
  left: 0;
  top: 0.25rem;
  bottom: 0.25rem;
  width: 4px;
  background-color: var(--primary-color);
  border-radius: 2px;
}

.detail-grid {
  display: grid;
  grid-template-columns: repeat(auto-fill, minmax(200px, 1fr));
  gap: 1.5rem;
}

.detail-item {
  display: flex;
  flex-direction: column;
}

.detail-label {
  font-size: 0.9rem;
  font-weight: 600;
  color: var(--text-muted);
  margin-bottom: 0.5rem;
}

.detail-value {
  font-size: 1.1rem;
  color: var(--text-color);
}

.total-price {
  font-weight: 700;
  color: var(--primary-color);
  font-size: 1.3rem;
}

.special-requests {
  background: var(--surface-color);
  padding: 1.5rem;
  border-radius: 12px;
  border: 1px solid var(--border-color);
}

.special-requests p {
  margin: 0;
  color: var(--text-secondary);
  line-height: 1.6;
}

.booking-summary {
  display: flex;
  flex-wrap: wrap;
  gap: 2rem;
  margin-top: 1rem;
}

.summary-section {
  flex: 1;
  min-width: 200px;
  display: flex;
  gap: 1rem;
  padding: 1.5rem;
  background: var(--surface-color);
  border-radius: 12px;
  border: 1px solid var(--border-color);
}

.summary-icon {
  font-size: 2rem;
}

.summary-content {
  flex: 1;
}

.summary-title {
  font-weight: 600;
  color: var(--text-color);
  margin-bottom: 0.5rem;
}

.summary-value {
  font-size: 1.1rem;
  color: var(--text-color);
  margin-bottom: 0.25rem;
}

.summary-note {
  font-size: 0.9rem;
  color: var(--text-muted);
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

/* Cancel Confirmation */
.cancel-confirmation {
  padding: 1rem 0;
}

.cancel-confirmation p {
  margin-bottom: 1.5rem;
  line-height: 1.6;
  color: var(--text-color);
}

.warning-text {
  background: var(--warning-bg);
  padding: 1rem;
  border-radius: 8px;
  color: var(--warning-text);
  border-left: 4px solid var(--warning-color);
}

.confirmation-actions {
  display: flex;
  justify-content: space-between;
  gap: 1rem;
  margin-top: 2rem;
}

/* Responsive */
@media (max-width: 768px) {
  .booking-header {
    flex-direction: column;
    align-items: flex-start;
    gap: 1rem;
  }
  
  .booking-ref {
    flex-direction: column;
    align-items: flex-start;
    gap: 0.5rem;
  }
  
  .booking-actions {
    width: 100%;
  }
  
  .booking-actions button {
    width: 100%;
  }
  
  .detail-grid {
    grid-template-columns: 1fr;
  }
  
  .booking-summary {
    flex-direction: column;
  }
  
  .confirmation-actions {
    flex-direction: column-reverse;
  }
}

/* Beach theme */
.booking-detail-container {
  background-image: linear-gradient(120deg, #fdfbfb 0%, #ebedee 100%);
}

.booking-header {
  background: linear-gradient(135deg, #4facfe 0%, #00f2fe 100%);
}

/* Beach vibe color overrides */
:root {
  --primary-color: #4facfe;
  --primary-gradient: linear-gradient(135deg, #4facfe 0%, #00f2fe 100%);
  --success-color: #38b2ac;
  --warning-bg: rgba(247, 140, 108, 0.1);
  --warning-text: #c05621;
  --warning-color: #ed8936;
}

[data-theme="dark"] {
  --primary-color: #4facfe;
  --primary-gradient: linear-gradient(135deg, #243949 0%, #517fa4 100%);
  --success-color: #38b2ac;
  --warning-bg: rgba(247, 140, 108, 0.2);
  --warning-text: #ed8936;
  --warning-color: #dd6b20;
}
</style>
