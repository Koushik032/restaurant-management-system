<template>
  <section class="customer-table-card">
    <!-- ==================================================
         Table Header
    =================================================== -->

    <header class="customer-table-header">
      <div>
        <h2>
          Customer List
        </h2>

        <p>
          View customer visits, spending and account status.
        </p>
      </div>

      <span class="customer-record-count">
        {{ meta.total || 0 }} records
      </span>
    </header>

    <!-- ==================================================
         Loading State
    =================================================== -->

    <div
      v-if="loading"
      class="customer-table-loading"
      role="status"
      aria-live="polite"
    >
      <span
        class="spinner-border"
        aria-hidden="true"
      ></span>

      <p>
        Loading customers...
      </p>
    </div>

    <!-- ==================================================
         Error State
    =================================================== -->

    <div
      v-else-if="errorMessage"
      class="customer-table-empty customer-table-error"
      role="alert"
    >
      <span class="customer-table-empty-icon">
        <i class="bi bi-exclamation-circle"></i>
      </span>

      <div>
        <strong>
          Unable to load customers
        </strong>

        <p>
          {{ errorMessage }}
        </p>
      </div>

      <button
        type="button"
        class="customer-empty-action"
        @click="emit('retry')"
      >
        <i class="bi bi-arrow-clockwise"></i>

        <span>
          Retry
        </span>
      </button>
    </div>

    <!-- ==================================================
         Empty State
    =================================================== -->

    <div
      v-else-if="customers.length === 0"
      class="customer-table-empty"
      role="status"
      aria-live="polite"
    >
      <span class="customer-table-empty-icon">
        <i class="bi bi-people"></i>
      </span>

      <div>
        <strong>
          No customers found
        </strong>

        <p>
          No customer matches the selected search and filters.
        </p>
      </div>
    </div>

    <!-- ==================================================
         Customer Table
    =================================================== -->

    <template v-else>
      <div class="customer-table-wrapper">
        <table class="customer-table">
          <thead>
            <tr>
              <th class="customer-serial-column">
                SL
              </th>

              <th>
                Customer
              </th>

              <th>
                Phone
              </th>

              <th>
                Email
              </th>

              <th class="text-center">
                Visit Count
              </th>

              <th class="text-right">
                Total Spend
              </th>

              <th>
                Last Visit
              </th>

              <th class="text-center">
                Status
              </th>

              <th class="text-right">
                Actions
              </th>
            </tr>
          </thead>

          <tbody>
            <tr
              v-for="(customer, index) in customers"
              :key="customer.id"
            >
              <!-- Serial -->

              <td class="customer-serial-column">
                {{
                  calculateSerial(
                    index
                  )
                }}
              </td>

              <!-- Customer -->

              <td>
                <div class="customer-profile-cell">
                  <div class="customer-profile-details">
                    <strong>
                      {{
                        customer.name ||
                        "Unnamed Customer"
                      }}
                    </strong>

                    <small>
                      {{
                        customer.customer_code ||
                        `CUST-${String(
                          customer.id
                        ).padStart(
                          4,
                          "0"
                        )}`
                      }}
                    </small>
                  </div>
                </div>
              </td>

              <!-- Phone -->

              <td>
                <a
                  v-if="customer.phone"
                  class="customer-contact-link"
                  :href="`tel:${customer.phone}`"
                >
                  <i class="bi bi-telephone"></i>

                  <span>
                    {{ customer.phone }}
                  </span>
                </a>

                <span
                  v-else
                  class="customer-muted-text"
                >
                  Not provided
                </span>
              </td>

              <!-- Email -->

              <td>
                <a
                  v-if="customer.email"
                  class="customer-contact-link"
                  :href="`mailto:${customer.email}`"
                >
                  <i class="bi bi-envelope"></i>

                  <span>
                    {{ customer.email }}
                  </span>
                </a>

                <span
                  v-else
                  class="customer-muted-text"
                >
                  Not provided
                </span>
              </td>

              <!-- Visit Count -->

              <td class="text-center">
                <span class="customer-visit-badge">
                  {{
                    formatInteger(
                      customer.visit_count
                    )
                  }}
                </span>
              </td>

              <!-- Total Spend -->

              <td class="text-right">
                <strong class="customer-spend-amount">
                  {{
                    customer.total_spent_formatted ||
                    formatCurrency(
                      customer.total_spent
                    )
                  }}
                </strong>
              </td>

              <!-- Last Visit -->

              <td>
                <strong class="customer-last-visit">
                  {{
                    customer.last_visit_label ||
                    "Never"
                  }}
                </strong>
              </td>

              <!-- Status -->

              <td class="text-center">
                <button
                  type="button"
                  class="customer-status-button"
                  :class="{
                    'customer-status-active':
                      customer.is_active,

                    'customer-status-inactive':
                      !customer.is_active,
                  }"
                  :disabled="
                    statusLoadingId ===
                    customer.id
                  "
                  :aria-label="
                    customer.is_active
                      ? `Deactivate ${customer.name}`
                      : `Activate ${customer.name}`
                  "
                  @click="
                    emit(
                      'toggle-status',
                      customer
                    )
                  "
                >
                  <span
                    v-if="
                      statusLoadingId ===
                      customer.id
                    "
                    class="spinner-border spinner-border-sm"
                    aria-hidden="true"
                  ></span>

                  <span v-else>
                    {{
                      customer.status_label ||
                      (
                        customer.is_active
                          ? "Active"
                          : "Inactive"
                      )
                    }}
                  </span>
                </button>
              </td>

              <!-- Actions -->

              <td class="text-right">
                <div class="customer-action-group">
                  <button
                    type="button"
                    class="customer-action-button customer-view-action"
                    title="View customer"
                    aria-label="View customer"
                    @click="
                      emit(
                        'view',
                        customer
                      )
                    "
                  >
                    <i class="bi bi-eye"></i>
                  </button>

                  <button
                    type="button"
                    class="customer-action-button customer-edit-action"
                    title="Edit customer"
                    aria-label="Edit customer"
                    @click="
                      emit(
                        'edit',
                        customer
                      )
                    "
                  >
                    <i class="bi bi-pencil-square"></i>
                  </button>

                  <button
                    type="button"
                    class="customer-action-button customer-delete-action"
                    title="Delete customer"
                    aria-label="Delete customer"
                    :disabled="
                      deleteLoadingId ===
                      customer.id
                    "
                    @click="
                      emit(
                        'delete',
                        customer
                      )
                    "
                  >
                    <span
                      v-if="
                        deleteLoadingId ===
                        customer.id
                      "
                      class="spinner-border spinner-border-sm"
                      aria-hidden="true"
                    ></span>

                    <i
                      v-else
                      class="bi bi-trash"
                    ></i>
                  </button>
                </div>
              </td>
            </tr>
          </tbody>
        </table>
      </div>

      <!-- ==================================================
           Pagination
      =================================================== -->

      <div
        v-if="meta.last_page > 1"
        class="customer-pagination"
      >
        <div class="customer-pagination-info">
          Showing
          <strong>
            {{ meta.from || 0 }}
          </strong>
          to
          <strong>
            {{ meta.to || 0 }}
          </strong>
          of
          <strong>
            {{ meta.total || 0 }}
          </strong>
          customers
        </div>

        <div class="customer-pagination-actions">
          <button
            type="button"
            class="customer-pagination-button"
            :disabled="
              meta.current_page <= 1
            "
            aria-label="Previous page"
            @click="
              emit(
                'page-change',
                meta.current_page - 1
              )
            "
          >
            <i class="bi bi-chevron-left"></i>
          </button>

          <button
            v-for="page in visiblePages"
            :key="page"
            type="button"
            class="customer-pagination-button"
            :class="{
              active:
                page ===
                meta.current_page,
            }"
            :disabled="
              page ===
              meta.current_page
            "
            @click="
              emit(
                'page-change',
                page
              )
            "
          >
            {{ page }}
          </button>

          <button
            type="button"
            class="customer-pagination-button"
            :disabled="
              meta.current_page >=
              meta.last_page
            "
            aria-label="Next page"
            @click="
              emit(
                'page-change',
                meta.current_page + 1
              )
            "
          >
            <i class="bi bi-chevron-right"></i>
          </button>
        </div>
      </div>
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
  customers: {
    type: Array,
    default: () => [],
  },

  meta: {
    type: Object,

    default: () => ({
      current_page: 1,
      last_page: 1,
      per_page: 10,
      total: 0,
      from: null,
      to: null,
    }),
  },

  loading: {
    type: Boolean,
    default: false,
  },

  errorMessage: {
    type: String,
    default: '',
  },

  statusLoadingId: {
    type: [
      Number,
      String,
    ],

    default: null,
  },

  deleteLoadingId: {
    type: [
      Number,
      String,
    ],

    default: null,
  },

  maxVisiblePages: {
    type: Number,
    default: 5,
  },
})

