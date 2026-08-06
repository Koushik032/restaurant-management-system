<script setup>
import {
  computed,
  ref,
  watch,
} from "vue";

import {
  restaurantTableService,
} from "@/services/restaurantTableService";

const props = defineProps({
  show: {
    type: Boolean,
    default: false,
  },

  table: {
    type: Object,
    default: null,
  },
});

const emit = defineEmits([
  "close",
  "merged",
]);

const loading = ref(false);
const submitting = ref(false);
const errorMessage = ref("");

const currentGroup = ref([]);
const availableTables = ref([]);
const selectedTableIds = ref([]);

const isExistingGroup = ref(false);
const masterTableId = ref(null);

/*
|--------------------------------------------------------------------------
| Computed properties
|--------------------------------------------------------------------------
*/

const selectedTables = computed(() => {
  return availableTables.value.filter(
    (table) =>
      selectedTableIds.value.includes(
        Number(table.id)
      )
  );
});

const currentCapacity = computed(() => {
  return currentGroup.value.reduce(
    (total, table) =>
      total +
      Number(table.capacity || 0),
    0
  );
});

const addedCapacity = computed(() => {
  return selectedTables.value.reduce(
    (total, table) =>
      total +
      Number(table.capacity || 0),
    0
  );
});

const finalCapacity = computed(() => {
  return (
    currentCapacity.value +
    addedCapacity.value
  );
});

const canSubmit = computed(() => {
  return (
    !loading.value &&
    !submitting.value &&
    selectedTableIds.value.length > 0
  );
});

/*
|--------------------------------------------------------------------------
| Load merge options
|--------------------------------------------------------------------------
*/

const loadMergeOptions = async () => {
  if (!props.table?.id) {
    return;
  }

  loading.value = true;
  errorMessage.value = "";

  currentGroup.value = [];
  availableTables.value = [];
  selectedTableIds.value = [];

  try {
    const response =
      await restaurantTableService
        .getMergeOptions(
          props.table.id
        );

    const data =
      response?.data ?? {};

    currentGroup.value =
      Array.isArray(
        data.current_group
      )
        ? data.current_group
        : [];

    availableTables.value =
      Array.isArray(
        data.available_tables
      )
        ? data.available_tables
        : [];

    isExistingGroup.value =
      Boolean(
        data.is_existing_group
      );

    masterTableId.value =
      data.master_table_id ??
      props.table.id;
  } catch (error) {
    console.error(
      "Failed to load merge options:",
      error?.response?.data ||
        error
    );

    errorMessage.value =
      error?.response?.data
        ?.message ||
      "Unable to load available tables.";
  } finally {
    loading.value = false;
  }
};

/*
|--------------------------------------------------------------------------
| Watch modal
|--------------------------------------------------------------------------
*/

watch(
  () => props.show,
  async (visible) => {
    if (visible) {
      await loadMergeOptions();

      return;
    }

    currentGroup.value = [];
    availableTables.value = [];
    selectedTableIds.value = [];

    isExistingGroup.value = false;
    masterTableId.value = null;

    errorMessage.value = "";
  }
);

/*
|--------------------------------------------------------------------------
| Table selection
|--------------------------------------------------------------------------
*/

const toggleTable = (tableId) => {
  const numericId =
    Number(tableId);

  const index =
    selectedTableIds.value.indexOf(
      numericId
    );

  if (index >= 0) {
    selectedTableIds.value.splice(
      index,
      1
    );

    return;
  }

  selectedTableIds.value.push(
    numericId
  );
};

const isSelected = (tableId) => {
  return selectedTableIds.value.includes(
    Number(tableId)
  );
};

/*
|--------------------------------------------------------------------------
| Modal actions
|--------------------------------------------------------------------------
*/

const closeModal = () => {
  if (submitting.value) {
    return;
  }

  errorMessage.value = "";

  emit("close");
};

const handleBackdropClick = (
  event
) => {
  if (
    event.target ===
    event.currentTarget
  ) {
    closeModal();
  }
};

