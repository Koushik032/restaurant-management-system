<template>
  <section
    class="restaurant-movement-history"
    :aria-busy="loading"
  >
    <!-- Header -->
    <div class="restaurant-movement-history-header">
      <div>
        <h3>
          Restaurant Stock Movement History
        </h3>

        <p>
          Immutable restaurant-side stock movements including
          transfers and restaurant adjustments.
        </p>
      </div>

      <button
        type="button"
        class="restaurant-movement-history-refresh"
        :disabled="loading"
        :aria-busy="loading"
        @click="refresh"
      >
        <i
          class="bi bi-arrow-clockwise"
          :class="{
            'inventory-refresh-spin': loading,
          }"
          aria-hidden="true"
        ></i>

        {{
          loading
            ? 'Refreshing...'
            : 'Refresh'
        }}
      </button>
    </div>


    <!-- Error -->
    <div
      v-if="errorMessage"
      class="restaurant-movement-history-error"
      role="alert"
    >
      <i
        class="bi bi-exclamation-triangle"
        aria-hidden="true"
      ></i>

      <span>
        {{ errorMessage }}
      </span>

      <button
        type="button"
        :disabled="loading"
        @click="loadMovements"
      >
        Try Again
      </button>
    </div>


    <!-- Filters -->
    <div class="restaurant-movement-history-filters">
      <div class="restaurant-movement-history-filter-grid">
        <!-- Search -->
        <div class="restaurant-movement-history-filter-group">
          <label for="restaurant-movement-search">
            Search
          </label>

          <input
            id="restaurant-movement-search"
            v-model.trim="filters.search"
            type="text"
            maxlength="180"
            autocomplete="off"
            placeholder="Material or notes"
            :disabled="loading"
            @keyup.enter="applyFilters"
          />
        </div>


        <!-- Movement Type -->
        <div class="restaurant-movement-history-filter-group">
          <label for="restaurant-movement-type">
            Movement Type
          </label>

          <select
            id="restaurant-movement-type"
            v-model="filters.movement_type"
            :disabled="loading"
          >
            <option value="">
              All Restaurant Movements
            </option>

            <option value="transfer_in">
              Transfer Received
            </option>

            <option value="restaurant_adjustment_in">
              Restaurant Adjustment In
            </option>

            <option value="restaurant_adjustment_out">
              Restaurant Adjustment Out
            </option>
          </select>
        </div>


        <!-- From -->
        <div class="restaurant-movement-history-filter-group">
          <label for="restaurant-movement-date-from">
            From Date
          </label>

          <input
            id="restaurant-movement-date-from"
            v-model="filters.date_from"
            type="date"
            :max="
              filters.date_to
              ||
              undefined
            "
            :disabled="loading"
            @change="
              validationError = ''
            "
          />
        </div>


        <!-- To -->
        <div class="restaurant-movement-history-filter-group">
          <label for="restaurant-movement-date-to">
            To Date
          </label>

          <input
            id="restaurant-movement-date-to"
            v-model="filters.date_to"
            type="date"
            :min="
              filters.date_from
              ||
              undefined
            "
            :disabled="loading"
            @change="
              validationError = ''
            "
          />
        </div>


        <!-- Rows -->
        <div class="restaurant-movement-history-filter-group">
          <label for="restaurant-movement-per-page">
            Rows Per Page
          </label>

          <select
            id="restaurant-movement-per-page"
            v-model.number="filters.per_page"
            :disabled="loading"
            @change="applyFilters"
          >
            <option :value="10">
              10 rows
            </option>

            <option :value="20">
              20 rows
            </option>

            <option :value="50">
              50 rows
            </option>

            <option :value="100">
              100 rows
            </option>
          </select>
        </div>
      </div>


      <!-- Validation -->
      <div
        v-if="validationError"
        class="restaurant-movement-history-validation"
        role="alert"
      >
        {{ validationError }}
      </div>


      <!-- Actions -->
      <div class="restaurant-movement-history-filter-actions">
        <button
          type="button"
          class="restaurant-movement-history-clear"
          :disabled="loading"
          @click="clearFilters"
        >
          <i
            class="bi bi-arrow-counterclockwise"
            aria-hidden="true"
          ></i>

          Clear
        </button>

        <button
          type="button"
          class="restaurant-movement-history-apply"
          :disabled="loading"
          @click="applyFilters"
        >
          <i
            class="bi bi-funnel"
            aria-hidden="true"
          ></i>

          Apply Filters
        </button>
      </div>
    </div>


    <!-- Summary -->
    <div class="restaurant-movement-history-summary">
      <div>
        <span>
          Total Movements
        </span>

        <strong>
          {{ meta.total }}
        </strong>
      </div>

      <div>
        <span>
          Stock In on Page
        </span>

        <strong>
          {{ stockInOnPage }}
        </strong>
      </div>

      <div>
        <span>
          Stock Out on Page
        </span>

        <strong>
          {{ stockOutOnPage }}
        </strong>
      </div>
    </div>


    <!-- Loading -->
    <div
      v-if="
        loading
        &&
        movements.length === 0
      "
      class="restaurant-movement-history-state"
      role="status"
      aria-live="polite"
    >
      <span
        class="spinner-border"
        aria-hidden="true"
      ></span>

      <strong>
        Loading restaurant stock movements...
      </strong>
    </div>


    <!-- Empty -->
    <div
      v-else-if="
        !loading
        &&
        movements.length === 0
      "
      class="restaurant-movement-history-state"
      role="status"
    >
      <i
        class="bi bi-arrow-left-right"
        aria-hidden="true"
      ></i>

      <strong>
        No restaurant stock movements found
      </strong>

      <p>
        Restaurant transfers and adjustments will appear here.
      </p>
    </div>


    <!-- Table -->
    <template v-else>
      <div class="restaurant-movement-history-table-scroll">
        <table class="restaurant-movement-history-table">
          <caption class="visually-hidden">
            Immutable restaurant stock movement history
          </caption>

          <thead>
            <tr>
              <th scope="col">
                SL
              </th>

              <th scope="col">
                Date
              </th>

              <th scope="col">
                Raw Material
              </th>

              <th scope="col">
                Movement
              </th>

              <th scope="col">
                Direction
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
                Reference
              </th>

              <th scope="col">
                Created By
              </th>

              <th scope="col">
                Details
              </th>
            </tr>
          </thead>

          <tbody>
            <template
              v-for="
                (
                  movement,
                  index
                )
                in movements
              "
              :key="
                movementRowKey(
                  movement,
                  index
                )
              "
            >
              <tr>
                <td>
                  {{
                    serialNumber(
                      index
                    )
                  }}
                </td>

                <td>
                  {{
                    movementDate(
                      movement
                    )
                  }}
                </td>

                <td>
                  <div class="restaurant-movement-material">
                    <strong>
                      {{
                        materialName(
                          movement
                        )
                      }}
                    </strong>

                    <small>
                      {{
                        materialCategory(
                          movement
                        )
                      }}
                    </small>
                  </div>
                </td>

                <td>
                  <span class="restaurant-movement-type-badge">
                    {{
                      movementTypeLabel(
                        movement
                      )
                    }}
                  </span>
                </td>

                <td>
                  <span
                    class="restaurant-movement-direction"
                    :class="
                      `restaurant-movement-direction-${movementDirection(movement)}`
                    "
                  >
                    <i
                      class="bi"
                      :class="
                        movementDirectionIcon(
                          movement
                        )
                      "
                      aria-hidden="true"
                    ></i>

                    {{
                      movementDirectionLabel(
                        movement
                      )
                    }}
                  </span>
                </td>

                <td>
                  <strong>
                    {{
                      movementQuantityDisplay(
                        movement
                      )
                    }}
                  </strong>
                </td>

                <td>
                  {{
                    quantityBeforeDisplay(
                      movement
                    )
                  }}
                </td>

                <td>
                  {{
                    quantityAfterDisplay(
                      movement
                    )
                  }}
                </td>

                <td>
                  {{
                    unitCostDisplay(
                      movement
                    )
                  }}
                </td>

                <td>
                  {{
                    referenceDisplay(
                      movement
                    )
                  }}
                </td>

                <td>
                  {{
                    createdByName(
                      movement
                    )
                  }}
                </td>

                <td>
                  <button
                    type="button"
                    class="restaurant-movement-details-button"
                    :aria-expanded="
                      isExpanded(
                        movement
                      )
                    "
                    :aria-controls="
                      detailsId(
                        movement
                      )
                    "
                    @click="
                      toggleDetails(
                        movement
                      )
                    "
                  >
                    <i
                      class="bi"
                      :class="
                        isExpanded(
                          movement
                        )
                          ? 'bi-chevron-up'
                          : 'bi-chevron-down'
                      "
                      aria-hidden="true"
                    ></i>

                    {{
                      isExpanded(
                        movement
                      )
                        ? 'Hide'
                        : 'View'
                    }}
                  </button>
                </td>
              </tr>


              <!-- Expanded Detail -->
              <tr
                v-if="
                  isExpanded(
                    movement
                  )
                "
                :id="
                  detailsId(
                    movement
                  )
                "
                class="restaurant-movement-detail-row"
              >
                <td colspan="12">
                  <div class="restaurant-movement-detail-card">
                    <div class="restaurant-movement-detail-heading">
                      <div>
                        <h4>
                          Movement Details
                        </h4>

                        <p>
                          Immutable stock ledger entry. This record
                          cannot be edited or deleted.
                        </p>
                      </div>

                      <span class="restaurant-movement-immutable-badge">
                        <i
                          class="bi bi-lock"
                          aria-hidden="true"
                        ></i>

                        Immutable
                      </span>
                    </div>


                    <div class="restaurant-movement-detail-grid">
                      <div>
                        <span>
                          Movement ID
                        </span>

                        <strong>
                          {{
                            movementId(
                              movement
                            )
                          }}
                        </strong>
                      </div>

                      <div>
                        <span>
                          Location
                        </span>

                        <strong>
                          {{
                            movementLocation(
                              movement
                            )
                          }}
                        </strong>
                      </div>

                      <div>
                        <span>
                          Unit
                        </span>

                        <strong>
                          {{
                            movementUnit(
                              movement
                            )
                            ||
                            '—'
                          }}
                        </strong>
                      </div>

                      <div>
                        <span>
                          Reference Type
                        </span>

                        <strong>
                          {{
                            referenceTypeDisplay(
                              movement
                            )
                          }}
                        </strong>
                      </div>

                      <div>
                        <span>
                          Reference ID
                        </span>

                        <strong>
                          {{
                            referenceIdDisplay(
                              movement
                            )
                          }}
                        </strong>
                      </div>

                      <div>
                        <span>
                          Created At
                        </span>

                        <strong>
                          {{
                            movementDate(
                              movement
                            )
                          }}
                        </strong>
                      </div>
                    </div>


                    <div class="restaurant-movement-notes">
                      <span>
                        Notes
                      </span>

                      <p>
                        {{
                          movementNotes(
                            movement
                          )
                        }}
                      </p>
                    </div>
                  </div>
                </td>
              </tr>
            </template>
          </tbody>
        </table>
      </div>


      <!-- Pagination -->
      <InventoryPagination
        :meta="meta"
        :loading="loading"
        @page-change="changePage"
      />
    </template>
  </section>
