<template>
  <section class="inventory-management-page">
    <!-- Page Header -->

    <InventoryPageHeader
      :summary="summary"
      :loading="isLoading"
      @refresh="refreshInventory"
    />

    <!-- Global Error -->

    <div
      v-if="errorMessage"
      class="inventory-global-message inventory-global-error"
      role="alert"
    >
      <i class="bi bi-exclamation-circle"></i>

      <span>
        {{ errorMessage }}
      </span>

      <button
        type="button"
        aria-label="Dismiss error"
        @click="errorMessage = ''"
      >
        <i class="bi bi-x-lg"></i>
      </button>
    </div>

    <!-- Loading Notice -->

    <div
      v-if="initialLoading && !errorMessage"
      class="inventory-loading-notice"
      role="status"
      aria-live="polite"
    >
      <span
        class="spinner-border spinner-border-sm"
        aria-hidden="true"
      ></span>

      <span>
        Loading inventory information...
      </span>
    </div>

    <!-- Summary -->

    <InventorySummaryCards
      :summary="summary"
      :loading="initialLoading"
    />

    <!-- Tabs -->

    <InventoryTabs
      v-model="activeTab"
      :tabs="tabs"
    />

    <!-- Recipe Mapping -->

    <RecipeMappingSection
      v-if="activeTab === 'recipe-mapping'"
      :key="`recipe-mapping-${recipeRefreshKey}`"
    />

    <!-- Warehouse -->

    <WarehouseStockSection
      v-else-if="activeTab === 'warehouse-stock'"
      :options="options"
      :refresh-key="warehouseRefreshKey"
      :can-manage="canManageInventory"
      @inventory-changed="handleInventoryChanged"
    />

    <!-- Restaurant -->

    <RestaurantStockSection
      v-else-if="activeTab === 'restaurant-stock'"
      :key="`restaurant-stock-${restaurantRefreshKey}`"
      @inventory-changed="handleInventoryChanged"
    />
  </section>
</template>

<script setup>
import {
  computed,
  onMounted,
  reactive,
  ref,
} from 'vue'

import {
  useAuthStore,
} from '@/stores/auth'

import inventoryService
  from '@/services/inventoryService'

import InventoryPageHeader
  from '@/components/inventory/InventoryPageHeader.vue'

import InventorySummaryCards
  from '@/components/inventory/InventorySummaryCards.vue'

import InventoryTabs
  from '@/components/inventory/InventoryTabs.vue'

import RecipeMappingSection
  from '@/components/inventory/sections/RecipeMappingSection.vue'

import WarehouseStockSection
  from '@/components/inventory/sections/WarehouseStockSection.vue'

import RestaurantStockSection
  from '@/components/inventory/sections/RestaurantStockSection.vue'

import '@/assets/css/inventory/inventory-management.css'
import '@/assets/css/inventory/inventory-summary.css'
import '@/assets/css/inventory/inventory-tabs.css'
import '@/assets/css/inventory/inventory-responsive.css'
import '@/assets/css/inventory/warehouse-stock.css'
import '@/assets/css/inventory/raw-material-form.css'
import '@/assets/css/inventory/warehouse-adjustment.css'
import '@/assets/css/inventory/raw-material-actions.css'
import '@/assets/css/inventory/stock-movement-history.css'
import '@/assets/css/inventory/restaurant-stock.css'

/*
|--------------------------------------------------------------------------
| Authentication
|--------------------------------------------------------------------------
*/

const authStore =
  useAuthStore()

const canManageInventory = computed(() => {
  if (
    typeof authStore.hasPermission
    !==
    'function'
  ) {
    return false
  }

  return Boolean(
    authStore.hasPermission(
      'inventory.manage',
    ),
  )
})

/*
|--------------------------------------------------------------------------
| Page State
|--------------------------------------------------------------------------
*/

const activeTab =
  ref('warehouse-stock')

const initialLoading =
  ref(false)

const refreshing =
  ref(false)

const errorMessage =
  ref('')

/*
|--------------------------------------------------------------------------
| Section Refresh Keys
|--------------------------------------------------------------------------
*/

const warehouseRefreshKey =
  ref(0)

const restaurantRefreshKey =
  ref(0)

const recipeRefreshKey =
  ref(0)

/*
|--------------------------------------------------------------------------
| Summary and Options
|--------------------------------------------------------------------------
*/

const summary = reactive(
  inventoryService
    .createDefaultSummary(),
)

function createDefaultOptions() {
  return {
    categories: [],
    units: [],
    warehouse_statuses: [],
    adjustment_types: [],
  }
}

const options = reactive(
  createDefaultOptions(),
)

const isLoading = computed(() => {
  return (
    initialLoading.value
    ||
    refreshing.value
  )
})

/*
|--------------------------------------------------------------------------
| Tabs
|--------------------------------------------------------------------------
*/

