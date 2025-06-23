<script setup lang="ts">
import { RouterLink, RouterView } from 'vue-router'
import { useAuthStore } from "@/stores/auth";
import { ref, onMounted, onUnmounted } from "vue";

// Declare Bootstrap types on window object
declare global {
  interface Window {
    bootstrap?: {
      Collapse: {
        getInstance(element: HTMLElement): {
          hide(): void;
          show(): void;
        } | null;
        getOrCreateInstance(element: HTMLElement): {
          hide(): void;
          show(): void;
        };
      };
    };
  }
}

// Use the Composition API directly in <script setup>
const authStore = useAuthStore();
const isDarkMode = ref(false);
const navbarCollapsed = ref(true);

const toggleDarkMode = () => {
  // Toggle dark mode state
  isDarkMode.value = !isDarkMode.value;
  
  // Set theme attribute on html element and save preference
  const newTheme = isDarkMode.value ? 'dark' : 'light';
  document.documentElement.setAttribute('data-theme', newTheme);
  localStorage.setItem('theme', newTheme);
  
  // Add/remove class on body for additional styling if needed
  document.body.classList.toggle('dark-mode', isDarkMode.value);
};

// Function to close the navbar when clicking outside
const closeNavbarOnClickOutside = (event: MouseEvent) => {
  // Check if navbar is expanded
  const navbarMenu = document.getElementById('navbarNav');
  if (!navbarCollapsed.value && 
      navbarMenu && 
      !navbarMenu.contains(event.target as Node) && 
      !(event.target as HTMLElement).closest('.navbar-toggler')) {
    // Collapse navbar manually
    navbarCollapsed.value = true;
    navbarMenu.classList.remove('show');
  }
};

onMounted(() => {
  // Initialize auth state
  if (authStore.token) {
    // If we have a token but no user data, fetch it from the API
    if (!authStore.user) {
      authStore.fetchProfile();
    }
  }
  
  // Initialize theme preference from localStorage
  const savedTheme = localStorage.getItem('theme');
  isDarkMode.value = savedTheme === 'dark';
  
  // Apply saved theme
  document.documentElement.setAttribute('data-theme', savedTheme || 'light');
  document.body.classList.toggle('dark-mode', isDarkMode.value);
  
  // Add document click listener for navbar collapse
  document.addEventListener('click', closeNavbarOnClickOutside);
});

onUnmounted(() => {
  // Clean up event listener when component is destroyed
  document.removeEventListener('click', closeNavbarOnClickOutside);
});
</script>

