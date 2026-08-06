<template>
  <article
    class="kitchen-order-card"
    :class="[
      `kitchen-order-${order.status}`,
      {
        'kitchen-order-assigned':
          Boolean(order.chef),
        'kitchen-order-unassigned':
          !order.chef,
      },
    ]"
  >
    <!-- ==================================================
         Order Card Header
    =================================================== -->
    <header class="kitchen-order-card-header">
      <div class="kitchen-order-heading">
        <span
          class="kitchen-order-status-icon"
          :class="statusClass"
        >
          <i :class="statusIcon"></i>
        </span>

        <div class="kitchen-order-title">
          <span class="kitchen-order-label">
            Kitchen Order
          </span>

          <h2>
            {{ order.order_number }}
          </h2>

          <div class="kitchen-order-header-meta">
            <span>
              <i class="bi bi-clock"></i>

              {{ order.time || formattedCreatedTime }}
            </span>

            <span>
              <i class="bi bi-calendar3"></i>

              {{ order.date || formattedCreatedDate }}
            </span>
          </div>
        </div>
      </div>

      <span
        class="kitchen-status-badge"
        :class="statusClass"
      >
        <i :class="statusIcon"></i>

        {{
          order.status_label ||
          formattedStatus
        }}
      </span>
    </header>

    <!-- ==================================================
         Quick Information
    =================================================== -->
    <section class="kitchen-order-quick-info">
      <!-- Table -->
      <div class="kitchen-quick-info-item">
        <span class="kitchen-quick-info-icon">
          <i class="bi bi-grid-3x3-gap"></i>
        </span>

        <div>
          <small>Table</small>

          <strong>
            {{
              order.primary_table?.table_name ||
              'Not assigned'
            }}
          </strong>

          <span
            v-if="order.merged_table_names"
          >
            Merged:
            {{ order.merged_table_names }}
          </span>

          <span v-else>
            {{
              order.primary_table?.section ||
              'Restaurant floor'
            }}
          </span>
        </div>
      </div>

      <!-- Assigned Chef -->
      <div class="kitchen-quick-info-item">
        <span class="kitchen-quick-info-icon">
          <i class="bi bi-person-badge"></i>
        </span>

        <div>
          <small>Assigned Chef</small>

          <strong>
            {{
              order.chef?.username ||
              order.chef?.name ||
              'Unassigned'
            }}
          </strong>

          <span v-if="order.chef?.name">
            {{ order.chef.name }}
          </span>

          <span v-else>
            Waiting for a chef
          </span>
        </div>
      </div>

      <!-- Total Items -->
      <div class="kitchen-quick-info-item">
        <span class="kitchen-quick-info-icon">
          <i class="bi bi-basket2"></i>
        </span>

        <div>
          <small>Total Items</small>

          <strong>
            {{ totalItemQuantity }}
          </strong>

          <span>
            {{
              totalItemQuantity === 1
                ? 'menu item'
                : 'menu items'
            }}
          </span>
        </div>
      </div>

      <!-- Waiting / Preparation Time -->
      <div class="kitchen-quick-info-item">
        <span class="kitchen-quick-info-icon">
          <i class="bi bi-stopwatch"></i>
        </span>

        <div>
          <small>
            {{ elapsedTimeLabel }}
          </small>

          <strong>
            {{ elapsedTime }}
          </strong>

          <span>
            {{ kitchenProgressText }}
          </span>
        </div>
      </div>
    </section>

    <!-- ==================================================
         Customer and General Notes
    =================================================== -->
    <section
      v-if="
        order.customer?.name ||
        order.kitchen_note ||
        order.order_note
      "
      class="kitchen-order-context"
    >
      <div
        v-if="order.customer?.name"
        class="kitchen-customer-summary"
      >
        <span>
          <i class="bi bi-person"></i>
        </span>

        <div>
          <small>Customer</small>

          <strong>
            {{
              order.customer.name ||
              'Walk-in Customer'
            }}
          </strong>

          <p v-if="order.customer.phone">
            {{ order.customer.phone }}
          </p>
        </div>
      </div>

      <div
        v-if="
          order.kitchen_note ||
          order.order_note
        "
        class="kitchen-general-notes"
      >
        <article
          v-if="order.kitchen_note"
          class="kitchen-note-box kitchen-priority-note"
        >
          <span>
            <i class="bi bi-fire"></i>
          </span>

          <div>
            <small>Kitchen Note</small>

            <p>
              {{ order.kitchen_note }}
            </p>
          </div>
        </article>

        <article
          v-if="order.order_note"
          class="kitchen-note-box"
        >
          <span>
            <i class="bi bi-sticky"></i>
          </span>

          <div>
            <small>Order Note</small>

            <p>
              {{ order.order_note }}
            </p>
          </div>
        </article>
      </div>
    </section>

    <!-- ==================================================
         Ordered Menu Items
    =================================================== -->
    <section class="kitchen-order-items-section">
      <header class="kitchen-order-section-header">
        <div>
          <span class="kitchen-section-icon">
            <i class="bi bi-list-check"></i>
          </span>

          <div>
            <h3>Ordered Menu Items</h3>

            <p>
              Ingredients, notes and selected add-ons
            </p>
          </div>
        </div>

        <span class="kitchen-item-count">
          {{ order.items?.length || 0 }}
          {{
            order.items?.length === 1
              ? 'line'
              : 'lines'
          }}
        </span>
      </header>

      <div
        v-if="order.items?.length"
        class="kitchen-order-items-list"
      >
        <KitchenOrderItem
          v-for="item in order.items"
          :key="item.id"
          :item="item"
        />
      </div>

      <div
        v-else
        class="kitchen-order-items-empty"
      >
        <i class="bi bi-basket"></i>

        <strong>No menu items found</strong>

        <span>
          This order does not contain any items.
        </span>
      </div>
    </section>

    <!-- ==================================================
         Kitchen Timeline
    =================================================== -->
    <section class="kitchen-order-timeline">
      <article
        class="kitchen-timeline-step"
        :class="{
          completed:
            Boolean(order.sent_to_kitchen_at),
        }"
      >
        <span class="kitchen-timeline-marker">
          <i class="bi bi-person-check"></i>
        </span>

        <div>
          <strong>Accepted</strong>

          <small>
            {{
              order.sent_to_kitchen_at
                ? formatDateTime(
                    order.sent_to_kitchen_at
                  )
                : 'Not accepted yet'
            }}
          </small>
        </div>
      </article>

      <article
        class="kitchen-timeline-step"
        :class="{
          completed:
            Boolean(order.preparing_at),
        }"
      >
        <span class="kitchen-timeline-marker">
          <i class="bi bi-fire"></i>
        </span>

        <div>
          <strong>Preparing</strong>

          <small>
            {{
              order.preparing_at
                ? formatDateTime(
                    order.preparing_at
                  )
                : 'Not started yet'
            }}
          </small>
        </div>
      </article>

      <article
        class="kitchen-timeline-step"
        :class="{
          completed:
            Boolean(order.ready_at),
        }"
      >
        <span class="kitchen-timeline-marker">
          <i class="bi bi-check-circle"></i>
        </span>

        <div>
          <strong>Ready</strong>

          <small>
            {{
              order.ready_at
                ? formatDateTime(
                    order.ready_at
                  )
                : 'Not ready yet'
            }}
          </small>
        </div>
      </article>
    </section>

    <!-- ==================================================
         Action Error
    =================================================== -->
    <div
      v-if="actionError"
      class="kitchen-action-error"
      role="alert"
    >
      <i class="bi bi-exclamation-circle"></i>

      <span>
        {{ actionError }}
      </span>

      <button
        type="button"
        aria-label="Dismiss error"
        @click="actionError = ''"
      >
        <i class="bi bi-x-lg"></i>
      </button>
    </div>

    <!-- ==================================================
         Card Footer and Status Actions
    =================================================== -->
    <footer class="kitchen-order-card-footer">
      <div class="kitchen-assignment-info">
        <span
          class="kitchen-assignment-avatar"
          :class="{
            assigned: Boolean(order.chef),
          }"
        >
          {{
            assignedChefInitial
          }}
        </span>

        <div>
          <small>
            {{
              order.chef
                ? 'Order assigned to'
                : 'Assignment status'
            }}
          </small>

          <strong>
            {{
              order.chef?.username ||
              order.chef?.name ||
              'Waiting for chef'
            }}
          </strong>
        </div>
      </div>

      <div class="kitchen-order-actions">
        <!-- Accept -->
        <button
          v-if="order.can_accept"
          type="button"
          class="kitchen-action-button accept-button"
          :disabled="isProcessing"
          @click="acceptKitchenOrder"
        >
          <span
            v-if="
              processingAction === 'accept'
            "
            class="spinner-border spinner-border-sm"
          ></span>

          <i
            v-else
            class="bi bi-person-check"
          ></i>

          <span>
            {{
              processingAction === 'accept'
                ? 'Accepting...'
                : 'Accept Order'
            }}
          </span>
        </button>

        <!-- Start Preparing -->
        <button
          v-if="order.can_start_preparing"
          type="button"
          class="kitchen-action-button preparing-button"
          :disabled="isProcessing"
          @click="startOrderPreparation"
        >
          <span
            v-if="
              processingAction ===
              'preparing'
            "
            class="spinner-border spinner-border-sm"
          ></span>

          <i
            v-else
            class="bi bi-fire"
          ></i>

          <span>
            {{
              processingAction ===
              'preparing'
                ? 'Starting...'
                : 'Start Preparing'
            }}
          </span>
        </button>

        <!-- Mark Ready -->
        <button
          v-if="order.can_mark_ready"
          type="button"
          class="kitchen-action-button ready-button"
          :disabled="isProcessing"
          @click="markOrderReady"
        >
          <span
            v-if="
              processingAction === 'ready'
            "
            class="spinner-border spinner-border-sm"
          ></span>

          <i
            v-else
            class="bi bi-check2-circle"
          ></i>

          <span>
            {{
              processingAction === 'ready'
                ? 'Updating...'
                : 'Mark as Ready'
            }}
          </span>
        </button>

        <!-- Ready State -->
        <span
          v-if="
            order.status === 'ready' &&
            !order.can_mark_ready
          "
          class="kitchen-ready-message"
        >
          <i class="bi bi-check-circle-fill"></i>

          Ready to Serve
        </span>

        <!-- Fallback -->
        <span
          v-if="
            !order.can_accept &&
            !order.can_start_preparing &&
            !order.can_mark_ready &&
            order.status !== 'ready'
          "
          class="kitchen-no-action-message"
        >
          <i class="bi bi-lock"></i>

          No kitchen action available
        </span>
      </div>
    </footer>
  </article>
