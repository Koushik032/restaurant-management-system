<script setup>
import {
  computed,
  reactive,
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
});

const emit = defineEmits([
  "close",
  "created",
]);

const form = reactive({
  table_name: "",
  capacity: "",
  section: "",
});

const state = reactive({
  submitting: false,
  errorMessage: "",
  fieldErrors: {},
});

const sections = [
  {
    value: "ac",
    label: "AC Section",
    description:
      "Indoor air-conditioned seating.",
    icon: "bi-snow",
  },
  {
    value: "non_ac",
    label: "Non-AC Section",
    description:
      "Regular indoor seating area.",
    icon: "bi-house",
  },
  {
    value: "outdoor",
    label: "Outdoor Section",
    description:
      "Open-air restaurant seating.",
    icon: "bi-tree",
  },
];

const selectedSection = computed(
  () => {
    return sections.find(
      (section) =>
        section.value ===
        form.section
    );
  }
);

const isFormValid = computed(
  () => {
    const capacity = Number(
      form.capacity
    );

    return Boolean(
      form.table_name.trim() &&
      form.section &&
      Number.isInteger(capacity) &&
      capacity >= 1 &&
      capacity <= 100
    );
  }
);

const resetForm = () => {
  Object.assign(form, {
    table_name: "",
    capacity: "",
    section: "",
  });

  state.errorMessage = "";
  state.fieldErrors = {};
};

const closeModal = () => {
  if (state.submitting) {
    return;
  }

  emit("close");
};

const selectSection = (
  section
) => {
  form.section = section;

  if (
    state.fieldErrors.section
  ) {
    delete state
      .fieldErrors
      .section;
  }
};

const clearFieldError = (
  field
) => {
  if (
    state.fieldErrors[field]
  ) {
    delete state
      .fieldErrors[field];
  }
};

const normalizeErrors = (
  errors
) => {
  const normalized = {};

  Object.entries(
    errors ?? {}
  ).forEach(
    ([field, messages]) => {
      normalized[field] =
        Array.isArray(messages)
          ? messages[0]
          : messages;
    }
  );

  return normalized;
};

const submitForm = async () => {
  state.errorMessage = "";
  state.fieldErrors = {};

  if (!isFormValid.value) {
    state.errorMessage =
      "Please complete all required fields correctly.";

    return;
  }

  state.submitting = true;

  try {
    const response =
      await restaurantTableService
        .createTable({
          table_name:
            form.table_name.trim(),

          capacity: Number(
            form.capacity
          ),

          section:
            form.section,
        });

    emit("created", response);
  } catch (error) {
    console.error(
      "Restaurant table creation failed:",
      error?.response?.data ||
        error
    );

    state.fieldErrors =
      normalizeErrors(
        error?.response?.data
          ?.errors
      );

    state.errorMessage =
      error?.response?.data
        ?.message ||
      "Unable to create the restaurant table.";
  } finally {
    state.submitting = false;
  }
};

watch(
  () => props.show,
  (show) => {
    if (show) {
      resetForm();
    }
  }
);
</script>

