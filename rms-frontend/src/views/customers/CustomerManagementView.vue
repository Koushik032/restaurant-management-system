<template>
  <section class="customer-management-page">
    <!-- ==================================================
         Customer Page Header
    =================================================== -->

    <CustomerPageHeader
      :loading="isAnyLoading"
      @refresh="refreshCustomerPage"
      @add="handleAddCustomer"
    />

    <!-- ==================================================
         Global Success Message
    =================================================== -->

    <div
      v-if="successMessage"
      class="customer-alert customer-success-alert"
      role="status"
      aria-live="polite"
    >
      <span
        class="customer-alert-icon"
        aria-hidden="true"
      >
        <i class="bi bi-check-circle-fill"></i>
      </span>

      <div class="customer-alert-content">
        <strong>
          Success
        </strong>

        <p>
          {{ successMessage }}
        </p>
      </div>

      <button
        type="button"
        class="customer-alert-close"
        aria-label="Close success message"
        @click="successMessage = ''"
      >
        <i
          class="bi bi-x-lg"
          aria-hidden="true"
        ></i>
      </button>
    </div>

    <!-- ==================================================
         Global Error Message
    =================================================== -->

    <div
      v-if="globalError"
      class="customer-alert customer-error-alert"
      role="alert"
      aria-live="assertive"
    >
      <span
        class="customer-alert-icon"
        aria-hidden="true"
      >
        <i class="bi bi-exclamation-triangle-fill"></i>
      </span>

      <div class="customer-alert-content">
        <strong>
          Unable to process customer request
        </strong>

        <p>
          {{ globalError }}
        </p>
      </div>

      <button
        type="button"
        class="customer-alert-close"
        aria-label="Close error message"
        @click="globalError = ''"
      >
        <i
          class="bi bi-x-lg"
          aria-hidden="true"
        ></i>
      </button>
    </div>

    <!-- ==================================================
         Customer Summary Cards
    =================================================== -->

    <CustomerSummaryCards
      :summary="summary"
      :loading="summaryLoading"
    />

    <!-- ==================================================
         Customer Filters
    =================================================== -->

    <CustomerFilters
      :search="filters.search"
      :status="filters.status"
      :sort="filters.sort"
      :status-options="statusOptions"
      :sort-options="sortOptions"
      :loading="customerLoading"
      @update:search="handleSearchChange"
      @update:status="handleStatusChange"
      @update:sort="handleSortChange"
      @apply="applyFilters"
      @clear="clearFilters"
    />

    <!-- ==================================================
         Customer Table
    =================================================== -->

    <CustomerTable
      :customers="customers"
      :meta="customerMeta"
      :loading="customerLoading"
      :error-message="customerError"
      :status-loading-id="statusLoadingId"
      :delete-loading-id="deleteLoadingId"
      @retry="loadCustomers"
      @page-change="changeCustomerPage"
      @view="handleViewCustomer"
      @edit="handleEditCustomer"
      @delete="handleDeleteCustomer"
      @toggle-status="toggleCustomerStatus"
    />
  </section>
</template>

<script setup>
/*
|--------------------------------------------------------------------------
| Vue Imports
|--------------------------------------------------------------------------
*/

import {
  computed,
  onMounted,
  reactive,
  ref,
} from 'vue'

/*
|--------------------------------------------------------------------------
| Vue Router
|--------------------------------------------------------------------------
*/

import {
  useRouter,
} from 'vue-router'

/*
|--------------------------------------------------------------------------
| Customer API Service
|--------------------------------------------------------------------------
*/

import customerService
  from '@/services/customerService'

/*
|--------------------------------------------------------------------------
| Customer Components
|--------------------------------------------------------------------------
*/

import CustomerPageHeader
  from '@/components/customers/CustomerPageHeader.vue'

import CustomerSummaryCards
  from '@/components/customers/CustomerSummaryCards.vue'

import CustomerFilters
  from '@/components/customers/CustomerFilters.vue'

import CustomerTable
  from '@/components/customers/CustomerTable.vue'

/*
|--------------------------------------------------------------------------
| Customer CSS
|--------------------------------------------------------------------------
*/

import '@/assets/css/customers/customer-overview.css'
import '@/assets/css/customers/customer-summary.css'
import '@/assets/css/customers/customer-filter.css'
import '@/assets/css/customers/customer-table.css'
import '@/assets/css/customers/customer-responsive.css'

