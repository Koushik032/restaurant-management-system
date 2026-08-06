import api from '@/services/api'

/*
|--------------------------------------------------------------------------
| Order Details API Service
|--------------------------------------------------------------------------
|
| এই service Order Details page, payment history এবং নতুন payment
| submission-এর সব API request এক জায়গা থেকে পরিচালনা করবে।
|
*/

const ORDER_BASE_URL =
  '/order-management/orders'

/**
 * একটি order-এর সম্পূর্ণ details load করবে।
 *
 * Backend endpoint:
 * GET /api/order-management/orders/{order}
 *
 * @param {number|string} orderId
 * @returns {Promise<object>}
 */
export async function getOrderDetails(
  orderId,
) {
  if (!orderId) {
    throw new Error(
      'Order ID is required.',
    )
  }

  const response = await api.get(
    `${ORDER_BASE_URL}/${orderId}`,
  )

  /*
   * Laravel JsonResource response সাধারণত:
   *
   * {
   *   data: {
   *      id: ...
   *   }
   * }
   *
   * Backend সরাসরি object পাঠালেও fallback কাজ করবে।
   */
  return (
    response.data?.data ??
    response.data
  )
}

/**
 * একটি order-এর payment history আলাদাভাবে load করবে।
 *
 * Backend endpoint:
 * GET /api/order-management/orders/{order}/payments
 *
 * Order Details API-তে payments already থাকলে সাধারণত
 * এই request প্রয়োজন হবে না। Payment refresh বা standalone
 * payment screen-এর জন্য methodটি রাখা হয়েছে।
 *
 * @param {number|string} orderId
 * @returns {Promise<Array>}
 */
export async function getOrderPayments(
  orderId,
) {
  if (!orderId) {
    throw new Error(
      'Order ID is required.',
    )
  }

  const response = await api.get(
    `${ORDER_BASE_URL}/${orderId}/payments`,
  )

  return (
    response.data?.data ??
    response.data ??
    []
  )
}

/**
 * একটি order-এ নতুন partial/full payment যোগ করবে।
 *
 * Backend endpoint:
 * POST /api/order-management/orders/{order}/payments
 *
 * @param {number|string} orderId
 * @param {{
 *   amount: number|string,
 *   payment_method: string,
 *   reference?: string|null,
 *   note?: string|null
 * }} paymentData
 *
 * @returns {Promise<object>}
 */
export async function addOrderPayment(
  orderId,
  paymentData,
) {
  if (!orderId) {
    throw new Error(
      'Order ID is required.',
    )
  }

  const amount = Number(
    paymentData?.amount,
  )

  if (
    !Number.isFinite(amount) ||
    amount <= 0
  ) {
    throw new Error(
      'Payment amount must be greater than zero.',
    )
  }

  if (
    !paymentData?.payment_method
  ) {
    throw new Error(
      'Payment method is required.',
    )
  }

  const payload = {
    amount,

    payment_method:
      paymentData.payment_method,

    reference:
      paymentData.reference?.trim() ||
      null,

    note:
      paymentData.note?.trim() ||
      null,
  }

  const response = await api.post(
    `${ORDER_BASE_URL}/${orderId}/payments`,
    payload,
  )

  return response.data
}

/**
 * Axios/Laravel error থেকে readable message বের করবে।
 *
 * @param {unknown} error
 * @param {string} fallbackMessage
 * @returns {string}
 */
export function getOrderApiErrorMessage(
  error,
  fallbackMessage =
    'Something went wrong.',
) {
  const validationErrors =
    error?.response?.data?.errors

  if (
    validationErrors &&
    typeof validationErrors ===
      'object'
  ) {
    const firstError =
      Object.values(
        validationErrors,
      )
        .flat()
        .find(Boolean)

    if (firstError) {
      return String(firstError)
    }
  }

  return (
    error?.response?.data?.message ||
    error?.message ||
    fallbackMessage
  )
}

export default {
  getOrderDetails,
  getOrderPayments,
  addOrderPayment,
  getOrderApiErrorMessage,
}