</template>

<script setup>
import {
  computed,
  ref,
} from 'vue'

import KitchenOrderItem from '@/components/kitchen/KitchenOrderItem.vue'
import kitchenOrderService from '@/services/kitchenOrderService'

const props = defineProps({
  order: {
    type: Object,
    required: true,
  },
})

const emit = defineEmits([
  'accepted',
  'started',
  'ready',
])

/*
|--------------------------------------------------------------------------
| Action State
|--------------------------------------------------------------------------
*/

const processingAction = ref('')

const actionError = ref('')

const isProcessing = computed(() => {
  return Boolean(
    processingAction.value
  )
})

/*
|--------------------------------------------------------------------------
| Item Summary
|--------------------------------------------------------------------------
*/

const totalItemQuantity = computed(() => {
  if (
    Number.isFinite(
      Number(
        props.order
          ?.total_item_quantity
      )
    )
  ) {
    return Number(
      props.order
        .total_item_quantity
    )
  }

  return Array.isArray(
    props.order?.items
  )
    ? props.order.items.reduce(
        (total, item) =>
          total +
          Number(
            item?.quantity || 0
          ),
        0,
      )
    : 0
})

/*
|--------------------------------------------------------------------------
| Status Information
|--------------------------------------------------------------------------
*/

const formattedStatus = computed(() => {
  if (
    props.order?.status ===
      'pending' &&
    props.order?.chef
  ) {
    return 'Accepted'
  }

  return formatLabel(
    props.order?.status
  ) || 'Unknown'
})

