import { defineStore } from "pinia";
import api from "../services/api";

/**
 * Pagination-এর default structure।
 */
const createPagination = () => ({
  current_page: 1,
  from: null,
  last_page: 1,
  per_page: 10,
  to: null,
  total: 0,
});

/**
 * API value-কে reliable boolean-এ convert করবে।
 */
const normalizeBoolean = (value) => {
  return (
    value === true ||
    value === 1 ||
    value === "1" ||
    value === "true" ||
    value === "on" ||
    value === "yes"
  );
};

/**
 * দুইটি ID একই কি না check করবে।
 */
const sameId = (firstId, secondId) => {
  return Number(firstId) === Number(secondId);
};

/**
 * Laravel API response থেকে collection বের করবে।
 */
const extractCollection = (response) => {
  const responseData = response?.data?.data;

  if (Array.isArray(responseData)) {
    return responseData;
  }

  if (Array.isArray(responseData?.data)) {
    return responseData.data;
  }

  return [];
};

/**
 * Laravel API response থেকে single resource বের করবে।
 */
const extractResource = (response) => {
  const responseData = response?.data?.data;

  if (
    responseData &&
    typeof responseData === "object" &&
    responseData.data !== undefined
  ) {
    return responseData.data;
  }

  return responseData ?? null;
};

/**
 * Empty query parameter remove করবে।
 */
const cleanQuery = (query) => {
  return Object.fromEntries(
    Object.entries(query).filter(([, value]) => {
      return (
        value !== "" &&
        value !== null &&
        value !== undefined
      );
    })
  );
};

