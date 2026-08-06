<template>
  <section
    id="payment-activity-section"
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
          <i class="bi bi-people"></i>
        </span>

        <div>
          <h2>
            Payment Activity
          </h2>

          <p>
            Staff and payment activity for
            {{ dateRangeText }}
          </p>
        </div>
      </div>

      <!-- ==================================================
           Filters and Actions
      =================================================== -->

      <div class="billing-report-actions">
        <!-- User Role Filter -->

        <select
          class="billing-filter-select"
          :value="userType"
          :disabled="loading"
          aria-label="Filter payment activity by user role"
          @change="handleUserTypeChange"
        >
          <option value="all">
            All Roles
          </option>

          <option value="waiter">
            Order Creator
          </option>

          <option value="chef">
            Chef
          </option>

          <option value="receiver">
            Payment Receiver
          </option>
        </select>

        <!-- User Filter -->

        <select
          class="billing-filter-select"
          :value="String(userId || '')"
          :disabled="loading"
          aria-label="Filter payment activity by user"
          @change="handleUserChange"
        >
          <option value="">
            All Users
          </option>

          <option
            v-for="user in users"
            :key="user.id"
            :value="String(user.id)"
          >
            {{ resolveUserName(user) }}
          </option>
        </select>

        <!-- Extract Button -->

        <button
          type="button"
          class="billing-action-button extract-button"
          :disabled="
            loading ||
            activities.length === 0
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
            activities.length === 0
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
      text="Loading payment activity..."
    />

    <!-- ==================================================
         Error State
    =================================================== -->

    <BillingEmptyState
      v-else-if="errorMessage"
      icon="bi bi-exclamation-circle"
      title="Unable to load payment activity"
      :description="errorMessage"
      button-text="Retry"
      :is-error="true"
      @retry="emit('retry')"
    />

    <!-- ==================================================
         Empty State
    =================================================== -->

    <BillingEmptyState
      v-else-if="activities.length === 0"
      icon="bi bi-person-lines-fill"
      title="No payment activity found"
      description="No payment activity matches the selected date and user filters."
    />

    <!-- ==================================================
         Payment Activity Table
    =================================================== -->

    <template v-else>
      <div class="billing-table-wrapper">
        <table class="billing-table billing-activity-table">
          <thead>
            <tr>
              <th>
                Order ID
              </th>

              <th>
                Date &amp; Time
              </th>

              <th>
                Customer
              </th>

              <th>
                Payment Method
              </th>

              <th class="text-right">
                Amount
              </th>

              <th>
                Order Creator
              </th>

              <th>
                Chef
              </th>

              <th>
                Received By
              </th>
            </tr>
          </thead>

          <tbody>
            <tr
              v-for="activity in activities"
              :key="activity.id"
            >
              <!-- Order ID -->

              <td>
                <strong class="billing-order-number">
                  {{
                    activity.order_number ||
                    `#${activity.order_id}`
                  }}
                </strong>
              </td>

              <!-- Date and Time -->

              <td>
                <strong>
                  {{
                    activity.date ||
                    "—"
                  }}
                </strong>

                <small>
                  {{
                    activity.time ||
                    "—"
                  }}
                </small>
              </td>

              <!-- Customer -->

              <td>
                <strong>
                  {{
                    activity.customer?.name ||
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
                      activity.payment_method
                    )
                  "
                >
                  <i
                    :class="
                      getPaymentMethodIcon(
                        activity.payment_method
                      )
                    "
                    aria-hidden="true"
                  ></i>

                  <span>
                    {{
                      activity.payment_method_label ||
                      formatLabel(
                        activity.payment_method
                      )
                    }}
                  </span>
                </span>
              </td>

              <!-- Amount -->

              <td class="text-right">
                <strong class="billing-amount-text">
                  {{
                    activity.amount_formatted ||
                    formatCurrency(
                      activity.amount
                    )
                  }}
                </strong>
              </td>

              <!-- Order Creator -->

              <td>
                <div class="billing-user-display">
                  <span class="billing-user-avatar">
                    {{
                      getUserInitial(
                        activity.waiter
                      )
                    }}
                  </span>

                  <div class="billing-user-details">
                    <strong>
                      {{
                        resolveUserName(
                          activity.waiter
                        )
                      }}
                    </strong>

                    <small
                      v-if="
                        resolveSecondaryName(
                          activity.waiter
                        )
                      "
                    >
                      {{
                        resolveSecondaryName(
                          activity.waiter
                        )
                      }}
                    </small>
                  </div>
                </div>
              </td>

              <!-- Chef -->

              <td>
                <div class="billing-user-display">
                  <span class="billing-user-avatar chef-avatar">
                    {{
                      getUserInitial(
                        activity.chef
                      )
                    }}
                  </span>

                  <div class="billing-user-details">
                    <strong>
                      {{
                        resolveUserName(
                          activity.chef
                        )
                      }}
                    </strong>

                    <small
                      v-if="
                        resolveSecondaryName(
                          activity.chef
                        )
                      "
                    >
                      {{
                        resolveSecondaryName(
                          activity.chef
                        )
                      }}
                    </small>
                  </div>
                </div>
              </td>

              <!-- Payment Receiver -->

              <td>
                <div class="billing-user-display">
                  <span class="billing-user-avatar receiver-avatar">
                    {{
                      getUserInitial(
                        activity.receiver
                      )
                    }}
                  </span>

                  <div class="billing-user-details">
                    <strong>
                      {{
                        resolveUserName(
                          activity.receiver
                        )
                      }}
                    </strong>

                    <small
                      v-if="
                        resolveSecondaryName(
                          activity.receiver
                        )
                      "
                    >
                      {{
                        resolveSecondaryName(
                          activity.receiver
                        )
                      }}
                    </small>
                  </div>
                </div>
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

              <td colspan="3"></td>
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
  | Payment Activities
  |--------------------------------------------------------------------------
  */

  activities: {
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
  | Billing Users
  |--------------------------------------------------------------------------
  */

  users: {
    type: Array,
    default: () => [],
  },

  /*
  |--------------------------------------------------------------------------
  | Selected User ID
  |--------------------------------------------------------------------------
  */

  userId: {
    type: [
      String,
      Number,
    ],

    default: "",
  },

  /*
  |--------------------------------------------------------------------------
  | Selected User Role
  |--------------------------------------------------------------------------
  */

  userType: {
    type: String,
    default: "all",
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
  "update:user-id",
  "update:user-type",
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
| Handle User Role Filter
|--------------------------------------------------------------------------
*/

function handleUserTypeChange(
  event
) {
  emit(
    "update:user-type",
    event?.target?.value ||
      "all"
  );
}

/*
|--------------------------------------------------------------------------
| Handle User Filter
|--------------------------------------------------------------------------
*/

function handleUserChange(
  event
) {
  emit(
    "update:user-id",
    event?.target?.value ||
      ""
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
    props.activities.length === 0
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
    props.activities.length === 0
  ) {
    return;
  }

  emit("print");
}

/*
|--------------------------------------------------------------------------
| Resolve User Display Name
|--------------------------------------------------------------------------
*/

function resolveUserName(
  user
) {
  return (
    user?.username ||
    user?.display_name ||
    user?.name ||
    "Not Assigned"
  );
}

/*
|--------------------------------------------------------------------------
| Resolve Secondary User Name
|--------------------------------------------------------------------------
*/

function resolveSecondaryName(
  user
) {
  const primaryName =
    resolveUserName(
      user
    );

  const fullName =
    user?.name || "";

  if (
    !fullName ||
    fullName === primaryName
  ) {
    return "";
  }

  return fullName;
}

/*
|--------------------------------------------------------------------------
| Resolve User Initial
|--------------------------------------------------------------------------
*/

function getUserInitial(
  user
) {
  const name =
    resolveUserName(
      user
    );

  if (
    !name ||
    name === "Not Assigned"
  ) {
    return "—";
  }

  return name
    .trim()
    .charAt(0)
    .toUpperCase();
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