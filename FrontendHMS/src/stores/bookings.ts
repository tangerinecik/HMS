import { defineStore } from 'pinia'
import { bookingsAPI } from '@/services/api'

export interface Booking {
  id: number
  ref_code: string
  customer_id: number
  room_id: number
  status: 'confirmed' | 'checked_in' | 'checked_out' | 'cancelled'
  check_in: string
  check_out: string
  guests: number
  nights: number
  total_amount: number
  special_requests?: string
  created_at: string
  updated_at: string
  // Joined data
  room_number?: string
  floor?: number
  room_type_name?: string
  room_capacity?: number
  price_night?: number
  location?: 'cabin' | 'hotel'
  first_name?: string
  last_name?: string
  email?: string
  phone?: string
}

export interface BookingCreate {
  room_id: number
  check_in: string
  check_out: string
  guests: number
  special_requests?: string
  customer_id?: number
}

export interface AvailabilityRequest {
  check_in: string
  check_out: string
  guests: number
  room_type_id?: number
}

export interface RoomAvailability {
  room_id: number
  room_number: string
  room_type_id: number
  room_type_name: string
  capacity: number
  price_night: number
  location: 'cabin' | 'hotel'
  is_available: boolean
  total_price: number
}

export interface Pagination {
  page: number
  perPage: number
  totalPages: number
  totalItems: number
  hasNext: boolean
  hasPrevious: boolean
}

