<template>
  <section class="restaurant-stock-section" :aria-busy="loading">
    <!-- Header -->
    <div class="restaurant-stock-header">
      <div>
        <h3>Restaurant Stock</h3>

        <p>
          View current raw-material stock and warehouse-to-restaurant
          transfer history.
        </p>
      </div>

      <div class="restaurant-stock-header-actions">
        <button
          v-if="canManageInventory"
          type="button"
          class="restaurant-stock-transfer-btn"
          :disabled="
            loading
            ||
            transferSubmitting
            ||
            warehouseStocksLoading
          "
          @click="openTransferModal"
        >
          <i
            class="bi bi-arrow-left-right"
            aria-hidden="true"
          ></i>

          New Transfer
        </button>

        <button
          type="button"
          class="restaurant-stock-refresh-btn"
          :disabled="
            loading
            ||
            transferSubmitting
          "
          :aria-busy="loading"
          @click="refresh"
        >
          <i
            class="bi bi-arrow-clockwise"
            :class="{
              'inventory-refresh-spin':
                loading,
            }"
            aria-hidden="true"
          ></i>

          {{
            loading
            &&
            activeSubTab ===
              'current-stock'
              ? 'Refreshing...'
              : 'Refresh'
          }}
        </button>
        
      </div>
    </div>


    <!-- Success Message -->
    <div
      v-if="successMessage"
      class="restaurant-stock-success"
      role="status"
      aria-live="polite"
    >
      <i
        class="bi bi-check-circle"
        aria-hidden="true"
      ></i>

      <span>
        {{ successMessage }}
      </span>
    </div>


    <!-- Sub Tabs -->
    <div
      class="restaurant-stock-subtabs"
      role="tablist"
      aria-label="Restaurant stock views"
    >
      <!-- Current Stock -->

      <button
        id="restaurant-stock-current-tab"
        type="button"
        role="tab"
        class="restaurant-stock-subtab"
        :class="{
          'restaurant-stock-subtab-active':
            activeSubTab ===
            'current-stock',
        }"
        :aria-selected="
          activeSubTab ===
          'current-stock'
        "
        aria-controls="restaurant-stock-current-panel"
        @click="
          activeSubTab =
            'current-stock'
        "
      >
        <i
          class="bi bi-box-seam"
          aria-hidden="true"
        ></i>

        Current Stock
      </button>


      <!-- Transfer History -->

      <button
        id="restaurant-stock-history-tab"
        type="button"
        role="tab"
        class="restaurant-stock-subtab"
        :class="{
          'restaurant-stock-subtab-active':
            activeSubTab ===
            'transfer-history',
        }"
        :aria-selected="
          activeSubTab ===
          'transfer-history'
        "
        aria-controls="restaurant-stock-history-panel"
        @click="
          activeSubTab =
            'transfer-history'
        "
      >
        <i
          class="bi bi-clock-history"
          aria-hidden="true"
        ></i>

        Transfer History
      </button>
      <button
        id="restaurant-stock-movements-tab"
        type="button"
        role="tab"
        class="restaurant-stock-subtab"
        :class="{
          'restaurant-stock-subtab-active':
            activeSubTab === 'stock-movements',
        }"
        :aria-selected="
          activeSubTab === 'stock-movements'
        "
        aria-controls="restaurant-stock-movements-panel"
        @click="
          activeSubTab = 'stock-movements'
        "
      >
        <i
          class="bi bi-arrow-left-right"
          aria-hidden="true"
        ></i>

        Stock Movements
      </button>
    </div>


    <!-- =========================================================
         Current Stock Panel
    ========================================================== -->

    <div
      v-if="
        activeSubTab ===
        'current-stock'
      "
      id="restaurant-stock-current-panel"
      class="restaurant-stock-panel"
      role="tabpanel"
      aria-labelledby="restaurant-stock-current-tab"
    >
      <!-- Error -->

      <div
        v-if="errorMessage"
        class="restaurant-stock-error"
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
          @click="
            loadRestaurantStocks
          "
        >
          Try Again
        </button>
      </div>


      <!-- Filters -->

      <div class="restaurant-stock-filter-card">
        <div class="restaurant-stock-filter-grid">
          <!-- Search -->

          <div class="restaurant-stock-filter-group">
            <label for="restaurant-stock-search">
              Search
            </label>

            <input
              id="restaurant-stock-search"
              v-model.trim="
                filters.search
              "
              type="text"
              maxlength="180"
              autocomplete="off"
              placeholder="Material name"
              :disabled="loading"
              @keyup.enter="
                applyFilters
              "
            />
          </div>


          <!-- Category -->

          <div class="restaurant-stock-filter-group">
            <label for="restaurant-stock-category">
              Category
            </label>

            <select
              id="restaurant-stock-category"
              v-model="
                filters.category
              "
              :disabled="loading"
            >
              <option value="">
                All Categories
              </option>

              <option
                v-for="
                  category
                  in categories
                "
                :key="category"
                :value="category"
              >
                {{ category }}
              </option>
            </select>
          </div>


          <!-- Per Page -->

          <div class="restaurant-stock-filter-group">
            <label for="restaurant-stock-per-page">
              Rows Per Page
            </label>

            <select
              id="restaurant-stock-per-page"
              v-model.number="
                filters.per_page
              "
              :disabled="loading"
              @change="
                applyFilters
              "
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


        <!-- Filter Actions -->

        <div class="restaurant-stock-filter-actions">
          <button
            type="button"
            class="restaurant-stock-clear-btn"
            :disabled="loading"
            @click="
              clearFilters
            "
          >
            <i
              class="bi bi-arrow-counterclockwise"
              aria-hidden="true"
            ></i>

            Clear
          </button>

          <button
            type="button"
            class="restaurant-stock-apply-btn"
            :disabled="loading"
            @click="
              applyFilters
            "
          >
            <i
              class="bi bi-funnel"
              aria-hidden="true"
            ></i>

            Apply Filters
          </button>
        </div>
      </div>


      <!-- Page Summary -->

      <div class="restaurant-stock-page-summary">
        <div>
          <span>
            Records
          </span>

          <strong>
            {{ meta.total }}
          </strong>
        </div>

        <div>
          <span>
            Limited on Page
          </span>

          <strong>
            {{ limitedOnPage }}
          </strong>
        </div>

        <div>
          <span>
            Out of Stock on Page
          </span>

          <strong>
            {{ outOfStockOnPage }}
          </strong>
        </div>
      </div>


      <!-- Loading -->

      <div
        v-if="
          loading
          &&
          stocks.length === 0
        "
        class="restaurant-stock-state"
        role="status"
        aria-live="polite"
      >
        <span
          class="spinner-border"
          aria-hidden="true"
        ></span>

        <strong>
          Loading restaurant stock...
        </strong>
      </div>


      <!-- Empty -->

      <div
        v-else-if="
          !loading
          &&
          stocks.length === 0
        "
        class="restaurant-stock-state"
        role="status"
      >
        <i
          class="bi bi-box-seam"
          aria-hidden="true"
        ></i>

        <strong>
          No restaurant stock found
        </strong>

        <p>
          Restaurant stock will appear
          here after warehouse stock is
          transferred to the restaurant.
        </p>
      </div>


      <!-- Stock Table -->

      <template v-else>
        <div class="restaurant-stock-table-scroll">
          <table class="restaurant-stock-table">
            <caption class="visually-hidden">
              Current restaurant raw-material stock
            </caption>

            <thead>
              <tr>
                <th scope="col">
                  SL
                </th>

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
                  Average Unit Cost
                </th>

                <th scope="col">
                  Stock Value
                </th>

                <th scope="col">
                  Status
                </th>
              </tr>
            </thead>

            <tbody>
              <tr
                v-for="
                  (
                    stock,
                    index
                  )
                  in stocks
                "
                :key="
                  stockRowKey(
                    stock,
                    index
                  )
                "
              >
                <td>
                  {{
                    serialNumber(
                      index
                    )
                  }}
                </td>

                <td>
                  <strong>
                    {{
                      materialName(
                        stock
                      )
                    }}
                  </strong>
                </td>

                <td>
                  {{
                    materialCategory(
                      stock
                    )
                  }}
                </td>

                <td>
                  {{
                    materialUnit(
                      stock
                    )
                  }}
                </td>

                <td>
                  {{
                    quantityDisplay(
                      stock
                    )
                  }}
                </td>

                <td>
                  {{
                    averageCostDisplay(
                      stock
                    )
                  }}
                </td>

                <td>
                  {{
                    stockValueDisplay(
                      stock
                    )
                  }}
                </td>

                <td>
                  <span
                    class="restaurant-stock-status"
                    :class="
                      `restaurant-stock-status-${stockStatus(stock)}`
                    "
                  >
                    {{
                      stockStatusLabel(
                        stock
                      )
                    }}
                  </span>
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
            changePage
          "
        />
      </template>
    </div>


    <!-- Transfer History Panel -->