/*
|--------------------------------------------------------------------------
| Component Events
|--------------------------------------------------------------------------
*/

const emit = defineEmits([
  'view',
  'edit',
  'delete',
  'toggle-status',
  'page-change',
  'retry',
])

/*
|--------------------------------------------------------------------------
| Visible Page Numbers
|--------------------------------------------------------------------------
*/

const visiblePages = computed(() => {
  const currentPage =
    Number(
      props.meta?.current_page
    ) || 1

  const lastPage =
    Number(
      props.meta?.last_page
    ) || 1

  const maxVisible =
    Math.max(
      1,
      Number(
        props.maxVisiblePages
      ) || 5
    )

  if (
    lastPage <=
    maxVisible
  ) {
    return createPageRange(
      1,
      lastPage
    )
  }

  const half =
    Math.floor(
      maxVisible / 2
    )

  let startPage =
    currentPage - half

  let endPage =
    startPage +
    maxVisible -
    1

  if (startPage < 1) {
    startPage = 1
    endPage = maxVisible
  }

  if (endPage > lastPage) {
    endPage = lastPage

    startPage =
      Math.max(
        1,
        lastPage -
          maxVisible +
          1
      )
  }

  return createPageRange(
    startPage,
    endPage
  )
})

/*
|--------------------------------------------------------------------------
| Calculate Serial Number
|--------------------------------------------------------------------------
*/

