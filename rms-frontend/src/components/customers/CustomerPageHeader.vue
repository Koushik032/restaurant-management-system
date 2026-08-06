<template>
  <header class="customer-page-header">
    <!-- ==================================================
         Page Heading
    =================================================== -->

    <div class="customer-page-heading">
      <p class="customer-page-eyebrow">
        Guest Management
      </p>

      <h1>
        Customer Management
      </h1>

      <p>
        Manage customer profiles, visits, spending
        and account activity.
      </p>
    </div>

    <!-- ==================================================
         Header Actions
    =================================================== -->

    <div class="customer-header-actions">
      <!-- Refresh Button -->

      <button
        type="button"
        class="customer-header-button customer-refresh-button"
        :disabled="loading"
        :aria-busy="loading"
        @click="handleRefresh"
      >
        <span
          v-if="loading"
          class="spinner-border spinner-border-sm"
          aria-hidden="true"
        ></span>

        <i
          v-else
          class="bi bi-arrow-clockwise"
          aria-hidden="true"
        ></i>

        <span>
          {{
            loading
              ? "Refreshing..."
              : "Refresh"
          }}
        </span>
      </button>

      <!-- Add Customer Button -->

      <button
        type="button"
        class="customer-header-button customer-add-button"
        :disabled="loading"
        @click="handleAddCustomer"
      >
        <i
          class="bi bi-person-plus"
          aria-hidden="true"
        ></i>

        <span>
          Add Customer
        </span>
      </button>
    </div>
  </header>
</template>

<script setup>
/*
|--------------------------------------------------------------------------
| Component Props
|--------------------------------------------------------------------------
*/

const props = defineProps({
  /*
  |--------------------------------------------------------------------------
  | Global Loading State
  |--------------------------------------------------------------------------
  */

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
  'refresh',
  'add',
])

/*
|--------------------------------------------------------------------------
| Handle Refresh
|--------------------------------------------------------------------------
*/

function handleRefresh() {
  if (props.loading) {
    return
  }

  emit('refresh')
}

/*
|--------------------------------------------------------------------------
| Handle Add Customer
|--------------------------------------------------------------------------
*/

function handleAddCustomer() {
  if (props.loading) {
    return
  }

  emit('add')
}
</script>