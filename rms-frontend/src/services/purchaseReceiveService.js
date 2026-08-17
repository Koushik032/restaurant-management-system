import api from '@/services/api'

const PURCHASE_ORDER_ENDPOINT =
  '/purchase-orders'

function normalizeResponse(response) {
  const responseBody =
    response?.data ?? {}

  return {
    data:
      responseBody?.data
      ??
      responseBody,

    message:
      responseBody?.message
      ??
      '',

    meta:
      responseBody?.meta
      ??
      null,

    rawResponse:
      response,
  }
}

function getValidationErrors(error) {
  const errors =
    error?.response?.data
      ?.errors

  if (
    !errors
    ||
    typeof errors !==
      'object'
  ) {
    return {}
  }

  return errors
}

function getErrorMessage(
  error,
  fallbackMessage =
    'Something went wrong.',
) {
  const responseData =
    error?.response?.data

  const validationErrors =
    responseData?.errors

  if (
    validationErrors
    &&
    typeof validationErrors ===
      'object'
  ) {
    const firstValidationMessage =
      Object
        .values(
          validationErrors,
        )
        .flat()
        .find(
          (message) =>
            typeof message ===
              'string'
            &&
            message.trim() !== '',
        )

    if (firstValidationMessage) {
      return firstValidationMessage
    }
  }

  if (
    typeof responseData?.message ===
      'string'
    &&
    responseData.message.trim() !==
      ''
  ) {
    return responseData
      .message
      .trim()
  }

  if (
    typeof error?.message ===
      'string'
    &&
    error.message.trim() !== ''
  ) {
    return error.message.trim()
  }

  return fallbackMessage
}

const purchaseReceiveService = {
  /*
  |--------------------------------------------------------------------------
  | Get Single Purchase Order
  |--------------------------------------------------------------------------
  */

  async getPurchaseOrder(
    purchaseOrderId,
  ) {
    if (!purchaseOrderId) {
      throw new Error(
        'Purchase order ID is required.',
      )
    }

    const response =
      await api.get(
        `${PURCHASE_ORDER_ENDPOINT}/${purchaseOrderId}`,
      )

    return normalizeResponse(
      response,
    )
  },

  /*
  |--------------------------------------------------------------------------
  | Receive Purchase Order
  |--------------------------------------------------------------------------
  */

  async receivePurchaseOrder(
    purchaseOrderId,
    payload,
  ) {
    if (!purchaseOrderId) {
      throw new Error(
        'Purchase order ID is required.',
      )
    }

    const response =
      await api.post(
        `${PURCHASE_ORDER_ENDPOINT}/${purchaseOrderId}/receive`,
        payload,
      )

    return normalizeResponse(
      response,
    )
  },

  getValidationErrors,

  getErrorMessage,
}

export default purchaseReceiveService