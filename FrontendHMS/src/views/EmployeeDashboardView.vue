<template>  <div class="employee-dashboard">
    <div class="dashboard-header">
      <div class="container">
        <div class="header-row">
          <div class="title-section">
            <h1 class="dashboard-title">
              🏨 Employee Dashboard
            </h1>
            <p class="dashboard-subtitle">
              Manage room types and rooms for Hotel Lebedivka
            </p>
          </div>
            <div class="header-stats">            
            <div class="stat-item compact clickable" @click="showRoomTypes()">
              <div class="stat-content">
                <h3 class="stat-number">{{ roomTypesStore.totalRoomTypes }}</h3>
                <p class="stat-label"><span class="stat-icon small">🏷️</span> Room Types</p>
              </div>
            </div>
            <div class="stat-item compact clickable" @click="showAllRooms()">
              <div class="stat-content">
                <h3 class="stat-number">{{ roomsStore.totalRooms }}</h3>
                <p class="stat-label"><span class="stat-icon small">🏠</span> Total Rooms</p>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>

    <div class="container">
      <div class="dashboard-content"> <!-- Quick Stats -->
        <div class="stats-grid">
          <GameCard elevated clickable @click="filterByStatus('available')">
            <div class="stat-item">
              <div class="stat-icon">✅</div>
              <div class="stat-content">
                <h3 class="stat-number">{{ statusCounts.available }}</h3>
                <p class="stat-label">Available</p>
              </div>
            </div>
          </GameCard>
          
          <GameCard elevated clickable @click="filterByStatus('occupied')">
            <div class="stat-item">
              <div class="stat-icon">👥</div>
              <div class="stat-content">
                <h3 class="stat-number">{{ statusCounts.occupied }}</h3>
                <p class="stat-label">Occupied</p>
              </div>
            </div>
          </GameCard>
          
          <GameCard elevated clickable @click="filterByStatus('cleaning')">
            <div class="stat-item">
              <div class="stat-icon">🧹</div>
              <div class="stat-content">
                <h3 class="stat-number">{{ statusCounts.cleaning }}</h3>
                <p class="stat-label">Cleaning</p>
              </div>
            </div>
          </GameCard>
          
          <GameCard elevated clickable @click="filterByStatus('maintenance')">
            <div class="stat-item">
              <div class="stat-icon">🔧</div>
              <div class="stat-content">
                <h3 class="stat-number">{{ statusCounts.maintenance + statusCounts.out_of_order }}</h3>
                <p class="stat-label">Maintenance</p>
              </div>
            </div>
          </GameCard>
        </div>

        <!-- Management Tabs -->
        <div class="management-tabs">
          <button 
            class="tab-button"
            :class="{ active: activeTab === 'room-types' }"
            @click="activeTab = 'room-types'"
          >
            🏷️ Room Types
          </button>
          <button 
            class="tab-button"
            :class="{ active: activeTab === 'rooms' }"
            @click="activeTab = 'rooms'"
          >
            🏠 Rooms
          </button>
        </div>

        <!-- Tab Content -->
        <div class="tab-content">
          <RoomsManagement v-if="activeTab === 'rooms'" />
          <RoomTypesManagement v-if="activeTab === 'room-types'" />
        </div>
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
import { ref, computed, onMounted, watch } from 'vue'
import { useRoomTypesStore } from '@/stores/roomTypes'
import { useRoomsStore } from '@/stores/rooms'
import { useAuthStore } from '@/stores/auth'
import type { Room } from '@/stores/rooms'
import GameCard from '@/components/GameCard.vue'
import RoomTypesManagement from '@/components/RoomTypesManagement.vue'
import RoomsManagement from '@/components/RoomsManagement.vue'

const roomTypesStore = useRoomTypesStore()
const roomsStore = useRoomsStore()
const authStore = useAuthStore()
const activeTab = ref<'room-types' | 'rooms'>('room-types')

// Status counts ref for accurate totals
const statusCounts = ref({
  available: 0,
  occupied: 0,
  cleaning: 0,
  maintenance: 0,
  out_of_order: 0
})

// Current filter state
const selectedStatusFilter = ref('')
const selectedRoomTypeFilter = ref('')

const hotelRoomsCount = computed(() => 
  roomsStore.rooms.filter(room => 
    (room.room_type?.location || room.location) === 'hotel'
  ).length
)

const cabinRoomsCount = computed(() => 
  roomsStore.rooms.filter(room => 
    (room.room_type?.location || room.location) === 'cabin'
  ).length
)

