/*
|--------------------------------------------------------------------------
| API Client
|--------------------------------------------------------------------------
*/

import api from '@/services/api'

/*
|--------------------------------------------------------------------------
| Customer API Base Path
|--------------------------------------------------------------------------
*/

const CUSTOMER_BASE_URL =
  '/customers'

/*
|--------------------------------------------------------------------------
| Build Customer Filters
|--------------------------------------------------------------------------
*/

function buildCustomerFilters(
  filters = {},
) {
  return cleanRequestParams({
    search:
      filters.search,

    status:
      filters.status,

    sort:
      filters.sort,

    page:
      filters.page,

    per_page:
      filters.perPage ??
      filters.per_page,
  })
}
/*
|--------------------------------------------------------------------------
| Normalise Customer Details Summary
|--------------------------------------------------------------------------
*/

function normaliseCustomerDetailsSummary(
  summary = {},
) {
  const totalOrderAmount =
    toNumber(
      summary
        ?.total_order_amount,
    )

  const totalPaidAmount =
    toNumber(
      summary
        ?.total_paid_amount,
    )

  const totalDueAmount =
    toNumber(
      summary
        ?.total_due_amount,
    )

  return {
    visit_count:
      toNonNegativeInteger(
        summary?.visit_count,
      ),

    total_order_amount:
      totalOrderAmount,

    total_order_amount_formatted:
      resolveFormattedMoney(
        summary
          ?.total_order_amount_formatted,
        totalOrderAmount,
      ),

    total_paid_amount:
      totalPaidAmount,

    total_paid_amount_formatted:
      resolveFormattedMoney(
        summary
          ?.total_paid_amount_formatted,
        totalPaidAmount,
      ),

    total_due_amount:
      totalDueAmount,

    total_due_amount_formatted:
      resolveFormattedMoney(
        summary
          ?.total_due_amount_formatted,
        totalDueAmount,
      ),

    first_visit_at:
      normaliseString(
        summary?.first_visit_at,
      ),

    first_visit_label:
      normaliseString(
        summary
          ?.first_visit_label,
      ) ||
      'Never',

    last_visit_at:
      normaliseString(
        summary?.last_visit_at,
      ),

    last_visit_label:
      normaliseString(
        summary
          ?.last_visit_label,
      ) ||
      'Never',
  }
}

/*
|--------------------------------------------------------------------------
| Normalise Customer Orders
|--------------------------------------------------------------------------
*/

function normaliseCustomerOrders(
  orders,
) {
  if (
    !Array.isArray(
      orders,
    )
  ) {
    return []
  }

  return orders.map(
    normaliseCustomerOrder,
  )
}

/*
|--------------------------------------------------------------------------
| Normalise One Customer Order
|--------------------------------------------------------------------------
*/

function normaliseCustomerOrder(
  order = {},
) {
  const totalAmount =
    toNumber(
      order?.total_amount,
    )

  const paidAmount =
    toNumber(
      order?.paid_amount,
    )

  const dueAmount =
    toNumber(
      order?.due_amount,
    )

  return {
    id:
      toPositiveInteger(
        order?.id,
        0,
      ),

    order_number:
      normaliseString(
        order?.order_number,
      ),

    created_at:
      normaliseString(
        order?.created_at,
      ),

    visit_date:
      normaliseString(
        order?.visit_date,
      ),

    visit_time:
      normaliseString(
        order?.visit_time,
      ),

    visit_day:
      normaliseString(
        order?.visit_day,
      ),

    visit_label:
      normaliseString(
        order?.visit_label,
      ) ||
      'Not Available',

    total_amount:
      totalAmount,

    total_amount_formatted:
      resolveFormattedMoney(
        order
          ?.total_amount_formatted,
        totalAmount,
      ),

    paid_amount:
      paidAmount,

    paid_amount_formatted:
      resolveFormattedMoney(
        order
          ?.paid_amount_formatted,
        paidAmount,
      ),

    due_amount:
      dueAmount,

    due_amount_formatted:
      resolveFormattedMoney(
        order
          ?.due_amount_formatted,
        dueAmount,
      ),

    items_count:
      toNonNegativeInteger(
        order?.items_count,
      ),

    items:
      normaliseCustomerOrderItems(
        order?.items,
      ),
  }
}

