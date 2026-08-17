<template>
  <section class="inventory-content-panel">
    <!-- Header -->

    <div class="inventory-section-header">
      <div>
        <h2>
          Warehouse Stock
        </h2>

        <p>
          Manage current warehouse stock and review
          warehouse-only stock movement history.
        </p>
      </div>

      <div class="warehouse-section-header-actions">
        <span
          class="
            inventory-section-status
            inventory-section-status-ready
          "
        >
          {{ headerStatusLabel }}
        </span>

        <button
          v-if="
            canManage
            &&
            activeWarehouseTab === 'current-stock'
          "
          type="button"
          class="warehouse-add-material-button"
          :disabled="loading"
          @click="openCreateModal"
        >
          <i
            class="bi bi-plus-circle"
            aria-hidden="true"
          ></i>

          <span>
            Add Raw Material
          </span>
        </button>

        <button
          type="button"
          class="warehouse-section-refresh"
          :disabled="currentViewLoading"
          @click="refreshCurrentView"
        >
          <i
            class="bi bi-arrow-clockwise"
            :class="{
              'inventory-refresh-spin':
                currentViewLoading,
            }"
            aria-hidden="true"
          ></i>

          {{
            currentViewLoading
              ? 'Refreshing...'
              : 'Refresh'
          }}
        </button>
      </div>
    </div>


    <!-- Success Message -->

    <div
      v-if="successMessage"
      class="
        warehouse-action-message
        warehouse-action-success
      "
      role="status"
    >
      <i
        class="bi bi-check-circle-fill"
        aria-hidden="true"
      ></i>

      <span>
        {{ successMessage }}
      </span>

      <button
        type="button"
        aria-label="Dismiss message"
        @click="successMessage = ''"
      >
        <i
          class="bi bi-x-lg"
          aria-hidden="true"
        ></i>
      </button>
    </div>


    <!-- Action Error -->

    <div
      v-if="actionErrorMessage"
      class="
        warehouse-action-message
        warehouse-action-error
      "
      role="alert"
    >
      <i
        class="bi bi-exclamation-circle-fill"
        aria-hidden="true"
      ></i>

      <span>
        {{ actionErrorMessage }}
      </span>

      <button
        type="button"
        aria-label="Dismiss error"
        @click="actionErrorMessage = ''"
      >
        <i
          class="bi bi-x-lg"
          aria-hidden="true"
        ></i>
      </button>
    </div>


    <!-- Warehouse Tabs -->

    <div
      class="warehouse-view-tabs"
      role="tablist"
      aria-label="Warehouse stock views"
    >
      <button
        type="button"
        role="tab"
        class="warehouse-view-tab"
        :class="{
          'warehouse-view-tab-active':
            activeWarehouseTab ===
            'current-stock',
        }"
        :aria-selected="
          activeWarehouseTab ===
          'current-stock'
        "
        @click="
          switchWarehouseTab(
            'current-stock',
          )
        "
      >
        <i
          class="bi bi-boxes"
          aria-hidden="true"
        ></i>

        <span>
          Current Stock
        </span>

        <span class="warehouse-view-tab-count">
          {{ totalRecords }}
        </span>
      </button>


      <button
        type="button"
        role="tab"
        class="warehouse-view-tab"
        :class="{
          'warehouse-view-tab-active':
            activeWarehouseTab ===
            'stock-movements',
        }"
        :aria-selected="
          activeWarehouseTab ===
          'stock-movements'
        "
        @click="
          switchWarehouseTab(
            'stock-movements',
          )
        "
      >
        <i
          class="bi bi-clock-history"
          aria-hidden="true"
        ></i>

        <span>
          Stock Movements
        </span>
      </button>
    </div>


    <!-- Current Stock Tab -->

    <div
      v-if="
        activeWarehouseTab ===
        'current-stock'
      "
      class="warehouse-view-panel"
      role="tabpanel"
    >
      <!-- Quick Summary -->

      <div class="warehouse-quick-summary">
        <article>
          <span>
            Current Results
          </span>

          <strong>
            {{ totalRecords }}
          </strong>
        </article>


        <article>
          <span>
            Page
          </span>

          <strong>
            {{ currentPage }}
            /
            {{ lastPage }}
          </strong>
        </article>


        <article
          :class="{
            'warehouse-quick-alert':
              limitedResultCount > 0,
          }"
        >
          <span>
            Limited on Page
          </span>

          <strong>
            {{ limitedResultCount }}
          </strong>
        </article>


        <article
          :class="{
            'warehouse-quick-danger':
              outOfStockResultCount > 0,
          }"
        >
          <span>
            Out of Stock on Page
          </span>

          <strong>
            {{ outOfStockResultCount }}
          </strong>
        </article>
      </div>


      <!-- Filters -->

      <WarehouseStockFilters
        v-model="filters"
        :options="options"
        :loading="loading"
        :active-filter-count="
          activeFilterCount
        "
        @apply="applyFilters"
        @clear="clearFilters"
      />


      <!-- Table -->

      <WarehouseStockTable
        :stocks="stocks"
        :meta="meta"
        :loading="loading"
        :error-message="errorMessage"
        :can-manage="canManage"
        :edit-loading-id="editLoadingId"
        :adjust-loading-id="
          adjustLoadingId
        "
        :status-loading-id="
          statusLoadingId
        "
        :delete-loading-id="
          deleteLoadingId
        "
        @retry="loadWarehouseStocks"
        @page-change="changePage"
        @edit="openEditModal"
        @adjust="openAdjustmentModal"
        @toggle-status="openStatusModal"
        @delete="openDeleteModal"
        @history="openMovementHistory"
      />
    </div>


    <!-- Stock Movements Tab -->

    <div
      v-else-if="
        activeWarehouseTab ===
        'stock-movements'
      "
      class="
        warehouse-view-panel
        warehouse-movements-view-panel
      "
      role="tabpanel"
    >
      <StockMovementHistorySection
        :selected-material="
          selectedHistoryMaterial
        "
        :refresh-key="
          movementHistoryRefreshKey
        "
        @clear-material="
          selectedHistoryMaterial = null
        "
      />
    </div>


    <!-- Create / Edit Modal -->

    <RawMaterialFormModal
      :show="showFormModal"
      :material="editingMaterial"
      :options="options"
      :submitting="formSubmitting"
      :server-errors="formServerErrors"
      :error-message="formErrorMessage"
      @close="closeFormModal"
      @submit="saveRawMaterial"
    />


    <!-- Warehouse Adjustment Modal -->

    <WarehouseStockAdjustmentModal
      :show="showAdjustmentModal"
      :stock="adjustmentStock"
      :submitting="
        adjustmentSubmitting
      "
      :server-errors="
        adjustmentServerErrors
      "
      :error-message="
        adjustmentErrorMessage
      "
      @close="closeAdjustmentModal"
      @submit="saveWarehouseAdjustment"
    />


    <!-- Status / Delete Modal -->

    <RawMaterialActionModal
      :show="showActionModal"
      :action-type="actionType"
      :stock="actionStock"
      :submitting="actionSubmitting"
      :error-message="
        materialActionError
      "
      @close="closeActionModal"
      @confirm="confirmMaterialAction"
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
  watch,
} from 'vue'

