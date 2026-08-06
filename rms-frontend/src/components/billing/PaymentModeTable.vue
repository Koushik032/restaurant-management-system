<template>
  <section
    id="payment-mode-section"
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
          <i class="bi bi-credit-card"></i>
        </span>

        <div>
          <h2>
            Payment Mode
          </h2>

          <p>
            Payment transactions for
            {{ dateRangeText }}
          </p>
        </div>
      </div>

      <!-- ==================================================
           Filters and Actions
      =================================================== -->

      <div class="billing-report-actions">
        <!-- Payment Method Filter -->

        <select
          class="billing-filter-select"
          :value="paymentMethod"
          :disabled="loading"
          aria-label="Filter by payment method"
          @change="handlePaymentMethodChange"
        >
          <option
            v-for="method in paymentMethodOptions"
            :key="method.value"
            :value="method.value"
          >
            {{ method.label }}
          </option>
        </select>

        <!-- Extract Button -->

        <button
          type="button"
          class="billing-action-button extract-button"
          :disabled="
            loading ||
            payments.length === 0
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
            payments.length === 0
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
      text="Loading payment transactions..."
    />

    <!-- ==================================================
         Error State
    =================================================== -->

    <BillingEmptyState
      v-else-if="errorMessage"
      icon="bi bi-exclamation-circle"
      title="Unable to load payment transactions"
      :description="errorMessage"
      button-text="Retry"
      :is-error="true"
      @retry="emit('retry')"
    />

    <!-- ==================================================
         Empty State
    =================================================== -->

    <BillingEmptyState
      v-else-if="payments.length === 0"
      icon="bi bi-wallet2"
      title="No payment transactions found"
      description="No payment record matches the selected date and payment method."
    />

    <!-- ==================================================
         Payment Mode Table
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
                Payment Method
              </th>

              <th>
                Reference
              </th>

              <th>
                Date &amp; Time
              </th>

              <th class="text-right">
                Amount
              </th>
            </tr>
          </thead>

          <tbody>
            <tr
              v-for="payment in payments"
              :key="payment.id"
            >
              <!-- Order ID -->

              <td>
                <strong class="billing-order-number">
                  {{
                    payment.order_number ||
                    `#${payment.order_id}`
                  }}
                </strong>
              </td>

              <!-- Customer -->

              <td>
                <strong>
                  {{
                    payment.customer?.name ||
                    "Walk-in Customer"
                  }}
                </strong>
              </td>

              <!-- Payment Method -->

              <td>
                <span
                  class="billing-payment-method"
                  :class="
                    getPaymentMethodClass(
                      payment.payment_method
                    )
                  "
                >
                  <i
                    :class="
                      getPaymentMethodIcon(
                        payment.payment_method
                      )
                    "
                    aria-hidden="true"
                  ></i>

                  <span>
                    {{
                      payment.payment_method_label ||
                      formatLabel(
                        payment.payment_method
                      )
                    }}
                  </span>
                </span>
              </td>

              <!-- Reference -->

              <td>
                <span class="billing-reference-text">
                  {{
                    payment.reference ||
                    "—"
                  }}
                </span>
              </td>

              <!-- Date & Time -->

              <td>
                <strong>
                  {{
                    payment.date ||
                    "—"
                  }}
                </strong>

                <small>
                  {{
                    payment.time ||
                    "—"
                  }}
                </small>
              </td>

              <!-- Amount -->

              <td class="text-right">
                <strong class="billing-amount-text">
                  {{
                    payment.amount_formatted ||
                    formatCurrency(
                      payment.amount
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
                colspan="5"
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
  | Payment Transactions
  |--------------------------------------------------------------------------
  */

  payments: {
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
  | Filtered Totals
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
  | Selected Payment Method
  |--------------------------------------------------------------------------
  */

  paymentMethod: {
    type: String,
    default: "",
  },

  /*
  |--------------------------------------------------------------------------
  | Payment Method Filter Options
  |--------------------------------------------------------------------------
  */

  paymentMethodOptions: {
    type: Array,

    default: () => [
      {
        value: "",
        label: "All Methods",
      },
    ],
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
});

/*
|--------------------------------------------------------------------------
| Component Events
|--------------------------------------------------------------------------
*/

const emit = defineEmits([
  "update:payment-method",
  "page-change",
  "retry",
  "extract",
  "print",
]);

/*
|--------------------------------------------------------------------------
| Selected Date Range Text
|--------------------------------------------------------------------------
*/

const dateRangeText =
  computed(() => {
    const fromDate =
      props.dateFrom;

    const toDate =
      props.dateTo;

    if (
      fromDate &&
      toDate
    ) {
      if (
        fromDate ===
        toDate
      ) {
        return formatDate(
          fromDate
        );
      }

      return `${formatDate(
        fromDate
      )} to ${formatDate(
        toDate
      )}`;
    }

    if (fromDate) {
      return formatDate(
        fromDate
      );
    }

    if (toDate) {
      return formatDate(
        toDate
      );
    }

    return "Today's records";
  });

/*
|--------------------------------------------------------------------------
| Handle Payment Method Filter
|--------------------------------------------------------------------------
*/

function handlePaymentMethodChange(
  event
) {
  emit(
    "update:payment-method",
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
    props.payments.length === 0
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
    props.payments.length === 0
  ) {
    return;
  }

  emit("print");
}

/*
|--------------------------------------------------------------------------
| Payment Method Icon
|--------------------------------------------------------------------------
*/

function getPaymentMethodIcon(
  method
) {
  const icons = {
    cash:
      "bi bi-cash-stack",

    card:
      "bi bi-credit-card",

    bkash:
      "bi bi-phone",

    nagad:
      "bi bi-phone",

    bank_transfer:
      "bi bi-bank",

    mixed:
      "bi bi-cash-coin",
  };

  return (
    icons[method] ||
    "bi bi-wallet2"
  );
}

/*
|--------------------------------------------------------------------------
| Payment Method Class
|--------------------------------------------------------------------------
*/

function getPaymentMethodClass(
  method
) {
  const classes = {
    cash:
      "payment-method-cash",

    card:
      "payment-method-card",

    bkash:
      "payment-method-bkash",

    nagad:
      "payment-method-nagad",

    bank_transfer:
      "payment-method-bank",

    mixed:
      "payment-method-mixed",
  };

  return (
    classes[method] ||
    "payment-method-default"
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
| Readable Label
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