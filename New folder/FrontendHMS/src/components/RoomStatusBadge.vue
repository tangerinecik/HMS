<template>
  <!-- 
    Room Status Badge Component
    Displays the status of a room with icon and label
    Can be styled as small, normal, or large size
  -->
  <span 
    class="room-status-badge"
    :class="getBadgeClasses"
    :style="getBadgeStyles"
  >
    <!-- Status icon (emoji) -->
    <span class="status-icon">{{ statusInfo.icon }}</span>
    
    <!-- Status label (only show if not hidden) -->
    <span v-if="!hideLabel" class="status-label">
      {{ statusInfo.label }}
    </span>
  </span>
</template>

<script setup lang="ts">
import { computed } from 'vue'
import type { Room } from '@/stores/rooms'
import { getRoomStatusInfo } from '@/utils/roomStatus'

/**
 * Props interface - defines what data this component accepts
 */
interface Props {
  status?: Room['status'] | undefined | null  // The current status of the room (allow undefined)
  hideLabel?: boolean     // Whether to hide the text label
  small?: boolean         // Make the badge smaller
  large?: boolean         // Make the badge larger
}

/**
 * Set default values for optional props
 */
const props = withDefaults(defineProps<Props>(), {
  status: 'available',    // Default to available if no status provided
  hideLabel: false,
  small: false,
  large: false
})

/**
 * Get status information (icon, label, color) from the utility function
 */
const statusInfo = computed(() => getRoomStatusInfo(props.status))

/**
 * Calculate the CSS classes for the badge based on props
 */
const getBadgeClasses = computed(() => [
  `status-${props.status}`,  // Add status-specific class (e.g., 'status-available')
  { 
    small: props.small,      // Add 'small' class if small prop is true
    large: props.large       // Add 'large' class if large prop is true
  }
])

/**
 * Calculate the CSS styles for the badge
 * Uses CSS custom property to set the color dynamically
 */
const getBadgeStyles = computed(() => ({
  '--status-color': statusInfo.value.color
}))
</script>

<style scoped>
/* ===================================
   BASE BADGE STYLES
   ================================== */

/**
 * Main badge container
 * Creates a flexible container with rounded corners and smooth transitions
 */
.room-status-badge {
  /* Layout */
  display: inline-flex;
  align-items: center;
  gap: 0.375rem;
  
  /* Spacing */
  padding: 0.375rem 0.75rem;
  
  /* Appearance */
  background: color-mix(in srgb, var(--status-color) 10%, transparent);
  color: var(--status-color);
  border: 1px solid color-mix(in srgb, var(--status-color) 30%, transparent);
  border-radius: 12px;
  
  /* Typography */
  font-size: 0.875rem;
  font-weight: 600;
  white-space: nowrap;
  
  /* Animation */
  transition: all 0.2s ease;
}

/**
 * Icon styling - keeps emoji consistent
 */
.status-icon {
  font-size: 1em;
  line-height: 1;
}

/**
 * Label styling - makes text look nice
 */
.status-label {
  text-transform: capitalize;
  letter-spacing: 0.025em;
}

/* ===================================
   SIZE VARIATIONS
   ================================== */

/**
 * Small badge variant - used in compact spaces
 */
.room-status-badge.small {
  padding: 0.25rem 0.5rem;
  font-size: 0.75rem;
  gap: 0.25rem;
  border-radius: 8px;
}

/**
 * Large badge variant - used for emphasis
 */
.room-status-badge.large {
  padding: 0.5rem 1rem;
  font-size: 1rem;
  gap: 0.5rem;
  border-radius: 16px;
}

/* ===================================
   STATUS-SPECIFIC BACKGROUNDS
   ================================== */

/**
 * Available rooms - green theme
 */
.status-available {
  background: color-mix(in srgb, var(--status-color) 15%, var(--success-bg, #f0f9ff));
}

/**
 * Occupied rooms - blue theme
 */
.status-occupied {
  background: color-mix(in srgb, var(--status-color) 15%, var(--info-bg, #eff6ff));
}

/**
 * Cleaning rooms - yellow/amber theme
 */
.status-cleaning {
  background: color-mix(in srgb, var(--status-color) 15%, var(--warning-bg, #fffbeb));
}

/**
 * Maintenance rooms - red theme
 */
.status-maintenance {
  background: color-mix(in srgb, var(--status-color) 15%, var(--error-bg, #fef2f2));
}

/**
 * Out of order rooms - gray theme
 */
.status-out_of_order {
  background: color-mix(in srgb, var(--status-color) 15%, var(--surface-color, #f8fafc));
}

/* ===================================
   DARK MODE SUPPORT
   ================================== */

/**
 * Adjustments for dark theme
 * Makes colors more visible on dark backgrounds
 */
[data-theme="dark"] .room-status-badge {
  background: color-mix(in srgb, var(--status-color) 20%, var(--surface-color-dark));
  border-color: color-mix(in srgb, var(--status-color) 40%, transparent);
}

/* ===================================
   INTERACTIVE EFFECTS
   ================================== */

/**
 * Hover effect - adds subtle lift and shadow
 * Makes the badge feel interactive
 */
.room-status-badge:hover {
  transform: translateY(-1px);
  box-shadow: 0 4px 12px color-mix(in srgb, var(--status-color) 25%, transparent);
}
</style>
