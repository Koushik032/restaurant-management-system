<template>
  <article
    class="kitchen-compact-card"
    :class="statusClass"
  >
    <!-- ==================================================
         Order Identity
    =================================================== -->

    <header class="compact-card-header">
      <div class="compact-order-identity">
        <span class="compact-order-icon">
          <i class="bi bi-receipt"></i>
        </span>

        <div>
          <small>Order ID</small>

          <h3>
            {{ order.order_number }}
          </h3>

          <div class="compact-batch-meta">
            <span class="compact-batch-badge">
              <i class="bi bi-layers"></i>

              Batch #{{ batchNumber }}
            </span>

            <span
              v-if="isExtensionBatch"
              class="compact-extension-badge"
            >
              <i class="bi bi-plus-circle"></i>

              Order Extension
            </span>
          </div>
        </div>
      </div>

      <span
        class="compact-status-badge"
        :class="statusClass"
      >
        <i :class="statusIcon"></i>

        {{ statusLabel }}
      </span>
    </header>

    <!-- ==================================================
         Current Kitchen Batch Items
    =================================================== -->

    <section class="compact-menu-section">
      <div class="compact-section-heading">
        <span>
          <i class="bi bi-basket2"></i>

          Current Batch Items
        </span>

        <strong>
          {{ totalQuantity }}
          items
        </strong>
      </div>

      <div
        v-if="order.items?.length"
        class="compact-menu-list"
      >
        <div
          v-for="item in visibleItems"
          :key="item.id"
          class="compact-menu-row"
        >
          <div>
            <strong>
              {{ item.item_name }}
            </strong>

            <small v-if="item.variant_name">
              {{ item.variant_name }}
            </small>
          </div>

          <span>
            × {{ item.quantity }}
          </span>
        </div>

        <button
          v-if="hiddenItemCount > 0"
          type="button"
          class="compact-more-items"
          @click="openDetails"
        >
          + {{ hiddenItemCount }}
          more
        </button>
      </div>

      <div
        v-else
        class="compact-empty-items"
      >
        No menu items found
      </div>
    </section>

    <!-- ==================================================
         Action Error
    =================================================== -->

    <div
      v-if="errorMessage"
      class="compact-action-error"
      role="alert"
    >
      <i class="bi bi-exclamation-circle"></i>

      <span>{{ errorMessage }}</span>
    </div>

    <!-- ==================================================
         Card Action
    =================================================== -->

    <footer class="compact-card-footer">
      <!-- Unassigned Order -->

      <button
        v-if="order.can_accept && !order.chef"
        type="button"
        class="compact-action-button accept-action"
        :disabled="isAccepting"
        @click="acceptOrder"
      >
        <span
          v-if="isAccepting"
          class="spinner-border spinner-border-sm"
        ></span>

        <i
          v-else
          class="bi bi-person-check"
        ></i>

        {{
          isAccepting
            ? 'Accepting...'
            : 'Accept Order'
        }}
      </button>

      <!-- Assigned Chef -->

      <button
        v-else-if="order.chef"
        type="button"
        class="compact-action-button chef-action"
        @click="openDetails"
      >
        <span class="compact-chef-avatar">
          {{ chefInitial }}
        </span>

        <span class="compact-chef-info">
          <small>Assigned Chef</small>

          <strong>
            {{
              order.chef.username ||
              order.chef.name
            }}
          </strong>
        </span>

        <i class="bi bi-chevron-right"></i>
      </button>

      <!-- Fallback -->

      <button
        v-else
        type="button"
        class="compact-action-button details-action"
        @click="openDetails"
      >
        <i class="bi bi-eye"></i>

        View Kitchen Order

        <i class="bi bi-chevron-right"></i>
      </button>
    </footer>
  </article>
</template>

<script setup>
import {
  computed,
  ref,
} from 'vue'

import { useRouter } from 'vue-router'

import kitchenOrderService from '@/services/kitchenOrderService'

const props = defineProps({
  order: {
    type: Object,
    required: true,
  },
})

const emit = defineEmits([
  'accepted',
])

const router = useRouter()

/*
|--------------------------------------------------------------------------
| Action State
|--------------------------------------------------------------------------
*/

const isAccepting = ref(false)

const errorMessage = ref('')

/*
|--------------------------------------------------------------------------
| Current Kitchen Batch
|--------------------------------------------------------------------------
|
| Backend final KitchenOrderResource exposes `kitchen_batch_no`.
| `batch_no` fallback keeps this component forward-compatible.
|
*/

const batchNumber = computed(() => {
  const value = Number(
    props.order?.kitchen_batch_no ??
    props.order?.batch_no ??
    1,
  )

  return Number.isInteger(value) &&
    value > 0
    ? value
    : 1
})

const isExtensionBatch = computed(() => {
  return batchNumber.value > 1
})

/*
|--------------------------------------------------------------------------
| Visible Items
|--------------------------------------------------------------------------
|
| Backend returns ONLY latest kitchen batch items.
| Compact card shows at most 3 lines.
|
*/

