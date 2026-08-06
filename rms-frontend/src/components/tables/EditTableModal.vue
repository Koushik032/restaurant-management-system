<script setup>
import {
  computed,
  reactive,
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
  "updated",
]);

const submitting = ref(false);
const loadingOptions = ref(false);

const generalError = ref("");
const errors = reactive({});

const currentMergeGroup = ref([]);
const availableMergeTables = ref([]);

const form = reactive({
  table_name: "",
  capacity: 2,
  section: "ac",
  status: "available",
  notes: "",

  reservation_date: "",
  reservation_start_time: "",
  reservation_end_time: "",

  merge_table_ids: [],
});

/*
|--------------------------------------------------------------------------
| Computed properties
|--------------------------------------------------------------------------
*/

const isReservedStatus = computed(() => {
  return form.status === "reserved";
});

const canShowMergeOptions = computed(() => {
  return [
    "occupied",
    "reserved",
  ].includes(form.status);
});

const isMerged = computed(() => {
  return (
    currentMergeGroup.value.length > 1 ||
    Boolean(props.table?.is_merged)
  );
});

const selectedMergeTables = computed(() => {
  return availableMergeTables.value.filter(
    (table) =>
      form.merge_table_ids.includes(
        Number(table.id)
      )
  );
});

const selectedMergeCapacity = computed(() => {
  return selectedMergeTables.value.reduce(
    (total, table) =>
      total +
      Number(table.capacity || 0),
    0
  );
});

const currentGroupCapacity = computed(() => {
  if (!isMerged.value) {
    return Number(form.capacity || 0);
  }

  return currentMergeGroup.value.reduce(
    (total, table) =>
      total +
      Number(table.capacity || 0),
    0
  );
});

const finalCapacity = computed(() => {
  if (!isMerged.value) {
    return (
      Number(form.capacity || 0) +
      selectedMergeCapacity.value
    );
  }

  const previousCapacity = Number(
    props.table?.capacity || 0
  );

  return (
    currentGroupCapacity.value -
    previousCapacity +
    Number(form.capacity || 0) +
    selectedMergeCapacity.value
  );
});

const minimumReservationDate = computed(() => {
  const today = new Date();

  const year = today.getFullYear();

  const month = String(
    today.getMonth() + 1
  ).padStart(2, "0");

  const day = String(
    today.getDate()
  ).padStart(2, "0");

  return `${year}-${month}-${day}`;
});

/*
|--------------------------------------------------------------------------
| Error helpers
|--------------------------------------------------------------------------
*/

const resetErrors = () => {
  generalError.value = "";

  Object.keys(errors).forEach(
    (key) => {
      delete errors[key];
    }
  );
};

const getError = (field) => {
  const fieldErrors = errors[field];

  if (
    Array.isArray(fieldErrors) &&
    fieldErrors.length
  ) {
    return fieldErrors[0];
  }

  return "";
};

/*
|--------------------------------------------------------------------------
| Form population
|--------------------------------------------------------------------------
*/

const getEditStatus = (table) => {
  if (!table) {
    return "available";
  }

  if (table.edit_status) {
    return table.edit_status;
  }

  if (
    table.has_active_reservation ||
    table.has_upcoming_reservation
  ) {
    return "reserved";
  }

  return (
    table.status ??
    table.current_status ??
    "available"
  );
};

const populateForm = () => {
  resetErrors();

  if (!props.table) {
    return;
  }

  form.table_name =
    props.table.table_name ?? "";

  form.capacity = Number(
    props.table.capacity ?? 2
  );

  form.section =
    props.table.section ?? "ac";

  form.status = getEditStatus(
    props.table
  );

  form.notes =
    props.table.notes ?? "";

  form.reservation_date =
    props.table.reservation_date ??
    props.table.edit_reservation_date ??
    "";

  form.reservation_start_time =
    props.table.reservation_start_time ??
    props.table
      .edit_reservation_start_time ??
    "";

  form.reservation_end_time =
    props.table.reservation_end_time ??
    props.table
      .edit_reservation_end_time ??
    "";

  form.merge_table_ids = [];
};

