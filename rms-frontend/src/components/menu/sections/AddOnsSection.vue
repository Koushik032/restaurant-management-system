<script setup>
import {
  onMounted,
  ref,
} from "vue";

import AddOnModal from "@/components/menu/AddOnModal.vue";

import ConfirmDeleteModal from "@/components/common/ConfirmDeleteModal.vue";

import {
  useCrudResource,
} from "@/composables/useCrudResource";

const addOnCrud =
  useCrudResource("addOns");

const {
  items: addOns,
  filters,
  pagination,
  loading,
  message,
  errorMessage,
  validationErrors,

  fetchItems,
  createItem,
  updateItem,
  deleteItem,
  toggleItem,
  resetFilters,
  clearFeedback,
} = addOnCrud;

const showAddOnModal =
  ref(false);

const selectedAddOn =
  ref(null);

const showDeleteModal =
  ref(false);

const addOnToDelete =
  ref(null);

const normalizeBoolean = (
  value
) => {
  if (
    value === false ||
    value === 0 ||
    value === "0" ||
    value === "false" ||
    value === null
  ) {
    return false;
  }

  return true;
};

const getName = (addOn) =>
  addOn?.add_on_name ||
  addOn?.name ||
  "Unnamed Add-on";

const isAvailable = (addOn) =>
  normalizeBoolean(
    addOn?.is_available
  );

const getDescription = (
  addOn
) =>
  addOn?.description ||
  "No description";

const formatPrice = (price) =>
  new Intl.NumberFormat(
    "en-BD",
    {
      style: "currency",
      currency: "BDT",
      maximumFractionDigits: 2,
    }
  ).format(Number(price ?? 0));

const loadAddOns = async (
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
      "Add-ons loading failed:",
      error?.response?.data ||
        error
    );
  }
};

const searchAddOns = async () => {
  filters.page = 1;
  await loadAddOns();
};

const resetAddOnFilters =
  async () => {
    resetFilters();
    await loadAddOns();
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
  await loadAddOns();
};

const openCreateModal = () => {
  clearFeedback();

  selectedAddOn.value = null;
  showAddOnModal.value = true;
};

const openEditModal = (
  addOn
) => {
  if (!addOn?.id) {
    return;
  }

  clearFeedback();

  selectedAddOn.value = {
    ...addOn,
  };

  showAddOnModal.value = true;
};

const closeAddOnModal = () => {
  if (loading.submitting) {
    return;
  }

  showAddOnModal.value = false;
  selectedAddOn.value = null;
};

const saveAddOn = async (
  payload
) => {
  try {
    if (selectedAddOn.value?.id) {
      await updateItem(
        selectedAddOn.value.id,
        payload
      );
    } else {
      await createItem(payload);
    }

    showAddOnModal.value = false;
    selectedAddOn.value = null;

    await loadAddOns(true);
  } catch (error) {
    console.error(
      "Add-on save failed:",
      error?.response?.data ||
        error
    );
  }
};

const openDeleteModal = (
  addOn
) => {
  if (!addOn?.id) {
    return;
  }

  clearFeedback();

  addOnToDelete.value = {
    ...addOn,
  };

  showDeleteModal.value = true;
};

const closeDeleteModal = () => {
  if (loading.deleting) {
    return;
  }

  showDeleteModal.value = false;
  addOnToDelete.value = null;
};

const confirmDeleteAddOn =
  async () => {
    const id =
      addOnToDelete.value?.id;

    if (!id) {
      return;
    }

    try {
      await deleteItem(id);

      showDeleteModal.value = false;
      addOnToDelete.value = null;

      if (
        addOns.value.length === 0 &&
        Number(filters.page) > 1
      ) {
        filters.page =
          Number(filters.page) - 1;
      }

      await loadAddOns(true);
    } catch (error) {
      console.error(
        "Add-on delete failed:",
        error?.response?.data ||
          error
      );
    }
  };

const toggleAvailability =
  async (addOn) => {
    if (!addOn?.id) {
      return;
    }

    try {
      await toggleItem(
        addOn.id,
        "status"
      );
    } catch (error) {
      console.error(
        "Add-on availability failed:",
        error?.response?.data ||
          error
      );
    }
  };

