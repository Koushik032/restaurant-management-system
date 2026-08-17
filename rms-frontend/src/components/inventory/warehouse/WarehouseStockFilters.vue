<template>
  <section
    class="warehouse-filter-card"
    :aria-busy="loading"
  >
    <!-- Header -->

    <div class="warehouse-filter-header">
      <div>
        <h3>
          Filter Warehouse Stock
        </h3>

        <p>
          Search materials and filter by category,
          unit or stock status.
        </p>
      </div>

      <span
        v-if="normalizedActiveFilterCount > 0"
        class="warehouse-active-filter-count"
        aria-live="polite"
      >
        {{ normalizedActiveFilterCount }}
        active
      </span>
    </div>


    <div class="warehouse-filter-grid">
      <!-- Search -->

      <div
        class="warehouse-filter-group warehouse-search-group"
      >
        <label for="warehouse-search">
          Search Material
        </label>

        <div class="warehouse-search-input">
          <i
            class="bi bi-search"
            aria-hidden="true"
          ></i>

          <input
            id="warehouse-search"
            v-model.trim="searchValue"
            type="text"
            maxlength="180"
            autocomplete="off"
            placeholder="Material name or category"
            :disabled="loading"
            @keyup.enter="applyFilters"
          />

          <button
            v-if="searchValue"
            type="button"
            title="Clear search"
            aria-label="Clear warehouse search"
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


      <!-- Category -->

      <div class="warehouse-filter-group">
        <label for="warehouse-category">
          Category
        </label>

        <select
          id="warehouse-category"
          v-model="categoryValue"
          :disabled="loading"
        >
          <option value="">
            All Categories
          </option>

          <option
            v-for="category in categoryOptions"
            :key="category"
            :value="category"
          >
            {{ category }}
          </option>
        </select>
      </div>


      <!-- Unit -->

      <div class="warehouse-filter-group">
        <label for="warehouse-unit">
          Unit
        </label>

        <select
          id="warehouse-unit"
          v-model="unitValue"
          :disabled="loading"
        >
          <option value="">
            All Units
          </option>

          <option
            v-for="unit in unitOptions"
            :key="unit.value"
            :value="unit.value"
          >
            {{ unit.label }}
          </option>
        </select>
      </div>


      <!-- Stock Status -->

      <div class="warehouse-filter-group">
        <label for="warehouse-status">
          Stock Status
        </label>

        <select
          id="warehouse-status"
          v-model="statusValue"
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


      <!-- Material Status -->

      <div class="warehouse-filter-group">
        <label for="warehouse-active">
          Material Status
        </label>

        <select
          id="warehouse-active"
          v-model="activeValue"
          :disabled="loading"
        >
          <option value="">
            All Materials
          </option>

          <option value="1">
            Active
          </option>

          <option value="0">
            Inactive
          </option>
        </select>
      </div>


      <!-- Sort -->

      <div class="warehouse-filter-group">
        <label for="warehouse-sort">
          Sort By
        </label>

        <select
          id="warehouse-sort"
          v-model="sortByValue"
          :disabled="loading"
        >
          <option value="created_at">
            Created Date
          </option>

          <option value="updated_at">
            Last Updated
          </option>

          <option value="quantity">
            Quantity
          </option>

          <option value="average_unit_cost">
            Average Cost
          </option>

          <option value="last_received_at">
            Last Received
          </option>
        </select>
      </div>


      <!-- Sort Direction -->

      <div class="warehouse-filter-group">
        <label for="warehouse-sort-direction">
          Sort Direction
        </label>

        <select
          id="warehouse-sort-direction"
          v-model="sortDirectionValue"
          :disabled="loading"
        >
          <option value="desc">
            Descending
          </option>

          <option value="asc">
            Ascending
          </option>
        </select>
      </div>


      <!-- Per Page -->

      <div class="warehouse-filter-group">
        <label for="warehouse-per-page">
          Rows Per Page
        </label>

        <select
          id="warehouse-per-page"
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

    <div class="warehouse-filter-actions">
      <button
        type="button"
        class="warehouse-clear-filter-button"
        :disabled="loading"
        @click="clearFilters"
      >
        <i
          class="bi bi-arrow-counterclockwise"
          aria-hidden="true"
        ></i>

        <span>
          Clear
        </span>
      </button>


      <button
        type="button"
        class="warehouse-apply-filter-button"
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

        <span>
          {{
            loading
              ? 'Applying...'
              : 'Apply Filters'
          }}
        </span>
      </button>
    </div>
  </section>
</template>


<script setup>
import {
  computed,
} from 'vue'


/*
|--------------------------------------------------------------------------
| Props
|--------------------------------------------------------------------------
*/


const props = defineProps({
  modelValue: {
    type: Object,
    required: true,
  },


  options: {
    type: Object,

    default: () => ({
      categories: [],
      units: [],
      warehouse_statuses: [],
    }),
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


/*
|--------------------------------------------------------------------------
| Emits
|--------------------------------------------------------------------------
*/


const emit = defineEmits([
  'update:modelValue',
  'apply',
  'clear',
])


/*
|--------------------------------------------------------------------------
| Filter Models
|--------------------------------------------------------------------------
*/


const searchValue =
  createFieldModel(
    'search',
    '',
  )


const categoryValue =
  createFieldModel(
    'category',
    '',
  )


const unitValue =
  createFieldModel(
    'base_unit',
    '',
  )


const statusValue =
  createFieldModel(
    'status',
    '',
  )


const activeValue =
  createFieldModel(
    'is_active',
    '',
  )


const sortByValue =
  createFieldModel(
    'sort_by',
    'created_at',
  )


const sortDirectionValue =
  createFieldModel(
    'sort_direction',
    'desc',
  )


const perPageValue =
  createFieldModel(
    'per_page',
    10,
  )


/*
|--------------------------------------------------------------------------
| Options
|--------------------------------------------------------------------------
*/


const categoryOptions = computed(() => {
  return Array.isArray(
    props.options?.categories,
  )
    ? props.options.categories
    : []
})


const unitOptions = computed(() => {
  return Array.isArray(
    props.options?.units,
  )
    ? props.options.units
    : []
})


const statusOptions = computed(() => {
  return Array.isArray(
    props.options
      ?.warehouse_statuses,
  )
    ? props.options
        .warehouse_statuses
    : []
})


/*
|--------------------------------------------------------------------------
| Active Filter Count
|--------------------------------------------------------------------------
*/


const normalizedActiveFilterCount =
  computed(() => {
    const count =
      Number(
        props.activeFilterCount,
      )


    if (
      !Number.isFinite(count)
      ||
      count <= 0
    ) {
      return 0
    }


    return Math.floor(
      count,
    )
  })


/*
|--------------------------------------------------------------------------
| Field Model Factory
|--------------------------------------------------------------------------
*/


function createFieldModel(
  field,
  fallbackValue,
) {
  return computed({
    get() {
      return (
        props.modelValue?.[field]
        ??
        fallbackValue
      )
    },


    set(value) {
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
| Apply
|--------------------------------------------------------------------------
*/


function applyFilters() {
  if (props.loading) {
    return
  }


  emit(
    'apply',
  )
}


/*
|--------------------------------------------------------------------------
| Clear
|--------------------------------------------------------------------------
*/


function clearFilters() {
  if (props.loading) {
    return
  }


  emit(
    'clear',
  )
}


/*
|--------------------------------------------------------------------------
| Clear Search
|--------------------------------------------------------------------------
*/


function clearSearch() {
  if (props.loading) {
    return
  }


  searchValue.value =
    ''
}
</script>