/*
|--------------------------------------------------------------------------
| Load merge/edit options
|--------------------------------------------------------------------------
*/

const loadEditOptions = async () => {
  if (!props.table?.id) {
    return;
  }

  loadingOptions.value = true;

  currentMergeGroup.value = [];
  availableMergeTables.value = [];

  try {
    const response =
      await restaurantTableService
        .getEditOptions(
          props.table.id
        );

    currentMergeGroup.value =
      response?.data
        ?.current_group ?? [];

    availableMergeTables.value =
      response?.data
        ?.available_tables ?? [];
  } catch (error) {
    console.error(
      "Unable to load edit options:",
      error?.response?.data ||
        error
    );

    generalError.value =
      error?.response?.data
        ?.message ||
      "Unable to load table edit options.";
  } finally {
    loadingOptions.value = false;
  }
};

/*
|--------------------------------------------------------------------------
| Watchers
|--------------------------------------------------------------------------
*/

watch(
  () => props.table,
  () => {
    populateForm();
  },
  {
    deep: true,
    immediate: true,
  }
);

watch(
  () => props.show,
  async (isVisible) => {
    if (isVisible) {
      populateForm();

      await loadEditOptions();

      return;
    }

    currentMergeGroup.value = [];
    availableMergeTables.value = [];
    form.merge_table_ids = [];

    resetErrors();
  }
);

watch(
  () => form.status,
  (
    newStatus,
    previousStatus
  ) => {
    if (
      previousStatus === "reserved" &&
      newStatus !== "reserved"
    ) {
      form.reservation_date = "";
      form.reservation_start_time = "";
      form.reservation_end_time = "";
    }

    if (
      ![
        "occupied",
        "reserved",
      ].includes(newStatus)
    ) {
      form.merge_table_ids = [];
    }

    resetErrors();
  }
);

/*
|--------------------------------------------------------------------------
| Merge selection
|--------------------------------------------------------------------------
*/

const toggleMergeTable = (
  tableId
) => {
  const numericId =
    Number(tableId);

  const index =
    form.merge_table_ids.indexOf(
      numericId
    );

  if (index >= 0) {
    form.merge_table_ids.splice(
      index,
      1
    );

    return;
  }

  form.merge_table_ids.push(
    numericId
  );
};

