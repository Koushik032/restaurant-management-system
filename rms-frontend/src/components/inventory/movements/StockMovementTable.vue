<template>
  <section
    class="movement-table-card"
    :aria-busy="loading"
  >
    <!-- Loading -->

    <div
      v-if="loading"
      class="movement-table-state"
      role="status"
      aria-live="polite"
    >
      <span
        class="spinner-border"
        aria-hidden="true"
      ></span>

      <strong>
        Loading stock movements...
      </strong>

      <small>
        Retrieving inventory audit records.
      </small>
    </div>


    <!-- Error -->

    <div
      v-else-if="errorMessage"
      class="movement-table-state movement-table-error"
      role="alert"
    >
      <i
        class="bi bi-exclamation-triangle"
        aria-hidden="true"
      ></i>

      <strong>
        Unable to load stock movements
      </strong>

      <p>
        {{ errorMessage }}
      </p>

      <button
        type="button"
        @click="retryLoad"
      >
        <i
          class="bi bi-arrow-clockwise"
          aria-hidden="true"
        ></i>

        Try Again
      </button>
    </div>


    <!-- Empty -->

    <div
      v-else-if="safeMovements.length === 0"
      class="movement-table-state movement-table-empty"
      role="status"
    >
      <div>
        <i
          class="bi bi-clock-history"
          aria-hidden="true"
        ></i>
      </div>

      <h3>
        No stock movement found
      </h3>

      <p>
        No movement record matches the
        selected filters.
      </p>
    </div>


    <!-- Table -->

    <template v-else>
      <div class="movement-table-scroll">
        <table class="stock-movement-table">
          <caption class="visually-hidden">
            Warehouse stock movement audit records
          </caption>

          <thead>
            <tr>
              <th scope="col">
                SL
              </th>

              <th scope="col">
                Date &amp; Time
              </th>

              <th scope="col">
                Raw Material
              </th>

              <th scope="col">
                Movement
              </th>

              <th scope="col">
                Quantity
              </th>

              <th scope="col">
                Before
              </th>

              <th scope="col">
                After
              </th>

              <th scope="col">
                Unit Cost
              </th>

              <th scope="col">
                Created By
              </th>

              <th scope="col">
                Notes
              </th>

              <th scope="col">
                Details
              </th>
            </tr>
          </thead>


          <tbody>
            <tr
              v-for="(
                movement,
                index
              ) in safeMovements"
              :key="movementKey(
                movement,
                index,
              )"
              :class="
                movementRowClass(
                  movement,
                )
              "
            >
              <!-- Serial -->

              <td>
                <span class="movement-serial">
                  {{ serialNumber(index) }}
                </span>
              </td>


              <!-- Date -->

              <td>
                <div class="movement-date-cell">
                  <strong>
                    {{
                      movementDateDisplay(
                        movement,
                      )
                    }}
                  </strong>

                  <small>
                    {{
                      movementIdLabel(
                        movement,
                      )
                    }}
                  </small>
                </div>
              </td>


              <!-- Raw Material -->

              <td>
                <div class="movement-material-cell">
                  <div class="movement-material-icon">
                    <i
                      class="bi bi-box"
                      aria-hidden="true"
                    ></i>
                  </div>

                  <div>
                    <strong>
                      {{
                        materialName(
                          movement,
                        )
                      }}
                    </strong>

                    <small>
                      {{
                        materialCategory(
                          movement,
                        )
                      }}
                    </small>
                  </div>
                </div>
              </td>


              <!-- Movement Type -->

              <td>
                <span
                  class="movement-type-badge"
                  :class="
                    movementTypeClass(
                      movement,
                    )
                  "
                >
                  <i
                    class="bi"
                    :class="
                      movementIcon(
                        movement,
                      )
                    "
                    aria-hidden="true"
                  ></i>

                  {{
                    movementTypeLabel(
                      movement,
                    )
                  }}
                </span>
              </td>


              <!-- Movement Quantity -->

              <td>
                <span
                  class="movement-quantity"
                  :class="
                    movementQuantityClass(
                      movement,
                    )
                  "
                >
                  <template
                    v-if="
                      movementSign(
                        movement,
                      )
                    "
                  >
                    {{
                      movementSign(
                        movement,
                      )
                    }}
                  </template>

                  {{
                    movementQuantityDisplay(
                      movement,
                    )
                  }}
                </span>
              </td>


              <!-- Quantity Before -->

              <td>
                <span class="movement-before-value">
                  {{
                    quantityBeforeDisplay(
                      movement,
                    )
                  }}
                </span>
              </td>


              <!-- Quantity After -->

              <td>
                <span class="movement-after-value">
                  {{
                    quantityAfterDisplay(
                      movement,
                    )
                  }}
                </span>
              </td>


              <!-- Unit Cost -->

              <td>
                <span
                  v-if="
                    hasUnitCost(
                      movement,
                    )
                  "
                  class="movement-cost-value"
                >
                  {{
                    unitCostDisplay(
                      movement,
                    )
                  }}
                </span>

                <span
                  v-else
                  class="movement-muted-value"
                >
                  Not provided
                </span>
              </td>


              <!-- Created By -->

              <td>
                <div class="movement-user-cell">
                  <i
                    class="bi bi-person-circle"
                    aria-hidden="true"
                  ></i>

                  <div>
                    <strong>
                      {{
                        createdByName(
                          movement,
                        )
                      }}
                    </strong>

                    <small>
                      {{
                        createdByEmail(
                          movement,
                        )
                      }}
                    </small>
                  </div>
                </div>
              </td>


              <!-- Notes -->

              <td>
                <p class="movement-notes-preview">
                  {{
                    truncateText(
                      movementNotes(
                        movement,
                      ),
                    )
                  }}
                </p>
              </td>


              <!-- Details -->

              <td>
                <button
                  type="button"
                  class="movement-details-button"
                  :aria-label="
                    detailsAriaLabel(
                      movement,
                    )
                  "
                  @click="
                    viewDetails(
                      movement,
                    )
                  "
                >
                  <i
                    class="bi bi-eye"
                    aria-hidden="true"
                  ></i>

                  View
                </button>
              </td>
            </tr>
          </tbody>
        </table>
      </div>


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
import {
  computed,
} from 'vue'


