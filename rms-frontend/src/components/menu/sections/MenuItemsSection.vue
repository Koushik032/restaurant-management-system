<script setup>
import {
  onMounted,
  ref,
} from "vue";

import ConfirmDeleteModal from "@/components/common/ConfirmDeleteModal.vue";
import MenuItemModal from "@/components/menu/MenuItemModal.vue";

import {
  normalizeBoolean,
  useCrudResource,
} from "@/composables/useCrudResource";

import {
  resolveMediaUrl,
} from "@/utils/mediaUrl";

const itemCrud =
  useCrudResource("menuItems");

const categoryCrud =
  useCrudResource("categories");

const {
  items: menuItems,
  filters,
  pagination,
  loading,
  message,
  errorMessage,
  validationErrors,
  clearFeedback,
  fetchItems,
  createItem,
  updateItem,
  deleteItem,
  toggleItem,
  resetFilters,
} = itemCrud;

const categories =
  categoryCrud.items;

const showMenuItemModal =
  ref(false);

const selectedMenuItem =
  ref(null);

const showDeleteModal =
  ref(false);

const itemToDelete =
  ref(null);

/*
|--------------------------------------------------------------------------
| Image URL
|--------------------------------------------------------------------------
|
| image_url অথবা image_path যেটাই backend থেকে
| পাওয়া যাক, resolveMediaUrl() correct Laravel
| storage URL তৈরি করবে।
|
*/

const getItemImage = (item) => {
  if (!item) {
    return "";
  }

  return resolveMediaUrl(
    item.image_url ||
      item.image_path
  );
};

/*
|--------------------------------------------------------------------------
| Load Menu Items
|--------------------------------------------------------------------------
*/

const loadMenuItems = async (
  preserveFeedback = false
) => {
  try {
    await fetchItems(
      {},
      {
        preserveFeedback,
      }
    );
  } catch (error) {
    console.error(
      "Menu item loading failed:",
      error?.response?.data ||
        error
    );
  }
};

/*
|--------------------------------------------------------------------------
| Load Categories
|--------------------------------------------------------------------------
*/

const loadCategories =
  async () => {
    try {
      categories.value =
        await categoryCrud.fetchAll({
          status: "available",
        });
    } catch (error) {
      console.error(
        "Category loading failed:",
        error?.response?.data ||
          error
      );
    }
  };

/*
|--------------------------------------------------------------------------
| Filters
|--------------------------------------------------------------------------
*/

const searchItems = async () => {
  filters.page = 1;

  await loadMenuItems();
};

const resetItemFilters =
  async () => {
    resetFilters();

    await loadMenuItems();
  };

/*
|--------------------------------------------------------------------------
| Pagination
|--------------------------------------------------------------------------
*/

const changePage = async (
  page
) => {
  const nextPage =
    Number(page);

  if (
    nextPage < 1 ||
    nextPage >
      Number(
        pagination.last_page ?? 1
      ) ||
    loading.list
  ) {
    return;
  }

  filters.page = nextPage;

  await loadMenuItems();
};

/*
|--------------------------------------------------------------------------
| Create / Edit Modal
|--------------------------------------------------------------------------
*/

const openCreateModal = () => {
  clearFeedback();

  selectedMenuItem.value =
    null;

  showMenuItemModal.value =
    true;
};

const openEditModal = (
  item
) => {
  if (!item?.id) {
    return;
  }

  clearFeedback();

  selectedMenuItem.value = {
    ...item,
  };

  showMenuItemModal.value =
    true;
};

const closeMenuItemModal =
  () => {
    if (
      loading.submitting
    ) {
      return;
    }

    showMenuItemModal.value =
      false;

    selectedMenuItem.value =
      null;
  };

/*
|--------------------------------------------------------------------------
| Save Menu Item
|--------------------------------------------------------------------------
*/

const saveMenuItem = async (
  payload
) => {
  try {
    if (
      selectedMenuItem.value?.id
    ) {
      await updateItem(
        selectedMenuItem.value.id,
        payload
      );
    } else {
      await createItem(
        payload
      );
    }

    showMenuItemModal.value =
      false;

    selectedMenuItem.value =
      null;

    /*
     * Reload করব যাতে fresh image_url এবং
     * image_path list-এ পাওয়া যায়।
     */
    await loadMenuItems(true);
  } catch (error) {
    console.error(
      "Menu item save failed:",
      error?.response?.data ||
        error
    );
  }
};

/*
|--------------------------------------------------------------------------
| Delete
|--------------------------------------------------------------------------
*/