<div
  v-else-if="
    activeSubTab === 'transfer-history'
  "
  id="restaurant-stock-history-panel"
  class="restaurant-stock-panel"
  role="tabpanel"
  aria-labelledby="restaurant-stock-history-tab"
>
  <StockTransferHistory
    :refresh-key="
      transferHistoryRefreshKey
    "
  />
</div>


<!-- Stock Movement History Panel -->

<div
  v-else
  id="restaurant-stock-movements-panel"
  class="restaurant-stock-panel"
  role="tabpanel"
  aria-labelledby="restaurant-stock-movements-tab"
>
  <RestaurantStockMovementHistory
    :refresh-key="
      movementHistoryRefreshKey
    "
  />
</div>


    <!-- =========================================================
         Warehouse -> Restaurant Transfer Modal
    ========================================================== -->

    <RestaurantStockTransferModal
      :show="showTransferModal"
      :warehouse-stocks="
        warehouseStocks
      "
      :warehouse-stocks-loading="
        warehouseStocksLoading
      "
      :submitting="
        transferSubmitting
      "
      :server-errors="
        transferServerErrors
      "
      :error-message="
        transferErrorMessage
      "
      @close="
        closeTransferModal
      "
      @submit="
        submitTransfer
      "
      @refresh-warehouse="
        loadTransferWarehouseStocks
      "
    />
  </section>
