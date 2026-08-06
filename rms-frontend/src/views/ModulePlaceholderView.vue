<template>
  <section>
    <div class="mb-4">
      <h1 class="page-title">
        {{ route.meta.title || 'Module' }}
      </h1>

      <p class="page-subtitle">
        {{ route.meta.description || 'Restaurant management module' }}
      </p>
    </div>

    <div class="card module-card">
      <div class="card-body p-4 p-lg-5 text-center">
        <div class="module-icon mx-auto mb-4">
          <i :class="route.meta.icon || 'bi bi-grid'"></i>
        </div>

        <h2 class="h4 fw-bold mb-3">
          {{ route.meta.title }} Module
        </h2>

        <p class="text-secondary mb-4">
          This module has been added to the system navigation.
          Its complete functionality will be developed in the
          upcoming parts.
        </p>

        <div class="module-status mx-auto">
          <div class="d-flex align-items-center justify-content-between">
            <span>Status</span>

            <span class="badge text-bg-warning">
              Coming Soon
            </span>
          </div>

          <hr />

          <div class="d-flex align-items-center justify-content-between">
            <span>Access Permission</span>

            <code>{{ route.meta.permission }}</code>
          </div>
        </div>

        <RouterLink
          to="/dashboard"
          class="btn btn-primary mt-4"
        >
          <i class="bi bi-arrow-left me-2"></i>
          Back to Dashboard
        </RouterLink>
      </div>
    </div>
  </section>
</template>

<script setup>
import { RouterLink, useRoute } from 'vue-router'

const route = useRoute()
</script>

<style scoped>
.module-card {
  min-height: 420px;
}

.module-icon {
  display: flex;
  width: 80px;
  height: 80px;
  align-items: center;
  justify-content: center;
  border-radius: 1.25rem;
  background: #eef3ff;
  color: #0d6efd;
  font-size: 2rem;
}

.module-status {
  max-width: 420px;
  border: 1px solid #e9ecef;
  border-radius: 0.8rem;
  padding: 1rem;
  text-align: left;
}
</style>