const isMergeTableSelected = (
  tableId
) => {
  return form.merge_table_ids.includes(
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

  resetErrors();

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
| Reservation date-time
|--------------------------------------------------------------------------
*/

const createReservationDateTime = (
  date,
  time
) => {
  if (!date || !time) {
    return null;
  }

  return `${date} ${time}:00`;
};

/*
|--------------------------------------------------------------------------
| Validation
|--------------------------------------------------------------------------
*/

const validateForm = () => {
  resetErrors();

  if (!form.table_name.trim()) {
    errors.table_name = [
      "Table name is required.",
    ];
  }

  if (
    !form.capacity ||
    Number(form.capacity) < 1
  ) {
    errors.capacity = [
      "Capacity must be at least 1.",
    ];
  } else if (
    Number(form.capacity) > 100
  ) {
    errors.capacity = [
      "Capacity cannot exceed 100.",
    ];
  }

  if (!form.section) {
    errors.section = [
      "Please select a section.",
    ];
  }

  if (!form.status) {
    errors.status = [
      "Please select a status.",
    ];
  }

  if (isReservedStatus.value) {
    if (!form.reservation_date) {
      errors.reservation_start_at = [
        "Reservation date is required.",
      ];
    }

    if (
      !form.reservation_start_time
    ) {
      errors.reservation_start_at = [
        "Reservation start time is required.",
      ];
    }

    if (
      !form.reservation_end_time
    ) {
      errors.reservation_end_at = [
        "Reservation end time is required.",
      ];
    }

    if (
      form.reservation_date &&
      form.reservation_start_time &&
      form.reservation_end_time
    ) {
      const startDateTime =
        new Date(
          `${form.reservation_date}T${form.reservation_start_time}:00`
        );

      const endDateTime =
        new Date(
          `${form.reservation_date}T${form.reservation_end_time}:00`
        );

      if (
        Number.isNaN(
          startDateTime.getTime()
        )
      ) {
        errors.reservation_start_at = [
          "Invalid reservation start date or time.",
        ];
      }

      if (
        Number.isNaN(
          endDateTime.getTime()
        )
      ) {
        errors.reservation_end_at = [
          "Invalid reservation end date or time.",
        ];
      }

      if (
        !Number.isNaN(
          startDateTime.getTime()
        ) &&
        !Number.isNaN(
          endDateTime.getTime()
        )
      ) {
        if (
          endDateTime.getTime() <=
          startDateTime.getTime()
        ) {
          errors.reservation_end_at = [
            "End time must be after start time.",
          ];
        }

        if (
          endDateTime.getTime() <=
          Date.now()
        ) {
          errors.reservation_end_at = [
            "Reservation end time must be in the future.",
          ];
        }
      }
    }
  }

  return (
    Object.keys(errors).length === 0
  );
};

/*
|--------------------------------------------------------------------------
| Submit
|--------------------------------------------------------------------------
*/

const submitForm = async () => {
  if (
    !props.table?.id ||
    !validateForm()
  ) {
    return;
  }

  submitting.value = true;

  resetErrors();

  try {
    const isReserved =
      form.status === "reserved";

    const payload = {
      table_name:
        form.table_name.trim(),

      capacity:
        Number(form.capacity),

      section:
        form.section,

      status:
        form.status,

      reservation_start_at:
        isReserved
          ? createReservationDateTime(
              form.reservation_date,
              form.reservation_start_time
            )
          : null,

      reservation_end_at:
        isReserved
          ? createReservationDateTime(
              form.reservation_date,
              form.reservation_end_time
            )
          : null,

      notes:
        form.notes.trim() ||
        null,

      merge_table_ids:
        canShowMergeOptions.value
          ? form.merge_table_ids
          : [],
    };

    const response =
      await restaurantTableService
        .updateTable(
          props.table.id,
          payload
        );

    emit(
      "updated",
      response
    );
  } catch (error) {
    console.error(
      "Table update failed:",
      error?.response?.data ||
        error
    );

    if (
      error?.response?.status ===
      422
    ) {
      Object.assign(
        errors,
        error.response.data
          ?.errors ?? {}
      );
    }

    generalError.value =
      error?.response?.data
        ?.message ||
      "Unable to update the table.";
  } finally {
    submitting.value = false;
  }
};
</script>

<template>
  <Teleport to="body">
    <Transition name="table-modal">
      <div
        v-if="show"
        class="table-modal-backdrop"
        @click="handleBackdropClick"
      >
        <div
          class="table-modal-dialog"
          role="dialog"
          aria-modal="true"
          aria-labelledby="edit-table-title"
        >
          <div class="table-modal-header">
            <div>
              <p class="table-modal-eyebrow">
                Table Management
              </p>

              <h2 id="edit-table-title">
                Edit Table
              </h2>

              <p>
                Update table details,
                reservation and merge
                information.
              </p>
            </div>

            <button
              type="button"
              class="table-modal-close"
              :disabled="submitting"
              aria-label="Close modal"
              @click="closeModal"
            >
              <i class="bi bi-x-lg"></i>
            </button>
          </div>

          <form
            @submit.prevent="submitForm"
          >
            <div class="table-modal-body">
              <div
                v-if="generalError"
                class="alert alert-danger py-2"
              >
                {{ generalError }}
              </div>

              <div class="row g-3">
                <div class="col-md-7">
                  <label
                    for="edit-table-name"
                    class="form-label"
                  >
                    Table name

                    <span class="text-danger">
                      *
                    </span>
                  </label>

                  <input
                    id="edit-table-name"
                    v-model="form.table_name"
                    type="text"
                    class="form-control"
                    :class="{
                      'is-invalid':
                        errors.table_name,
                    }"
                    maxlength="100"
                    placeholder="Example: Table A-01"
                  />

                  <div
                    v-if="errors.table_name"
                    class="invalid-feedback"
                  >
                    {{
                      getError(
                        "table_name"
                      )
                    }}
                  </div>
                </div>

                <div class="col-md-5">
                  <label
                    for="edit-table-capacity"
                    class="form-label"
                  >
                    Capacity

                    <span class="text-danger">
                      *
                    </span>
                  </label>

                  <input
                    id="edit-table-capacity"
                    v-model.number="
                      form.capacity
                    "
                    type="number"
                    min="1"
                    max="100"
                    class="form-control"
                    :class="{
                      'is-invalid':
                        errors.capacity,
                    }"
                  />

                  <div
                    v-if="errors.capacity"
                    class="invalid-feedback"
                  >
                    {{
                      getError(
                        "capacity"
                      )
                    }}
                  </div>
                </div>

                <div class="col-md-6">
                  <label
                    for="edit-table-section"
                    class="form-label"
                  >
                    Section

                    <span class="text-danger">
                      *
                    </span>
                  </label>

                  <select
                    id="edit-table-section"
                    v-model="form.section"
                    class="form-select"
                    :class="{
                      'is-invalid':
                        errors.section,
                    }"
                  >
                    <option value="ac">
                      AC
                    </option>

                    <option value="non_ac">
                      Non-AC
                    </option>

                    <option value="outdoor">
                      Outdoor
                    </option>
                  </select>

                  <div
                    v-if="errors.section"
                    class="invalid-feedback"
                  >
                    {{
                      getError(
                        "section"
                      )
                    }}
                  </div>
                </div>

                <div class="col-md-6">
                  <label
                    for="edit-table-status"
                    class="form-label"
                  >
                    Status

                    <span class="text-danger">
                      *
                    </span>
                  </label>

                  <select
                    id="edit-table-status"
                    v-model="form.status"
                    class="form-select"
                    :class="{
                      'is-invalid':
                        errors.status,
                    }"
                  >
                    <option value="available">
                      Available
                    </option>

                    <option value="occupied">
                      Occupied
                    </option>

                    <option value="reserved">
                      Reserved
                    </option>

                    <option value="cleaning">
                      Cleaning
                    </option>
                  </select>

                  <div
                    v-if="errors.status"
                    class="invalid-feedback"
                  >
                    {{
                      getError(
                        "status"
                      )
                    }}
                  </div>
                </div>

                <!-- Reservation -->
                <div
                  v-if="isReservedStatus"
                  class="col-12"
                >
                  <section
                    class="reservation-fields"
                  >
                    <div
                      class="reservation-heading"
                    >
                      <div
                        class="reservation-heading-icon"
                      >
                        <i
                          class="bi bi-calendar-event"
                        ></i>
                      </div>

                      <div>
                        <strong>
                          Reservation Schedule
                        </strong>

                        <span>
                          Select reservation
                          date, start time and
                          end time.
                        </span>
                      </div>
                    </div>

                    <div class="row g-3">
                      <div class="col-md-4">
                        <label
                          for="edit-reservation-date"
                          class="form-label"
                        >
                          Date

                          <span
                            class="text-danger"
                          >
                            *
                          </span>
                        </label>

                        <input
                          id="edit-reservation-date"
                          v-model="
                            form.reservation_date
                          "
                          type="date"
                          class="form-control"
                          :min="
                            minimumReservationDate
                          "
                          :class="{
                            'is-invalid':
                              errors
                                .reservation_start_at,
                          }"
                        />
                      </div>

                      <div class="col-md-4">
                        <label
                          for="edit-reservation-start-time"
                          class="form-label"
                        >
                          Start time

                          <span
                            class="text-danger"
                          >
                            *
                          </span>
                        </label>

                        <input
                          id="edit-reservation-start-time"
                          v-model="
                            form
                              .reservation_start_time
                          "
                          type="time"
                          class="form-control"
                          :class="{
                            'is-invalid':
                              errors
                                .reservation_start_at,
                          }"
                        />
                      </div>

                      <div class="col-md-4">
                        <label
                          for="edit-reservation-end-time"
                          class="form-label"
                        >
                          End time

                          <span
                            class="text-danger"
                          >
                            *
                          </span>
                        </label>

                        <input
                          id="edit-reservation-end-time"
                          v-model="
                            form
                              .reservation_end_time
                          "
                          type="time"
                          class="form-control"
                          :class="{
                            'is-invalid':
                              errors
                                .reservation_end_at,
                          }"
                        />
                      </div>
                    </div>

                    <div
                      v-if="
                        errors.reservation_start_at
                      "
                      class="reservation-error"
                    >
                      <i
                        class="bi bi-exclamation-circle"
                      ></i>

                      {{
                        getError(
                          "reservation_start_at"
                        )
                      }}
                    </div>

                    <div
                      v-if="
                        errors.reservation_end_at
                      "
                      class="reservation-error"
                    >
                      <i
                        class="bi bi-exclamation-circle"
                      ></i>

                      {{
                        getError(
                          "reservation_end_at"
                        )
                      }}
                    </div>

                    <div
                      v-if="
                        form.reservation_date &&
                        form
                          .reservation_start_time &&
                        form
                          .reservation_end_time
                      "
                      class="reservation-preview"
                    >
                      <i
                        class="bi bi-clock-history"
                      ></i>

                      <span>
                        Reservation:
                      </span>

                      <strong>
                        {{
                          form
                            .reservation_date
                        }}
                        ·
                        {{
                          form
                            .reservation_start_time
                        }}
                        -
                        {{
                          form
                            .reservation_end_time
                        }}
                      </strong>
                    </div>
                  </section>
                </div>

                <div class="col-12">
                  <label
                    for="edit-table-notes"
                    class="form-label"
                  >
                    Notes
                  </label>

                  <textarea
                    id="edit-table-notes"
                    v-model="form.notes"
                    rows="3"
                    maxlength="1000"
                    class="form-control"
                    :class="{
                      'is-invalid':
                        errors.notes,
                    }"
                    placeholder="Optional table notes"
                  ></textarea>

                  <div
                    class="table-note-counter"
                  >
                    {{
                      form.notes.length
                    }}/1000
                  </div>

                  <div
                    v-if="errors.notes"
                    class="invalid-feedback"
                  >
                    {{
                      getError(
                        "notes"
                      )
                    }}
                  </div>
                </div>
              </div>

              <!-- Merge section -->
              <section
                v-if="canShowMergeOptions"
                class="edit-merge-section"
              >
                <div
                  class="edit-merge-heading"
                >
                  <div>
                    <h3>
                      {{
                        isMerged
                          ? "Current Merge Group"
                          : "Merge With Tables"
                      }}
                    </h3>

                    <p>
                      Select available tables
                      to include with this
                      table.
                    </p>
                  </div>

                  <span
                    class="edit-merge-count"
                  >
                    {{
                      form
                        .merge_table_ids
                        .length
                    }}
                    selected
                  </span>
                </div>

                <div
                  v-if="loadingOptions"
                  class="edit-merge-loading"
                >
                  <span
                    class="spinner-border spinner-border-sm"
                  ></span>

                  Loading tables...
                </div>

                <template v-else>
                  <!-- Current group list -->
                  <div
                    v-if="isMerged"
                    class="edit-current-group"
                  >
                    <p
                      class="edit-group-label"
                    >
                      Connected tables
                    </p>

                    <div
                      class="edit-table-list"
                    >
                      <div
                        v-for="
                          groupTable in
                          currentMergeGroup
                        "
                        :key="
                          groupTable.id
                        "
                        class="edit-table-list-item edit-table-list-item--connected"
                      >
                        <div
                          class="edit-table-list-main"
                        >
                          <div
                            class="edit-table-list-icon"
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
                          class="edit-table-list-badge"
                        >
                          {{
                            groupTable
                              .is_master
                              ? "Master"
                              : "Connected"
                          }}
                        </span>
                      </div>
                    </div>

                    <div
                      class="edit-split-notice"
                    >
                      <i
                        class="bi bi-info-circle"
                      ></i>

                      Existing connected
                      tables can only be
                      removed using Split.
                    </div>
                  </div>

                  <!-- Available table list -->
                  <div
                    v-if="
                      availableMergeTables
                        .length
                    "
                  >
                    <p
                      class="edit-group-label"
                    >
                      Available tables
                    </p>

                    <div
                      class="edit-table-list"
                    >
                      <button
                        v-for="
                          availableTable in
                          availableMergeTables
                        "
                        :key="
                          availableTable.id
                        "
                        type="button"
                        class="edit-table-list-item edit-table-list-item--selectable"
                        :class="{
                          'edit-table-list-item--selected':
                            isMergeTableSelected(
                              availableTable.id
                            ),
                        }"
                        @click="
                          toggleMergeTable(
                            availableTable.id
                          )
                        "
                      >
                        <div
                          class="edit-table-list-main"
                        >
                          <div
                            class="edit-table-list-check"
                          >
                            <i
                              :class="
                                isMergeTableSelected(
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
                          class="edit-table-list-status"
                        >
                          Available
                        </span>
                      </button>
                    </div>
                  </div>

                  <div
                    v-else
                    class="edit-merge-empty"
                  >
                    <i
                      class="bi bi-table"
                    ></i>

                    <div>
                      <strong>
                        No available tables
                      </strong>

                      <span>
                        No other table is
                        currently available
                        for merging.
                      </span>
                    </div>
                  </div>

                  <div
                    v-if="
                      isMerged ||
                      form.merge_table_ids
                        .length
                    "
                    class="edit-capacity-preview"
                  >
                    <div>
                      <span>
                        Current
                      </span>

                      <strong>
                        {{
                          isMerged
                            ? currentGroupCapacity
                            : form.capacity
                        }}
                      </strong>
                    </div>

                    <i
                      class="bi bi-plus-lg"
                    ></i>

                    <div>
                      <span>
                        Added
                      </span>

                      <strong>
                        {{
                          selectedMergeCapacity
                        }}
                      </strong>
                    </div>

                    <i
                      class="bi bi-arrow-right"
                    ></i>

                    <div
                      class="edit-capacity-final"
                    >
                      <span>
                        Final
                      </span>

                      <strong>
                        {{
                          finalCapacity
                        }}
                      </strong>
                    </div>
                  </div>

                  <div
                    v-if="
                      errors.merge_table_ids
                    "
                    class="edit-merge-error"
                  >
                    {{
                      getError(
                        "merge_table_ids"
                      )
                    }}
                  </div>
                </template>
              </section>
            </div>

            <div class="table-modal-footer">
              <button
                type="button"
                class="btn btn-light"
                :disabled="submitting"
                @click="closeModal"
              >
                Cancel
              </button>

              <button
                type="submit"
                class="btn btn-primary"
                :disabled="
                  submitting ||
                  loadingOptions
                "
              >
                <span
                  v-if="submitting"
                  class="spinner-border spinner-border-sm me-2"
                ></span>

                <i
                  v-else
                  class="bi bi-check2-circle me-1"
                ></i>

                {{
                  submitting
                    ? "Updating..."
                    : "Update Table"
                }}
              </button>
            </div>
          </form>
        </div>
      </div>
    </Transition>
  </Teleport>
</template>

<style
  src="@/assets/css/edit-table-modal.css"
></style>