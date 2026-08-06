<template>
  <section
    class="billing-summary-section"
    aria-label="Billing summary"
  >
    <!-- ==================================================
         Loading Cards
    =================================================== -->

    <template v-if="loading">
      <article
        v-for="index in 8"
        :key="`billing-summary-loading-${index}`"
        class="billing-summary-card billing-summary-loading-card"
        aria-hidden="true"
      >
        <div class="billing-summary-card-header">
          <span class="billing-summary-loading-icon"></span>
        </div>

        <div class="billing-summary-card-content">
          <span class="billing-summary-loading-label"></span>

          <strong class="billing-summary-loading-value"></strong>

          <small class="billing-summary-loading-description"></small>
        </div>
      </article>
    </template>

    <!-- ==================================================
         Summary Cards
    =================================================== -->

    <template v-else>
      <article
        v-for="card in cards"
        :key="card.key"
        class="billing-summary-card"
        :class="card.className"
      >
        <!-- ================================================
             Card Header
        ================================================= -->

        <div class="billing-summary-card-header">
          <span
            class="billing-summary-icon"
            aria-hidden="true"
          >
            <i :class="card.icon"></i>
          </span>

          <span
            v-if="card.comingSoon"
            class="billing-coming-soon"
          >
            Coming soon
          </span>
        </div>

        <!-- ================================================
             Card Content
        ================================================= -->

        <div class="billing-summary-card-content">
          <span>
            {{ card.label }}
          </span>

          <strong>
            {{ card.value }}
          </strong>

          <small>
            {{ card.description }}
          </small>
        </div>
      </article>
    </template>
  </section>
</template>

<script setup>
import {
  computed,
} from "vue";

/*
|--------------------------------------------------------------------------
| Component Props
|--------------------------------------------------------------------------
*/

const props = defineProps({
  /*
  |--------------------------------------------------------------------------
  | Billing Summary Data
  |--------------------------------------------------------------------------
  */

  summary: {
    type: Object,

    default: () => ({
      net_sales: 0,
      collected_amount: 0,
      tax: 0,
      service_charge: 0,
      expenses: 0,
      cash_collection: 0,
      outstanding_due: 0,
      total_orders: 0,
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
});

/*
|--------------------------------------------------------------------------
| Summary Card Configuration
|--------------------------------------------------------------------------
*/

const cards = computed(() => {
  return [
    /*
    |--------------------------------------------------------------------------
    | Net Sales
    |--------------------------------------------------------------------------
    |
    | Net Sales = Gross Sales - Discount
    |
    */

    {
      key: "net-sales",

      label: "Net Sales",

      value:
        resolveFormattedAmount({
          formattedValue:
            props.summary
              ?.net_sales_formatted,

          rawValue:
            props.summary
              ?.net_sales,
        }),

      description:
        "Gross sales minus discounts",

      icon:
        "bi bi-graph-up-arrow",

      className:
        "sales-card",
    },

    /*
    |--------------------------------------------------------------------------
    | Collected Amount
    |--------------------------------------------------------------------------
    */

    {
      key: "collected",

      label:
        "Collected Amount",

      value:
        resolveFormattedAmount({
          formattedValue:
            props.summary
              ?.collected_amount_formatted,

          rawValue:
            props.summary
              ?.collected_amount,
        }),

      description:
        "Actual payments received",

      icon:
        "bi bi-wallet2",

      className:
        "collected-card",
    },

    /*
    |--------------------------------------------------------------------------
    | Tax
    |--------------------------------------------------------------------------
    */

    {
      key: "tax",

      label: "Tax",

      value:
        resolveFormattedAmount({
          formattedValue:
            props.summary
              ?.tax_formatted,

          rawValue:
            props.summary
              ?.tax,
        }),

      description:
        "Tax charged on valid orders",

      icon:
        "bi bi-percent",

      className:
        "tax-card",
    },

    /*
    |--------------------------------------------------------------------------
    | Service Charge
    |--------------------------------------------------------------------------
    */

    {
      key: "service-charge",

      label:
        "Service Charges",

      value:
        resolveFormattedAmount({
          formattedValue:
            props.summary
              ?.service_charge_formatted,

          rawValue:
            props.summary
              ?.service_charge,
        }),

      description:
        "Service charges on valid orders",

      icon:
        "bi bi-stars",

      className:
        "service-card",
    },

    /*
    |--------------------------------------------------------------------------
    | Expenses
    |--------------------------------------------------------------------------
    */

    {
      key: "expenses",

      label: "Expenses",

      value:
        resolveFormattedAmount({
          formattedValue:
            props.summary
              ?.expenses_formatted,

          rawValue:
            props.summary
              ?.expenses,
        }),

      description:
        "Expense module not connected yet",

      icon:
        "bi bi-box-arrow-up-right",

      className:
        "expense-card",

      comingSoon: true,
    },

    /*
    |--------------------------------------------------------------------------
    | Cash Collection
    |--------------------------------------------------------------------------
    */

    {
      key: "cash",

      label:
        "Cash Collection",

      value:
        resolveFormattedAmount({
          formattedValue:
            props.summary
              ?.cash_collection_formatted,

          rawValue:
            props.summary
              ?.cash_collection,
        }),

      description:
        "Payments received by cash",

      icon:
        "bi bi-cash-stack",

      className:
        "cash-card",
    },

    /*
    |--------------------------------------------------------------------------
    | Outstanding Due
    |--------------------------------------------------------------------------
    */

    {
      key: "due",

      label:
        "Outstanding Due",

      value:
        resolveFormattedAmount({
          formattedValue:
            props.summary
              ?.outstanding_due_formatted,

          rawValue:
            props.summary
              ?.outstanding_due,
        }),

      description:
        "Remaining unpaid order balance",

      icon:
        "bi bi-exclamation-circle",

      className:
        "due-card",
    },

    /*
    |--------------------------------------------------------------------------
    | Total Orders
    |--------------------------------------------------------------------------
    */

    {
      key: "orders",

      label: "Total Orders",

      value:
        formatOrderCount(
          props.summary
            ?.total_orders
        ),

      description:
        "Non-canceled orders in period",

      icon:
        "bi bi-receipt",

      className:
        "orders-card",
    },
  ];
});

/*
|--------------------------------------------------------------------------
| Resolve Formatted Amount
|--------------------------------------------------------------------------
*/

function resolveFormattedAmount({
  formattedValue,
  rawValue,
}) {
  if (
    typeof formattedValue ===
      "string" &&
    formattedValue.trim() !== ""
  ) {
    return formattedValue;
  }

  return formatCurrency(
    rawValue
  );
}

/*
|--------------------------------------------------------------------------
| Format Order Count
|--------------------------------------------------------------------------
*/

function formatOrderCount(
  value
) {
  const resolvedValue =
    Number(value);

  if (
    !Number.isFinite(
      resolvedValue
    ) ||
    resolvedValue < 0
  ) {
    return "0";
  }

  return Math.floor(
    resolvedValue
  ).toLocaleString(
    "en-GB"
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