/*
|--------------------------------------------------------------------------
| Router Instance
|--------------------------------------------------------------------------
*/

const router =
  useRouter()

/*
|--------------------------------------------------------------------------
| Customer Filters
|--------------------------------------------------------------------------
*/

const filters = reactive({
  search: '',
  status: 'all',
  sort: 'latest',
})

/*
|--------------------------------------------------------------------------
| Global Message State
|--------------------------------------------------------------------------
*/

const successMessage =
  ref('')

const globalError =
  ref('')

/*
|--------------------------------------------------------------------------
| Customer Summary State
|--------------------------------------------------------------------------
*/

const summaryLoading =
  ref(false)

const summary = reactive(
  createDefaultSummary(),
)

/*
|--------------------------------------------------------------------------
| Customer List State
|--------------------------------------------------------------------------
*/

const customerLoading =
  ref(false)

const customerError =
  ref('')

const customers =
  ref([])

const customerMeta = reactive(
  createDefaultMeta(),
)

/*
|--------------------------------------------------------------------------
| Customer Filter Options
|--------------------------------------------------------------------------
*/

const statusOptions =
  ref(
    createDefaultStatusOptions(),
  )

const sortOptions =
  ref(
    createDefaultSortOptions(),
  )

/*
|--------------------------------------------------------------------------
| Row Action Loading States
|--------------------------------------------------------------------------
*/

const statusLoadingId =
  ref(null)

const deleteLoadingId =
  ref(null)

/*
|--------------------------------------------------------------------------
| Combined Loading State
|--------------------------------------------------------------------------
*/

const isAnyLoading =
  computed(() => {
    return (
      summaryLoading.value ||
      customerLoading.value ||
      statusLoadingId.value !== null ||
      deleteLoadingId.value !== null
    )
  })

/*
|--------------------------------------------------------------------------
| Load Customer Summary
|--------------------------------------------------------------------------
*/

async function loadSummary() {
  summaryLoading.value = true

  try {
    const response =
      await customerService.getSummary()

    Object.assign(
      summary,
      response?.data ||
        createDefaultSummary(),
    )
  } catch (error) {
    Object.assign(
      summary,
      createDefaultSummary(),
    )

    globalError.value =
      customerService
        .getCustomerErrorMessage(
          error,
          'Unable to load customer summary.',
        )
  } finally {
    summaryLoading.value = false
  }
}

/*
|--------------------------------------------------------------------------
| Load Customer List
|--------------------------------------------------------------------------
*/

async function loadCustomers() {
  customerLoading.value = true
  customerError.value = ''

  try {
    const response =
      await customerService.getCustomers({
        search:
          filters.search,

        status:
          filters.status,

        sort:
          filters.sort,

        page:
          customerMeta.current_page,

        perPage:
          customerMeta.per_page,
      })

    customers.value =
      Array.isArray(
        response?.data,
      )
        ? response.data
        : []

    Object.assign(
      customerMeta,
      response?.meta ||
        createDefaultMeta(),
    )

    const responseStatuses =
      response?.filters
        ?.statuses

    if (
      Array.isArray(
        responseStatuses,
      ) &&
      responseStatuses.length > 0
    ) {
      statusOptions.value =
        responseStatuses
    }

    const responseSortOptions =
      response?.filters
        ?.sort_options

    if (
      Array.isArray(
        responseSortOptions,
      ) &&
      responseSortOptions.length > 0
    ) {
      sortOptions.value =
        responseSortOptions
    }
  } catch (error) {
    customers.value = []

    Object.assign(
      customerMeta,
      createDefaultMeta(),
    )

    customerError.value =
      customerService
        .getCustomerErrorMessage(
          error,
          'Unable to load customer list.',
        )
  } finally {
    customerLoading.value = false
  }
}

/*
|--------------------------------------------------------------------------
| Refresh Customer Page
|--------------------------------------------------------------------------
*/

async function refreshCustomerPage() {
  if (isAnyLoading.value) {
    return
  }

  globalError.value = ''
  successMessage.value = ''

  customerMeta.current_page = 1

  await Promise.allSettled([
    loadSummary(),
    loadCustomers(),
  ])
}

