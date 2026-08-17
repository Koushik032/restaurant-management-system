<template>
  <section
    class="purchase-order-filter-card"
    :aria-busy="loading"
  >
    <!-- Validation Error -->

    <div
      v-if="validationError"
      class="purchase-order-filter-error"
      role="alert"
    >
      <i
        class="bi bi-exclamation-circle"
        aria-hidden="true"
      ></i>

      <span>
        {{ validationError }}
      </span>
    </div>


    <div class="purchase-order-filter-grid">
      <!-- From Date -->

      <div class="filter-group">
        <label for="purchase-order-date-from">
          From Date
        </label>

        <input
          id="purchase-order-date-from"
          v-model="localFilters.date_from"
          type="date"
          :max="
            localFilters.date_to
            ||
            undefined
          "
          :disabled="loading"
          @change="clearValidationError"
        />
      </div>


      <!-- To Date -->

      <div class="filter-group">
        <label for="purchase-order-date-to">
          To Date
        </label>

        <input
          id="purchase-order-date-to"
          v-model="localFilters.date_to"
          type="date"
          :min="
            localFilters.date_from
            ||
            undefined
          "
          :disabled="loading"
          @change="clearValidationError"
        />
      </div>


      <!-- Supplier -->

      <div class="filter-group">
        <label for="purchase-order-supplier">
          Supplier
        </label>

        <select
          id="purchase-order-supplier"
          v-model="localFilters.supplier_id"
          :disabled="loading"
        >
          <option value="">
            All Suppliers
          </option>

          <option
            v-if="hasUnavailableSelectedSupplier"
            :value="selectedSupplierId"
          >
            Supplier #{{ selectedSupplierId }}
            — Unavailable
          </option>

          <option
            v-for="supplier in supplierOptions"
            :key="String(supplier.id)"
            :value="String(supplier.id)"
          >
            {{ supplierLabel(supplier) }}
          </option>
        </select>
      </div>


      <!-- Status -->

      <div class="filter-group">
        <label for="purchase-order-status">
          Status
        </label>

        <select
          id="purchase-order-status"
          v-model="localFilters.status"
          :disabled="loading"
        >
          <option value="">
            All Statuses
          </option>

          <option
            v-for="status in statusOptions"
            :key="status.value"
            :value="status.value"
          >
            {{ status.label }}
          </option>
        </select>
      </div>
    </div>


    <!-- Actions -->

    <div class="purchase-order-filter-actions">
      <button
        type="button"
        class="po-filter-btn"
        :disabled="loading"
        :aria-busy="loading"
        @click="apply"
      >
        <span
          v-if="loading"
          class="spinner-border spinner-border-sm"
          aria-hidden="true"
        ></span>

        <i
          v-else
          class="bi bi-funnel"
          aria-hidden="true"
        ></i>

        {{
          loading
            ? 'Applying...'
            : 'Apply Filter'
        }}
      </button>


      <button
        type="button"
        class="po-clear-btn"
        :disabled="loading"
        @click="clear"
      >
        <i
          class="bi bi-x-circle"
          aria-hidden="true"
        ></i>

        Clear
      </button>
    </div>
  </section>
</template>


<script setup>
import {
  computed,
  reactive,
  ref,
  watch,
} from 'vue'


/*
|--------------------------------------------------------------------------
| Props / Emits
|--------------------------------------------------------------------------
*/


const props = defineProps({
  filters: {
    type: Object,
    required: true,
  },


  suppliers: {
    type: Array,
    default: () => [],
  },


  loading: {
    type: Boolean,
    default: false,
  },
})


const emit = defineEmits([
  'update:filters',
  'apply',
  'clear',
])


/*
|--------------------------------------------------------------------------
| Constants
|--------------------------------------------------------------------------
*/


const statusOptions = [
  {
    value: 'ordered',
    label: 'Ordered',
  },

  {
    value: 'partially_received',
    label: 'Partially Received',
  },

  {
    value: 'received',
    label: 'Received',
  },

  {
    value: 'cancelled',
    label: 'Cancelled',
  },
]


/*
|--------------------------------------------------------------------------
| State
|--------------------------------------------------------------------------
*/


const localFilters = reactive(
  createDefaultFilters(),
)


const validationError =
  ref('')


let syncingFromParent =
  false


/*
|--------------------------------------------------------------------------
| Supplier Options
|--------------------------------------------------------------------------
*/


const supplierOptions = computed(() => {
  if (
    !Array.isArray(
      props.suppliers,
    )
  ) {
    return []
  }


  const uniqueSuppliers =
    new Map()


  props.suppliers.forEach(
    (supplier) => {
      const id =
        supplier?.id


      if (
        id === null
        ||
        id === undefined
        ||
        id === ''
      ) {
        return
      }


      uniqueSuppliers.set(
        String(id),
        supplier,
      )
    },
  )


  return Array.from(
    uniqueSuppliers.values(),
  )
})


const selectedSupplierId = computed(() => {
  const value =
    localFilters.supplier_id


  if (
    value === null
    ||
    value === undefined
    ||
    value === ''
  ) {
    return ''
  }


  return String(value)
})


const hasUnavailableSelectedSupplier =
  computed(() => {
    if (!selectedSupplierId.value) {
      return false
    }


    return !supplierOptions.value.some(
      (supplier) =>
        String(supplier.id) ===
        selectedSupplierId.value,
    )
  })


/*
|--------------------------------------------------------------------------
| Parent -> Local Sync
|--------------------------------------------------------------------------
|
| Watch explicit filter fields instead of a deep object watcher.
| `flush: sync` lets the local watcher ignore values copied from the parent,
| preventing unnecessary update:filters echo emissions.
|
*/