import inventoryService
  from '@/services/inventoryService'

import WarehouseStockFilters
  from '@/components/inventory/warehouse/WarehouseStockFilters.vue'

import WarehouseStockTable
  from '@/components/inventory/warehouse/WarehouseStockTable.vue'

import RawMaterialFormModal
  from '@/components/inventory/raw-materials/RawMaterialFormModal.vue'

import WarehouseStockAdjustmentModal
  from '@/components/inventory/warehouse/WarehouseStockAdjustmentModal.vue'
import RawMaterialActionModal
  from '@/components/inventory/raw-materials/RawMaterialActionModal.vue'
import StockMovementHistorySection
  from '@/components/inventory/sections/StockMovementHistorySection.vue'

const props = defineProps({
  options: {
    type: Object,
    default: () => ({
      categories: [],
      units: [],
      warehouse_statuses: [],
    }),
  },

  refreshKey: {
    type: Number,
    default: 0,
  },

  canManage: {
    type: Boolean,
    default: false,
  },
})

const emit = defineEmits([
  'inventory-changed',
])

/*
|--------------------------------------------------------------------------
| Warehouse List State
|--------------------------------------------------------------------------
*/

const loading =
  ref(false)

const errorMessage =
  ref('')

const stocks =
  ref([])

const filters = ref(
  createDefaultFilters(),
)

