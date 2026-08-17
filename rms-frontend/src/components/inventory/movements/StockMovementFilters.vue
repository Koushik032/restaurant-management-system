<template>
  <section
    class="movement-filter-card"
    :aria-busy="loading"
  >
    <!-- Header -->

    <div class="movement-filter-header">
      <div>
        <h3>
          Movement Filters
        </h3>

        <p>
          Filter warehouse movements by material,
          movement type and date.
        </p>
      </div>

      <span
        v-if="normalizedActiveFilterCount > 0"
        class="movement-active-filter-count"
        aria-live="polite"
      >
        {{ normalizedActiveFilterCount }}
        active
      </span>
    </div>


    <!-- Validation Error -->

    <div
      v-if="validationError"
      class="movement-filter-error"
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


    <div class="movement-filter-grid">
      <!-- Search -->

      <div
        class="movement-filter-group movement-filter-search"
      >
        <label for="movement-search">
          Search
        </label>

        <div class="movement-search-input">
          <i
            class="bi bi-search"
            aria-hidden="true"
          ></i>

          <input
            id="movement-search"
            v-model.trim="searchValue"
            type="text"
            maxlength="180"
            autocomplete="off"
            placeholder="Material name or movement notes"
            :disabled="loading"
            @keyup.enter="applyFilters"
          />

          <button
            v-if="searchValue"
            type="button"
            title="Clear search"
            aria-label="Clear movement search"
            :disabled="loading"
            @click="clearSearch"
          >
            <i
              class="bi bi-x-lg"
              aria-hidden="true"
            ></i>
          </button>
        </div>
      </div>


      <!-- Material -->

      <div class="movement-filter-group">
        <label for="movement-material">
          Raw Material
        </label>

        <select
          id="movement-material"
          v-model="materialValue"
          :disabled="loading"
        >
          <option value="">
            All Raw Materials
          </option>

          <option
            v-if="hasUnavailableSelectedMaterial"
            :value="selectedMaterialId"
          >
            Material #{{ selectedMaterialId }}
            — Archived / unavailable
          </option>

          <option
            v-for="material in materialOptions"
            :key="String(material.id)"
            :value="String(material.id)"
          >
            {{ materialOptionLabel(material) }}
          </option>
        </select>
      </div>


      <!-- Movement Type -->

      <div class="movement-filter-group">
        <label for="movement-type">
          Movement Type
        </label>

        <select
          id="movement-type"
          v-model="movementTypeValue"
          :disabled="loading"
        >
          <option value="">
            All Movement Types
          </option>

          <option
            v-for="movementType in movementTypes"
            :key="movementType.value"
            :value="movementType.value"
          >
            {{ movementType.label }}
          </option>
        </select>
      </div>


      <!-- From Date -->

      <div class="movement-filter-group">
        <label for="movement-date-from">
          Date From
        </label>

        <input
          id="movement-date-from"
          v-model="dateFromValue"
          type="date"
          :max="dateToValue || undefined"
          :disabled="loading"
        />
      </div>


      <!-- To Date -->

      <div class="movement-filter-group">
        <label for="movement-date-to">
          Date To
        </label>

        <input
          id="movement-date-to"
          v-model="dateToValue"
          type="date"
          :min="dateFromValue || undefined"
          :disabled="loading"
        />
      </div>


      <!-- Rows -->

      <div class="movement-filter-group">
        <label for="movement-per-page">
          Rows Per Page
        </label>

        <select
          id="movement-per-page"
          v-model.number="perPageValue"
          :disabled="loading"
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


    <!-- Actions -->

    <div class="movement-filter-actions">
      <button
        type="button"
        class="movement-clear-button"
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
        class="movement-apply-button"
        :disabled="loading"
        :aria-busy="loading"
        @click="applyFilters"
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
            : 'Apply Filters'
        }}
      </button>
    </div>
  </section>
</template>


<script setup>
import {
  computed,
  ref,
} from 'vue'


/*
|--------------------------------------------------------------------------
| Props / Emits
|--------------------------------------------------------------------------
*/


const props = defineProps({
  modelValue: {
    type: Object,
    required: true,
  },


  materials: {
    type: Array,
    default: () => [],
  },


  loading: {
    type: Boolean,
    default: false,
  },


  activeFilterCount: {
    type: Number,
    default: 0,
  },
})


const emit = defineEmits([
  'update:modelValue',
  'apply',
  'clear',
])


/*
|--------------------------------------------------------------------------
| Local State
|--------------------------------------------------------------------------
*/


const validationError =
  ref('')