export const useBookingsStore = defineStore('bookings', {
  state: () => ({
    bookings: [] as Booking[],
    currentBooking: null as Booking | null,
    availableRooms: [] as RoomAvailability[],
    pagination: null as Pagination | null,
    isLoading: false,
    isCheckingAvailability: false,
    error: null as string | null,
  }),
  
  getters: {
    getBookingById: (state) => (id: number) => 
      state.bookings.find(booking => booking.id === id),
      
    getBookingByRefCode: (state) => (refCode: string) => 
      state.bookings.find(booking => booking.ref_code === refCode),
    
    hasAvailableRooms: (state) => state.availableRooms.length > 0,
    
    totalBookings: (state) => state.pagination?.totalItems || 0,
    
    hasError: (state) => !!state.error
  },
  
  actions: {
    clearError() {
      this.error = null
    },      async fetchMyBookings(page = 1, limit = 10) {
      this.isLoading = true
      this.error = null
      
      try {
        console.log(`Fetching bookings for page ${page} with limit ${limit}`)
        const response = await bookingsAPI.getMyBookings({ page, limit })
        console.log('Response received:', response)
        
        // The API returns {bookings: [...], pagination: {...}} format
        if (!response.data || !Array.isArray(response.data.bookings)) {
          console.error('Invalid response format:', response.data)
          this.bookings = []
          this.error = 'Invalid booking data received from server'
          return []
        }
        
        this.bookings = response.data.bookings
        this.pagination = response.data.pagination
        console.log(`Received ${this.bookings.length} bookings`)
        return this.bookings
      } catch (error: any) {
        console.error('Error in fetchMyBookings:', error)
        if (error.response) {
          console.error('Response data:', error.response.data)
          console.error('Status:', error.response.status)
        }
        this.bookings = []
        this.error = error.response?.data?.message || 'Failed to fetch bookings'
        throw error
      } finally {
        this.isLoading = false
      }
    },
    
    async fetchBooking(id: number) {
      this.isLoading = true
      this.error = null
      
      try {
        const response = await bookingsAPI.getById(id)
        this.currentBooking = response.data
        return this.currentBooking
      } catch (error: any) {
        this.error = error.response?.data?.message || 'Failed to fetch booking details'
        throw error
      } finally {
        this.isLoading = false
      }
    },

    async fetchBookingByRefCode(refCode: string) {
      this.isLoading = true
      this.error = null
      
      try {
        const response = await bookingsAPI.getByRefCode(refCode)
        this.currentBooking = response.data
        return this.currentBooking
      } catch (error: any) {
        this.error = error.response?.data?.message || 'Failed to fetch booking details'
        throw error
      } finally {
        this.isLoading = false
      }
    },
      async checkAvailability(params: AvailabilityRequest) {
      this.isCheckingAvailability = true
      this.error = null
      
      try {
        const response = await bookingsAPI.checkAvailability(
          params.check_in, 
          params.check_out,
          params.guests
        )
        
        // Process the data from API response
        // API might return data in available_rooms or another field
        let availableRoomsData = response.data.available_rooms || response.data.data || [];
        
        // Map the API response to our expected format
        this.availableRooms = availableRoomsData.map((room: any) => ({
          room_id: room.id,
          room_number: room.number,
          room_type_id: room.room_type_id,
          room_type_name: room.room_type_name,
          capacity: room.capacity,
          price_night: room.price_night,
          location: room.location,
          is_available: true,
          // Calculate total price based on check-in/check-out dates
          total_price: this.calculateTotalPrice(room.price_night, params.check_in, params.check_out)
        }));
        
        // If room_type_id is provided, filter results
        if (params.room_type_id) {
          this.availableRooms = this.availableRooms.filter(
            room => room.room_type_id === params.room_type_id
          )
        }
        
        console.log('Available rooms:', this.availableRooms);
        return this.availableRooms
      } catch (error: any) {
        this.error = error.response?.data?.message || 'Failed to check availability'
        console.error('Error checking availability:', error);
        throw error
      } finally {
        this.isCheckingAvailability = false
      }
    },
    
    // Helper method to calculate total price
    calculateTotalPrice(pricePerNight: number, checkIn: string, checkOut: string): number {
      const startDate = new Date(checkIn);
      const endDate = new Date(checkOut);
      const nights = Math.max(1, Math.floor((endDate.getTime() - startDate.getTime()) / (1000 * 60 * 60 * 24)));
      return pricePerNight * nights;
    },      async createBooking(bookingData: BookingCreate) {
      this.isLoading = true
      this.error = null
      
      try {
        // Validate booking data before sending to API
        const validationError = this.validateBookingData(bookingData)
        if (validationError) {
          this.error = validationError
          throw new Error(validationError)
        }
        
        // Debug log the data we're sending
        console.log('Sending booking data to API:', JSON.stringify(bookingData))
        
        const response = await bookingsAPI.create(bookingData)
        console.log('Booking API response:', response)
        this.currentBooking = response.data
        return this.currentBooking
      } catch (error: any) {
        // If it's our own validation error or non-Axios error
        if (!(error && typeof error === 'object' && 'response' in error)) {
          console.error('Booking creation error (client-side):', error.message)
          throw error
        }
        
        // Handle different error formats from the backend
        if (error.response?.data?.error) {
          this.error = error.response.data.error
          console.error('Server reported error:', error.response.data.error)
        } else if (error.response?.data?.message) {
          this.error = error.response.data.message
          console.error('Server reported message:', error.response.data.message)
        } else {
          this.error = 'Failed to create booking'
          console.error('Unknown error format from server:', error.response?.data)
        }
        
        // Log the full error for debugging
        console.error('Full booking creation error:', error)
        throw error
      } finally {
        this.isLoading = false
      }
    },
    
    async cancelBooking(id: number) {
      this.isLoading = true
      this.error = null
      
      try {
        const response = await bookingsAPI.cancel(id)
        
        // Update local booking if in the list
        const bookingIndex = this.bookings.findIndex(b => b.id === id)
        if (bookingIndex !== -1) {
          this.bookings[bookingIndex] = response.data
        }
        
        // Update current booking if this is it
        if (this.currentBooking?.id === id) {
          this.currentBooking = response.data
        }
        
        return response.data
      } catch (error: any) {
        this.error = error.response?.data?.message || 'Failed to cancel booking'
        throw error
      } finally {
        this.isLoading = false
      }
    },
    
    // Helper function to validate booking data
    validateBookingData(bookingData: BookingCreate): string | null {
      if (!bookingData.room_id) {
        return 'Room ID is required'
      }
      if (!bookingData.check_in) {
        return 'Check-in date is required'
      }
      if (!bookingData.check_out) {
        return 'Check-out date is required'
      }
      if (!bookingData.guests || bookingData.guests < 1) {
        return 'Number of guests must be at least 1'
      }
      if (!bookingData.customer_id) {
        return 'You must be logged in to book a room'
      }
      
      // Validate dates
      const today = new Date()
      today.setHours(0, 0, 0, 0)
      
      const checkIn = new Date(bookingData.check_in)
      const checkOut = new Date(bookingData.check_out)
      
      if (checkIn < today) {
        return 'Check-in date cannot be in the past'
      }
      
      if (checkOut <= checkIn) {
        return 'Check-out date must be after check-in date'
      }
      
      return null
    },
  }
})