const meta = reactive(
  inventoryService
    .createDefaultMeta(),
)

/*
|--------------------------------------------------------------------------
| Movement History State
|--------------------------------------------------------------------------
*/

const selectedHistoryMaterial =
  ref(null)

const movementHistoryRefreshKey =
  ref(0)

const activeWarehouseTab =
  ref('current-stock')

const WAREHOUSE_TABS =
  Object.freeze([
    'current-stock',
    'stock-movements',
  ])

/*
|--------------------------------------------------------------------------
| Status and Delete Action State
|--------------------------------------------------------------------------
*/

const showActionModal =
  ref(false)

const actionType =
  ref('')

const actionStock =
  ref(null)

const actionSubmitting =
  ref(false)

const materialActionError =
  ref('')

const statusLoadingId =
  ref(null)

const deleteLoadingId =
  ref(null)

/*
|--------------------------------------------------------------------------
| Action Messages
|--------------------------------------------------------------------------
*/

const successMessage =
  ref('')

const actionErrorMessage =
  ref('')

let successMessageTimer =
  null


let reloadRequestedWhileLoading =
  false

/*
|--------------------------------------------------------------------------
| Form Modal State
|--------------------------------------------------------------------------
*/

const showFormModal =
  ref(false)

const editingMaterial =
  ref(null)

const formSubmitting =
  ref(false)

const formServerErrors =
  ref({})

const formErrorMessage =
  ref('')

const editLoadingId =
  ref(null)

/*
|--------------------------------------------------------------------------
| Adjustment Modal State
|--------------------------------------------------------------------------
*/

const showAdjustmentModal =
  ref(false)

const adjustmentStock =
  ref(null)

const adjustmentSubmitting =
  ref(false)

const adjustmentServerErrors =
  ref({})

const adjustmentErrorMessage =
  ref('')

const adjustLoadingId =
  ref(null)

/*
|--------------------------------------------------------------------------
| Computed Values
|--------------------------------------------------------------------------
*/

const activeFilterCount = computed(() => {
  let count = 0


  if (filters.value.search) {
    count += 1
  }


  if (filters.value.category) {
    count += 1
  }


  if (filters.value.base_unit) {
    count += 1
  }


  if (filters.value.status) {
    count += 1
  }


  if (
    filters.value.is_active !==
    ''
  ) {
    count += 1
  }


  return count
})


const totalRecords = computed(() => {
  return normalizeNonNegativeInteger(
    meta.total,
    0,
  )
})


const lastPage = computed(() => {
  return normalizePositiveInteger(
    meta.last_page,
    1,
  )
})


const currentPage = computed(() => {
  return Math.min(
    normalizePositiveInteger(
      meta.current_page,
      1,
    ),
    lastPage.value,
  )
})

const limitedResultCount = computed(() => {
  return stocks.value.filter(
    (stock) =>
      stock.status ===
      'limited',
  ).length
})

const outOfStockResultCount = computed(() => {
  return stocks.value.filter(
    (stock) =>
      stock.status ===
      'out_of_stock',
  ).length
})

const headerStatusLabel =
  computed(() => {
    if (
      activeWarehouseTab.value ===
      'stock-movements'
    ) {
      return 'Warehouse Audit Log'
    }

    return (
      `${totalRecords.value} Stock Records`
    )
  })


const currentViewLoading =
  computed(() => {
    return (
      activeWarehouseTab.value ===
        'current-stock'
      &&
      loading.value
    )
  })


/*
|--------------------------------------------------------------------------
| Warehouse View Tabs
|--------------------------------------------------------------------------
*/

