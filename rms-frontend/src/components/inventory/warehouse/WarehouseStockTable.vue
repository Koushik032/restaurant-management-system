<template>
  <section class="warehouse-table-card">
    <!-- Loading -->

    <div
      v-if="loading"
      class="warehouse-table-loading"
      role="status"
      aria-live="polite"
    >
      <span
        class="spinner-border"
        aria-hidden="true"
      ></span>

      <strong>
        Loading warehouse stocks...
      </strong>

      <small>
        Please wait while inventory data is loaded.
      </small>
    </div>

    <!-- Error -->

    <div
      v-else-if="errorMessage"
      class="warehouse-table-error"
      role="alert"
    >
      <i class="bi bi-exclamation-triangle" aria-hidden="true"></i>

      <strong>
        Unable to load warehouse stock
      </strong>

      <p>
        {{ errorMessage }}
      </p>

      <button
        type="button"
        @click="emit('retry')"
      >
        <i class="bi bi-arrow-clockwise" aria-hidden="true"></i>

        Try Again
      </button>
    </div>

    <!-- Empty -->

    <div
      v-else-if="stocks.length === 0"
      class="warehouse-table-empty"
    >
      <div>
        <i class="bi bi-box-seam" aria-hidden="true"></i>
      </div>

      <h3>
        No warehouse stock found
      </h3>

      <p>
        Add a raw material or change the
        current filters to see inventory data.
      </p>
    </div>

    <!-- Table -->

    <template v-else>
      <div class="warehouse-table-scroll">
        <table class="warehouse-stock-table">
          <caption class="visually-hidden">
            Warehouse stock inventory records
          </caption>

          <thead>
            <tr>
              <th scope="col">SL</th>

              <th scope="col">
                Raw Material
              </th>

              <th scope="col">
                Category
              </th>

              <th scope="col">
                Unit
              </th>

              <th scope="col">
                Quantity
              </th>

              <th scope="col">
                Minimum
              </th>

              <th scope="col">
                Stock Status
              </th>

              <th scope="col">
                Average Cost
              </th>

              <th scope="col">
                Stock Value
              </th>

              <th scope="col">
                Last Received
              </th>

              <th scope="col">
                Material Status
              </th>

              <th scope="col">
                History
              </th>

              <th v-if="canManage" scope="col">
                Action
              </th>
            </tr>
          </thead>

          <tbody>
            <tr
              v-for="(
                stock,
                index
              ) in stocks"
              :key="
                stock.id
                ||
                stock.raw_material_id
              "
              :class="
                rowStatusClass(
                  stock,
                )
              "
            >
              <!-- Serial -->

              <td>
                <span class="warehouse-serial">
                  {{ serialNumber(index) }}
                </span>
              </td>

              <!-- Raw Material -->

              <td>
                <div class="warehouse-material-cell">
                  <div class="warehouse-material-icon">
                    <i class="bi bi-box" aria-hidden="true"></i>
                  </div>

                  <div>
                    <strong>
                      {{ materialName(stock) }}
                    </strong>

                    <small>
                      Material ID:
                      {{
                        stock.raw_material_id
                        ||
                        stock.raw_material?.id
                        ||
                        '—'
                      }}
                    </small>
                  </div>
                </div>
              </td>

              <!-- Category -->

              <td>
                <span
                  v-if="materialCategory(stock)"
                  class="warehouse-category-badge"
                >
                  {{ materialCategory(stock) }}
                </span>

                <span
                  v-else
                  class="warehouse-muted-value"
                >
                  Not assigned
                </span>
              </td>

              <!-- Unit -->

              <td>
                <span class="warehouse-unit-badge">
                  {{ materialUnit(stock) }}
                </span>
              </td>

              <!-- Current Quantity -->

              <td>
                <div class="warehouse-quantity-cell">
                  <strong>
                    {{
                      stock.quantity_formatted
                      ||
                      formatQuantity(
                        stock.quantity,
                        materialUnit(stock),
                      )
                    }}
                  </strong>

                  <small>
                    Current stock
                  </small>
                </div>
              </td>

              <!-- Minimum Quantity -->

              <td>
                <div class="warehouse-minimum-cell">
                  <strong>
                    {{
                      stock.minimum_quantity_formatted
                      ||
                      formatQuantity(
                        stock.minimum_quantity
                        ??
                        stock.warehouse_minimum_quantity,
                        materialUnit(stock),
                      )
                    }}
                  </strong>

                  <small>
                    Alert level
                  </small>
                </div>
              </td>

              <!-- Automatic Stock Status -->

              <td>
                <span
                  class="warehouse-status-badge"
                  :class="
                    statusBadgeClass(
                      stock.status,
                    )
                  "
                >
                  <i
                    class="bi"
                    :class="
                      statusIcon(
                        stock.status,
                      )
                    "
                    aria-hidden="true"
                  ></i>

                  {{
                    stock.status_label
                    ||
                    formatStatus(
                      stock.status,
                    )
                  }}
                </span>
              </td>

              <!-- Average Unit Cost -->

              <td>
                <span class="warehouse-money-value">
                  {{
                    stock
                      .average_unit_cost_formatted
                    ||
                    formatMoney(
                      stock.average_unit_cost,
                    )
                  }}
                </span>
              </td>

              <!-- Stock Value -->

              <td>
                <span class="warehouse-stock-value">
                  {{
                    stock
                      .stock_value_formatted
                    ||
                    formatMoney(
                      stock.stock_value,
                    )
                  }}
                </span>
              </td>

              <!-- Last Received -->

              <td>
                <div class="warehouse-date-cell">
                  <strong>
                    {{
                      stock.last_received_at_label
                      ||
                      formatDateTime(
                        stock.last_received_at,
                      )
                    }}
                  </strong>

                  <small
                    v-if="stock.last_received_at"
                  >
                    Warehouse receive
                  </small>
                </div>
              </td>

              <!-- Material Active / Inactive Status -->

              <td>
                <span
                  class="warehouse-material-status"
                  :class="{
                    'warehouse-material-status-active':
                      materialIsActive(
                        stock,
                      ),

                    'warehouse-material-status-inactive':
                      !materialIsActive(
                        stock,
                      ),
                  }"
                >
                  <i
                    class="bi"
                    :class="
                      materialIsActive(
                        stock,
                      )
                        ? 'bi-check-circle-fill'
                        : 'bi-pause-circle-fill'
                    "
                    aria-hidden="true"
                  ></i>

                  {{
                    materialIsActive(
                      stock,
                    )
                      ? 'Active'
                      : 'Inactive'
                  }}
                </span>
              </td>

              <!-- Movement History -->

              <td>
                <button
                  type="button"
                  class="warehouse-action-history"
                  title="View stock movement history"
                  :aria-label="
                    `View stock movement history for ${materialName(
                      stock,
                    )}`
                  "
                  @click="
                    emit(
                      'history',
                      stock,
                    )
                  "
                >
                  <i class="bi bi-clock-history" aria-hidden="true"></i>

                  <span>
                    History
                  </span>
                </button>
              </td>

              <!-- Management Actions -->

              <td v-if="canManage">
                <div class="warehouse-row-actions">
                  <!-- Adjust -->

                  <button
                    type="button"
                    class="warehouse-action-adjust"
                    :title="
                      materialIsActive(
                        stock,
                      )
                        ? 'Adjust warehouse stock'
                        : 'Activate material before adjustment'
                    "
                    :disabled="
                      !materialIsActive(
                        stock,
                      )
                      ||
                      rowActionIsLoading(
                        stock,
                      )
                    "
                    :aria-label="
                      `Adjust warehouse stock for ${materialName(
                        stock,
                      )}`
                    "
                    @click="
                      emit(
                        'adjust',
                        stock,
                      )
                    "
                  >
                    <span
                      v-if="
                        isLoadingFor(
                          adjustLoadingId,
                          stock,
                        )
                      "
                      class="spinner-border spinner-border-sm"
                      aria-hidden="true"
                    ></span>

                    <i
                      v-else
                      class="bi bi-arrow-down-up"
                      aria-hidden="true"
                    ></i>

                    <span>
                      Adjust
                    </span>
                  </button>

                  <!-- Edit -->

                  <button
                    type="button"
                    class="warehouse-action-edit"
                    title="Edit raw material"
                    :disabled="
                      rowActionIsLoading(
                        stock,
                      )
                    "
                    :aria-label="
                      `Edit raw material ${materialName(
                        stock,
                      )}`
                    "
                    @click="
                      emit(
                        'edit',
                        stock,
                      )
                    "
                  >
                    <span
                      v-if="
                        isLoadingFor(
                          editLoadingId,
                          stock,
                        )
                      "
                      class="spinner-border spinner-border-sm"
                      aria-hidden="true"
                    ></span>

                    <i
                      v-else
                      class="bi bi-pencil-square"
                      aria-hidden="true"
                    ></i>

                    <span>
                      Edit
                    </span>
                  </button>

                  <!-- Activate / Deactivate -->

                  <button
                    type="button"
                    class="warehouse-action-status"
                    :class="{
                      'warehouse-action-activate':
                        !materialIsActive(
                          stock,
                        ),

                      'warehouse-action-deactivate':
                        materialIsActive(
                          stock,
                        ),
                    }"
                    :title="
                      materialStatusActionTitle(
                        stock,
                      )
                    "
                    :disabled="
                      rowActionIsLoading(
                        stock,
                      )
                      ||
                      materialDeactivationBlocked(
                        stock,
                      )
                    "
                    :aria-label="
                      materialIsActive(
                        stock,
                      )
                        ? `Deactivate raw material ${materialName(
                            stock,
                          )}`
                        : `Activate raw material ${materialName(
                            stock,
                          )}`
                    "
                    @click="
                      emit(
                        'toggle-status',
                        stock,
                      )
                    "
                  >
                    <span
                      v-if="
                        isLoadingFor(
                          statusLoadingId,
                          stock,
                        )
                      "
                      class="spinner-border spinner-border-sm"
                      aria-hidden="true"
                    ></span>

                    <i
                      v-else
                      class="bi"
                      :class="
                        materialIsActive(
                          stock,
                        )
                          ? 'bi-pause-circle'
                          : 'bi-play-circle'
                      "
                      aria-hidden="true"
                    ></i>

                    <span>
                      {{
                        materialIsActive(
                          stock,
                        )
                          ? 'Disable'
                          : 'Activate'
                      }}
                    </span>
                  </button>

                  <!-- Delete -->

                  <button
                    type="button"
                    class="warehouse-action-delete"
                    :title="
                      deleteActionTitle(
                        stock,
                      )
                    "
                    :disabled="
                      rowActionIsLoading(
                        stock,
                      )
                      ||
                      warehouseQuantity(
                        stock,
                      ) > 0
                    "
                    :aria-label="
                      `Archive raw material ${materialName(
                        stock,
                      )}`
                    "
                    @click="
                      emit(
                        'delete',
                        stock,
                      )
                    "
                  >
                    <span
                      v-if="
                        isLoadingFor(
                          deleteLoadingId,
                          stock,
                        )
                      "
                      class="spinner-border spinner-border-sm"
                      aria-hidden="true"
                    ></span>

                    <i
                      v-else
                      class="bi bi-trash3"
                      aria-hidden="true"
                    ></i>

                    <span>
                      Delete
                    </span>
                  </button>
                </div>
              </td>
            </tr>
          </tbody>
        </table>
      </div>

      <!-- Pagination -->

      <InventoryPagination
        :meta="meta"
        :loading="loading"
        @page-change="
          emit(
            'page-change',
            $event,
          )
        "
      />
    </template>
  </section>