</template>


<script setup>
import {
  computed,
  onMounted,
  reactive,
  ref,
  watch,
} from 'vue'


import InventoryPagination
  from '@/components/inventory/shared/InventoryPagination.vue'


import inventoryService
  from '@/services/inventoryService'


/*
|--------------------------------------------------------------------------
| Props
|--------------------------------------------------------------------------
*/


const props = defineProps({
  refreshKey: {
    type: Number,
    default: 0,
  },
})


/*
|--------------------------------------------------------------------------
| State
|--------------------------------------------------------------------------
*/


const movements =
  ref([])


const loading =
  ref(false)


const errorMessage =
  ref('')


const validationError =
  ref('')


const expandedMovementIds =
  ref(
    new Set()
  )


let reloadRequestedWhileLoading =
  false


const filters =
  reactive({
    search:
      '',

    movement_type:
      '',

    date_from:
      '',

    date_to:
      '',

    page:
      1,

    per_page:
      10,
  })


const meta =
  ref(
    createDefaultMeta()
  )


/*
|--------------------------------------------------------------------------
| Summary
|--------------------------------------------------------------------------
*/


const stockInOnPage =
  computed(() => {

    return movements.value
      .filter(
        (movement) =>
          movementDirection(
            movement
          )
          ===
          'in'
      )
      .length

  })