</template>


<script setup>
import {
  computed,
  onBeforeUnmount,
  onMounted,
  reactive,
  ref,
} from 'vue'


import {
  useAuthStore,
} from '@/stores/auth'


import InventoryPagination
  from '@/components/inventory/shared/InventoryPagination.vue'


import RestaurantStockTransferModal
  from '@/components/inventory/restaurant/RestaurantStockTransferModal.vue'


import StockTransferHistory
  from '@/components/inventory/restaurant/StockTransferHistory.vue'

import RestaurantStockMovementHistory
  from '@/components/inventory/restaurant/RestaurantStockMovementHistory.vue'


import inventoryService
  from '@/services/inventoryService'


/*
|--------------------------------------------------------------------------
| Emits
|--------------------------------------------------------------------------
*/


const emit = defineEmits([
  'loaded',
  'inventory-changed',
])


/*
|--------------------------------------------------------------------------
| Permission
|--------------------------------------------------------------------------
*/


const authStore =
  useAuthStore()


const canManageInventory =
  computed(() => {

    if (
      typeof authStore
        .hasPermission
      !==
      'function'
    ) {
      return false
    }

    return Boolean(
      authStore
        .hasPermission(
          'inventory.manage'
        )
    )

  })


/*
|--------------------------------------------------------------------------
| Sub Tabs
|--------------------------------------------------------------------------
*/