</template>

<script setup>
import InventoryPagination
  from '@/components/inventory/shared/InventoryPagination.vue'

const props = defineProps({
  stocks: {
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

  canManage: {
    type: Boolean,
    default: false,
  },

  editLoadingId: {
    type: [
      Number,
      String,
    ],
    default: null,
  },

  adjustLoadingId: {
    type: [
      Number,
      String,
    ],
    default: null,
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
})

const emit = defineEmits([
  'retry',
  'page-change',
  'edit',
  'adjust',
  'toggle-status',
  'delete',
  'history',
])

/*
|--------------------------------------------------------------------------
| Serial Number
|--------------------------------------------------------------------------
*/

function serialNumber(
  index,
) {
  const pageValue =
    Number(
      props.meta?.current_page,
    )

  const perPageValue =
    Number(
      props.meta?.per_page,
    )

  const currentPage =
    Number.isFinite(pageValue)
    &&
    pageValue >= 1
      ? Math.floor(pageValue)
      : 1

  const perPage =
    Number.isFinite(perPageValue)
    &&
    perPageValue >= 1
      ? Math.floor(perPageValue)
      : 10

  return (
    (
      currentPage - 1
    )
    *
    perPage
  ) + index + 1
}

/*
|--------------------------------------------------------------------------
| Raw Material Display Helpers
|--------------------------------------------------------------------------
*/

function materialName(
  stock,
) {
  return (
    stock?.material_name
    ||
    stock?.raw_material
      ?.material_name
    ||
    'Unknown Material'
  )
}

function materialCategory(
  stock,
) {
  return (
    stock?.category
    ||
    stock?.raw_material
      ?.category
    ||
    ''
  )
}

function materialUnit(
  stock,
) {
  return (
    stock?.unit
    ||
    stock?.base_unit
    ||
    stock?.raw_material
      ?.base_unit
    ||
    '—'
  )
}

/*
|--------------------------------------------------------------------------
| Material Status
|--------------------------------------------------------------------------
*/

function materialIsActive(
  stock,
) {
  if (
    typeof stock
      ?.raw_material
      ?.is_active ===
      'boolean'
  ) {
    return stock
      .raw_material
      .is_active
  }

  if (
    typeof stock?.is_active ===
    'boolean'
  ) {
    return stock.is_active
  }

  return false
}

/*
|--------------------------------------------------------------------------
| Known Stock Action Guards
|--------------------------------------------------------------------------
|
| These guards only block conditions the warehouse table can prove locally.
| Restaurant stock and incomplete purchase-order protections remain
| authoritative on the backend.
|
*/

function warehouseQuantity(
  stock,
) {
  const value =
    Number(
      stock?.quantity,
    )


  if (
    !Number.isFinite(value)
    ||
    value <= 0
  ) {
    return 0
  }


  return Math.round(
    (
      value
      +
      Number.EPSILON
    )
    *
    10000,
  ) / 10000
}


function materialDeactivationBlocked(
  stock,
) {
  return (
    materialIsActive(
      stock,
    )
    &&
    warehouseQuantity(
      stock,
    ) > 0
  )
}


function materialStatusActionTitle(
  stock,
) {
  if (
    !materialIsActive(
      stock,
    )
  ) {
    return 'Activate raw material'
  }


  if (
    warehouseQuantity(
      stock,
    ) > 0
  ) {
    return 'Warehouse quantity must be zero before deactivation'
  }


  return 'Deactivate raw material'
}


function deleteActionTitle(
  stock,
) {
  if (
    warehouseQuantity(
      stock,
    ) > 0
  ) {
    return 'Warehouse quantity must be zero before deletion'
  }


  return 'Archive raw material'
}


/*
|--------------------------------------------------------------------------
| Loading State
|--------------------------------------------------------------------------
*/

function isLoadingFor(
  loadingId,
  stock,
) {
  if (
    loadingId === null
    ||
    loadingId === undefined
  ) {
    return false
  }

  const materialId =
    stock?.raw_material_id
    ??
    stock?.raw_material?.id

  return (
    String(loadingId) ===
    String(materialId)
  )
}

function rowActionIsLoading(
  stock,
) {
  return (
    isLoadingFor(
      props.editLoadingId,
      stock,
    )
    ||
    isLoadingFor(
      props.adjustLoadingId,
      stock,
    )
    ||
    isLoadingFor(
      props.statusLoadingId,
      stock,
    )
    ||
    isLoadingFor(
      props.deleteLoadingId,
      stock,
    )
  )
}

/*
|--------------------------------------------------------------------------
| Warehouse Status
|--------------------------------------------------------------------------
*/

function statusBadgeClass(
  status,
) {
  return {
    'warehouse-status-available':
      status === 'available',

    'warehouse-status-limited':
      status === 'limited',

    'warehouse-status-out':
      status ===
      'out_of_stock',
  }
}

function rowStatusClass(
  stock,
) {
  return {
    'warehouse-row-limited':
      stock?.status ===
      'limited',

    'warehouse-row-out':
      stock?.status ===
      'out_of_stock',

    'warehouse-row-inactive':
      !materialIsActive(
        stock,
      ),
  }
}

function statusIcon(
  status,
) {
  if (status === 'limited') {
    return 'bi-exclamation-triangle-fill'
  }

  if (
    status ===
    'out_of_stock'
  ) {
    return 'bi-x-circle-fill'
  }

  return 'bi-check-circle-fill'
}

/*
|--------------------------------------------------------------------------
| Formatters
|--------------------------------------------------------------------------
*/

function formatStatus(
  status,
) {
  if (!status) {
    return 'Unknown'
  }

  return String(status)
    .replaceAll('_', ' ')
    .replace(
      /\b\w/g,
      (letter) =>
        letter.toUpperCase(),
    )
}

function formatQuantity(
  quantity,
  unit,
) {
  const numericValue =
    Number(
      quantity,
    )

  const value =
    Number.isFinite(
      numericValue,
    )
      ? numericValue
      : 0

  return `${value.toLocaleString(
    'en-BD',
    {
      maximumFractionDigits: 4,
    },
  )} ${unit || ''}`.trim()
}

function formatMoney(
  amount,
) {
  const numericValue =
    Number(
      amount,
    )

  const value =
    Number.isFinite(
      numericValue,
    )
      ? numericValue
      : 0

  return `৳ ${value.toLocaleString(
    'en-BD',
    {
      minimumFractionDigits: 2,
      maximumFractionDigits: 2,
    },
  )}`
}

function formatDateTime(
  value,
) {
  if (!value) {
    return 'Not received'
  }

  const date =
    new Date(value)

  if (
    Number.isNaN(
      date.getTime(),
    )
  ) {
    return String(value)
  }

  return date.toLocaleString(
    'en-BD',
    {
      dateStyle: 'medium',
      timeStyle: 'short',
    },
  )
}
</script>