const openDeleteModal = (
  item
) => {
  if (!item?.id) {
    return;
  }

  clearFeedback();

  itemToDelete.value = {
    ...item,
  };

  showDeleteModal.value =
    true;
};

const closeDeleteModal =
  () => {
    if (loading.deleting) {
      return;
    }

    showDeleteModal.value =
      false;

    itemToDelete.value =
      null;
  };

const confirmDeleteMenuItem =
  async () => {
    const id =
      itemToDelete.value?.id;

    if (!id) {
      return;
    }

    try {
      await deleteItem(id);

      showDeleteModal.value =
        false;

      itemToDelete.value =
        null;

      if (
        menuItems.value.length ===
          0 &&
        Number(filters.page) > 1
      ) {
        filters.page =
          Number(filters.page) -
          1;
      }

      await loadMenuItems(true);
    } catch (error) {
      console.error(
        "Menu item delete failed:",
        error?.response?.data ||
          error
      );
    }
  };

/*
|--------------------------------------------------------------------------
| Availability
|--------------------------------------------------------------------------
*/

const toggleAvailability =
  async (item) => {
    if (!item?.id) {
      return;
    }

    try {
      await toggleItem(
        item.id,
        "status"
      );
    } catch (error) {
      console.error(
        "Availability update failed:",
        error?.response?.data ||
          error
      );
    }
  };

/*
|--------------------------------------------------------------------------
| Featured
|--------------------------------------------------------------------------
*/

const toggleFeatured =
  async (item) => {
    if (!item?.id) {
      return;
    }

    try {
      await toggleItem(
        item.id,
        "featured"
      );
    } catch (error) {
      console.error(
        "Featured update failed:",
        error?.response?.data ||
          error
      );
    }
  };

/*
|--------------------------------------------------------------------------
| Helpers
|--------------------------------------------------------------------------
*/

const getItemName = (
  item
) =>
  item?.menu_name ||
  "Unnamed Item";

const getCategoryName = (
  item
) =>
  item?.category
    ?.category_name ||
  item?.category_name ||
  "No Category";

const isAvailable = (
  item
) =>
  normalizeBoolean(
    item?.is_available
  );

const isFeatured = (
  item
) =>
  normalizeBoolean(
    item?.is_featured
  );

const formatPrice = (
  price
) =>
  new Intl.NumberFormat(
    "en-BD",
    {
      style: "currency",
      currency: "BDT",
      maximumFractionDigits: 2,
    }
  ).format(
    Number(price ?? 0)
  );

const formatType = (
  type
) =>
  String(
    type || "regular"
  )
    .replaceAll(
      "_",
      " "
    )
    .replace(
      /\b\w/g,
      (letter) =>
        letter.toUpperCase()
    );

const isToggling = (
  item
) =>
  loading.toggling &&
  Number(
    loading.togglingId
  ) === Number(item?.id);

/*
|--------------------------------------------------------------------------
| Initial Load
|--------------------------------------------------------------------------
*/

onMounted(async () => {
  await Promise.all([
    loadMenuItems(),
    loadCategories(),
  ]);
});
</script>