/*
|--------------------------------------------------------------------------
| Warehouse Movement Types
|--------------------------------------------------------------------------
|
| This filter is used by the warehouse movement section, which always sends
| location=warehouse. Restaurant-only movement types are intentionally omitted.
|
*/


const movementTypes = [
  {
    value:
      'opening_stock',

    label:
      'Opening Stock',
  },

  {
    value:
      'purchase_receive',

    label:
      'Purchase Receive',
  },

  {
    value:
      'warehouse_adjustment_in',

    label:
      'Warehouse Adjustment In',
  },

  {
    value:
      'warehouse_adjustment_out',

    label:
      'Warehouse Adjustment Out',
  },

  {
    value:
      'transfer_out',

    label:
      'Warehouse Transfer Out',
  },
]


/*
|--------------------------------------------------------------------------
| Field Models
|--------------------------------------------------------------------------
*/


const searchValue =
  createFieldModel(
    'search',
    '',
  )


const materialValue =
  createFieldModel(
    'raw_material_id',
    '',
  )


const movementTypeValue =
  createFieldModel(
    'movement_type',
    '',
  )


const dateFromValue =
  createFieldModel(
    'date_from',
    '',
  )


const dateToValue =
  createFieldModel(
    'date_to',
    '',
  )


const perPageValue =
  createFieldModel(
    'per_page',
    10,
  )


/*
|--------------------------------------------------------------------------
| Normalized Display Data
|--------------------------------------------------------------------------
*/


const normalizedActiveFilterCount =
  computed(() => {
    const value =
      Number(
        props.activeFilterCount,
      )


    if (
      !Number.isFinite(value)
      ||
      value <= 0
    ) {
      return 0
    }


    return Math.floor(
      value,
    )
  })


const materialOptions = computed(() => {
  if (
    !Array.isArray(
      props.materials,
    )
  ) {
    return []
  }


  return props.materials.filter(
    (material) => {
      const id =
        material?.id


      return (
        id !== null
        &&
        id !== undefined
        &&
        id !== ''
      )
    },
  )
})


const selectedMaterialId = computed(() => {
  const value =
    materialValue.value


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
})


const hasUnavailableSelectedMaterial =
  computed(() => {
    if (!selectedMaterialId.value) {
      return false
    }


    return !materialOptions.value.some(
      (material) =>
        String(
          material.id,
        ) ===
        selectedMaterialId.value,
    )
  })


/*
|--------------------------------------------------------------------------
| Model Factory
|--------------------------------------------------------------------------
*/


function createFieldModel(
  field,
  fallback,
) {
  return computed({
    get() {
      return (
        props.modelValue?.[field]
        ??
        fallback
      )
    },


    set(value) {
      validationError.value =
        ''


      emit(
        'update:modelValue',
        {
          ...props.modelValue,

          [field]:
            value,
        },
      )
    },
  })
}


/*
|--------------------------------------------------------------------------
| Display Helpers
|--------------------------------------------------------------------------
*/


function materialOptionLabel(
  material,
) {
  const name =
    String(
      material?.material_name
      ??
      '',
    ).trim()
    ||
    `Material #${material?.id}`


  const unit =
    String(
      material?.base_unit
      ??
      material?.unit
      ??
      '',
    ).trim()


  return unit
    ? `${name} — ${unit}`
    : name
}


/*
|--------------------------------------------------------------------------
| Validation
|--------------------------------------------------------------------------
*/


function isValidDateValue(
  value,
) {
  if (!value) {
    return true
  }


  const normalized =
    String(
      value,
    )


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
| Actions
|--------------------------------------------------------------------------
*/


function applyFilters() {
  if (props.loading) {
    return
  }


  validationError.value =
    ''


  if (
    !isValidDateValue(
      dateFromValue.value,
    )
  ) {
    validationError.value =
      'Please select a valid Date From.'

    return
  }


  if (
    !isValidDateValue(
      dateToValue.value,
    )
  ) {
    validationError.value =
      'Please select a valid Date To.'

    return
  }


  if (
    dateFromValue.value
    &&
    dateToValue.value
    &&
    dateToValue.value
      <
      dateFromValue.value
  ) {
    validationError.value =
      'Date To cannot be earlier than Date From.'

    return
  }


  emit(
    'apply',
  )
}


function clearFilters() {
  if (props.loading) {
    return
  }


  validationError.value =
    ''


  emit(
    'clear',
  )
}


function clearSearch() {
  if (props.loading) {
    return
  }


  searchValue.value =
    ''
}
</script>
