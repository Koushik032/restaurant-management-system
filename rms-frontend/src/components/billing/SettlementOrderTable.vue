<template>
  <section
    id="settlement-section"
    class="billing-report-card"
  >
    <!-- ==================================================
         Report Header
    =================================================== -->

    <header class="billing-report-header">
      <div class="billing-report-heading">
        <span
          class="billing-report-icon"
          aria-hidden="true"
        >
          <i class="bi bi-receipt"></i>
        </span>

        <div>
          <h2>
            Settlement Orders
          </h2>

          <p>
            Order settlement report for
            {{ dateRangeText }}
          </p>
        </div>
      </div>

      <!-- ==================================================
           Filters and Actions
      =================================================== -->

      <div class="billing-report-actions">
        <!-- Status Filter -->

        <select
          class="billing-filter-select"
          :value="status"
          :disabled="loading"
          aria-label="Filter settlement orders by status"
          @change="handleStatusChange"
        >
          <option
            v-for="option in statusOptions"
            :key="option.value"
            :value="option.value"
          >
            {{ option.label }}
          </option>
        </select>

        <!-- Extract Button -->

        <button
          type="button"
          class="billing-action-button extract-button"
          :disabled="
            loading ||
            orders.length === 0
          "
          @click="handleExtract"
        >
          <i
            class="bi bi-download"
            aria-hidden="true"
          ></i>

          <span>
            Extract
          </span>
        </button>

        <!-- Print Button -->

        <button
          type="button"
          class="billing-action-button print-button"
          :disabled="
            loading ||
            orders.length === 0
          "
          @click="handlePrint"
        >
          <i
            class="bi bi-printer"
            aria-hidden="true"
          ></i>

          <span>
            Print
          </span>
        </button>
      </div>
    </header>

    <!-- ==================================================
         Loading State
    =================================================== -->

    <BillingLoading
      v-if="loading"
      text="Loading settlement orders..."
    />

    <!-- ==================================================
         Error State
    =================================================== -->

    <BillingEmptyState
      v-else-if="errorMessage"
      icon="bi bi-exclamation-circle"
      title="Unable to load settlement orders"
      :description="errorMessage"
      button-text="Retry"
      :is-error="true"
      @retry="emit('retry')"
    />

    <!-- ==================================================
         Empty State
    =================================================== -->

    <BillingEmptyState
      v-else-if="orders.length === 0"
      icon="bi bi-receipt-cutoff"
      title="No settlement orders found"
      description="No order matches the selected date and status filter."
    />

    <!-- ==================================================
         Settlement Table
    =================================================== -->

    <template v-else>
      <div class="billing-table-wrapper">
        <table class="billing-table">
          <thead>
            <tr>
              <th>
                Order ID
              </th>

              <th>
                Customer Name
              </th>

              <th>
                Order Status
              </th>

              <th>
                Payment Status
              </th>

              <th class="text-right">
                Total Amount
              </th>

              <th class="text-right">
                Due Amount
              </th>
            </tr>
          </thead>

          <tbody>
            <tr
              v-for="order in orders"
              :key="order.id"
            >
              <!-- Order ID -->

              <td>
                <strong class="billing-order-number">
                  {{
                    order.order_number ||
                    `#${order.id}`
                  }}
                </strong>

                <small>
                  {{
                    order.time ||
                    "—"
                  }}
                </small>
              </td>

              <!-- Customer -->

              <td>
                <strong>
                  {{
                    order.customer?.name ||
                    "Walk-in Customer"
                  }}
                </strong>
              </td>

              <!-- Order Status -->

              <td>
                <span
                  class="billing-status-badge"
                  :class="
                    getOrderStatusClass(
                      order.order_status
                    )
                  "
                >
                  {{
                    order.order_status_label ||
                    formatLabel(
                      order.order_status
                    )
                  }}
                </span>
              </td>

              <!-- Payment Status -->

              <td>
                <span
                  class="billing-status-badge"
                  :class="
                    getPaymentStatusClass(
                      order.payment_status
                    )
                  "
                >
                  {{
                    order.payment_status_label ||
                    formatLabel(
                      order.payment_status
                    )
                  }}
                </span>
              </td>

              <!-- Total Amount -->

              <td class="text-right">
                <strong class="billing-amount-text">
                  {{
                    order.total_amount_formatted ||
                    formatCurrency(
                      order.total_amount
                    )
                  }}
                </strong>

                <small
                  v-if="
                    Number(
                      order.paid_amount
                    ) > 0
                  "
                >
                  Paid:
                  {{
                    order.paid_amount_formatted ||
                    formatCurrency(
                      order.paid_amount
                    )
                  }}
                </small>
              </td>

              <!-- Due Amount -->

              <td class="text-right">
                <strong
                  class="billing-amount-text"
                  :class="{
                    'billing-due-amount':
                      Number(
                        order.due_amount
                      ) > 0,

                    'billing-paid-amount':
                      Number(
                        order.due_amount
                      ) <= 0,
                  }"
                >
                  {{
                    order.due_amount_formatted ||
                    formatCurrency(
                      order.due_amount
                    )
                  }}
                </strong>
              </td>
            </tr>
          </tbody>
                    <!-- ==================================================
               Filtered Total
          =================================================== -->

          <tfoot>
            <tr class="billing-table-total-row">
              <td
                colspan="4"
                class="billing-table-total-label"
              >
                <div>
                  <strong>
                    Filtered Total Amount
                  </strong>

                  <small>
                    {{ dateRangeText }}
                  </small>
                </div>
              </td>

              <!-- Total Amount -->

              <td
                class="billing-table-total-amount text-right"
              >
                {{
                  totals.amount_formatted ||
                  formatCurrency(
                    totals.amount
                  )
                }}
              </td>

              <!-- Empty Due Column -->

              <td></td>
            </tr>
          </tfoot>
        </table>
      </div>

      <!-- ==================================================
           Pagination
      =================================================== -->

      <BillingPagination
        :meta="meta"
        @change="
          emit(
            'page-change',
            $event
          )
        "
      />
    </template>
  </section>