const stockOutOnPage =
  computed(() => {

    return movements.value
      .filter(
        (movement) =>
          movementDirection(
            movement
          )
          ===
          'out'
      )
      .length

  })


/*
|--------------------------------------------------------------------------
| Load Movements
|--------------------------------------------------------------------------
*/


async function loadMovements()
{
  if (
    loading.value
  ) {

    reloadRequestedWhileLoading =
      true

    return false
  }


  loading.value =
    true


  errorMessage.value =
    ''


  let succeeded =
    false


  try {

    const response =
      await inventoryService
        .getStockMovements({
          search:
            filters.search
            ||
            undefined,

          /*
          |--------------------------------------------------------------------------
          | Important
          |--------------------------------------------------------------------------
          |
          | This component is restaurant-side history only.
          |
          */

          location:
            'restaurant',

          movement_type:
            filters.movement_type
            ||
            undefined,

          date_from:
            filters.date_from
            ||
            undefined,

          date_to:
            filters.date_to
            ||
            undefined,

          page:
            filters.page,

          per_page:
            filters.per_page,
        })


    movements.value =
      Array.isArray(
        response?.data
      )
        ? response.data
            .filter(
              (movement) =>
                movement
                &&
                typeof movement ===
                  'object'
            )
        : []


    meta.value =
      normalizeMeta(
        response?.meta
      )


    filters.page =
      meta.value
        .current_page


    pruneExpandedMovements()


    succeeded =
      true

  }

  catch (error) {

    errorMessage.value =
      inventoryErrorMessage(
        error,
        'Unable to load restaurant stock movement history.'
      )

  }

  finally {

    loading.value =
      false


    if (
      reloadRequestedWhileLoading
    ) {

      reloadRequestedWhileLoading =
        false


      void loadMovements()

    }

  }


  return succeeded
}


