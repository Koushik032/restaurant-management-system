import api from "./api";

/**
 * Empty query parameter remove করবে।
 */
const cleanQuery = (query = {}) => {
  return Object.fromEntries(
    Object.entries(query).filter(
      ([, value]) => {
        return (
          value !== "" &&
          value !== null &&
          value !== undefined
        );
      }
    )
  );
};

/**
 * Response থেকে collection বের করবে।
 */
const extractCollection = (response) => {
  const responseData =
    response?.data?.data;

  if (Array.isArray(responseData)) {
    return responseData;
  }

  if (
    responseData &&
    Array.isArray(responseData.data)
  ) {
    return responseData.data;
  }

  return [];
};

/**
 * Response থেকে single resource বের করবে।
 */
const extractResource = (response) => {
  const responseData =
    response?.data?.data;

  if (
    responseData &&
    typeof responseData === "object" &&
    !Array.isArray(responseData) &&
    responseData.data !== undefined
  ) {
    return responseData.data;
  }

  return responseData ?? null;
};

/**
 * Response থেকে pagination বের করবে।
 */
const extractPagination = (response) => {
  const meta =
    response?.data?.meta ??
    response?.data?.data?.meta ??
    {};

  return {
    current_page: Number(
      meta.current_page ?? 1
    ),

    from: meta.from ?? null,

    last_page: Number(
      meta.last_page ?? 1
    ),

    per_page: Number(
      meta.per_page ?? 10
    ),

    to: meta.to ?? null,

    total: Number(meta.total ?? 0),
  };
};

const getMessage = (
  response,
  fallbackMessage
) => {
  return (
    response?.data?.message ||
    fallbackMessage
  );
};

const isFormData = (payload) => {
  return (
    typeof FormData !== "undefined" &&
    payload instanceof FormData
  );
};

export const crudService = {
  /**
   * Resource list fetch করবে।
   */
  async list(endpoint, params = {}) {
    const response = await api.get(
      endpoint,
      {
        params: cleanQuery(params),
      }
    );

    return {
      items: extractCollection(response),

      pagination:
        extractPagination(response),

      message: getMessage(response, ""),

      rawResponse: response,
    };
  },

  /**
   * একটি single resource fetch করবে।
   */
  async show(endpoint, id) {
    const response = await api.get(
      `${endpoint}/${id}`
    );

    return {
      item: extractResource(response),

      message: getMessage(response, ""),

      rawResponse: response,
    };
  },

  /**
   * নতুন resource create করবে।
   */
  async create(endpoint, payload) {
    const response = await api.post(
      endpoint,
      payload
    );

    return {
      item: extractResource(response),

      message: getMessage(
        response,
        "Record created successfully."
      ),

      rawResponse: response,
    };
  },

  /**
   * Existing resource update করবে।
   *
   * FormData হলে Laravel-এর জন্য
   * POST + _method=PUT ব্যবহার করবে।
   */
  async update(endpoint, id, payload) {
    let response;

    if (isFormData(payload)) {
      if (!payload.has("_method")) {
        payload.append("_method", "PUT");
      }

      response = await api.post(
        `${endpoint}/${id}`,
        payload
      );
    } else {
      response = await api.put(
        `${endpoint}/${id}`,
        payload
      );
    }

    return {
      item: extractResource(response),

      message: getMessage(
        response,
        "Record updated successfully."
      ),

      rawResponse: response,
    };
  },

  /**
   * Resource delete করবে।
   */
  async remove(endpoint, id) {
    const response = await api.delete(
      `${endpoint}/${id}`
    );

    return {
      message: getMessage(
        response,
        "Record deleted successfully."
      ),

      rawResponse: response,
    };
  },

  /**
   * Status, featured বা অন্য custom
   * PATCH action call করবে।
   */
  async toggle(
    endpoint,
    id,
    action = "status"
  ) {
    const response = await api.patch(
      `${endpoint}/${id}/${action}`
    );

    return {
      item: extractResource(response),

      message: getMessage(
        response,
        "Record updated successfully."
      ),

      rawResponse: response,
    };
  },
};