const statusClass = computed(() => {
  if (
    props.order?.status ===
      'pending' &&
    props.order?.chef
  ) {
    return 'kitchen-status-accepted'
  }

  switch (
    props.order?.status
  ) {
    case 'pending':
      return 'kitchen-status-pending'

    case 'preparing':
      return 'kitchen-status-preparing'

    case 'ready':
      return 'kitchen-status-ready'

    default:
      return 'kitchen-status-default'
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
    case 'pending':
      return 'bi bi-hourglass-split'

    case 'preparing':
      return 'bi bi-fire'

    case 'ready':
      return 'bi bi-check2-circle'

    default:
      return 'bi bi-circle'
  }
})

/*
|--------------------------------------------------------------------------
| Assigned Chef
|--------------------------------------------------------------------------
*/

const assignedChefInitial =
  computed(() => {
    const chefName =
      props.order?.chef
        ?.username ||
      props.order?.chef?.name

    return chefName
      ? chefName
          .charAt(0)
          .toUpperCase()
      : '?'
  })

/*
|--------------------------------------------------------------------------
| Order Date and Time
|--------------------------------------------------------------------------
*/

const formattedCreatedDate =
  computed(() => {
    return formatDate(
      props.order?.created_at
    )
  })

const formattedCreatedTime =
  computed(() => {
    return formatTime(
      props.order?.created_at
    )
  })

/*
|--------------------------------------------------------------------------
| Kitchen Elapsed Time
|--------------------------------------------------------------------------
*/

const elapsedTimeLabel =
  computed(() => {
    if (
      props.order?.status ===
      'ready'
    ) {
      return 'Total Kitchen Time'
    }

    if (
      props.order?.status ===
      'preparing'
    ) {
      return 'Preparation Time'
    }

    return 'Waiting Time'
  })

