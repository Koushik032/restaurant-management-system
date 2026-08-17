/*
|--------------------------------------------------------------------------
| API Client
|--------------------------------------------------------------------------
*/

import api from '@/services/api'

/*
|--------------------------------------------------------------------------
| Inventory API Base URL
|--------------------------------------------------------------------------
*/

const INVENTORY_BASE_URL =
  '/inventory'

/*
|--------------------------------------------------------------------------
| Default Inventory Summary
|--------------------------------------------------------------------------
*/

function createDefaultSummary() {
  return {
    total_raw_materials: 0,
    available_count: 0,
    limited_count: 0,
    out_of_stock_count: 0,
    low_stock_alert_count: 0,
    total_stock_value: 0,
    total_stock_value_formatted:
      '৳ 0.00',
    has_low_stock_alert: false,
  }
}

/*
|--------------------------------------------------------------------------
| Default Pagination Meta
|--------------------------------------------------------------------------
*/

function createDefaultMeta() {
  return {
    current_page: 1,
    last_page: 1,
    per_page: 10,
    total: 0,
    from: null,
    to: null,
  }
}

/*
|--------------------------------------------------------------------------
| Clean Request Parameters
|--------------------------------------------------------------------------
*/

function cleanRequestParams(
  params = {},
) {
  return Object.fromEntries(
    Object.entries(params).filter(
      ([, value]) => {
        return (
          value !== undefined &&
          value !== null &&
          value !== ''
        )
      },
    ),
  )
}

/*
|--------------------------------------------------------------------------
| Normalize Pagination Meta
|--------------------------------------------------------------------------
*/

function normalizePaginationMeta(
  meta = {},
) {
  return {
    current_page:
      Number(meta?.current_page) || 1,

    last_page:
      Number(meta?.last_page) || 1,

    per_page:
      Number(meta?.per_page) || 10,

    total:
      Number(meta?.total) || 0,

    from:
      meta?.from ?? null,

    to:
      meta?.to ?? null,
  }
}

/*
|--------------------------------------------------------------------------
| Normalize Inventory Summary
|--------------------------------------------------------------------------
*/

function normalizeSummary(
  summary = {},
) {
  const defaults =
    createDefaultSummary()

  return {
    ...defaults,
    ...summary,

    total_raw_materials:
      Number(
        summary?.total_raw_materials,
      ) || 0,

    available_count:
      Number(
        summary?.available_count,
      ) || 0,

    limited_count:
      Number(
        summary?.limited_count,
      ) || 0,

    out_of_stock_count:
      Number(
        summary?.out_of_stock_count,
      ) || 0,

    low_stock_alert_count:
      Number(
        summary?.low_stock_alert_count,
      ) || 0,

    total_stock_value:
      Number(
        summary?.total_stock_value,
      ) || 0,

    total_stock_value_formatted:
      summary
        ?.total_stock_value_formatted ||
      defaults
        .total_stock_value_formatted,

    has_low_stock_alert:
      Boolean(
        summary?.has_low_stock_alert,
      ),
  }
}

/*
|--------------------------------------------------------------------------
| Inventory Service
|--------------------------------------------------------------------------
*/

