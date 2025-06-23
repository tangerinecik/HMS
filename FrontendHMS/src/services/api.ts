import axios from 'axios'
import type { AxiosInstance } from 'axios'

// API Base URL from environment variable
const API_BASE_URL = import.meta.env.VITE_API_BASE_URL || 'http://localhost/api'

// Create axios instance
const api: AxiosInstance = axios.create({
  baseURL: API_BASE_URL,
  headers: {
    'Content-Type': 'application/json',
  },
})

// Setup API response interceptor
api.interceptors.response.use(
  (response) => response,
  async (error) => {
    // Check for unauthorized error (expired token)
    if (error.response?.status === 401) {
      // We'll handle this in the auth store
      const event = new CustomEvent('auth:unauthorized')
      window.dispatchEvent(event)
    }
    return Promise.reject(error)
  }
)

// Function to set the auth token
export const setAuthToken = (token: string | null): void => {
  if (token) {
    api.defaults.headers.common['Authorization'] = `Bearer ${token}`
    console.log('Auth token set in API headers')
  } else {
    delete api.defaults.headers.common['Authorization']
    console.log('Auth token removed from API headers')
  }
}

/**
 * Auth API endpoints
 */
export const authAPI = {
  login: (credentials: { email: string; password: string }) => 
    api.post('/auth/login', credentials),
  
  register: (userData: { 
    email: string; 
    password: string; 
    first_name: string;
    last_name: string;
    phone?: string;
    role?: string;
  }) => api.post('/auth/register', userData),
  
  logout: () => api.post('/auth/logout'),
  
  getProfile: () => api.get('/auth/me'),
  
  refreshToken: () => api.post('/auth/refresh'),
  
  verifyEmail: (token: string) => api.post('/auth/verify-email', { token }),
  
  resendVerification: (email: string) => api.post('/auth/resend-verification', { email }),
}

/**
 * Room Type API endpoints
 */
export const roomTypesAPI = {
  getAll: (params?: any) => api.get('/room-types', { params }),
  getById: (id: number) => api.get(`/room-types/${id}`),
  create: (roomTypeData: any) => api.post('/room-types', roomTypeData),
  update: (id: number, roomTypeData: any) => api.put(`/room-types/${id}`, roomTypeData),
  delete: (id: number) => api.delete(`/room-types/${id}`),
}

/**
 * Room API endpoints
 */
export const roomsAPI = {
  getAll: (params?: any) => api.get('/rooms', { params }),
  getById: (id: number) => api.get(`/rooms/${id}`),
  create: (roomData: any) => api.post('/rooms', roomData),
  update: (id: number, roomData: any) => api.put(`/rooms/${id}`, roomData),
  delete: (id: number) => api.delete(`/rooms/${id}`),
  updateStatus: (id: number, status: string) => api.put(`/rooms/${id}/status`, { status }),
  getByStatus: (status: string, page?: number, limit?: number) => 
    api.get(`/rooms/status/${status}`, { params: { page, limit } }),
  getStatusStats: () => api.get('/rooms/status/stats'),
  checkAvailability: (checkIn: string, checkOut: string, roomTypeId?: number) =>
    api.get('/rooms/availability', { params: { check_in: checkIn, check_out: checkOut, room_type_id: roomTypeId } }),
}

/**
 * Booking API endpoints
 */
export const bookingsAPI = {
  // Get user's own bookings
  getMyBookings: (params?: any) => api.get('/bookings/my', { params }),
  
  // Get all bookings (admin/employee only)
  getAllBookings: (params?: any) => api.get('/bookings/all', { params }),
  
  // Get booking by ID
  getById: (id: number) => api.get(`/bookings/${id}`),
  
  // Get booking by reference code
  getByRefCode: (refCode: string) => api.get(`/bookings/ref/${refCode}`),
  
  // Create new booking
  create: (bookingData: any) => api.post('/bookings', bookingData),
  
  // Cancel booking
  cancel: (id: number) => api.put(`/bookings/${id}/cancel`),
  
  // Update booking status (admin/employee only)
  updateStatus: (id: number, status: string) => api.put(`/bookings/${id}/status`, { status }),
    // Check room availability
  checkAvailability: (checkIn: string, checkOut: string, guests: number) =>
    api.get('/bookings/availability', { params: { check_in: checkIn, check_out: checkOut, guests } }),
    
  // Get booking statistics (admin/employee only)
  getStats: () => api.get('/bookings/stats'),
}

export { api }
export default api
