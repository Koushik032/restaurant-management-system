<script setup>
import {
  computed,
  reactive,
  watch,
} from "vue";

const props = defineProps({
  show: {
    type: Boolean,
    default: false,
  },

  addOn: {
    type: Object,
    default: null,
  },

  submitting: {
    type: Boolean,
    default: false,
  },

  errorMessage: {
    type: String,
    default: "",
  },

  validationErrors: {
    type: Object,
    default: () => ({}),
  },
});

const emit = defineEmits([
  "close",
  "submit",
]);

const createDefaultForm = () => ({
  add_on_name: "",
  price: "",
  description: "",
  is_available: true,
});

const form = reactive(
  createDefaultForm()
);

const isEditing = computed(
  () => Boolean(props.addOn?.id)
);

const modalTitle = computed(() =>
  isEditing.value
    ? "Edit Add-on"
    : "Add Add-on"
);

const submitLabel = computed(() => {
  if (props.submitting) {
    return isEditing.value
      ? "Updating..."
      : "Creating...";
  }

  return isEditing.value
    ? "Update Add-on"
    : "Create Add-on";
});

const normalizeBoolean = (
  value
) => {
  if (
    value === false ||
    value === 0 ||
    value === "0" ||
    value === "false"
  ) {
    return false;
  }

  return true;
};

const firstError = (field) => {
  const error =
    props.validationErrors?.[field];

  return Array.isArray(error)
    ? error[0] ?? ""
    : error ?? "";
};

const resetForm = () => {
  Object.assign(
    form,
    createDefaultForm()
  );
};

const fillForm = () => {
  resetForm();

  if (!props.addOn) {
    return;
  }

  Object.assign(form, {
    add_on_name:
      props.addOn.add_on_name ?? "",

    price:
      props.addOn.price ?? "",

    description:
      props.addOn.description ?? "",

    is_available:
      normalizeBoolean(
        props.addOn.is_available
      ),
  });
};

const closeModal = () => {
  if (props.submitting) {
    return;
  }

  emit("close");
};

const submitForm = () => {
  emit("submit", {
    add_on_name:
      form.add_on_name.trim(),

    price:
      form.price,

    description:
      form.description.trim() || null,

    is_available:
      Boolean(form.is_available),
  });
};

watch(
  () => props.show,
  (show) => {
    if (show) {
      fillForm();
    } else {
      resetForm();
    }
  }
);

watch(
  () => props.addOn,
  () => {
    if (props.show) {
      fillForm();
    }
  },
  {
    deep: true,
  }
);
</script>

<template>
  <Teleport to="body">
    <div
      v-if="show"
      class="add-on-modal-backdrop"
      @click.self="closeModal"
    >
      <div
        class="add-on-modal-dialog"
        role="dialog"
        aria-modal="true"
        :aria-label="modalTitle"
      >
        <div
          class="add-on-modal-content"
        >
          <header
            class="add-on-modal-header"
          >
            <div>
              <h2>
                {{ modalTitle }}
              </h2>

              <p>
                {{
                  isEditing
                    ? "Update the optional extra."
                    : "Create an optional extra for orders."
                }}
              </p>
            </div>

            <button
              type="button"
              class="btn-close"
              :disabled="submitting"
              aria-label="Close"
              @click="closeModal"
            ></button>
          </header>

          <form
            @submit.prevent="submitForm"
          >
            <div
              class="add-on-modal-body"
            >
              <div
                v-if="errorMessage"
                class="alert alert-danger"
              >
                {{ errorMessage }}
              </div>

              <div class="row g-3">
                <div class="col-md-7">
                  <label
                    class="form-label"
                  >
                    Add-on Name
                    <span
                      class="text-danger"
                    >
                      *
                    </span>
                  </label>

                  <input
                    v-model="
                      form.add_on_name
                    "
                    type="text"
                    class="form-control"
                    :class="{
                      'is-invalid':
                        firstError(
                          'add_on_name'
                        ),
                    }"
                    maxlength="150"
                    placeholder="Example: Extra Cheese"
                    required
                  />

                  <div
                    class="invalid-feedback"
                  >
                    {{
                      firstError(
                        "add_on_name"
                      )
                    }}
                  </div>
                </div>

                <div class="col-md-5">
                  <label
                    class="form-label"
                  >
                    Price
                    <span
                      class="text-danger"
                    >
                      *
                    </span>
                  </label>

                  <div
                    class="input-group"
                  >
                    <span
                      class="input-group-text"
                    >
                      ৳
                    </span>

                    <input
                      v-model="form.price"
                      type="number"
                      class="form-control"
                      :class="{
                        'is-invalid':
                          firstError(
                            'price'
                          ),
                      }"
                      min="0"
                      step="0.01"
                      placeholder="0.00"
                      required
                    />

                    <div
                      class="invalid-feedback"
                    >
                      {{
                        firstError(
                          "price"
                        )
                      }}
                    </div>
                  </div>
                </div>

                <div class="col-12">
                  <label
                    class="form-label"
                  >
                    Description
                  </label>

                  <textarea
                    v-model="
                      form.description
                    "
                    class="form-control"
                    :class="{
                      'is-invalid':
                        firstError(
                          'description'
                        ),
                    }"
                    rows="4"
                    maxlength="2000"
                    placeholder="Optional description..."
                  ></textarea>

                  <div
                    class="invalid-feedback"
                  >
                    {{
                      firstError(
                        "description"
                      )
                    }}
                  </div>

                  <small
                    class="text-muted"
                  >
                    {{
                      form.description.length
                    }}/2000
                  </small>
                </div>

                <div class="col-12">
                  <div
                    class="add-on-availability-box"
                  >
                    <div>
                      <strong>
                        Availability
                      </strong>

                      <small>
                        Available add-ons will
                        appear during order
                        creation.
                      </small>
                    </div>

                    <div
                      class="form-check form-switch"
                    >
                      <input
                        id="add-on-available"
                        v-model="
                          form.is_available
                        "
                        class="form-check-input"
                        type="checkbox"
                      />

                      <label
                        class="form-check-label"
                        for="add-on-available"
                      >
                        {{
                          form.is_available
                            ? "Available"
                            : "Unavailable"
                        }}
                      </label>
                    </div>
                  </div>
                </div>
              </div>
            </div>

            <footer
              class="add-on-modal-footer"
            >
              <button
                type="button"
                class="btn btn-outline-secondary"
                :disabled="submitting"
                @click="closeModal"
              >
                Cancel
              </button>

              <button
                type="submit"
                class="btn btn-primary"
                :disabled="submitting"
              >
                <span
                  v-if="submitting"
                  class="spinner-border spinner-border-sm me-2"
                ></span>

                {{ submitLabel }}
              </button>
            </footer>
          </form>
        </div>
      </div>
    </div>
  </Teleport>
</template>

<style
  scoped
  src="@/assets/css/add-on-modal.css"
></style>