<template>
  <Teleport to="body">
    <Transition
      name="add-table-modal"
    >
      <div
        v-if="show"
        class="add-table-modal"
        role="dialog"
        aria-modal="true"
        aria-labelledby="add-table-title"
        @click.self="closeModal"
      >
        <div
          class="add-table-modal__panel"
        >
          <!-- Header -->
          <header
            class="add-table-modal__header"
          >
            <div
              class="add-table-modal__header-icon"
            >
              <i
                class="bi bi-plus-lg"
              ></i>
            </div>

            <div
              class="add-table-modal__header-content"
            >
              <p>
                Restaurant floor
              </p>

              <h2
                id="add-table-title"
              >
                Add New Table
              </h2>

              <span>
                Create a new restaurant
                table and assign its
                capacity and section.
              </span>
            </div>

            <button
              type="button"
              class="add-table-modal__close"
              :disabled="
                state.submitting
              "
              aria-label="Close modal"
              @click="closeModal"
            >
              <i
                class="bi bi-x-lg"
              ></i>
            </button>
          </header>

          <!-- Default state notice -->
          <div
            class="add-table-modal__defaults"
          >
            <div>
              <i
                class="bi bi-check-circle-fill"
              ></i>
            </div>

            <div>
              <strong>
                Initial table state
              </strong>

              <p>
                The table will be
                created as available,
                without any reservation
                or merged group.
              </p>
            </div>
          </div>

          <!-- Error -->
          <div
            v-if="
              state.errorMessage
            "
            class="add-table-modal__error"
          >
            <i
              class="bi bi-exclamation-circle-fill"
            ></i>

            <span>
              {{
                state.errorMessage
              }}
            </span>
          </div>

          <!-- Form -->
          <form
            class="add-table-form"
            @submit.prevent="
              submitForm
            "
          >
            <!-- Basic information -->
            <section
              class="add-table-form__section"
            >
              <div
                class="add-table-form__section-heading"
              >
                <span>
                  01
                </span>

                <div>
                  <h3>
                    Basic information
                  </h3>

                  <p>
                    Enter a unique table
                    name and guest
                    capacity.
                  </p>
                </div>
              </div>

              <div
                class="add-table-form__grid"
              >
                <!-- Table name -->
                <div
                  class="add-table-form__group"
                >
                  <label
                    for="add-table-name"
                  >
                    Table name

                    <span>*</span>
                  </label>

                  <div
                    class="add-table-form__input"
                    :class="{
                      'add-table-form__input--error':
                        state
                          .fieldErrors
                          .table_name,
                    }"
                  >
                    <i
                      class="bi bi-table"
                    ></i>

                    <input
                      id="add-table-name"
                      v-model="
                        form.table_name
                      "
                      type="text"
                      maxlength="100"
                      autocomplete="off"
                      placeholder="Example: Table 01"
                      :disabled="
                        state.submitting
                      "
                      @input="
                        clearFieldError(
                          'table_name'
                        )
                      "
                    />
                  </div>

                  <small
                    v-if="
                      state.fieldErrors
                        .table_name
                    "
                    class="add-table-form__field-error"
                  >
                    {{
                      state.fieldErrors
                        .table_name
                    }}
                  </small>

                  <small v-else>
                    Use a clear and
                    unique table name.
                  </small>
                </div>

                <!-- Capacity -->
                <div
                  class="add-table-form__group"
                >
                  <label
                    for="add-table-capacity"
                  >
                    Capacity

                    <span>*</span>
                  </label>

                  <div
                    class="add-table-form__input"
                    :class="{
                      'add-table-form__input--error':
                        state
                          .fieldErrors
                          .capacity,
                    }"
                  >
                    <i
                      class="bi bi-people"
                    ></i>

                    <input
                      id="add-table-capacity"
                      v-model="
                        form.capacity
                      "
                      type="number"
                      min="1"
                      max="100"
                      step="1"
                      placeholder="Example: 4"
                      :disabled="
                        state.submitting
                      "
                      @input="
                        clearFieldError(
                          'capacity'
                        )
                      "
                    />
                  </div>

                  <small
                    v-if="
                      state.fieldErrors
                        .capacity
                    "
                    class="add-table-form__field-error"
                  >
                    {{
                      state.fieldErrors
                        .capacity
                    }}
                  </small>

                  <small v-else>
                    Allowed capacity:
                    1–100 guests.
                  </small>
                </div>
              </div>
            </section>

            <!-- Section -->
            <section
              class="add-table-form__section"
            >
              <div
                class="add-table-form__section-heading"
              >
                <span>
                  02
                </span>

                <div>
                  <h3>
                    Select section
                  </h3>

                  <p>
                    Choose where this
                    table is located.
                  </p>
                </div>
              </div>

              <div
                class="add-table-form__sections"
              >
                <button
                  v-for="
                    section in sections
                  "
                  :key="
                    section.value
                  "
                  type="button"
                  class="add-table-form__section-option"
                  :class="{
                    'add-table-form__section-option--active':
                      form.section ===
                      section.value,

                    'add-table-form__section-option--error':
                      state
                        .fieldErrors
                        .section,
                  }"
                  :disabled="
                    state.submitting
                  "
                  @click="
                    selectSection(
                      section.value
                    )
                  "
                >
                  <span
                    class="add-table-form__section-icon"
                  >
                    <i
                      :class="[
                        'bi',
                        section.icon,
                      ]"
                    ></i>
                  </span>

                  <span
                    class="add-table-form__section-content"
                  >
                    <strong>
                      {{
                        section.label
                      }}
                    </strong>

                    <small>
                      {{
                        section.description
                      }}
                    </small>
                  </span>

                  <i
                    class="bi add-table-form__section-check"
                    :class="
                      form.section ===
                      section.value
                        ? 'bi-check-circle-fill'
                        : 'bi-circle'
                    "
                  ></i>
                </button>
              </div>

              <small
                v-if="
                  state.fieldErrors
                    .section
                "
                class="add-table-form__field-error"
              >
                {{
                  state.fieldErrors
                    .section
                }}
              </small>
            </section>

            <!-- Preview -->
            <section
              class="add-table-preview"
            >
              <div
                class="add-table-preview__heading"
              >
                <i
                  class="bi bi-eye"
                ></i>

                <span>
                  Table preview
                </span>
              </div>

              <div
                class="add-table-preview__row"
              >
                <div
                  class="add-table-preview__identity"
                >
                  <span
                    class="add-table-preview__icon"
                  >
                    <i
                      class="bi bi-table"
                    ></i>
                  </span>

                  <div>
                    <strong>
                      {{
                        form.table_name
                          .trim() ||
                        "New Table"
                      }}
                    </strong>

                    <small>
                      New restaurant
                      table
                    </small>
                  </div>
                </div>

                <div
                  class="add-table-preview__item"
                >
                  <span>
                    Capacity
                  </span>

                  <strong>
                    {{
                      form.capacity ||
                      "—"
                    }}
                    guests
                  </strong>
                </div>

                <div
                  class="add-table-preview__item"
                >
                  <span>
                    Section
                  </span>

                  <strong>
                    {{
                      selectedSection
                        ?.label ||
                      "Not selected"
                    }}
                  </strong>
                </div>

                <div
                  class="add-table-preview__status"
                >
                  <i
                    class="bi bi-check-circle-fill"
                  ></i>

                  Available
                </div>
              </div>
            </section>

            <!-- Footer -->
            <footer
              class="add-table-modal__footer"
            >
              <button
                type="button"
                class="add-table-modal__cancel"
                :disabled="
                  state.submitting
                "
                @click="closeModal"
              >
                Cancel
              </button>

              <button
                type="submit"
                class="add-table-modal__submit"
                :disabled="
                  state.submitting ||
                  !isFormValid
                "
              >
                <span
                  v-if="
                    state.submitting
                  "
                  class="add-table-modal__spinner"
                ></span>

                <i
                  v-else
                  class="bi bi-plus-circle-fill"
                ></i>

                {{
                  state.submitting
                    ? "Creating table..."
                    : "Create table"
                }}
              </button>
            </footer>
          </form>
        </div>
      </div>
    </Transition>
  </Teleport>
</template>

<style
  scoped
  src="@/assets/css/add-table-modal.css"
></style>