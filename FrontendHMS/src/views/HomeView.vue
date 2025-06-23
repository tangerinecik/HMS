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
      <div class="hero-waves">
        <div class="wave wave-1"></div>
        <div class="wave wave-2"></div>
        <div class="wave wave-3"></div>
      </div>
      <div class="hero-content">
        <div class="hero-badge">🌊 Ukraine's Hidden Paradise</div>
        <h1 class="hero-title">
          <span class="title-main">Hotel Lebedivka</span>
          <span class="title-sub">Where Silence Meets the Sea</span>
        </h1>
        <p class="hero-description">
          Escape to absolute tranquility. No sirens, no crowds, no restrictions. 
          Just pristine beaches, peaceful nature, and the gentle sound of waves.
        </p>

        <div class="hero-actions">
          <router-link to="/rooms" class="btn btn-primary">
            <span>Discover Our Rooms</span>
            <i class="fas fa-arrow-right"></i>
          </router-link>
          <router-link to="/about" class="btn btn-secondary">
            <span>Learn More</span>
          </router-link>
        </div>
      </div>
      <div class="hero-scroll-indicator">
        <div class="scroll-arrow"></div>
        <span>Scroll to explore</span>
      </div>
    </section>

    <!-- Unique Selling Points Section -->
    <section class="tranquility-section">
      <div class="container">
        <div class="section-header">
          <h2>Why Ukraine Chooses Lebedivka</h2>
          <p>Experience what makes us the perfect escape from city life</p>
        </div>
        <div class="tranquility-grid">
          <div class="tranquility-card featured">
            <div class="card-icon">🔇</div>
            <h3>Complete Silence</h3>
            <p>No police sirens, no traffic noise, no city chaos. Only the gentle sounds of nature and waves.</p>
          </div>
          <div class="tranquility-card">
            <div class="card-icon">🌙</div>
            <h3>No Curfews</h3>
            <p>Enjoy complete freedom with no restrictions. Watch the sunset, stargaze, or take midnight walks on the beach.</p>
          </div>
          <div class="tranquility-card">
            <div class="card-icon">👥</div>
            <h3>Uncrowded Paradise</h3>
            <p>Our private beach means space to breathe. No crowds, no rush - just you and the endless horizon.</p>
          </div>
          <div class="tranquility-card">
            <div class="card-icon">🐾</div>
            <h3>Nature & Cats</h3>
            <p>Friendly local cats and untouched nature create the perfect peaceful atmosphere you've been seeking.</p>
          </div>
        </div>
      </div>
    </section>

    <!-- Features Section -->
    <section class="features-section">
      <div class="container">
        <div class="section-header">
          <h2>Pure Luxury in Nature's Embrace</h2>
          <p>Every amenity designed for your complete relaxation</p>
        </div>
        <div class="features-grid">
          <div class="feature-card">
            <div class="feature-visual">
              <div class="feature-icon">🏖️</div>
              <div class="feature-glow"></div>
            </div>
            <div class="feature-content">
              <h3>Private Beach Access</h3>
              <p>Step directly onto our exclusive sandy beach with crystal-clear waters and complete privacy.</p>
            </div>
          </div>
          <div class="feature-card">
            <div class="feature-visual">
              <div class="feature-icon">🍽️</div>
              <div class="feature-glow"></div>
            </div>
            <div class="feature-content">
              <h3>Gourmet Dining</h3>
              <p>Savor exquisite Ukrainian and international cuisine prepared by our talented chefs.</p>
            </div>
          </div>
          <div class="feature-card">
            <div class="feature-visual">
              <div class="feature-icon">💆‍♀️</div>
              <div class="feature-glow"></div>
            </div>
            <div class="feature-content">
              <h3>Spa & Wellness</h3>
              <p>Rejuvenate with premium spa treatments and wellness services in our tranquil setting.</p>
            </div>
          </div>
          <div class="feature-card">
            <div class="feature-visual">
              <div class="feature-icon">🚗</div>
              <div class="feature-glow"></div>
            </div>
            <div class="feature-content">
              <h3>Complimentary Parking</h3>
              <p>Secure, free parking for all guests with easy access to your accommodations.</p>
            </div>
          </div>
        </div>
      </div>
    </section>

    <!-- Room Types Section -->
    <section class="rooms-showcase">
      <div class="container">
        <div class="section-header">
          <h2>Your Perfect Retreat Awaits</h2>
          <p>Choose from our carefully designed accommodations</p>
        </div>
        <div class="rooms-grid">
          <div class="room-card">
            <div class="room-image">
              <div class="room-overlay">
                <div class="room-badge">Most Popular</div>
              </div>
              <div class="room-placeholder">
                <div class="room-visual">
                  <span class="room-icon">🏨</span>
                  <div class="room-shimmer"></div>
                </div>
              </div>
            </div>
            <div class="room-info">
              <div class="room-header">
                <h3>Hotel Standard Room</h3>
                <div class="room-rating">
                  <span class="stars">★★★★☆</span>
                </div>
              </div>
              <p>Comfortable accommodation with essential amenities and serene garden views</p>
              <div class="room-features">
                <span class="feature-tag">
                  <i class="fas fa-expand"></i>
                  25m²
                </span>
                <span class="feature-tag">
                  <i class="fas fa-users"></i>
                  2 Guests
                </span>
                <span class="feature-tag">
                  <i class="fas fa-wifi"></i>
                  Free Wi-Fi
                </span>
                <span class="feature-tag">
                  <i class="fas fa-leaf"></i>
                  Garden View
                </span>
              </div>
              <div class="room-footer">
                <div class="room-price">
                  <span class="price-from">From</span>
                  <span class="price-amount">€80</span>
                  <span class="price-period">/night</span>
                </div>
                <router-link to="/rooms" class="room-cta">
                  View Details
                  <i class="fas fa-arrow-right"></i>
                </router-link>
              </div>
            </div>
          </div>

          <div class="room-card featured">
            <div class="room-image">
              <div class="room-overlay">
                <div class="room-badge premium">Premium</div>
              </div>
              <div class="room-placeholder deluxe">
                <div class="room-visual">
                  <span class="room-icon">🌊</span>
                  <div class="room-shimmer"></div>
                </div>
              </div>
            </div>
            <div class="room-info">
              <div class="room-header">
                <h3>Hotel Deluxe Room</h3>
                <div class="room-rating">
                  <span class="stars">★★★★★</span>
                </div>
              </div>
              <p>Spacious room with enhanced amenities and breathtaking sea views</p>
              <div class="room-features">
                <span class="feature-tag">
                  <i class="fas fa-expand"></i>
                  35m²
                </span>
                <span class="feature-tag">
                  <i class="fas fa-users"></i>
                  2 Guests
                </span>
                <span class="feature-tag">
                  <i class="fas fa-water"></i>
                  Sea View
                </span>
                <span class="feature-tag">
                  <i class="fas fa-cocktail"></i>
                  Mini Bar
                </span>
              </div>
              <div class="room-footer">
                <div class="room-price">
                  <span class="price-from">From</span>
                  <span class="price-amount">€120</span>
                  <span class="price-period">/night</span>
                </div>
                <router-link to="/rooms" class="room-cta">
                  View Details
                  <i class="fas fa-arrow-right"></i>
                </router-link>
              </div>
            </div>
          </div>

          <div class="room-card">
            <div class="room-image">
              <div class="room-overlay">
                <div class="room-badge luxury">Luxury</div>
              </div>
              <div class="room-placeholder cabin">
                <div class="room-visual">
                  <span class="room-icon">🏕️</span>
                  <div class="room-shimmer"></div>
                </div>
              </div>
            </div>
            <div class="room-info">
              <div class="room-header">
                <h3>Private Cabin</h3>
                <div class="room-rating">
                  <span class="stars">★★★★★</span>
                </div>
              </div>
              <p>Ultimate privacy with kitchenette and panoramic nature views</p>
              <div class="room-features">
                <span class="feature-tag">
                  <i class="fas fa-expand"></i>
                  65m²
                </span>
                <span class="feature-tag">
                  <i class="fas fa-users"></i>
                  4 Guests
                </span>
                <span class="feature-tag">
                  <i class="fas fa-utensils"></i>
                  Kitchenette
                </span>
                <span class="feature-tag">
                  <i class="fas fa-tree"></i>
                  Nature View
                </span>
              </div>
              <div class="room-footer">
                <div class="room-price">
                  <span class="price-from">From</span>
                  <span class="price-amount">€200</span>
                  <span class="price-period">/night</span>
                </div>
                <router-link to="/rooms" class="room-cta">
                  View Details
                  <i class="fas fa-arrow-right"></i>
                </router-link>
              </div>
            </div>
          </div>
        </div>
      </div>
    </section>

    <!-- Call to Action Section -->
    <section class="cta-section">
      <div class="cta-waves">
        <div class="wave wave-1"></div>
        <div class="wave wave-2"></div>
      </div>
      <div class="container">
        <div class="cta-content">
          <h2>Ready for Your Peaceful Escape?</h2>
          <p>Join thousands of Ukrainians who have found their perfect retreat at Hotel Lebedivka</p>
          <div class="cta-actions">
            <router-link to="/rooms" class="btn btn-primary large">
              <span>Book Your Stay</span>
              <i class="fas fa-calendar-check"></i>
            </router-link>
            <router-link to="/about" class="btn btn-outline large">
              <span>Contact Us</span>
              <i class="fas fa-phone"></i>
            </router-link>
          </div>
        </div>
      </div>
    </section>
  </main>