function switchWarehouseTab(
  tab,
) {
  if (
    !WAREHOUSE_TABS.includes(
      tab,
    )
  ) {
    return
  }

  /*
  |--------------------------------------------------------------------------
  | Manual Movement Tab
  |--------------------------------------------------------------------------
  |
  | Clicking Stock Movements directly means "show all".
  |
  | Material-specific history is preserved only when the History button
  | from the Current Stock table opens the movement tab.
  |
  */

  if (
    tab === 'stock-movements'
    &&
    activeWarehouseTab.value !==
      'stock-movements'
  ) {
    selectedHistoryMaterial.value =
      null
  }

  activeWarehouseTab.value =
    tab

  actionErrorMessage.value =
    ''
}


/*
|--------------------------------------------------------------------------
| Refresh Active Warehouse View
|--------------------------------------------------------------------------
*/

async function refreshCurrentView() {
  if (
    activeWarehouseTab.value ===
    'stock-movements'
  ) {
    movementHistoryRefreshKey.value +=
      1

    return
  }

  await loadWarehouseStocks()
}
/*
|--------------------------------------------------------------------------
| Load Warehouse Stocks
|--------------------------------------------------------------------------
*/

async function loadWarehouseStocks() {
  if (loading.value) {
    reloadRequestedWhileLoading =
      true

    return
  }


  loading.value = true
  errorMessage.value = ''


  try {
    const response =
      await inventoryService
        .getWarehouseStocks({
          search:
            filters.value.search,

          category:
            filters.value.category,

          base_unit:
            filters.value.base_unit,

          status:
            filters.value.status,

          is_active:
            filters.value.is_active,

          sort_by:
            filters.value.sort_by,

          sort_direction:
            filters.value
              .sort_direction,

          page:
            filters.value.page,

          per_page:
            filters.value.per_page,
        })


    stocks.value =
      Array.isArray(
        response?.data,
      )
        ? response.data
        : []


    updateMeta(
      response?.meta,
    )
  } catch (error) {
    stocks.value = []

    resetMeta()


    errorMessage.value =
      inventoryService
        .getInventoryErrorMessage(
          error,
          'Unable to load warehouse stock.',
        )
  } finally {
    loading.value = false


    if (
      reloadRequestedWhileLoading
    ) {
      reloadRequestedWhileLoading =
        false

      void loadWarehouseStocks()
    }
  }
}

/*
|--------------------------------------------------------------------------
| Filters and Pagination
|--------------------------------------------------------------------------
*/

async function applyFilters() {
  filters.value.page = 1

  await loadWarehouseStocks()
}

async function clearFilters() {
  filters.value =
    createDefaultFilters()

  await loadWarehouseStocks()
}

async function changePage(
  page,
) {
  const targetPage =
    normalizePositiveInteger(
      page,
      currentPage.value,
    )


  if (
    targetPage ===
      currentPage.value
    ||
    targetPage >
      lastPage.value
  ) {
    return
  }


  filters.value.page =
    targetPage


  await loadWarehouseStocks()
}

/*
|--------------------------------------------------------------------------
| Create Modal
|--------------------------------------------------------------------------
*/

function openCreateModal() {
  if (
    !props.canManage
    ||
    loading.value
  ) {
    return
  }

  clearFormMessages()

  editingMaterial.value = null
  showFormModal.value = true
}

/*
|--------------------------------------------------------------------------
| Open Material Movement History
|--------------------------------------------------------------------------
*/

function openMovementHistory(
  stock,
) {
  if (
    !stock?.raw_material_id
  ) {
    return
  }

  selectedHistoryMaterial.value = {
    id:
      stock.raw_material_id,

    material_name:
      stock.material_name
      ||
      stock.raw_material
        ?.material_name
      ||
      'Unknown Material',

    unit:
      stock.unit
      ||
      stock.base_unit
      ||
      stock.raw_material
        ?.base_unit
      ||
      '',
  }

  /*
  |--------------------------------------------------------------------------
  | Open Movement Tab
  |--------------------------------------------------------------------------
  |
  | StockMovementHistorySection receives selectedMaterial before it mounts.
  | Its own onMounted() copies that material ID into its raw_material_id
  | filter before loading movements.
  |
  */

  activeWarehouseTab.value =
    'stock-movements'

  actionErrorMessage.value =
    ''

  window.requestAnimationFrame(() => {
    document
      .querySelector(
        '.warehouse-view-tabs',
      )
      ?.scrollIntoView({
        behavior:
          'smooth',

        block:
          'start',
      })
  })
}

