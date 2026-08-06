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
  "split",
]);

const submitting = ref(false);
const errorMessage = ref("");

/*
|--------------------------------------------------------------------------
| Merge group information
|--------------------------------------------------------------------------
*/

const mergeGroupTables = computed(() => {
  const tables =
    props.table?.merge_group_tables;

  return Array.isArray(tables)
    ? tables
    : [];
});

const mergeGroupIds = computed(() => {
  const ids =
    props.table?.merge_group_ids;

  if (Array.isArray(ids)) {
    return ids.map(
      (id) => Number(id)
    );
  }

  return mergeGroupTables.value.map(
    (table) => Number(table.id)
  );
});

const mergeGroupCount = computed(() => {
  if (mergeGroupTables.value.length) {
    return mergeGroupTables.value.length;
  }

  return mergeGroupIds.value.length;
});

const totalCapacity = computed(() => {
  const resourceCapacity =
    Number(
      props.table
        ?.merged_total_capacity
    );

  if (
    Number.isFinite(
      resourceCapacity
    ) &&
    resourceCapacity > 0
  ) {
    return resourceCapacity;
  }

  return mergeGroupTables.value.reduce(
    (total, table) =>
      total +
      Number(table.capacity || 0),
    0
  );
});

const selectedTableStatus =
  computed(() => {
    return (
      props.table
        ?.current_status_label ||
      props.table?.status_label ||
      props.table?.current_status ||
      props.table?.status ||
      "Merged"
    );
  });

const hasMergeGroup = computed(() => {
  return mergeGroupCount.value > 1;
});

const canSubmit = computed(() => {
  return (
    Boolean(props.table?.id) &&
    hasMergeGroup.value &&
    !submitting.value
  );
});

/*
|--------------------------------------------------------------------------
| Modal state
|--------------------------------------------------------------------------
*/

watch(
  () => props.show,
  (visible) => {
    if (visible) {
      errorMessage.value = "";
      return;
    }

    errorMessage.value = "";
  }
);

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
| Split group
|--------------------------------------------------------------------------
*/

const confirmSplit = async () => {
  if (!canSubmit.value) {
    return;
  }

  submitting.value = true;
  errorMessage.value = "";

  try {
    const response =
      await restaurantTableService
        .splitTableGroup(
          props.table.id
        );

    emit(
      "split",
      response
    );
  } catch (error) {
    console.error(
      "Table split failed:",
      error?.response?.data ||
        error
    );

    const validationErrors =
      error?.response?.data
        ?.errors;

    errorMessage.value =
      validationErrors
        ?.table?.[0] ||
      validationErrors
        ?.table_id?.[0] ||
      error?.response?.data
        ?.message ||
      "Unable to split the merged table group.";
  } finally {
    submitting.value = false;
  }
};
</script>