/*
|--------------------------------------------------------------------------
| Normalise Customer Order Items
|--------------------------------------------------------------------------
*/

function normaliseCustomerOrderItems(
  items,
) {
  if (
    !Array.isArray(
      items,
    )
  ) {
    return []
  }

  return items.map(
    (item = {}) => {
      const unitPrice =
        toNumber(
          item?.unit_price,
        )

      const lineTotal =
        toNumber(
          item?.line_total,
        )

      return {
        id:
          toPositiveInteger(
            item?.id,
            0,
          ),

        item_name:
          normaliseString(
            item?.item_name,
          ) ||
          'Unnamed Item',

        variant_name:
          normaliseString(
            item?.variant_name,
          ),

        quantity:
          Math.max(
            1,
            toNonNegativeInteger(
              item?.quantity,
              1,
            ),
          ),

        unit_price:
          unitPrice,

        unit_price_formatted:
          resolveFormattedMoney(
            item
              ?.unit_price_formatted,
            unitPrice,
          ),

        line_total:
          lineTotal,

        line_total_formatted:
          resolveFormattedMoney(
            item
              ?.line_total_formatted,
            lineTotal,
          ),

        addons:
          Array.isArray(
            item?.addons,
          )
            ? item.addons
            : [],
      }
    },
  )
}

/*
|--------------------------------------------------------------------------
| Customer Service
|--------------------------------------------------------------------------
*/

