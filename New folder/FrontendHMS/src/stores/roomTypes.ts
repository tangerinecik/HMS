import { defineStore } from 'pinia'
import { roomTypesAPI, setAuthToken } from '@/services/api'

export interface RoomType {
  id: number
  name: string
  capacity: number
  price_night: number
  location: 'cabin' | 'hotel'
}

export interface RoomTypeCreate {
  name: string
  capacity: number
  price_night: number
  location: 'cabin' | 'hotel'
}

export interface Pagination {
  page: number
  perPage: number
  totalPages: number
  totalItems: number
  hasNext: boolean
  hasPrevious: boolean
}

export const useRoomTypesStore = defineStore('roomTypes', {
  state: () => ({
    roomTypes: [] as RoomType[],
    currentRoomType: null as RoomType | null,
    pagination: null as Pagination | null,
    isLoading: false,
    error: null as string | null,
  }),

  getters: {
    getRoomTypeById: (state) => (id: number) => 
      state.roomTypes.find(rt => rt.id === id),
    
    totalRoomTypes: (state) => state.pagination?.totalItems || 0,
    
    hasError: (state) => !!state.error
  },

  actions: {
    clearError() {
      this.error = null
    },    async fetchRoomTypes(page = 1, limit = 10) {
      this.isLoading = true
      this.error = null

      try {
        const response = await roomTypesAPI.getAll({ page, limit })
        
        // Handle the response structure from backend
        if (response.data) {
          // If response has data and pagination properties (new format)
          if (response.data.data && response.data.pagination) {
            this.roomTypes = response.data.data
            this.pagination = response.data.pagination
          }
          // If response has roomTypes array directly (old format)
          else if (Array.isArray(response.data)) {
            this.roomTypes = response.data
            this.pagination = null
          } 
          // If response has roomTypes and pagination properties (legacy)
          else if (response.data.roomTypes) {
            this.roomTypes = response.data.roomTypes
            this.pagination = response.data.pagination
          }
          // Fallback: assume it's the direct data
          else {
            this.roomTypes = response.data
            this.pagination = null
          }
        } else {
          this.roomTypes = []
          this.pagination = null
        }
      } catch (error: any) {
        const errorMessage = error.response?.data?.message || error.response?.data?.error || error.message || 'Failed to fetch room types'
        this.error = errorMessage
        console.error('Error fetching room types:', error)
      } finally {
        this.isLoading = false
      }
    },

    async fetchRoomType(id: number) {
      this.isLoading = true
      this.error = null

      try {
        const response = await roomTypesAPI.getById(id)
        this.currentRoomType = response.data
        return response.data
      } catch (error: any) {
        this.error = error.response?.data?.error || 'Failed to fetch room type'
        console.error('Error fetching room type:', error)
        return null
      } finally {
        this.isLoading = false
      }
    },    async createRoomType(roomTypeData: RoomTypeCreate) {
      this.isLoading = true
      this.error = null

      try {
        // Make sure we have a valid token before making the request
        const token = localStorage.getItem('token')
        if (!token) {
          console.error('No authentication token available')
          this.error = 'Authentication required. Please log in again.'
          return { success: false, error: this.error }
        }
        
        // Set token in headers again to ensure it's present
        setAuthToken(token)
        
        const response = await roomTypesAPI.create(roomTypeData)
        
        // Check if response is successful
        if (response.data) {
          const newRoomType = response.data
          
          // Add to the beginning of the list
          this.roomTypes.unshift(newRoomType)
          
          return { success: true, data: newRoomType }
        } else {
          throw new Error('Invalid response from server')
        }
      } catch (error: any) {
        const errorMessage = error.response?.data?.message || error.response?.data?.error || error.message || 'Failed to create room type'
        this.error = errorMessage
        
        // Special handling for 403 error to provide more helpful message
        if (error.response?.status === 403) {
          this.error = 'You do not have permission to create room types. Please contact an administrator.'
        }
        
        console.error('Error creating room type:', error)
        return { success: false, error: this.error }
      } finally {
        this.isLoading = false
      }
    },async updateRoomType(id: number, roomTypeData: Partial<RoomTypeCreate>) {
      this.isLoading = true
      this.error = null

      try {
        const response = await roomTypesAPI.update(id, roomTypeData)
        
        if (response.data) {
          const updatedRoomType = response.data
          
          // Update in the list
          const index = this.roomTypes.findIndex(rt => rt.id === id)
          if (index !== -1) {
            this.roomTypes[index] = updatedRoomType
          }
          
          // Update current room type if it's the same
          if (this.currentRoomType?.id === id) {
            this.currentRoomType = updatedRoomType
          }
          
          return { success: true, data: updatedRoomType }
        } else {
          throw new Error('Invalid response from server')
        }
      } catch (error: any) {
        const errorMessage = error.response?.data?.message || error.response?.data?.error || error.message || 'Failed to update room type'
        this.error = errorMessage
        console.error('Error updating room type:', error)
        return { success: false, error: errorMessage }
      } finally {
        this.isLoading = false
      }
    },    async deleteRoomType(id: number) {
      this.isLoading = true
      this.error = null

      try {
        await roomTypesAPI.delete(id)
        
        // Remove from the list
        this.roomTypes = this.roomTypes.filter(rt => rt.id !== id)
        
        // Clear current room type if it's the deleted one
        if (this.currentRoomType?.id === id) {
          this.currentRoomType = null
        }
        
        return { success: true }
      } catch (error: any) {
        const errorMessage = error.response?.data?.message || error.response?.data?.error || error.message || 'Failed to delete room type'
        this.error = errorMessage
        console.error('Error deleting room type:', error)
        return { success: false, error: errorMessage }
      } finally {
        this.isLoading = false
      }
    }
  }
})
