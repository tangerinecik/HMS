import { defineStore } from 'pinia'
import { roomsAPI } from '@/services/api'
import type { Pagination } from './roomTypes'

export interface Room {
  id: number
  number: string
  floor: number
  status: 'available' | 'occupied' | 'cleaning' | 'maintenance' | 'out_of_order'
  room_type_id: number
  room_type_name?: string
  capacity?: number
  price_night?: number
  location?: 'cabin' | 'hotel'
  // For backwards compatibility, we'll also support the nested format
  room_type?: {
    id: number
    name: string
    capacity: number
    price_night: number
    location: 'cabin' | 'hotel'
  }
}

export interface RoomCreate {
  number: string
  room_type_id: number
  floor: number
  status?: 'available' | 'occupied' | 'cleaning' | 'maintenance' | 'out_of_order'
}

export const useRoomsStore = defineStore('rooms', {
  state: () => ({
    rooms: [] as Room[],
    currentRoom: null as Room | null,
    pagination: null as Pagination | null,
    isLoading: false,
    error: null as string | null,
  }),
  getters: {
    getRoomById: (state) => (id: number) => 
      state.rooms.find(room => room.id === id),
      getRoomsByType: (state) => (roomTypeId: number) =>
      state.rooms.filter(room => 
        room.room_type?.id === roomTypeId || room.room_type_id === roomTypeId
      ),
    
    getRoomsByStatus: (state) => (status: Room['status']) =>
      state.rooms.filter(room => room.status === status),
    
    totalRooms: (state) => state.pagination?.totalItems || 0,
    
    hasError: (state) => !!state.error,
    
    statusCounts: (state) => {
      const counts = {
        available: 0,
        occupied: 0,
        cleaning: 0,
        maintenance: 0,
        out_of_order: 0
      }
      
      state.rooms.forEach(room => {
        if (room.status && counts.hasOwnProperty(room.status)) {
          counts[room.status]++
        }
      })
      
      return counts
    }
  },

  actions: {
    clearError() {
      this.error = null
    },

    async fetchRooms(page = 1, limit = 10, roomTypeId?: number) {
      this.isLoading = true
      this.error = null

      try {
        const params: any = { page, limit }
        if (roomTypeId) {
          params.room_type_id = roomTypeId
        }        const response = await roomsAPI.getAll(params)
        
        // Handle the response structure from backend
        if (response.data) {
          // If response has rooms array directly
          if (Array.isArray(response.data)) {
            this.rooms = response.data
            this.pagination = null
          } 
          // If response has rooms and pagination properties
          else if (response.data.rooms) {
            this.rooms = response.data.rooms
            this.pagination = response.data.pagination
          }
          // If response is the direct array from backend (no wrapper)
          else {
            this.rooms = response.data
            this.pagination = null
          }
        } else {
          this.rooms = []
          this.pagination = null
        }
      } catch (error: any) {
        const errorMessage = error.response?.data?.message || error.response?.data?.error || error.message || 'Failed to fetch rooms'
        this.error = errorMessage
        console.error('Error fetching rooms:', error)
      } finally {
        this.isLoading = false
      }
    },

    async fetchRoom(id: number) {
      this.isLoading = true
      this.error = null

      try {
        const response = await roomsAPI.getById(id)
        this.currentRoom = response.data
        return response.data
      } catch (error: any) {
        this.error = error.response?.data?.error || 'Failed to fetch room'
        console.error('Error fetching room:', error)
        return null
      } finally {
        this.isLoading = false
      }
    },    async createRoom(roomData: RoomCreate) {
      this.isLoading = true
      this.error = null

      try {
        const response = await roomsAPI.create(roomData)
        
        if (response.data) {
          const newRoom = response.data
          
          // Add to the beginning of the list
          this.rooms.unshift(newRoom)
          
          return { success: true, data: newRoom }
        } else {
          throw new Error('Invalid response from server')
        }
      } catch (error: any) {
        const errorMessage = error.response?.data?.message || error.response?.data?.error || error.message || 'Failed to create room'
        this.error = errorMessage
        console.error('Error creating room:', error)
        return { success: false, error: errorMessage }
      } finally {
        this.isLoading = false
      }
    },

    async createBulkRooms(roomData: RoomCreate & { quantity: number }) {
      this.isLoading = true
      this.error = null

      try {
        const baseRoomNumber = roomData.number
        const quantity = roomData.quantity
        const createdRooms = []
        
        // Check if room number is numeric or alphanumeric
        const isNumeric = /^\d+$/.test(baseRoomNumber)
        
        for (let i = 0; i < quantity; i++) {
          let roomNumber: string
          
          if (isNumeric) {
            // For numeric room numbers, increment sequentially
            roomNumber = (parseInt(baseRoomNumber) + i).toString()
          } else {
            // For alphanumeric room numbers, append sequence
            roomNumber = i === 0 ? baseRoomNumber : `${baseRoomNumber}-${i + 1}`
          }
          
          const singleRoomData = {
            number: roomNumber,
            room_type_id: roomData.room_type_id,
            floor: roomData.floor
          }
          
          try {
            const response = await roomsAPI.create(singleRoomData)
            
            if (response.data) {
              createdRooms.push(response.data)
              // Add to the beginning of the list
              this.rooms.unshift(response.data)
            }
          } catch (error: any) {
            console.error(`Failed to create room ${roomNumber}:`, error)
            // Continue with next room even if one fails
          }
        }
        
        if (createdRooms.length > 0) {
          return { 
            success: true, 
            data: createdRooms,
            message: `Successfully created ${createdRooms.length} out of ${quantity} rooms`
          }
        } else {
          throw new Error('Failed to create any rooms')
        }
      } catch (error: any) {
        const errorMessage = error.response?.data?.message || error.response?.data?.error || error.message || 'Failed to create rooms'
        this.error = errorMessage
        console.error('Error creating bulk rooms:', error)
        return { success: false, error: errorMessage }
      } finally {
        this.isLoading = false
      }
    },    async updateRoom(id: number, roomData: Partial<RoomCreate>) {
      this.isLoading = true
      this.error = null

      try {
        const response = await roomsAPI.update(id, roomData)
        
        if (response.data) {
          const updatedRoom = response.data
          
          // Update in the list
          const index = this.rooms.findIndex(room => room.id === id)
          if (index !== -1) {
            this.rooms[index] = updatedRoom
          }
          
          // Update current room if it's the same
          if (this.currentRoom?.id === id) {
            this.currentRoom = updatedRoom
          }
          
          return { success: true, data: updatedRoom }
        } else {
          throw new Error('Invalid response from server')
        }
      } catch (error: any) {
        const errorMessage = error.response?.data?.message || error.response?.data?.error || error.message || 'Failed to update room'
        this.error = errorMessage
        console.error('Error updating room:', error)
        return { success: false, error: errorMessage }
      } finally {
        this.isLoading = false
      }
    },

    async deleteRoom(id: number) {
      this.isLoading = true
      this.error = null

      try {
        await roomsAPI.delete(id)
        
        // Remove from the list
        this.rooms = this.rooms.filter(room => room.id !== id)
        
        // Clear current room if it's the deleted one
        if (this.currentRoom?.id === id) {
          this.currentRoom = null
        }
          return { success: true }
      } catch (error: any) {
        const errorMessage = error.response?.data?.message || error.response?.data?.error || error.message || 'Failed to delete room'
        this.error = errorMessage
        console.error('Error deleting room:', error)
        return { success: false, error: errorMessage }
      } finally {
        this.isLoading = false
      }
    },

    async updateRoomStatus(id: number, status: Room['status']) {
      this.isLoading = true
      this.error = null

      try {
        const response = await roomsAPI.updateStatus(id, status)
        
        if (response.data) {
          const updatedRoom = response.data
          
          // Update in the list
          const index = this.rooms.findIndex(room => room.id === id)
          if (index !== -1) {
            this.rooms[index] = updatedRoom
          }
          
          // Update current room if it's the same
          if (this.currentRoom?.id === id) {
            this.currentRoom = updatedRoom
          }
          
          return { success: true, data: updatedRoom }
        } else {
          throw new Error('Invalid response from server')
        }
      } catch (error: any) {
        const errorMessage = error.response?.data?.message || error.response?.data?.error || error.message || 'Failed to update room status'
        this.error = errorMessage
        console.error('Error updating room status:', error)
        return { success: false, error: errorMessage }
      } finally {
        this.isLoading = false
      }
    },

    async fetchRoomsByStatus(status: Room['status']) {
      this.isLoading = true;
      this.error = null;
      
      try {
        const response = await roomsAPI.getByStatus(status);
        
        if (Array.isArray(response.data)) {
          this.rooms = response.data;
          // No pagination info here, but we can still keep track of the filter
          this.pagination = null;
        } else {
          this.rooms = [];
        }
        
        return { success: true };
      } catch (error: any) {
        const errorMessage = error.response?.data?.message || error.response?.data?.error || error.message || 'Failed to fetch rooms by status';
        this.error = errorMessage;
        console.error('Error fetching rooms by status:', error);
        return { success: false, error: errorMessage };
      } finally {
        this.isLoading = false;
      }
    },

    async fetchRoomStatusStats() {
      this.error = null

      try {
        const response = await roomsAPI.getStatusStats()
        
        if (response.data) {
          return { success: true, data: response.data }
        } else {
          throw new Error('Invalid response from server')
        }
      } catch (error: any) {
        const errorMessage = error.response?.data?.message || error.response?.data?.error || error.message || 'Failed to fetch room status statistics'
        this.error = errorMessage
        console.error('Error fetching room status stats:', error)
        return { success: false, error: errorMessage }
      }
    },    async fetchAllStatusCounts() {
      this.error = null;
      
      try {
        console.log('Fetching status counts from API...');
        const response = await roomsAPI.getStatusStats();
        console.log('Status counts API response:', response.data);
        
        if (response.data && typeof response.data === 'object') {
          return response.data;
        }
        
        console.warn('Invalid response structure from status stats API:', response);
        throw new Error('Invalid response structure from server');
        
      } catch (error: any) {
        const errorMessage = error.response?.data?.message || error.response?.data?.error || error.message || 'Failed to fetch status counts';
        this.error = errorMessage;
        console.error('Error fetching status counts from API:', error);
        console.error('Error response:', error.response);
        console.error('Error status:', error.response?.status);
        
        // If API fails, try fetching all rooms as fallback (only for auth errors or if stats API is not working)
        if (error.response?.status === 401 || error.response?.status === 403 || error.response?.status === 400) {
          console.log('API failed, trying fallback method to fetch all rooms for stats calculation...');
          try {
            const statusCounts = await this.fetchAllRoomsForStats();
            console.log('Fallback method successful:', statusCounts);
            return statusCounts;
          } catch (fallbackError) {
            console.error('Fallback method also failed:', fallbackError);
            throw error; // Throw original error
          }
        }
        
        // For other errors, just throw
        throw error;
      }
    },

    async fetchAllRoomsForStats() {
      try {
        console.log('Fetching all rooms for accurate stats calculation...');
        let page = 1;
        const limit = 50; // Larger page size for efficiency
        let allRooms: Room[] = [];
        let hasMore = true;
        
        while (hasMore) {
          const response = await roomsAPI.getAll({ page, limit });
          
          if (response.data) {
            let pageRooms: Room[] = [];
            let pagination = null;
            
            // Handle different response structures
            if (Array.isArray(response.data)) {
              pageRooms = response.data;
              hasMore = pageRooms.length === limit; // Continue if full page
            } else if (response.data.rooms) {
              pageRooms = response.data.rooms;
              pagination = response.data.pagination;
              hasMore = pagination && pagination.hasNext;
            } else {
              pageRooms = response.data;
              hasMore = pageRooms.length === limit;
            }
            
            allRooms = [...allRooms, ...pageRooms];
            console.log(`Fetched page ${page}, got ${pageRooms.length} rooms, total: ${allRooms.length}`);
            
            if (pagination && !pagination.hasNext) {
              hasMore = false;
            }
            
            page++;
            
            // Safety break to prevent infinite loop
            if (page > 100) {
              console.warn('Stopped fetching rooms after 100 pages');
              break;
            }
          } else {
            hasMore = false;
          }
        }
        
        // Calculate status counts from all rooms
        const statusCounts = {
          available: 0,
          occupied: 0,
          cleaning: 0,
          maintenance: 0,
          out_of_order: 0
        };
        
        allRooms.forEach(room => {
          if (room.status && statusCounts.hasOwnProperty(room.status)) {
            statusCounts[room.status]++;
          }
        });
        
        console.log('Calculated status counts from all rooms:', statusCounts);
        console.log('Total rooms processed:', allRooms.length);
        
        return statusCounts;
        
      } catch (error: any) {
        console.error('Error fetching all rooms for stats:', error);
        throw error;
      }
    }
  }
})
