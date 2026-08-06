<template>
  <section class="kitchen-summary-grid">
    <!-- Pending -->

    <article class="summary-card pending-card">
      <div class="summary-card-icon">
        <i class="bi bi-hourglass-split"></i>
      </div>

      <div class="summary-card-content">
        <span>Pending</span>

        <strong>
          {{ pendingCount }}
        </strong>

        <small>
          Waiting for chef
        </small>
      </div>
    </article>

    <!-- Accepted -->

    <article class="summary-card accepted-card">
      <div class="summary-card-icon">
        <i class="bi bi-person-check"></i>
      </div>

      <div class="summary-card-content">
        <span>Accepted</span>

        <strong>
          {{ acceptedCount }}
        </strong>

        <small>
          Chef assigned
        </small>
      </div>
    </article>

    <!-- Preparing -->

    <article class="summary-card preparing-card">
      <div class="summary-card-icon">
        <i class="bi bi-fire"></i>
      </div>

      <div class="summary-card-content">
        <span>Preparing</span>

        <strong>
          {{ preparingCount }}
        </strong>

        <small>
          Cooking now
        </small>
      </div>
    </article>

    <!-- Ready -->

    <article class="summary-card ready-card">
      <div class="summary-card-icon">
        <i class="bi bi-check-circle"></i>
      </div>

      <div class="summary-card-content">
        <span>Ready</span>

        <strong>
          {{ readyCount }}
        </strong>

        <small>
          Ready to serve
        </small>
      </div>
    </article>
  </section>
</template>

<script setup>
import { computed } from 'vue'

const props = defineProps({
  orders: {
    type: Array,
    default: () => [],
  },
})

/*
|--------------------------------------------------------------------------
| Pending Orders
|--------------------------------------------------------------------------
|
| Pending + no chef assigned
|
*/

const pendingCount = computed(() => {
  return props.orders.filter(
    (order) =>
      order.status === 'pending' &&
      !order.chef,
  ).length
})

/*
|--------------------------------------------------------------------------
| Accepted Orders
|--------------------------------------------------------------------------
|
| Pending + chef assigned
|
*/

const acceptedCount = computed(() => {
  return props.orders.filter(
    (order) =>
      order.status === 'pending' &&
      order.chef,
  ).length
})

/*
|--------------------------------------------------------------------------
| Preparing Orders
|--------------------------------------------------------------------------
*/

const preparingCount = computed(() => {
  return props.orders.filter(
    (order) =>
      order.status ===
      'preparing',
  ).length
})

/*
|--------------------------------------------------------------------------
| Ready Orders
|--------------------------------------------------------------------------
*/

const readyCount = computed(() => {
  return props.orders.filter(
    (order) =>
      order.status === 'ready',
  ).length
})
</script>