<template>
  <!-- Navbar - always using the blue gradient regardless of theme -->
  <nav class="navbar navbar-expand-lg navbar-dark">
    <div class="container position-relative">
      <RouterLink to="/" class="navbar-brand">
        <img src="@/assets/logo.png" alt="Hotel Logo" class="logo-image" style="max-height: 30px; width: auto; object-fit: contain; margin-right: 20px;">
      </RouterLink>
      
      <button 
        class="navbar-toggler" 
        type="button" 
        @click="navbarCollapsed = !navbarCollapsed"
        :aria-expanded="!navbarCollapsed"
        aria-label="Toggle navigation"
      >
        <span class="navbar-toggler-icon"></span>
      </button>
      
      <!-- Fixed toggle button for mobile, always visible regardless of menu state -->
      <button @click="toggleDarkMode" class="theme-toggle-button d-lg-none fixed-toggle" aria-label="Toggle theme">
        <div class="sun-moon-container">
          <div class="sun" :class="{ 'sun-hidden': isDarkMode }">
            <div class="sun-core"></div>
            <div class="sun-ray"></div>
            <div class="sun-ray"></div>
            <div class="sun-ray"></div>
            <div class="sun-ray"></div>
            <div class="sun-ray"></div>
            <div class="sun-ray"></div>
            <div class="sun-ray"></div>
            <div class="sun-ray"></div>
          </div>
          <div class="moon" :class="{ 'moon-visible': isDarkMode }">
            <div class="moon-crater"></div>
            <div class="moon-crater"></div>
            <div class="moon-crater"></div>
          </div>
        </div>
        <span class="toggle-label" :class="{ 'toggle-label-visible': true }">
          
        </span>
      </button>
      
      <div class="collapse navbar-collapse" :class="{ 'show': !navbarCollapsed }" id="navbarNav">
        <ul class="navbar-nav me-auto">
          <li class="nav-item">
            <RouterLink class="nav-link" to="/" @click="navbarCollapsed = true">
              <i class="fas fa-home me-1"></i> Home
            </RouterLink>
          </li>
          <li class="nav-item">
            <RouterLink class="nav-link" to="/rooms" @click="navbarCollapsed = true">
              <i class="fas fa-bed me-1"></i> Rooms
            </RouterLink>
          </li>
          <li class="nav-item" v-if="authStore.isAuthenticated">
            <RouterLink class="nav-link" to="/bookings" @click="navbarCollapsed = true">
              <i class="fas fa-calendar-alt me-1"></i> My Bookings
            </RouterLink>
          </li>
          <li class="nav-item" v-if="authStore.isEmployee">
            <RouterLink class="nav-link" to="/employee" @click="navbarCollapsed = true">
              🏨 Dashboard
            </RouterLink>
          </li>
          <li class="nav-item">
            <RouterLink class="nav-link" to="/about" @click="navbarCollapsed = true">
              <i class="fas fa-info-circle me-1"></i> About
            </RouterLink>
          </li>
        </ul>
        
        <div class="navbar-nav ms-auto">
          <template v-if="authStore.isAuthenticated">
            <RouterLink class="nav-link" to="/profile" @click="navbarCollapsed = true">
              <i class="fas fa-user me-1"></i> Profile
            </RouterLink>
            <a href="#" class="nav-link" @click.prevent="authStore.logout(); navbarCollapsed = true">
              <i class="fas fa-sign-out-alt me-1"></i> Logout
            </a>
          </template>
          <template v-else>
            <RouterLink class="nav-link" to="/login" @click="navbarCollapsed = true">
              <i class="fas fa-sign-in-alt me-1"></i> Login
            </RouterLink>
            <RouterLink class="nav-link" to="/register" @click="navbarCollapsed = true">
              <i class="fas fa-user-plus me-1"></i> Register
            </RouterLink>
          </template>
          
          <!-- Theme toggle button (desktop only) -->
          <button @click="toggleDarkMode" class="theme-toggle-button ms-3 d-none d-lg-flex" aria-label="Toggle theme">
            <div class="sun-moon-container">
              <div class="sun" :class="{ 'sun-hidden': isDarkMode }">
                <div class="sun-core"></div>
                <div class="sun-ray"></div>
                <div class="sun-ray"></div>
                <div class="sun-ray"></div>
                <div class="sun-ray"></div>
                <div class="sun-ray"></div>
                <div class="sun-ray"></div>
                <div class="sun-ray"></div>
                <div class="sun-ray"></div>
              </div>
              <div class="moon" :class="{ 'moon-visible': isDarkMode }">
                <div class="moon-crater"></div>
                <div class="moon-crater"></div>
                <div class="moon-crater"></div>
              </div>
            </div>
          </button>
        </div>
      </div>
    </div>
  </nav>
  
  <div class="app-container">
    <main>
      <RouterView v-slot="{ Component }">
        <template v-if="Component">
          <Suspense>
            <component :is="Component" />
            <template #fallback>
              <div class="container py-5 text-center">
                <div class="spinner-border text-primary" role="status">
                  <span class="visually-hidden">Loading...</span>
                </div>
                <p class="mt-3">Loading content...</p>
              </div>
            </template>
          </Suspense>
        </template>
      </RouterView>
    </main>
    
    <footer class="footer mt-auto py-4">
      <div class="container">
        <div class="row">
          <div class="col-md-6">
            <h5 class="mb-3">Hotel Lebedivka</h5>
            <p>Experience luxury and comfort in the heart of Ukraine. Your perfect getaway awaits!</p>
          </div>
          <div class="col-md-3">
            <h5 class="mb-3">Links</h5>
            <ul class="list-unstyled">
              <li><RouterLink to="/about">About Us</RouterLink></li>
              <li><RouterLink to="/rooms">Our Rooms</RouterLink></li>
              <li><a href="#privacy">Privacy Policy</a></li>
              <li><a href="#terms">Terms of Service</a></li>
            </ul>
          </div>
          <div class="col-md-3">
            <h5 class="mb-3">Connect</h5>
            <div class="social-links">
              <a href="#" class="me-2" aria-label="Facebook"><i class="fab fa-facebook-f"></i></a>
              <a href="#" class="me-2" aria-label="Instagram"><i class="fab fa-instagram"></i></a>
              <a href="#" class="me-2" aria-label="TripAdvisor"><i class="fab fa-tripadvisor"></i></a>
              <a href="#" aria-label="Email"><i class="fas fa-envelope"></i></a>
            </div>
          </div>
        </div>
        <hr>
        <div class="text-center">
          <p class="mb-0">&copy; {{ new Date().getFullYear() }} Hotel Lebedivka. All rights reserved.</p>
        </div>
      </div>
    </footer>
  </div>
</template>

<style>
/* Import organized CSS files */
@import '@/assets/main.css';
</style>