/*
|--------------------------------------------------------------------------
| Edit Modal
|--------------------------------------------------------------------------
*/

async function openEditModal(
  stock,
) {
  if (
    !props.canManage ||
    !stock?.raw_material_id ||
    editLoadingId.value !== null
  ) {
    return
  }

  actionErrorMessage.value = ''
  editLoadingId.value =
    stock.raw_material_id

  try {
    const response =
      await inventoryService
        .getRawMaterial(
          stock.raw_material_id,
        )

    if (!response?.data) {
      throw new Error(
        'Raw material information was not found.',
      )
    }

    clearFormMessages()

    editingMaterial.value =
      response.data

    showFormModal.value = true
  } catch (error) {
    actionErrorMessage.value =
      inventoryService
        .getInventoryErrorMessage(
          error,
          'Unable to load raw material information.',
        )
  } finally {
    editLoadingId.value = null
  }
}

/*
|--------------------------------------------------------------------------
| Save Raw Material
|--------------------------------------------------------------------------
*/

async function saveRawMaterial(
  payload,
) {
  if (
    formSubmitting.value ||
    !props.canManage
  ) {
    return
  }

  formSubmitting.value = true
  formServerErrors.value = {}
  formErrorMessage.value = ''
  actionErrorMessage.value = ''

  const isEditing =
    Boolean(
      editingMaterial.value?.id,
    )

  try {
    const response =
      isEditing
        ? await inventoryService
            .updateRawMaterial(
              editingMaterial.value.id,
              payload,
            )
        : await inventoryService
            .createRawMaterial(
              payload,
            )

    /*
    |--------------------------------------------------------------------------
    | Stop Submit Loading
    |--------------------------------------------------------------------------
    |
    | Modal close করার আগে submitting false করতে হবে।
    |
    */

    formSubmitting.value = false

    /*
    |--------------------------------------------------------------------------
    | Close Form and Return to Warehouse Stock
    |--------------------------------------------------------------------------
    */

    closeFormModal(true)

    /*
    |--------------------------------------------------------------------------
    | Success Message
    |--------------------------------------------------------------------------
    */

    showSuccessMessage(
      response?.message ||
      (
        isEditing
          ? 'Raw material updated successfully.'
          : 'Raw material created successfully.'
      ),
    )

    /*
    |--------------------------------------------------------------------------
    | Return to First Page and Reload Table
    |--------------------------------------------------------------------------
    */

    filters.value.page = 1

    await loadWarehouseStocks()
    movementHistoryRefreshKey.value += 1
    /*
    |--------------------------------------------------------------------------
    | Refresh Summary Cards and Options
    |--------------------------------------------------------------------------
    */

    emit('inventory-changed')

    /*
    |--------------------------------------------------------------------------
    | Scroll Back to Warehouse Section
    |--------------------------------------------------------------------------
    */

    window.requestAnimationFrame(() => {
      document
        .querySelector(
          '.inventory-content-panel',
        )
        ?.scrollIntoView({
          behavior: 'smooth',
          block: 'start',
        })
    })
  } catch (error) {
    formServerErrors.value =
      extractValidationErrors(
        error,
      )

    formErrorMessage.value =
      inventoryService
        .getInventoryErrorMessage(
          error,
          isEditing
            ? 'Unable to update raw material.'
            : 'Unable to create raw material.',
        )
  } finally {
    formSubmitting.value = false
  }
}

/*
|--------------------------------------------------------------------------
| Close Modal
|--------------------------------------------------------------------------
*/

function closeFormModal(
  forceClose = false,
) {
  if (
    formSubmitting.value &&
    !forceClose
  ) {
    return
  }

  showFormModal.value = false
  editingMaterial.value = null

  clearFormMessages()
}

function clearFormMessages() {
  formServerErrors.value = {}
  formErrorMessage.value = ''
}
/*
|--------------------------------------------------------------------------
| Open Activate / Deactivate Modal
|--------------------------------------------------------------------------
*/

