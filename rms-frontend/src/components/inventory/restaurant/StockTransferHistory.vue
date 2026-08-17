<template>
  <section
    class="stock-transfer-history"
    :aria-busy="loading"
  >
    <!-- Header -->

    <div class="stock-transfer-history-header">
      <div>
        <h3>
          Transfer History
        </h3>

        <p>
          Immutable warehouse-to-restaurant stock transfer records.
        </p>
      </div>

      <button
        type="button"
        class="stock-transfer-history-refresh"
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
      class="stock-transfer-history-error"
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
        @click="loadTransfers"
      >
        Try Again
      </button>
    </div>


    <!-- Filters -->

    <div class="stock-transfer-history-filters">
      <div class="stock-transfer-history-filter-grid">
        <!-- Search -->

        <div class="stock-transfer-history-filter-group">
          <label for="stock-transfer-search">
            Search
          </label>

          <input
            id="stock-transfer-search"
            v-model.trim="filters.search"
            type="text"
            maxlength="180"
            autocomplete="off"
            placeholder="Transfer no, material or notes"
            :disabled="loading"
            @keyup.enter="applyFilters"
          />
        </div>


        <!-- Date From -->

        <div class="stock-transfer-history-filter-group">
          <label for="stock-transfer-date-from">
            From Date
          </label>

          <input
            id="stock-transfer-date-from"
            v-model="filters.date_from"
            type="date"
            :max="
              filters.date_to
              ||
              undefined
            "
            :disabled="loading"
            @change="clearValidationError"
          />
        </div>


        <!-- Date To -->

        <div class="stock-transfer-history-filter-group">
          <label for="stock-transfer-date-to">
            To Date
          </label>

          <input
            id="stock-transfer-date-to"
            v-model="filters.date_to"
            type="date"
            :min="
              filters.date_from
              ||
              undefined
            "
            :disabled="loading"
            @change="clearValidationError"
          />
        </div>


        <!-- Rows -->

        <div class="stock-transfer-history-filter-group">
          <label for="stock-transfer-per-page">
            Rows Per Page
          </label>

          <select
            id="stock-transfer-per-page"
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


      <div
        v-if="validationError"
        class="stock-transfer-history-validation"
        role="alert"
      >
        {{ validationError }}
      </div>


      <div class="stock-transfer-history-filter-actions">
        <button
          type="button"
          class="stock-transfer-history-clear"
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
          class="stock-transfer-history-apply"
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


    <!-- Result Count -->

    <div class="stock-transfer-history-summary">
      <span>
        Total Transfers
      </span>

      <strong>
        {{ meta.total }}
      </strong>
    </div>


    <!-- Loading -->

    <div
      v-if="
        loading
        &&
        transfers.length === 0
      "
      class="stock-transfer-history-state"
      role="status"
      aria-live="polite"
    >
      <span
        class="spinner-border"
        aria-hidden="true"
      ></span>

      <strong>
        Loading transfer history...
      </strong>
    </div>


    <!-- Empty -->

    <div
      v-else-if="
        !loading
        &&
        transfers.length === 0
      "
      class="stock-transfer-history-state"
      role="status"
    >
      <i
        class="bi bi-clock-history"
        aria-hidden="true"
      ></i>

      <strong>
        No stock transfer history found
      </strong>

      <p>
        Completed warehouse-to-restaurant transfers will appear here.
      </p>
    </div>


    <!-- History -->

    <template v-else>
      <div class="stock-transfer-history-list">
        <article
          v-for="(
            transfer,
            index
          ) in transfers"
          :key="
            transferRowKey(
              transfer,
              index
            )
          "
          class="stock-transfer-history-card"
        >
          <!-- Card Header -->

          <div class="stock-transfer-history-card-header">
            <div class="stock-transfer-history-number">
              <span>
                Transfer
              </span>

              <strong>
                {{ transferNumber(transfer) }}
              </strong>
            </div>

            <div class="stock-transfer-history-date">
              <i
                class="bi bi-calendar3"
                aria-hidden="true"
              ></i>

              <span>
                {{ transferDate(transfer) }}
              </span>
            </div>
          </div>


          <!-- Main Summary -->

          <div class="stock-transfer-history-card-body">
            <div class="stock-transfer-history-metrics">
              <div>
                <span>
                  Items
                </span>

                <strong>
                  {{ totalItems(transfer) }}
                </strong>
              </div>

              <div>
                <span>
                  Total Quantity
                </span>

                <strong>
                  {{ totalQuantityDisplay(transfer) }}
                </strong>
              </div>

              <div>
                <span>
                  Transfer Value
                </span>

                <strong>
                  {{ totalValueDisplay(transfer) }}
                </strong>
              </div>

              <div>
                <span>
                  Transferred By
                </span>

                <strong>
                  {{ transferredByName(transfer) }}
                </strong>
              </div>
            </div>


            <!-- Item Preview -->

            <div
              v-if="
                transferItems(transfer).length > 0
              "
              class="stock-transfer-history-preview"
            >
              <span
                v-for="(
                  item,
                  itemIndex
                ) in previewItems(transfer)"
                :key="
                  transferItemKey(
                    transfer,
                    item,
                    itemIndex
                  )
                "
                class="stock-transfer-history-preview-item"
              >
                {{ itemName(item) }}

                <small>
                  {{ itemQuantityDisplay(item) }}
                </small>
              </span>

              <span
                v-if="
                  hiddenItemCount(transfer) > 0
                "
                class="stock-transfer-history-preview-more"
              >
                +{{ hiddenItemCount(transfer) }} more
              </span>
            </div>


            <!-- Notes -->

            <div
              v-if="transferNotes(transfer)"
              class="stock-transfer-history-notes"
            >
              <i
                class="bi bi-chat-left-text"
                aria-hidden="true"
              ></i>

              <span>
                {{ transferNotes(transfer) }}
              </span>
            </div>


            <!-- Toggle -->

            <button
              type="button"
              class="stock-transfer-history-details-toggle"
              :aria-expanded="
                isExpanded(transfer)
              "
              :aria-controls="
                detailsId(transfer)
              "
              @click="
                toggleDetails(transfer)
              "
            >
              <i
                class="bi"
                :class="
                  isExpanded(transfer)
                    ? 'bi-chevron-up'
                    : 'bi-chevron-down'
                "
                aria-hidden="true"
              ></i>

              {{
                isExpanded(transfer)
                  ? 'Hide Details'
                  : 'View Details'
              }}
            </button>


            <!-- Immutable Detail -->

            <div
              v-if="isExpanded(transfer)"
              :id="detailsId(transfer)"
              class="stock-transfer-history-details"
            >
              <div class="stock-transfer-history-details-heading">
                <div>
                  <h4>
                    Transfer Items
                  </h4>

                  <p>
                    Historical snapshot. This record cannot be edited or deleted.
                  </p>
                </div>

                <span class="stock-transfer-history-immutable-badge">
                  <i
                    class="bi bi-lock"
                    aria-hidden="true"
                  ></i>

                  Immutable
                </span>
              </div>


              <div class="stock-transfer-history-table-scroll">
                <table class="stock-transfer-history-table">
                  <caption class="visually-hidden">
                    Items in stock transfer
                    {{ transferNumber(transfer) }}
                  </caption>

                  <thead>
                    <tr>
                      <th scope="col">
                        Item
                      </th>

                      <th scope="col">
                        Unit
                      </th>

                      <th scope="col">
                        Quantity
                      </th>

                      <th scope="col">
                        Unit Cost
                      </th>

                      <th scope="col">
                        Warehouse Before
                      </th>

                      <th scope="col">
                        Warehouse After
                      </th>

                      <th scope="col">
                        Restaurant Before
                      </th>

                      <th scope="col">
                        Restaurant After
                      </th>

                      <th scope="col">
                        Notes
                      </th>
                    </tr>
                  </thead>

                  <tbody>
                    <tr
                      v-for="(
                        item,
                        itemIndex
                      ) in transferItems(transfer)"
                      :key="
                        transferItemKey(
                          transfer,
                          item,
                          itemIndex
                        )
                      "
                    >
                      <td>
                        <strong>
                          {{ itemName(item) }}
                        </strong>
                      </td>

                      <td>
                        {{ itemUnit(item) }}
                      </td>

                      <td>
                        {{ quantityValue(item?.quantity) }}
                      </td>

                      <td>
                        {{ unitCostDisplay(item) }}
                      </td>

                      <td>
                        {{
                          quantityValue(
                            item?.warehouse_quantity_before
                          )
                        }}
                      </td>

                      <td>
                        {{
                          quantityValue(
                            item?.warehouse_quantity_after
                          )
                        }}
                      </td>

                      <td>
                        {{
                          quantityValue(
                            item?.restaurant_quantity_before
                          )
                        }}
                      </td>

                      <td>
                        {{
                          quantityValue(
                            item?.restaurant_quantity_after
                          )
                        }}
                      </td>

                      <td>
                        {{ itemNotes(item) }}
                      </td>
                    </tr>

                    <tr
                      v-if="
                        transferItems(transfer).length === 0
                      "
                    >
                      <td
                        colspan="9"
                        class="stock-transfer-history-no-items"
                      >
                        Item details are not available.
                      </td>
                    </tr>
                  </tbody>
                </table>
              </div>
            </div>
          </div>
        </article>
      </div>


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


