<template>
  <section class="kitchen-empty-state">
    <div class="kitchen-empty-illustration">
      <span class="kitchen-empty-icon">
        <i class="bi bi-egg-fried"></i>
      </span>

      <span class="kitchen-empty-check">
        <i class="bi bi-check2"></i>
      </span>
    </div>

    <div class="kitchen-empty-content">
      <span class="kitchen-empty-eyebrow">
        Kitchen Queue
      </span>

      <h2>No kitchen orders found</h2>

      <p>
        There are no orders matching the current
        search or filter. New kitchen orders will
        appear here automatically after refresh.
      </p>
    </div>

    <button
      type="button"
      class="kitchen-empty-refresh-button"
      @click="emit('refresh')"
    >
      <i class="bi bi-arrow-clockwise"></i>

      <span>Refresh Orders</span>
    </button>
  </section>
</template>

<script setup>
const emit = defineEmits([
  'refresh',
])
</script>