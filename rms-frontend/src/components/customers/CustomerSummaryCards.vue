<template>
  <section
    class="customer-summary-grid"
    aria-label="Customer summary"
  >
    <!-- ==================================================
         Loading Cards
    =================================================== -->

    <template v-if="loading">
      <article
        v-for="index in 4"
        :key="`customer-summary-loading-${index}`"
        class="customer-summary-card customer-summary-loading-card"
        aria-hidden="true"
      >
        <div class="customer-summary-card-header">
          <span class="customer-summary-loading-icon"></span>
        </div>

        <div class="customer-summary-card-content">
          <span class="customer-summary-loading-label"></span>

          <strong class="customer-summary-loading-value"></strong>

          <small class="customer-summary-loading-description"></small>
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
        class="customer-summary-card"
        :class="card.className"
      >
        <div class="customer-summary-card-header">
          <span
            class="customer-summary-icon"
            aria-hidden="true"
          >
            <i :class="card.icon"></i>
          </span>

          <span
            v-if="card.badge"
            class="customer-summary-badge"
          >
            {{ card.badge }}
          </span>
        </div>

        <div class="customer-summary-card-content">
          <span class="customer-summary-label">
            {{ card.label }}
          </span>

          <strong class="customer-summary-value">
            {{ card.value }}
          </strong>

          <small class="customer-summary-description">
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
} from 'vue'

/*
|--------------------------------------------------------------------------
| Component Props
|--------------------------------------------------------------------------
*/

const props = defineProps({
  /*
  |--------------------------------------------------------------------------
  | Customer Summary Data
  |--------------------------------------------------------------------------
  */

  summary: {
    type: Object,

    default: () => ({
      total_customers: 0,
      active_customers: 0,
      inactive_customers: 0,
      new_customers_this_month: 0,
      total_visits: 0,
      lifetime_spend: 0,
      lifetime_spend_formatted:
        '৳ 0.00',
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
})

/*
|--------------------------------------------------------------------------
| Summary Cards
|--------------------------------------------------------------------------
*/

const cards = computed(() => {
  return [
    /*
    |--------------------------------------------------------------------------
    | Total Customers
    |--------------------------------------------------------------------------
    */

    {
      key:
        'total-customers',

      label:
        'Total Customers',

      value:
        formatInteger(
          props.summary
            ?.total_customers,
        ),

      description:
        'All registered customer profiles',

      icon:
        'bi bi-people-fill',

      className:
        'customer-total-card',
    },

    /*
    |--------------------------------------------------------------------------
    | Active Customers
    |--------------------------------------------------------------------------
    */

    {
      key:
        'active-customers',

      label:
        'Active Customers',

      value:
        formatInteger(
          props.summary
            ?.active_customers,
        ),

      description:
        'Customers currently marked active',

      icon:
        'bi bi-person-check-fill',

      className:
        'customer-active-card',

      badge:
        buildActivePercentage(),
    },

    /*
    |--------------------------------------------------------------------------
    | New Customers This Month
    |--------------------------------------------------------------------------
    */

    {
      key:
        'new-customers',

      label:
        'New This Month',

      value:
        formatInteger(
          props.summary
            ?.new_customers_this_month,
        ),

      description:
        'Customers added this month',

      icon:
        'bi bi-person-plus-fill',

      className:
        'customer-new-card',
    },

    /*
    |--------------------------------------------------------------------------
    | Lifetime Customer Spend
    |--------------------------------------------------------------------------
    */

    {
      key:
        'lifetime-spend',

      label:
        'Lifetime Spend',

      value:
        resolveFormattedMoney(
          props.summary
            ?.lifetime_spend_formatted,

          props.summary
            ?.lifetime_spend,
        ),

      description:
        'Total recorded customer spending',

      icon:
        'bi bi-wallet2',

      className:
        'customer-spend-card',
    },
  ]
})

/*
|--------------------------------------------------------------------------
| Build Active Percentage
|--------------------------------------------------------------------------
*/

function buildActivePercentage() {
  const totalCustomers =
    toNonNegativeInteger(
      props.summary
        ?.total_customers,
    )

  const activeCustomers =
    toNonNegativeInteger(
      props.summary
        ?.active_customers,
    )

  if (totalCustomers <= 0) {
    return ''
  }

  const percentage =
    Math.round(
      (
        activeCustomers /
        totalCustomers
      ) * 100,
    )

  return `${percentage}% active`
}

/*
|--------------------------------------------------------------------------
| Resolve Formatted Money
|--------------------------------------------------------------------------
*/

function resolveFormattedMoney(
  formattedValue,
  rawValue,
) {
  if (
    typeof formattedValue ===
      'string' &&
    formattedValue.trim() !==
      ''
  ) {
    return formattedValue
  }

  return formatMoney(
    rawValue,
  )
}

/*
|--------------------------------------------------------------------------
| Format Money
|--------------------------------------------------------------------------
*/

function formatMoney(
  value,
) {
  const amount =
    Number(value)

  const resolvedAmount =
    Number.isFinite(
      amount,
    )
      ? amount
      : 0

  return `৳ ${resolvedAmount.toLocaleString(
    'en-GB',
    {
      minimumFractionDigits:
        2,

      maximumFractionDigits:
        2,
    },
  )}`
}

/*
|--------------------------------------------------------------------------
| Format Integer
|--------------------------------------------------------------------------
*/

function formatInteger(
  value,
) {
  return toNonNegativeInteger(
    value,
  ).toLocaleString(
    'en-GB',
  )
}

/*
|--------------------------------------------------------------------------
| Non-negative Integer Helper
|--------------------------------------------------------------------------
*/

function toNonNegativeInteger(
  value,
) {
  const numberValue =
    Number(value)

  if (
    Number.isInteger(
      numberValue,
    ) &&
    numberValue >= 0
  ) {
    return numberValue
  }

  return 0
}
</script>