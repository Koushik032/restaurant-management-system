<template>
  <section class="kitchen-page">
    <!-- ==================================================
         Kitchen Page Header
    =================================================== -->

    <header class="kitchen-header">
      <div class="kitchen-header-left">
        <div class="kitchen-header-icon">
          <i class="bi bi-fire"></i>
        </div>

        <div>
          <p class="kitchen-page-tag">
            Restaurant Kitchen
          </p>

          <h1>
            Kitchen Display System
          </h1>

          <p class="kitchen-page-description">
            Review incoming orders, assign chefs and
            manage kitchen preparation progress.
          </p>
        </div>
      </div>

      <div class="kitchen-header-right">
        <button
          type="button"
          class="btn btn-outline-primary"
          :disabled="loading"
          @click="refreshOrders"
        >
          <span
            v-if="loading"
            class="spinner-border spinner-border-sm"
            role="status"
            aria-hidden="true"
          ></span>

          <i
            v-else
            class="bi bi-arrow-clockwise"
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

    <!-- ==================================================
         Kitchen Summary Cards
    =================================================== -->

    <KitchenSummaryCards
      :orders="orders"
    />

    <!-- ==================================================
         Search and Filter Bar
    =================================================== -->

    <KitchenFilterBar
      v-model:search="filters.search"
      v-model:status="filters.status"
      v-model:assignment="filters.assignment"
      @search="handleFilterSearch"
      @refresh="refreshOrders"
    />

    <!-- ==================================================
         Global Error State
    =================================================== -->

    <div
      v-if="errorMessage && !loading"
      class="kitchen-page-error"
      role="alert"
    >
      <span class="kitchen-page-error-icon">
        <i class="bi bi-exclamation-circle"></i>
      </span>

      <div class="kitchen-page-error-content">
        <strong>
          Unable to load kitchen orders
        </strong>

        <p>
          {{ errorMessage }}
        </p>
      </div>

      <button
        type="button"
        class="kitchen-page-error-retry"
        @click="refreshOrders"
      >
        <i class="bi bi-arrow-clockwise"></i>

        <span>Try Again</span>
      </button>
    </div>

    <!-- ==================================================
         Loading Skeleton
    =================================================== -->

    <KitchenLoading
      v-if="loading"
    />

    <!-- ==================================================
         Empty State
    =================================================== -->

    <KitchenEmptyState
      v-else-if="
        !errorMessage &&
        !orders.length
      "
      @refresh="refreshOrders"
    />

    <!-- ==================================================
         Compact Kitchen Order Grid
    =================================================== -->

    <section
      v-else-if="orders.length"
      class="kitchen-order-grid"
      aria-label="Kitchen orders"
    >
      <KitchenCompactOrderCard
        v-for="order in orders"
        :key="`${order.id}-${order.kitchen_batch_no || order.batch_no || 1}`"
        :order="order"
        @accepted="
          handleOrderAccepted(
            order,
            $event
          )
        "
      />
    </section>

    <!-- ==================================================
         Pagination
    =================================================== -->

    <nav
      v-if="
        !loading &&
        !errorMessage &&
        meta.last_page > 1
      "
      class="kitchen-pagination"
      aria-label="Kitchen order pagination"
    >
      <button
        type="button"
        class="btn btn-light"
        :disabled="
          loading ||
          meta.current_page <= 1
        "
        @click="previousPage"
      >
        <i class="bi bi-chevron-left"></i>

        <span>Previous</span>
      </button>

      <div class="kitchen-pagination-info">
        <span>
          Page

          <strong>
            {{ meta.current_page }}
          </strong>

          of

          <strong>
            {{ meta.last_page }}
          </strong>
        </span>

        <small>
          {{ meta.total }}

          {{
            meta.total === 1
              ? 'order'
              : 'orders'
          }}
        </small>
      </div>

      <button
        type="button"
        class="btn btn-light"
        :disabled="
          loading ||
          meta.current_page >=
            meta.last_page
        "
        @click="nextPage"
      >
        <span>Next</span>

        <i class="bi bi-chevron-right"></i>
      </button>
    </nav>
  </section>
</template>

<script setup>
import {
  onMounted,
  reactive,
  ref,
} from 'vue'

import kitchenOrderService from '@/services/kitchenOrderService'

import KitchenSummaryCards from '@/components/kitchen/KitchenSummaryCards.vue'
import KitchenFilterBar from '@/components/kitchen/KitchenFilterBar.vue'
import KitchenCompactOrderCard from '@/components/kitchen/KitchenCompactOrderCard.vue'
import KitchenLoading from '@/components/kitchen/KitchenLoading.vue'
import KitchenEmptyState from '@/components/kitchen/KitchenEmptyState.vue'

/*
|--------------------------------------------------------------------------
| Reactive State
|--------------------------------------------------------------------------
*/

const loading = ref(false)

const errorMessage = ref('')

const orders = ref([])

/*
|--------------------------------------------------------------------------
| Pagination Meta
|--------------------------------------------------------------------------
*/

const meta = reactive({
  current_page: 1,
  last_page: 1,
  per_page: 20,
  total: 0,
  from: null,
  to: null,
})

/*
|--------------------------------------------------------------------------
| Kitchen Filters
|--------------------------------------------------------------------------
*/

const filters = reactive({
  page: 1,
  per_page: 20,
  search: '',
  status: '',
  assignment: '',
})

/*
|--------------------------------------------------------------------------
| Load Kitchen Orders
|--------------------------------------------------------------------------
*/

