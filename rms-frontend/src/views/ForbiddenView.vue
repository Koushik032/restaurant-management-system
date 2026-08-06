<template>
  <main class="container py-5">
    <div class="card mx-auto text-center error-card">
      <div class="card-body p-5">
        <div class="error-icon mb-3">
          <i class="bi bi-shield-exclamation"></i>
        </div>

        <h1 class="h3 fw-bold">Access Denied</h1>

        <p class="text-secondary">
          You do not have permission to access this page.
        </p>

        <RouterLink
          to="/dashboard"
          class="btn btn-primary"
        >
          Return to Dashboard
        </RouterLink>
      </div>
    </div>
  </main>
</template>

<script setup>
import { RouterLink } from 'vue-router'
</script>

<style scoped>
.error-card {
  max-width: 520px;
}

.error-icon {
  font-size: 4rem;
  color: #dc3545;
}
</style>