import InventoryPagination
  from '@/components/inventory/shared/InventoryPagination.vue'


/*
|--------------------------------------------------------------------------
| Props / Emits
|--------------------------------------------------------------------------
*/


const props = defineProps({
  movements: {
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
})


const emit = defineEmits([
  'retry',
  'page-change',
  'view-details',
])


/*
|--------------------------------------------------------------------------
| Normalized Rows
|--------------------------------------------------------------------------
*/


const safeMovements = computed(() => {
  return Array.isArray(
    props.movements,
  )
    ? props.movements.filter(
        (movement) =>
          movement
          &&
          typeof movement ===
            'object',
      )
    : []
})


/*
|--------------------------------------------------------------------------
| Pagination
|--------------------------------------------------------------------------
*/


function serialNumber(
  index,
) {
  const currentPage =
    normalizePositiveInteger(
      props.meta?.current_page,
      1,
    )


  const perPage =
    normalizePositiveInteger(
      props.meta?.per_page,
      10,
    )


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
| Direction
|--------------------------------------------------------------------------
*/


function movementDirection(
  movement,
) {
  const direction =
    movement?.direction


  return [
    'in',
    'out',
  ].includes(
    direction,
  )
    ? direction
    : null
}


function movementSign(
  movement,
) {
  const direction =
    movementDirection(
      movement,
    )


  if (direction === 'out') {
    return '−'
  }


  if (direction === 'in') {
    return '+'
  }


  return ''
}


function movementQuantityClass(
  movement,
) {
  const direction =
    movementDirection(
      movement,
    )


  return {
    'movement-quantity-in':
      direction === 'in',

    'movement-quantity-out':
      direction === 'out',
  }
}


function movementTypeClass(
  movement,
) {
  const direction =
    movementDirection(
      movement,
    )


  return {
    'movement-type-in':
      direction === 'in',

    'movement-type-out':
      direction === 'out',
  }
}


function movementRowClass(
  movement,
) {
  const direction =
    movementDirection(
      movement,
    )


  return {
    'movement-row-in':
      direction === 'in',

    'movement-row-out':
      direction === 'out',
  }
}


/*
|--------------------------------------------------------------------------
| Movement Presentation
|--------------------------------------------------------------------------
*/


function movementIcon(
  movement,
) {
  const type =
    movement?.movement_type


  if (type === 'opening_stock') {
    return 'bi-box-arrow-in-down'
  }


  if (type === 'purchase_receive') {
    return 'bi-truck'
  }


  if (type === 'transfer_out') {
    return 'bi-arrow-right-circle'
  }


  if (
    type ===
    'warehouse_adjustment_in'
  ) {
    return 'bi-plus-circle'
  }


  if (
    type ===
    'warehouse_adjustment_out'
  ) {
    return 'bi-dash-circle'
  }


  const direction =
    movementDirection(
      movement,
    )


  if (direction === 'out') {
    return 'bi-dash-circle'
  }


  if (direction === 'in') {
    return 'bi-plus-circle'
  }


  return 'bi-question-circle'
}


function movementTypeLabel(
  movement,
) {
  const label =
    movement
      ?.movement_type_label


  if (
    typeof label ===
      'string'
    &&
    label.trim() !== ''
  ) {
    return label
  }


  return formatLabel(
    movement?.movement_type,
  )
}


/*
|--------------------------------------------------------------------------
| Material
|--------------------------------------------------------------------------
*/


function materialName(
  movement,
) {
  return (
    movement?.material_name
    ||
    movement?.raw_material
      ?.material_name
    ||
    'Unknown Material'
  )
}


function materialCategory(
  movement,
) {
  return (
    movement?.raw_material
      ?.category
    ||
    movement?.category
    ||
    'No category'
  )
}


function movementUnit(
  movement,
) {
  return (
    movement?.unit
    ||
    movement?.raw_material
      ?.base_unit
    ||
    ''
  )
}


/*
|--------------------------------------------------------------------------
| Quantity Displays
|--------------------------------------------------------------------------
*/


function movementQuantityDisplay(
  movement,
) {
  return formattedOrQuantity(
    movement?.quantity_formatted,
    movement?.quantity,
    movementUnit(
      movement,
    ),
  )
}


function quantityBeforeDisplay(
  movement,
) {
  return formattedOrQuantity(
    movement
      ?.quantity_before_formatted,
    movement?.quantity_before,
    movementUnit(
      movement,
    ),
  )
}


function quantityAfterDisplay(
  movement,
) {
  return formattedOrQuantity(
    movement
      ?.quantity_after_formatted,
    movement?.quantity_after,
    movementUnit(
      movement,
    ),
  )
}


function formattedOrQuantity(
  formattedValue,
  numericValue,
  unit,
) {
  if (
    typeof formattedValue ===
      'string'
    &&
    formattedValue.trim() !== ''
  ) {
    return formattedValue
  }


  return formatQuantity(
    numericValue,
    unit,
  )
}


/*
|--------------------------------------------------------------------------
| Unit Cost
|--------------------------------------------------------------------------
*/


function hasUnitCost(
  movement,
) {
  const value =
    movement?.unit_cost


  return !(
    value === null
    ||
    value === undefined
    ||
    value === ''
  )
}


function unitCostDisplay(
  movement,
) {
  const formatted =
    movement
      ?.unit_cost_formatted


  if (
    typeof formatted ===
      'string'
    &&
    formatted.trim() !== ''
  ) {
    return formatted
  }


  return formatMoney(
    movement?.unit_cost,
  )
}


/*
|--------------------------------------------------------------------------
| User / Notes / Date
|--------------------------------------------------------------------------
*/


function createdByName(
  movement,
) {
  return (
    movement?.created_by
      ?.name
    ||
    movement?.creator
      ?.name
    ||
    movement?.created_by_name
    ||
    'System'
  )
}


function createdByEmail(
  movement,
) {
  return (
    movement?.created_by
      ?.email
    ||
    movement?.creator
      ?.email
    ||
    movement?.created_by_email
    ||
    'Automated record'
  )
}


function movementNotes(
  movement,
) {
  const notes =
    movement?.notes


  if (
    notes === null
    ||
    notes === undefined
  ) {
    return 'No notes provided'
  }


  const value =
    String(
      notes,
    ).trim()


  return (
    value
    ||
    'No notes provided'
  )
}


function movementDateDisplay(
  movement,
) {
  const label =
    movement?.created_at_label


  if (
    typeof label ===
      'string'
    &&
    label.trim() !== ''
  ) {
    return label
  }


  return formatDate(
    movement?.created_at,
  )
}


/*
|--------------------------------------------------------------------------
| IDs / Keys / Actions
|--------------------------------------------------------------------------
*/


function movementKey(
  movement,
  index,
) {
  const id =
    movement?.id


  if (
    id !== null
    &&
    id !== undefined
    &&
    id !== ''
  ) {
    return `movement-${id}`
  }


  return `movement-row-${serialNumber(
    index,
  )}`
}


function movementIdLabel(
  movement,
) {
  const id =
    movement?.id


  if (
    id === null
    ||
    id === undefined
    ||
    id === ''
  ) {
    return 'Movement ID unavailable'
  }


  return `Movement #${id}`
}


function detailsAriaLabel(
  movement,
) {
  const id =
    movement?.id


  if (
    id !== null
    &&
    id !== undefined
    &&
    id !== ''
  ) {
    return `View stock movement ${id} details`
  }


  return `View ${materialName(
    movement,
  )} stock movement details`
}


function retryLoad() {
  if (props.loading) {
    return
  }


  emit(
    'retry',
  )
}


function viewDetails(
  movement,
) {
  if (
    !movement
    ||
    typeof movement !==
      'object'
  ) {
    return
  }


  emit(
    'view-details',
    movement,
  )
}


/*
|--------------------------------------------------------------------------
| Formatters
|--------------------------------------------------------------------------
*/


function truncateText(
  value,
) {
  const text =
    String(
      value ?? '',
    ).trim()


  return text.length > 55
    ? `${text.slice(
        0,
        55,
      )}…`
    : text
}


function formatQuantity(
  quantity,
  unit,
) {
  const numericValue =
    Number(
      quantity,
    )


  if (
    !Number.isFinite(
      numericValue,
    )
  ) {
    return unit
      ? `Not available ${unit}`
      : 'Not available'
  }


  return `${numericValue.toLocaleString(
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


  if (
    !Number.isFinite(
      numericValue,
    )
  ) {
    return 'Not available'
  }


  return `৳ ${numericValue.toLocaleString(
    'en-BD',
    {
      minimumFractionDigits: 2,
      maximumFractionDigits: 4,
    },
  )}`
}


function formatLabel(
  value,
) {
  if (
    value === null
    ||
    value === undefined
    ||
    value === ''
  ) {
    return 'Not available'
  }


  return String(
    value,
  )
    .replaceAll(
      '_',
      ' ',
    )
    .replace(
      /\b\w/g,
      (letter) =>
        letter.toUpperCase(),
    )
}


function formatDate(
  value,
) {
  if (!value) {
    return 'Date unavailable'
  }


  const date =
    new Date(
      value,
    )


  if (
    Number.isNaN(
      date.getTime(),
    )
  ) {
    return 'Date unavailable'
  }


  return date.toLocaleString(
    'en-BD',
    {
      dateStyle:
        'medium',

      timeStyle:
        'short',
    },
  )
}


function normalizePositiveInteger(
  value,
  fallback,
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
    numericValue < 1
  ) {
    return fallback
  }


  return Math.floor(
    numericValue,
  )
}
</script>