const activeSubTab =
  ref(
    'current-stock'
  )


const transferHistoryRefreshKey =
  ref(0)

const movementHistoryRefreshKey =
  ref(0)

/*
|--------------------------------------------------------------------------
| Restaurant Stock State
|--------------------------------------------------------------------------
*/


const stocks =
  ref([])


const loading =
  ref(false)


const errorMessage =
  ref('')


let reloadRequestedWhileLoading =
  false


/*
|--------------------------------------------------------------------------
| Transfer Modal State
|--------------------------------------------------------------------------
*/


const showTransferModal =
  ref(false)


const warehouseStocks =
  ref([])


const warehouseStocksLoading =
  ref(false)


const transferSubmitting =
  ref(false)


const transferErrorMessage =
  ref('')


const transferServerErrors =
  ref({})


let warehouseLoadPromise =
  null


/*
|--------------------------------------------------------------------------
| Success Message
|--------------------------------------------------------------------------
*/


const successMessage =
  ref('')


let successTimer =
  null


/*
|--------------------------------------------------------------------------
| Current Stock Filters
|--------------------------------------------------------------------------
*/


const filters =
  reactive({
    search:
      '',

    category:
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
| Computed
|--------------------------------------------------------------------------
*/


const categories =
  computed(() => {

    const values =
      new Set()

    stocks.value
      .forEach(
        (stock) => {

          const category =
            materialCategory(
              stock
            )

          if (
            category
            &&
            category !==
              'No category'
          ) {
            values.add(
              category
            )
          }

        }
      )

    return Array
      .from(values)
      .sort(
        (
          first,
          second
        ) =>
          first.localeCompare(
            second
          )
      )

  })


const limitedOnPage =
  computed(() => {

    return stocks.value
      .filter(
        (stock) =>
          stockStatus(
            stock
          )
          ===
          'limited'
      )
      .length

  })


const outOfStockOnPage =
  computed(() => {

    return stocks.value
      .filter(
        (stock) =>
          stockStatus(
            stock
          )
          ===
          'out_of_stock'
      )
      .length

  })


/*
|--------------------------------------------------------------------------
| Load Restaurant Stock
|--------------------------------------------------------------------------
*/


async function loadRestaurantStocks()
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
        .getRestaurantStocks({
          search:
            filters.search
            ||
            undefined,

          category:
            filters.category
            ||
            undefined,

          page:
            filters.page,

          per_page:
            filters.per_page,
        })


    stocks.value =
      Array.isArray(
        response?.data
      )
        ? response.data
            .filter(
              (stock) =>
                stock
                &&
                typeof stock ===
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


    succeeded =
      true


    emit(
      'loaded',
      {
        stocks:
          stocks.value,

        meta:
          meta.value,
      }
    )

  }

  catch (error) {

    errorMessage.value =
      inventoryErrorMessage(
        error,
        'Unable to load restaurant stock.'
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

      void loadRestaurantStocks()

    }

  }


  return succeeded
}


/*
|--------------------------------------------------------------------------
| Open Transfer Modal
|--------------------------------------------------------------------------
*/


async function openTransferModal()
{
  if (
    !canManageInventory.value
    ||
    transferSubmitting.value
  ) {
    return
  }


  clearTransferMessages()


  showTransferModal.value =
    true


  await loadTransferWarehouseStocks()
}


/*
|--------------------------------------------------------------------------
| Close Transfer Modal
|--------------------------------------------------------------------------
*/


function closeTransferModal()
{
  if (
    transferSubmitting.value
  ) {
    return
  }


  showTransferModal.value =
    false


  clearTransferMessages()
}


/*
|--------------------------------------------------------------------------
| Load Transferable Warehouse Stock
|--------------------------------------------------------------------------
*/


async function loadTransferWarehouseStocks()
{
  if (
    warehouseLoadPromise
  ) {
    return warehouseLoadPromise
  }


  if (
    !canManageInventory.value
  ) {
    return false
  }


  warehouseLoadPromise =
    (async () => {

      warehouseStocksLoading.value =
        true

      transferErrorMessage.value =
        ''


      try {

        const collected =
          []


        let page =
          1


        let lastPage =
          1


        do {

          const response =
            await inventoryService
              .getWarehouseStocks({
                is_active:
                  1,

                sort_by:
                  'quantity',

                sort_direction:
                  'desc',

                page,

                per_page:
                  100,
              })


          const rows =
            Array.isArray(
              response?.data
            )
              ? response.data
              : []


          collected.push(
            ...rows
          )


          lastPage =
            positiveInteger(
              response?.meta
                ?.last_page,
              1
            )


          page +=
            1

        }
        while (
          page <=
          lastPage
        )


        const unique =
          new Map()


        collected.forEach(
          (stock) => {

            const id =
              stockRawMaterialId(
                stock
              )


            const quantity =
              Number(
                stock?.quantity
              )


            if (
              !id
              ||
              !stockMaterialIsActive(
                stock
              )
              ||
              !Number.isFinite(
                quantity
              )
              ||
              quantity <= 0
            ) {
              return
            }


            unique.set(
              String(id),
              stock
            )

          }
        )


        warehouseStocks.value =
          Array.from(
            unique.values()
          )


        return true

      }

      catch (error) {

        warehouseStocks.value =
          []


        transferErrorMessage.value =
          inventoryErrorMessage(
            error,
            'Unable to load transferable warehouse stock.'
          )


        return false

      }

      finally {

        warehouseStocksLoading.value =
          false

      }

    })()


  try {

    return await
      warehouseLoadPromise

  }

  finally {

    warehouseLoadPromise =
      null

  }
}


/*
|--------------------------------------------------------------------------
| Submit Transfer
|--------------------------------------------------------------------------
*/


async function submitTransfer(
  payload
)
{
  if (
    !canManageInventory.value
    ||
    transferSubmitting.value
    ||
    !payload
    ||
    typeof payload !==
      'object'
  ) {
    return
  }


  transferSubmitting.value =
    true


  transferErrorMessage.value =
    ''


  transferServerErrors.value =
    {}


  try {

    const response =
      await inventoryService
        .createStockTransfer(
          payload
        )


    if (
      response?.success ===
      false
    ) {

      throw new Error(
        response?.message
        ||
        'Unable to complete stock transfer.'
      )

    }


    showTransferModal.value =
      false


    warehouseStocks.value =
      []


    showSuccessMessage(
      response?.message
      ||
      'Stock transferred to restaurant successfully.'
    )


    /*
    |--------------------------------------------------------------------------
    | Refresh Current Restaurant Stock
    |--------------------------------------------------------------------------
    */


    await loadRestaurantStocks()


    /*
    |--------------------------------------------------------------------------
    | Refresh Transfer History
    |--------------------------------------------------------------------------
    */


    transferHistoryRefreshKey.value +=
      1
    
    movementHistoryRefreshKey.value +=
  1


    /*
    |--------------------------------------------------------------------------
    | Notify Parent
    |--------------------------------------------------------------------------
    */


    emit(
      'inventory-changed',
      {
        type:
          'stock-transfer',

        transfer:
          response?.data
          ??
          null,
      }
    )

  }

  catch (error) {

    transferServerErrors.value =
      normalizeValidationErrors(
        error?.response
          ?.data
          ?.errors
      )


    transferErrorMessage.value =
      inventoryErrorMessage(
        error,
        'Unable to transfer warehouse stock to the restaurant.'
      )

  }

  finally {

    transferSubmitting.value =
      false

  }
}


/*
|--------------------------------------------------------------------------
| Transfer Messages
|--------------------------------------------------------------------------
*/


function clearTransferMessages()
{
  transferErrorMessage.value =
    ''


  transferServerErrors.value =
    {}
}


function showSuccessMessage(
  message
)
{
  successMessage.value =
    String(
      message
      ||
      ''
    )
      .trim()


  if (
    successTimer
  ) {

    clearTimeout(
      successTimer
    )

  }


  successTimer =
    setTimeout(
      () => {

        successMessage.value =
          ''


        successTimer =
          null

      },
      4000
    )
}


/*
|--------------------------------------------------------------------------
| Validation Errors
|--------------------------------------------------------------------------
*/


function normalizeValidationErrors(
  value
)
{
  if (
    !value
    ||
    typeof value !==
      'object'
    ||
    Array.isArray(
      value
    )
  ) {
    return {}
  }


  const normalized =
    {}


  Object.entries(
    value
  )
    .forEach(
      ([
        key,
        messages,
      ]) => {

        if (
          Array.isArray(
            messages
          )
        ) {

          normalized[
            key
          ] =
            messages
              .map(
                (message) =>
                  String(
                    message
                  )
              )
              .filter(
                Boolean
              )


          return
        }


        if (
          messages !==
            null
          &&
          messages !==
            undefined
        ) {

          normalized[
            key
          ] = [
            String(
              messages
            ),
          ]

        }

      }
    )


  return normalized
}


/*
|--------------------------------------------------------------------------
| Warehouse Stock Helpers
|--------------------------------------------------------------------------
*/


function stockRawMaterialId(
  stock
)
{
  const value =
    stock
      ?.raw_material_id
    ??
    stock
      ?.raw_material
      ?.id
    ??
    null


  const number =
    Number(
      value
    )


  return (
    Number.isInteger(
      number
    )
    &&
    number > 0
  )
    ? number
    : null
}


function stockMaterialIsActive(
  stock
)
{
  const value =
    stock
      ?.raw_material
      ?.is_active
    ??
    stock
      ?.is_active
    ??
    null


  if (
    value === true
    ||
    value === 1
    ||
    value === '1'
  ) {
    return true
  }


  return (
    typeof value ===
      'string'
    &&
    value
      .trim()
      .toLowerCase()
    ===
    'true'
  )
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


  filters.page =
    1


  await loadRestaurantStocks()
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


  filters.category =
    ''


  filters.page =
    1


  await loadRestaurantStocks()
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


  await loadRestaurantStocks()
}


/*
|--------------------------------------------------------------------------
| Refresh Active Sub Tab
|--------------------------------------------------------------------------
*/


async function refresh()
{
  /*
  |--------------------------------------------------------------------------
  | Transfer History
  |--------------------------------------------------------------------------
  */


  if (
    activeSubTab.value ===
    'transfer-history'
  ) {

    transferHistoryRefreshKey.value +=
      1


    return
  }


  /*
  |--------------------------------------------------------------------------
  | Restaurant Stock Movements
  |--------------------------------------------------------------------------
  */


  if (
    activeSubTab.value ===
    'stock-movements'
  ) {

    movementHistoryRefreshKey.value +=
      1


    return
  }


  /*
  |--------------------------------------------------------------------------
  | Current Restaurant Stock
  |--------------------------------------------------------------------------
  */


  await loadRestaurantStocks()
}


/*
|--------------------------------------------------------------------------
| Restaurant Stock Row Helpers
|--------------------------------------------------------------------------
*/


function materialName(
  stock
)
{
  return (
    stock
      ?.raw_material
      ?.material_name
    ||
    stock
      ?.material_name
    ||
    'Unknown Material'
  )
}


function materialCategory(
  stock
)
{
  return (
    stock
      ?.raw_material
      ?.category
    ||
    stock
      ?.category
    ||
    'No category'
  )
}


function materialUnit(
  stock
)
{
  return (
    stock
      ?.raw_material
      ?.base_unit
    ||
    stock
      ?.unit
    ||
    stock
      ?.base_unit
    ||
    '—'
  )
}


/*
|--------------------------------------------------------------------------
| Quantity / Cost Display
|--------------------------------------------------------------------------
*/


function quantityDisplay(
  stock
)
{
  return formattedOrDecimal(
    stock
      ?.quantity_formatted,

    stock
      ?.quantity,

    4
  )
}


function averageCostDisplay(
  stock
)
{
  const formatted =
    stock
      ?.average_unit_cost_formatted


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


  return formatMoney(
    stock
      ?.average_unit_cost,

    4
  )
}


function stockValueDisplay(
  stock
)
{
  const formatted =
    stock
      ?.stock_value_formatted


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


  return formatMoney(
    stock
      ?.stock_value,

    2
  )
}


/*
|--------------------------------------------------------------------------
| Restaurant Stock Status
|--------------------------------------------------------------------------
*/


function stockStatus(
  stock
)
{
  const explicit =
    String(
      stock
        ?.status
        ?.value
      ??
      stock
        ?.status
      ??
      ''
    )
      .trim()
      .toLowerCase()


  if (
    [
      'available',
      'limited',
      'out_of_stock',
    ].includes(
      explicit
    )
  ) {
    return explicit
  }


  const quantity =
    Number(
      stock
        ?.quantity
    )


  const minimum =
    Number(
      stock
        ?.raw_material
        ?.restaurant_minimum_quantity
      ??
      stock
        ?.restaurant_minimum_quantity
      ??
      0
    )


  if (
    !Number.isFinite(
      quantity
    )
  ) {
    return 'unknown'
  }


  if (
    quantity <= 0
  ) {
    return 'out_of_stock'
  }


  if (
    Number.isFinite(
      minimum
    )
    &&
    minimum > 0
    &&
    quantity <=
      minimum
  ) {
    return 'limited'
  }


  return 'available'
}


function stockStatusLabel(
  stock
)
{
  const provided =
    stock
      ?.status_label


  if (
    typeof provided ===
      'string'
    &&
    provided
      .trim() !==
      ''
  ) {
    return provided
  }


  const status =
    stockStatus(
      stock
    )


  if (
    status ===
    'available'
  ) {
    return 'Available'
  }


  if (
    status ===
    'limited'
  ) {
    return 'Limited'
  }


  if (
    status ===
    'out_of_stock'
  ) {
    return 'Out of Stock'
  }


  return 'Unknown'
}


/*
|--------------------------------------------------------------------------
| Row Key / Serial
|--------------------------------------------------------------------------
*/


function stockRowKey(
  stock,
  index
)
{
  const id =
    stock
      ?.id
    ??
    stock
      ?.raw_material_id
    ??
    stock
      ?.raw_material
      ?.id


  return id
    ? `restaurant-stock-${id}`
    : `restaurant-stock-row-${serialNumber(index)}`
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
| Formatting
|--------------------------------------------------------------------------
*/


function formattedOrDecimal(
  formatted,
  value,
  decimalPlaces
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


  return number
    .toLocaleString(
      'en-BD',
      {
        maximumFractionDigits:
          decimalPlaces,
      }
    )
}


function formatMoney(
  value,
  decimalPlaces
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


  return `৳ ${number.toLocaleString(
    'en-BD',
    {
      minimumFractionDigits:
        2,

      maximumFractionDigits:
        decimalPlaces,
    }
  )}`
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


/*
|--------------------------------------------------------------------------
| Error Message
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
| Lifecycle
|--------------------------------------------------------------------------
*/


onMounted(() => {

  void loadRestaurantStocks()

})


onBeforeUnmount(() => {

  if (
    successTimer
  ) {

    clearTimeout(
      successTimer
    )


    successTimer =
      null

  }

})
</script>