const customerService = {
  /*
  |--------------------------------------------------------------------------
  | Load Customer List
  |--------------------------------------------------------------------------
  |
  | GET /api/customers
  |
  */

  async getCustomers(
    filters = {},
  ) {
    const response =
      await api.get(
        CUSTOMER_BASE_URL,
        {
          params:
            buildCustomerFilters(
              filters,
            ),
        },
      )

    return {
      success:
        Boolean(
          response?.data
            ?.success,
        ),

      message:
        response?.data
          ?.message ||
        '',

      data:
        normaliseCustomerList(
          response?.data
            ?.data,
        ),

      meta:
        normalisePaginationMeta(
          response?.data
            ?.meta,
        ),

      filters:
        normaliseCustomerFilters(
          response?.data
            ?.filters,
        ),
    }
  },
  /*
|--------------------------------------------------------------------------
| Load Customer Details
|--------------------------------------------------------------------------
|
| GET /api/customers/{customer}?page=1&per_page=10
|
*/

async getCustomerDetails(
  customerId,
  params = {},
) {
  const id =
    resolveCustomerId(
      customerId,
    )

  const response =
    await api.get(
      `${CUSTOMER_BASE_URL}/${id}`,
      {
        params:
          cleanRequestParams({
            page:
              params.page ??
              1,

            per_page:
              params.per_page ??
              params.perPage ??
              10,
          }),
      },
    )

  /*
  |--------------------------------------------------------------------------
  | Laravel JsonResource Response
  |--------------------------------------------------------------------------
  |
  | CustomerDetailsResource direct return করলে response সাধারণত:
  |
  | {
  |   data: {
  |     customer: {},
  |     summary: {},
  |     orders: [],
  |     meta: {}
  |   }
  | }
  |
  | Controller response()->json() দিয়ে wrap করলে data সরাসরিও থাকতে পারে।
  |
  */

  const responseData =
    response?.data?.data ??
    response?.data ??
    {}

  return {
    success:
      Boolean(
        response?.data
          ?.success ??
        true,
      ),

    message:
      response?.data
        ?.message ||
      'Customer details loaded successfully.',

    data: {
      customer:
        normaliseCustomer(
          responseData
            ?.customer,
        ),

      summary:
        normaliseCustomerDetailsSummary(
          responseData
            ?.summary,
        ),

      orders:
        normaliseCustomerOrders(
          responseData
            ?.orders,
        ),

      meta:
        normalisePaginationMeta(
          responseData
            ?.meta,
        ),
    },
  }
},

  /*
  |--------------------------------------------------------------------------
  | Load Customer Summary
  |--------------------------------------------------------------------------
  |
  | GET /api/customers/summary
  |
  */

  async getSummary() {
    const response =
      await api.get(
        `${CUSTOMER_BASE_URL}/summary`,
      )

    return {
      success:
        Boolean(
          response?.data
            ?.success,
        ),

      message:
        response?.data
          ?.message ||
        '',

      data:
        normaliseCustomerSummary(
          response?.data
            ?.data,
        ),
    }
  },

  /*
  |--------------------------------------------------------------------------
  | Load Customer Details
  |--------------------------------------------------------------------------
  |
  | GET /api/customers/{customer}
  |
  */

  async getCustomer(
    customerId,
  ) {
    const id =
      resolveCustomerId(
        customerId,
      )

    const response =
      await api.get(
        `${CUSTOMER_BASE_URL}/${id}`,
      )

    return {
      success:
        Boolean(
          response?.data
            ?.success,
        ),

      message:
        response?.data
          ?.message ||
        '',

      data:
        response?.data
          ?.data ||
        null,
    }
  },

  /*
  |--------------------------------------------------------------------------
  | Create Customer
  |--------------------------------------------------------------------------
  |
  | POST /api/customers
  |
  */

  async createCustomer(
    payload,
  ) {
    const response =
      await api.post(
        CUSTOMER_BASE_URL,
        buildCustomerPayload(
          payload,
        ),
      )

    return {
      success:
        Boolean(
          response?.data
            ?.success,
        ),

      message:
        response?.data
          ?.message ||
        'Customer created successfully.',

      data:
        normaliseCustomer(
          response?.data
            ?.data,
        ),
    }
  },

  /*
  |--------------------------------------------------------------------------
  | Update Customer
  |--------------------------------------------------------------------------
  |
  | PUT /api/customers/{customer}
  |
  */

  async updateCustomer(
    customerId,
    payload,
  ) {
    const id =
      resolveCustomerId(
        customerId,
      )

    const response =
      await api.put(
        `${CUSTOMER_BASE_URL}/${id}`,
        buildCustomerPayload(
          payload,
        ),
      )

    return {
      success:
        Boolean(
          response?.data
            ?.success,
        ),

      message:
        response?.data
          ?.message ||
        'Customer updated successfully.',

      data:
        normaliseCustomer(
          response?.data
            ?.data,
        ),
    }
  },

  /*
  |--------------------------------------------------------------------------
  | Toggle Customer Status
  |--------------------------------------------------------------------------
  |
  | PATCH /api/customers/{customer}/status
  |
  */

  async toggleStatus(
    customerId,
  ) {
    const id =
      resolveCustomerId(
        customerId,
      )

    const response =
      await api.patch(
        `${CUSTOMER_BASE_URL}/${id}/status`,
      )

    return {
      success:
        Boolean(
          response?.data
            ?.success,
        ),

      message:
        response?.data
          ?.message ||
        'Customer status updated successfully.',

      data:
        normaliseCustomer(
          response?.data
            ?.data,
        ),
    }
  },

  /*
  |--------------------------------------------------------------------------
  | Delete Customer
  |--------------------------------------------------------------------------
  |
  | DELETE /api/customers/{customer}
  |
  */

  async deleteCustomer(
    customerId,
  ) {
    const id =
      resolveCustomerId(
        customerId,
      )

    const response =
      await api.delete(
        `${CUSTOMER_BASE_URL}/${id}`,
      )

    return {
      success:
        Boolean(
          response?.data
            ?.success,
        ),

      message:
        response?.data
          ?.message ||
        'Customer deleted successfully.',
    }
  },

  /*
  |--------------------------------------------------------------------------
  | Customer Search Suggestions
  |--------------------------------------------------------------------------
  |
  | GET /api/customers/search
  |
  */

  async searchCustomers(
    search,
    limit = 10,
  ) {
    const response =
      await api.get(
        `${CUSTOMER_BASE_URL}/search`,
        {
          params:
            cleanRequestParams({
              search,

              limit,
            }),
        },
      )

    return {
      success:
        Boolean(
          response?.data
            ?.success,
        ),

      message:
        response?.data
          ?.message ||
        '',

      data:
        normaliseCustomerList(
          response?.data
            ?.data,
        ),
    }
  },

  /*
  |--------------------------------------------------------------------------
  | Resolve Customer API Error
  |--------------------------------------------------------------------------
  */

  getCustomerErrorMessage(
    error,
    fallbackMessage =
      'Unable to process the customer request.',
  ) {
    const validationErrors =
      error?.response
        ?.data
        ?.errors

    if (
      validationErrors &&
      typeof validationErrors ===
        'object'
    ) {
      const firstMessage =
        Object.values(
          validationErrors,
        )
          .flat()
          .find(Boolean)

      if (firstMessage) {
        return String(
          firstMessage,
        )
      }
    }

    const responseMessage =
      error?.response
        ?.data
        ?.message

    if (responseMessage) {
      return String(
        responseMessage,
      )
    }

    if (error?.message) {
      return String(
        error.message,
      )
    }

    return fallbackMessage
  },

  /*
  |--------------------------------------------------------------------------
  | Resolve Validation Errors
  |--------------------------------------------------------------------------
  */

  getValidationErrors(
    error,
  ) {
    const errors =
      error?.response
        ?.data
        ?.errors

    if (
      !errors ||
      typeof errors !==
        'object'
    ) {
      return {}
    }

    return Object.entries(
      errors,
    ).reduce(
      (
        resolvedErrors,
        [field, messages],
      ) => {
        resolvedErrors[field] =
          Array.isArray(
            messages,
          )
            ? String(
                messages[0] ||
                '',
              )
            : String(
                messages ||
                '',
              )

        return resolvedErrors
      },
      {},
    )
  },
}