const isToggling = (
  addOn
) =>
  Boolean(
    loading.toggling &&
      Number(
        loading.togglingId
      ) === Number(addOn?.id)
  );

onMounted(loadAddOns);
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
        !showAddOnModal &&
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
        <h2>Add-ons</h2>

        <p>
          Manage optional extras that
          can be added while creating
          an order.
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

        Add Add-on
      </button>
    </div>

    <form
      class="menu-filter-panel"
      @submit.prevent="
        searchAddOns
      "
    >
      <div class="row g-3">
        <div class="col-lg-6">
          <label
            class="form-label"
          >
            Search
          </label>

          <input
            v-model="filters.search"
            type="search"
            class="form-control"
            placeholder="Search by name or description..."
          />
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

        <div class="col-lg-3">
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
                resetAddOnFilters
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
        Loading add-ons...
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
            <th>Add-on</th>
            <th>Description</th>
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
              addOn in addOns
            "
            :key="addOn.id"
          >
            <td>
              <div
                class="menu-record-title"
              >
                <span
                  class="menu-record-icon"
                >
                  <i
                    class="bi bi-plus-circle"
                  ></i>
                </span>

                <div>
                  <strong>
                    {{ getName(addOn) }}
                  </strong>

                  <small>
                    Add-on #{{ addOn.id }}
                  </small>
                </div>
              </div>
            </td>

            <td
              class="menu-description-cell"
            >
              {{
                getDescription(addOn)
              }}
            </td>

            <td
              class="menu-price-cell"
            >
              {{
                formatPrice(
                  addOn.price
                )
              }}
            </td>

            <td>
              <button
                type="button"
                class="menu-availability-button"
                :class="{
                  available:
                    isAvailable(addOn),

                  unavailable:
                    !isAvailable(addOn),
                }"
                :disabled="
                  isToggling(addOn)
                "
                @click="
                  toggleAvailability(
                    addOn
                  )
                "
              >
                <span
                  v-if="
                    isToggling(addOn)
                  "
                  class="spinner-border spinner-border-sm"
                ></span>

                <i
                  v-else
                  :class="
                    isAvailable(addOn)
                      ? 'bi bi-check-circle-fill'
                      : 'bi bi-x-circle-fill'
                  "
                ></i>

                {{
                  isAvailable(addOn)
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
                  title="Edit add-on"
                  @click="
                    openEditModal(addOn)
                  "
                >
                  <i
                    class="bi bi-pencil"
                  ></i>
                </button>

                <button
                  type="button"
                  class="btn btn-sm btn-outline-danger"
                  title="Delete add-on"
                  @click="
                    openDeleteModal(
                      addOn
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
              addOns.length === 0
            "
          >
            <td
              colspan="5"
              class="menu-empty-state"
            >
              <i
                class="bi bi-inbox"
              ></i>

              No add-ons found.
            </td>
          </tr>
        </tbody>
      </table>
    </div>

    <div
      v-if="
        !loading.list &&
        Number(pagination.total) > 0
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
        add-ons
      </p>

      <div
        class="menu-pagination-controls"
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
          Previous
        </button>

        <span>
          Page
          {{
            pagination.current_page
          }}
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
        </button>
      </div>
    </div>

    <AddOnModal
      :show="showAddOnModal"
      :add-on="selectedAddOn"
      :submitting="
        loading.submitting
      "
      :error-message="
        errorMessage
      "
      :validation-errors="
        validationErrors
      "
      @close="closeAddOnModal"
      @submit="saveAddOn"
    />

    <ConfirmDeleteModal
      :show="showDeleteModal"
      title="Delete Add-on"
      :item-name="
        getName(addOnToDelete)
      "
      message="This add-on will be removed from the available extras list."
      :loading="
        loading.deleting
      "
      @close="closeDeleteModal"
      @confirm="
        confirmDeleteAddOn
      "
    />
  </div>
</template>

<style
  scoped
  src="@/assets/css/menu-section.css"
></style>