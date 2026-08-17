<template>
  <header class="inventory-page-header">
    <div class="inventory-header-content">
      <div class="inventory-header-icon">
        <i
          class="bi bi-box-seam"
          aria-hidden="true"
        ></i>
      </div>


      <div class="inventory-header-text">
        <h1>
          Inventory Management
        </h1>


        <p>
          Manage recipes, warehouse stock
          and restaurant stock
        </p>
      </div>
    </div>


    <div class="inventory-header-actions">
      <!-- Low Stock Status -->

      <div
        class="inventory-low-stock-alert"
        :class="{
          'inventory-alert-active':
            hasLowStock,

          'inventory-alert-clear':
            !hasLowStock,
        }"
        role="status"
        aria-live="polite"
      >
        <i
          class="bi"
          :class="
            hasLowStock
              ? 'bi-exclamation-triangle-fill'
              : 'bi-check-circle-fill'
          "
          aria-hidden="true"
        ></i>


        <span>
          {{ lowStockMessage }}
        </span>
      </div>


      <!-- Refresh -->

      <button
        type="button"
        class="inventory-refresh-button"
        :disabled="loading"
        :aria-busy="loading"
        :aria-label="
          loading
            ? 'Refreshing inventory'
            : 'Refresh inventory'
        "
        @click="handleRefresh"
      >
        <i
          class="bi bi-arrow-clockwise"
          :class="{
            'inventory-refresh-spin':
              loading,
          }"
          aria-hidden="true"
        ></i>


        <span>
          {{
            loading
              ? 'Refreshing...'
              : 'Refresh'
          }}
        </span>
      </button>
    </div>
  </header>
</template>


<script setup>
import {
  computed,
} from 'vue'


/*
|--------------------------------------------------------------------------
| Props
|--------------------------------------------------------------------------
*/


const props = defineProps({
  summary: {
    type: Object,

    default: () => ({
      low_stock_alert_count: 0,
      has_low_stock_alert: false,
    }),
  },


  loading: {
    type: Boolean,
    default: false,
  },
})


/*
|--------------------------------------------------------------------------
| Emits
|--------------------------------------------------------------------------
*/


const emit = defineEmits([
  'refresh',
])


/*
|--------------------------------------------------------------------------
| Low Stock Count
|--------------------------------------------------------------------------
*/


const lowStockCount = computed(() => {
  const count =
    Number(
      props.summary
        ?.low_stock_alert_count,
    )


  if (
    !Number.isFinite(count)
    ||
    count <= 0
  ) {
    return 0
  }


  return Math.floor(
    count,
  )
})


/*
|--------------------------------------------------------------------------
| Low Stock Status
|--------------------------------------------------------------------------
*/


const hasLowStock = computed(() => {
  return (
    props.summary
      ?.has_low_stock_alert === true
    ||
    lowStockCount.value > 0
  )
})


/*
|--------------------------------------------------------------------------
| Low Stock Message
|--------------------------------------------------------------------------
*/


const lowStockMessage = computed(() => {
  if (!hasLowStock.value) {
    return 'Stock Levels Normal'
  }


  /*
  |--------------------------------------------------------------------------
  | Count Not Available
  |--------------------------------------------------------------------------
  */

  if (lowStockCount.value <= 0) {
    return 'Low Stock Alert'
  }


  return (
    `${lowStockCount.value} Low Stock Alert`
    +
    (
      lowStockCount.value === 1
        ? ''
        : 's'
    )
  )
})


/*
|--------------------------------------------------------------------------
| Refresh
|--------------------------------------------------------------------------
*/


function handleRefresh() {
  if (props.loading) {
    return
  }


  emit(
    'refresh',
  )
}
</script>