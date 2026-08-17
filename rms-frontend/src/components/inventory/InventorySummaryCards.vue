<template>
  <section
    class="inventory-summary-grid"
    aria-label="Inventory summary"
    :aria-busy="loading"
  >
    <!-- Total Raw Materials -->

    <article
      class="inventory-summary-card inventory-summary-total"
    >
      <div class="inventory-summary-card-icon">
        <i
          class="bi bi-boxes"
          aria-hidden="true"
        ></i>
      </div>

      <div class="inventory-summary-card-content">
        <span class="inventory-summary-label">
          Total Raw Materials
        </span>

        <strong class="inventory-summary-value">
          {{
            loading
              ? '—'
              : countValue(
                  summary.total_raw_materials,
                )
          }}
        </strong>

        <small>
          Active inventory materials
        </small>
      </div>
    </article>


    <!-- Available -->

    <article
      class="inventory-summary-card inventory-summary-available"
    >
      <div class="inventory-summary-card-icon">
        <i
          class="bi bi-check-circle"
          aria-hidden="true"
        ></i>
      </div>

      <div class="inventory-summary-card-content">
        <span class="inventory-summary-label">
          Available
        </span>

        <strong class="inventory-summary-value">
          {{
            loading
              ? '—'
              : countValue(
                  summary.available_count,
                )
          }}
        </strong>

        <small>
          Above minimum level
        </small>
      </div>
    </article>


    <!-- Limited -->

    <article
      class="inventory-summary-card inventory-summary-limited"
    >
      <div class="inventory-summary-card-icon">
        <i
          class="bi bi-exclamation-triangle"
          aria-hidden="true"
        ></i>
      </div>

      <div class="inventory-summary-card-content">
        <span class="inventory-summary-label">
          Limited Stock
        </span>

        <strong class="inventory-summary-value">
          {{
            loading
              ? '—'
              : countValue(
                  summary.limited_count,
                )
          }}
        </strong>

        <small>
          At or below minimum
        </small>
      </div>
    </article>


    <!-- Out of Stock -->

    <article
      class="inventory-summary-card inventory-summary-out"
    >
      <div class="inventory-summary-card-icon">
        <i
          class="bi bi-x-circle"
          aria-hidden="true"
        ></i>
      </div>

      <div class="inventory-summary-card-content">
        <span class="inventory-summary-label">
          Out of Stock
        </span>

        <strong class="inventory-summary-value">
          {{
            loading
              ? '—'
              : countValue(
                  summary.out_of_stock_count,
                )
          }}
        </strong>

        <small>
          Requires stock immediately
        </small>
      </div>
    </article>


    <!-- Warehouse Value -->

    <article
      class="inventory-summary-card inventory-summary-value-card"
    >
      <div class="inventory-summary-card-icon">
        <i
          class="bi bi-cash-stack"
          aria-hidden="true"
        ></i>
      </div>

      <div class="inventory-summary-card-content">
        <span class="inventory-summary-label">
          Warehouse Value
        </span>

        <strong
          class="inventory-summary-value inventory-money-value"
        >
          {{
            loading
              ? '—'
              : stockValue
          }}
        </strong>

        <small>
          Current warehouse valuation
        </small>
      </div>
    </article>
  </section>
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
      total_raw_materials: 0,
      available_count: 0,
      limited_count: 0,
      out_of_stock_count: 0,
      total_stock_value: 0,

      total_stock_value_formatted:
        '৳ 0.00',
    }),
  },


  loading: {
    type: Boolean,
    default: false,
  },
})


/*
|--------------------------------------------------------------------------
| Warehouse Stock Value
|--------------------------------------------------------------------------
*/


const stockValue = computed(() => {
  const formattedValue =
    props.summary
      ?.total_stock_value_formatted


  if (
    typeof formattedValue ===
      'string'
    &&
    formattedValue.trim() !== ''
  ) {
    return formattedValue.trim()
  }


  const numericValue =
    Number(
      props.summary
        ?.total_stock_value,
    )


  const safeValue =
    Number.isFinite(
      numericValue,
    )
    &&
    numericValue >= 0
      ? numericValue
      : 0


  return `৳ ${safeValue.toLocaleString(
    'en-BD',
    {
      minimumFractionDigits: 2,
      maximumFractionDigits: 2,
    },
  )}`
})


/*
|--------------------------------------------------------------------------
| Count Formatter
|--------------------------------------------------------------------------
*/


function countValue(
  value,
) {
  const numericValue =
    Number(
      value,
    )


  if (
    !Number.isFinite(
      numericValue,
    )
    ||
    numericValue <= 0
  ) {
    return 0
  }


  return Math.floor(
    numericValue,
  )
}
</script>