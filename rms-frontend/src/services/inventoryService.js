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

    total_raw_materials:
      0,

    available_count:
      0,

    limited_count:
      0,

    out_of_stock_count:
      0,

    low_stock_alert_count:
      0,

    total_stock_value:
      0,

    total_stock_value_formatted:
      '৳ 0.00',

    has_low_stock_alert:
      false,

  }
}


/*
|--------------------------------------------------------------------------
| Default Pagination Meta
|--------------------------------------------------------------------------
*/

function createDefaultMeta() {

  return {

    current_page:
      1,

    last_page:
      1,

    per_page:
      10,

    total:
      0,

    from:
      null,

    to:
      null,

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

    Object.entries(
      params,
    ).filter(
      ([, value]) => {

        return (
          value !== undefined
          &&
          value !== null
          &&
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
      Number(
        meta?.current_page,
      )
      ||
      1,

    last_page:
      Number(
        meta?.last_page,
      )
      ||
      1,

    per_page:
      Number(
        meta?.per_page,
      )
      ||
      10,

    total:
      Number(
        meta?.total,
      )
      ||
      0,

    from:
      meta?.from
      ??
      null,

    to:
      meta?.to
      ??
      null,

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
      )
      ||
      0,

    available_count:
      Number(
        summary?.available_count,
      )
      ||
      0,

    limited_count:
      Number(
        summary?.limited_count,
      )
      ||
      0,

    out_of_stock_count:
      Number(
        summary?.out_of_stock_count,
      )
      ||
      0,

    low_stock_alert_count:
      Number(
        summary?.low_stock_alert_count,
      )
      ||
      0,

    total_stock_value:
      Number(
        summary?.total_stock_value,
      )
      ||
      0,

    total_stock_value_formatted:
      summary
        ?.total_stock_value_formatted
      ||
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
| Normalize Recipe Target Type
|--------------------------------------------------------------------------
*/

function normalizeRecipeTargetType(
  targetType,
) {

  return String(
    targetType
    ||
    '',
  )
    .trim()
    .toLowerCase()
    .replaceAll(
      '-',
      '_',
    )
}


/*
|--------------------------------------------------------------------------
| Normalize Positive ID
|--------------------------------------------------------------------------
*/

function normalizePositiveId(
  value,
  fieldName = 'ID',
) {

  const id =
    Number(
      value,
    )


  if (
    !Number.isInteger(
      id,
    )
    ||
    id <= 0
  ) {

    throw new Error(
      `Invalid ${fieldName}.`,
    )
  }


  return id
}


/*
|--------------------------------------------------------------------------
| Normalize Optional Variant ID
|--------------------------------------------------------------------------
*/

function normalizeVariantId(
  value,
) {

  if (
    value === null
    ||
    value === undefined
    ||
    value === ''
  ) {

    return null
  }


  const id =
    Number(
      value,
    )


  if (
    !Number.isInteger(
      id,
    )
    ||
    id <= 0
  ) {

    throw new Error(
      'Invalid variant ID.',
    )
  }


  return id
}


/*
|--------------------------------------------------------------------------
| Normalize Recipe Payload
|--------------------------------------------------------------------------
*/

function normalizeRecipePayload(
  payload = {},
) {

  const targetType =
    normalizeRecipeTargetType(
      payload?.target_type,
    )


  if (
    ![
      'menu_item',
      'add_on',
    ].includes(
      targetType,
    )
  ) {

    throw new Error(
      'Invalid recipe target type.',
    )
  }


  const targetId =
    normalizePositiveId(
      payload?.target_id,
      'recipe target ID',
    )


  const variantId =
    targetType ===
    'menu_item'

      ? normalizeVariantId(
          payload?.variant_id,
        )

      : null


  const ingredients =
    Array.isArray(
      payload?.ingredients,
    )
      ? payload.ingredients
      : []


  return {

    ...payload,

    target_type:
      targetType,

    target_id:
      targetId,

    variant_id:
      variantId,

    ingredients:
      ingredients.map(
        (
          ingredient,
        ) => {

          return {

            ...ingredient,

            raw_material_id:
              normalizePositiveId(
                ingredient?.raw_material_id,
                'raw material ID',
              ),

            quantity:
              Number(
                ingredient?.quantity,
              ),

            notes:
              ingredient?.notes
              !== null
              &&
              ingredient?.notes
              !== undefined
                ? String(
                    ingredient.notes,
                  ).trim()
                  ||
                  null
                : null,

          }

        },
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
  */

  async getSummary() {

    const response =
      await api.get(
        `${INVENTORY_BASE_URL}/summary`,
      )


    return {

      success:
        Boolean(
          response?.data?.success,
        ),

      message:
        response?.data?.message
        ||
        '',

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
  */

  async getOptions() {

    const response =
      await api.get(
        `${INVENTORY_BASE_URL}/options`,
      )


    const data =
      response?.data?.data
      ||
      {}


    return {

      success:
        Boolean(
          response?.data?.success,
        ),

      message:
        response?.data?.message
        ||
        '',

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
            ? data.warehouse_statuses
            : [],

        adjustment_types:
          Array.isArray(
            data?.adjustment_types,
          )
            ? data.adjustment_types
            : [],

      },

    }

  },


  /*
  |--------------------------------------------------------------------------
  | Raw Material List
  |--------------------------------------------------------------------------
  */

  async getRawMaterials(
    filters = {},
  ) {

    const response =
      await api.get(
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
        response?.data?.message
        ||
        '',

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

    const id =
      normalizePositiveId(
        rawMaterialId,
        'raw material ID',
      )


    const response =
      await api.get(
        `${INVENTORY_BASE_URL}/raw-materials/${id}`,
      )


    return {

      success:
        Boolean(
          response?.data?.success,
        ),

      message:
        response?.data?.message
        ||
        '',

      data:
        response?.data?.data
        ||
        null,

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

    const response =
      await api.post(
        `${INVENTORY_BASE_URL}/raw-materials`,
        payload,
      )


    return {

      success:
        Boolean(
          response?.data?.success,
        ),

      message:
        response?.data?.message
        ||
        '',

      data:
        response?.data?.data
        ||
        null,

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

    const id =
      normalizePositiveId(
        rawMaterialId,
        'raw material ID',
      )


    const response =
      await api.put(
        `${INVENTORY_BASE_URL}/raw-materials/${id}`,
        payload,
      )


    return {

      success:
        Boolean(
          response?.data?.success,
        ),

      message:
        response?.data?.message
        ||
        '',

      data:
        response?.data?.data
        ||
        null,

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

    const id =
      normalizePositiveId(
        rawMaterialId,
        'raw material ID',
      )


    const response =
      await api.patch(
        `${INVENTORY_BASE_URL}/raw-materials/${id}/status`,
      )


    return {

      success:
        Boolean(
          response?.data?.success,
        ),

      message:
        response?.data?.message
        ||
        '',

      data:
        response?.data?.data
        ||
        null,

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

    const id =
      normalizePositiveId(
        rawMaterialId,
        'raw material ID',
      )


    const response =
      await api.delete(
        `${INVENTORY_BASE_URL}/raw-materials/${id}`,
      )


    return {

      success:
        Boolean(
          response?.data?.success,
        ),

      message:
        response?.data?.message
        ||
        '',

      data:
        response?.data?.data
        ??
        null,

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

    const response =
      await api.get(
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
        response?.data?.message
        ||
        '',

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

    const id =
      normalizePositiveId(
        rawMaterialId,
        'raw material ID',
      )


    const response =
      await api.get(
        `${INVENTORY_BASE_URL}/warehouse-stocks/${id}`,
      )


    return {

      success:
        Boolean(
          response?.data?.success,
        ),

      message:
        response?.data?.message
        ||
        '',

      data:
        response?.data?.data
        ||
        null,

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

    const id =
      normalizePositiveId(
        rawMaterialId,
        'raw material ID',
      )


    const response =
      await api.post(
        `${INVENTORY_BASE_URL}/raw-materials/${id}/warehouse-adjustment`,
        payload,
      )


    return {

      success:
        Boolean(
          response?.data?.success,
        ),

      message:
        response?.data?.message
        ||
        '',

      data:
        response?.data?.data
        ||
        null,

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

    const response =
      await api.get(
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
        response?.data?.message
        ||
        '',

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
  */

  async getRestaurantStocks(
    filters = {},
  ) {

    const response =
      await api.get(
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
        response?.data?.message
        ||
        '',

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
  */

  async getRestaurantStock(
    rawMaterialId,
  ) {

    const id =
      normalizePositiveId(
        rawMaterialId,
        'raw material ID',
      )


    const response =
      await api.get(
        `${INVENTORY_BASE_URL}/restaurant-stocks/${id}`,
      )


    return {

      success:
        Boolean(
          response?.data?.success,
        ),

      message:
        response?.data?.message
        ||
        '',

      data:
        response?.data?.data
        ||
        null,

    }

  },


  /*
  |--------------------------------------------------------------------------
  | Stock Transfer History
  |--------------------------------------------------------------------------
  */

  async getStockTransfers(
    filters = {},
  ) {

    const response =
      await api.get(
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
        response?.data?.message
        ||
        '',

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
  */

  async getStockTransfer(
    stockTransferId,
  ) {

    const id =
      normalizePositiveId(
        stockTransferId,
        'stock transfer ID',
      )


    const response =
      await api.get(
        `${INVENTORY_BASE_URL}/stock-transfers/${id}`,
      )


    return {

      success:
        Boolean(
          response?.data?.success,
        ),

      message:
        response?.data?.message
        ||
        '',

      data:
        response?.data?.data
        ||
        null,

    }

  },


  /*
  |--------------------------------------------------------------------------
  | Create Stock Transfer
  |--------------------------------------------------------------------------
  */

  async createStockTransfer(
    payload,
  ) {

    const response =
      await api.post(
        `${INVENTORY_BASE_URL}/stock-transfers`,
        payload,
      )


    return {

      success:
        Boolean(
          response?.data?.success,
        ),

      message:
        response?.data?.message
        ||
        '',

      data:
        response?.data?.data
        ||
        null,

    }

  },


  /*
  |--------------------------------------------------------------------------
  | Recipe Mapping List
  |--------------------------------------------------------------------------
  |
  | GET /api/inventory/recipe-mappings
  |
  | Returns:
  |
  | - menu item recipes
  | - menu item variant recipes
  | - add-on recipes
  |
  */

  async getRecipeMappings() {

    const response =
      await api.get(
        `${INVENTORY_BASE_URL}/recipe-mappings`,
      )


    return {

      success:
        Boolean(
          response?.data?.success,
        ),

      message:
        response?.data?.message
        ||
        '',

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
  | GET:
  |
  | /api/inventory/recipe-mappings/menu_item/5
  |
  | /api/inventory/recipe-mappings/menu_item/5?variant_id=2
  |
  | /api/inventory/recipe-mappings/add_on/3
  |
  */

  async getRecipeTarget(
    targetType,
    targetId,
    variantId = null,
  ) {

    const normalizedTargetType =
      normalizeRecipeTargetType(
        targetType,
      )


    if (
      ![
        'menu_item',
        'add_on',
      ].includes(
        normalizedTargetType,
      )
    ) {

      throw new Error(
        'Invalid recipe target type.',
      )
    }


    const normalizedTargetId =
      normalizePositiveId(
        targetId,
        'recipe target ID',
      )


    const normalizedVariantId =
      normalizedTargetType ===
      'menu_item'

        ? normalizeVariantId(
            variantId,
          )

        : null


    const params = {}


    if (
      normalizedVariantId !== null
    ) {

      params.variant_id =
        normalizedVariantId

    }


    const response =
      await api.get(

        `${INVENTORY_BASE_URL}/recipe-mappings/${normalizedTargetType}/${normalizedTargetId}`,

        {
          params,
        },

      )


    return {

      success:
        Boolean(
          response?.data?.success,
        ),

      message:
        response?.data?.message
        ||
        '',

      data:
        response?.data?.data
        ||
        null,

    }

  },


  /*
  |--------------------------------------------------------------------------
  | Save / Replace Unified Recipe Target
  |--------------------------------------------------------------------------
  |
  | PUT /api/inventory/recipe-mappings
  |
  */

  async saveRecipeTarget(
    payload = {},
  ) {

    const requestPayload =
      normalizeRecipePayload(
        payload,
      )


    const response =
      await api.put(
        `${INVENTORY_BASE_URL}/recipe-mappings`,
        requestPayload,
      )


    return {

      success:
        Boolean(
          response?.data?.success,
        ),

      message:
        response?.data?.message
        ||
        '',

      data:
        response?.data?.data
        ||
        null,

    }

  },


  /*
  |--------------------------------------------------------------------------
  | Delete Unified Recipe Target
  |--------------------------------------------------------------------------
  |
  | DELETE:
  |
  | Direct menu item:
  | /recipe-mappings/menu_item/5
  |
  | Variant:
  | /recipe-mappings/menu_item/5?variant_id=2
  |
  | Add-on:
  | /recipe-mappings/add_on/3
  |
  */

  async deleteRecipeTarget(
    targetType,
    targetId,
    variantId = null,
  ) {

    const normalizedTargetType =
      normalizeRecipeTargetType(
        targetType,
      )


    if (
      ![
        'menu_item',
        'add_on',
      ].includes(
        normalizedTargetType,
      )
    ) {

      throw new Error(
        'Invalid recipe target type.',
      )
    }


    const normalizedTargetId =
      normalizePositiveId(
        targetId,
        'recipe target ID',
      )


    const normalizedVariantId =
      normalizedTargetType ===
      'menu_item'

        ? normalizeVariantId(
            variantId,
          )

        : null


    const params = {}


    if (
      normalizedVariantId !== null
    ) {

      params.variant_id =
        normalizedVariantId

    }


    const response =
      await api.delete(

        `${INVENTORY_BASE_URL}/recipe-mappings/${normalizedTargetType}/${normalizedTargetId}`,

        {
          params,
        },

      )


    return {

      success:
        Boolean(
          response?.data?.success,
        ),

      message:
        response?.data?.message
        ||
        '',

      data:
        response?.data?.data
        ??
        null,

    }

  },


  /*
  |--------------------------------------------------------------------------
  | Legacy Menu Item Recipe - Show
  |--------------------------------------------------------------------------
  */

  async getRecipeMapping(
    menuItemId,
    variantId = null,
  ) {

    const normalizedMenuItemId =
      normalizePositiveId(
        menuItemId,
        'menu item ID',
      )


    const normalizedVariantId =
      normalizeVariantId(
        variantId,
      )


    const params = {}


    if (
      normalizedVariantId !== null
    ) {

      params.variant_id =
        normalizedVariantId

    }


    const response =
      await api.get(

        `${INVENTORY_BASE_URL}/recipe-mappings/${normalizedMenuItemId}`,

        {
          params,
        },

      )


    return {

      success:
        Boolean(
          response?.data?.success,
        ),

      message:
        response?.data?.message
        ||
        '',

      data:
        response?.data?.data
        ||
        null,

    }

  },


  /*
  |--------------------------------------------------------------------------
  | Legacy Menu Item Recipe - Save
  |--------------------------------------------------------------------------
  */

  async saveRecipeMapping(
    menuItemId,
    payload = {},
    variantId = null,
  ) {

    const normalizedMenuItemId =
      normalizePositiveId(
        menuItemId,
        'menu item ID',
      )


    const normalizedVariantId =
      normalizeVariantId(
        variantId
        ??
        payload?.variant_id,
      )


    const requestPayload = {

      ...payload,

      target_type:
        'menu_item',

      target_id:
        normalizedMenuItemId,

      variant_id:
        normalizedVariantId,

      ingredients:
        Array.isArray(
          payload?.ingredients,
        )
          ? payload.ingredients
          : [],

    }


    const response =
      await api.put(

        `${INVENTORY_BASE_URL}/recipe-mappings/${normalizedMenuItemId}`,

        requestPayload,

      )


    return {

      success:
        Boolean(
          response?.data?.success,
        ),

      message:
        response?.data?.message
        ||
        '',

      data:
        response?.data?.data
        ||
        null,

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
      error
        ?.response
        ?.data
        ?.errors


    if (
      validationErrors
      &&
      typeof validationErrors ===
        'object'
    ) {

      const firstValidationError =
        Object.values(
          validationErrors,
        )
          .flat()
          .find(
            Boolean,
          )


      if (
        firstValidationError
      ) {

        return String(
          firstValidationError,
        )

      }

    }


    return (

      error
        ?.response
        ?.data
        ?.message

      ||

      error?.message

      ||

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