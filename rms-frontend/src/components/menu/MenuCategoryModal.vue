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

  category: {
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

const form = reactive({
  category_name: "",
  is_available: true,
});

const isEditing = computed(() => {
  return Boolean(props.category?.id);
});

const modalTitle = computed(() => {
  return isEditing.value
    ? "Edit Menu Category"
    : "Add Menu Category";
});

const submitButtonText = computed(
  () => {
    if (props.submitting) {
      return isEditing.value
        ? "Updating..."
        : "Creating...";
    }

    return isEditing.value
      ? "Update Category"
      : "Create Category";
  }
);

const normalizeBoolean = (
  value
) => {
  return (
    value === true ||
    value === 1 ||
    value === "1" ||
    value === "true" ||
    value === "on" ||
    value === "yes"
  );
};

const firstError = (field) => {
  const errors =
    props.validationErrors?.[field];

  if (Array.isArray(errors)) {
    return errors[0] || "";
  }

  return errors || "";
};

const resetForm = () => {
  form.category_name = "";

  form.is_available = true;
};

const fillForm = () => {
  resetForm();

  if (!props.category) {
    return;
  }

  form.category_name =
    props.category.category_name ?? "";

  form.is_available =
    normalizeBoolean(
      props.category.is_available
    );
};

const closeModal = () => {
  if (props.submitting) {
    return;
  }

  emit("close");
};

const submitForm = () => {
  const categoryName =
    form.category_name.trim();

  if (!categoryName) {
    return;
  }

  emit("submit", {
    category_name: categoryName,

    is_available:
      form.is_available,
  });
};

watch(
  () => props.show,
  (show) => {
    if (show) {
      fillForm();
    }
  }
);

watch(
  () => props.category,
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
      class="menu-category-modal-backdrop"
      @click.self="closeModal"
    >
      <div
        class="menu-category-modal-dialog"
        role="dialog"
        aria-modal="true"
        aria-labelledby="category-modal-title"
      >
        <div
          class="menu-category-modal-content"
        >
          <div
            class="menu-category-modal-header"
          >
            <div>
              <h2
                id="category-modal-title"
              >
                {{ modalTitle }}
              </h2>

              <p>
                {{
                  isEditing
                    ? "Update the selected menu category."
                    : "Create a new menu category."
                }}
              </p>
            </div>

            <button
              type="button"
              class="btn-close"
              aria-label="Close"
              :disabled="submitting"
              @click="closeModal"
            ></button>
          </div>

          <form
            @submit.prevent="submitForm"
          >
            <div
              class="menu-category-modal-body"
            >
              <div
                v-if="errorMessage"
                class="alert alert-danger"
              >
                {{ errorMessage }}
              </div>

              <div class="mb-4">
                <label
                  for="category-name"
                  class="form-label"
                >
                  Category Name

                  <span
                    class="text-danger"
                  >
                    *
                  </span>
                </label>

                <input
                  id="category-name"
                  v-model="
                    form.category_name
                  "
                  type="text"
                  class="form-control"
                  :class="{
                    'is-invalid':
                      firstError(
                        'category_name'
                      ),
                  }"
                  maxlength="150"
                  placeholder="Example: Burgers"
                  autocomplete="off"
                  required
                />

                <div
                  v-if="
                    firstError(
                      'category_name'
                    )
                  "
                  class="invalid-feedback"
                >
                  {{
                    firstError(
                      "category_name"
                    )
                  }}
                </div>
              </div>

              <div
                class="menu-category-status-box"
              >
                <div>
                  <strong>
                    Category Availability
                  </strong>

                  <small>
                    Available categories can
                    be selected when creating
                    menu items.
                  </small>
                </div>

                <div
                  class="form-check form-switch"
                >
                  <input
                    id="category-available"
                    v-model="
                      form.is_available
                    "
                    class="form-check-input"
                    type="checkbox"
                    role="switch"
                  />

                  <label
                    class="form-check-label"
                    for="category-available"
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

            <div
              class="menu-category-modal-footer"
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
                :disabled="
                  submitting ||
                  !form.category_name.trim()
                "
              >
                <span
                  v-if="submitting"
                  class="spinner-border spinner-border-sm me-2"
                ></span>

                {{ submitButtonText }}
              </button>
            </div>
          </form>
        </div>
      </div>
    </div>
  </Teleport>
</template>

<style
  scoped
  src="@/assets/css/menu-category-modal.css"
></style>