const transfers =
  ref([])

const loading =
  ref(false)

const errorMessage =
  ref('')

const validationError =
  ref('')

const expandedTransferIds =
  ref(
    new Set()
  )

let reloadRequestedWhileLoading =
  false


const filters = reactive({
  search: '',
  date_from: '',
  date_to: '',
  page: 1,
  per_page: 10,
})


const meta = ref(
  createDefaultMeta()
)


/*
|--------------------------------------------------------------------------
| API
|--------------------------------------------------------------------------
*/


async function loadTransfers()
{
  if (loading.value) {

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
        .getStockTransfers({
          search:
            filters.search
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

    transfers.value =
      Array.isArray(
        response?.data
      )
        ? response.data.filter(
            (transfer) =>
              transfer
              &&
              typeof transfer ===
                'object'
          )
        : []

    meta.value =
      normalizeMeta(
        response?.meta
      )

    filters.page =
      meta.value.current_page

    pruneExpandedTransfers()

    succeeded =
      true

  }

  catch (error) {

    errorMessage.value =
      inventoryService
        .getInventoryErrorMessage(
          error,
          'Unable to load stock transfer history.'
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

      void loadTransfers()

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
  if (loading.value) {
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

  await loadTransfers()
}


async function clearFilters()
{
  if (loading.value) {
    return
  }

  filters.search =
    ''

  filters.date_from =
    ''

  filters.date_to =
    ''

  filters.page =
    1

  validationError.value =
    ''

  expandedTransferIds.value =
    new Set()

  await loadTransfers()
}


function clearValidationError()
{
  validationError.value =
    ''
}


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
| Pagination / Refresh
|--------------------------------------------------------------------------
*/


async function changePage(page)
{
  if (loading.value) {
    return
  }

  const target =
    Number(page)

  if (
    !Number.isFinite(target)
    ||
    !Number.isInteger(target)
    ||
    target < 1
    ||
    target >
      meta.value.last_page
    ||
    target ===
      meta.value.current_page
  ) {
    return
  }

  filters.page =
    target

  expandedTransferIds.value =
    new Set()

  await loadTransfers()
}


async function refresh()
{
  await loadTransfers()
}


/*
|--------------------------------------------------------------------------
| Expand Details
|--------------------------------------------------------------------------
*/


function transferIdentity(transfer)
{
  const id =
    transfer?.id

  if (
    id !== null
    &&
    id !== undefined
    &&
    id !== ''
  ) {
    return String(id)
  }

  const transferNo =
    String(
      transfer?.transfer_no
      ||
      ''
    ).trim()

  return transferNo
    ||
    ''
}


function isExpanded(transfer)
{
  const id =
    transferIdentity(
      transfer
    )

  return Boolean(
    id
    &&
    expandedTransferIds.value
      .has(id)
  )
}


function toggleDetails(transfer)
{
  const id =
    transferIdentity(
      transfer
    )

  if (!id) {
    return
  }

  const next =
    new Set(
      expandedTransferIds.value
    )

  if (next.has(id)) {
    next.delete(id)
  }
  else {
    next.add(id)
  }

  expandedTransferIds.value =
    next
}


function pruneExpandedTransfers()
{
  const visibleIds =
    new Set(
      transfers.value
        .map(
          (transfer) =>
            transferIdentity(
              transfer
            )
        )
        .filter(Boolean)
    )

  expandedTransferIds.value =
    new Set(
      Array.from(
        expandedTransferIds.value
      ).filter(
        (id) =>
          visibleIds.has(id)
      )
    )
}


/*
|--------------------------------------------------------------------------
| Transfer Helpers
|--------------------------------------------------------------------------
*/


function transferRowKey(
  transfer,
  index
)
{
  const identity =
    transferIdentity(
      transfer
    )

  return identity
    ? `stock-transfer-${identity}`
    : `stock-transfer-row-${serialNumber(index)}`
}


function transferNumber(transfer)
{
  const value =
    String(
      transfer?.transfer_no
      ||
      ''
    ).trim()

  if (value) {
    return value
  }

  return transfer?.id
    ? `Transfer #${transfer.id}`
    : 'Transfer unavailable'
}


function transferDate(transfer)
{
  const label =
    String(
      transfer?.transferred_at_label
      ||
      ''
    ).trim()

  if (label) {
    return label
  }

  return formatDateTime(
    transfer?.transferred_at
  )
}


function totalItems(transfer)
{
  const value =
    Number(
      transfer?.total_items
    )

  if (
    Number.isFinite(value)
    &&
    value >= 0
  ) {
    return Math.floor(value)
  }

  return transferItems(
    transfer
  ).length
}


function totalQuantityDisplay(transfer)
{
  return quantityValue(
    transfer?.total_quantity
  )
}


function totalValueDisplay(transfer)
{
  const formatted =
    String(
      transfer?.total_value_formatted
      ||
      ''
    ).trim()

  if (formatted) {
    return formatted
  }

  return moneyValue(
    transfer?.total_value,
    2
  )
}


function transferredByName(transfer)
{
  return (
    String(
      transfer?.transferred_by
        ?.name
      ||
      ''
    ).trim()
    ||
    'Unknown User'
  )
}


function transferNotes(transfer)
{
  const value =
    String(
      transfer?.notes
      ??
      ''
    ).trim()

  return value
}


function detailsId(transfer)
{
  const identity =
    transferIdentity(
      transfer
    )
      .replace(
        /[^A-Za-z0-9_-]/g,
        '-'
      )

  return `stock-transfer-details-${identity || 'unknown'}`
}


/*
|--------------------------------------------------------------------------
| Item Helpers
|--------------------------------------------------------------------------
*/


function transferItems(transfer)
{
  const items =
    transfer?.items?.data
    ??
    transfer?.items
    ??
    []

  return Array.isArray(items)
    ? items.filter(
        (item) =>
          item
          &&
          typeof item ===
            'object'
      )
    : []
}


function previewItems(transfer)
{
  return transferItems(
    transfer
  ).slice(
    0,
    3
  )
}


function hiddenItemCount(transfer)
{
  return Math.max(
    0,
    transferItems(
      transfer
    ).length - 3
  )
}


function transferItemKey(
  transfer,
  item,
  index
)
{
  const itemId =
    item?.id

  if (
    itemId !== null
    &&
    itemId !== undefined
    &&
    itemId !== ''
  ) {
    return `stock-transfer-item-${itemId}`
  }

  return [
    'stock-transfer-item',
    transferIdentity(transfer)
      ||
      'unknown-transfer',
    item?.raw_material_id
      ??
      'unknown-material',
    index,
  ].join('-')
}


function itemName(item)
{
  return (
    String(
      item?.item_name
      ??
      item?.raw_material
        ?.material_name
      ??
      ''
    ).trim()
    ||
    'Unknown Material'
  )
}


function itemUnit(item)
{
  return (
    String(
      item?.unit
      ??
      item?.raw_material
        ?.base_unit
      ??
      ''
    ).trim()
    ||
    '—'
  )
}


function itemQuantityDisplay(item)
{
  return `${quantityValue(
    item?.quantity
  )} ${itemUnit(item)}`.trim()
}


function unitCostDisplay(item)
{
  const formatted =
    String(
      item?.unit_cost_formatted
      ||
      ''
    ).trim()

  if (formatted) {
    return formatted
  }

  return moneyValue(
    item?.unit_cost,
    4
  )
}


function itemNotes(item)
{
  return (
    String(
      item?.notes
      ??
      ''
    ).trim()
    ||
    '—'
  )
}


/*
|--------------------------------------------------------------------------
| Formatting
|--------------------------------------------------------------------------
*/


function quantityValue(value)
{
  const number =
    Number(value)

  if (
    !Number.isFinite(number)
  ) {
    return 'Not available'
  }

  return number.toLocaleString(
    'en-BD',
    {
      maximumFractionDigits: 4,
    }
  )
}


function moneyValue(
  value,
  maximumFractionDigits = 2
)
{
  const number =
    Number(value)

  if (
    !Number.isFinite(number)
  ) {
    return 'Not available'
  }

  return `৳ ${number.toLocaleString(
    'en-BD',
    {
      minimumFractionDigits: 2,
      maximumFractionDigits,
    }
  )}`
}


function formatDateTime(value)
{
  if (!value) {
    return 'Date unavailable'
  }

  const date =
    new Date(value)

  if (
    Number.isNaN(
      date.getTime()
    )
  ) {
    return 'Date unavailable'
  }

  return date.toLocaleString(
    'en-BD',
    {
      year: 'numeric',
      month: 'short',
      day: '2-digit',
      hour: '2-digit',
      minute: '2-digit',
    }
  )
}


function isValidOptionalDate(value)
{
  if (!value) {
    return true
  }

  const normalized =
    String(value)

  if (
    !/^\d{4}-\d{2}-\d{2}$/.test(
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
      .split('-')
      .map(Number)

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


function normalizeMeta(value)
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
        source.current_page,
        1
      ),

    last_page:
      positiveInteger(
        source.last_page,
        1
      ),

    per_page:
      positiveInteger(
        source.per_page,
        filters.per_page
      ),

    total:
      nonNegativeInteger(
        source.total,
        0
      ),

    from:
      source.from
      ??
      null,

    to:
      source.to
      ??
      null,
  }
}


function createDefaultMeta()
{
  return {
    current_page: 1,
    last_page: 1,
    per_page: 10,
    total: 0,
    from: null,
    to: null,
  }
}


function positiveInteger(
  value,
  fallback
)
{
  const number =
    Number(value)

  if (
    !Number.isFinite(number)
    ||
    number < 1
  ) {
    return fallback
  }

  return Math.floor(number)
}


function nonNegativeInteger(
  value,
  fallback
)
{
  const number =
    Number(value)

  if (
    !Number.isFinite(number)
    ||
    number < 0
  ) {
    return fallback
  }

  return Math.floor(number)
}


function serialNumber(index)
{
  return (
    (
      meta.value.current_page - 1
    )
    *
    meta.value.per_page
  )
  +
  index
  +
  1
}


/*
|--------------------------------------------------------------------------
| Refresh Key
|--------------------------------------------------------------------------
*/


watch(
  () => props.refreshKey,

  () => {
    void loadTransfers()
  }
)


/*
|--------------------------------------------------------------------------
| Lifecycle
|--------------------------------------------------------------------------
*/


onMounted(() => {
  void loadTransfers()
})
</script>