const visibleItems = computed(() => {
  return Array.isArray(
    props.order?.items,
  )
    ? props.order.items.slice(0, 3)
    : []
})

const hiddenItemCount = computed(() => {
  const total =
    Array.isArray(
      props.order?.items,
    )
      ? props.order.items.length
      : 0

  return Math.max(
    0,
    total -
      visibleItems.value.length,
  )
})

/*
|--------------------------------------------------------------------------
| Total Quantity
|--------------------------------------------------------------------------
*/

const totalQuantity = computed(() => {
  const resourceQuantity =
    Number(
      props.order
        ?.total_item_quantity,
    )

  if (
    Number.isFinite(
      resourceQuantity,
    )
  ) {
    return resourceQuantity
  }

  return Array.isArray(
    props.order?.items,
  )
    ? props.order.items.reduce(
        (total, item) =>
          total +
          Number(
            item?.quantity || 0,
          ),
        0,
      )
    : 0
})

/*
|--------------------------------------------------------------------------
| Chef Information
|--------------------------------------------------------------------------
*/

const chefInitial = computed(() => {
  const displayName =
    props.order?.chef
      ?.username ||
    props.order?.chef?.name ||
    '?'

  return displayName
    .charAt(0)
    .toUpperCase()
})

/*
|--------------------------------------------------------------------------
| Kitchen Status
|--------------------------------------------------------------------------
*/

const statusLabel = computed(() => {
  if (
    props.order?.status ===
      'pending' &&
    props.order?.chef
  ) {
    return 'Accepted'
  }

  return (
    props.order?.status_label ||
    formatLabel(
      props.order?.status,
    ) ||
    'Pending'
  )
})

const statusClass = computed(() => {
  if (
    props.order?.status ===
      'pending' &&
    props.order?.chef
  ) {
    return 'compact-status-accepted'
  }

  switch (
    props.order?.status
  ) {
    case 'preparing':
      return 'compact-status-preparing'

    case 'ready':
      return 'compact-status-ready'

    case 'pending':
    default:
      return 'compact-status-pending'
  }
})

const statusIcon = computed(() => {
  if (
    props.order?.status ===
      'pending' &&
    props.order?.chef
  ) {
    return 'bi bi-person-check'
  }

  switch (
    props.order?.status
  ) {
    case 'preparing':
      return 'bi bi-fire'

    case 'ready':
      return 'bi bi-check-circle'

    case 'pending':
    default:
      return 'bi bi-hourglass-split'
  }
})

/*
|--------------------------------------------------------------------------
| Accept Order
|--------------------------------------------------------------------------
*/

async function acceptOrder() {
  if (isAccepting.value) {
    return
  }

  isAccepting.value = true
  errorMessage.value = ''

  try {
    const response =
      await kitchenOrderService
        .acceptOrder(
          props.order.id,
        )

    emit(
      'accepted',
      response.data,
    )
  } catch (error) {
    console.error(
      'Unable to accept kitchen order:',
      error,
    )

    errorMessage.value =
      kitchenOrderService
        .getKitchenErrorMessage(
          error,
          'Unable to accept this order.',
        )
  } finally {
    isAccepting.value = false
  }
}

/*
|--------------------------------------------------------------------------
| Open Kitchen Order Details
|--------------------------------------------------------------------------
|
| Route remains parent order ID.
| Backend resolves the latest active kitchen batch for that order.
|
*/

function openDetails() {
  router.push({
    name: 'kitchen-order-details',

    params: {
      id: props.order.id,
    },
  })
}

/*
|--------------------------------------------------------------------------
| Label Formatter
|--------------------------------------------------------------------------
*/

function formatLabel(value) {
  if (!value) {
    return ''
  }

  return String(value)
    .replaceAll('_', ' ')
    .replace(
      /\b\w/g,
      (letter) =>
        letter.toUpperCase(),
    )
}
</script>

<style scoped>
.compact-batch-meta {
  display: flex;
  flex-wrap: wrap;
  gap: 0.4rem;
  margin-top: 0.35rem;
}

.compact-batch-badge,
.compact-extension-badge {
  display: inline-flex;
  align-items: center;
  gap: 0.3rem;
  width: fit-content;
  padding: 0.22rem 0.5rem;
  border: 1px solid rgba(15, 23, 42, 0.12);
  border-radius: 999px;
  background: rgba(15, 23, 42, 0.04);
  font-size: 0.72rem;
  font-weight: 700;
  line-height: 1.2;
}

.compact-extension-badge {
  border-color: rgba(13, 110, 253, 0.2);
  background: rgba(13, 110, 253, 0.08);
}

@media (max-width: 575.98px) {
  .compact-batch-meta {
    gap: 0.3rem;
  }

  .compact-batch-badge,
  .compact-extension-badge {
    font-size: 0.68rem;
  }
}
</style>