</template>

<style scoped>
/* Hero Section */
.hero {
  position: relative;
  height: 100vh;
  background: linear-gradient(
    135deg,
    rgba(14, 165, 233, 0.9) 0%,
    rgba(59, 130, 246, 0.8) 50%,
    rgba(99, 102, 241, 0.9) 100%
  ),
  linear-gradient(
    45deg,
    var(--ocean-blue) 0%,
    var(--ocean-deep) 100%
  );
  display: flex;
  align-items: center;
  justify-content: center;
  text-align: center;
  color: white;
  overflow: hidden;
}

.hero-waves {
  position: absolute;
  bottom: 0;
  left: 0;
  width: 100%;
  height: 150px;
  z-index: 1;
}

.wave {
  position: absolute;
  bottom: 0;
  left: 0;
  width: 100%;
  height: 100px;
  background: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 1200 120'%3E%3Cpath d='M0,50 Q300,100 600,50 T1200,50 L1200,120 L0,120 Z' fill='white' fill-opacity='0.1'/%3E%3C/svg%3E") repeat-x;
  animation: wave 3s ease-in-out infinite;
}

.wave-1 {
  animation-delay: 0s;
  opacity: 0.7;
}

.wave-2 {
  animation-delay: -1s;
  opacity: 0.5;
  height: 80px;
}

