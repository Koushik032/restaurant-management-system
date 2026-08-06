<script setup>
import {
  onMounted,
  ref,
} from "vue";

import ConfirmDeleteModal from "@/components/common/ConfirmDeleteModal.vue";

import MenuCategoryModal from "@/components/menu/MenuCategoryModal.vue";

import {
  normalizeBoolean,
  useCrudResource,
} from "@/composables/useCrudResource";

const categoryCrud =
  useCrudResource("categories");

const {
  items: categories,

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
} = categoryCrud;

const showCategoryModal =
  ref(false);

const selectedCategory =
  ref(null);

const showDeleteModal =
  ref(false);

const categoryToDelete =
  ref(null);

const loadCategories = async (
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
      "Menu category loading failed:",
      error?.response?.data ||
        error
    );
  }
};

const searchCategories =
  async () => {
    filters.page = 1;

    await loadCategories();
  };

const resetCategoryFilters =
  async () => {
    resetFilters();

    await loadCategories();
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

  await loadCategories();
};

const openCreateModal = () => {
  clearFeedback();

  selectedCategory.value = null;

  showCategoryModal.value = true;
};

const openEditModal = (
  category
) => {
  if (!category?.id) {
    return;
  }

  clearFeedback();

  selectedCategory.value = {
    ...category,
  };

  showCategoryModal.value = true;
};

const closeCategoryModal = () => {
  if (loading.submitting) {
    return;
  }

  showCategoryModal.value = false;

  selectedCategory.value = null;
};

const saveCategory = async (
  payload
) => {
  try {
    if (
      selectedCategory.value?.id
    ) {
      await updateItem(
        selectedCategory.value.id,
        payload
      );
    } else {
      await createItem(payload);
    }

    showCategoryModal.value = false;

    selectedCategory.value = null;

    await loadCategories(true);
  } catch (error) {
    console.error(
      "Menu category save failed:",
      error?.response?.data ||
        error
    );
  }
};

const openDeleteModal = (
  category
) => {
  if (!category?.id) {
    return;
  }

  clearFeedback();

  categoryToDelete.value = {
    ...category,
  };

  showDeleteModal.value = true;
};

const closeDeleteModal = () => {
  if (loading.deleting) {
    return;
  }

  showDeleteModal.value = false;

  categoryToDelete.value = null;
};

const confirmDeleteCategory =
  async () => {
    const categoryId =
      categoryToDelete.value?.id;

    if (!categoryId) {
      return;
    }

    try {
      await deleteItem(categoryId);

      showDeleteModal.value = false;

      categoryToDelete.value = null;

      if (
        categories.value.length ===
          0 &&
        Number(filters.page) > 1
      ) {
        filters.page =
          Number(filters.page) - 1;
      }

      await loadCategories(true);
    } catch (error) {
      console.error(
        "Menu category delete failed:",
        error?.response?.data ||
          error
      );
    }
  };

const toggleCategoryAvailability =
  async (category) => {
    if (!category?.id) {
      return;
    }

    try {
      await toggleItem(
        category.id,
        "status"
      );

      await loadCategories(true);
    } catch (error) {
      console.error(
        "Category availability update failed:",
        error?.response?.data ||
          error
      );
    }
  };

const isAvailable = (
  category
) => {
  return normalizeBoolean(
    category?.is_available
  );
};

const categoryName = (
  category
) => {
  return (
    category?.category_name ||
    category?.name ||
    "Unnamed Category"
  );
};

onMounted(() => {
  loadCategories();
});
</script>

