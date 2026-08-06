<template>
  <main class="container py-5">
    <div class="card mx-auto text-center not-found-card">
      <div class="card-body p-5">
        <div class="display-1 fw-bold text-primary">404</div>

        <h1 class="h3 fw-bold">Page Not Found</h1>

        <p class="text-secondary">
          The page you requested does not exist.
        </p>

        <RouterLink
          :to="destination"
          class="btn btn-primary"
        >
          Go Back
        </RouterLink>
      </div>
    </div>
  </main>
</template>

<script setup>
import { computed } from 'vue'
import { RouterLink } from 'vue-router'
import { useAuthStore } from '@/stores/auth'

const authStore = useAuthStore()

const destination = computed(() =>
  authStore.isAuthenticated ? '/dashboard' : '/login',
)
</script>

<style scoped>
.not-found-card {
  max-width: 520px;
}
</style>