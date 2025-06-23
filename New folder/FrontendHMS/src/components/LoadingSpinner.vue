<template>
  <div class="loading-spinner" :class="{ 'loading-overlay': overlay }">
    <div class="spinner">
      <div class="spinner-ring"></div>
      <div class="spinner-ring"></div>
      <div class="spinner-ring"></div>
    </div>
    <p v-if="message" class="loading-message">{{ message }}</p>
  </div>
</template>

<script setup lang="ts">
interface Props {
  overlay?: boolean
  message?: string
}

withDefaults(defineProps<Props>(), {
  overlay: false,
  message: ''
})
</script>

<style scoped>
.loading-spinner {
  display: flex;
  flex-direction: column;
  align-items: center;
  justify-content: center;
  padding: 2rem;
}

.loading-overlay {
  position: fixed;
  top: 0;
  left: 0;
  right: 0;
  bottom: 0;
  background: rgba(0, 0, 0, 0.5);
  z-index: 9999;
}

.spinner {
  position: relative;
  width: 60px;
  height: 60px;
}

.spinner-ring {
  position: absolute;
  width: 100%;
  height: 100%;
  border: 3px solid transparent;
  border-radius: 50%;
  animation: spin 1.5s ease-in-out infinite;
}

.spinner-ring:nth-child(1) {
  border-top-color: var(--primary-color, #667eea);
  animation-delay: 0s;
}

.spinner-ring:nth-child(2) {
  border-right-color: var(--secondary-color, #764ba2);
  animation-delay: -0.5s;
}

.spinner-ring:nth-child(3) {
  border-bottom-color: var(--accent-color, #f093fb);
  animation-delay: -1s;
}

@keyframes spin {
  0% {
    transform: rotate(0deg);
    border-width: 3px;
  }
  50% {
    transform: rotate(180deg);
    border-width: 1px;
  }
  100% {
    transform: rotate(360deg);
    border-width: 3px;
  }
}

.loading-message {
  margin-top: 1rem;
  color: var(--text-color);
  font-weight: 500;
  text-align: center;
}

/* Dark mode */
[data-theme="dark"] .loading-overlay {
  background: rgba(0, 0, 0, 0.8);
}
</style>