function calculateSerial(
  index
) {
  const currentPage =
    Number(
      props.meta?.current_page
    ) || 1

  const perPage =
    Number(
      props.meta?.per_page
    ) || 10

  return (
    (
      currentPage - 1
    ) * perPage
  ) + index + 1
}

/*
|--------------------------------------------------------------------------
| Create Page Range
|--------------------------------------------------------------------------
*/

function createPageRange(
  startPage,
  endPage
) {
  return Array.from(
    {
      length:
        Math.max(
          0,
          endPage -
            startPage +
            1
        ),
    },

    (
      _,
      index
    ) =>
      startPage +
      index
  )
}

/*
|--------------------------------------------------------------------------
| Customer Initial
|--------------------------------------------------------------------------
*/

function getInitial(
  value
) {
  const name =
    String(
      value || ''
    ).trim()

  return name
    ? name
        .charAt(0)
        .toUpperCase()
    : 'C'
}

/*
|--------------------------------------------------------------------------
| Format Integer
|--------------------------------------------------------------------------
*/

function formatInteger(
  value
) {
  const numberValue =
    Number(value)

  return (
    Number.isInteger(
      numberValue
    ) &&
    numberValue >= 0
      ? numberValue
      : 0
  ).toLocaleString(
    'en-GB'
  )
}

/*
|--------------------------------------------------------------------------
| Format Currency
|--------------------------------------------------------------------------
*/

function formatCurrency(
  value
) {
  const numberValue =
    Number(value)

  const amount =
    Number.isFinite(
      numberValue
    )
      ? numberValue
      : 0

  return `৳ ${amount.toLocaleString(
    'en-GB',
    {
      minimumFractionDigits: 2,
      maximumFractionDigits: 2,
    }
  )}`
}
</script>