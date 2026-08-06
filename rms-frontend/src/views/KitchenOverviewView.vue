<template>
  <section>
    <div class="mb-4">
      <h1 class="page-title">Kitchen Overview</h1>
      <p class="page-subtitle">
        Kitchen Display System summary
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
      <div
        v-for="item in cards"
        :key="item.key"
        class="col-12 col-sm-6 col-xl-3"
      >
        <div class="card stat-card">
          <div class="card-body">
            <div class="text-secondary">
              {{ item.label }}
            </div>

            <div class="display-6 fw-bold">
              {{ overview[item.key] ?? 0 }}
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

const cards = [
  {
    key: 'new_orders',
    label: 'New Orders',
  },
  {
    key: 'preparing_orders',
    label: 'Preparing',
  },
  {
    key: 'ready_orders',
    label: 'Ready',
  },
  {
    key: 'delayed_orders',
    label: 'Delayed',
  },
]

onMounted(async () => {
  try {
    const response = await api.get('/kitchen/overview')
    overview.value = response.data.data
  } catch (error) {
    errorMessage.value =
      error.response?.data?.message ||
      'Could not load kitchen overview.'
  } finally {
    loading.value = false
  }
})
</script>