</template>

<script setup>
import {
  computed,
} from "vue";

import BillingPagination
  from "./BillingPagination.vue";

import BillingLoading
  from "./BillingLoading.vue";

import BillingEmptyState
  from "./BillingEmptyState.vue";

/*
|--------------------------------------------------------------------------
| Component Props
|--------------------------------------------------------------------------
*/

const props = defineProps({
  /*
  |--------------------------------------------------------------------------
  | Settlement Orders
  |--------------------------------------------------------------------------
  */

  orders: {
    type: Array,
    default: () => [],
  },

  /*
  |--------------------------------------------------------------------------
  | Pagination Metadata
  |--------------------------------------------------------------------------
  */

  meta: {
    type: Object,

    default: () => ({
      current_page: 1,
      last_page: 1,
      per_page: 5,
      total: 0,
      from: null,
      to: null,
    }),
  },

  /*
  |--------------------------------------------------------------------------
  | Filtered Total
  |--------------------------------------------------------------------------
  */

  totals: {
    type: Object,

    default: () => ({
      amount: 0,
      amount_formatted:
        "৳ 0.00",
    }),
  },

  /*
  |--------------------------------------------------------------------------
  | Loading State
  |--------------------------------------------------------------------------
  */

  loading: {
    type: Boolean,
    default: false,
  },

  /*
  |--------------------------------------------------------------------------
  | Error Message
  |--------------------------------------------------------------------------
  */

  errorMessage: {
    type: String,
    default: "",
  },

  /*
  |--------------------------------------------------------------------------
  | From Date
  |--------------------------------------------------------------------------
  */

  dateFrom: {
    type: String,
    default: "",
  },

  /*
  |--------------------------------------------------------------------------
  | To Date
  |--------------------------------------------------------------------------
  */

  dateTo: {
    type: String,
    default: "",
  },

  /*
  |--------------------------------------------------------------------------
  | Selected Status
  |--------------------------------------------------------------------------
  */

  status: {
    type: String,
    default: "",
  },

  /*
  |--------------------------------------------------------------------------
  | Status Options
  |--------------------------------------------------------------------------
  */

  statusOptions: {
    type: Array,
    default: () => [],
  },
});