function openStatusModal(
  stock,
) {
  if (
    !props.canManage
    ||
    !stock?.raw_material_id
  ) {
    return
  }


  if (
    materialIsActive(
      stock,
    )
    &&
    warehouseQuantity(
      stock,
    ) > 0
  ) {
    actionErrorMessage.value =
      'Warehouse quantity must be zero before this material can be deactivated.'

    return
  }


  clearMaterialActionError()

  actionErrorMessage.value = ''

  actionStock.value = stock

  actionType.value =
    materialIsActive(stock)
      ? 'deactivate'
      : 'activate'

  showActionModal.value = true
}
/*
|--------------------------------------------------------------------------
| Open Delete Modal
|--------------------------------------------------------------------------
*/

function openDeleteModal(
  stock,
) {
  if (
    !props.canManage
    ||
    !stock?.raw_material_id
  ) {
    return
  }


  if (
    warehouseQuantity(
      stock,
    ) > 0
  ) {
    actionErrorMessage.value =
      'Warehouse quantity must be zero before this material can be archived.'

    return
  }


  clearMaterialActionError()

  actionErrorMessage.value = ''

  actionStock.value = stock
  actionType.value = 'delete'
  showActionModal.value = true
}
/*
|--------------------------------------------------------------------------
| Confirm Material Status or Delete
|--------------------------------------------------------------------------
*/

async function confirmMaterialAction() {
  if (
    actionSubmitting.value
    ||
    !props.canManage
    ||
    !actionStock.value
      ?.raw_material_id
  ) {
    return
  }

  const rawMaterialId =
    actionStock.value
      .raw_material_id

  const selectedAction =
    actionType.value


  if (
    ![
      'activate',
      'deactivate',
      'delete',
    ].includes(
      selectedAction,
    )
  ) {
    materialActionError.value =
      'Invalid material action.'

    return
  }


  if (
    (
      selectedAction ===
        'deactivate'
      ||
      selectedAction ===
        'delete'
    )
    &&
    warehouseQuantity(
      actionStock.value,
    ) > 0
  ) {
    materialActionError.value =
      selectedAction ===
        'delete'
        ? 'Warehouse quantity must be zero before this material can be archived.'
        : 'Warehouse quantity must be zero before this material can be deactivated.'

    return
  }


  actionSubmitting.value = true
  materialActionError.value = ''
  actionErrorMessage.value = ''

  if (
    selectedAction === 'delete'
  ) {
    deleteLoadingId.value =
      rawMaterialId
  } else {
    statusLoadingId.value =
      rawMaterialId
  }

  try {
    let response = null

    if (
      selectedAction === 'delete'
    ) {
      response =
        await inventoryService
          .deleteRawMaterial(
            rawMaterialId,
          )
    } else {
      response =
        await inventoryService
          .toggleRawMaterialStatus(
            rawMaterialId,
          )
    }

    /*
    |--------------------------------------------------------------------------
    | Stop Loading Before Modal Close
    |--------------------------------------------------------------------------
    */

    actionSubmitting.value = false

    /*
    |--------------------------------------------------------------------------
    | Preserve Message Before Clearing Modal
    |--------------------------------------------------------------------------
    */

    const successText =
      response?.message
      ||
      resolveMaterialActionSuccessMessage(
        selectedAction,
      )

    /*
    |--------------------------------------------------------------------------
    | Adjust Page Before Deleted Row Reload
    |--------------------------------------------------------------------------
    */

    if (
      selectedAction === 'delete'
      &&
      stocks.value.length === 1
      &&
      Number(meta.current_page) > 1
    ) {
      filters.value.page =
        Number(meta.current_page) - 1
    }

    closeActionModal(true)

    showSuccessMessage(
      successText,
    )

    await loadWarehouseStocks()

    emit('inventory-changed')

    window.requestAnimationFrame(() => {
      document
        .querySelector(
          '.warehouse-table-card',
        )
        ?.scrollIntoView({
          behavior: 'smooth',
          block: 'start',
        })
    })
  } catch (error) {
    materialActionError.value =
      inventoryService
        .getInventoryErrorMessage(
          error,
          selectedAction === 'delete'
            ? 'Unable to archive raw material.'
            : 'Unable to update raw material status.',
        )
  } finally {
    actionSubmitting.value = false
    statusLoadingId.value = null
    deleteLoadingId.value = null
  }
}
/*
|--------------------------------------------------------------------------
| Close Action Modal
|--------------------------------------------------------------------------
*/