export const useMenuManagementStore = defineStore(
  "menuManagement",
  {
    state: () => ({
      /*
      |--------------------------------------------------------------------------
      | Menu Categories
      |--------------------------------------------------------------------------
      */

      categories: [],
      selectedCategory: null,

      categoryFilters: {
        search: "",
        status: "",
        page: 1,
        per_page: 10,
      },

      categoryPagination: createPagination(),

      /*
      |--------------------------------------------------------------------------
      | Menu Items
      |--------------------------------------------------------------------------
      */

      menuItems: [],
      selectedMenuItem: null,

      menuItemFilters: {
        search: "",
        menu_category_id: "",
        item_type: "",
        status: "",
        featured: "",
        page: 1,
        per_page: 10,
      },

      menuItemPagination: createPagination(),

      /*
      |--------------------------------------------------------------------------
      | Variants
      |--------------------------------------------------------------------------
      */

      variants: [],
      selectedVariant: null,

      variantFilters: {
        search: "",
        menu_item_id: "",
        status: "",
        page: 1,
        per_page: 10,
      },

      variantPagination: createPagination(),

      /*
      |--------------------------------------------------------------------------
      | Add-on Categories
      |--------------------------------------------------------------------------
      */

      addOnCategories: [],
      selectedAddOnCategory: null,

      addOnCategoryFilters: {
        search: "",
        status: "",
        page: 1,
        per_page: 10,
      },

      addOnCategoryPagination:
        createPagination(),

      /*
      |--------------------------------------------------------------------------
      | Add-ons
      |--------------------------------------------------------------------------
      */

      addOns: [],
      selectedAddOn: null,

      addOnFilters: {
        search: "",
        add_on_category_id: "",
        status: "",
        page: 1,
        per_page: 10,
      },

      addOnPagination: createPagination(),

      /*
      |--------------------------------------------------------------------------
      | Common Loading State
      |--------------------------------------------------------------------------
      */

      loading: {
        categories: false,
        menuItems: false,
        variants: false,
        addOnCategories: false,
        addOns: false,

        submitting: false,
        deleting: false,
        toggling: false,

        deletingMenuItemId: null,
        togglingMenuItemId: null,
        togglingFeaturedMenuItemId: null,

        togglingCategoryId: null,
        deletingCategoryId: null,

        togglingVariantId: null,
        deletingVariantId: null,

        togglingAddOnCategoryId: null,
        deletingAddOnCategoryId: null,

        togglingAddOnId: null,
        deletingAddOnId: null,
      },

      message: "",
      errorMessage: "",
      validationErrors: {},
    }),

    getters: {
      categoryOptions: (state) => {
        return state.categories.map(
          (category) => ({
            value: category.id,
            label:
              category.category_name ||
              category.name,
          })
        );
      },

      menuItemOptions: (state) => {
        return state.menuItems.map((item) => ({
          value: item.id,
          label: item.menu_name,
        }));
      },

      addOnCategoryOptions: (state) => {
        return state.addOnCategories.map(
          (category) => ({
            value: category.id,
            label:
              category.category_name ||
              category.name,
          })
        );
      },

      itemTypeOptions: () => [
        {
          value: "regular",
          label: "Regular",
        },
        {
          value: "combo",
          label: "Combo",
        },
        {
          value: "set_meal",
          label: "Set Meal",
        },
      ],

      firstValidationError: (state) => {
        return (field) => {
          const errors =
            state.validationErrors[field];

          if (Array.isArray(errors)) {
            return errors[0] || "";
          }

          return errors || "";
        };
      },
    },

    actions: {
      /*
      |--------------------------------------------------------------------------
      | Common Methods
      |--------------------------------------------------------------------------
      */

      clearFeedback() {
        this.message = "";
        this.errorMessage = "";
        this.validationErrors = {};
      },

      handleError(error) {
        const responseData =
          error?.response?.data ?? {};

        this.errorMessage =
          responseData.message ||
          error?.message ||
          "Something went wrong.";

        this.validationErrors =
          responseData.errors &&
          typeof responseData.errors ===
            "object"
            ? responseData.errors
            : {};
      },

      updatePagination(target, response) {
        const meta =
          response?.data?.meta ||
          response?.data?.data?.meta ||
          {};

        this[target] = {
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
      },

      replaceItemInCollection(
        collectionName,
        updatedItem
      ) {
        if (!updatedItem?.id) {
          return;
        }

        const collection =
          this[collectionName];

        if (!Array.isArray(collection)) {
          return;
        }

        const itemIndex =
          collection.findIndex((item) =>
            sameId(item.id, updatedItem.id)
          );

        if (itemIndex === -1) {
          return;
        }

        collection[itemIndex] = {
          ...collection[itemIndex],
          ...updatedItem,
        };
      },

      removeItemFromCollection(
        collectionName,
        id
      ) {
        const collection =
          this[collectionName];

        if (!Array.isArray(collection)) {
          return;
        }

        this[collectionName] =
          collection.filter(
            (item) => !sameId(item.id, id)
          );
      },

      /*
      |--------------------------------------------------------------------------
      | Menu Category Actions
      |--------------------------------------------------------------------------
      */

      async fetchCategories(params = {}) {
        this.loading.categories = true;
        this.clearFeedback();

        try {
          const response = await api.get(
            "/menu-management/menu-categories",
            {
              params: cleanQuery({
                ...this.categoryFilters,
                ...params,
              }),
            }
          );

          this.categories =
            extractCollection(response);

          this.updatePagination(
            "categoryPagination",
            response
          );

          return this.categories;
        } catch (error) {
          this.categories = [];
          this.handleError(error);
          throw error;
        } finally {
          this.loading.categories = false;
        }
      },

      async fetchAllCategories() {
        try {
          const response = await api.get(
            "/menu-management/menu-categories",
            {
              params: {
                per_page: 100,
              },
            }
          );

          this.categories =
            extractCollection(response);

          return this.categories;
        } catch (error) {
          this.handleError(error);
          throw error;
        }
      },

      async createCategory(payload) {
        this.loading.submitting = true;
        this.clearFeedback();

        try {
          const response = await api.post(
            "/menu-management/menu-categories",
            payload
          );

          const category =
            extractResource(response);

          if (category?.id) {
            this.categories.unshift(
              category
            );
          }

          this.message =
            response?.data?.message ||
            "Category created successfully.";

          return category;
        } catch (error) {
          this.handleError(error);
          throw error;
        } finally {
          this.loading.submitting = false;
        }
      },

      async updateCategory(id, payload) {
        this.loading.submitting = true;
        this.clearFeedback();

        try {
          const response = await api.put(
            `/menu-management/menu-categories/${id}`,
            payload
          );

          const category =
            extractResource(response);

          this.replaceItemInCollection(
            "categories",
            category
          );

          this.message =
            response?.data?.message ||
            "Category updated successfully.";

          return category;
        } catch (error) {
          this.handleError(error);
          throw error;
        } finally {
          this.loading.submitting = false;
        }
      },

      async deleteCategory(categoryId) {
        this.loading.deleting = true;
        this.loading.deletingCategoryId =
          categoryId;

        this.clearFeedback();

        try {
          const response = await api.delete(
            `/menu-management/menu-categories/${categoryId}`
          );

          this.removeItemFromCollection(
            "categories",
            categoryId
          );

          this.message =
            response?.data?.message ||
            "Menu category deleted successfully.";

          return true;
        } catch (error) {
          const status =
            error?.response?.status;

          const responseData =
            error?.response?.data ?? {};

          if (status === 422) {
            this.validationErrors =
              responseData.errors || {};

            this.errorMessage =
              responseData.message ||
              "This category cannot be deleted.";
          } else if (status === 404) {
            this.errorMessage =
              "The selected menu category was not found.";
          } else if (status === 403) {
            this.errorMessage =
              "You do not have permission to delete this category.";
          } else {
            this.errorMessage =
              responseData.message ||
              "Failed to delete menu category.";
          }

          throw error;
        } finally {
          this.loading.deleting = false;
          this.loading.deletingCategoryId =
            null;
        }
      },

      async toggleCategoryStatus(
        categoryId
      ) {
        this.loading.toggling = true;
        this.loading.togglingCategoryId =
          categoryId;

        this.clearFeedback();

        try {
          const response = await api.patch(
            `/menu-management/menu-categories/${categoryId}/status`
          );

          const category =
            extractResource(response);

          if (category?.id) {
            category.is_available =
              normalizeBoolean(
                category.is_available
              );

            this.replaceItemInCollection(
              "categories",
              category
            );
          }

          this.message =
            response?.data?.message ||
            "Category availability updated successfully.";

          return category;
        } catch (error) {
          this.handleError(error);
          throw error;
        } finally {
          this.loading.toggling = false;
          this.loading.togglingCategoryId =
            null;
        }
      },

      resetCategoryFilters() {
        this.categoryFilters = {
          search: "",
          status: "",
          page: 1,
          per_page: 10,
        };
      },

      /*
      |--------------------------------------------------------------------------
      | Menu Item Actions
      |--------------------------------------------------------------------------
      */

      async fetchMenuItems(params = {}) {
        this.loading.menuItems = true;
        this.clearFeedback();

        try {
          const response = await api.get(
            "/menu-management/menu-items",
            {
              params: cleanQuery({
                ...this.menuItemFilters,
                ...params,
              }),
            }
          );

          this.menuItems =
            extractCollection(response).map(
              (item) => ({
                ...item,

                is_available:
                  normalizeBoolean(
                    item.is_available
                  ),

                is_featured:
                  normalizeBoolean(
                    item.is_featured
                  ),
              })
            );

          this.updatePagination(
            "menuItemPagination",
            response
          );

          return this.menuItems;
        } catch (error) {
          this.menuItems = [];
          this.handleError(error);
          throw error;
        } finally {
          this.loading.menuItems = false;
        }
      },

      async fetchAllMenuItems() {
        try {
          const response = await api.get(
            "/menu-management/menu-items",
            {
              params: {
                per_page: 100,
              },
            }
          );

          this.menuItems =
            extractCollection(response).map(
              (item) => ({
                ...item,

                is_available:
                  normalizeBoolean(
                    item.is_available
                  ),

                is_featured:
                  normalizeBoolean(
                    item.is_featured
                  ),
              })
            );

          return this.menuItems;
        } catch (error) {
          this.handleError(error);
          throw error;
        }
      },

      async createMenuItem(payload) {
        this.loading.submitting = true;
        this.clearFeedback();

        try {
          const response = await api.post(
            "/menu-management/menu-items",
            payload
          );

          const menuItem =
            extractResource(response);

          if (menuItem?.id) {
            const normalizedItem = {
              ...menuItem,

              is_available:
                normalizeBoolean(
                  menuItem.is_available
                ),

              is_featured:
                normalizeBoolean(
                  menuItem.is_featured
                ),
            };

            this.menuItems.unshift(
              normalizedItem
            );

            this.menuItemPagination.total +=
              1;
          }

          this.message =
            response?.data?.message ||
            "Menu item created successfully.";

          return menuItem;
        } catch (error) {
          this.handleError(error);
          throw error;
        } finally {
          this.loading.submitting = false;
        }
      },

      async updateMenuItem(id, payload) {
        this.loading.submitting = true;
        this.clearFeedback();

        try {
          let response;

          if (payload instanceof FormData) {
            if (!payload.has("_method")) {
              payload.append(
                "_method",
                "PUT"
              );
            }

            response = await api.post(
              `/menu-management/menu-items/${id}`,
              payload
            );
          } else {
            response = await api.put(
              `/menu-management/menu-items/${id}`,
              payload
            );
          }

          const menuItem =
            extractResource(response);

          if (menuItem?.id) {
            const normalizedItem = {
              ...menuItem,

              is_available:
                normalizeBoolean(
                  menuItem.is_available
                ),

              is_featured:
                normalizeBoolean(
                  menuItem.is_featured
                ),
            };

            this.replaceItemInCollection(
              "menuItems",
              normalizedItem
            );

            this.selectedMenuItem =
              normalizedItem;
          }

          this.message =
            response?.data?.message ||
            "Menu item updated successfully.";

          return menuItem;
        } catch (error) {
          this.handleError(error);
          throw error;
        } finally {
          this.loading.submitting = false;
        }
      },

      async deleteMenuItem(id) {
        this.loading.deleting = true;
        this.loading.deletingMenuItemId =
          id;

        this.clearFeedback();

        try {
          const response = await api.delete(
            `/menu-management/menu-items/${id}`
          );

          this.removeItemFromCollection(
            "menuItems",
            id
          );

          if (
            this.selectedMenuItem &&
            sameId(
              this.selectedMenuItem.id,
              id
            )
          ) {
            this.selectedMenuItem = null;
          }

          this.menuItemPagination.total =
            Math.max(
              0,
              this.menuItemPagination.total -
                1
            );

          this.message =
            response?.data?.message ||
            "Menu item deleted successfully.";

          return true;
        } catch (error) {
          this.handleError(error);
          throw error;
        } finally {
          this.loading.deleting = false;

          this.loading.deletingMenuItemId =
            null;
        }
      },

      async toggleMenuItemStatus(id) {
        this.loading.toggling = true;
        this.loading.togglingMenuItemId =
          id;

        this.clearFeedback();

        try {
          const response = await api.patch(
            `/menu-management/menu-items/${id}/status`
          );

          const menuItem =
            extractResource(response);

          if (menuItem?.id) {
            const normalizedItem = {
              ...menuItem,

              is_available:
                normalizeBoolean(
                  menuItem.is_available
                ),

              is_featured:
                normalizeBoolean(
                  menuItem.is_featured
                ),
            };

            this.replaceItemInCollection(
              "menuItems",
              normalizedItem
            );
          }

          this.message =
            response?.data?.message ||
            "Menu item availability updated successfully.";

          return menuItem;
        } catch (error) {
          this.handleError(error);
          throw error;
        } finally {
          this.loading.toggling = false;

          this.loading.togglingMenuItemId =
            null;
        }
      },

      async toggleMenuItemFeatured(id) {
        this.loading.toggling = true;

        this.loading
          .togglingFeaturedMenuItemId = id;

        this.clearFeedback();

        try {
          const response = await api.patch(
            `/menu-management/menu-items/${id}/featured`
          );

          const menuItem =
            extractResource(response);

          if (menuItem?.id) {
            const normalizedItem = {
              ...menuItem,

              is_available:
                normalizeBoolean(
                  menuItem.is_available
                ),

              is_featured:
                normalizeBoolean(
                  menuItem.is_featured
                ),
            };

            this.replaceItemInCollection(
              "menuItems",
              normalizedItem
            );
          }

          this.message =
            response?.data?.message ||
            "Featured status updated successfully.";

          return menuItem;
        } catch (error) {
          this.handleError(error);
          throw error;
        } finally {
          this.loading.toggling = false;

          this.loading
            .togglingFeaturedMenuItemId =
            null;
        }
      },

      resetMenuItemFilters() {
        this.menuItemFilters = {
          search: "",
          menu_category_id: "",
          item_type: "",
          status: "",
          featured: "",
          page: 1,
          per_page: 10,
        };
      },

      /*
      |--------------------------------------------------------------------------
      | Variant Actions
      |--------------------------------------------------------------------------
      */

      async fetchVariants(params = {}) {
        this.loading.variants = true;
        this.clearFeedback();

        try {
          const response = await api.get(
            "/menu-management/menu-variants",
            {
              params: cleanQuery({
                ...this.variantFilters,
                ...params,
              }),
            }
          );

          this.variants =
            extractCollection(response);

          this.updatePagination(
            "variantPagination",
            response
          );

          return this.variants;
        } catch (error) {
          this.variants = [];
          this.handleError(error);
          throw error;
        } finally {
          this.loading.variants = false;
        }
      },

      async createVariant(payload) {
        this.loading.submitting = true;
        this.clearFeedback();

        try {
          const response = await api.post(
            "/menu-management/menu-variants",
            payload
          );

          const variant =
            extractResource(response);

          if (variant?.id) {
            this.variants.unshift(variant);
          }

          this.message =
            response?.data?.message ||
            "Variant created successfully.";

          return variant;
        } catch (error) {
          this.handleError(error);
          throw error;
        } finally {
          this.loading.submitting = false;
        }
      },

      async updateVariant(id, payload) {
        this.loading.submitting = true;
        this.clearFeedback();

        try {
          const response = await api.put(
            `/menu-management/menu-variants/${id}`,
            payload
          );

          const variant =
            extractResource(response);

          this.replaceItemInCollection(
            "variants",
            variant
          );

          this.message =
            response?.data?.message ||
            "Variant updated successfully.";

          return variant;
        } catch (error) {
          this.handleError(error);
          throw error;
        } finally {
          this.loading.submitting = false;
        }
      },

      async deleteVariant(id) {
        this.loading.deleting = true;
        this.loading.deletingVariantId =
          id;

        this.clearFeedback();

        try {
          const response = await api.delete(
            `/menu-management/menu-variants/${id}`
          );

          this.removeItemFromCollection(
            "variants",
            id
          );

          this.message =
            response?.data?.message ||
            "Variant deleted successfully.";

          return true;
        } catch (error) {
          this.handleError(error);
          throw error;
        } finally {
          this.loading.deleting = false;
          this.loading.deletingVariantId =
            null;
        }
      },

      async toggleVariantStatus(id) {
        this.loading.toggling = true;
        this.loading.togglingVariantId =
          id;

        this.clearFeedback();

        try {
          const response = await api.patch(
            `/menu-management/menu-variants/${id}/status`
          );

          const variant =
            extractResource(response);

          this.replaceItemInCollection(
            "variants",
            variant
          );

          this.message =
            response?.data?.message ||
            "Variant status updated successfully.";

          return variant;
        } catch (error) {
          this.handleError(error);
          throw error;
        } finally {
          this.loading.toggling = false;
          this.loading.togglingVariantId =
            null;
        }
      },

      /*
      |--------------------------------------------------------------------------
      | Add-on Category Actions
      |--------------------------------------------------------------------------
      */

      async fetchAddOnCategories(
        params = {}
      ) {
        this.loading.addOnCategories =
          true;

        this.clearFeedback();

        try {
          const response = await api.get(
            "/menu-management/add-on-categories",
            {
              params: cleanQuery({
                ...this.addOnCategoryFilters,
                ...params,
              }),
            }
          );

          this.addOnCategories =
            extractCollection(response);

          this.updatePagination(
            "addOnCategoryPagination",
            response
          );

          return this.addOnCategories;
        } catch (error) {
          this.addOnCategories = [];
          this.handleError(error);
          throw error;
        } finally {
          this.loading.addOnCategories =
            false;
        }
      },

      async fetchAllAddOnCategories() {
        try {
          const response = await api.get(
            "/menu-management/add-on-categories",
            {
              params: {
                per_page: 100,
              },
            }
          );

          this.addOnCategories =
            extractCollection(response);

          return this.addOnCategories;
        } catch (error) {
          this.handleError(error);
          throw error;
        }
      },

      async createAddOnCategory(
        payload
      ) {
        this.loading.submitting = true;
        this.clearFeedback();

        try {
          const response = await api.post(
            "/menu-management/add-on-categories",
            payload
          );

          const addOnCategory =
            extractResource(response);

          if (addOnCategory?.id) {
            this.addOnCategories.unshift(
              addOnCategory
            );
          }

          this.message =
            response?.data?.message ||
            "Add-on category created successfully.";

          return addOnCategory;
        } catch (error) {
          this.handleError(error);
          throw error;
        } finally {
          this.loading.submitting = false;
        }
      },

      async updateAddOnCategory(
        id,
        payload
      ) {
        this.loading.submitting = true;
        this.clearFeedback();

        try {
          const response = await api.put(
            `/menu-management/add-on-categories/${id}`,
            payload
          );

          const addOnCategory =
            extractResource(response);

          this.replaceItemInCollection(
            "addOnCategories",
            addOnCategory
          );

          this.message =
            response?.data?.message ||
            "Add-on category updated successfully.";

          return addOnCategory;
        } catch (error) {
          this.handleError(error);
          throw error;
        } finally {
          this.loading.submitting = false;
        }
      },

      async deleteAddOnCategory(id) {
        this.loading.deleting = true;

        this.loading
          .deletingAddOnCategoryId = id;

        this.clearFeedback();

        try {
          const response = await api.delete(
            `/menu-management/add-on-categories/${id}`
          );

          this.removeItemFromCollection(
            "addOnCategories",
            id
          );

          this.message =
            response?.data?.message ||
            "Add-on category deleted successfully.";

          return true;
        } catch (error) {
          this.handleError(error);
          throw error;
        } finally {
          this.loading.deleting = false;

          this.loading
            .deletingAddOnCategoryId =
            null;
        }
      },

      async toggleAddOnCategoryStatus(
        id
      ) {
        this.loading.toggling = true;

        this.loading
          .togglingAddOnCategoryId = id;

        this.clearFeedback();

        try {
          const response = await api.patch(
            `/menu-management/add-on-categories/${id}/status`
          );

          const addOnCategory =
            extractResource(response);

          this.replaceItemInCollection(
            "addOnCategories",
            addOnCategory
          );

          this.message =
            response?.data?.message ||
            "Add-on category status updated successfully.";

          return addOnCategory;
        } catch (error) {
          this.handleError(error);
          throw error;
        } finally {
          this.loading.toggling = false;

          this.loading
            .togglingAddOnCategoryId =
            null;
        }
      },

      /*
      |--------------------------------------------------------------------------
      | Add-on Actions
      |--------------------------------------------------------------------------
      */

      async fetchAddOns(params = {}) {
        this.loading.addOns = true;
        this.clearFeedback();

        try {
          const response = await api.get(
            "/menu-management/add-ons",
            {
              params: cleanQuery({
                ...this.addOnFilters,
                ...params,
              }),
            }
          );

          this.addOns =
            extractCollection(response);

          this.updatePagination(
            "addOnPagination",
            response
          );

          return this.addOns;
        } catch (error) {
          this.addOns = [];
          this.handleError(error);
          throw error;
        } finally {
          this.loading.addOns = false;
        }
      },

      async createAddOn(payload) {
        this.loading.submitting = true;
        this.clearFeedback();

        try {
          const response = await api.post(
            "/menu-management/add-ons",
            payload
          );

          const addOn =
            extractResource(response);

          if (addOn?.id) {
            this.addOns.unshift(addOn);
          }

          this.message =
            response?.data?.message ||
            "Add-on created successfully.";

          return addOn;
        } catch (error) {
          this.handleError(error);
          throw error;
        } finally {
          this.loading.submitting = false;
        }
      },

      async updateAddOn(id, payload) {
        this.loading.submitting = true;
        this.clearFeedback();

        try {
          const response = await api.put(
            `/menu-management/add-ons/${id}`,
            payload
          );

          const addOn =
            extractResource(response);

          this.replaceItemInCollection(
            "addOns",
            addOn
          );

          this.message =
            response?.data?.message ||
            "Add-on updated successfully.";

          return addOn;
        } catch (error) {
          this.handleError(error);
          throw error;
        } finally {
          this.loading.submitting = false;
        }
      },

      async deleteAddOn(id) {
        this.loading.deleting = true;
        this.loading.deletingAddOnId = id;

        this.clearFeedback();

        try {
          const response = await api.delete(
            `/menu-management/add-ons/${id}`
          );

          this.removeItemFromCollection(
            "addOns",
            id
          );

          this.message =
            response?.data?.message ||
            "Add-on deleted successfully.";

          return true;
        } catch (error) {
          this.handleError(error);
          throw error;
        } finally {
          this.loading.deleting = false;
          this.loading.deletingAddOnId =
            null;
        }
      },

      async toggleAddOnStatus(id) {
        this.loading.toggling = true;
        this.loading.togglingAddOnId = id;

        this.clearFeedback();

        try {
          const response = await api.patch(
            `/menu-management/add-ons/${id}/status`
          );

          const addOn =
            extractResource(response);

          this.replaceItemInCollection(
            "addOns",
            addOn
          );

          this.message =
            response?.data?.message ||
            "Add-on status updated successfully.";

          return addOn;
        } catch (error) {
          this.handleError(error);
          throw error;
        } finally {
          this.loading.toggling = false;
          this.loading.togglingAddOnId =
            null;
        }
      },

      /*
      |--------------------------------------------------------------------------
      | Reset Store
      |--------------------------------------------------------------------------
      */

      resetStore() {
        this.$reset();
      },
    },
  }
);