const inventoryService = {
  /*
  |--------------------------------------------------------------------------
  | Inventory Summary
  |--------------------------------------------------------------------------
  |
  | GET /api/inventory/summary
  |
  */

  async getSummary() {
    const response = await api.get(
      `${INVENTORY_BASE_URL}/summary`,
    )

    return {
      success:
        Boolean(
          response?.data?.success,
        ),

      message:
        response?.data?.message || '',

      data:
        normalizeSummary(
          response?.data?.data,
        ),
    }
  },

  /*
  |--------------------------------------------------------------------------
  | Inventory Options
  |--------------------------------------------------------------------------
  |
  | GET /api/inventory/options
  |
  */

  async getOptions() {
    const response = await api.get(
      `${INVENTORY_BASE_URL}/options`,
    )

    const data =
      response?.data?.data || {}

    return {
      success:
        Boolean(
          response?.data?.success,
        ),

      message:
        response?.data?.message || '',

      data: {
        categories:
          Array.isArray(
            data?.categories,
          )
            ? data.categories
            : [],

        units:
          Array.isArray(
            data?.units,
          )
            ? data.units
            : [],

        warehouse_statuses:
          Array.isArray(
            data?.warehouse_statuses,
          )
            ? data
                .warehouse_statuses
            : [],

        adjustment_types:
          Array.isArray(
            data?.adjustment_types,
          )
            ? data
                .adjustment_types
            : [],
      },
    }
  },

  /*
  |--------------------------------------------------------------------------
  | Raw Material List
  |--------------------------------------------------------------------------
  |
  | GET /api/inventory/raw-materials
  |
  */

  async getRawMaterials(
    filters = {},
  ) {
    const response = await api.get(
      `${INVENTORY_BASE_URL}/raw-materials`,
      {
        params:
          cleanRequestParams(
            filters,
          ),
      },
    )

    return {
      success:
        Boolean(
          response?.data?.success,
        ),

      message:
        response?.data?.message || '',

      data:
        Array.isArray(
          response?.data?.data,
        )
          ? response.data.data
          : [],

      meta:
        normalizePaginationMeta(
          response?.data?.meta,
        ),
    }
  },

  /*
  |--------------------------------------------------------------------------
  | Single Raw Material
  |--------------------------------------------------------------------------
  */

  async getRawMaterial(
    rawMaterialId,
  ) {
    const response = await api.get(
      `${INVENTORY_BASE_URL}/raw-materials/${rawMaterialId}`,
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

  /*
  |--------------------------------------------------------------------------
  | Create Raw Material
  |--------------------------------------------------------------------------
  */

  async createRawMaterial(
    payload,
  ) {
    const response = await api.post(
      `${INVENTORY_BASE_URL}/raw-materials`,
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

  /*
  |--------------------------------------------------------------------------
  | Update Raw Material
  |--------------------------------------------------------------------------
  */

  async updateRawMaterial(
    rawMaterialId,
    payload,
  ) {
    const response = await api.put(
      `${INVENTORY_BASE_URL}/raw-materials/${rawMaterialId}`,
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

  /*
  |--------------------------------------------------------------------------
  | Toggle Raw Material Status
  |--------------------------------------------------------------------------
  */

  async toggleRawMaterialStatus(
    rawMaterialId,
  ) {
    const response = await api.patch(
      `${INVENTORY_BASE_URL}/raw-materials/${rawMaterialId}/status`,
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

  /*
  |--------------------------------------------------------------------------
  | Delete Raw Material
  |--------------------------------------------------------------------------
  */

  async deleteRawMaterial(
    rawMaterialId,
  ) {
    const response = await api.delete(
      `${INVENTORY_BASE_URL}/raw-materials/${rawMaterialId}`,
    )

    return {
      success:
        Boolean(
          response?.data?.success,
        ),

      message:
        response?.data?.message || '',

      data:
        response?.data?.data ?? null,
    }
  },

  /*
  |--------------------------------------------------------------------------
  | Warehouse Stock List
  |--------------------------------------------------------------------------
  */

  async getWarehouseStocks(
    filters = {},
  ) {
    const response = await api.get(
      `${INVENTORY_BASE_URL}/warehouse-stocks`,
      {
        params:
          cleanRequestParams(
            filters,
          ),
      },
    )

    return {
      success:
        Boolean(
          response?.data?.success,
        ),

      message:
        response?.data?.message || '',

      data:
        Array.isArray(
          response?.data?.data,
        )
          ? response.data.data
          : [],

      meta:
        normalizePaginationMeta(
          response?.data?.meta,
        ),
    }
  },

  /*
  |--------------------------------------------------------------------------
  | Single Warehouse Stock
  |--------------------------------------------------------------------------
  */

  async getWarehouseStock(
    rawMaterialId,
  ) {
    const response = await api.get(
      `${INVENTORY_BASE_URL}/warehouse-stocks/${rawMaterialId}`,
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

  /*
  |--------------------------------------------------------------------------
  | Warehouse Adjustment
  |--------------------------------------------------------------------------
  */

  async adjustWarehouseStock(
    rawMaterialId,
    payload,
  ) {
    const response = await api.post(
      `${INVENTORY_BASE_URL}/raw-materials/${rawMaterialId}/warehouse-adjustment`,
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

  /*
  |--------------------------------------------------------------------------
  | Stock Movement History
  |--------------------------------------------------------------------------
  */

  async getStockMovements(
    filters = {},
  ) {
    const response = await api.get(
      `${INVENTORY_BASE_URL}/stock-movements`,
      {
        params:
          cleanRequestParams(
            filters,
          ),
      },
    )

    return {
      success:
        Boolean(
          response?.data?.success,
        ),

      message:
        response?.data?.message || '',

      data:
        Array.isArray(
          response?.data?.data,
        )
          ? response.data.data
          : [],

      meta:
        normalizePaginationMeta(
          response?.data?.meta,
        ),
    }
  },
  /*
|--------------------------------------------------------------------------
| Restaurant Stock List
|--------------------------------------------------------------------------
|
| GET /api/inventory/restaurant-stocks
|
*/

async getRestaurantStocks(
  filters = {},
) {
  const response = await api.get(
    `${INVENTORY_BASE_URL}/restaurant-stocks`,
    {
      params:
        cleanRequestParams(
          filters,
        ),
    },
  )

  return {
    success:
      Boolean(
        response?.data?.success,
      ),

    message:
      response?.data?.message || '',

    data:
      Array.isArray(
        response?.data?.data,
      )
        ? response.data.data
        : [],

    meta:
      normalizePaginationMeta(
        response?.data?.meta,
      ),
  }
},


/*
|--------------------------------------------------------------------------
| Single Restaurant Stock
|--------------------------------------------------------------------------
|
| GET /api/inventory/restaurant-stocks/{rawMaterial}
|
*/

async getRestaurantStock(
  rawMaterialId,
) {
  const response = await api.get(
    `${INVENTORY_BASE_URL}/restaurant-stocks/${rawMaterialId}`,
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


/*
|--------------------------------------------------------------------------
| Stock Transfer History
|--------------------------------------------------------------------------
|
| GET /api/inventory/stock-transfers
|
*/

async getStockTransfers(
  filters = {},
) {
  const response = await api.get(
    `${INVENTORY_BASE_URL}/stock-transfers`,
    {
      params:
        cleanRequestParams(
          filters,
        ),
    },
  )

  return {
    success:
      Boolean(
        response?.data?.success,
      ),

    message:
      response?.data?.message || '',

    data:
      Array.isArray(
        response?.data?.data,
      )
        ? response.data.data
        : [],

    meta:
      normalizePaginationMeta(
        response?.data?.meta,
      ),
  }
},


/*
|--------------------------------------------------------------------------
| Single Stock Transfer
|--------------------------------------------------------------------------
|
| GET /api/inventory/stock-transfers/{stockTransfer}
|
*/

async getStockTransfer(
  stockTransferId,
) {
  const response = await api.get(
    `${INVENTORY_BASE_URL}/stock-transfers/${stockTransferId}`,
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


/*
|--------------------------------------------------------------------------
| Create Stock Transfer
|--------------------------------------------------------------------------
|
| POST /api/inventory/stock-transfers
|
*/

async createStockTransfer(
  payload,
) {
  const response = await api.post(
    `${INVENTORY_BASE_URL}/stock-transfers`,
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
/*
|--------------------------------------------------------------------------
| Recipe Mapping List
|--------------------------------------------------------------------------
|
| GET /api/inventory/recipe-mappings
|
| Returns configured Menu Item + Add-on recipe targets.
|
*/

async getRecipeMappings() {
  const response = await api.get(
    `${INVENTORY_BASE_URL}/recipe-mappings`,
  )

  return {
    success:
      Boolean(
        response?.data?.success,
      ),

    message:
      response?.data?.message || '',

    data:
      Array.isArray(
        response?.data?.data,
      )
        ? response.data.data
        : [],
  }
},


/*
|--------------------------------------------------------------------------
| Unified Recipe Target
|--------------------------------------------------------------------------
|
| GET /api/inventory/recipe-mappings/{targetType}/{targetId}
|
| targetType:
|   menu_item
|   add_on
|
*/

async getRecipeTarget(
  targetType,
  targetId,
) {
  const normalizedTargetType =
    String(
      targetType || '',
    )
      .trim()
      .toLowerCase()
      .replaceAll(
        '-',
        '_',
      )

  const response = await api.get(
    `${INVENTORY_BASE_URL}/recipe-mappings/${normalizedTargetType}/${targetId}`,
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


/*
|--------------------------------------------------------------------------
| Save / Replace Unified Recipe Target
|--------------------------------------------------------------------------
|
| PUT /api/inventory/recipe-mappings
|
| Payload:
|
| {
|   target_type: 'menu_item' | 'add_on',
|   target_id: 1,
|   ingredients: [...]
| }
|
*/

async saveRecipeTarget(
  payload,
) {
  const response = await api.put(
    `${INVENTORY_BASE_URL}/recipe-mappings`,
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


/*
|--------------------------------------------------------------------------
| Delete Unified Recipe Target
|--------------------------------------------------------------------------
|
| DELETE /api/inventory/recipe-mappings/{targetType}/{targetId}
|
| Deletes recipe mapping rows only.
| It does not delete Menu Item / Add-on / Raw Material masters.
|
*/

async deleteRecipeTarget(
  targetType,
  targetId,
) {
  const normalizedTargetType =
    String(
      targetType || '',
    )
      .trim()
      .toLowerCase()
      .replaceAll(
        '-',
        '_',
      )

  const response = await api.delete(
    `${INVENTORY_BASE_URL}/recipe-mappings/${normalizedTargetType}/${targetId}`,
  )

  return {
    success:
      Boolean(
        response?.data?.success,
      ),

    message:
      response?.data?.message || '',

    data:
      response?.data?.data ?? null,
  }
},


/*
|--------------------------------------------------------------------------
| Legacy Menu Item Recipe
|--------------------------------------------------------------------------
|
| GET /api/inventory/recipe-mappings/{menuItem}
|
| Preserved temporarily so the existing Recipe Mapping UI does not break
| before the new unified UI is installed.
|
*/

async getRecipeMapping(
  menuItemId,
) {
  const response = await api.get(
    `${INVENTORY_BASE_URL}/recipe-mappings/${menuItemId}`,
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


/*
|--------------------------------------------------------------------------
| Legacy Save / Replace Menu Item Recipe
|--------------------------------------------------------------------------
|
| PUT /api/inventory/recipe-mappings/{menuItem}
|
| Preserved temporarily for backward compatibility.
|
*/

async saveRecipeMapping(
  menuItemId,
  payload,
) {
  const response = await api.put(
    `${INVENTORY_BASE_URL}/recipe-mappings/${menuItemId}`,
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
  /*
  |--------------------------------------------------------------------------
  | Error Message Resolver
  |--------------------------------------------------------------------------
  */

  getInventoryErrorMessage(
    error,
    fallbackMessage =
      'Unable to complete the inventory request.',
  ) {
    const validationErrors =
      error?.response?.data?.errors

    if (
      validationErrors &&
      typeof validationErrors ===
        'object'
    ) {
      const firstValidationError =
        Object.values(
          validationErrors,
        )
          .flat()
          .find(Boolean)

      if (firstValidationError) {
        return String(
          firstValidationError,
        )
      }
    }

    return (
      error?.response?.data
        ?.message ||
      error?.message ||
      fallbackMessage
    )
  },

  /*
  |--------------------------------------------------------------------------
  | Factories
  |--------------------------------------------------------------------------
  */

  createDefaultSummary,

  createDefaultMeta,
}

export default inventoryService