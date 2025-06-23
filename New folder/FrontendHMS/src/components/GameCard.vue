<template>
  <div 
    class="game-card" 
    :class="{ 
      'card-clickable': clickable,
      'card-elevated': elevated,
      'card-gradient': gradient 
    }"
    @click="handleClick"
  >
    <div v-if="$slots.header" class="card-header">
      <slot name="header"></slot>
    </div>
    
    <div class="card-body">
      <slot></slot>
    </div>
    
    <div v-if="$slots.footer" class="card-footer">
      <slot name="footer"></slot>
    </div>
  </div>
</template>

<script setup lang="ts">
interface Props {
  clickable?: boolean
  elevated?: boolean
  gradient?: boolean
}

interface Emits {
  (e: 'click'): void
}

const props = withDefaults(defineProps<Props>(), {
  clickable: false,
  elevated: false,
  gradient: false
})

const emit = defineEmits<Emits>()

const handleClick = () => {
  if (props.clickable) {
    emit('click')
  }
}
</script>

<style scoped>
.game-card {
  background: var(--card-bg);
  border: 2px solid var(--border-color);
  border-radius: 16px;
  overflow: hidden;
  transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
  position: relative;
}

.game-card::before {
  content: '';
  position: absolute;
  top: 0;
  left: 0;
  right: 0;
  height: 4px;
  background: var(--primary-gradient);
  opacity: 0;
  transition: opacity 0.3s ease;
}

.game-card:hover::before {
  opacity: 1;
}

.card-clickable {
  cursor: pointer;
}

.card-clickable:hover {
  transform: translateY(-4px);
  box-shadow: 0 12px 24px rgba(0, 0, 0, 0.15);
  border-color: var(--primary-color);
}

.card-elevated {
  box-shadow: 0 8px 16px rgba(0, 0, 0, 0.1);
}

.card-gradient {
  background: var(--card-gradient);
}

.card-header {
  padding: 1.5rem 1.5rem 1rem;
  border-bottom: 2px solid var(--border-color);
  background: var(--primary-gradient);
  color: white;
}

.card-body {
  padding: 1.5rem;
}

.card-footer {
  padding: 1rem 1.5rem 1.5rem;
  border-top: 2px solid var(--border-color);
  background: var(--surface-color);
}

/* Dark mode adjustments */
[data-theme="dark"] .game-card {
  border-color: var(--border-color-dark);
}

[data-theme="dark"] .card-clickable:hover {
  border-color: var(--primary-color-light);
  box-shadow: 0 12px 24px rgba(0, 0, 0, 0.3);
}

[data-theme="dark"] .card-header {
  border-bottom-color: var(--border-color-dark);
}

[data-theme="dark"] .card-footer {
  border-top-color: var(--border-color-dark);
}

@media (max-width: 768px) {
  .card-header,
  .card-body,
  .card-footer {
    padding: 1rem;
  }
  
  .card-clickable:hover {
    transform: translateY(-2px);
  }
}
</style>
