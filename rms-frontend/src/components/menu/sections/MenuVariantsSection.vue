<script setup>
import {
  onMounted,
  ref,
} from "vue";

import ConfirmDeleteModal from "@/components/common/ConfirmDeleteModal.vue";

import MenuVariantModal from "@/components/menu/MenuVariantModal.vue";

import {
  normalizeBoolean,
  useCrudResource,
} from "@/composables/useCrudResource";

const variantCrud =
  useCrudResource("variants");

const menuItemCrud =
  useCrudResource("menuItems");

const {
  items: variants,
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
} = variantCrud;

const menuItems =
  menuItemCrud.items;

const showVariantModal =
  ref(false);

const selectedVariant =
  ref(null);

const showDeleteModal =
  ref(false);

const variantToDelete =
  ref(null);

const loadVariants = async (
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
      "Variants loading failed:",
      error?.response?.data ||
        error
    );
  }
};

const loadMenuItems = async () => {
  try {
    menuItems.value =
      await menuItemCrud.fetchAll({
        status: "available",
      });
  } catch (error) {
    console.error(
      "Menu items loading failed:",
      error?.response?.data ||
        error
    );
  }
};

const searchVariants = async () => {
  filters.page = 1;
  await loadVariants();
};

const resetVariantFilters =
  async () => {
    resetFilters();
    await loadVariants();
  };

const changePage = async (
  page
) => {
  const nextPage = Number(page);

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

  await loadVariants();
};

const openCreateModal = () => {
  clearFeedback();

  selectedVariant.value = null;
  showVariantModal.value = true;
};

const openEditModal = (
  variant
) => {
  if (!variant?.id) {
    return;
  }

  clearFeedback();

  selectedVariant.value = {
    ...variant,
  };

  showVariantModal.value = true;
};

const closeVariantModal = () => {
  if (loading.submitting) {
    return;
  }

  showVariantModal.value = false;
  selectedVariant.value = null;
};

const saveVariant = async (
  payload
) => {
  try {
    if (selectedVariant.value?.id) {
      await updateItem(
        selectedVariant.value.id,
        payload
      );
    } else {
      await createItem(payload);
    }

    showVariantModal.value = false;
    selectedVariant.value = null;

    await loadVariants(true);
  } catch (error) {
    console.error(
      "Variant save failed:",
      error?.response?.data ||
        error
    );
  }
};

const openDeleteModal = (
  variant
) => {
  if (!variant?.id) {
    return;
  }

  clearFeedback();

  variantToDelete.value = {
    ...variant,
  };

  showDeleteModal.value = true;
};

const closeDeleteModal = () => {
  if (loading.deleting) {
    return;
  }

  showDeleteModal.value = false;
  variantToDelete.value = null;
};

const confirmDeleteVariant =
  async () => {
    const id =
      variantToDelete.value?.id;

    if (!id) {
      return;
    }

    try {
      await deleteItem(id);

      showDeleteModal.value = false;
      variantToDelete.value = null;

      if (
        variants.value.length === 0 &&
        Number(filters.page) > 1
      ) {
        filters.page =
          Number(filters.page) - 1;
      }

      await loadVariants(true);
    } catch (error) {
      console.error(
        "Variant delete failed:",
        error?.response?.data ||
          error
      );
    }
  };

const toggleAvailability =
  async (variant) => {
    if (!variant?.id) {
      return;
    }

    try {
      await toggleItem(
        variant.id,
        "status"
      );
    } catch (error) {
      console.error(
        "Variant availability failed:",
        error?.response?.data ||
          error
      );
    }
  };

const getVariantName = (
  variant
) =>
  variant?.variant_name ||
  "Unnamed Variant";

const getMenuItemName = (
  variant
) =>
  variant?.menu_item?.menu_name ||
  variant?.menuItem?.menu_name ||
  "No Menu Item";

const isAvailable = (
  variant
) =>
  normalizeBoolean(
    variant?.is_available
  );

const formatPrice = (price) =>
  new Intl.NumberFormat(
    "en-BD",
    {
      style: "currency",
      currency: "BDT",
      maximumFractionDigits: 2,
    }
  ).format(Number(price ?? 0));

const isToggling = (
  variant
) =>
  loading.toggling &&
  Number(loading.togglingId) ===
    Number(variant?.id);

onMounted(async () => {
  await Promise.all([
    loadVariants(),
    loadMenuItems(),
  ]);
});
</script>