// Function to fetch accurate status counts from API
const fetchStatusCounts = async () => {
  try {
    console.log('Dashboard: Auth check - isAuthenticated:', authStore.isAuthenticated);
    console.log('Dashboard: Auth check - isEmployee:', authStore.isEmployee);
    console.log('Dashboard: Auth check - user:', authStore.user);
    console.log('Dashboard: Auth check - token exists:', !!authStore.token);
    
    console.log('Dashboard: Fetching status counts...');
    const result = await roomsStore.fetchAllStatusCounts()
    console.log('Dashboard: Status counts result:', result);
    
    if (result && typeof result === 'object') {
      statusCounts.value = {
        available: result.available || 0,
        occupied: result.occupied || 0,
        cleaning: result.cleaning || 0,
        maintenance: result.maintenance || 0,
        out_of_order: result.out_of_order || 0
      }
      console.log('Dashboard: Updated status counts:', statusCounts.value);
    }
  } catch (error) {
    console.error('Dashboard: Error fetching status counts:', error)
    console.log('Dashboard: Falling back to paginated counts:', roomsStore.statusCounts)
    // Fallback to current status counts from store
    statusCounts.value = roomsStore.statusCounts
  }
}

// Methods to filter and display rooms
const showRoomTypes = () => {
  activeTab.value = 'room-types'
}

const showAllRooms = () => {
  activeTab.value = 'rooms'
  selectedStatusFilter.value = ''
  selectedRoomTypeFilter.value = ''
  notifyRoomsComponent()
}

const filterByStatus = (status: Room['status']) => {
  activeTab.value = 'rooms'
  selectedStatusFilter.value = status
  notifyRoomsComponent()
}

// Notify the RoomsManagement component about filter changes
const notifyRoomsComponent = () => {
  // Use a custom event to communicate with child components
  const event = new CustomEvent('filter-rooms', {
    detail: {
      status: selectedStatusFilter.value,
      roomTypeId: selectedRoomTypeFilter.value
    }
  })
  window.dispatchEvent(event)
}

// Refresh status counts when rooms are updated
const refreshStats = async () => {
  await fetchStatusCounts()
}

// Listen for room updates to refresh stats
window.addEventListener('room-updated', refreshStats)
window.addEventListener('room-created', refreshStats)
window.addEventListener('room-deleted', refreshStats)

onMounted(async () => {
  // Wait for auth to be ready first
  console.log('Dashboard: Mounting - checking auth status...');
  
  // If not authenticated, wait a bit for auth initialization
  if (!authStore.isAuthenticated) {
    console.log('Dashboard: Not authenticated yet, waiting...');
    await new Promise(resolve => setTimeout(resolve, 100));
  }
  
  console.log('Dashboard: Final auth check - isAuthenticated:', authStore.isAuthenticated);
  console.log('Dashboard: Final auth check - isEmployee:', authStore.isEmployee);
  
  // Load initial data
  await Promise.all([
    roomTypesStore.fetchRoomTypes(),
    roomsStore.fetchRooms() 
  ])
  
  // Fetch accurate status counts from API
  await fetchStatusCounts()
})
</script>

<style scoped>
.employee-dashboard {
  min-height: 100vh;
  background: var(--page-bg);
}

.dashboard-header {
  color: white;
  padding: 3rem 0;
  margin-bottom: 2rem;
}

.dashboard-title {
  font-size: 2.5rem;
  font-weight: 700;
  margin: 0 0 0.5rem 0;
  text-shadow: 0 2px 4px rgba(0, 0, 0, 0.3);
}

.dashboard-subtitle {
  font-size: 1.25rem;
  margin: 0;
  opacity: 0.9;
}

.container {
  max-width: 1200px;
  margin: 0 auto;
  padding: 0 1rem;
}

.dashboard-content {
  padding-bottom: 4rem;
}

.stats-grid {
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
  gap: 1.5rem;
  margin-bottom: 3rem;
}

.stat-item {
  display: flex;
  align-items: center;
  gap: 1rem;
  padding: 0.5rem;
}

.stat-icon {
  font-size: 2.5rem;
  width: 60px;
  height: 60px;
  display: flex;
  align-items: center;
  justify-content: center;
  background: var(--primary-gradient);
  border-radius: 16px;
  box-shadow: 0 4px 12px rgba(102, 126, 234, 0.3);
}

.stat-icon.small {
  display: inline-flex;
  width: auto;
  height: auto;
  font-size: 1rem;
  background: none;
  box-shadow: none;
  margin-right: 0.25rem;
}

.stat-content {
  flex: 1;
}

