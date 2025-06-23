import { createRouter, createWebHistory } from 'vue-router'
import { useAuthStore } from '@/stores/auth'
import HomeView from '../views/HomeView.vue'

const router = createRouter({
  history: createWebHistory(import.meta.env.BASE_URL),
  routes: [
    {
      path: '/',
      name: 'home',
      component: HomeView,
    },
    {
      path: '/about',
      name: 'about',
      component: () => import('../views/AboutView.vue'),
    },
    {
      path: '/login',
      name: 'login',
      component: () => import('../views/LoginView.vue'),
      meta: { requiresGuest: true }
    },
    {
      path: '/register',
      name: 'register',
      component: () => import('../views/RegisterView.vue'),
      meta: { requiresGuest: true }
    },
    {
      path: '/registration-success',
      name: 'registration-success',
      component: () => import('../views/RegistrationSuccessView.vue'),
      meta: { requiresGuest: true }
    },
    {
      path: '/verify-email',
      name: 'verify-email',
      component: () => import('../views/EmailVerificationView.vue'),
      meta: { requiresGuest: true }
    },
    {
      path: '/bookings',
      name: 'bookings',
      component: () => import('../views/BookingsView.vue'),
      meta: { requiresAuth: true }
    },
    {
      path: '/bookings/:id',
      name: 'booking-detail',
      component: () => import('../views/BookingDetailView.vue'),
      meta: { requiresAuth: true }
    },
    {
      path: '/rooms',
      name: 'rooms',
      component: () => import('../views/RoomsView.vue'),
    },
    {
      path: '/rooms/:id',
      name: 'room-details',
      component: () => import('../views/RoomDetailsView.vue'),
    },
    {
      path: '/profile',
      name: 'profile',
      component: () => import('../views/ProfileView.vue'),
      meta: { requiresAuth: true }
    },
    {
      path: '/employee',
      name: 'employee',
      component: () => import('../views/EmployeeDashboardView.vue'),
      meta: { requiresAuth: true, requiresEmployee: true }
    }
  ],
})

// Navigation guards
router.beforeEach((to, from, next) => {
  const authStore = useAuthStore()

  // Clean up any page-specific CSS classes to prevent scroll issues
  document.body.classList.remove('bookings-page')
  document.documentElement.classList.remove('bookings-page')

  // Check if route requires authentication
  if (to.meta.requiresAuth && !authStore.isAuthenticated) {
    next('/login')
    return
  }

  // Check if route requires employee access
  if (to.meta.requiresEmployee && !authStore.isEmployee) {
    next('/')
    return
  }

  // Check if route requires guest (not authenticated)
  if (to.meta.requiresGuest && authStore.isAuthenticated) {
    next('/')
    return
  }

  next()
})

// After each navigation, scroll to top and ensure proper scroll behavior
router.afterEach((to, from) => {
  // Scroll to top of page on route change
  window.scrollTo(0, 0)
  
  // Ensure body and html can scroll
  document.body.style.overflowY = 'auto'
  document.documentElement.style.overflowY = 'auto'
})

export default router