const elapsedTime = computed(() => {
  const startValue =
    props.order?.status ===
      'preparing'
      ? props.order?.preparing_at
      : props.order
          ?.sent_to_kitchen_at ||
        props.order?.created_at

  const endValue =
    props.order?.status ===
      'ready'
      ? props.order?.ready_at
      : null

  return calculateElapsedTime(
    startValue,
    endValue
  )
})

const kitchenProgressText =
  computed(() => {
    if (
      props.order?.status ===
      'ready'
    ) {
      return 'Preparation completed'
    }

    if (
      props.order?.status ===
      'preparing'
    ) {
      return 'Currently cooking'
    }

    if (props.order?.chef) {
      return 'Accepted by chef'
    }

    return 'Waiting for acceptance'
  })

/*
|--------------------------------------------------------------------------
| Accept Order
|--------------------------------------------------------------------------
*/

async function acceptKitchenOrder() {
  await executeAction(
    'accept',
    async () => {
      const response =
        await kitchenOrderService
          .acceptOrder(
            props.order.id
          )

      emit(
        'accepted',
        response.data
      )
    },
    'Unable to accept the order.',
  )
}

/*
|--------------------------------------------------------------------------
| Start Preparing
|--------------------------------------------------------------------------
*/

async function startOrderPreparation() {
  await executeAction(
    'preparing',
    async () => {
      const response =
        await kitchenOrderService
          .startPreparing(
            props.order.id
          )

      emit(
        'started',
        response.data
      )
    },
    'Unable to start preparing the order.',
  )
}

/*
|--------------------------------------------------------------------------
| Mark Ready
|--------------------------------------------------------------------------
*/

async function markOrderReady() {
  await executeAction(
    'ready',
    async () => {
      const response =
        await kitchenOrderService
          .markReady(
            props.order.id
          )

      emit(
        'ready',
        response.data
      )
    },
    'Unable to mark the order as ready.',
  )
}

/*
|--------------------------------------------------------------------------
| Execute Kitchen Action
|--------------------------------------------------------------------------
*/

async function executeAction(
  action,
  callback,
  fallbackMessage,
) {
  if (isProcessing.value) {
    return
  }

  processingAction.value =
    action

  actionError.value = ''

  try {
    await callback()
  } catch (error) {
    console.error(
      'Kitchen order action failed:',
      error,
    )

    actionError.value =
      kitchenOrderService
        .getKitchenErrorMessage(
          error,
          fallbackMessage,
        )
  } finally {
    processingAction.value = ''
  }
}

/*
|--------------------------------------------------------------------------
| Formatters
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

function formatDateTime(value) {
  if (!value) {
    return 'Not available'
  }

  const date = new Date(value)

  if (
    Number.isNaN(
      date.getTime()
    )
  ) {
    return 'Not available'
  }

  return new Intl.DateTimeFormat(
    'en-GB',
    {
      day: '2-digit',
      month: 'short',
      year: 'numeric',
      hour: '2-digit',
      minute: '2-digit',
      hour12: true,
    },
  ).format(date)
}

function formatDate(value) {
  if (!value) {
    return 'Not available'
  }

  const date = new Date(value)

  if (
    Number.isNaN(
      date.getTime()
    )
  ) {
    return 'Not available'
  }

  return new Intl.DateTimeFormat(
    'en-GB',
    {
      day: '2-digit',
      month: 'short',
      year: 'numeric',
    },
  ).format(date)
}

function formatTime(value) {
  if (!value) {
    return 'Not available'
  }

  const date = new Date(value)

  if (
    Number.isNaN(
      date.getTime()
    )
  ) {
    return 'Not available'
  }

  return new Intl.DateTimeFormat(
    'en-GB',
    {
      hour: '2-digit',
      minute: '2-digit',
      hour12: true,
    },
  ).format(date)
}

function calculateElapsedTime(
  startValue,
  endValue = null,
) {
  if (!startValue) {
    return '0 min'
  }

  const start = new Date(
    startValue
  )

  const end = endValue
    ? new Date(endValue)
    : new Date()

  if (
    Number.isNaN(
      start.getTime()
    ) ||
    Number.isNaN(
      end.getTime()
    )
  ) {
    return '0 min'
  }

  const difference =
    Math.max(
      0,
      end.getTime() -
        start.getTime(),
    )

  const totalMinutes =
    Math.floor(
      difference / 60000
    )

  if (totalMinutes < 60) {
    return `${totalMinutes} min`
  }

  const hours =
    Math.floor(
      totalMinutes / 60
    )

  const minutes =
    totalMinutes % 60

  return minutes > 0
    ? `${hours}h ${minutes}m`
    : `${hours}h`
}
</script>