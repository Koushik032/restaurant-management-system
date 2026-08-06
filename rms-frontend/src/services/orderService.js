import api from "@/services/api";

const orderService = {
  /*
  |--------------------------------------------------------------------------
  | Order List
  |--------------------------------------------------------------------------
  */

  getOrders(params = {}) {
    return api
      .get(
        "/order-management/orders",
        {
          params,
        }
      )
      .then(
        (response) =>
          response.data
      );
  },

  /*
  |--------------------------------------------------------------------------
  | Single Order
  |--------------------------------------------------------------------------
  */

  getOrder(orderId) {
    if (!orderId) {
      return Promise.reject(
        new Error(
          "Order ID is required."
        )
      );
    }

    return api
      .get(
        `/order-management/orders/${orderId}`
      )
      .then(
        (response) =>
          response.data
      );
  },
  /*
|--------------------------------------------------------------------------
| Load Order Edit Options
|--------------------------------------------------------------------------
*/

getEditOptions(orderId) {
  if (!orderId) {
    return Promise.reject(
      new Error(
        "Order ID is required."
      )
    );
  }

  return api
    .get(
      `/order-management/orders/${orderId}/edit-options`
    )
    .then(
      (response) =>
        response.data
    );
},



  /*
  |--------------------------------------------------------------------------
  | Create Order Options
  |--------------------------------------------------------------------------
  */

  getCreateOptions() {
    return api
      .get(
        "/order-management/create-options"
      )
      .then(
        (response) =>
          response.data
      );
  },

  /*
  |--------------------------------------------------------------------------
  | Customer Search
  |--------------------------------------------------------------------------
  */

  searchCustomers(params = {}) {
    return api
      .get(
        "/customers/search",
        {
          params,
        }
      )
      .then(
        (response) =>
          response.data
      );
  },

  /*
  |--------------------------------------------------------------------------
  | Create Order
  |--------------------------------------------------------------------------
  */

  createOrder(payload) {
    if (
      !payload ||
      typeof payload !== "object"
    ) {
      return Promise.reject(
        new Error(
          "Order payload is required."
        )
      );
    }

    return api
      .post(
        "/order-management/orders",
        payload
      )
      .then(
        (response) =>
          response.data
      );
  },

  /*
  |--------------------------------------------------------------------------
  | Update Order
  |--------------------------------------------------------------------------
  */

  updateOrder(
    orderId,
    payload
  ) {
    if (!orderId) {
      return Promise.reject(
        new Error(
          "Order ID is required."
        )
      );
    }

    if (
      !payload ||
      typeof payload !== "object"
    ) {
      return Promise.reject(
        new Error(
          "Order payload is required."
        )
      );
    }

    return api
      .put(
        `/order-management/orders/${orderId}`,
        payload
      )
      .then(
        (response) =>
          response.data
      );
  },

  /*
  |--------------------------------------------------------------------------
  | Update Order Status
  |--------------------------------------------------------------------------
  */

  updateStatus(
    orderId,
    status
  ) {
    if (!orderId) {
      return Promise.reject(
        new Error(
          "Order ID is required."
        )
      );
    }

    if (!status) {
      return Promise.reject(
        new Error(
          "Order status is required."
        )
      );
    }

    return api
      .patch(
        `/order-management/orders/${orderId}/status`,
        {
          status,
        }
      )
      .then(
        (response) =>
          response.data
      );
  },

  /*
  |--------------------------------------------------------------------------
  | Cancel Order
  |--------------------------------------------------------------------------
  */

  cancelOrder(
    orderId,
    cancellationReason
  ) {
    if (!orderId) {
      return Promise.reject(
        new Error(
          "Order ID is required."
        )
      );
    }

    if (
      !cancellationReason ||
      !String(
        cancellationReason
      ).trim()
    ) {
      return Promise.reject(
        new Error(
          "Cancellation reason is required."
        )
      );
    }

    return api
      .post(
        `/order-management/orders/${orderId}/cancel`,
        {
          cancellation_reason:
            String(
              cancellationReason
            ).trim(),
        }
      )
      .then(
        (response) =>
          response.data
      );
  },
  /*
  |--------------------------------------------------------------------------
  | Download Order Invoice
  |--------------------------------------------------------------------------
  |
  | Backend থেকে PDF blob response নিয়ে browser download trigger করবে।
  |
  */

  async downloadInvoice(
    orderId,
  ) {
    const resolvedOrderId =
      Number(orderId)

    if (
      !Number.isInteger(
        resolvedOrderId,
      ) ||
      resolvedOrderId <= 0
    ) {
      throw new Error(
        'A valid order ID is required.',
      )
    }

    const response = await api.get(
      `/order-management/orders/${resolvedOrderId}/invoice`,
      {
        responseType: 'blob',
      },
    )

    /*
    |--------------------------------------------------------------------------
    | Resolve Filename
    |--------------------------------------------------------------------------
    |
    | Backend Content-Disposition header-এ filename থাকলে সেটি ব্যবহার হবে।
    |
    */

    const disposition =
      response.headers[
        'content-disposition'
      ]

    const fileName =
      resolveDownloadFileName(
        disposition,
        `invoice-order-${resolvedOrderId}.pdf`,
      )

    /*
    |--------------------------------------------------------------------------
    | Create Temporary Download URL
    |--------------------------------------------------------------------------
    */

    const fileBlob = new Blob(
      [response.data],
      {
        type:
          response.headers[
            'content-type'
          ] ||
          'application/pdf',
      },
    )

    const downloadUrl =
      window.URL.createObjectURL(
        fileBlob,
      )

    const downloadLink =
      document.createElement('a')

    downloadLink.href =
      downloadUrl

    downloadLink.download =
      fileName

    document.body.appendChild(
      downloadLink,
    )

    downloadLink.click()

    document.body.removeChild(
      downloadLink,
    )

    window.URL.revokeObjectURL(
      downloadUrl,
    )

    return {
      success: true,
      fileName,
    }
  },

  /*
  |--------------------------------------------------------------------------
  | Complete Order
  |--------------------------------------------------------------------------
  */

  completeOrder(orderId) {
    if (!orderId) {
      return Promise.reject(
        new Error(
          "Order ID is required."
        )
      );
    }

    return api
      .post(
        `/order-management/orders/${orderId}/complete`
      )
      .then(
        (response) =>
          response.data
      );
  },
};
/*
|--------------------------------------------------------------------------
| Resolve Download Filename
|--------------------------------------------------------------------------
|
| Content-Disposition header থেকে UTF-8 বা সাধারণ filename parse করে।
|
*/

function resolveDownloadFileName(
  disposition,
  fallbackFileName,
) {
  if (!disposition) {
    return fallbackFileName
  }

  /*
  |--------------------------------------------------------------------------
  | UTF-8 Filename
  |--------------------------------------------------------------------------
  */

  const utf8Match =
    disposition.match(
      /filename\*=UTF-8''([^;]+)/i,
    )

  if (utf8Match?.[1]) {
    try {
      return decodeURIComponent(
        utf8Match[1],
      )
    } catch {
      return utf8Match[1]
    }
  }

  /*
  |--------------------------------------------------------------------------
  | Standard Filename
  |--------------------------------------------------------------------------
  */

  const standardMatch =
    disposition.match(
      /filename="?([^"]+)"?/i,
    )

  if (standardMatch?.[1]) {
    return standardMatch[1]
      .trim()
      .replace(/;$/, '')
  }

  return fallbackFileName
}
export default orderService;