/*
|--------------------------------------------------------------------------
| Component Events
|--------------------------------------------------------------------------
*/

const emit = defineEmits([
  "update:status",
  "page-change",
  "retry",
  "extract",
  "print",
]);

/*
|--------------------------------------------------------------------------
| Selected Date Range
|--------------------------------------------------------------------------
*/

const dateRangeText =
  computed(() => {
    const from =
      props.dateFrom;

    const to =
      props.dateTo;

    if (
      from &&
      to
    ) {
      if (
        from === to
      ) {
        return formatDate(
          from
        );
      }

      return `${formatDate(
        from
      )} to ${formatDate(
        to
      )}`;
    }

    if (from) {
      return formatDate(
        from
      );
    }

    if (to) {
      return formatDate(
        to
      );
    }

    return "Today's records";
  });
  /*
|--------------------------------------------------------------------------
| Handle Status Filter
|--------------------------------------------------------------------------
*/

function handleStatusChange(
  event
) {
  emit(
    "update:status",
    event?.target?.value || ""
  );
}

/*
|--------------------------------------------------------------------------
| Handle Extract
|--------------------------------------------------------------------------
*/

function handleExtract() {
  if (
    props.loading ||
    props.orders.length === 0
  ) {
    return;
  }

  emit("extract");
}

/*
|--------------------------------------------------------------------------
| Handle Print
|--------------------------------------------------------------------------
*/

function handlePrint() {
  if (
    props.loading ||
    props.orders.length === 0
  ) {
    return;
  }

  emit("print");
}

/*
|--------------------------------------------------------------------------
| Order Status Class
|--------------------------------------------------------------------------
*/

function getOrderStatusClass(
  status
) {
  const classes = {
    pending:
      "status-pending",

    preparing:
      "status-preparing",

    ready:
      "status-ready",

    served:
      "status-served",

    completed:
      "status-completed",

    canceled:
      "status-canceled",
  };

  return (
    classes[status] ||
    "status-default"
  );
}

/*
|--------------------------------------------------------------------------
| Payment Status Class
|--------------------------------------------------------------------------
*/

function getPaymentStatusClass(
  status
) {
  const classes = {
    due:
      "payment-due",

    partially_paid:
      "payment-partial",

    paid:
      "payment-paid",
  };

  return (
    classes[status] ||
    "payment-default"
  );
}

/*
|--------------------------------------------------------------------------
| Date Formatter
|--------------------------------------------------------------------------
*/

function formatDate(
  value
) {
  if (!value) {
    return "—";
  }

  const date =
    new Date(
      `${value}T00:00:00`
    );

  if (
    Number.isNaN(
      date.getTime()
    )
  ) {
    return value;
  }

  return new Intl.DateTimeFormat(
    "en-GB",
    {
      day: "2-digit",
      month: "short",
      year: "numeric",
    }
  ).format(date);
}

/*
|--------------------------------------------------------------------------
| Readable Label Formatter
|--------------------------------------------------------------------------
*/

function formatLabel(
  value
) {
  if (!value) {
    return "Not Available";
  }

  return String(value)
    .replaceAll(
      "_",
      " "
    )
    .replace(
      /\b\w/g,
      (letter) =>
        letter.toUpperCase()
    );
}

/*
|--------------------------------------------------------------------------
| Currency Formatter
|--------------------------------------------------------------------------
*/

function formatCurrency(
  value
) {
  const amount =
    Number(value);

  return `৳ ${(
    Number.isFinite(amount)
      ? amount
      : 0
  ).toLocaleString(
    "en-GB",
    {
      minimumFractionDigits: 2,
      maximumFractionDigits: 2,
    }
  )}`;
}
</script>