/*
|--------------------------------------------------------------------------
| Submit merge
|--------------------------------------------------------------------------
*/

const submitMerge = async () => {
  if (
    !props.table?.id ||
    !canSubmit.value
  ) {
    return;
  }

  submitting.value = true;
  errorMessage.value = "";

  try {
    const response =
      await restaurantTableService
        .mergeTables(
          props.table.id,
          selectedTableIds.value
        );

    emit(
      "merged",
      response
    );
  } catch (error) {
    console.error(
      "Merge failed:",
      error?.response?.data ||
        error
    );

    const validationErrors =
      error?.response?.data
        ?.errors;

    errorMessage.value =
      validationErrors
        ?.table_ids?.[0] ||
      error?.response?.data
        ?.message ||
      "Unable to merge the selected tables.";
  } finally {
    submitting.value = false;
  }
};
</script>

<template>
  <Teleport to="body">
    <Transition
      name="merge-table-modal"
    >
      <div
        v-if="show"
        class="merge-table-backdrop"
        @click="handleBackdropClick"
      >
        <div
          class="merge-table-dialog"
          role="dialog"
          aria-modal="true"
          aria-labelledby="merge-table-title"
        >
          <div
            class="merge-table-header"
          >
            <div>
              <p
                class="merge-table-eyebrow"
              >
                Table Management
              </p>

              <h2
                id="merge-table-title"
              >
                {{
                  isExistingGroup
                    ? "Add Tables to Merge"
                    : "Merge Tables"
                }}
              </h2>

              <p>
                {{
                  isExistingGroup
                    ? "Add available tables to the existing group."
                    : "Select available tables to create a merged group."
                }}
              </p>
            </div>

            <button
              type="button"
              class="merge-table-close"
              :disabled="submitting"
              aria-label="Close modal"
              @click="closeModal"
            >
              <i
                class="bi bi-x-lg"
              ></i>
            </button>
          </div>

          <div
            class="merge-table-body"
          >
            <div
              v-if="errorMessage"
              class="alert alert-danger py-2"
            >
              <i
                class="bi bi-exclamation-circle me-1"
              ></i>

              {{ errorMessage }}
            </div>

            <div
              v-if="loading"
              class="merge-table-loading"
            >
              <span
                class="spinner-border"
              ></span>

              <span>
                Loading available tables...
              </span>
            </div>

            <template v-else>
              <!-- Current group -->
              <section
                class="merge-section"
              >
                <div
                  class="merge-section-title"
                >
                  <div>
                    <h3>
                      {{
                        isExistingGroup
                          ? "Current Merge Group"
                          : "Selected Base Table"
                      }}
                    </h3>

                    <p>
                      Master table:
                      #{{ masterTableId }}
                    </p>
                  </div>

                  <span
                    class="merge-count-badge"
                  >
                    {{
                      currentGroup.length
                    }}
                    tables
                  </span>
                </div>

                <div
                  v-if="currentGroup.length"
                  class="merge-table-list"
                >
                  <div
                    v-for="
                      groupTable in
                      currentGroup
                    "
                    :key="groupTable.id"
                    class="merge-table-list-item merge-table-list-item--connected"
                  >
                    <div
                      class="merge-table-list-main"
                    >
                      <div
                        class="merge-table-list-icon"
                      >
                        <i
                          class="bi bi-table"
                        ></i>
                      </div>

                      <div>
                        <strong>
                          {{
                            groupTable
                              .table_name
                          }}
                        </strong>

                        <span>
                          Table
                          #{{
                            groupTable.id
                          }}
                          ·
                          {{
                            groupTable
                              .capacity
                          }}
                          seats
                          ·
                          {{
                            groupTable
                              .section
                          }}
                        </span>
                      </div>
                    </div>

                    <span
                      :class="[
                        'merge-status',
                        `merge-status--${
                          groupTable.current_status ||
                          groupTable.status
                        }`,
                      ]"
                    >
                      {{
                        groupTable
                          .current_status ||
                        groupTable.status
                      }}
                    </span>
                  </div>
                </div>

                <div
                  v-else
                  class="merge-empty-state"
                >
                  <i
                    class="bi bi-table"
                  ></i>

                  <strong>
                    No base table information
                  </strong>
                </div>
              </section>

              <!-- Available tables -->
              <section
                class="merge-section"
              >
                <div
                  class="merge-section-title"
                >
                  <div>
                    <h3>
                      Available Tables
                    </h3>

                    <p>
                      Select one or more
                      standalone available
                      tables.
                    </p>
                  </div>

                  <span
                    class="merge-count-badge"
                  >
                    {{
                      selectedTableIds
                        .length
                    }}
                    selected
                  </span>
                </div>

                <div
                  v-if="
                    availableTables.length
                  "
                  class="merge-table-list"
                >
                  <button
                    v-for="
                      availableTable in
                      availableTables
                    "
                    :key="
                      availableTable.id
                    "
                    type="button"
                    class="merge-table-list-item merge-table-list-item--selectable"
                    :class="{
                      'merge-table-list-item--selected':
                        isSelected(
                          availableTable.id
                        ),
                    }"
                    @click="
                      toggleTable(
                        availableTable.id
                      )
                    "
                  >
                    <div
                      class="merge-table-list-main"
                    >
                      <div
                        class="merge-table-list-check"
                      >
                        <i
                          :class="
                            isSelected(
                              availableTable.id
                            )
                              ? 'bi bi-check-lg'
                              : 'bi bi-plus-lg'
                          "
                        ></i>
                      </div>

                      <div>
                        <strong>
                          {{
                            availableTable
                              .table_name
                          }}
                        </strong>

                        <span>
                          Table
                          #{{
                            availableTable.id
                          }}
                          ·
                          {{
                            availableTable
                              .capacity
                          }}
                          seats
                          ·
                          {{
                            availableTable
                              .section
                          }}
                        </span>
                      </div>
                    </div>

                    <span
                      class="merge-available-badge"
                    >
                      Available
                    </span>
                  </button>
                </div>

                <div
                  v-else
                  class="merge-empty-state"
                >
                  <i
                    class="bi bi-table"
                  ></i>

                  <strong>
                    No available tables
                  </strong>

                  <span>
                    Other tables are
                    occupied, reserved,
                    cleaning or already
                    merged.
                  </span>
                </div>
              </section>

              <!-- Capacity -->
              <section
                class="merge-capacity-summary"
              >
                <div>
                  <span>
                    Current capacity
                  </span>

                  <strong>
                    {{ currentCapacity }}
                  </strong>
                </div>

                <i
                  class="bi bi-plus-lg"
                ></i>

                <div>
                  <span>
                    Added capacity
                  </span>

                  <strong>
                    {{ addedCapacity }}
                  </strong>
                </div>

                <i
                  class="bi bi-arrow-right"
                ></i>

                <div
                  class="merge-capacity-total"
                >
                  <span>
                    Final capacity
                  </span>

                  <strong>
                    {{ finalCapacity }}
                  </strong>
                </div>
              </section>
            </template>
          </div>

          <div
            class="merge-table-footer"
          >
            <button
              type="button"
              class="btn btn-light"
              :disabled="submitting"
              @click="closeModal"
            >
              Cancel
            </button>

            <button
              type="button"
              class="btn btn-warning"
              :disabled="!canSubmit"
              @click="submitMerge"
            >
              <span
                v-if="submitting"
                class="spinner-border spinner-border-sm me-2"
              ></span>

              <i
                v-else
                class="bi bi-intersect me-1"
              ></i>

              {{
                submitting
                  ? "Merging..."
                  : isExistingGroup
                    ? "Add to Merge"
                    : "Merge Tables"
              }}
            </button>
          </div>
        </div>
      </div>
    </Transition>
  </Teleport>
</template>

<style
  src="@/assets/css/merge-table-modal.css"
></style>