<template>
  <section class="customer-filter-card">
    <!-- ==================================================
         Filter Fields
    =================================================== -->

    <div class="customer-filter-fields">
      <!-- Search -->

      <label class="customer-search-field">
        <span class="customer-filter-label">
          Search Customer
        </span>

        <span class="customer-search-input-wrapper">
          <i
            class="bi bi-search"
            aria-hidden="true"
          ></i>

          <input
            :value="search"
            type="search"
            placeholder="Search by name, phone or email..."
            aria-label="Search customers"
            :disabled="loading"
            @input="handleSearchInput"
            @keyup.enter="emitSearch"
          />

          <button
            v-if="search"
            type="button"
            class="customer-search-clear"
            aria-label="Clear customer search"
            :disabled="loading"
            @click="clearSearch"
          >
            <i
              class="bi bi-x-lg"
              aria-hidden="true"
            ></i>
          </button>
        </span>
      </label>

      <!-- Status Filter -->

      <label class="customer-filter-field">
        <span class="customer-filter-label">
          Status
        </span>

        <select
          :value="status"
          :disabled="loading"
          aria-label="Filter customers by status"
          @change="handleStatusChange"
        >
          <option
            v-for="option in statusOptions"
            :key="option.value"
            :value="option.value"
          >
            {{ option.label }}
          </option>
        </select>
      </label>

      <!-- Sort Filter -->

      <label class="customer-filter-field">
        <span class="customer-filter-label">
          Sort By
        </span>

        <select
          :value="sort"
          :disabled="loading"
          aria-label="Sort customer list"
          @change="handleSortChange"
        >
          <option
            v-for="option in sortOptions"
            :key="option.value"
            :value="option.value"
          >
            {{ option.label }}
          </option>
        </select>
      </label>
    </div>

    <!-- ==================================================
         Filter Actions
    =================================================== -->

    <div class="customer-filter-actions-row">
      <div class="customer-filter-active-text">
        <i
          class="bi bi-funnel"
          aria-hidden="true"
        ></i>

        <span>
          {{
            hasActiveFilters
              ? "Custom filters selected"
              : "Showing default customer list"
          }}
        </span>
      </div>

      <div class="customer-filter-actions">
        <button
          type="button"
          class="customer-filter-button customer-clear-button"
          :disabled="
            loading ||
            !hasActiveFilters
          "
          @click="clearFilters"
        >
          <i
            class="bi bi-arrow-counterclockwise"
            aria-hidden="true"
          ></i>

          <span>
            Clear Filters
          </span>
        </button>

        <button
          type="button"
          class="customer-filter-button customer-apply-button"
          :disabled="loading"
          :aria-busy="loading"
          @click="emitSearch"
        >
          <span
            v-if="loading"
            class="spinner-border spinner-border-sm"
            aria-hidden="true"
          ></span>

          <i
            v-else
            class="bi bi-search"
            aria-hidden="true"
          ></i>

          <span>
            {{
              loading
                ? "Filtering..."
                : "Apply Filters"
            }}
          </span>
        </button>
      </div>
    </div>
  </section>
</template>

<script setup>
import {
  computed,
} from 'vue'

/*
|--------------------------------------------------------------------------
| Component Props
|--------------------------------------------------------------------------
*/

const props = defineProps({
  search: {
    type: String,
    default: '',
  },

  status: {
    type: String,
    default: 'all',
  },

  sort: {
    type: String,
    default: 'latest',
  },

  statusOptions: {
    type: Array,

    default: () => [
      {
        value: 'all',
        label: 'All Customers',
      },

      {
        value: 'active',
        label: 'Active',
      },

      {
        value: 'inactive',
        label: 'Inactive',
      },
    ],
  },

  sortOptions: {
    type: Array,

    default: () => [
      {
        value: 'latest',
        label: 'Newest First',
      },

      {
        value: 'oldest',
        label: 'Oldest First',
      },

      {
        value: 'name_asc',
        label: 'Name A–Z',
      },

      {
        value: 'name_desc',
        label: 'Name Z–A',
      },

      {
        value: 'visits_high',
        label: 'Most Visits',
      },

      {
        value: 'visits_low',
        label: 'Fewest Visits',
      },

      {
        value: 'spend_high',
        label: 'Highest Spend',
      },

      {
        value: 'spend_low',
        label: 'Lowest Spend',
      },

      {
        value: 'last_visit_latest',
        label: 'Latest Visit',
      },

      {
        value: 'last_visit_oldest',
        label: 'Oldest Visit',
      },
    ],
  },

  loading: {
    type: Boolean,
    default: false,
  },
})

/*
|--------------------------------------------------------------------------
| Component Events
|--------------------------------------------------------------------------
*/

const emit = defineEmits([
  'update:search',
  'update:status',
  'update:sort',
  'apply',
  'clear',
])

/*
|--------------------------------------------------------------------------
| Active Filter State
|--------------------------------------------------------------------------
*/

const hasActiveFilters =
  computed(() => {
    return (
      props.search.trim() !== '' ||
      props.status !== 'all' ||
      props.sort !== 'latest'
    )
  })

/*
|--------------------------------------------------------------------------
| Handle Search Input
|--------------------------------------------------------------------------
*/

function handleSearchInput(
  event,
) {
  emit(
    'update:search',
    event?.target?.value || '',
  )
}

/*
|--------------------------------------------------------------------------
| Apply Filters
|--------------------------------------------------------------------------
*/

function emitSearch() {
  if (props.loading) {
    return
  }

  emit('apply')
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

  emit(
    'update:search',
    '',
  )

  emit('apply')
}

/*
|--------------------------------------------------------------------------
| Handle Status Change
|--------------------------------------------------------------------------
*/

function handleStatusChange(
  event,
) {
  emit(
    'update:status',
    event?.target?.value ||
      'all',
  )
}

/*
|--------------------------------------------------------------------------
| Handle Sort Change
|--------------------------------------------------------------------------
*/

function handleSortChange(
  event,
) {
  emit(
    'update:sort',
    event?.target?.value ||
      'latest',
  )
}

/*
|--------------------------------------------------------------------------
| Clear All Filters
|--------------------------------------------------------------------------
*/

function clearFilters() {
  if (
    props.loading ||
    !hasActiveFilters.value
  ) {
    return
  }

  emit(
    'update:search',
    '',
  )

  emit(
    'update:status',
    'all',
  )

  emit(
    'update:sort',
    'latest',
  )

  emit('clear')
}
</script>