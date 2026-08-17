/*
|--------------------------------------------------------------------------
| API Client
|--------------------------------------------------------------------------
*/

import api from "@/services/api";

/*
|--------------------------------------------------------------------------
| Kitchen Order Base URL
|--------------------------------------------------------------------------
*/

const KITCHEN_ORDER_BASE_URL =
  "/kitchen/orders";

/*
|--------------------------------------------------------------------------
| Helpers
|--------------------------------------------------------------------------
*/

function cleanKitchenParams(
  params = {}
) {
  return Object.fromEntries(
    Object.entries(
      params || {}
    ).filter(([, value]) => {
      return (
        value !== undefined &&
        value !== null &&
        value !== ""
      );
    })
  );
}

function resolveOrderId(
  value
) {
  const id = Number(value);

  if (
    !Number.isInteger(id) ||
    id <= 0
  ) {
    throw new TypeError(
      "Kitchen order ID must be a positive integer."
    );
  }

  return id;
}

function resolveBody(
  response
) {
  return (
    response?.data ??
    response ??
    {}
  );
}

function normalizeMeta(
  meta = {}
) {
  return {
    current_page:
      Number(
        meta?.current_page
      ) || 1,

    last_page:
      Number(
        meta?.last_page
      ) || 1,

    per_page:
      Number(
        meta?.per_page
      ) || 20,

    total:
      Number(
        meta?.total
      ) || 0,

    from:
      meta?.from ?? null,

    to:
      meta?.to ?? null,
  };
}

/*
|--------------------------------------------------------------------------
| Error Message
|--------------------------------------------------------------------------
*/

function getKitchenErrorMessage(
  error,
  fallbackMessage =
    "Unable to complete the kitchen request."
) {
  const validationErrors =
    error?.response?.data?.errors;

  if (
    validationErrors &&
    typeof validationErrors ===
      "object"
  ) {
    const firstError =
      Object.values(
        validationErrors
      )
        .flat()
        .find(Boolean);

    if (firstError) {
      return String(firstError);
    }
  }

  return String(
    error?.response?.data
      ?.message ||
      error?.message ||
      fallbackMessage
  );
}

/*
|--------------------------------------------------------------------------
| Kitchen Order Service
|--------------------------------------------------------------------------
|
| IMPORTANT:
| Kitchen routes use the PARENT order ID.
| Backend resolves and locks the latest active OrderKitchenBatch.
|
*/

const kitchenOrderService = {
  /*
  |--------------------------------------------------------------------------
  | Active Kitchen Queue
  |--------------------------------------------------------------------------
  |
  | GET /api/kitchen/orders
  |
  */

  async getKitchenOrders(
    params = {}
  ) {
    const response =
      await api.get(
        KITCHEN_ORDER_BASE_URL,
        {
          params:
            cleanKitchenParams(
              params
            ),
        }
      );

    const body =
      resolveBody(
        response
      );

    return {
      success:
        Boolean(
          body?.success
        ),

      message:
        body?.message || "",

      data:
        Array.isArray(
          body?.data
        )
          ? body.data
          : [],

      meta:
        normalizeMeta(
          body?.meta
        ),
    };
  },

  /*
  |--------------------------------------------------------------------------
  | Kitchen Order Details
  |--------------------------------------------------------------------------
  */

  async getKitchenOrder(
    orderId
  ) {
    const id =
      resolveOrderId(
        orderId
      );

    const response =
      await api.get(
        `${KITCHEN_ORDER_BASE_URL}/${id}`
      );

    const body =
      resolveBody(
        response
      );

    return {
      success:
        Boolean(
          body?.success
        ),

      message:
        body?.message || "",

      data:
        body?.data &&
        typeof body.data ===
          "object"
          ? body.data
          : null,
    };
  },

  /*
  |--------------------------------------------------------------------------
  | Accept Current Kitchen Batch
  |--------------------------------------------------------------------------
  */

  async acceptOrder(
    orderId
  ) {
    const id =
      resolveOrderId(
        orderId
      );

    const response =
      await api.post(
        `${KITCHEN_ORDER_BASE_URL}/${id}/accept`
      );

    return resolveBody(
      response
    );
  },

  /*
  |--------------------------------------------------------------------------
  | Start Preparing Current Kitchen Batch
  |--------------------------------------------------------------------------
  */

  async startPreparing(
    orderId
  ) {
    const id =
      resolveOrderId(
        orderId
      );

    const response =
      await api.post(
        `${KITCHEN_ORDER_BASE_URL}/${id}/start-preparing`
      );

    return resolveBody(
      response
    );
  },

  /*
  |--------------------------------------------------------------------------
  | Mark Current Kitchen Batch Ready
  |--------------------------------------------------------------------------
  */

  async markReady(
    orderId
  ) {
    const id =
      resolveOrderId(
        orderId
      );

    const response =
      await api.post(
        `${KITCHEN_ORDER_BASE_URL}/${id}/ready`
      );

    return resolveBody(
      response
    );
  },

  /*
  |--------------------------------------------------------------------------
  | Public Error Helper
  |--------------------------------------------------------------------------
  */

  getKitchenErrorMessage,
};

export {
  cleanKitchenParams,
  getKitchenErrorMessage,
};

export default kitchenOrderService;