<template>
  <section>
    <div class="mb-4">
      <h1 class="page-title">Admin Overview</h1>
      <p class="page-subtitle">
        System users, roles and permission summary
      </p>
    </div>

    <div
      v-if="loading"
      class="text-center py-5"
    >
      <div class="spinner-border text-primary"></div>
    </div>

    <div
      v-else-if="errorMessage"
      class="alert alert-danger"
    >
      {{ errorMessage }}
    </div>

    <div
      v-else
      class="row g-4"
    >
      <div class="col-12 col-md-4">
        <div class="card stat-card">
          <div class="card-body">
            <div class="text-secondary">Total Users</div>
            <div class="display-6 fw-bold">
              {{ overview.total_users }}
            </div>
          </div>
        </div>
      </div>

      <div class="col-12 col-md-4">
        <div class="card stat-card">
          <div class="card-body">
            <div class="text-secondary">Total Roles</div>
            <div class="display-6 fw-bold">
              {{ overview.total_roles }}
            </div>
          </div>
        </div>
      </div>

      <div class="col-12 col-md-4">
        <div class="card stat-card">
          <div class="card-body">
            <div class="text-secondary">
              Total Permissions
            </div>
            <div class="display-6 fw-bold">
              {{ overview.total_permissions }}
            </div>
          </div>
        </div>
      </div>
    </div>
  </section>
</template>

<script setup>
import { onMounted, ref } from 'vue'
import api from '@/services/api'

const loading = ref(true)
const errorMessage = ref('')
const overview = ref({})

onMounted(async () => {
  try {
    const response = await api.get('/admin/overview')
    overview.value = response.data.data
  } catch (error) {
    errorMessage.value =
      error.response?.data?.message ||
      'Could not load admin overview.'
  } finally {
    loading.value = false
  }
})
</script>