/*
|--------------------------------------------------------------------------
| Filters
|--------------------------------------------------------------------------
*/


async function applyFilters()
{
  if (
    loading.value
  ) {
    return
  }


  validationError.value =
    ''


  if (
    !validateDateRange()
  ) {
    return
  }


  filters.page =
    1


  expandedMovementIds.value =
    new Set()


  await loadMovements()
}


async function clearFilters()
{
  if (
    loading.value
  ) {
    return
  }


  filters.search =
    ''


  filters.movement_type =
    ''


  filters.date_from =
    ''


  filters.date_to =
    ''


  filters.page =
    1


  validationError.value =
    ''


  expandedMovementIds.value =
    new Set()


  await loadMovements()
}


/*
|--------------------------------------------------------------------------
| Date Validation
|--------------------------------------------------------------------------
*/


function validateDateRange()
{
  if (
    !isValidOptionalDate(
      filters.date_from
    )
  ) {

    validationError.value =
      'Please select a valid From Date.'


    return false
  }


  if (
    !isValidOptionalDate(
      filters.date_to
    )
  ) {

    validationError.value =
      'Please select a valid To Date.'


    return false
  }


  if (
    filters.date_from
    &&
    filters.date_to
    &&
    filters.date_to <
      filters.date_from
  ) {

    validationError.value =
      'To Date cannot be earlier than From Date.'


    return false
  }


  return true
}


/*
|--------------------------------------------------------------------------
| Pagination
|--------------------------------------------------------------------------
*/


async function changePage(
  page
)
{
  if (
    loading.value
  ) {
    return
  }


  const target =
    Number(
      page
    )


  if (
    !Number.isFinite(
      target
    )
    ||
    !Number.isInteger(
      target
    )
    ||
    target < 1
    ||
    target >
      meta.value
        .last_page
    ||
    target ===
      meta.value
        .current_page
  ) {
    return
  }


  filters.page =
    target


  expandedMovementIds.value =
    new Set()


  await loadMovements()
}


/*
|--------------------------------------------------------------------------
| Refresh
|--------------------------------------------------------------------------
*/


async function refresh()
{
  await loadMovements()
}


/*
|--------------------------------------------------------------------------
| Expand Details
|--------------------------------------------------------------------------
*/


function movementIdentity(
  movement
)
{
  const id =
    movement?.id


  if (
    id === null
    ||
    id === undefined
    ||
    id === ''
  ) {
    return ''
  }


  return String(
    id
  )
}


