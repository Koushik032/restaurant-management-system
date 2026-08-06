/*
|--------------------------------------------------------------------------
| API Client
|--------------------------------------------------------------------------
*/

import api from '@/services/api'

/*
|--------------------------------------------------------------------------
| Billing API Base Path
|--------------------------------------------------------------------------
*/

const BILLING_BASE_URL =
  '/billing'

/*
|--------------------------------------------------------------------------
| Build Billing Request Filters
|--------------------------------------------------------------------------
|
| Frontend camelCase filters-কে Laravel API snake_case query parameter-এ
| convert করবে।
|
*/

function buildFilters(
  filters = {},
) {
  return cleanRequestParams({
    date_from:
      filters.dateFrom ??
      filters.date_from,

    date_to:
      filters.dateTo ??
      filters.date_to,

    status:
      filters.status,

    payment_method:
      filters.paymentMethod ??
      filters.payment_method,

    user_id:
      filters.userId ??
      filters.user_id,

    user_type:
      filters.userType ??
      filters.user_type,

    page:
      filters.page,

    per_page:
      filters.perPage ??
      filters.per_page,
  })
}

/*
|--------------------------------------------------------------------------
| Billing Service
|--------------------------------------------------------------------------
*/

const billingService = {
  /*
  |--------------------------------------------------------------------------
  | Load Billing Summary
  |--------------------------------------------------------------------------
  |
  | GET /api/billing/summary
  |
  */

  async getSummary(
    params = {},
  ) {
    const response =
      await api.get(
        `${BILLING_BASE_URL}/summary`,
        {
          params:
            buildFilters(
              params,
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
        normaliseSummary(
          response?.data
            ?.data,
        ),
    }
  },

  /*
  |--------------------------------------------------------------------------
  | Load Settlement Orders
  |--------------------------------------------------------------------------
  |
  | GET /api/billing/settlements
  |
  */

  async getSettlements(
    params = {},
  ) {
    const response =
      await api.get(
        `${BILLING_BASE_URL}/settlements`,
        {
          params:
            buildFilters(
              params,
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
        Array.isArray(
          response?.data
            ?.data,
        )
          ? response.data.data
          : [],

      meta:
        normalisePaginationMeta(
          response?.data
            ?.meta,
        ),

      totals:
        normaliseTotals(
          response?.data
            ?.totals,
        ),

      dateRange:
        normaliseDateRange(
          response?.data
            ?.date_range,
        ),

      filters:
        normaliseSettlementFilters(
          response?.data
            ?.filters,
        ),
    }
  },

  /*
  |--------------------------------------------------------------------------
  | Load Payment Mode Report
  |--------------------------------------------------------------------------
  |
  | GET /api/billing/payment-modes
  |
  */

  async getPaymentModes(
    params = {},
  ) {
    const response =
      await api.get(
        `${BILLING_BASE_URL}/payment-modes`,
        {
          params:
            buildFilters(
              params,
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
        Array.isArray(
          response?.data
            ?.data,
        )
          ? response.data.data
          : [],

      meta:
        normalisePaginationMeta(
          response?.data
            ?.meta,
        ),

      totals:
        normaliseTotals(
          response?.data
            ?.totals,
        ),

      dateRange:
        normaliseDateRange(
          response?.data
            ?.date_range,
        ),

      filters:
        normalisePaymentModeFilters(
          response?.data
            ?.filters,
        ),
    }
  },

  /*
  |--------------------------------------------------------------------------
  | Load Payment Activity
  |--------------------------------------------------------------------------
  |
  | GET /api/billing/payment-activities
  |
  */

  async getPaymentActivities(
    params = {},
  ) {
    const response =
      await api.get(
        `${BILLING_BASE_URL}/payment-activities`,
        {
          params:
            buildFilters(
              params,
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
        Array.isArray(
          response?.data
            ?.data,
        )
          ? response.data.data
          : [],

      meta:
        normalisePaginationMeta(
          response?.data
            ?.meta,
        ),

      totals:
        normaliseTotals(
          response?.data
            ?.totals,
        ),

      dateRange:
        normaliseDateRange(
          response?.data
            ?.date_range,
        ),
    }
  },

  /*
  |--------------------------------------------------------------------------
  | Load Billing Users
  |--------------------------------------------------------------------------
  |
  | GET /api/billing/users
  |
  */

  async getUsers() {
    const response =
      await api.get(
        `${BILLING_BASE_URL}/users`,
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
        Array.isArray(
          response?.data
            ?.data,
        )
          ? response.data.data
          : [],
    }
  },

  /*
  |--------------------------------------------------------------------------
  | Resolve Billing API Error
  |--------------------------------------------------------------------------
  */

  getBillingErrorMessage(
    error,
    fallbackMessage =
      'Unable to load billing information.',
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
}
/*
|--------------------------------------------------------------------------
| Clean Request Parameters
|--------------------------------------------------------------------------
|
| null, undefined এবং empty string API request থেকে বাদ দেওয়া হবে।
| তবে 0 এবং false valid value হিসেবে রাখা হবে।
|
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
| Normalise Billing Summary
|--------------------------------------------------------------------------
|
| Backend থেকে field missing থাকলেও frontend-safe default return করবে।
|
*/

function normaliseSummary(
  summary = {},
) {
  const grossSales =
    toNumber(
      summary?.gross_sales,
    )

  const discountAmount =
    toNumber(
      summary?.discount_amount,
    )

  const netSales =
    toNumber(
      summary?.net_sales,
    )

  const tax =
    toNumber(
      summary?.tax,
    )

  const serviceCharge =
    toNumber(
      summary?.service_charge,
    )

  const totalBilled =
    toNumber(
      summary?.total_billed,
    )

  const collectedAmount =
    toNumber(
      summary?.collected_amount,
    )

  const expenses =
    toNumber(
      summary?.expenses,
    )

  const cashCollection =
    toNumber(
      summary?.cash_collection,
    )

  const outstandingDue =
    toNumber(
      summary?.outstanding_due,
    )

  return {
    /*
    |--------------------------------------------------------------------------
    | Raw Values
    |--------------------------------------------------------------------------
    */

    gross_sales:
      grossSales,

    discount_amount:
      discountAmount,

    net_sales:
      netSales,

    tax,

    service_charge:
      serviceCharge,

    total_billed:
      totalBilled,

    collected_amount:
      collectedAmount,

    expenses,

    cash_collection:
      cashCollection,

    outstanding_due:
      outstandingDue,

    total_orders:
      toNonNegativeInteger(
        summary?.total_orders,
      ),

    /*
    |--------------------------------------------------------------------------
    | Formatted Values
    |--------------------------------------------------------------------------
    */

    gross_sales_formatted:
      resolveFormattedMoney(
        summary
          ?.gross_sales_formatted,
        grossSales,
      ),

    discount_amount_formatted:
      resolveFormattedMoney(
        summary
          ?.discount_amount_formatted,
        discountAmount,
      ),

    net_sales_formatted:
      resolveFormattedMoney(
        summary
          ?.net_sales_formatted,
        netSales,
      ),

    tax_formatted:
      resolveFormattedMoney(
        summary
          ?.tax_formatted,
        tax,
      ),

    service_charge_formatted:
      resolveFormattedMoney(
        summary
          ?.service_charge_formatted,
        serviceCharge,
      ),

    total_billed_formatted:
      resolveFormattedMoney(
        summary
          ?.total_billed_formatted,
        totalBilled,
      ),

    collected_amount_formatted:
      resolveFormattedMoney(
        summary
          ?.collected_amount_formatted,
        collectedAmount,
      ),

    expenses_formatted:
      resolveFormattedMoney(
        summary
          ?.expenses_formatted,
        expenses,
      ),

    cash_collection_formatted:
      resolveFormattedMoney(
        summary
          ?.cash_collection_formatted,
        cashCollection,
      ),

    outstanding_due_formatted:
      resolveFormattedMoney(
        summary
          ?.outstanding_due_formatted,
        outstandingDue,
      ),

    /*
    |--------------------------------------------------------------------------
    | Active Date Range
    |--------------------------------------------------------------------------
    */

    date_range:
      normaliseDateRange(
        summary?.date_range,
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
      5,
    )

  const total =
    toNonNegativeInteger(
      meta?.total,
    )

  const fallbackFrom =
    total > 0
      ? (
          (currentPage - 1) *
          perPage
        ) + 1
      : null

  const fallbackTo =
    total > 0
      ? Math.min(
          currentPage *
            perPage,
          total,
        )
      : null

  return {
    current_page:
      currentPage,

    last_page:
      lastPage,

    per_page:
      perPage,

    total,

    from:
      normaliseNullableInteger(
        meta?.from,
        fallbackFrom,
      ),

    to:
      normaliseNullableInteger(
        meta?.to,
        fallbackTo,
      ),
  }
}

/*
|--------------------------------------------------------------------------
| Normalise Filtered Totals
|--------------------------------------------------------------------------
*/

function normaliseTotals(
  totals = {},
) {
  const amount =
    toNumber(
      totals?.amount,
    )

  return {
    amount,

    amount_formatted:
      resolveFormattedMoney(
        totals
          ?.amount_formatted,
        amount,
      ),
  }
}

/*
|--------------------------------------------------------------------------
| Normalise Date Range
|--------------------------------------------------------------------------
*/

function normaliseDateRange(
  dateRange = {},
) {
  return {
    from:
      normaliseString(
        dateRange?.from,
      ),

    to:
      normaliseString(
        dateRange?.to,
      ),

    from_label:
      normaliseString(
        dateRange?.from_label,
      ),

    to_label:
      normaliseString(
        dateRange?.to_label,
      ),
  }
}
/*
|--------------------------------------------------------------------------
| Default Settlement Status Options
|--------------------------------------------------------------------------
*/

const DEFAULT_SETTLEMENT_STATUS_OPTIONS = [
  {
    value: '',
    label: 'All Statuses',
  },
  {
    value: 'due',
    label: 'Due',
  },
  {
    value: 'partially_paid',
    label: 'Partially Paid',
  },
  {
    value: 'paid',
    label: 'Paid',
  },
  {
    value: 'pending',
    label: 'Pending',
  },
  {
    value: 'preparing',
    label: 'Preparing',
  },
  {
    value: 'ready',
    label: 'Ready',
  },
  {
    value: 'served',
    label: 'Served',
  },
  {
    value: 'completed',
    label: 'Completed',
  },
  {
    value: 'canceled',
    label: 'Canceled',
  },
]

/*
|--------------------------------------------------------------------------
| Default Payment Method Options
|--------------------------------------------------------------------------
*/

const DEFAULT_PAYMENT_METHOD_OPTIONS = [
  {
    value: '',
    label: 'All Methods',
  },
  {
    value: 'cash',
    label: 'Cash',
  },
  {
    value: 'card',
    label: 'Card',
  },
  {
    value: 'bkash',
    label: 'bKash',
  },
  {
    value: 'nagad',
    label: 'Nagad',
  },
  {
    value: 'bank_transfer',
    label: 'Bank Transfer',
  },
  {
    value: 'mixed',
    label: 'Mixed',
  },
]

/*
|--------------------------------------------------------------------------
| Normalise Settlement Filters
|--------------------------------------------------------------------------
*/

function normaliseSettlementFilters(
  filters = {},
) {
  const statuses =
    Array.isArray(
      filters?.statuses,
    )
      ? filters.statuses
      : []

  return {
    statuses:
      statuses.length > 0
        ? normaliseFilterOptions(
            statuses,
          )
        : cloneFilterOptions(
            DEFAULT_SETTLEMENT_STATUS_OPTIONS,
          ),
  }
}

/*
|--------------------------------------------------------------------------
| Normalise Payment Method Filters
|--------------------------------------------------------------------------
*/

function normalisePaymentModeFilters(
  filters = {},
) {
  const paymentMethods =
    Array.isArray(
      filters?.payment_methods,
    )
      ? filters.payment_methods
      : []

  return {
    payment_methods:
      paymentMethods.length > 0
        ? normaliseFilterOptions(
            paymentMethods,
          )
        : cloneFilterOptions(
            DEFAULT_PAYMENT_METHOD_OPTIONS,
          ),
  }
}

/*
|--------------------------------------------------------------------------
| Normalise Filter Options
|--------------------------------------------------------------------------
|
| Invalid option rows remove করবে এবং value/label string হিসেবে রাখবে।
|
*/

function normaliseFilterOptions(
  options = [],
) {
  return options
    .filter(
      (option) =>
        option &&
        typeof option ===
          'object',
    )
    .map((option) => ({
      value:
        normaliseString(
          option?.value,
        ),

      label:
        normaliseString(
          option?.label,
        ) ||
        formatReadableLabel(
          option?.value,
        ),
    }))
}

/*
|--------------------------------------------------------------------------
| Clone Filter Options
|--------------------------------------------------------------------------
|
| Default arrays সরাসরি expose না করে নতুন array/object return করবে।
|
*/

function cloneFilterOptions(
  options = [],
) {
  return options.map(
    (option) => ({
      ...option,
    }),
  )
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
  if (
    typeof formattedValue ===
      'string' &&
    formattedValue.trim() !==
      ''
  ) {
    return formattedValue
  }

  return formatMoney(
    rawValue,
  )
}

/*
|--------------------------------------------------------------------------
| Format Money
|--------------------------------------------------------------------------
*/

function formatMoney(
  amount,
) {
  const resolvedAmount =
    toNumber(
      amount,
    )

  return `৳ ${resolvedAmount.toLocaleString(
    'en-GB',
    {
      minimumFractionDigits: 2,
      maximumFractionDigits: 2,
    },
  )}`
}

/*
|--------------------------------------------------------------------------
| Number Helper
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

/*
|--------------------------------------------------------------------------
| Positive Integer Helper
|--------------------------------------------------------------------------
*/

function toPositiveInteger(
  value,
  fallback = 1,
) {
  const numberValue =
    Number(value)

  if (
    Number.isInteger(
      numberValue,
    ) &&
    numberValue > 0
  ) {
    return numberValue
  }

  return fallback
}

/*
|--------------------------------------------------------------------------
| Non-negative Integer Helper
|--------------------------------------------------------------------------
*/

function toNonNegativeInteger(
  value,
  fallback = 0,
) {
  const numberValue =
    Number(value)

  if (
    Number.isInteger(
      numberValue,
    ) &&
    numberValue >= 0
  ) {
    return numberValue
  }

  return fallback
}

/*
|--------------------------------------------------------------------------
| Nullable Integer Helper
|--------------------------------------------------------------------------
*/

function normaliseNullableInteger(
  value,
  fallback = null,
) {
  if (
    value === null ||
    value === undefined ||
    value === ''
  ) {
    return fallback
  }

  const numberValue =
    Number(value)

  return Number.isInteger(
    numberValue,
  )
    ? numberValue
    : fallback
}

/*
|--------------------------------------------------------------------------
| String Helper
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

/*
|--------------------------------------------------------------------------
| Readable Label Formatter
|--------------------------------------------------------------------------
*/

function formatReadableLabel(
  value,
) {
  const resolvedValue =
    normaliseString(
      value,
    )

  if (!resolvedValue) {
    return 'Not Available'
  }

  return resolvedValue
    .replaceAll(
      '_',
      ' ',
    )
    .replace(
      /\b\w/g,
      (letter) =>
        letter.toUpperCase(),
    )
}

/*
|--------------------------------------------------------------------------
| Export Billing Service
|--------------------------------------------------------------------------
*/

export default billingService