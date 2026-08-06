<template>
  <section>
    <div class="mb-4">
      <h1 class="page-title">Dashboard</h1>
      <p class="page-subtitle">
        Overview of restaurant operations
      </p>
    </div>

    <div
      v-if="loading"
      class="card"
    >
      <div class="card-body text-center py-5">
        <div class="spinner-border text-primary"></div>
        <p class="text-secondary mt-3 mb-0">
          Loading dashboard...
        </p>
      </div>
    </div>

    <div
      v-else-if="errorMessage"
      class="alert alert-danger"
    >
      {{ errorMessage }}
    </div>

    <template v-else>
      <div class="alert alert-primary">
        <strong>{{ dashboard.welcome_message }}</strong>
        <div class="small mt-1">
          Logged in as {{ dashboard.user?.name }}
          ({{ dashboard.user?.role }})
        </div>
      </div>

      <div class="row g-4">
        <div
          v-for="stat in statistics"
          :key="stat.key"
          class="col-12 col-sm-6 col-xl-3"
        >
          <div class="card stat-card">
            <div class="card-body">
              <div class="d-flex justify-content-between">
                <div>
                  <div class="text-secondary small">
                    {{ stat.label }}
                  </div>

                  <div class="fs-3 fw-bold mt-2">
                    {{ stat.value }}
                  </div>
                </div>

                <div class="stat-icon">
                  <i :class="stat.icon"></i>
                </div>
              </div>
            </div>
          </div>
        </div>
      </div>
    </template>
  </section>
</template>

<script setup>
import { computed, onMounted, ref } from 'vue'
import api from '@/services/api'

const loading = ref(true)
const errorMessage = ref('')
const dashboard = ref({
  user: null,
  statistics: {},
})

const statistics = computed(() => [
  {
    key: 'total_sales',
    label: 'Total Sales',
    value: dashboard.value.statistics?.total_sales ?? 0,
    icon: 'bi bi-cash-stack',
  },
  {
    key: 'completed_orders',
    label: 'Completed Orders',
    value: dashboard.value.statistics?.completed_orders ?? 0,
    icon: 'bi bi-check-circle',
  },
  {
    key: 'active_orders',
    label: 'Active Orders',
    value: dashboard.value.statistics?.active_orders ?? 0,
    icon: 'bi bi-receipt',
  },
  {
    key: 'pending_kitchen_orders',
    label: 'Kitchen Pending',
    value:
      dashboard.value.statistics?.pending_kitchen_orders ?? 0,
    icon: 'bi bi-fire',
  },
  {
    key: 'occupied_tables',
    label: 'Occupied Tables',
    value: dashboard.value.statistics?.occupied_tables ?? 0,
    icon: 'bi bi-people',
  },
  {
    key: 'available_tables',
    label: 'Available Tables',
    value: dashboard.value.statistics?.available_tables ?? 0,
    icon: 'bi bi-table',
  },
  {
    key: 'low_stock_items',
    label: 'Low Stock Items',
    value: dashboard.value.statistics?.low_stock_items ?? 0,
    icon: 'bi bi-box-seam',
  },
  {
    key: 'upcoming_reservations',
    label: 'Reservations',
    value:
      dashboard.value.statistics?.upcoming_reservations ?? 0,
    icon: 'bi bi-calendar-check',
  },
])

async function loadDashboard() {
  loading.value = true
  errorMessage.value = ''

  try {
    const response = await api.get('/dashboard')
    dashboard.value = response.data.data
  } catch (error) {
    errorMessage.value =
      error.response?.data?.message ||
      'Could not load dashboard data.'
  } finally {
    loading.value = false
  }
}

onMounted(loadDashboard)
</script>