function isExpanded(
  movement
)
{
  const id =
    movementIdentity(
      movement
    )


  return Boolean(
    id
    &&
    expandedMovementIds.value
      .has(
        id
      )
  )
}


function toggleDetails(
  movement
)
{
  const id =
    movementIdentity(
      movement
    )


  if (
    !id
  ) {
    return
  }


  const next =
    new Set(
      expandedMovementIds.value
    )


  if (
    next.has(
      id
    )
  ) {

    next.delete(
      id
    )

  }

  else {

    next.add(
      id
    )

  }


  expandedMovementIds.value =
    next
}


function pruneExpandedMovements()
{
  const visible =
    new Set(
      movements.value
        .map(
          movementIdentity
        )
        .filter(
          Boolean
        )
    )


  expandedMovementIds.value =
    new Set(
      Array.from(
        expandedMovementIds.value
      )
        .filter(
          (id) =>
            visible.has(
              id
            )
        )
    )
}


/*
|--------------------------------------------------------------------------
| Keys / IDs
|--------------------------------------------------------------------------
*/


function movementRowKey(
  movement,
  index
)
{
  const id =
    movementIdentity(
      movement
    )


  return id
    ? `restaurant-movement-${id}`
    : `restaurant-movement-row-${serialNumber(index)}`
}


function detailsId(
  movement
)
{
  const id =
    movementIdentity(
      movement
    )
      .replace(
        /[^A-Za-z0-9_-]/g,
        '-'
      )


  return `restaurant-movement-details-${id || 'unknown'}`
}


/*
|--------------------------------------------------------------------------
| Movement Display
|--------------------------------------------------------------------------
*/


function movementId(
  movement
)
{
  const id =
    movement?.id


  return (
    id !== null
    &&
    id !== undefined
    &&
    id !== ''
  )
    ? `#${id}`
    : '—'
}


function materialName(
  movement
)
{
  return (
    movement?.material_name
    ||
    movement
      ?.raw_material
      ?.material_name
    ||
    'Unknown Material'
  )
}


function materialCategory(
  movement
)
{
  return (
    movement
      ?.raw_material
      ?.category
    ||
    movement?.category
    ||
    'No category'
  )
}


function movementUnit(
  movement
)
{
  return (
    movement?.unit
    ||
    movement
      ?.raw_material
      ?.base_unit
    ||
    ''
  )
}


function movementTypeLabel(
  movement
)
{
  const label =
    movement
      ?.movement_type_label


  if (
    typeof label ===
      'string'
    &&
    label
      .trim() !==
      ''
  ) {
    return label
  }


  return formatLabel(
    movement
      ?.movement_type
  )
}


/*
|--------------------------------------------------------------------------
| Direction
|--------------------------------------------------------------------------
*/


function movementDirection(
  movement
)
{
  const value =
    String(
      movement?.direction
      ??
      ''
    )
      .trim()
      .toLowerCase()


  if (
    value ===
      'in'
    ||
    value ===
      'out'
  ) {
    return value
  }


  /*
  |--------------------------------------------------------------------------
  | Safe Fallback
  |--------------------------------------------------------------------------
  */


  const type =
    String(
      movement
        ?.movement_type
      ??
      ''
    )
      .trim()
      .toLowerCase()


  if (
    type ===
    'restaurant_adjustment_out'
  ) {
    return 'out'
  }


  if (
    type ===
      'transfer_in'
    ||
    type ===
      'restaurant_adjustment_in'
  ) {
    return 'in'
  }


  return 'unknown'
}


function movementDirectionLabel(
  movement
)
{
  const direction =
    movementDirection(
      movement
    )


  if (
    direction ===
    'in'
  ) {
    return 'Stock In'
  }


  if (
    direction ===
    'out'
  ) {
    return 'Stock Out'
  }


  return 'Unknown'
}


function movementDirectionIcon(
  movement
)
{
  const direction =
    movementDirection(
      movement
    )


  if (
    direction ===
    'in'
  ) {
    return 'bi-plus-circle'
  }


  if (
    direction ===
    'out'
  ) {
    return 'bi-dash-circle'
  }


  return 'bi-question-circle'
}


/*
|--------------------------------------------------------------------------
| Quantity Display
|--------------------------------------------------------------------------
*/