watch(
  () => [
    props.filters?.date_from,
    props.filters?.date_to,
    props.filters?.supplier_id,
    props.filters?.status,
    props.filters?.page,
    props.filters?.per_page,
  ],

  (values) => {
    syncingFromParent =
      true


    Object.assign(
      localFilters,
      normalizeFilters({
        date_from:
          values[0],

        date_to:
          values[1],

        supplier_id:
          values[2],

        status:
          values[3],

        page:
          values[4],

        per_page:
          values[5],
      }),
    )


    syncingFromParent =
      false
  },

  {
    immediate: true,
    flush: 'sync',
  },
)


/*
|--------------------------------------------------------------------------
| Local -> Parent Sync
|--------------------------------------------------------------------------
*/


watch(
  localFilters,

  () => {
    if (syncingFromParent) {
      return
    }


    const nextFilters =
      normalizeFilters(
        localFilters,
      )


    if (
      filtersAreEqual(
        nextFilters,
        props.filters,
      )
    ) {
      return
    }


    emit(
      'update:filters',
      nextFilters,
    )
  },

  {
    deep: true,
    flush: 'sync',
  },
)


/*
|--------------------------------------------------------------------------
| Actions
|--------------------------------------------------------------------------
*/


function apply() {
  if (props.loading) {
    return
  }


  validationError.value =
    ''


  if (
    !validateDateRange()
  ) {
    return
  }


  localFilters.page =
    1


  emit(
    'apply',
  )
}


function clear() {
  if (props.loading) {
    return
  }


  validationError.value =
    ''


  const preservedPerPage =
    normalizePerPage(
      localFilters.per_page,
    )


  Object.assign(
    localFilters,
    createDefaultFilters(
      preservedPerPage,
    ),
  )


  emit(
    'clear',
  )
}


function clearValidationError() {
  validationError.value =
    ''
}


/*
|--------------------------------------------------------------------------
| Validation
|--------------------------------------------------------------------------
*/


function validateDateRange() {
  if (
    !isValidDateValue(
      localFilters.date_from,
    )
  ) {
    validationError.value =
      'Please select a valid From Date.'

    return false
  }


  if (
    !isValidDateValue(
      localFilters.date_to,
    )
  ) {
    validationError.value =
      'Please select a valid To Date.'

    return false
  }


  if (
    localFilters.date_from
    &&
    localFilters.date_to
    &&
    localFilters.date_to <
      localFilters.date_from
  ) {
    validationError.value =
      'To Date cannot be earlier than From Date.'

    return false
  }


  return true
}


function isValidDateValue(
  value,
) {
  if (!value) {
    return true
  }


  const normalized =
    String(value)


  if (
    !/^\d{4}-\d{2}-\d{2}$/.test(
      normalized,
    )
  ) {
    return false
  }


  const date =
    new Date(
      `${normalized}T00:00:00`,
    )


  if (
    Number.isNaN(
      date.getTime(),
    )
  ) {
    return false
  }


  const [
    year,
    month,
    day,
  ] = normalized
    .split('-')
    .map(Number)


  return (
    date.getFullYear() === year
    &&
    date.getMonth() + 1 ===
      month
    &&
    date.getDate() === day
  )
}


/*
|--------------------------------------------------------------------------
| Helpers
|--------------------------------------------------------------------------
*/


function createDefaultFilters(
  perPage = 10,
) {
  return {
    date_from: '',
    date_to: '',
    supplier_id: '',
    status: '',
    page: 1,
    per_page:
      normalizePerPage(
        perPage,
      ),
  }
}


function normalizeFilters(
  value,
) {
  const source =
    value
    &&
    typeof value === 'object'
      ? value
      : {}


  return {
    date_from:
      normalizeString(
        source.date_from,
      ),

    date_to:
      normalizeString(
        source.date_to,
      ),

    supplier_id:
      normalizeId(
        source.supplier_id,
      ),

    status:
      normalizeStatus(
        source.status,
      ),

    page:
      normalizePositiveInteger(
        source.page,
        1,
      ),

    per_page:
      normalizePerPage(
        source.per_page,
      ),
  }
}


function normalizeString(
  value,
) {
  if (
    value === null
    ||
    value === undefined
  ) {
    return ''
  }


  return String(
    value,
  ).trim()
}


function normalizeId(
  value,
) {
  if (
    value === null
    ||
    value === undefined
    ||
    value === ''
  ) {
    return ''
  }


  return String(
    value,
  )
}


function normalizeStatus(
  value,
) {
  const normalized =
    normalizeString(
      value,
    ).toLowerCase()


  if (!normalized) {
    return ''
  }


  return statusOptions.some(
    (status) =>
      status.value ===
      normalized,
  )
    ? normalized
    : ''
}


function normalizePositiveInteger(
  value,
  fallback,
) {
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


function normalizePerPage(
  value,
) {
  const allowedValues =
    [
      10,
      20,
      50,
      100,
    ]


  const number =
    normalizePositiveInteger(
      value,
      10,
    )


  return allowedValues.includes(
    number,
  )
    ? number
    : 10
}


function filtersAreEqual(
  first,
  second,
) {
  const normalizedSecond =
    normalizeFilters(
      second,
    )


  return (
    first.date_from ===
      normalizedSecond.date_from
    &&
    first.date_to ===
      normalizedSecond.date_to
    &&
    first.supplier_id ===
      normalizedSecond.supplier_id
    &&
    first.status ===
      normalizedSecond.status
    &&
    first.page ===
      normalizedSecond.page
    &&
    first.per_page ===
      normalizedSecond.per_page
  )
}


function supplierLabel(
  supplier,
) {
  const name =
    String(
      supplier?.supplier_name
      ??
      supplier?.name
      ??
      '',
    ).trim()


  if (name) {
    return name
  }


  return `Supplier #${supplier?.id}`
}
</script>
