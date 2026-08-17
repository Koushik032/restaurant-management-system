<template>
  <section
    id="warehouse-movement-history"
    class="movement-history-panel"
  >
    <!-- Header -->

    <header class="movement-history-header">
      <div>
        <div class="movement-history-title">
          <div>
            <i
              class="bi bi-clock-history"
              aria-hidden="true"
            ></i>
          </div>

          <div>
            <h2>
              Stock Movement History
            </h2>

            <p>
              Review warehouse stock changes,
              quantities and audit details.
            </p>
          </div>
        </div>
      </div>


      <div class="movement-history-header-actions">
        <span
          class="movement-history-count"
          aria-live="polite"
        >
          {{ totalRecords }} Records
        </span>


        <button
          type="button"
          :disabled="loading"
          :aria-busy="loading"
          @click="loadMovements"
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
              ? 'Refreshing...'
              : 'Refresh'
          }}
        </button>
      </div>
    </header>


    <!-- Selected Material -->

    <div
      v-if="selectedMaterial"
      class="movement-selected-material"
    >
      <div>
        <i
          class="bi bi-funnel-fill"
          aria-hidden="true"
        ></i>

        <span>
          Showing history for
        </span>

        <strong>
          {{
            selectedMaterial.material_name
            ||
            'Unknown Material'
          }}
        </strong>

        <small>
          {{
            selectedMaterial.unit
            ||
            ''
          }}
        </small>
      </div>


      <button
        type="button"
        :disabled="loading"
        @click="clearSelectedMaterial"
      >
        <i
          class="bi bi-x-lg"
          aria-hidden="true"
        ></i>

        Show All
      </button>
    </div>


    <!-- Filters -->

    <StockMovementFilters
      v-model="filters"
      :materials="materials"
      :loading="loading"
      :active-filter-count="activeFilterCount"
      @apply="applyFilters"
      @clear="clearFilters"
    />


    <!-- Table -->

    <StockMovementTable
      :movements="movements"
      :meta="meta"
      :loading="loading"
      :error-message="errorMessage"
      @retry="loadMovements"
      @page-change="changePage"
      @view-details="openDetails"
    />


    <!-- Details Modal -->

    <StockMovementDetailsModal
      :show="showDetailsModal"
      :movement="selectedMovement"
      @close="closeDetails"
    />
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


import inventoryService
  from '@/services/inventoryService'


import StockMovementFilters
  from '@/components/inventory/movements/StockMovementFilters.vue'


import StockMovementTable
  from '@/components/inventory/movements/StockMovementTable.vue'


import StockMovementDetailsModal
  from '@/components/inventory/movements/StockMovementDetailsModal.vue'


/*
|--------------------------------------------------------------------------
| Props / Emits
|--------------------------------------------------------------------------
*/


const props = defineProps({
  selectedMaterial: {
    type: Object,
    default: null,
  },


  refreshKey: {
    type: Number,
    default: 0,
  },
})


const emit = defineEmits([
  'clear-material',
])


/*
|--------------------------------------------------------------------------
| State
|--------------------------------------------------------------------------
*/


const loading =
  ref(false)


const errorMessage =
  ref('')


const movements =
  ref([])


const materials =
  ref([])


const filters = ref(
  createDefaultFilters(),
)


const meta = reactive(
  inventoryService
    .createDefaultMeta(),
)


const showDetailsModal =
  ref(false)


const selectedMovement =
  ref(null)


let reloadRequestedWhileLoading =
  false


/*
|--------------------------------------------------------------------------
| Computed
|--------------------------------------------------------------------------
*/