function movementQuantityDisplay(
  movement
)
{
  return formattedOrQuantity(
    movement
      ?.quantity_formatted,

    movement
      ?.quantity,

    movementUnit(
      movement
    )
  )
}


function quantityBeforeDisplay(
  movement
)
{
  return formattedOrQuantity(
    movement
      ?.quantity_before_formatted,

    movement
      ?.quantity_before,

    movementUnit(
      movement
    )
  )
}


function quantityAfterDisplay(
  movement
)
{
  return formattedOrQuantity(
    movement
      ?.quantity_after_formatted,

    movement
      ?.quantity_after,

    movementUnit(
      movement
    )
  )
}


function formattedOrQuantity(
  formatted,
  value,
  unit
)
{
  if (
    typeof formatted ===
      'string'
    &&
    formatted
      .trim() !==
      ''
  ) {
    return formatted
  }


  return formatQuantity(
    value,
    unit
  )
}


function formatQuantity(
  value,
  unit = ''
)
{
  const number =
    Number(
      value
    )


  if (
    !Number.isFinite(
      number
    )
  ) {
    return 'Not available'
  }


  const formatted =
    number
      .toLocaleString(
        'en-BD',
        {
          maximumFractionDigits:
            4,
        }
      )


  return `${formatted} ${unit}`
    .trim()
}


/*
|--------------------------------------------------------------------------
| Unit Cost
|--------------------------------------------------------------------------
*/


function unitCostDisplay(
  movement
)
{
  const value =
    movement
      ?.unit_cost


  if (
    value === null
    ||
    value === undefined
    ||
    value === ''
  ) {
    return 'Not provided'
  }


  const formatted =
    movement
      ?.unit_cost_formatted


  if (
    typeof formatted ===
      'string'
    &&
    formatted
      .trim() !==
      ''
  ) {
    return formatted
  }


  const number =
    Number(
      value
    )


  if (
    !Number.isFinite(
      number
    )
  ) {
    return 'Not available'
  }


  return `৳ ${number.toLocaleString(
    'en-BD',
    {
      minimumFractionDigits:
        2,

      maximumFractionDigits:
        4,
    }
  )}`
}


/*
|--------------------------------------------------------------------------
| Reference
|--------------------------------------------------------------------------
*/


function referenceDisplay(
  movement
)
{
  const type =
    referenceTypeDisplay(
      movement
    )


  const id =
    referenceIdDisplay(
      movement
    )


  if (
    type ===
      'Not linked'
    &&
    id ===
      'Not linked'
  ) {
    return 'Not linked'
  }


  if (
    id ===
    'Not linked'
  ) {
    return type
  }


  return `${type} #${id}`
}


function referenceTypeDisplay(
  movement
)
{
  const value =
    String(
      movement
        ?.reference_type
      ??
      ''
    )
      .trim()


  if (
    !value
  ) {
    return 'Not linked'
  }


  const parts =
    value.split(
      '\\'
    )


  return (
    parts[
      parts.length - 1
    ]
    ||
    value
  )
}


function referenceIdDisplay(
  movement
)
{
  const value =
    movement
      ?.reference_id


  if (
    value === null
    ||
    value === undefined
    ||
    value === ''
  ) {
    return 'Not linked'
  }


  return String(
    value
  )
}


/*
|--------------------------------------------------------------------------
| Location / User / Notes
|--------------------------------------------------------------------------
*/


function movementLocation(
  movement
)
{
  return (
    movement
      ?.location_label
    ||
    formatLabel(
      movement
        ?.location
    )
    ||
    'Restaurant'
  )
}


function createdByName(
  movement
)
{
  return (
    movement
      ?.created_by
      ?.name
    ||
    movement
      ?.creator
      ?.name
    ||
    movement
      ?.created_by_name
    ||
    'System'
  )
}


function movementNotes(
  movement
)
{
  const value =
    String(
      movement
        ?.notes
      ??
      ''
    )
      .trim()


  return (
    value
    ||
    'No notes provided'
  )
}


/*
|--------------------------------------------------------------------------
| Date
|--------------------------------------------------------------------------
*/


function movementDate(
  movement
)
{
  const label =
    String(
      movement
        ?.created_at_label
      ??
      ''
    )
      .trim()


  if (
    label
  ) {
    return label
  }


  return formatDateTime(
    movement
      ?.created_at
  )
}


