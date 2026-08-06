<script setup>
import {
  computed,
  reactive,
  watch,
} from "vue";

import {
  normalizeBoolean,
} from "@/composables/useCrudResource";

const props = defineProps({
  show: {
    type: Boolean,
    default: false,
  },

  variant: {
    type: Object,
    default: null,
  },

  menuItems: {
    type: Array,
    default: () => [],
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

const defaultForm = () => ({
  menu_item_id: "",
  variant_name: "",
  price: "",
  is_available: true,
});

const form = reactive(
  defaultForm()
);

const isEditing = computed(
  () => Boolean(props.variant?.id)
);

const title = computed(() =>
  isEditing.value
    ? "Edit Variant"
    : "Add Variant"
);

const submitText = computed(() => {
  if (props.submitting) {
    return isEditing.value
      ? "Updating..."
      : "Creating...";
  }

  return isEditing.value
    ? "Update Variant"
    : "Create Variant";
});

const firstError = (field) => {
  const error =
    props.validationErrors?.[field];

  return Array.isArray(error)
    ? error[0] || ""
    : error || "";
};

const resetForm = () => {
  Object.assign(
    form,
    defaultForm()
  );
};

const fillForm = () => {
  resetForm();

  if (!props.variant) {
    return;
  }

  Object.assign(form, {
    menu_item_id:
      props.variant.menu_item_id ?? "",

    variant_name:
      props.variant.variant_name ?? "",

    price:
      props.variant.price ?? "",

    is_available:
      normalizeBoolean(
        props.variant.is_available
      ),
  });
};

const closeModal = () => {
  if (props.submitting) {
    return;
  }

  resetForm();
  emit("close");
};

const submitForm = () => {
  emit("submit", {
    menu_item_id:
      Number(form.menu_item_id),

    variant_name:
      form.variant_name.trim(),

    price:
      form.price,

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
  () => props.variant,
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
      class="variant-modal-backdrop"
      @click.self="closeModal"
    >
      <div
        class="variant-modal-dialog"
        role="dialog"
        aria-modal="true"
      >
        <div
          class="variant-modal-content"
        >
          <header
            class="variant-modal-header"
          >
            <div>
              <h2>{{ title }}</h2>

              <p>
                {{
                  isEditing
                    ? "Update variant information."
                    : "Add a size or pricing variation."
                }}
              </p>
            </div>

            <button
              type="button"
              class="btn-close"
              :disabled="submitting"
              @click="closeModal"
            ></button>
          </header>

          <form
            @submit.prevent="submitForm"
          >
            <div
              class="variant-modal-body"
            >
              <div
                v-if="errorMessage"
                class="alert alert-danger"
              >
                {{ errorMessage }}
              </div>

              <div class="row g-3">
                <div class="col-12">
                  <label
                    class="form-label"
                  >
                    Menu Item
                    <span
                      class="text-danger"
                    >
                      *
                    </span>
                  </label>

                  <select
                    v-model="
                      form.menu_item_id
                    "
                    class="form-select"
                    :class="{
                      'is-invalid':
                        firstError(
                          'menu_item_id'
                        ),
                    }"
                    required
                  >
                    <option
                      value=""
                      disabled
                    >
                      Select menu item
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

                  <div
                    class="invalid-feedback"
                  >
                    {{
                      firstError(
                        "menu_item_id"
                      )
                    }}
                  </div>
                </div>

                <div class="col-md-7">
                  <label
                    class="form-label"
                  >
                    Variant Name
                    <span
                      class="text-danger"
                    >
                      *
                    </span>
                  </label>

                  <input
                    v-model="
                      form.variant_name
                    "
                    type="text"
                    class="form-control"
                    :class="{
                      'is-invalid':
                        firstError(
                          'variant_name'
                        ),
                    }"
                    maxlength="120"
                    placeholder="Example: Large"
                    required
                  />

                  <div
                    class="invalid-feedback"
                  >
                    {{
                      firstError(
                        "variant_name"
                      )
                    }}
                  </div>
                </div>

                <div class="col-md-5">
                  <label
                    class="form-label"
                  >
                    Variant Price
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
                  <div
                    class="variant-switch-box"
                  >
                    <div>
                      <strong>
                        Availability
                      </strong>

                      <small>
                        Allow customers to
                        select this variant.
                      </small>
                    </div>

                    <div
                      class="form-check form-switch"
                    >
                      <input
                        id="variant-available"
                        v-model="
                          form.is_available
                        "
                        class="form-check-input"
                        type="checkbox"
                      />

                      <label
                        class="form-check-label"
                        for="variant-available"
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
              class="variant-modal-footer"
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

                {{ submitText }}
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
  src="@/assets/css/menu-variant-modal.css"
></style>