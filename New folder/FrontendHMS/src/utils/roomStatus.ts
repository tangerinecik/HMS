import type { Room } from '@/stores/rooms'

export type RoomStatus = Room['status']

export interface RoomStatusInfo {
  label: string
  icon: string
  color: string
  description: string
}

export const ROOM_STATUSES: Record<RoomStatus, RoomStatusInfo> = {
  available: {
    label: 'Available',
    icon: '✅',
    color: '#10B981', // green-500
    description: 'Room is ready for guests'
  },
  occupied: {
    label: 'Occupied',
    icon: '👥',
    color: '#3B82F6', // blue-500
    description: 'Room is currently occupied by guests'
  },
  cleaning: {
    label: 'Cleaning',
    icon: '🧹',
    color: '#F59E0B', // amber-500
    description: 'Room is being cleaned'
  },
  maintenance: {
    label: 'Maintenance',
    icon: '🔧',
    color: '#EF4444', // red-500
    description: 'Room requires maintenance'
  },
  out_of_order: {
    label: 'Out of Order',
    icon: '❌',
    color: '#6B7280', // gray-500
    description: 'Room is temporarily unavailable'
  }
}

export const getRoomStatusInfo = (status: RoomStatus | undefined | null): RoomStatusInfo => {
  if (!status || !ROOM_STATUSES[status]) {
    return ROOM_STATUSES.available // Default to available if status is undefined/invalid
  }
  return ROOM_STATUSES[status]
}

export const getRoomStatusColor = (status: RoomStatus | undefined | null): string => {
  return getRoomStatusInfo(status).color
}

export const getRoomStatusIcon = (status: RoomStatus | undefined | null): string => {
  return getRoomStatusInfo(status).icon
}

export const getRoomStatusLabel = (status: RoomStatus | undefined | null): string => {
  return getRoomStatusInfo(status).label
}

export const getAvailableStatuses = (): RoomStatus[] => {
  return Object.keys(ROOM_STATUSES) as RoomStatus[]
}

export const getStatusTransitions = (currentStatus: RoomStatus | undefined | null): RoomStatus[] => {
  if (!currentStatus) return Object.keys(ROOM_STATUSES) as RoomStatus[]
  
  // Define valid status transitions based on hotel workflow
  const transitions: Record<RoomStatus, RoomStatus[]> = {
    available: ['occupied', 'cleaning', 'maintenance', 'out_of_order'],
    occupied: ['cleaning', 'maintenance'],
    cleaning: ['available', 'maintenance'],
    maintenance: ['available', 'out_of_order'],
    out_of_order: ['maintenance', 'available']
  }
  
  return transitions[currentStatus] || []
}