const tabs = computed(() => {
  const alertCount =
    Number(
      summary.low_stock_alert_count,
    ) || 0

  return [
    {
      key: 'recipe-mapping',
      label: 'Recipe Mapping',
      icon: 'bi bi-diagram-3',
    },
    {
      key: 'warehouse-stock',
      label: 'Warehouse Stock',
      icon: 'bi bi-boxes',
      badge:
        alertCount > 0
          ? alertCount
          : null,
      badgeType:
        alertCount > 0
          ? 'alert'
          : null,
    },
    {
      key: 'restaurant-stock',
      label: 'Restaurant Stock',
      icon: 'bi bi-shop-window',
    },
  ]
})

/*
|--------------------------------------------------------------------------
| Refresh Active Section
|--------------------------------------------------------------------------
*/

function refreshActiveSection() {
  if (
    activeTab.value ===
    'warehouse-stock'
  ) {
    warehouseRefreshKey.value += 1
    return
  }

  if (
    activeTab.value ===
    'restaurant-stock'
  ) {
    restaurantRefreshKey.value += 1
    return
  }

  if (
    activeTab.value ===
    'recipe-mapping'
  ) {
    recipeRefreshKey.value += 1
  }
}

/*
|--------------------------------------------------------------------------
| Foundation Error Message
|--------------------------------------------------------------------------
*/

function getFoundationErrorMessage(
  result,
  fallback,
) {
  if (
    result?.status ===
    'rejected'
  ) {
    return inventoryService
      .getInventoryErrorMessage(
        result.reason,
        fallback,
      )
  }

  if (
    result?.status ===
    'fulfilled'
    &&
    result.value?.success === false
  ) {
    return (
      result.value?.message
      ||
      fallback
    )
  }

  return fallback
}

/*
|--------------------------------------------------------------------------
| Load Summary and Options
|--------------------------------------------------------------------------
*/

async function loadInventoryFoundation(
  {
    initial = false,
    refreshSection = false,
  } = {},
) {
  if (initial) {
    initialLoading.value = true
  } else {
    refreshing.value = true
  }

  errorMessage.value = ''

  try {
    const [
      summaryResult,
      optionsResult,
    ] = await Promise.allSettled([
      inventoryService
        .getSummary(),

      inventoryService
        .getOptions(),
    ])

    const errors = []

    if (
      summaryResult.status ===
      'fulfilled'
      &&
      summaryResult.value?.success
      !==
      false
    ) {
      Object.assign(
        summary,
        inventoryService
          .createDefaultSummary(),
        summaryResult.value?.data || {},
      )
    } else {
      if (initial) {
        Object.assign(
          summary,
          inventoryService
            .createDefaultSummary(),
        )
      }

      errors.push(
        getFoundationErrorMessage(
          summaryResult,
          'Unable to load inventory summary.',
        ),
      )
    }

    if (
      optionsResult.status ===
      'fulfilled'
      &&
      optionsResult.value?.success
      !==
      false
    ) {
      Object.assign(
        options,
        createDefaultOptions(),
        optionsResult.value?.data || {},
      )
    } else {
      if (initial) {
        Object.assign(
          options,
          createDefaultOptions(),
        )
      }

      errors.push(
        getFoundationErrorMessage(
          optionsResult,
          'Unable to load inventory options.',
        ),
      )
    }

    errorMessage.value =
      errors
        .filter(Boolean)
        .join(' ')
  } catch (error) {
    if (initial) {
      Object.assign(
        summary,
        inventoryService
          .createDefaultSummary(),
      )

      Object.assign(
        options,
        createDefaultOptions(),
      )
    }

    errorMessage.value =
      inventoryService
        .getInventoryErrorMessage(
          error,
          'Unable to load inventory information.',
        )
  } finally {
    if (refreshSection) {
      refreshActiveSection()
    }

    initialLoading.value = false
    refreshing.value = false
  }
}

/*
|--------------------------------------------------------------------------
| Header Refresh
|--------------------------------------------------------------------------
*/

async function refreshInventory() {
  if (isLoading.value) {
    return
  }

  await loadInventoryFoundation({
    initial: false,
    refreshSection: true,
  })
}

/*
|--------------------------------------------------------------------------
| Material Created or Updated
|--------------------------------------------------------------------------
*/

async function handleInventoryChanged(event = null) {
  if (isLoading.value) {
    return
  }

  if (
    event?.type ===
    'stock-transfer'
  ) {
    /*
    |--------------------------------------------------------------------------
    | Warehouse Snapshot Changed
    |--------------------------------------------------------------------------
    |
    | The restaurant section already refreshes itself after a successful
    | transfer. Bump only the warehouse key so the next warehouse render
    | is guaranteed to use the latest snapshot.
    |
    */

    warehouseRefreshKey.value +=
      1
  }

  await loadInventoryFoundation({
    initial: false,
    refreshSection: false,
  })
}

/*
|--------------------------------------------------------------------------
| Initial Load
|--------------------------------------------------------------------------
*/

onMounted(() => {
  void loadInventoryFoundation({
    initial: true,
    refreshSection: false,
  })
})
</script>