/*
|--------------------------------------------------------------------------
| Apply Customer Filters
|--------------------------------------------------------------------------
*/

async function applyFilters() {
  if (customerLoading.value) {
    return
  }

  customerMeta.current_page = 1

  await loadCustomers()
}

/*
|--------------------------------------------------------------------------
| Clear Customer Filters
|--------------------------------------------------------------------------
*/

async function clearFilters() {
  filters.search = ''
  filters.status = 'all'
  filters.sort = 'latest'

  customerMeta.current_page = 1

  await loadCustomers()
}

/*
|--------------------------------------------------------------------------
| Handle Search Change
|--------------------------------------------------------------------------
*/

function handleSearchChange(
  value,
) {
  filters.search =
    String(
      value || '',
    )
}

/*
|--------------------------------------------------------------------------
| Handle Status Change
|--------------------------------------------------------------------------
*/

function handleStatusChange(
  value,
) {
  filters.status =
    String(
      value || 'all',
    )
}

/*
|--------------------------------------------------------------------------
| Handle Sort Change
|--------------------------------------------------------------------------
*/

function handleSortChange(
  value,
) {
  filters.sort =
    String(
      value || 'latest',
    )
}

/*
|--------------------------------------------------------------------------
| Change Customer Page
|--------------------------------------------------------------------------
*/

async function changeCustomerPage(
  page,
) {
  if (
    !isValidPage(
      page,
      customerMeta,
    )
  ) {
    return
  }

  customerMeta.current_page =
    Number(page)

  await loadCustomers()
}

/*
|--------------------------------------------------------------------------
| Toggle Customer Status
|--------------------------------------------------------------------------
*/

async function toggleCustomerStatus(
  customer,
) {
  const customerId =
    Number(
      customer?.id,
    )

  if (
    !Number.isInteger(
      customerId,
    ) ||
    customerId <= 0 ||
    statusLoadingId.value !== null
  ) {
    return
  }

  statusLoadingId.value =
    customerId

  globalError.value = ''
  successMessage.value = ''

  try {
    const response =
      await customerService.toggleStatus(
        customerId,
      )

    const updatedCustomer =
      response?.data

    if (updatedCustomer) {
      replaceCustomerInList(
        updatedCustomer,
      )
    }

    successMessage.value =
      response?.message ||
      'Customer status updated successfully.'

    await loadSummary()
  } catch (error) {
    globalError.value =
      customerService
        .getCustomerErrorMessage(
          error,
          'Unable to update customer status.',
        )
  } finally {
    statusLoadingId.value = null
  }
}

/*
|--------------------------------------------------------------------------
| View Customer
|--------------------------------------------------------------------------
*/

/*
|--------------------------------------------------------------------------
| View Customer
|--------------------------------------------------------------------------
*/

function handleViewCustomer(
  customer,
) {

  const customerId =
    Number(
      customer?.id,
    )


  if (
    !Number.isInteger(
      customerId,
    ) ||
    customerId <= 0
  ) {

    globalError.value =
      'A valid customer ID is required.'

    return

  }


  router.push({

    name:
      'customer-details',

    params: {

      id:
        customerId,

    },

  })

}

/*
|--------------------------------------------------------------------------
| Edit Customer
|--------------------------------------------------------------------------
*/

function handleEditCustomer(
  customer,
) {
  const customerId =
    Number(
      customer?.id,
    )

  if (
    !Number.isInteger(
      customerId,
    ) ||
    customerId <= 0
  ) {
    globalError.value =
      'A valid customer ID is required.'

    return
  }

  router.push({
    name: 'customer-edit',

    params: {
      id: customerId,
    },
  })
}

/*
|--------------------------------------------------------------------------
| Add Customer
|--------------------------------------------------------------------------
*/

function handleAddCustomer() {
  successMessage.value =
    'Add Customer will be added after the list page is verified.'
}

/*
|--------------------------------------------------------------------------
| Delete Customer
|--------------------------------------------------------------------------
*/

/*
|--------------------------------------------------------------------------
| Delete Customer
|--------------------------------------------------------------------------
|
| Customer permanently delete হবে না।
| Database-এর deleted_at column update হবে।
|
*/