.wave-3 {
  animation-delay: -2s;
  opacity: 0.3;
  height: 60px;
}

@keyframes wave {
  0%, 100% { transform: translateX(0); }
  50% { transform: translateX(-25px); }
}

.hero-content {
  position: relative;
  z-index: 2;
  max-width: 800px;
  padding: 0 2rem;
}

.hero-badge {
  display: inline-block;
  background: rgba(255, 255, 255, 0.2);
  padding: 0.5rem 1.5rem;
  border-radius: 50px;
  font-size: 0.9rem;
  font-weight: 500;
  margin-bottom: 2rem;
  backdrop-filter: blur(10px);
  border: 1px solid rgba(255, 255, 255, 0.3);
}

.hero-title {
  margin-bottom: 1.5rem;
}

.title-main {
  display: block;
  font-size: 4rem;
  font-weight: 700;
  font-family: 'Playfair Display', serif;
  margin-bottom: 0.5rem;
  background: linear-gradient(45deg, #ffffff, #f0f9ff);
  background-clip: text;
  -webkit-background-clip: text;
  -webkit-text-fill-color: transparent;
  text-shadow: 0 4px 20px rgba(0, 0, 0, 0.3);
}

.title-sub {
  display: block;
  font-size: 1.5rem;
  font-weight: 300;
  opacity: 0.9;
  font-style: italic;
}

.hero-description {
  font-size: 1.3rem;
  line-height: 1.6;
  margin-bottom: 3rem;
  opacity: 0.95;
  max-width: 600px;
  margin-left: auto;
  margin-right: auto;
}

.hero-features {
  display: flex;
  justify-content: center;
  gap: 2rem;
  margin-bottom: 3rem;
  flex-wrap: wrap;
}

.hero-feature {
  display: flex;
  align-items: center;
  gap: 0.5rem;
  background: rgba(255, 255, 255, 0.15);
  padding: 0.75rem 1.5rem;
  border-radius: 25px;
  backdrop-filter: blur(10px);
  border: 1px solid rgba(255, 255, 255, 0.2);
  font-weight: 500;
}

.feature-icon {
  font-size: 1.2rem;
}

.hero-actions {
  display: flex;
  gap: 1.5rem;
  justify-content: center;
  flex-wrap: wrap;
}

.btn {
  display: inline-flex;
  align-items: center;
  gap: 0.75rem;
  padding: 1rem 2rem;
  text-decoration: none;
  border-radius: 50px;
  font-weight: 600;
  font-size: 1.1rem;
  transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
  position: relative;
  overflow: hidden;
}

.btn::before {
  content: '';
  position: absolute;
  top: 0;
  left: -100%;
  width: 100%;
  height: 100%;
  background: linear-gradient(90deg, transparent, rgba(255, 255, 255, 0.2), transparent);
  transition: left 0.5s;
}

.btn:hover::before {
  left: 100%;
}

.btn-primary {
  background: linear-gradient(135deg, rgba(255, 255, 255, 0.2), rgba(255, 255, 255, 0.1));
  color: white;
  border: 2px solid rgba(255, 255, 255, 0.3);
  backdrop-filter: blur(10px);
}

.btn-primary:hover {
  transform: translateY(-3px);
  box-shadow: 0 15px 30px rgba(0, 0, 0, 0.2);
  border-color: rgba(255, 255, 255, 0.5);
}

.btn-secondary {
  background: transparent;
  color: white;
  border: 2px solid rgba(255, 255, 255, 0.5);
}

.btn-secondary:hover {
  background: rgba(255, 255, 255, 0.1);
  transform: translateY(-3px);
  box-shadow: 0 15px 30px rgba(0, 0, 0, 0.2);
}

.hero-scroll-indicator {
  position: absolute;
  bottom: 2rem;
  left: 50%;
  transform: translateX(-50%);
  display: flex;
  flex-direction: column;
  align-items: center;
  color: white;
  opacity: 0.7;
  z-index: 2;
}

.scroll-arrow {
  width: 2px;
  height: 30px;
  background: white;
  position: relative;
  margin-bottom: 0.5rem;
  animation: scroll-bounce 2s infinite;
}

.scroll-arrow::after {
  content: '';
  position: absolute;
  bottom: 0;
  left: -3px;
  width: 8px;
  height: 8px;
  border-bottom: 2px solid white;
  border-right: 2px solid white;
  transform: rotate(45deg);
}

@keyframes scroll-bounce {
  0%, 100% { transform: translateY(0); }
  50% { transform: translateY(10px); }
}

/* Tranquility Section */
.tranquility-section {
  padding: 8rem 0;
  background: linear-gradient(180deg, #ffffff 0%, #f8fafc 100%);
}

.section-header {
  text-align: center;
  margin-bottom: 4rem;
}

.section-header h2 {
  font-size: 3rem;
  font-weight: 700;
  color: var(--text-color);
  margin-bottom: 1rem;
  font-family: 'Playfair Display', serif;
}

.section-header p {
  font-size: 1.3rem;
  color: var(--text-secondary);
  max-width: 600px;
  margin: 0 auto;
}

.container {
  max-width: 1200px;
  margin: 0 auto;
  padding: 0 2rem;
}

.tranquility-grid {
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));
  gap: 2rem;
}