/*
|--------------------------------------------------------------------------
| Build Customer Payload
|--------------------------------------------------------------------------
*/

function buildCustomerPayload(
  payload = {},
) {
  return {
    name:
      normaliseString(
        payload.name,
      ),

    phone:
      normaliseString(
        payload.phone,
      ),

    email:
      normaliseNullableString(
        payload.email,
      ),

    is_active:
      resolveBoolean(
        payload.is_active,
        true,
      ),

    notes:
      normaliseNullableString(
        payload.notes,
      ),
  }
}

/*
|--------------------------------------------------------------------------
| Clean Request Parameters
|--------------------------------------------------------------------------
*/

function cleanRequestParams(
  params = {},
) {
  return Object.entries(
    params,
  ).reduce(
    (
      cleanedParams,
      [key, value],
    ) => {
      if (
        value === null ||
        value === undefined ||
        value === ''
      ) {
        return cleanedParams
      }

      cleanedParams[key] =
        value

      return cleanedParams
    },
    {},
  )
}

/*
|--------------------------------------------------------------------------
| Normalise Customer List
|--------------------------------------------------------------------------
*/

function normaliseCustomerList(
  customers,
) {
  if (
    !Array.isArray(
      customers,
    )
  ) {
    return []
  }

  return customers.map(
    normaliseCustomer,
  )
}

/*
|--------------------------------------------------------------------------
| Normalise Customer
|--------------------------------------------------------------------------
*/

