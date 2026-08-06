/*
|--------------------------------------------------------------------------
| API Client
|--------------------------------------------------------------------------
*/

import api from '@/services/api'

/*
|--------------------------------------------------------------------------
| Kitchen API Base Path
|--------------------------------------------------------------------------
*/

const KITCHEN_ORDER_BASE_URL =
  '/kitchen/orders'

/*
|--------------------------------------------------------------------------
| Kitchen Order Service
|--------------------------------------------------------------------------
*/

const kitchenOrderService = {
  /*
  |--------------------------------------------------------------------------
  | Load Kitchen Orders
  |--------------------------------------------------------------------------
  |
  | GET /api/kitchen/orders
  |
  | Supported query parameters:
  | - page
  | - per_page
  | - search
  | - status
  | - assignment
  | - chef_id
  |
  */

  async getKitchenOrders(
    params = {},
  ) {
    const response = await api.get(
      KITCHEN_ORDER_BASE_URL,
      {
        params:
          cleanRequestParams(
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
          response?.data?.data,
        )
          ? response.data.data
          : [],

      meta:
        normalisePaginationMeta(
          response?.data?.meta,
        ),
    }
  },

  /*
  |--------------------------------------------------------------------------
  | Load Single Kitchen Order
  |--------------------------------------------------------------------------
  |
  | GET /api/kitchen/orders/{order}
  |
  */

  async getKitchenOrder(
    orderId,
  ) {
    validateOrderId(orderId)

    const response = await api.get(
      `${KITCHEN_ORDER_BASE_URL}/${orderId}`,
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
        response?.data?.data ||
        null,
    }
  },

  /*
  |--------------------------------------------------------------------------
  | Accept Kitchen Order
  |--------------------------------------------------------------------------
  |
  | POST /api/kitchen/orders/{order}/accept
  |
  | Backend updates:
  | - chef_id
  | - sent_to_kitchen_at
  |
  */

  async acceptOrder(
    orderId,
  ) {
    validateOrderId(orderId)

    const response = await api.post(
      `${KITCHEN_ORDER_BASE_URL}/${orderId}/accept`,
    )

    return normaliseActionResponse(
      response,
      'Order accepted successfully.',
    )
  },

  /*
  |--------------------------------------------------------------------------
  | Start Preparing
  |--------------------------------------------------------------------------
  |
  | POST /api/kitchen/orders/{order}/start-preparing
  |
  | Backend updates:
  | - status = preparing
  | - preparing_at
  | - order_items.status = preparing
  |
  */

  async startPreparing(
    orderId,
  ) {
    validateOrderId(orderId)

    const response = await api.post(
      `${KITCHEN_ORDER_BASE_URL}/${orderId}/start-preparing`,
    )

    return normaliseActionResponse(
      response,
      'Order preparation started successfully.',
    )
  },

  /*
  |--------------------------------------------------------------------------
  | Mark Kitchen Order Ready
  |--------------------------------------------------------------------------
  |
  | POST /api/kitchen/orders/{order}/ready
  |
  | Backend updates:
  | - status = ready
  | - ready_at
  | - order_items.status = ready
  |
  */

  async markReady(
    orderId,
  ) {
    validateOrderId(orderId)

    const response = await api.post(
      `${KITCHEN_ORDER_BASE_URL}/${orderId}/ready`,
    )

    return normaliseActionResponse(
      response,
      'Order marked as ready successfully.',
    )
  },

  /*
  |--------------------------------------------------------------------------
  | Resolve Kitchen API Error
  |--------------------------------------------------------------------------
  */

  getKitchenErrorMessage(
    error,
    fallbackMessage =
      'Something went wrong.',
  ) {
    /*
    |--------------------------------------------------------------------------
    | Laravel Validation Errors
    |--------------------------------------------------------------------------
    */

    const errors =
      error?.response?.data
        ?.errors

    if (
      errors &&
      typeof errors ===
        'object'
    ) {
      const firstMessage =
        Object.values(errors)
          .flat()
          .find(Boolean)

      if (firstMessage) {
        return String(
          firstMessage,
        )
      }
    }

    /*
    |--------------------------------------------------------------------------
    | Standard Laravel Error Message
    |--------------------------------------------------------------------------
    */

    const responseMessage =
      error?.response?.data
        ?.message

    if (responseMessage) {
      return String(
        responseMessage,
      )
    }

    /*
    |--------------------------------------------------------------------------
    | Native JavaScript Error
    |--------------------------------------------------------------------------
    */

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
| Validate Order ID
|--------------------------------------------------------------------------
*/

function validateOrderId(
  orderId,
) {
  const resolvedId =
    Number(orderId)

  if (
    !Number.isInteger(
      resolvedId,
    ) ||
    resolvedId <= 0
  ) {
    throw new Error(
      'A valid kitchen order ID is required.',
    )
  }
}

/*
|--------------------------------------------------------------------------
| Clean Request Parameters
|--------------------------------------------------------------------------
|
| Empty search/filter values request থেকে বাদ দেওয়া হবে।
|
*/

function cleanRequestParams(
  params,
) {
  return Object.entries(
    params || {},
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
| Normalise Kitchen Action Response
|--------------------------------------------------------------------------
*/

function normaliseActionResponse(
  response,
  fallbackMessage,
) {
  return {
    success:
      Boolean(
        response?.data
          ?.success,
      ),

    message:
      response?.data
        ?.message ||
      fallbackMessage,

    data:
      response?.data?.data ||
      null,
  }
}

/*
|--------------------------------------------------------------------------
| Normalise Pagination Metadata
|--------------------------------------------------------------------------
*/

function normalisePaginationMeta(
  meta,
) {
  return {
    current_page:
      toPositiveNumber(
        meta?.current_page,
        1,
      ),

    last_page:
      toPositiveNumber(
        meta?.last_page,
        1,
      ),

    per_page:
      toPositiveNumber(
        meta?.per_page,
        20,
      ),

    total:
      toNonNegativeNumber(
        meta?.total,
        0,
      ),

    from:
      meta?.from ?? null,

    to:
      meta?.to ?? null,
  }
}

/*
|--------------------------------------------------------------------------
| Positive Number Helper
|--------------------------------------------------------------------------
*/

function toPositiveNumber(
  value,
  fallback,
) {
  const numberValue =
    Number(value)

  return Number.isFinite(
    numberValue,
  ) &&
    numberValue > 0
    ? numberValue
    : fallback
}

/*
|--------------------------------------------------------------------------
| Non-negative Number Helper
|--------------------------------------------------------------------------
*/

function toNonNegativeNumber(
  value,
  fallback,
) {
  const numberValue =
    Number(value)

  return Number.isFinite(
    numberValue,
  ) &&
    numberValue >= 0
    ? numberValue
    : fallback
}

/*
|--------------------------------------------------------------------------
| Export Kitchen Order Service
|--------------------------------------------------------------------------
*/

export default kitchenOrderService