async function loadOrders() {
  if (loading.value) {
    return
  }

  loading.value = true
  errorMessage.value = ''

  try {
    const response =
      await kitchenOrderService
        .getKitchenOrders({
          page:
            filters.page,

          per_page:
            filters.per_page,

          search:
            filters.search.trim(),

          status:
            filters.status,

          assignment:
            filters.assignment,
        })

    orders.value =
      Array.isArray(
        response?.data,
      )
        ? response.data
        : []

    updatePaginationMeta(
      response?.meta,
    )
  } catch (error) {
    console.error(
      'Unable to load kitchen orders:',
      error,
    )

    orders.value = []

    resetPaginationMeta()

    errorMessage.value =
      resolveErrorMessage(
        error,
        'Unable to load kitchen orders. Please try again.',
      )
  } finally {
    loading.value = false
  }
}

/*
|--------------------------------------------------------------------------
| Handle Filter Search
|--------------------------------------------------------------------------
|
| নতুন search বা filter apply করলে প্রথম page থেকে result load হবে।
|
*/

function handleFilterSearch() {
  filters.page = 1

  loadOrders()
}

/*
|--------------------------------------------------------------------------
| Refresh Kitchen Orders
|--------------------------------------------------------------------------
*/

function refreshOrders() {
  filters.page = 1

  loadOrders()
}

/*
|--------------------------------------------------------------------------
| Handle Accepted Order
|--------------------------------------------------------------------------
|
| Chef Accept করার পরে backend থেকে পাওয়া updated order সরাসরি current
| card-এ replace হবে। ফলে page reload ছাড়াই assigned chef username দেখাবে।
|
*/

function handleOrderAccepted(
  currentOrder,
  updatedOrder,
) {
  if (
    !updatedOrder ||
    typeof updatedOrder !==
      'object'
  ) {
    loadOrders()

    return
  }

  const orderIndex =
    orders.value.findIndex(
      (order) =>
        Number(order.id) ===
        Number(currentOrder.id),
    )

  if (orderIndex === -1) {
    loadOrders()

    return
  }

  orders.value.splice(
    orderIndex,
    1,
    {
      ...orders.value[
        orderIndex
      ],

      ...updatedOrder,
    },
  )
}

/*
|--------------------------------------------------------------------------
| Previous Page
|--------------------------------------------------------------------------
*/

function previousPage() {
  if (
    loading.value ||
    filters.page <= 1
  ) {
    return
  }

  filters.page -= 1

  loadOrders()
}

/*
|--------------------------------------------------------------------------
| Next Page
|--------------------------------------------------------------------------
*/

function nextPage() {
  if (
    loading.value ||
    filters.page >=
      meta.last_page
  ) {
    return
  }

  filters.page += 1

  loadOrders()
}

/*
|--------------------------------------------------------------------------
| Update Pagination Meta
|--------------------------------------------------------------------------
*/

function updatePaginationMeta(
  paginationMeta,
) {
  const resolvedMeta =
    paginationMeta &&
    typeof paginationMeta ===
      'object'
      ? paginationMeta
      : {}

  meta.current_page =
    toPositiveNumber(
      resolvedMeta.current_page,
      1,
    )

  meta.last_page =
    toPositiveNumber(
      resolvedMeta.last_page,
      1,
    )

  meta.per_page =
    toPositiveNumber(
      resolvedMeta.per_page,
      filters.per_page,
    )

  meta.total =
    toNonNegativeNumber(
      resolvedMeta.total,
      0,
    )

  meta.from =
    resolvedMeta.from ??
    null

  meta.to =
    resolvedMeta.to ??
    null

  filters.page =
    meta.current_page
}

/*
|--------------------------------------------------------------------------
| Reset Pagination Meta
|--------------------------------------------------------------------------
*/

function resetPaginationMeta() {
  Object.assign(
    meta,
    {
      current_page: 1,
      last_page: 1,
      per_page:
        filters.per_page,
      total: 0,
      from: null,
      to: null,
    },
  )

  filters.page = 1
}

/*
|--------------------------------------------------------------------------
| Positive Number Helper
|--------------------------------------------------------------------------
*/

function toPositiveNumber(
  value,
  fallback,
) {
  const resolvedValue =
    Number(value)

  if (
    Number.isFinite(
      resolvedValue,
    ) &&
    resolvedValue > 0
  ) {
    return resolvedValue
  }

  return fallback
}

/*
|--------------------------------------------------------------------------
| Non-negative Number Helper
|--------------------------------------------------------------------------
*/

function toNonNegativeNumber(
  value,
  fallback,
) {
  const resolvedValue =
    Number(value)

  if (
    Number.isFinite(
      resolvedValue,
    ) &&
    resolvedValue >= 0
  ) {
    return resolvedValue
  }

  return fallback
}

/*
|--------------------------------------------------------------------------
| Resolve API Error
|--------------------------------------------------------------------------
*/

function resolveErrorMessage(
  error,
  fallbackMessage,
) {
  if (
    typeof kitchenOrderService
      .getKitchenErrorMessage ===
    'function'
  ) {
    return kitchenOrderService
      .getKitchenErrorMessage(
        error,
        fallbackMessage,
      )
  }

  const validationErrors =
    error?.response?.data
      ?.errors

  if (
    validationErrors &&
    typeof validationErrors ===
      'object'
  ) {
    const firstError =
      Object.values(
        validationErrors,
      )
        .flat()
        .find(Boolean)

    if (firstError) {
      return String(
        firstError,
      )
    }
  }

  return (
    error?.response?.data
      ?.message ||
    error?.message ||
    fallbackMessage
  )
}

/*
|--------------------------------------------------------------------------
| Initial Page Load
|--------------------------------------------------------------------------
*/

onMounted(() => {
  loadOrders()
})
</script>

<style
  src="@/assets/css/kitchen/kitchen-overview.css"
></style>

<style
  src="@/assets/css/kitchen/kitchen-card.css"
></style>

<style
  src="@/assets/css/kitchen/kitchen-responsive.css"
></style>