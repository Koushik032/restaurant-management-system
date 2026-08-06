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
  "deleted",
]);

const deleting = ref(false);
const errorMessage = ref("");

/*
|--------------------------------------------------------------------------
| Table information
|--------------------------------------------------------------------------
*/

const tableName = computed(() => {
  return (
    props.table?.table_name ||
    "this table"
  );
});

const effectiveStatus = computed(() => {
  return (
    props.table?.current_status ||
    props.table?.status ||
    "available"
  );
});

const effectiveStatusLabel = computed(() => {
  return (
    props.table?.current_status_label ||
    props.table?.status_label ||
    effectiveStatus.value
  );
});

/*
|--------------------------------------------------------------------------
| Delete restrictions
|--------------------------------------------------------------------------
*/

const isOccupied = computed(() => {
  return (
    effectiveStatus.value ===
    "occupied"
  );
});

const hasActiveReservation =
  computed(() => {
    return Boolean(
      props.table
        ?.has_active_reservation
    );
  });

const hasUpcomingReservation =
  computed(() => {
    return Boolean(
      props.table
        ?.has_upcoming_reservation
    );
  });

const hasValidReservation =
  computed(() => {
    return (
      hasActiveReservation.value ||
      hasUpcomingReservation.value
    );
  });

const isMerged = computed(() => {
  return Boolean(
    props.table?.is_merged ||
    props.table?.is_merge_master ||
    props.table?.is_merge_child ||
    props.table?.merged_with_id ||
    props.table?.merge_master_id
  );
});

const cannotDelete = computed(() => {
  return (
    isOccupied.value ||
    hasValidReservation.value ||
    isMerged.value
  );
});

const restrictionMessage =
  computed(() => {
    if (isOccupied.value) {
      return (
        "This table is currently occupied. " +
        "Complete or move the active order before deleting it."
      );
    }

    if (
      hasActiveReservation.value
    ) {
      return (
        "This table currently has an active reservation. " +
        "Complete or cancel the reservation before deleting it."
      );
    }

    if (
      hasUpcomingReservation.value
    ) {
      return (
        "This table has an upcoming reservation. " +
        "Cancel the reservation before deleting it."
      );
    }

    if (isMerged.value) {
      return (
        "This table is part of a merged table group. " +
        "Split the table group before deleting it."
      );
    }

    return "";
  });

/*
|--------------------------------------------------------------------------
| Reservation display
|--------------------------------------------------------------------------
*/

const reservationDisplay =
  computed(() => {
    if (
      props.table
        ?.reservation_display
    ) {
      return props.table
        .reservation_display;
    }

    const date =
      props.table
        ?.reservation_date;

    const startTime =
      props.table
        ?.reservation_start_time;

    const endTime =
      props.table
        ?.reservation_end_time;

    if (
      date &&
      startTime &&
      endTime
    ) {
      return (
        `${date}, ` +
        `${startTime} - ${endTime}`
      );
    }

    return "";
  });

/*
|--------------------------------------------------------------------------
| Modal handling
|--------------------------------------------------------------------------
*/

watch(
  () => props.show,
  (isVisible) => {
    if (isVisible) {
      errorMessage.value = "";
    }
  }
);

