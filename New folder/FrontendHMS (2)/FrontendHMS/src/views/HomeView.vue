<script setup lang="ts">
import { onMounted } from 'vue'
import { useAuthStore } from '@/stores/auth'

const authStore = useAuthStore()

onMounted(() => {
  // Initialize auth store when app loads
  authStore.initialize()
})
</script>

<template>
  <main>
    <!-- Hero Section -->
    <section class="hero">
      <div class="hero-content">
        <h1>Welcome to Hotel Lebedivka</h1>
        <p>Experience luxury and comfort in the heart of Ukraine</p>
        <div class="hero-actions">
          <router-link to="/bookings" class="btn btn-primary">Book Now</router-link>
          <router-link to="/about" class="btn btn-secondary">Learn More</router-link>
        </div>
      </div>
    </section>

    <!-- Features Section -->
    <section class="features">
      <div class="container">
        <h2>Why Choose Hotel Lebedivka?</h2>
        <div class="features-grid">
          <div class="feature-card">
            <div class="feature-icon">🏖️</div>
            <h3>Beach Access</h3>
            <p>Direct access to our private sandy beach with crystal clear waters</p>
          </div>
          <div class="feature-card">
            <div class="feature-icon">🍽️</div>
            <h3>Fine Dining</h3>
            <p>Exquisite cuisine prepared by our world-class chefs</p>
          </div>
          <div class="feature-card">
            <div class="feature-icon">💆</div>
            <h3>Spa & Wellness</h3>
            <p>Relax and rejuvenate with our premium spa services</p>
          </div>
          <div class="feature-card">
            <div class="feature-icon">🚗</div>
            <h3>Free Parking</h3>
            <p>Complimentary on-site parking for all guests</p>
          </div>
        </div>
      </div>
    </section>

    <!-- Room Types Section -->
    <section class="room-types">
      <div class="container">
        <h2>Our Rooms</h2>
        <div class="rooms-grid">
          <div class="room-card">
            <div class="room-image">
              <div class="room-placeholder">Standard Room</div>
            </div>
            <div class="room-info">
              <h3>Standard Room</h3>
              <p>Comfortable accommodation with essential amenities</p>
              <div class="room-features">
                <span>25m²</span>
                <span>2 Guests</span>
                <span>Wi-Fi</span>
              </div>
              <div class="room-price">From €80/night</div>
            </div>
          </div>
          <div class="room-card">
            <div class="room-image">
              <div class="room-placeholder">Deluxe Room</div>
            </div>
            <div class="room-info">
              <h3>Deluxe Room</h3>
              <p>Spacious room with enhanced amenities and sea view</p>
              <div class="room-features">
                <span>35m²</span>
                <span>2 Guests</span>
                <span>Sea View</span>
              </div>
              <div class="room-price">From €120/night</div>
            </div>
          </div>
          <div class="room-card">
            <div class="room-image">
              <div class="room-placeholder">Premium Suite</div>
            </div>
            <div class="room-info">
              <h3>Premium Suite</h3>
              <p>Luxury suite with kitchenette and panoramic views</p>
              <div class="room-features">
                <span>65m²</span>
                <span>4 Guests</span>
                <span>Kitchenette</span>
              </div>
              <div class="room-price">From €200/night</div>
            </div>
          </div>
        </div>
      </div>
    </section>
  </main>
</template>

<style scoped>
.hero {
  background: linear-gradient(rgba(0,0,0,0.4), rgba(0,0,0,0.4)), 
              url('data:image/svg+xml,<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 1200 600"><rect fill="%23667eea" width="1200" height="600"/><path fill="%23764ba2" d="M0,300 Q300,100 600,300 T1200,300 L1200,600 L0,600 Z"/></svg>');
  background-size: cover;
  background-position: center;
  height: 100vh;
  display: flex;
  align-items: center;
  justify-content: center;
  text-align: center;
  color: white;
}

.hero-content h1 {
  font-size: 3.5rem;
  margin-bottom: 1rem;
  font-weight: 300;
}

.hero-content p {
  font-size: 1.5rem;
  margin-bottom: 2rem;
  opacity: 0.9;
}

.hero-actions {
  display: flex;
  gap: 1rem;
  justify-content: center;
  flex-wrap: wrap;
}

.btn {
  padding: 1rem 2rem;
  text-decoration: none;
  border-radius: 5px;
  font-weight: 500;
  transition: transform 0.3s ease, box-shadow 0.3s ease;
  display: inline-block;
}

.btn:hover {
  transform: translateY(-2px);
  box-shadow: 0 10px 20px rgba(0,0,0,0.2);
}

.btn-primary {
  background: #667eea;
  color: white;
}

.btn-secondary {
  background: transparent;
  color: white;
  border: 2px solid white;
}

.features, .room-types {
  padding: 4rem 0;
}

.container {
  max-width: 1200px;
  margin: 0 auto;
  padding: 0 2rem;
}

.features h2, .room-types h2 {
  text-align: center;
  margin-bottom: 3rem;
  font-size: 2.5rem;
  color: #333;
}

.features-grid {
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
  gap: 2rem;
}

.feature-card {
  text-align: center;
  padding: 2rem;
  border-radius: 10px;
  box-shadow: 0 5px 15px rgba(0,0,0,0.1);
  transition: transform 0.3s ease;
}

.feature-card:hover {
  transform: translateY(-5px);
}

.feature-icon {
  font-size: 3rem;
  margin-bottom: 1rem;
}

.feature-card h3 {
  margin-bottom: 1rem;
  color: #333;
}

.feature-card p {
  color: #666;
  line-height: 1.6;
}

.room-types {
  background: #f8f9fa;
}

.rooms-grid {
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(350px, 1fr));
  gap: 2rem;
}

.room-card {
  background: white;
  border-radius: 10px;
  overflow: hidden;
  box-shadow: 0 5px 15px rgba(0,0,0,0.1);
  transition: transform 0.3s ease;
}

.room-card:hover {
  transform: translateY(-5px);
}

.room-image {
  height: 200px;
  background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
  display: flex;
  align-items: center;
  justify-content: center;
}

.room-placeholder {
  color: white;
  font-size: 1.5rem;
  font-weight: 500;
}

.room-info {
  padding: 1.5rem;
}

.room-info h3 {
  margin-bottom: 0.5rem;
  color: #333;
}

.room-info p {
  color: #666;
  margin-bottom: 1rem;
  line-height: 1.6;
}

.room-features {
  display: flex;
  gap: 1rem;
  margin-bottom: 1rem;
  flex-wrap: wrap;
}

.room-features span {
  background: #f0f2f5;
  padding: 0.25rem 0.5rem;
  border-radius: 3px;
  font-size: 0.85rem;
  color: #555;
}

.room-price {
  font-size: 1.25rem;
  font-weight: 600;
  color: #667eea;
}

@media (max-width: 768px) {
  .hero-content h1 {
    font-size: 2.5rem;
  }
  
  .hero-content p {
    font-size: 1.2rem;
  }
  
  .hero-actions {
    flex-direction: column;
    align-items: center;
  }
  
  .features-grid {
    grid-template-columns: 1fr;
  }
  
  .rooms-grid {
    grid-template-columns: 1fr;
  }
}
</style>