function normaliseCustomer(
  customer = {},
) {
  const totalSpent =
    toNumber(
      customer?.total_spent,
    )

  const visitCount =
    toNonNegativeInteger(
      customer?.visit_count ??
      customer?.total_orders,
    )

  return {
    id:
      toPositiveInteger(
        customer?.id,
        0,
      ),

    name:
      normaliseString(
        customer?.name,
      ) ||
      'Unnamed Customer',

    phone:
      normaliseString(
        customer?.phone,
      ),

    email:
      normaliseString(
        customer?.email,
      ),

    notes:
      normaliseString(
        customer?.notes,
      ),

    initial:
      normaliseString(
        customer?.initial,
      ) ||
      getInitial(
        customer?.name,
      ),

    customer_code:
      normaliseString(
        customer?.customer_code,
      ),

    visit_count:
      visitCount,

    total_orders:
      visitCount,

    total_spent:
      totalSpent,

    total_spent_formatted:
      resolveFormattedMoney(
        customer
          ?.total_spent_formatted,
        totalSpent,
      ),

    last_visit_at:
      normaliseString(
        customer?.last_visit_at,
      ),

    last_visit_date:
      normaliseString(
        customer?.last_visit_date,
      ),

    last_visit_time:
      normaliseString(
        customer?.last_visit_time,
      ),

    last_visit_label:
      normaliseString(
        customer?.last_visit_label,
      ) ||
      'Never',

    is_active:
      resolveBoolean(
        customer?.is_active,
        false,
      ),

    status:
      normaliseString(
        customer?.status,
      ) ||
      (
        resolveBoolean(
          customer?.is_active,
          false,
        )
          ? 'active'
          : 'inactive'
      ),

    status_label:
      normaliseString(
        customer?.status_label,
      ) ||
      (
        resolveBoolean(
          customer?.is_active,
          false,
        )
          ? 'Active'
          : 'Inactive'
      ),

    created_at:
      normaliseString(
        customer?.created_at,
      ),

    created_date:
      normaliseString(
        customer?.created_date,
      ),

    updated_at:
      normaliseString(
        customer?.updated_at,
      ),
  }
}

/*
|--------------------------------------------------------------------------
| Normalise Customer Summary
|--------------------------------------------------------------------------
*/

function normaliseCustomerSummary(
  summary = {},
) {
  const lifetimeSpend =
    toNumber(
      summary?.lifetime_spend,
    )

  return {
    total_customers:
      toNonNegativeInteger(
        summary?.total_customers,
      ),

    active_customers:
      toNonNegativeInteger(
        summary?.active_customers,
      ),

    inactive_customers:
      toNonNegativeInteger(
        summary?.inactive_customers,
      ),

    new_customers_this_month:
      toNonNegativeInteger(
        summary
          ?.new_customers_this_month,
      ),

    total_visits:
      toNonNegativeInteger(
        summary?.total_visits,
      ),

    lifetime_spend:
      lifetimeSpend,

    lifetime_spend_formatted:
      resolveFormattedMoney(
        summary
          ?.lifetime_spend_formatted,
        lifetimeSpend,
      ),
  }
}

/*
|--------------------------------------------------------------------------
| Normalise Pagination Metadata
|--------------------------------------------------------------------------
*/

function normalisePaginationMeta(
  meta = {},
) {
  const lastPage =
    toPositiveInteger(
      meta?.last_page,
      1,
    )

  const currentPage =
    Math.min(
      toPositiveInteger(
        meta?.current_page,
        1,
      ),
      lastPage,
    )

  const perPage =
    toPositiveInteger(
      meta?.per_page,
      10,
    )

  const total =
    toNonNegativeInteger(
      meta?.total,
    )

  return {
    current_page:
      currentPage,

    last_page:
      lastPage,

    per_page:
      perPage,

    total,

    from:
      meta?.from ??
      null,

    to:
      meta?.to ??
      null,
  }
}

/*
|--------------------------------------------------------------------------
| Normalise Customer Filters
|--------------------------------------------------------------------------
*/