const closeModal = () => {
  if (deleting.value) {
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
| Delete table
|--------------------------------------------------------------------------
*/

const confirmDelete = async () => {
  if (
    !props.table?.id ||
    cannotDelete.value
  ) {
    return;
  }

  deleting.value = true;
  errorMessage.value = "";

  try {
    const response =
      await restaurantTableService
        .deleteTable(
          props.table.id
        );

    emit(
      "deleted",
      response
    );
  } catch (error) {
    console.error(
      "Table deletion failed:",
      error?.response?.data ||
        error
    );

    errorMessage.value =
      error?.response?.data
        ?.message ||
      "Unable to delete the table.";
  } finally {
    deleting.value = false;
  }
};
</script>

<template>
  <Teleport to="body">
    <Transition
      name="delete-table-modal"
    >
      <div
        v-if="show"
        class="delete-table-backdrop"
        @click="handleBackdropClick"
      >
        <div
          class="delete-table-dialog"
          role="dialog"
          aria-modal="true"
          aria-labelledby="delete-table-title"
        >
          <div
            class="delete-table-icon"
            :class="{
              'delete-table-icon--warning':
                cannotDelete,
            }"
          >
            <i
              :class="
                cannotDelete
                  ? 'bi bi-exclamation-triangle'
                  : 'bi bi-trash3'
              "
            ></i>
          </div>

          <div
            class="delete-table-content"
          >
            <p
              class="delete-table-eyebrow"
            >
              Table Management
            </p>

            <h2
              id="delete-table-title"
            >
              {{
                cannotDelete
                  ? "Cannot Delete Table"
                  : "Delete Table?"
              }}
            </h2>

            <p
              v-if="!cannotDelete"
              class="delete-table-description"
            >
              Are you sure you want
              to delete

              <strong>
                {{ tableName }}
              </strong>

              ? This table will be
              removed from the active
              table list.
            </p>

            <p
              v-else
              class="delete-table-description"
            >
              {{ restrictionMessage }}
            </p>

            <div
              v-if="table"
              class="delete-table-summary"
            >
              <div
                class="delete-table-summary__row"
              >
                <span>
                  Table
                </span>

                <strong>
                  {{
                    table.table_name
                  }}
                </strong>
              </div>

              <div
                class="delete-table-summary__row"
              >
                <span>
                  Capacity
                </span>

                <strong>
                  {{
                    table.capacity
                  }}
                  Guests
                </strong>
              </div>

              <div
                class="delete-table-summary__row"
              >
                <span>
                  Section
                </span>

                <strong>
                  {{
                    table.section_label ||
                    table.section
                  }}
                </strong>
              </div>

              <div
                class="delete-table-summary__row"
              >
                <span>
                  Status
                </span>

                <span
                  class="delete-table-status"
                  :class="
                    `delete-table-status--${effectiveStatus}`
                  "
                >
                  {{
                    effectiveStatusLabel
                  }}
                </span>
              </div>

              <div
                v-if="
                  hasValidReservation &&
                  reservationDisplay
                "
                class="delete-table-summary__row"
              >
                <span>
                  Reservation
                </span>

                <strong
                  class="delete-table-reservation"
                >
                  {{
                    reservationDisplay
                  }}
                </strong>
              </div>

              <div
                v-if="isMerged"
                class="delete-table-summary__row"
              >
                <span>
                  Merge status
                </span>

                <strong>
                  {{
                    table.is_merge_master
                      ? "Master table"
                      : "Connected table"
                  }}
                </strong>
              </div>
            </div>

            <div
              v-if="errorMessage"
              class="alert alert-danger py-2 mt-3 mb-0"
            >
              <i
                class="bi bi-exclamation-circle me-1"
              ></i>

              {{ errorMessage }}
            </div>
          </div>

          <div
            class="delete-table-footer"
          >
            <button
              type="button"
              class="btn btn-light"
              :disabled="deleting"
              @click="closeModal"
            >
              {{
                cannotDelete
                  ? "Close"
                  : "Cancel"
              }}
            </button>

            <button
              v-if="!cannotDelete"
              type="button"
              class="btn btn-danger"
              :disabled="deleting"
              @click="confirmDelete"
            >
              <span
                v-if="deleting"
                class="spinner-border spinner-border-sm me-2"
              ></span>

              <i
                v-else
                class="bi bi-trash3 me-1"
              ></i>

              {{
                deleting
                  ? "Deleting..."
                  : "Delete Table"
              }}
            </button>
          </div>
        </div>
      </div>
    </Transition>
  </Teleport>
</template>

<style
  src="@/assets/css/delete-table-modal.css"
></style>