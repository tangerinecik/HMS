<template>
  <div class="hello-world">
    <h1>{{ msg }}</h1>
    <p>Welcome to Hotel Lebedivka Management System!</p>
  </div>
</template>

<script setup lang="ts">
interface Props {
  msg: string
}

defineProps<Props>()
</script>

<style scoped>
.hello-world {
  text-align: center;
  padding: 2rem;
}

.hello-world h1 {
  color: var(--text-color);
  margin-bottom: 1rem;
}

.hello-world p {
  color: var(--text-secondary);
}
</style>