<template>
  <div class="menu-section">
    <!-- Success Message -->
    <div
      v-if="message"
      class="alert alert-success alert-dismissible fade show"
    >
      <i
        class="bi bi-check-circle-fill me-2"
      ></i>

      {{ message }}

      <button
        type="button"
        class="btn-close"
        @click="clearFeedback"
      ></button>
    </div>

    <!-- Error Message -->
    <div
      v-if="
        errorMessage &&
        !showMenuItemModal &&
        !showDeleteModal
      "
      class="alert alert-danger alert-dismissible fade show"
    >
      <i
        class="bi bi-exclamation-circle-fill me-2"
      ></i>

      {{ errorMessage }}

      <button
        type="button"
        class="btn-close"
        @click="clearFeedback"
      ></button>
    </div>

    <!-- Header -->
    <div
      class="menu-section-header"
    >
      <div>
        <h2>
          Menu Items
        </h2>

        <p>
          Manage restaurant food
          and beverage items.
        </p>
      </div>

      <button
        type="button"
        class="btn btn-primary"
        @click="
          openCreateModal
        "
      >
        <i
          class="bi bi-plus-lg me-2"
        ></i>

        Add Menu Item
      </button>
    </div>

    <!-- Filters -->
    <form
      class="menu-filter-panel"
      @submit.prevent="
        searchItems
      "
    >
      <div class="row g-3">
        <div
          class="col-lg-3"
        >
          <label
            class="form-label"
          >
            Search
          </label>

          <input
            v-model="
              filters.search
            "
            type="search"
            class="form-control"
            placeholder="Search menu item..."
          />
        </div>

        <div
          class="col-lg-3"
        >
          <label
            class="form-label"
          >
            Category
          </label>

          <select
            v-model="
              filters.menu_category_id
            "
            class="form-select"
          >
            <option value="">
              All Categories
            </option>

            <option
              v-for="
                category in categories
              "
              :key="
                category.id
              "
              :value="
                category.id
              "
            >
              {{
                category.category_name ||
                category.name
              }}
            </option>
          </select>
        </div>

        <div
          class="col-lg-2"
        >
          <label
            class="form-label"
          >
            Type
          </label>

          <select
            v-model="
              filters.item_type
            "
            class="form-select"
          >
            <option value="">
              All Types
            </option>

            <option
              value="regular"
            >
              Regular
            </option>

            <option
              value="combo"
            >
              Combo
            </option>

            <option
              value="set_meal"
            >
              Set Meal
            </option>
          </select>
        </div>

        <div
          class="col-lg-2"
        >
          <label
            class="form-label"
          >
            Availability
          </label>

          <select
            v-model="
              filters.status
            "
            class="form-select"
          >
            <option value="">
              All
            </option>

            <option
              value="available"
            >
              Available
            </option>

            <option
              value="unavailable"
            >
              Unavailable
            </option>
          </select>
        </div>

        <div
          class="col-lg-2"
        >
          <label
            class="form-label d-none d-lg-block"
          >
            &nbsp;
          </label>

          <div
            class="d-flex gap-2"
          >
            <button
              type="submit"
              class="btn btn-primary flex-grow-1"
              :disabled="
                loading.list
              "
            >
              <span
                v-if="
                  loading.list
                "
                class="spinner-border spinner-border-sm me-1"
              ></span>

              Filter
            </button>

            <button
              type="button"
              class="btn btn-outline-secondary"
              :disabled="
                loading.list
              "
              @click="
                resetItemFilters
              "
            >
              <i
                class="bi bi-arrow-counterclockwise"
              ></i>
            </button>
          </div>
        </div>
      </div>
    </form>

    <!-- Loading -->
    <div
      v-if="
        loading.list
      "
      class="menu-loading-state"
    >
      <div
        class="spinner-border text-primary"
      ></div>

      <p>
        Loading menu items...
      </p>
    </div>

    <!-- Table -->
    <div
      v-else
      class="table-responsive"
    >
      <table
        class="table menu-section-table align-middle"
      >
        <thead>
          <tr>
            <th>
              Item
            </th>

            <th>
              Category
            </th>

            <th>
              Type
            </th>

            <th>
              Price
            </th>

            <th>
              Featured
            </th>

            <th>
              Availability
            </th>

            <th
              class="text-end"
            >
              Actions
            </th>
          </tr>
        </thead>

        <tbody>
          <tr
            v-for="
              item in menuItems
            "
            :key="
              item.id
            "
          >
            <!-- Item + Image -->
            <td>
              <div
                class="menu-record-title"
              >
                <div
                  class="menu-record-icon"
                >
                  <img
                    v-if="
                      getItemImage(
                        item
                      )
                    "
                    :src="
                      getItemImage(
                        item
                      )
                    "
                    :alt="
                      getItemName(
                        item
                      )
                    "
                    loading="lazy"
                  />

                  <i
                    v-else
                    class="bi bi-cup-hot"
                  ></i>
                </div>

                <div>
                  <strong>
                    {{
                      getItemName(
                        item
                      )
                    }}
                  </strong>

                  <small>
                    {{
                      item.preparation_time ??
                      0
                    }}
                    minutes
                  </small>
                </div>
              </div>
            </td>

            <!-- Category -->
            <td>
              {{
                getCategoryName(
                  item
                )
              }}
            </td>

            <!-- Type -->
            <td>
              <span
                class="menu-type-badge"
              >
                {{
                  item.item_type_label ||
                  formatType(
                    item.item_type
                  )
                }}
              </span>
            </td>

            <!-- Price -->
            <td
              class="menu-price-cell"
            >
              {{
                formatPrice(
                  item.price
                )
              }}
            </td>

            <!-- Featured -->
            <td>
              <button
                type="button"
                class="menu-featured-button"
                :class="{
                  featured:
                    isFeatured(
                      item
                    ),
                }"
                :disabled="
                  isToggling(
                    item
                  )
                "
                @click="
                  toggleFeatured(
                    item
                  )
                "
              >
                <span
                  v-if="
                    isToggling(
                      item
                    )
                  "
                  class="spinner-border spinner-border-sm"
                ></span>

                <i
                  v-else
                  :class="
                    isFeatured(
                      item
                    )
                      ? 'bi bi-star-fill'
                      : 'bi bi-star'
                  "
                ></i>
              </button>
            </td>

            <!-- Availability -->
            <td>
              <button
                type="button"
                class="menu-availability-button"
                :class="{
                  available:
                    isAvailable(
                      item
                    ),

                  unavailable:
                    !isAvailable(
                      item
                    ),
                }"
                :disabled="
                  isToggling(
                    item
                  )
                "
                @click="
                  toggleAvailability(
                    item
                  )
                "
              >
                <span
                  v-if="
                    isToggling(
                      item
                    )
                  "
                  class="spinner-border spinner-border-sm"
                ></span>

                <i
                  v-else
                  :class="
                    isAvailable(
                      item
                    )
                      ? 'bi bi-check-circle-fill'
                      : 'bi bi-x-circle-fill'
                  "
                ></i>

                {{
                  isAvailable(
                    item
                  )
                    ? "Available"
                    : "Unavailable"
                }}
              </button>
            </td>

            <!-- Actions -->
            <td>
              <div
                class="d-flex justify-content-end gap-2"
              >
                <button
                  type="button"
                  class="btn btn-sm btn-outline-primary"
                  title="Edit item"
                  :disabled="
                    loading.submitting ||
                    loading.deleting
                  "
                  @click="
                    openEditModal(
                      item
                    )
                  "
                >
                  <i
                    class="bi bi-pencil"
                  ></i>
                </button>

                <button
                  type="button"
                  class="btn btn-sm btn-outline-danger"
                  title="Delete item"
                  :disabled="
                    loading.submitting ||
                    loading.deleting
                  "
                  @click="
                    openDeleteModal(
                      item
                    )
                  "
                >
                  <i
                    class="bi bi-trash3"
                  ></i>
                </button>
              </div>
            </td>
          </tr>

          <!-- Empty -->
          <tr
            v-if="
              menuItems.length ===
              0
            "
          >
            <td
              colspan="7"
              class="menu-empty-state"
            >
              <i
                class="bi bi-inbox"
              ></i>

              No menu items found.
            </td>
          </tr>
        </tbody>
      </table>
    </div>

    <!-- Pagination -->
    <div
      v-if="
        !loading.list &&
        pagination.total > 0
      "
      class="menu-pagination"
    >
      <p>
        Showing
        {{
          pagination.from ??
          0
        }}
        to
        {{
          pagination.to ??
          0
        }}
        of
        {{
          pagination.total
        }}
        menu items
      </p>

      <div
        class="menu-pagination-controls"
      >
        <button
          type="button"
          class="btn btn-sm btn-outline-secondary"
          :disabled="
            pagination.current_page <=
              1 ||
            loading.list
          "
          @click="
            changePage(
              pagination.current_page -
                1
            )
          "
        >
          Previous
        </button>

        <span>
          Page
          {{
            pagination.current_page
          }}
          of
          {{
            pagination.last_page
          }}
        </span>

        <button
          type="button"
          class="btn btn-sm btn-outline-secondary"
          :disabled="
            pagination.current_page >=
              pagination.last_page ||
            loading.list
          "
          @click="
            changePage(
              pagination.current_page +
                1
            )
          "
        >
          Next
        </button>
      </div>
    </div>

    <!-- Create / Edit Modal -->
    <MenuItemModal
      :show="
        showMenuItemModal
      "
      :menu-item="
        selectedMenuItem
      "
      :categories="
        categories
      "
      :submitting="
        loading.submitting
      "
      :error-message="
        errorMessage
      "
      :validation-errors="
        validationErrors
      "
      @close="
        closeMenuItemModal
      "
      @submit="
        saveMenuItem
      "
    />

    <!-- Delete Modal -->
    <ConfirmDeleteModal
      :show="
        showDeleteModal
      "
      title="Delete Menu Item"
      :item-name="
        getItemName(
          itemToDelete
        )
      "
      message="This action cannot be undone. Related variants may also be deleted."
      :loading="
        loading.deleting
      "
      @close="
        closeDeleteModal
      "
      @confirm="
        confirmDeleteMenuItem
      "
    />
  </div>
</template>

<style
  scoped
  src="@/assets/css/menu-section.css"
></style>