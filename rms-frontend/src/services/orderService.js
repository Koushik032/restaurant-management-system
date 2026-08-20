/*
|--------------------------------------------------------------------------
| API Client
|--------------------------------------------------------------------------
*/

import api from "@/services/api";


/*
|--------------------------------------------------------------------------
| API Base URLs
|--------------------------------------------------------------------------
*/

const ORDER_BASE_URL =
  "/order-management";

const ORDER_URL =
  `${ORDER_BASE_URL}/orders`;

const CUSTOMER_URL =
  "/customers";


/*
|--------------------------------------------------------------------------
| Request Helpers
|--------------------------------------------------------------------------
*/

function cleanRequestParams(
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


function resolvePositiveId(
  value,
  label = "ID"
) {
  const id = Number(value);

  if (
    !Number.isInteger(id) ||
    id <= 0
  ) {
    throw new TypeError(
      `${label} must be a positive integer.`
    );
  }

  return id;
}


/*
|--------------------------------------------------------------------------
| Response Helper
|--------------------------------------------------------------------------
*/

function getResponseBody(
  response
) {
  return (
    response?.data ??
    response ??
    {}
  );
}


/*
|--------------------------------------------------------------------------
| Error Helper
|--------------------------------------------------------------------------
*/

function getOrderErrorMessage(
  error,
  fallbackMessage =
    "Unable to complete the order request."
) {
  const validationErrors =
    error?.response?.data?.errors;

  if (
    validationErrors &&
    typeof validationErrors === "object"
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
    error?.response?.data?.message ||
    error?.message ||
    fallbackMessage
  );
}


/*
|--------------------------------------------------------------------------
| Content-Disposition Filename
|--------------------------------------------------------------------------
*/

function decodeFileName(
  value
) {
  if (!value) {
    return "";
  }

  try {
    return decodeURIComponent(
      String(value)
        .trim()
        .replace(
          /^UTF-8''/i,
          ""
        )
        .replace(
          /^["']|["']$/g,
          ""
        )
    );
  } catch {
    return String(value)
      .trim()
      .replace(
        /^UTF-8''/i,
        ""
      )
      .replace(
        /^["']|["']$/g,
        ""
      );
  }
}


function getDownloadFileName(
  contentDisposition,
  fallbackName
) {
  const header =
    String(
      contentDisposition || ""
    );

  const utfMatch =
    header.match(
      /filename\*\s*=\s*([^;]+)/i
    );

  if (utfMatch?.[1]) {
    const decoded =
      decodeFileName(
        utfMatch[1]
      );

    if (decoded) {
      return decoded;
    }
  }

  const normalMatch =
    header.match(
      /filename\s*=\s*("?)([^";]+)\1/i
    );

  if (normalMatch?.[2]) {
    const decoded =
      decodeFileName(
        normalMatch[2]
      );

    if (decoded) {
      return decoded;
    }
  }

  return fallbackName;
}


/*
|--------------------------------------------------------------------------
| Order Service
|--------------------------------------------------------------------------
*/

const orderService = {

  /*
  |--------------------------------------------------------------------------
  | Order List
  |--------------------------------------------------------------------------
  */

  async getOrders(
    params = {}
  ) {
    const response =
      await api.get(
        ORDER_URL,
        {
          params:
            cleanRequestParams(
              params
            ),
        }
      );

    return getResponseBody(
      response
    );
  },


  /*
  |--------------------------------------------------------------------------
  | Single Order
  |--------------------------------------------------------------------------
  */

  async getOrder(
    orderId
  ) {
    const id =
      resolvePositiveId(
        orderId,
        "Order ID"
      );

    const response =
      await api.get(
        `${ORDER_URL}/${id}`
      );

    return getResponseBody(
      response
    );
  },


  /*
  |--------------------------------------------------------------------------
  | Create Options
  |--------------------------------------------------------------------------
  */

  async getCreateOptions() {
    const response =
      await api.get(
        `${ORDER_BASE_URL}/create-options`
      );

    return getResponseBody(
      response
    );
  },


  /*
  |--------------------------------------------------------------------------
  | Edit Options
  |--------------------------------------------------------------------------
  */

  async getEditOptions(
    orderId
  ) {
    const id =
      resolvePositiveId(
        orderId,
        "Order ID"
      );

    const response =
      await api.get(
        `${ORDER_URL}/${id}/edit-options`
      );

    return getResponseBody(
      response
    );
  },


  /*
  |--------------------------------------------------------------------------
  | Create Order
  |--------------------------------------------------------------------------
  */

  async createOrder(
    payload
  ) {
    const response =
      await api.post(
        ORDER_URL,
        payload
      );

    return getResponseBody(
      response
    );
  },


  /*
  |--------------------------------------------------------------------------
  | Update Order
  |--------------------------------------------------------------------------
  */

  async updateOrder(
    orderId,
    payload
  ) {
    const id =
      resolvePositiveId(
        orderId,
        "Order ID"
      );

    const response =
      await api.put(
        `${ORDER_URL}/${id}`,
        payload
      );

    return getResponseBody(
      response
    );
  },


  /*
  |--------------------------------------------------------------------------
  | Update Status
  |--------------------------------------------------------------------------
  */

  async updateStatus(
    orderId,
    status
  ) {
    const id =
      resolvePositiveId(
        orderId,
        "Order ID"
      );

    const normalizedStatus =
      String(
        status || ""
      ).trim();

    if (!normalizedStatus) {
      throw new TypeError(
        "Order status is required."
      );
    }

    const response =
      await api.patch(
        `${ORDER_URL}/${id}/status`,
        {
          status:
            normalizedStatus,
        }
      );

    return getResponseBody(
      response
    );
  },


  /*
  |--------------------------------------------------------------------------
  | Cancel Order
  |--------------------------------------------------------------------------
  */

  async cancelOrder(
    orderId,
    cancellationReason
  ) {
    const id =
      resolvePositiveId(
        orderId,
        "Order ID"
      );

    const reason =
      String(
        cancellationReason || ""
      ).trim();

    if (!reason) {
      throw new TypeError(
        "Cancellation reason is required."
      );
    }

    const response =
      await api.post(
        `${ORDER_URL}/${id}/cancel`,
        {
          cancellation_reason:
            reason,
        }
      );

    return getResponseBody(
      response
    );
  },


  /*
  |--------------------------------------------------------------------------
  | Complete Order
  |--------------------------------------------------------------------------
  */

  async completeOrder(
    orderId
  ) {
    const id =
      resolvePositiveId(
        orderId,
        "Order ID"
      );

    const response =
      await api.post(
        `${ORDER_URL}/${id}/complete`
      );

    return getResponseBody(
      response
    );
  },


  /*
  |--------------------------------------------------------------------------
  | Customer Search
  |--------------------------------------------------------------------------
  */

  async searchCustomers(
    params = {}
  ) {
    const response =
      await api.get(
        `${CUSTOMER_URL}/search`,
        {
          params:
            cleanRequestParams(
              params
            ),
        }
      );

    return getResponseBody(
      response
    );
  },


  /*
  |--------------------------------------------------------------------------
  | Payment Ledger
  |--------------------------------------------------------------------------
  */

  async getPayments(
    params = {}
  ) {
    const response =
      await api.get(
        `${ORDER_BASE_URL}/payments`,
        {
          params:
            cleanRequestParams(
              params
            ),
        }
      );

    return getResponseBody(
      response
    );
  },


  async recordPayment(
    payload
  ) {
    const response =
      await api.post(
        `${ORDER_BASE_URL}/payments`,
        payload
      );

    return getResponseBody(
      response
    );
  },


  /*
  |--------------------------------------------------------------------------
  | Download Invoice
  |--------------------------------------------------------------------------
  */

  async downloadInvoice(
    orderId
  ) {
    const id =
      resolvePositiveId(
        orderId,
        "Order ID"
      );

    const response =
      await api.get(
        `${ORDER_URL}/${id}/invoice`,
        {
          responseType:
            "blob",
        }
      );

    const blob =
      response?.data;

    if (
      !(blob instanceof Blob)
    ) {
      throw new Error(
        "Invoice file was not returned."
      );
    }

    const fileName =
      getDownloadFileName(
        response?.headers?.[
          "content-disposition"
        ],
        `order-${id}-invoice.pdf`
      );

    const objectUrl =
      URL.createObjectURL(
        blob
      );

    try {
      const anchor =
        document.createElement(
          "a"
        );

      anchor.href =
        objectUrl;

      anchor.download =
        fileName;

      anchor.style.display =
        "none";

      document.body.appendChild(
        anchor
      );

      anchor.click();

      anchor.remove();

    } finally {

      window.setTimeout(
        () => {
          URL.revokeObjectURL(
            objectUrl
          );
        },
        0
      );
    }

    return {
      blob,
      fileName,
    };
  },


  /*
  |--------------------------------------------------------------------------
  | Public Helpers
  |--------------------------------------------------------------------------
  */

  getOrderErrorMessage,

  getErrorMessage:
    getOrderErrorMessage,
};


export {
  cleanRequestParams,
  getOrderErrorMessage,
};


export default orderService;