function formatDateTime(
  value
)
{
  if (
    !value
  ) {
    return 'Date unavailable'
  }


  const date =
    new Date(
      value
    )


  if (
    Number.isNaN(
      date.getTime()
    )
  ) {
    return 'Date unavailable'
  }


  return date
    .toLocaleString(
      'en-BD',
      {
        year:
          'numeric',

        month:
          'short',

        day:
          '2-digit',

        hour:
          '2-digit',

        minute:
          '2-digit',
      }
    )
}


/*
|--------------------------------------------------------------------------
| Label Helper
|--------------------------------------------------------------------------
*/


function formatLabel(
  value
)
{
  const normalized =
    String(
      value
      ??
      ''
    )
      .trim()
      .replace(
        /_/g,
        ' '
      )


  if (
    !normalized
  ) {
    return 'Unknown'
  }


  return normalized
    .replace(
      /\b\w/g,
      (letter) =>
        letter
          .toUpperCase()
    )
}


/*
|--------------------------------------------------------------------------
| Date Validation Helper
|--------------------------------------------------------------------------
*/


function isValidOptionalDate(
  value
)
{
  if (
    !value
  ) {
    return true
  }


  const normalized =
    String(
      value
    )


  if (
    !/^\d{4}-\d{2}-\d{2}$/
      .test(
        normalized
      )
  ) {
    return false
  }


  const [
    year,
    month,
    day,
  ] =
    normalized
      .split(
        '-'
      )
      .map(
        Number
      )


  const date =
    new Date(
      year,
      month - 1,
      day
    )


  return (
    date.getFullYear() ===
      year
    &&
    date.getMonth() ===
      month - 1
    &&
    date.getDate() ===
      day
  )
}


/*
|--------------------------------------------------------------------------
| Pagination Meta
|--------------------------------------------------------------------------
*/


function normalizeMeta(
  value
)
{
  const source =
    value
    &&
    typeof value ===
      'object'
      ? value
      : {}


  return {
    current_page:
      positiveInteger(
        source
          .current_page,

        1
      ),

    last_page:
      positiveInteger(
        source
          .last_page,

        1
      ),

    per_page:
      positiveInteger(
        source
          .per_page,

        filters
          .per_page
      ),

    total:
      nonNegativeInteger(
        source
          .total,

        0
      ),

    from:
      source
        .from
      ??
      null,

    to:
      source
        .to
      ??
      null,
  }
}


function createDefaultMeta()
{
  return {
    current_page:
      1,

    last_page:
      1,

    per_page:
      10,

    total:
      0,

    from:
      null,

    to:
      null,
  }
}


function positiveInteger(
  value,
  fallback
)
{
  const number =
    Number(
      value
    )


  if (
    !Number.isFinite(
      number
    )
    ||
    number < 1
  ) {
    return fallback
  }


  return Math.floor(
    number
  )
}


function nonNegativeInteger(
  value,
  fallback
)
{
  const number =
    Number(
      value
    )


  if (
    !Number.isFinite(
      number
    )
    ||
    number < 0
  ) {
    return fallback
  }


  return Math.floor(
    number
  )
}


function serialNumber(
  index
)
{
  return (
    (
      meta.value
        .current_page
      -
      1
    )
    *
    meta.value
      .per_page
  )
  +
  index
  +
  1
}


/*
|--------------------------------------------------------------------------
| Error
|--------------------------------------------------------------------------
*/


function inventoryErrorMessage(
  error,
  fallback
)
{
  if (
    typeof inventoryService
      .getInventoryErrorMessage
    ===
    'function'
  ) {

    return inventoryService
      .getInventoryErrorMessage(
        error,
        fallback
      )

  }


  return (
    error
      ?.response
      ?.data
      ?.message
    ||
    error
      ?.message
    ||
    fallback
  )
}


/*
|--------------------------------------------------------------------------
| Refresh Key
|--------------------------------------------------------------------------
*/


watch(
  () =>
    props.refreshKey,

  () => {

    void loadMovements()

  }
)


/*
|--------------------------------------------------------------------------
| Lifecycle
|--------------------------------------------------------------------------
*/


onMounted(
  () => {

    void loadMovements()

  }
)
</script>