<template>
  <div
    class="menu-category-section"
  >
    <div
      v-if="message"
      class="alert alert-success alert-dismissible fade show"
      role="alert"
    >
      <i
        class="bi bi-check-circle-fill me-2"
      ></i>

      {{ message }}

      <button
        type="button"
        class="btn-close"
        aria-label="Close"
        @click="clearFeedback"
      ></button>
    </div>

    <div
      v-if="
        errorMessage &&
        !showCategoryModal &&
        !showDeleteModal
      "
      class="alert alert-danger alert-dismissible fade show"
      role="alert"
    >
      <i
        class="bi bi-exclamation-circle-fill me-2"
      ></i>

      {{ errorMessage }}

      <button
        type="button"
        class="btn-close"
        aria-label="Close"
        @click="clearFeedback"
      ></button>
    </div>

    <div
      class="menu-category-section-header"
    >
      <div>
        <h2>Menu Categories</h2>

        <p>
          Create, edit, delete and
          control category availability.
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

        Add Category
      </button>
    </div>

    <form
      class="menu-category-filter-panel"
      @submit.prevent="
        searchCategories
      "
    >
      <div class="row g-3">
        <div class="col-lg-6">
          <label
            for="category-search"
            class="form-label"
          >
            Search Category
          </label>

          <div
            class="menu-category-search-wrapper"
          >
            <i
              class="bi bi-search"
            ></i>

            <input
              id="category-search"
              v-model="filters.search"
              type="search"
              class="form-control"
              placeholder="Search by category name..."
            />
          </div>
        </div>

        <div class="col-lg-3">
          <label
            for="category-status"
            class="form-label"
          >
            Availability
          </label>

          <select
            id="category-status"
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

        <div class="col-lg-3">
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
              :disabled="loading.list"
            >
              <span
                v-if="loading.list"
                class="spinner-border spinner-border-sm me-2"
              ></span>

              Filter
            </button>

            <button
              type="button"
              class="btn btn-outline-secondary"
              title="Reset filters"
              :disabled="loading.list"
              @click="
                resetCategoryFilters
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
      class="menu-category-loading"
    >
      <div
        class="spinner-border text-primary"
        role="status"
      ></div>

      <p>
        Loading menu categories...
      </p>
    </div>

    <div
      v-else
      class="menu-category-table-card"
    >
      <div
        class="table-responsive"
      >
        <table
          class="table menu-category-table align-middle"
        >
          <thead>
            <tr>
              <th>Category</th>

              <th>Availability</th>

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
                category in categories
              "
              :key="category.id"
            >
              <td>
                <div
                  class="menu-category-record"
                >
                  <div
                    class="menu-category-icon"
                  >
                    <i
                      class="bi bi-grid"
                    ></i>
                  </div>

                  <div>
                    <strong>
                      {{
                        categoryName(
                          category
                        )
                      }}
                    </strong>

                    <small>
                      Category ID:
                      {{ category.id }}
                    </small>
                  </div>
                </div>
              </td>

              <td>
                <button
                  type="button"
                  class="menu-category-status-button"
                  :class="{
                    available:
                      isAvailable(
                        category
                      ),

                    unavailable:
                      !isAvailable(
                        category
                      ),
                  }"
                  :disabled="
                    loading.toggling &&
                    Number(
                      loading.togglingId
                    ) ===
                      Number(
                        category.id
                      )
                  "
                  @click="
                    toggleCategoryAvailability(
                      category
                    )
                  "
                >
                  <span
                    v-if="
                      loading.toggling &&
                      Number(
                        loading.togglingId
                      ) ===
                        Number(
                          category.id
                        )
                    "
                    class="spinner-border spinner-border-sm"
                  ></span>

                  <i
                    v-else
                    :class="
                      isAvailable(
                        category
                      )
                        ? 'bi bi-check-circle-fill'
                        : 'bi bi-x-circle-fill'
                    "
                  ></i>

                  {{
                    isAvailable(category)
                      ? "Available"
                      : "Unavailable"
                  }}
                </button>
              </td>

              <td>
                <div
                  class="menu-category-actions"
                >
                  <button
                    type="button"
                    class="btn btn-sm btn-outline-primary"
                    title="Edit category"
                    :disabled="
                      loading.submitting ||
                      loading.deleting
                    "
                    @click="
                      openEditModal(
                        category
                      )
                    "
                  >
                    <i
                      class="bi bi-pencil-square"
                    ></i>
                  </button>

                  <button
                    type="button"
                    class="btn btn-sm btn-outline-danger"
                    title="Delete category"
                    :disabled="
                      loading.deleting
                    "
                    @click="
                      openDeleteModal(
                        category
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
                categories.length === 0
              "
            >
              <td
                colspan="3"
                class="menu-category-empty"
              >
                <i
                  class="bi bi-inbox"
                ></i>

                <strong>
                  No categories found
                </strong>

                <span>
                  Create a new category or
                  change the current filters.
                </span>
              </td>
            </tr>
          </tbody>
        </table>
      </div>
    </div>

    <div
      v-if="
        !loading.list &&
        pagination.total > 0
      "
      class="menu-category-pagination"
    >
      <p>
        Showing
        {{ pagination.from ?? 0 }}
        to
        {{ pagination.to ?? 0 }}
        of
        {{ pagination.total }}
        categories
      </p>

      <div
        class="menu-category-pagination-controls"
      >
        <button
          type="button"
          class="btn btn-sm btn-outline-secondary"
          :disabled="
            Number(
              pagination.current_page
            ) <= 1 ||
            loading.list
          "
          @click="
            changePage(
              Number(
                pagination.current_page
              ) - 1
            )
          "
        >
          <i
            class="bi bi-chevron-left me-1"
          ></i>

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
            Number(
              pagination.current_page
            ) >=
              Number(
                pagination.last_page
              ) ||
            loading.list
          "
          @click="
            changePage(
              Number(
                pagination.current_page
              ) + 1
            )
          "
        >
          Next

          <i
            class="bi bi-chevron-right ms-1"
          ></i>
        </button>
      </div>
    </div>

    <MenuCategoryModal
      :show="showCategoryModal"
      :category="selectedCategory"
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
        closeCategoryModal
      "
      @submit="saveCategory"
    />

    <ConfirmDeleteModal
      :show="showDeleteModal"
      title="Delete Menu Category"
      :item-name="
        categoryName(
          categoryToDelete
        )
      "
      message="This action cannot be undone. Categories connected with menu items may not be removable."
      :loading="
        loading.deleting
      "
      @close="closeDeleteModal"
      @confirm="
        confirmDeleteCategory
      "
    />
  </div>
</template>

<style
  scoped
  src="@/assets/css/menu-category-section.css"
></style>