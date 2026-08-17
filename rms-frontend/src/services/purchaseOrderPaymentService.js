import api from '@/services/api'

const PURCHASE_ORDER_BASE_URL =
  '/purchase-orders'

function normalizeCollection(value) {
  const resolved =
    value?.data ?? value ?? []

  return Array.isArray(resolved)
    ? resolved
    : []
}

function firstValidationMessage(error) {
  const errors =
    error?.response?.data?.errors

  if (
    !errors
    || typeof errors !== 'object'
  ) {
    return ''
  }

  const first = Object.values(errors)
    .flat()
    .find(Boolean)

  return first
    ? String(first)
    : ''
}

const purchaseOrderPaymentService = {
  async getPayments(
    purchaseOrderId,
  ) {
    const response = await api.get(
      `${PURCHASE_ORDER_BASE_URL}/${purchaseOrderId}/payments`,
    )

    return {
      success:
        Boolean(
          response?.data?.success,
        ),

      message:
        response?.data?.message || '',

      data:
        normalizeCollection(
          response?.data?.data,
        ),

      summary:
        response?.data?.summary || {},
    }
  },

  async recordPayment(
    purchaseOrderId,
    payload,
  ) {
    const response = await api.post(
      `${PURCHASE_ORDER_BASE_URL}/${purchaseOrderId}/payments`,
      payload,
    )

    return {
      success:
        Boolean(
          response?.data?.success,
        ),

      message:
        response?.data?.message || '',

      data:
        response?.data?.data || null,
    }
  },

  async getReceipts(
    purchaseOrderId,
  ) {
    const response = await api.get(
      `${PURCHASE_ORDER_BASE_URL}/${purchaseOrderId}/receipts`,
    )

    return {
      success:
        Boolean(
          response?.data?.success,
        ),

      message:
        response?.data?.message || '',

      data:
        normalizeCollection(
          response?.data?.data,
        ),

      summary:
        response?.data?.summary || {},
    }
  },

  getValidationErrors(error) {
    const errors =
      error?.response?.data?.errors

    return (
      errors
      && typeof errors === 'object'
    )
      ? errors
      : {}
  },

  getErrorMessage(
    error,
    fallback =
      'Unable to complete the purchase payment request.',
  ) {
    return (
      firstValidationMessage(error)
      || error?.response?.data?.message
      || error?.message
      || fallback
    )
  },
}

export default purchaseOrderPaymentService