function closeActionModal(
  forceClose = false,
) {
  if (
    actionSubmitting.value
    &&
    !forceClose
  ) {
    return
  }

  showActionModal.value = false
  actionType.value = ''
  actionStock.value = null

  clearMaterialActionError()
}
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


function normalizePositiveInteger(
  value,
  fallback = 1,
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


function normalizeNonNegativeInteger(
  value,
  fallback = 0,
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
    numericValue < 0
  ) {
    return fallback
  }


  return Math.floor(
    numericValue,
  )
}

function clearMaterialActionError() {
  materialActionError.value = ''
}

function resolveMaterialActionSuccessMessage(
  selectedAction,
) {
  if (
    selectedAction === 'delete'
  ) {
    return 'Raw material archived successfully.'
  }

  if (
    selectedAction === 'activate'
  ) {
    return 'Raw material activated successfully.'
  }

  return 'Raw material deactivated successfully.'
}
/*
|--------------------------------------------------------------------------
| Validation Error Resolver
|--------------------------------------------------------------------------
*/

function extractValidationErrors(
  error,
) {
  const errors =
    error?.response?.data?.errors

  return (
    errors &&
    typeof errors === 'object'
  )
    ? errors
    : {}
}

/*
|--------------------------------------------------------------------------
| Success Message
|--------------------------------------------------------------------------
*/

function showSuccessMessage(
  message,
) {
  successMessage.value = message
  actionErrorMessage.value = ''

  if (successMessageTimer) {
    clearTimeout(
      successMessageTimer,
    )
  }

  successMessageTimer =
    setTimeout(() => {
      successMessage.value = ''
    }, 5000)
}

/*
|--------------------------------------------------------------------------
| Pagination Meta
|--------------------------------------------------------------------------
*/

function updateMeta(
  paginationMeta,
) {
  const resolvedMeta =
    paginationMeta &&
    typeof paginationMeta ===
      'object'
      ? paginationMeta
      : {}

  Object.assign(
    meta,
    inventoryService
      .createDefaultMeta(),
    resolvedMeta,
  )

  filters.value.page =
    normalizePositiveInteger(
      meta.current_page,
      1,
    )
}

function resetMeta() {
  Object.assign(
    meta,
    inventoryService
      .createDefaultMeta(),
  )
}

/*
|--------------------------------------------------------------------------
| Open Warehouse Adjustment Modal
|--------------------------------------------------------------------------
*/

async function openAdjustmentModal(
  stock,
) {
  if (
    !props.canManage
    ||
    !stock?.raw_material_id
    ||
    adjustLoadingId.value !== null
  ) {
    return
  }

  actionErrorMessage.value = ''
  adjustLoadingId.value =
    stock.raw_material_id

  try {
    /*
    |--------------------------------------------------------------------------
    | Load Latest Warehouse Quantity
    |--------------------------------------------------------------------------
    |
    | Table data পুরোনো হলেও modal-এ latest database quantity দেখাবে।
    |
    */

    const response =
      await inventoryService
        .getWarehouseStock(
          stock.raw_material_id,
        )

    if (!response?.data) {
      throw new Error(
        'Warehouse stock information was not found.',
      )
    }


    if (
      !materialIsActive(
        response.data,
      )
    ) {
      throw new Error(
        'Activate this raw material before adjusting warehouse stock.',
      )
    }


    clearAdjustmentMessages()

    adjustmentStock.value =
      response.data

    showAdjustmentModal.value =
      true
  } catch (error) {
    actionErrorMessage.value =
      inventoryService
        .getInventoryErrorMessage(
          error,
          'Unable to load warehouse stock information.',
        )
  } finally {
    adjustLoadingId.value = null
  }
}

/*
|--------------------------------------------------------------------------
| Save Warehouse Adjustment
|--------------------------------------------------------------------------
*/