function normaliseCustomerFilters(
  filters = {},
) {
  return {
    statuses:
      Array.isArray(
        filters?.statuses,
      )
        ? filters.statuses
        : [
            {
              value: 'all',
              label:
                'All Customers',
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

    sort_options:
      Array.isArray(
        filters?.sort_options,
      )
        ? filters.sort_options
        : [
            {
              value: 'latest',
              label:
                'Newest First',
            },
            {
              value: 'oldest',
              label:
                'Oldest First',
            },
            {
              value: 'name_asc',
              label:
                'Name A–Z',
            },
            {
              value: 'name_desc',
              label:
                'Name Z–A',
            },
            {
              value: 'visits_high',
              label:
                'Most Visits',
            },
            {
              value: 'spend_high',
              label:
                'Highest Spend',
            },
            {
              value:
                'last_visit_latest',
              label:
                'Latest Visit',
            },
          ],
  }
}

/*
|--------------------------------------------------------------------------
| Resolve Customer ID
|--------------------------------------------------------------------------
*/

function resolveCustomerId(
  value,
) {
  const customerId =
    Number(value)

  if (
    !Number.isInteger(
      customerId,
    ) ||
    customerId <= 0
  ) {
    throw new Error(
      'A valid customer ID is required.',
    )
  }

  return customerId
}

/*
|--------------------------------------------------------------------------
| Resolve Formatted Money
|--------------------------------------------------------------------------
*/

function resolveFormattedMoney(
  formattedValue,
  rawValue,
) {
  const resolvedValue =
    normaliseString(
      formattedValue,
    )

  return resolvedValue ||
    formatMoney(
      rawValue,
    )
}

/*
|--------------------------------------------------------------------------
| Get Customer Details
|--------------------------------------------------------------------------
|
| Load:
| - Customer profile
| - Summary
| - Visit/order history
|
*/

async function getCustomerDetails(
  customerId,
  params = {},
) {
  if (
    !customerId
  ) {
    throw new Error(
      'Customer ID is required.',
    )
  }

  try {
    const response =
      await api.get(
        `/customers/${customerId}`,
        {
          params: {
            page:
              params.page || 1,

            per_page:
              params.per_page || 10,
          },
        },
      )

    return {
      success:
        true,

      data:
        response.data,
    }

  } catch (error) {

    throw error

  }
}

/*
|--------------------------------------------------------------------------
| Format Money
|--------------------------------------------------------------------------
*/

function formatMoney(
  value,
) {
  const amount =
    toNumber(
      value,
    )

  return `৳ ${amount.toLocaleString(
    'en-GB',
    {
      minimumFractionDigits: 2,
      maximumFractionDigits: 2,
    },
  )}`
}
/*
|--------------------------------------------------------------------------
| Validation Errors
|--------------------------------------------------------------------------
*/

function getValidationErrors(
  error,
) {

  return (
    error
      ?.response
      ?.data
      ?.errors
    ||
    {}
  )
}
/*
|--------------------------------------------------------------------------
| Get Customer Initial
|--------------------------------------------------------------------------
*/

function getInitial(
  value,
) {
  const name =
    normaliseString(
      value,
    )

  return name
    ? name
        .charAt(0)
        .toUpperCase()
    : 'C'
}

/*
|--------------------------------------------------------------------------
| Number Helpers
|--------------------------------------------------------------------------
*/

function toNumber(
  value,
) {
  const numberValue =
    Number(value)

  return Number.isFinite(
    numberValue,
  )
    ? numberValue
    : 0
}

function toPositiveInteger(
  value,
  fallback = 1,
) {
  const numberValue =
    Number(value)

  return (
    Number.isInteger(
      numberValue,
    ) &&
    numberValue > 0
  )
    ? numberValue
    : fallback
}

function toNonNegativeInteger(
  value,
  fallback = 0,
) {
  const numberValue =
    Number(value)

  return (
    Number.isInteger(
      numberValue,
    ) &&
    numberValue >= 0
  )
    ? numberValue
    : fallback
}

/*
|--------------------------------------------------------------------------
| String Helpers
|--------------------------------------------------------------------------
*/

function normaliseString(
  value,
) {
  if (
    value === null ||
    value === undefined
  ) {
    return ''
  }

  return String(
    value,
  ).trim()
}

function normaliseNullableString(
  value,
) {
  const resolvedValue =
    normaliseString(
      value,
    )

  return resolvedValue ||
    null
}

/*
|--------------------------------------------------------------------------
| Boolean Helper
|--------------------------------------------------------------------------
*/

function resolveBoolean(
  value,
  fallback = false,
) {
  if (
    typeof value ===
      'boolean'
  ) {
    return value
  }

  if (
    value === 1 ||
    value === '1' ||
    value === 'true'
  ) {
    return true
  }

  if (
    value === 0 ||
    value === '0' ||
    value === 'false'
  ) {
    return false
  }

  return fallback
}

/*
|--------------------------------------------------------------------------
| Export Customer Service
|--------------------------------------------------------------------------
*/

export default customerService