<template>
  <Teleport to="body">
    <Transition
      name="split-table-modal"
    >
      <div
        v-if="show"
        class="split-table-backdrop"
        @click="handleBackdropClick"
      >
        <div
          class="split-table-dialog"
          role="dialog"
          aria-modal="true"
          aria-labelledby="split-table-title"
        >
          <div
            class="split-table-header"
          >
            <div
              class="split-table-icon"
            >
              <i
                class="bi bi-distribute-horizontal"
              ></i>
            </div>

            <div
              class="split-table-header-content"
            >
              <p
                class="split-table-eyebrow"
              >
                Table Management
              </p>

              <h2
                id="split-table-title"
              >
                Split Merged Tables
              </h2>

              <p>
                Separate every table in
                this connected group.
              </p>
            </div>

            <button
              type="button"
              class="split-table-close"
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
            class="split-table-body"
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
              v-if="!hasMergeGroup"
              class="split-table-empty"
            >
              <i
                class="bi bi-exclamation-circle"
              ></i>

              <div>
                <strong>
                  No merged group found
                </strong>

                <span>
                  This table is not currently
                  connected to another table.
                </span>
              </div>
            </div>

            <template v-else>
              <div
                class="split-table-warning"
              >
                <i
                  class="bi bi-exclamation-triangle-fill"
                ></i>

                <div>
                  <strong>
                    Entire group will be split
                  </strong>

                  <p>
                    All connected tables will
                    become standalone tables.
                  </p>
                </div>
              </div>

              <div
                class="split-selected-table"
              >
                <div>
                  <span>
                    Selected table
                  </span>

                  <strong>
                    {{
                      table?.table_name
                    }}
                  </strong>

                  <small>
                    Table #{{ table?.id }}
                  </small>
                </div>

                <span
                  class="split-selected-status"
                >
                  {{
                    selectedTableStatus
                  }}
                </span>
              </div>

              <section
                class="split-group-section"
              >
                <div
                  class="split-section-heading"
                >
                  <div>
                    <h3>
                      Connected Tables
                    </h3>

                    <p>
                      Every table below will
                      be separated.
                    </p>
                  </div>

                  <span
                    class="split-count-badge"
                  >
                    {{
                      mergeGroupCount
                    }}
                    tables
                  </span>
                </div>

                <div
                  v-if="
                    mergeGroupTables.length
                  "
                  class="split-group-list"
                >
                  <div
                    v-for="
                      groupTable in
                      mergeGroupTables
                    "
                    :key="
                      groupTable.id
                    "
                    class="split-group-item"
                  >
                    <div
                      class="split-group-main"
                    >
                      <div
                        class="split-group-icon"
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

                        <small>
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
                              .section_label ||
                            groupTable
                              .section
                          }}
                        </small>
                      </div>
                    </div>

                    <span
                      :class="[
                        'split-group-badge',
                        groupTable.is_master
                          ? 'split-group-badge--master'
                          : 'split-group-badge--connected',
                      ]"
                    >
                      {{
                        groupTable.is_master
                          ? "Master"
                          : "Connected"
                      }}
                    </span>
                  </div>
                </div>

                <div
                  v-else
                  class="split-id-list"
                >
                  <div
                    v-for="
                      tableId in
                      mergeGroupIds
                    "
                    :key="tableId"
                    class="split-id-item"
                  >
                    <i
                      class="bi bi-table"
                    ></i>

                    <span>
                      Table #{{ tableId }}
                    </span>
                  </div>
                </div>
              </section>

              <div
                class="split-result-preview"
              >
                <div>
                  <span>
                    Current state
                  </span>

                  <strong>
                    Merged Group
                  </strong>
                </div>

                <i
                  class="bi bi-arrow-right"
                ></i>

                <div>
                  <span>
                    After split
                  </span>

                  <strong>
                    Standalone Tables
                  </strong>
                </div>
              </div>

              <div
                class="split-capacity-info"
              >
                <i
                  class="bi bi-people-fill"
                ></i>

                <span>
                  Combined capacity
                </span>

                <strong>
                  {{ totalCapacity }}
                </strong>
              </div>
            </template>
          </div>

          <div
            class="split-table-footer"
          >
            <button
              type="button"
              class="btn btn-light"
              :disabled="submitting"
              @click="closeModal"
            >
              {{
                hasMergeGroup
                  ? "Cancel"
                  : "Close"
              }}
            </button>

            <button
              v-if="hasMergeGroup"
              type="button"
              class="btn btn-secondary"
              :disabled="!canSubmit"
              @click="confirmSplit"
            >
              <span
                v-if="submitting"
                class="spinner-border spinner-border-sm me-2"
              ></span>

              <i
                v-else
                class="bi bi-distribute-horizontal me-1"
              ></i>

              {{
                submitting
                  ? "Splitting..."
                  : "Split All Tables"
              }}
            </button>
          </div>
        </div>
      </div>
    </Transition>
  </Teleport>
</template>

<style
  src="@/assets/css/split-table-modal.css"
></style>