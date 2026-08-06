import {
  reactive,
  ref,
} from "vue";

import { crudService } from "@/services/crudService";

import {
  getMenuResource,
} from "@/config/menuResources";

const createPagination = () => ({
  current_page: 1,
  from: null,
  last_page: 1,
  per_page: 10,
  to: null,
  total: 0,
});

const cloneObject = (value) => {
  return JSON.parse(
    JSON.stringify(value)
  );
};

const sameId = (
  firstId,
  secondId
) => {
  return (
    Number(firstId) ===
    Number(secondId)
  );
};

/**
 * Backend থেকে true, 1, "1", "true"
 * যেকোনো value এলে boolean করবে।
 */
export const normalizeBoolean = (
  value
) => {
  return (
    value === true ||
    value === 1 ||
    value === "1" ||
    value === "true" ||
    value === "on" ||
    value === "yes"
  );
};

export const useCrudResource = (
  resourceName
) => {
  const resource =
    getMenuResource(resourceName);

  const items = ref([]);

  const selectedItem = ref(null);

  const filters = reactive(
    cloneObject(
      resource.defaultFilters
    )
  );

  const pagination = reactive(
    createPagination()
  );

  const loading = reactive({
    list: false,
    submitting: false,
    deleting: false,
    toggling: false,

    deletingId: null,
    togglingId: null,
  });

  const message = ref("");

  const errorMessage = ref("");

  const validationErrors = ref({});

  const clearFeedback = () => {
    message.value = "";
    errorMessage.value = "";
    validationErrors.value = {};
  };

  const handleError = (
    error,
    fallbackMessage =
      "Something went wrong."
  ) => {
    const responseData =
      error?.response?.data ?? {};

    errorMessage.value =
      responseData.message ||
      error?.message ||
      fallbackMessage;

    validationErrors.value =
      responseData.errors &&
      typeof responseData.errors ===
        "object"
        ? responseData.errors
        : {};
  };

  const firstValidationError = (
    field
  ) => {
    const errors =
      validationErrors.value?.[field];

    if (Array.isArray(errors)) {
      return errors[0] || "";
    }

    return errors || "";
  };

  const updatePagination = (
    paginationData
  ) => {
    Object.assign(
      pagination,
      createPagination(),
      paginationData ?? {}
    );
  };

  const replaceLocalItem = (
    updatedItem
  ) => {
    if (!updatedItem?.id) {
      return false;
    }

    const itemIndex =
      items.value.findIndex((item) =>
        sameId(
          item.id,
          updatedItem.id
        )
      );

    if (itemIndex === -1) {
      return false;
    }

    items.value[itemIndex] = {
      ...items.value[itemIndex],
      ...updatedItem,
    };

    return true;
  };

  const removeLocalItem = (id) => {
    items.value =
      items.value.filter(
        (item) =>
          !sameId(item.id, id)
      );
  };

  /**
   * Resource list fetch করবে।
   */
  const fetchItems = async (
    params = {},
    options = {}
  ) => {
    const {
      preserveFeedback = false,
    } = options;

    loading.list = true;

    if (!preserveFeedback) {
      clearFeedback();
    }

    try {
      const result =
        await crudService.list(
          resource.endpoint,
          {
            ...filters,
            ...params,
          }
        );

      items.value = result.items;

      updatePagination(
        result.pagination
      );

      return items.value;
    } catch (error) {
      items.value = [];

      handleError(
        error,
        "Failed to load records."
      );

      throw error;
    } finally {
      loading.list = false;
    }
  };

  /**
   * Dropdown-এর জন্য সব resource fetch করবে।
   */
  const fetchAll = async (
    params = {}
  ) => {
    try {
      const result =
        await crudService.list(
          resource.endpoint,
          {
            per_page: 100,
            ...params,
          }
        );

      return result.items;
    } catch (error) {
      handleError(
        error,
        "Failed to load records."
      );

      throw error;
    }
  };

  /**
   * নতুন resource create করবে।
   */
  const createItem = async (
    payload
  ) => {
    loading.submitting = true;

    clearFeedback();

    try {
      const result =
        await crudService.create(
          resource.endpoint,
          payload
        );

      if (result.item?.id) {
        items.value.unshift(
          result.item
        );

        pagination.total =
          Number(
            pagination.total ?? 0
          ) + 1;
      }

      message.value =
        result.message;

      return result.item;
    } catch (error) {
      handleError(
        error,
        "Failed to create record."
      );

      throw error;
    } finally {
      loading.submitting = false;
    }
  };

  /**
   * Existing resource update করবে।
   */
  const updateItem = async (
    id,
    payload
  ) => {
    loading.submitting = true;

    clearFeedback();

    try {
      const result =
        await crudService.update(
          resource.endpoint,
          id,
          payload
        );

      if (result.item?.id) {
        replaceLocalItem(result.item);

        selectedItem.value =
          result.item;
      }

      message.value =
        result.message;

      return result.item;
    } catch (error) {
      handleError(
        error,
        "Failed to update record."
      );

      throw error;
    } finally {
      loading.submitting = false;
    }
  };

  /**
   * Resource delete করবে।
   */
  const deleteItem = async (id) => {
    loading.deleting = true;

    loading.deletingId = id;

    clearFeedback();

    try {
      const result =
        await crudService.remove(
          resource.endpoint,
          id
        );

      removeLocalItem(id);

      pagination.total = Math.max(
        0,
        Number(
          pagination.total ?? 0
        ) - 1
      );

      if (
        selectedItem.value &&
        sameId(
          selectedItem.value.id,
          id
        )
      ) {
        selectedItem.value = null;
      }

      message.value =
        result.message;

      return true;
    } catch (error) {
      handleError(
        error,
        "Failed to delete record."
      );

      throw error;
    } finally {
      loading.deleting = false;

      loading.deletingId = null;
    }
  };

  /**
   * Status/featured/custom action toggle করবে।
   */
  const toggleItem = async (
    id,
    action =
      resource.statusAction ||
      "status"
  ) => {
    loading.toggling = true;

    loading.togglingId = id;

    clearFeedback();

    try {
      const result =
        await crudService.toggle(
          resource.endpoint,
          id,
          action
        );

      if (result.item?.id) {
        replaceLocalItem(result.item);
      }

      message.value =
        result.message;

      return result.item;
    } catch (error) {
      handleError(
        error,
        "Failed to update record."
      );

      throw error;
    } finally {
      loading.toggling = false;

      loading.togglingId = null;
    }
  };

  const resetFilters = () => {
    Object.assign(
      filters,
      cloneObject(
        resource.defaultFilters
      )
    );
  };

  return {
    resource,

    items,
    selectedItem,
    filters,
    pagination,
    loading,

    message,
    errorMessage,
    validationErrors,

    clearFeedback,
    handleError,
    firstValidationError,

    fetchItems,
    fetchAll,
    createItem,
    updateItem,
    deleteItem,
    toggleItem,

    resetFilters,
    replaceLocalItem,
    removeLocalItem,
  };
};