const activeFilterCount = computed(() => {
  let count = 0


  if (filters.value.search) {
    count += 1
  }


  if (
    filters.value.raw_material_id
  ) {
    count += 1
  }


  if (
    filters.value.movement_type
  ) {
    count += 1
  }


  if (filters.value.date_from) {
    count += 1
  }


  if (filters.value.date_to) {
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


/*
|--------------------------------------------------------------------------
| Load Material Options
|--------------------------------------------------------------------------
|
| The raw-material endpoint is paginated and caps per_page at 100.
| Follow pagination so the movement filter does not silently omit materials
| when more than 100 current raw materials exist.
|
*/


async function loadMaterials() {
  try {
    const collectedMaterials = []

    let page = 1
    let resolvedLastPage = 1


    do {
      const response =
        await inventoryService
          .getRawMaterials({
            sort_by:
              'material_name',

            sort_direction:
              'asc',

            page,

            per_page:
              100,
          })


      const pageMaterials =
        Array.isArray(
          response?.data,
        )
          ? response.data
          : []


      collectedMaterials.push(
        ...pageMaterials,
      )


      resolvedLastPage =
        normalizePositiveInteger(
          response?.meta?.last_page,
          1,
        )


      page += 1
    } while (
      page <= resolvedLastPage
      &&
      page <= 100
    )


    const uniqueMaterials =
      new Map()


    collectedMaterials.forEach(
      (material) => {
        const id =
          material?.id


        if (
          id !== null
          &&
          id !== undefined
        ) {
          uniqueMaterials.set(
            String(id),
            material,
          )
        }
      },
    )


    materials.value =
      Array.from(
        uniqueMaterials.values(),
      )
  } catch {
    materials.value = []
  }
}


/*
|--------------------------------------------------------------------------
| Load Warehouse Movement History
|--------------------------------------------------------------------------
*/


async function loadMovements() {
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
        .getStockMovements({
          search:
            filters.value.search,

          raw_material_id:
            filters.value
              .raw_material_id,

          movement_type:
            filters.value
              .movement_type,

          location:
            'warehouse',

          date_from:
            filters.value.date_from,

          date_to:
            filters.value.date_to,

          page:
            filters.value.page,

          per_page:
            filters.value.per_page,
        })


    movements.value =
      Array.isArray(
        response?.data,
      )
        ? response.data
        : []


    updateMeta(
      response?.meta,
    )
  } catch (error) {
    movements.value = []

    resetMeta()


    errorMessage.value =
      inventoryService
        .getInventoryErrorMessage(
          error,
          'Unable to load stock movement history.',
        )
  } finally {
    loading.value = false


    if (
      reloadRequestedWhileLoading
    ) {
      reloadRequestedWhileLoading =
        false

      void loadMovements()
    }
  }
}


/*
|--------------------------------------------------------------------------
| Filters
|--------------------------------------------------------------------------
*/


async function applyFilters() {
  filters.value.page = 1

  await loadMovements()
}


async function clearFilters() {
  filters.value =
    createDefaultFilters()


  emit(
    'clear-material',
  )


  await loadMovements()
}


async function clearSelectedMaterial() {
  filters.value.raw_material_id =
    ''

  filters.value.page =
    1


  emit(
    'clear-material',
  )


  await loadMovements()
}


/*
|--------------------------------------------------------------------------
| Pagination
|--------------------------------------------------------------------------
*/


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


  await loadMovements()
}


/*
|--------------------------------------------------------------------------
| Details
|--------------------------------------------------------------------------
*/


function openDetails(
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


  selectedMovement.value =
    movement


  showDetailsModal.value =
    true
}


function closeDetails() {
  showDetailsModal.value =
    false


  selectedMovement.value =
    null
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
    paginationMeta
    &&
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


  filters.value.page =
    1
}


/*
|--------------------------------------------------------------------------
| Helpers
|--------------------------------------------------------------------------
*/


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


function selectedMaterialId(
  material,
) {
  const id =
    material?.id


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
    id,
  )
}


function createDefaultFilters() {
  return {
    search: '',
    raw_material_id: '',
    movement_type: '',
    date_from: '',
    date_to: '',
    page: 1,
    per_page: 10,
  }
}


/*
|--------------------------------------------------------------------------
| Selected Material Watch
|--------------------------------------------------------------------------
|
| Watch only the ID. Name/unit display changes do not need another API call.
| The current filter is checked first to avoid duplicate reloads when this
| component itself emits clear-material.
|
*/


watch(
  () =>
    selectedMaterialId(
      props.selectedMaterial,
    ),

  async (
    materialId,
  ) => {
    if (
      filters.value
        .raw_material_id ===
      materialId
    ) {
      return
    }


    filters.value
      .raw_material_id =
        materialId


    filters.value.page =
      1


    await loadMovements()
  },
)


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
      newValue !== oldValue
    ) {
      void Promise.allSettled([
        loadMaterials(),
        loadMovements(),
      ])
    }
  },
)


/*
|--------------------------------------------------------------------------
| Initial Load
|--------------------------------------------------------------------------
*/


onMounted(() => {
  filters.value
    .raw_material_id =
      selectedMaterialId(
        props.selectedMaterial,
      )


  void Promise.allSettled([
    loadMaterials(),
    loadMovements(),
  ])
})
</script>