.tranquility-card {
  background: white;
  padding: 3rem 2rem;
  border-radius: 20px;
  text-align: center;
  box-shadow: 0 10px 40px rgba(0, 0, 0, 0.1);
  transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
  position: relative;
  overflow: hidden;
}

.tranquility-card::before {
  content: '';
  position: absolute;
  top: 0;
  left: 0;
  right: 0;
  height: 4px;
  background: linear-gradient(90deg, var(--ocean-blue), var(--seafoam));
  transform: scaleX(0);
  transition: transform 0.3s ease;
}

.tranquility-card:hover {
  transform: translateY(-10px);
  box-shadow: 0 20px 60px rgba(0, 0, 0, 0.15);
}

.tranquility-card:hover::before {
  transform: scaleX(1);
}

.tranquility-card.featured {
  background: linear-gradient(135deg, var(--ocean-blue), var(--ocean-deep));
  color: white;
}

.tranquility-card.featured::before {
  background: linear-gradient(90deg, #ffffff, #f0f9ff);
}

.card-icon {
  font-size: 4rem;
  margin-bottom: 1.5rem;
  display: block;
}

.tranquility-card h3 {
  font-size: 1.5rem;
  font-weight: 600;
  margin-bottom: 1rem;
  color: inherit;
}

.tranquility-card p {
  line-height: 1.6;
  color: inherit;
  opacity: 0.9;
}

/* Features Section */
.features-section {
  padding: 8rem 0;
  background: var(--background-gradient);
}

.features-grid {
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
  gap: 3rem;
}

.feature-card {
  background: var(--card-bg);
  padding: 3rem 2rem;
  border-radius: 24px;
  text-align: center;
  box-shadow: var(--shadow-lg);
  transition: all 0.4s cubic-bezier(0.4, 0, 0.2, 1);
  position: relative;
  border: 1px solid var(--border-color);
}

.feature-card:hover {
  transform: translateY(-15px);
  box-shadow: var(--shadow-xl);
}

.feature-visual {
  position: relative;
  margin-bottom: 2rem;
  height: 80px;
  display: flex;
  align-items: center;
  justify-content: center;
}

.feature-visual .feature-icon {
  font-size: 3rem;
  z-index: 2;
  position: relative;
}

.feature-glow {
  position: absolute;
  top: 50%;
  left: 50%;
  transform: translate(-50%, -50%);
  width: 100px;
  height: 100px;
  background: radial-gradient(circle, var(--primary-color) 0%, transparent 70%);
  opacity: 0.2;
  border-radius: 50%;
  transition: all 0.3s ease;
}

.feature-card:hover .feature-glow {
  opacity: 0.4;
  transform: translate(-50%, -50%) scale(1.2);
}

.feature-content h3 {
  font-size: 1.5rem;
  font-weight: 600;
  margin-bottom: 1rem;
  color: var(--text-color);
}

.feature-content p {
  line-height: 1.6;
  color: var(--text-secondary);
}

/* Rooms Showcase */
.rooms-showcase {
  padding: 8rem 0;
  background: linear-gradient(180deg, #f8fafc 0%, #ffffff 100%);
}

.rooms-grid {
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(380px, 1fr));
  gap: 3rem;
}

.room-card {
  background: var(--card-bg);
  border-radius: 24px;
  overflow: hidden;
  box-shadow: var(--shadow-lg);
  transition: all 0.4s cubic-bezier(0.4, 0, 0.2, 1);
  position: relative;
  border: 1px solid var(--border-color);
}

.room-card:hover {
  transform: translateY(-10px);
  box-shadow: var(--shadow-xl);
}

.room-card.featured {
  border: 2px solid var(--primary-color);
  box-shadow: 0 20px 60px rgba(14, 165, 233, 0.2);
}

.room-image {
  height: 240px;
  position: relative;
  overflow: hidden;
}

.room-overlay {
  position: absolute;
  top: 1rem;
  right: 1rem;
  z-index: 3;
}

.room-badge {
  background: var(--ocean-blue);
  color: white;
  padding: 0.5rem 1rem;
  border-radius: 20px;
  font-size: 0.8rem;
  font-weight: 600;
}

.room-badge.premium {
  background: linear-gradient(135deg, #fbbf24, #f59e0b);
}

.room-badge.luxury {
  background: linear-gradient(135deg, #8b5cf6, #7c3aed);
}

.room-placeholder {
  height: 100%;
  background: linear-gradient(135deg, var(--ocean-blue) 0%, var(--ocean-deep) 100%);
  display: flex;
  align-items: center;
  justify-content: center;
  position: relative;
  overflow: hidden;
}

.room-placeholder.deluxe {
  background: linear-gradient(135deg, #06b6d4 0%, #0891b2 100%);
}

.room-placeholder.cabin {
  background: linear-gradient(135deg, #10b981 0%, #059669 100%);
}

.room-visual {
  display: flex;
  align-items: center;
  justify-content: center;
  position: relative;
  z-index: 2;
}

.room-icon {
  font-size: 4rem;
  color: white;
  opacity: 0.9;
}

.room-shimmer {
  position: absolute;
  top: -50%;
  left: -50%;
  width: 200%;
  height: 200%;
  background: linear-gradient(45deg, transparent, rgba(255, 255, 255, 0.1), transparent);
  animation: shimmer 3s infinite;
}

@keyframes shimmer {
  0% { transform: translateX(-100%) translateY(-100%) rotate(45deg); }
  100% { transform: translateX(100%) translateY(100%) rotate(45deg); }
}

.room-info {
  padding: 2rem;
}

.room-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
  margin-bottom: 1rem;
}

.room-header h3 {
  font-size: 1.4rem;
  font-weight: 600;
  color: var(--text-color);
  margin: 0;
}

.room-rating .stars {
  color: #fbbf24;
  font-size: 1rem;
}

.room-info p {
  color: var(--text-secondary);
  line-height: 1.6;
  margin-bottom: 1.5rem;
}

.room-features {
  display: flex;
  gap: 0.75rem;
  margin-bottom: 2rem;
  flex-wrap: wrap;
}

.feature-tag {
  display: flex;
  align-items: center;
  gap: 0.5rem;
  background: var(--primary-bg);
  color: var(--primary-color);
  padding: 0.5rem 0.75rem;
  border-radius: 20px;
  font-size: 0.85rem;
  font-weight: 500;
}

.room-footer {
  display: flex;
  justify-content: space-between;
  align-items: center;
}

.room-price {
  display: flex;
  align-items: baseline;
  gap: 0.25rem;
}

.price-from {
  font-size: 0.9rem;
  color: var(--text-muted);
}

.price-amount {
  font-size: 2rem;
  font-weight: 700;
  color: var(--primary-color);
}

.price-period {
  font-size: 0.9rem;
  color: var(--text-muted);
}

.room-cta {
  display: flex;
  align-items: center;
  gap: 0.5rem;
  color: var(--primary-color);
  text-decoration: none;
  font-weight: 600;
  padding: 0.75rem 1.5rem;
  border: 2px solid var(--primary-color);
  border-radius: 25px;
  transition: all 0.3s ease;
}

.room-cta:hover {
  background: var(--primary-color);
  color: white;
  transform: translateX(5px);
}

/* CTA Section */
.cta-section {
  padding: 8rem 0;
  background: linear-gradient(135deg, var(--ocean-blue) 0%, var(--ocean-deep) 100%);
  position: relative;
  overflow: hidden;
  color: white;
  text-align: center;
}

.cta-waves {
  position: absolute;
  top: 0;
  left: 0;
  width: 100%;
  height: 100px;
  z-index: 1;
}

.cta-waves .wave {
  background: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 1200 120'%3E%3Cpath d='M0,50 Q300,0 600,50 T1200,50 L1200,0 L0,0 Z' fill='white' fill-opacity='0.1'/%3E%3C/svg%3E") repeat-x;
  top: 0;
  bottom: auto;
}

.cta-content {
  position: relative;
  z-index: 2;
}

.cta-content h2 {
  font-size: 3rem;
  font-weight: 700;
  margin-bottom: 1.5rem;
  font-family: 'Playfair Display', serif;
}

.cta-content p {
  font-size: 1.3rem;
  margin-bottom: 3rem;
  opacity: 0.9;
  max-width: 600px;
  margin-left: auto;
  margin-right: auto;
}

.cta-actions {
  display: flex;
  gap: 2rem;
  justify-content: center;
  flex-wrap: wrap;
}

.btn.large {
  padding: 1.25rem 2.5rem;
  font-size: 1.2rem;
}

.btn-outline {
  background: transparent;
  color: white;
  border: 2px solid rgba(255, 255, 255, 0.5);
}

.btn-outline:hover {
  background: rgba(255, 255, 255, 0.1);
  border-color: white;
}

/* Dark Mode Support */
[data-theme="dark"] .tranquility-section {
  background: var(--background-gradient);
}

[data-theme="dark"] .features-section {
  background: var(--background-gradient);
}

[data-theme="dark"] .rooms-showcase {
  background: var(--background-gradient);
}

[data-theme="dark"] .section-header h2 {
  color: var(--text-color);
}

[data-theme="dark"] .section-header p {
  color: var(--text-secondary);
}

[data-theme="dark"] .tranquility-card {
  background: var(--card-bg);
  color: var(--text-color);
}

[data-theme="dark"] .tranquility-card p {
  color: var(--text-secondary);
}

[data-theme="dark"] .feature-card {
  background: var(--card-bg);
  border-color: var(--border-color);
}

[data-theme="dark"] .feature-content h3 {
  color: var(--text-color);
}

[data-theme="dark"] .feature-content p {
  color: var(--text-secondary);
}

[data-theme="dark"] .room-card {
  background: var(--card-bg);
  border-color: var(--border-color);
}

[data-theme="dark"] .room-header h3 {
  color: var(--text-color);
}

[data-theme="dark"] .room-info p {
  color: var(--text-secondary);
}

/* Responsive Design */
@media (max-width: 768px) {
  .title-main {
    font-size: 2.8rem;
  }
  
  .title-sub {
    font-size: 1.2rem;
  }
  
  .hero-description {
    font-size: 1.1rem;
  }
  
  .hero-features {
    flex-direction: column;
    align-items: center;
  }
  
  .hero-actions {
    flex-direction: column;
    align-items: center;
  }
  
  .section-header h2 {
    font-size: 2.2rem;
  }
  
  .section-header p {
    font-size: 1.1rem;
  }
  
  .tranquility-grid,
  .features-grid,
  .rooms-grid {
    grid-template-columns: 1fr;
  }
  
  .room-card {
    max-width: 400px;
    margin: 0 auto;
  }
  
  .cta-content h2 {
    font-size: 2.2rem;
  }
  
  .cta-actions {
    flex-direction: column;
    align-items: center;
  }
}

@media (max-width: 480px) {
  .hero {
    padding: 0 1rem;
  }
  
  .title-main {
    font-size: 2.2rem;
  }
  
  .container {
    padding: 0 1rem;
  }
  
  .tranquility-card,
  .feature-card {
    padding: 2rem 1.5rem;
  }
  
  .room-info {
    padding: 1.5rem;
  }
}
</style>