<template>
  <div class="menu-section">
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

    <div
      v-if="
        errorMessage &&
        !showVariantModal &&
        !showDeleteModal
      "
      class="alert alert-danger alert-dismissible fade show"
    >
      {{ errorMessage }}

      <button
        type="button"
        class="btn-close"
        @click="clearFeedback"
      ></button>
    </div>

    <div class="menu-section-header">
      <div>
        <h2>
          Menu Item Variants
        </h2>

        <p>
          Manage size and pricing
          variations.
        </p>
      </div>

      <button
        type="button"
        class="btn btn-primary"
        @click="openCreateModal"
      >
        <i
          class="bi bi-plus-lg me-2"
        ></i>

        Add Variant
      </button>
    </div>

    <form
      class="menu-filter-panel"
      @submit.prevent="
        searchVariants
      "
    >
      <div class="row g-3">
        <div class="col-lg-4">
          <label
            class="form-label"
          >
            Search
          </label>

          <input
            v-model="filters.search"
            type="search"
            class="form-control"
            placeholder="Search variant..."
          />
        </div>

        <div class="col-lg-3">
          <label
            class="form-label"
          >
            Menu Item
          </label>

          <select
            v-model="
              filters.menu_item_id
            "
            class="form-select"
          >
            <option value="">
              All Menu Items
            </option>

            <option
              v-for="
                item in menuItems
              "
              :key="item.id"
              :value="item.id"
            >
              {{ item.menu_name }}
            </option>
          </select>
        </div>

        <div class="col-lg-3">
          <label
            class="form-label"
          >
            Availability
          </label>

          <select
            v-model="filters.status"
            class="form-select"
          >
            <option value="">
              All Status
            </option>

            <option value="available">
              Available
            </option>

            <option value="unavailable">
              Unavailable
            </option>
          </select>
        </div>

        <div class="col-lg-2">
          <label
            class="form-label d-none d-lg-block"
          >
            &nbsp;
          </label>

          <div class="d-flex gap-2">
            <button
              type="submit"
              class="btn btn-primary flex-grow-1"
              :disabled="loading.list"
            >
              Filter
            </button>

            <button
              type="button"
              class="btn btn-outline-secondary"
              :disabled="loading.list"
              @click="
                resetVariantFilters
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

    <div
      v-if="loading.list"
      class="menu-loading-state"
    >
      <div
        class="spinner-border text-primary"
      ></div>

      <p>
        Loading variants...
      </p>
    </div>

    <div
      v-else
      class="table-responsive"
    >
      <table
        class="table menu-section-table align-middle"
      >
        <thead>
          <tr>
            <th>Variant</th>
            <th>Menu Item</th>
            <th>Price</th>
            <th>Availability</th>

            <th class="text-end">
              Actions
            </th>
          </tr>
        </thead>

        <tbody>
          <tr
            v-for="
              variant in variants
            "
            :key="variant.id"
          >
            <td>
              <strong>
                {{
                  getVariantName(
                    variant
                  )
                }}
              </strong>
            </td>

            <td>
              {{
                getMenuItemName(
                  variant
                )
              }}
            </td>

            <td
              class="menu-price-cell"
            >
              {{
                formatPrice(
                  variant.price
                )
              }}
            </td>

            <td>
              <button
                type="button"
                class="menu-availability-button"
                :class="{
                  available:
                    isAvailable(
                      variant
                    ),

                  unavailable:
                    !isAvailable(
                      variant
                    ),
                }"
                :disabled="
                  isToggling(
                    variant
                  )
                "
                @click="
                  toggleAvailability(
                    variant
                  )
                "
              >
                <span
                  v-if="
                    isToggling(
                      variant
                    )
                  "
                  class="spinner-border spinner-border-sm"
                ></span>

                <i
                  v-else
                  :class="
                    isAvailable(
                      variant
                    )
                      ? 'bi bi-check-circle-fill'
                      : 'bi bi-x-circle-fill'
                  "
                ></i>

                {{
                  isAvailable(
                    variant
                  )
                    ? "Available"
                    : "Unavailable"
                }}
              </button>
            </td>

            <td>
              <div
                class="d-flex justify-content-end gap-2"
              >
                <button
                  type="button"
                  class="btn btn-sm btn-outline-primary"
                  title="Edit variant"
                  @click="
                    openEditModal(
                      variant
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
                  title="Delete variant"
                  @click="
                    openDeleteModal(
                      variant
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

          <tr
            v-if="
              variants.length === 0
            "
          >
            <td
              colspan="5"
              class="menu-empty-state"
            >
              <i
                class="bi bi-inbox"
              ></i>

              No variants found.
            </td>
          </tr>
        </tbody>
      </table>
    </div>

    <div
      v-if="
        !loading.list &&
        pagination.total > 0
      "
      class="menu-pagination"
    >
      <p>
        Showing
        {{ pagination.from ?? 0 }}
        to
        {{ pagination.to ?? 0 }}
        of
        {{ pagination.total }}
        variants
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
          {{ pagination.current_page }}
          of
          {{ pagination.last_page }}
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

    <MenuVariantModal
      :show="showVariantModal"
      :variant="selectedVariant"
      :menu-items="menuItems"
      :submitting="
        loading.submitting
      "
      :error-message="
        errorMessage
      "
      :validation-errors="
        validationErrors
      "
      @close="closeVariantModal"
      @submit="saveVariant"
    />

    <ConfirmDeleteModal
      :show="showDeleteModal"
      title="Delete Variant"
      :item-name="
        getVariantName(
          variantToDelete
        )
      "
      message="This variant will be permanently removed."
      :loading="
        loading.deleting
      "
      @close="closeDeleteModal"
      @confirm="
        confirmDeleteVariant
      "
    />
  </div>
</template>

<style
  scoped
  src="@/assets/css/menu-section.css"
></style>