async function handleDeleteCustomer(
  customer,
) {
  const customerId =
    Number(
      customer?.id,
    )

  if (
    !Number.isInteger(
      customerId,
    ) ||
    customerId <= 0 ||
    deleteLoadingId.value !== null
  ) {
    return
  }

  const customerName =
    String(
      customer?.name ||
      'this customer',
    ).trim()

  const confirmed =
    window.confirm(
      `Are you sure you want to delete ${customerName}?\n\nThe customer will be removed from the website, but the record will remain safely stored in the database.`,
    )

  if (!confirmed) {
    return
  }

  deleteLoadingId.value =
    customerId

  globalError.value = ''
  successMessage.value = ''

  try {
    const response =
      await customerService
        .deleteCustomer(
          customerId,
        )

    /*
    |--------------------------------------------------------------------------
    | Remove Deleted Customer from Current List
    |--------------------------------------------------------------------------
    */

    customers.value =
      customers.value.filter(
        (currentCustomer) =>
          Number(
            currentCustomer?.id,
          ) !== customerId,
      )

    /*
    |--------------------------------------------------------------------------
    | Reload Current Page
    |--------------------------------------------------------------------------
    |
    | যদি current page-এর শেষ customer delete হয়, আগের page-এ যাবে।
    |
    */

    if (
      customers.value.length === 0 &&
      customerMeta.current_page > 1
    ) {
      customerMeta.current_page -= 1
    }

    await Promise.allSettled([
      loadCustomers(),
      loadSummary(),
    ])

    successMessage.value =
      response?.message ||
      `${customerName} deleted successfully.`
  } catch (error) {
    globalError.value =
      customerService
        .getCustomerErrorMessage(
          error,
          'Unable to delete customer.',
        )
  } finally {
    deleteLoadingId.value = null
  }
}

/*
|--------------------------------------------------------------------------
| Replace Customer in Current List
|--------------------------------------------------------------------------
*/

function replaceCustomerInList(
  updatedCustomer,
) {
  const customerId =
    Number(
      updatedCustomer?.id,
    )

  const customerIndex =
    customers.value.findIndex(
      (customer) =>
        Number(
          customer?.id,
        ) === customerId,
    )

  if (customerIndex < 0) {
    return
  }

  customers.value.splice(
    customerIndex,
    1,
    {
      ...customers.value[
        customerIndex
      ],

      ...updatedCustomer,
    },
  )
}

/*
|--------------------------------------------------------------------------
| Validate Pagination Page
|--------------------------------------------------------------------------
*/

function isValidPage(
  page,
  meta,
) {
  const resolvedPage =
    Number(page)

  const currentPage =
    Number(
      meta?.current_page ||
      1,
    )

  const lastPage =
    Number(
      meta?.last_page ||
      1,
    )

  return (
    Number.isInteger(
      resolvedPage,
    ) &&
    resolvedPage >= 1 &&
    resolvedPage <= lastPage &&
    resolvedPage !== currentPage
  )
}

/*
|--------------------------------------------------------------------------
| Initial Customer Page Load
|--------------------------------------------------------------------------
*/

async function loadInitialCustomerPage() {
  await Promise.allSettled([
    loadSummary(),
    loadCustomers(),
  ])
}

/*
|--------------------------------------------------------------------------
| Default Customer Summary
|--------------------------------------------------------------------------
*/

function createDefaultSummary() {
  return {
    total_customers: 0,
    active_customers: 0,
    inactive_customers: 0,
    new_customers_this_month: 0,
    total_visits: 0,
    lifetime_spend: 0,
    lifetime_spend_formatted:
      '৳ 0.00',
  }
}

/*
|--------------------------------------------------------------------------
| Default Pagination Metadata
|--------------------------------------------------------------------------
*/

function createDefaultMeta() {
  return {
    current_page: 1,
    last_page: 1,
    per_page: 10,
    total: 0,
    from: null,
    to: null,
  }
}

/*
|--------------------------------------------------------------------------
| Default Status Options
|--------------------------------------------------------------------------
*/

function createDefaultStatusOptions() {
  return [
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
  ]
}

/*
|--------------------------------------------------------------------------
| Default Sort Options
|--------------------------------------------------------------------------
*/

function createDefaultSortOptions() {
  return [
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
  ]
}

/*
|--------------------------------------------------------------------------
| Mounted Lifecycle
|--------------------------------------------------------------------------
*/

onMounted(() => {
  loadInitialCustomerPage()
})
</script>