.stat-number {
  font-size: 2rem;
  font-weight: 700;
  margin: 0;
  color: var(--primary-color);
  text-shadow: 0 1px 2px rgba(0, 0, 0, 0.1);
}

/* Dark mode styles for stat numbers */
[data-theme="dark"] .stat-number {
  color: #ffffff;
  text-shadow: 0 1px 3px rgba(0, 0, 0, 0.3);
}

.stat-label {
  font-size: 0.875rem;
  color: var(--text-muted);
  margin: 0;
  text-transform: uppercase;
  letter-spacing: 0.5px;
  font-weight: 600;
}

.management-tabs {
  display: flex;
  gap: 0.5rem;
  margin-bottom: 2rem;
  background: var(--surface-color);
  padding: 0.5rem;
  border-radius: 16px;
  box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
}

.tab-button {
  flex: 1;
  padding: 1rem 1.5rem;
  border: none;
  background: transparent;
  color: var(--text-muted);
  font-weight: 600;
  border-radius: 12px;
  cursor: pointer;
  transition: all 0.3s ease;
  font-size: 1rem;
}

.tab-button:hover {
  background: var(--card-bg);
  color: var(--text-color);
}

.tab-button.active {
  background: var(--primary-gradient);
  color: var(--text-color);
  font-weight: 700;
  box-shadow: 0 4px 12px rgba(102, 126, 234, 0.3);
}

/* Dark mode - white text for active tab */
[data-theme="dark"] .tab-button.active {
  color: white;
}

.tab-content {
  min-height: 400px;
}

/* Header Row Layout */
.header-row {
  display: flex;
  justify-content: space-between;
  align-items: center;
  flex-wrap: wrap;
  gap: 2rem;
}

.title-section {
  flex: 1;
}

.header-stats {
  display: flex;
  gap: 2rem;
  align-items: center;
}

.stat-item.compact {
  background: rgba(255, 255, 255, 0.1);
  border-radius: 12px;
  padding: 0.75rem 1rem;
  backdrop-filter: blur(10px);
  box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
  min-width: 120px;
  transition: all 0.3s ease;
  text-align: center;
}

.stat-item.compact.clickable {
  cursor: pointer;
}

.stat-item.compact:hover {
  transform: translateY(-3px);
  box-shadow: 0 8px 16px rgba(0, 0, 0, 0.15);
}

.stat-item.compact .stat-content {
  width: 100%;
}

.stat-item.compact .stat-number {
  font-size: 1.75rem;
  font-weight: 700;
  margin-bottom: 0.25rem;
  background: var(--primary-gradient);
  background-clip: text;
  -webkit-background-clip: text;
  -webkit-text-fill-color: transparent;
}

/* Dark mode styles for compact stat numbers */
[data-theme="dark"] .stat-item.compact .stat-number {
  background: #ffffff;
  -webkit-text-fill-color: #ffffff;
  background-clip: text;
  -webkit-background-clip: text;
  text-shadow: 0 1px 3px rgba(0, 0, 0, 0.3);
}

.stat-item.compact .stat-label {
  font-size: 0.85rem;
  font-weight: 600;
  display: flex;
  align-items: center;
  justify-content: center;
}

/* New styles for clickable cards in stats grid */
.stats-grid .game-card {
  position: relative;
  overflow: hidden;
}

.stats-grid .game-card::after {
  content: 'Click to filter';
  position: absolute;
  bottom: 10px;
  right: 10px;
  background: var(--primary-gradient);
  color: white;
  font-size: 0.75rem;
  padding: 2px 8px;
  border-radius: 10px;
  opacity: 0;
  transform: translateY(10px);
  transition: all 0.3s ease;
}

.stats-grid .game-card:hover::after {
  opacity: 1;
  transform: translateY(0);
}

/* Responsive adjustments */
@media (max-width: 768px) {
  .dashboard-title {
    font-size: 2rem;
  }
  
  .dashboard-subtitle {
    font-size: 1rem;
  }
  
  .stats-grid {
    grid-template-columns: repeat(auto-fit, minmax(150px, 1fr));
    gap: 1rem;
  }
  
  .stat-icon {
    font-size: 2rem;
    width: 50px;
    height: 50px;
  }
  
  .stat-number {
    font-size: 1.5rem;
  }
  
  .management-tabs {
    flex-direction: column;
  }
  
  .tab-button {
    text-align: center;
  }
  
  .header-row {
    flex-direction: column;
    gap: 1rem;
  }
  
  .header-stats {
    width: 100%;
    justify-content: space-around;
  }
}
</style>
