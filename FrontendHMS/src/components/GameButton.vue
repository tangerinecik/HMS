<template>
  <button
    :type="type"
    :disabled="disabled || loading"
    :class="buttonClasses"
    @click="handleClick"
  >
    <span v-if="loading" class="button-spinner"></span>
    <span v-if="icon && !loading" class="button-icon" v-html="icon"></span>
    <span v-if="$slots.default" class="button-text">
      <slot></slot>
    </span>
  </button>
</template>

<script setup lang="ts">
import { computed, useSlots } from 'vue'

interface Props {
  variant?: 'primary' | 'secondary' | 'success' | 'danger' | 'warning' | 'ghost'
  size?: 'sm' | 'md' | 'lg'
  type?: 'button' | 'submit' | 'reset'
  disabled?: boolean
  loading?: boolean
  icon?: string
  block?: boolean
}

interface Emits {
  (e: 'click', event: MouseEvent): void
}

const props = withDefaults(defineProps<Props>(), {
  variant: 'primary',
  size: 'md',
  type: 'button',
  disabled: false,
  loading: false,
  block: false
})

const emit = defineEmits<Emits>()
const slots = useSlots()

const buttonClasses = computed(() => [
  'game-button',
  `game-button--${props.variant}`,
  `game-button--${props.size}`,
  {
    'game-button--loading': props.loading,
    'game-button--disabled': props.disabled,
    'game-button--block': props.block,
    'game-button--icon-only': props.icon && !slots.default
  }
])

const handleClick = (event: MouseEvent) => {
  if (!props.disabled && !props.loading) {
    emit('click', event)
  }
}
</script>

<style scoped>
.game-button {
  position: relative;
  display: inline-flex;
  align-items: center;
  justify-content: center;
  gap: 0.5rem;
  border: 2px solid transparent;
  border-radius: 12px;
  font-weight: 600;
  text-decoration: none;
  cursor: pointer;
  transition: all 0.2s cubic-bezier(0.4, 0, 0.2, 1);
  outline: none;
  overflow: hidden;
  white-space: nowrap;
}

.game-button::before {
  content: '';
  position: absolute;
  top: 0;
  left: -100%;
  width: 100%;
  height: 100%;
  background: linear-gradient(90deg, transparent, rgba(255,255,255,0.3), transparent);
  transition: left 0.5s;
}

.game-button:hover:not(:disabled):not(.game-button--loading)::before {
  left: 100%;
}

/* Sizes */
.game-button--sm {
  padding: 0.5rem 1rem;
  font-size: 0.875rem;
  min-height: 2.25rem;
}

.game-button--md {
  padding: 0.75rem 1.5rem;
  font-size: 1rem;
  min-height: 2.75rem;
}

.game-button--lg {
  padding: 1rem 2rem;
  font-size: 1.125rem;
  min-height: 3.25rem;
}

/* Variants */
.game-button--primary {
  background: var(--primary-gradient);
  color: #ffffff;
  font-weight: 600;
  text-shadow: 0 1px 2px rgba(0, 0, 0, 0.3);
  box-shadow: 0 4px 12px rgba(102, 126, 234, 0.3);
}

.game-button--primary:hover:not(:disabled):not(.game-button--loading) {
  transform: translateY(-2px);
  box-shadow: 0 8px 20px rgba(102, 126, 234, 0.4);
}

.game-button--secondary {
  background: var(--secondary-gradient);
  color: #ffffff;
  font-weight: 600;
  text-shadow: 0 1px 2px rgba(0, 0, 0, 0.3);
  box-shadow: 0 4px 12px rgba(118, 75, 162, 0.3);
}

.game-button--secondary:hover:not(:disabled):not(.game-button--loading) {
  transform: translateY(-2px);
  box-shadow: 0 8px 20px rgba(118, 75, 162, 0.4);
}

.game-button--success {
  background: linear-gradient(135deg, #4ade80, #22c55e);
  color: #ffffff;
  font-weight: 600;
  text-shadow: 0 1px 2px rgba(0, 0, 0, 0.3);
  box-shadow: 0 4px 12px rgba(34, 197, 94, 0.3);
}

.game-button--success:hover:not(:disabled):not(.game-button--loading) {
  transform: translateY(-2px);
  box-shadow: 0 8px 20px rgba(34, 197, 94, 0.4);
}

.game-button--danger {
  background: linear-gradient(135deg, #f87171, #dc2626);
  color: #ffffff;
  font-weight: 600;
  text-shadow: 0 1px 2px rgba(0, 0, 0, 0.3);
  box-shadow: 0 4px 12px rgba(220, 38, 38, 0.3);
}

.game-button--danger:hover:not(:disabled):not(.game-button--loading) {
  transform: translateY(-2px);
  box-shadow: 0 8px 20px rgba(220, 38, 38, 0.4);
}

.game-button--warning {
  background: linear-gradient(135deg, #fbbf24, #f59e0b);
  color: #0f172a; /* Dark text for better contrast on yellow/orange backgrounds */
  font-weight: 700;
  box-shadow: 0 4px 12px rgba(245, 158, 11, 0.3);
}

.game-button--warning:hover:not(:disabled):not(.game-button--loading) {
  transform: translateY(-2px);
  box-shadow: 0 8px 20px rgba(245, 158, 11, 0.4);
}

.game-button--ghost {
  background: transparent;
  color: var(--text-color);
  border-color: var(--border-color);
  font-weight: 500;
}

.game-button--ghost:hover:not(:disabled):not(.game-button--loading) {
  background: var(--surface-color);
  border-color: var(--primary-color);
  color: var(--primary-color);
  font-weight: 600;
}

/* States */
.game-button--loading,
.game-button--disabled {
  cursor: not-allowed;
  opacity: 0.6;
  transform: none !important;
}

.game-button--block {
  width: 100%;
}

.game-button--icon-only {
  padding: 0.75rem;
  aspect-ratio: 1;
}

/* Button elements */
.button-spinner {
  width: 1rem;
  height: 1rem;
  border: 2px solid currentColor;
  border-top-color: transparent;
  border-radius: 50%;
  animation: spin 1s linear infinite;
}

.button-icon {
  display: flex;
  align-items: center;
  justify-content: center;
}

.button-text {
  display: flex;
  align-items: center;
}

@keyframes spin {
  to {
    transform: rotate(360deg);
  }
}

/* Dark mode adjustments */
[data-theme="dark"] .game-button--ghost {
  border-color: var(--border-color-dark);
}

[data-theme="dark"] .game-button--ghost:hover:not(:disabled):not(.game-button--loading) {
  background: var(--surface-color-dark);
  border-color: var(--primary-color-light);
  color: var(--primary-color-light);
}

/* Ensure text contrast in all button types in dark mode */
[data-theme="dark"] .game-button--primary,
[data-theme="dark"] .game-button--secondary,
[data-theme="dark"] .game-button--success,
[data-theme="dark"] .game-button--danger {
  color: #ffffff;
  text-shadow: 0 1px 3px rgba(0, 0, 0, 0.5);
}

[data-theme="dark"] .game-button--warning {
  color: #0f172a; /* Dark text for warning buttons in dark mode */
  font-weight: 800;
}

@media (max-width: 768px) {
  .game-button:hover:not(:disabled):not(.game-button--loading) {
    transform: none;
  }
}
</style>