async function saveWarehouseAdjustment(
  payload,
) {
  if (
    adjustmentSubmitting.value
    ||
    !props.canManage
    ||
    !adjustmentStock.value
      ?.raw_material_id
  ) {
    return
  }

  adjustmentSubmitting.value = true
  adjustmentServerErrors.value = {}
  adjustmentErrorMessage.value = ''
  actionErrorMessage.value = ''

  const adjustmentType =
    payload.adjustment_type

  try {
    const response =
      await inventoryService
        .adjustWarehouseStock(
          adjustmentStock.value
            .raw_material_id,
          payload,
        )

    /*
    |--------------------------------------------------------------------------
    | Stop Loading Before Closing
    |--------------------------------------------------------------------------
    */

    adjustmentSubmitting.value = false

    /*
    |--------------------------------------------------------------------------
    | Close Modal and Return to Warehouse Table
    |--------------------------------------------------------------------------
    */

    closeAdjustmentModal(true)

    showSuccessMessage(
      response?.message
      ||
      (
        adjustmentType ===
        'decrease'
          ? 'Warehouse stock decreased successfully.'
          : 'Warehouse stock increased successfully.'
      ),
    )

    /*
    |--------------------------------------------------------------------------
    | Reload Current Warehouse Page
    |--------------------------------------------------------------------------
    */

    await loadWarehouseStocks()

    movementHistoryRefreshKey.value += 1


    /*
    |--------------------------------------------------------------------------
    | Refresh Top Summary Cards
    |--------------------------------------------------------------------------
    */

    emit('inventory-changed')

    /*
    |--------------------------------------------------------------------------
    | Scroll Back to Warehouse Section
    |--------------------------------------------------------------------------
    */

    window.requestAnimationFrame(() => {
      document
        .querySelector(
          '.warehouse-table-card',
        )
        ?.scrollIntoView({
          behavior: 'smooth',
          block: 'start',
        })
    })
  } catch (error) {
    adjustmentServerErrors.value =
      extractValidationErrors(
        error,
      )

    adjustmentErrorMessage.value =
      inventoryService
        .getInventoryErrorMessage(
          error,
          adjustmentType ===
          'decrease'
            ? 'Unable to decrease warehouse stock.'
            : 'Unable to increase warehouse stock.',
        )
  } finally {
    adjustmentSubmitting.value = false
  }
}

/*
|--------------------------------------------------------------------------
| Close Warehouse Adjustment Modal
|--------------------------------------------------------------------------
*/

function closeAdjustmentModal(
  forceClose = false,
) {
  if (
    adjustmentSubmitting.value
    &&
    !forceClose
  ) {
    return
  }

  showAdjustmentModal.value = false
  adjustmentStock.value = null

  clearAdjustmentMessages()
}

/*
|--------------------------------------------------------------------------
| Clear Adjustment Errors
|--------------------------------------------------------------------------
*/

function clearAdjustmentMessages() {
  adjustmentServerErrors.value = {}
  adjustmentErrorMessage.value = ''
}

/*
|--------------------------------------------------------------------------
| Default Filters
|--------------------------------------------------------------------------
|
| Default state intentionally shows all materials so inactive materials
| remain visible and can be reactivated from this management screen.
|
| Controller validation-এ sort_by=id allowed নয়।
| তাই latest sort-এর জন্য created_at ব্যবহার করা হচ্ছে।
|
*/

function createDefaultFilters() {
  return {
    search: '',
    category: '',
    base_unit: '',
    status: '',
    is_active: '',
    sort_by: 'created_at',
    sort_direction: 'desc',
    page: 1,
    per_page: 10,
  }
}

/*
|--------------------------------------------------------------------------
| Parent Refresh Watch
|--------------------------------------------------------------------------
*/

watch(
  () => props.refreshKey,

  (
    newValue,
    oldValue,
  ) => {
    if (
      newValue === oldValue
    ) {
      return
    }

    /*
    |--------------------------------------------------------------------------
    | Refresh Current Nested View
    |--------------------------------------------------------------------------
    */

    if (
      activeWarehouseTab.value ===
      'stock-movements'
    ) {
      movementHistoryRefreshKey.value +=
        1

      return
    }

    void loadWarehouseStocks()
  },
)

onMounted(() => {
  void loadWarehouseStocks()
})


onBeforeUnmount(() => {
  if (successMessageTimer) {
    clearTimeout(
      successMessageTimer,
    )

    successMessageTimer =
      null
  }
})
</script>