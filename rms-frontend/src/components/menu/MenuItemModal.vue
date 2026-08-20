<script setup>
import {
  computed,
  onBeforeUnmount,
  reactive,
  ref,
  watch,
} from "vue";

import {
  normalizeBoolean,
} from "@/composables/useCrudResource";
import {
  resolveMediaUrl,
} from "@/utils/mediaUrl";

const props = defineProps({
  show: {
    type: Boolean,
    default: false,
  },

  menuItem: {
    type: Object,
    default: null,
  },

  categories: {
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

const imageInput = ref(null);
const selectedImage = ref(null);
const imagePreview = ref(null);
const removeExistingImage = ref(false);

const defaultForm = () => ({
  menu_category_id: "",
  menu_name: "",
  item_type: "regular",
  price: "",
  ingredients: "",
  description: "",
  preparation_time: "",
  is_available: true,
  is_featured: false,
});

const form = reactive(defaultForm());

const isEditing = computed(
  () => Boolean(props.menuItem?.id)
);

const modalTitle = computed(() =>
  isEditing.value
    ? "Edit Menu Item"
    : "Add Menu Item"
);

const submitText = computed(() => {
  if (props.submitting) {
    return isEditing.value
      ? "Updating..."
      : "Creating...";
  }

  return isEditing.value
    ? "Update Menu Item"
    : "Create Menu Item";
});

const firstError = (field) => {
  const error =
    props.validationErrors?.[field];

  return Array.isArray(error)
    ? error[0] || ""
    : error || "";
};

const clearObjectUrl = () => {
  if (
    imagePreview.value?.startsWith(
      "blob:"
    )
  ) {
    URL.revokeObjectURL(
      imagePreview.value
    );
  }
};

const resetForm = () => {
  clearObjectUrl();

  Object.assign(
    form,
    defaultForm()
  );

  selectedImage.value = null;
  imagePreview.value = null;
  removeExistingImage.value = false;

  if (imageInput.value) {
    imageInput.value.value = "";
  }
};

const fillForm = () => {
  resetForm();

  const item = props.menuItem;

  if (!item) {
    return;
  }

  Object.assign(form, {
    menu_category_id:
      item.menu_category_id ?? "",

    menu_name:
      item.menu_name ?? "",

    item_type:
      item.item_type ?? "regular",

    price:
      item.price ?? "",

    ingredients:
      item.ingredients ?? "",

    description:
      item.description ?? "",

    preparation_time:
      item.preparation_time ?? "",

    is_available:
      normalizeBoolean(
        item.is_available
      ),

    is_featured:
      normalizeBoolean(
        item.is_featured
      ),
  });

  imagePreview.value =
    resolveMediaUrl(
      item.image_url ||
      item.image_path
    );
};

const handleImageChange = (event) => {
  const file =
    event.target.files?.[0];

  if (!file) {
    return;
  }

  const maxSize =
    10 * 1024 * 1024;

  if (file.size > maxSize) {
    window.alert(
      "Image size cannot be more than 10 MB."
    );

    event.target.value = "";

    selectedImage.value = null;

    return;
  }

  const allowedTypes = [
    "image/jpeg",
    "image/png",
    "image/webp",
  ];

  if (
    !allowedTypes.includes(
      file.type
    )
  ) {
    window.alert(
      "Only JPG, JPEG, PNG or WEBP images are allowed."
    );

    event.target.value = "";

    selectedImage.value = null;

    return;
  }

  clearObjectUrl();

  selectedImage.value =
    file;

  imagePreview.value =
    URL.createObjectURL(
      file
    );

  removeExistingImage.value =
    false;
};

const removeImage = () => {
  clearObjectUrl();

  selectedImage.value = null;
  imagePreview.value = null;
  removeExistingImage.value = true;

  if (imageInput.value) {
    imageInput.value.value = "";
  }
};

const restoreImage = () => {
  clearObjectUrl();

  selectedImage.value = null;
  removeExistingImage.value = false;

  imagePreview.value =
    resolveMediaUrl(
      props.menuItem?.image_url ||
      props.menuItem?.image_path
    );

  if (imageInput.value) {
    imageInput.value.value = "";
  }
};

const closeModal = () => {
  if (props.submitting) {
    return;
  }

  resetForm();
  emit("close");
};

const append = (
  payload,
  key,
  value
) => {
  payload.append(
    key,
    value === null ||
      value === undefined
      ? ""
      : String(value)
  );
};

const buildPayload = () => {
  const payload = new FormData();

  append(
    payload,
    "menu_category_id",
    form.menu_category_id
  );

  append(
    payload,
    "menu_name",
    form.menu_name.trim()
  );

  append(
    payload,
    "item_type",
    form.item_type
  );

  append(
    payload,
    "price",
    form.price
  );

  append(
    payload,
    "ingredients",
    form.ingredients?.trim()
  );

  append(
    payload,
    "description",
    form.description?.trim()
  );

  append(
    payload,
    "preparation_time",
    form.preparation_time
  );

  append(
    payload,
    "is_available",
    form.is_available ? 1 : 0
  );

  append(
    payload,
    "is_featured",
    form.is_featured ? 1 : 0
  );

  append(
    payload,
    "remove_image",
    removeExistingImage.value
      ? 1
      : 0
  );

  if (selectedImage.value) {
    payload.append(
      "image",
      selectedImage.value
    );
  }

  return payload;
};

const submitForm = () => {
  emit(
    "submit",
    buildPayload()
  );
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
  () => props.menuItem,
  () => {
    if (props.show) {
      fillForm();
    }
  },
  {
    deep: true,
  }
);

onBeforeUnmount(clearObjectUrl);
</script>

<template>
  <Teleport to="body">
    <div
      v-if="show"
      class="menu-item-modal-backdrop"
      @click.self="closeModal"
    >
      <div
        class="menu-item-modal-dialog"
        role="dialog"
        aria-modal="true"
        aria-labelledby="menu-item-modal-title"
      >
        <div
          class="menu-item-modal-content"
        >
          <header
            class="menu-item-modal-header"
          >
            <div>
              <h2
                id="menu-item-modal-title"
              >
                {{ modalTitle }}
              </h2>

              <p>
                {{
                  isEditing
                    ? "Update the selected menu item."
                    : "Add a new food or beverage item."
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
          </header>

          <form
            @submit.prevent="submitForm"
          >
            <div
              class="menu-item-modal-body"
            >
              <div
                v-if="errorMessage"
                class="alert alert-danger"
              >
                {{ errorMessage }}
              </div>

              <div class="row g-3">
                <div class="col-md-6">
                  <label
                    class="form-label"
                  >
                    Menu Category
                    <span
                      class="text-danger"
                    >
                      *
                    </span>
                  </label>

                  <select
                    v-model="
                      form.menu_category_id
                    "
                    class="form-select"
                    :class="{
                      'is-invalid':
                        firstError(
                          'menu_category_id'
                        ),
                    }"
                    required
                  >
                    <option
                      value=""
                      disabled
                    >
                      Select category
                    </option>

                    <option
                      v-for="
                        category in categories
                      "
                      :key="category.id"
                      :value="category.id"
                    >
                      {{
                        category.category_name ||
                        category.name
                      }}
                    </option>
                  </select>

                  <div
                    class="invalid-feedback"
                  >
                    {{
                      firstError(
                        "menu_category_id"
                      )
                    }}
                  </div>
                </div>

                <div class="col-md-6">
                  <label
                    class="form-label"
                  >
                    Menu Item Name
                    <span
                      class="text-danger"
                    >
                      *
                    </span>
                  </label>

                  <input
                    v-model="
                      form.menu_name
                    "
                    type="text"
                    class="form-control"
                    :class="{
                      'is-invalid':
                        firstError(
                          'menu_name'
                        ),
                    }"
                    maxlength="180"
                    placeholder="Example: Chicken Burger"
                    required
                  />

                  <div
                    class="invalid-feedback"
                  >
                    {{
                      firstError(
                        "menu_name"
                      )
                    }}
                  </div>
                </div>

                <div class="col-md-4">
                  <label
                    class="form-label"
                  >
                    Item Type
                  </label>

                  <select
                    v-model="
                      form.item_type
                    "
                    class="form-select"
                    :class="{
                      'is-invalid':
                        firstError(
                          'item_type'
                        ),
                    }"
                    required
                  >
                    <option value="regular">
                      Regular
                    </option>

                    <option value="combo">
                      Combo
                    </option>

                    <option value="set_meal">
                      Set Meal
                    </option>
                  </select>

                  <div
                    class="invalid-feedback"
                  >
                    {{
                      firstError(
                        "item_type"
                      )
                    }}
                  </div>
                </div>

                <div class="col-md-4">
                  <label
                    class="form-label"
                  >
                    Price
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

                <div class="col-md-4">
                  <label
                    class="form-label"
                  >
                    Preparation Time
                  </label>

                  <div
                    class="input-group"
                  >
                    <input
                      v-model="
                        form.preparation_time
                      "
                      type="number"
                      class="form-control"
                      :class="{
                        'is-invalid':
                          firstError(
                            'preparation_time'
                          ),
                      }"
                      min="0"
                      max="1440"
                      placeholder="20"
                    />

                    <span
                      class="input-group-text"
                    >
                      min
                    </span>

                    <div
                      class="invalid-feedback"
                    >
                      {{
                        firstError(
                          "preparation_time"
                        )
                      }}
                    </div>
                  </div>
                </div>

                <div class="col-12">
                  <label
                    class="form-label"
                  >
                    Item Image
                  </label>

                  <input
                    ref="imageInput"
                    type="file"
                    class="form-control"
                    :class="{
                      'is-invalid':
                        firstError(
                          'image'
                        ),
                    }"
                    accept=".jpg,.jpeg,.png,.webp"
                    @change="
                      handleImageChange
                    "
                  />

                  <div
                    class="invalid-feedback"
                  >
                    {{
                      firstError(
                        "image"
                      )
                    }}
                  </div>

                  <small
  class="text-muted"
>
  JPG, JPEG, PNG or WEBP.
  Maximum 10 MB.
</small>
                </div>

                <div
                  v-if="imagePreview"
                  class="col-12"
                >
                  <div
                    class="menu-item-image-preview"
                  >
                    <img
                      :src="imagePreview"
                      alt="Menu item preview"
                    />

                    <button
                      type="button"
                      class="btn btn-sm btn-outline-danger"
                      @click="removeImage"
                    >
                      <i
                        class="bi bi-trash3 me-1"
                      ></i>

                      Remove Image
                    </button>
                  </div>
                </div>

                <div
                  v-else-if="
                    isEditing &&
                    removeExistingImage
                  "
                  class="col-12"
                >
                  <button
                    type="button"
                    class="btn btn-sm btn-outline-secondary"
                    @click="restoreImage"
                  >
                    Restore Previous Image
                  </button>
                </div>

                <div class="col-md-6">
                  <label
                    class="form-label"
                  >
                    Ingredients
                  </label>

                  <textarea
                    v-model="
                      form.ingredients
                    "
                    class="form-control"
                    :class="{
                      'is-invalid':
                        firstError(
                          'ingredients'
                        ),
                    }"
                    rows="4"
                    maxlength="5000"
                    placeholder="Write the main ingredients..."
                  ></textarea>

                  <div
                    class="invalid-feedback"
                  >
                    {{
                      firstError(
                        "ingredients"
                      )
                    }}
                  </div>
                </div>

                <div class="col-md-6">
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
                    maxlength="5000"
                    placeholder="Write a short description..."
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
                </div>

                <div class="col-md-6">
                  <div
                    class="menu-item-switch-box"
                  >
                    <div>
                      <strong>
                        Availability
                      </strong>

                      <small>
                        Allow customers to
                        order this item.
                      </small>
                    </div>

                    <div
                      class="form-check form-switch"
                    >
                      <input
                        id="menu-item-available"
                        v-model="
                          form.is_available
                        "
                        class="form-check-input"
                        type="checkbox"
                      />

                      <label
                        class="form-check-label"
                        for="menu-item-available"
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

                <div class="col-md-6">
                  <div
                    class="menu-item-switch-box"
                  >
                    <div>
                      <strong>
                        Featured Item
                      </strong>

                      <small>
                        Highlight this item
                        in featured lists.
                      </small>
                    </div>

                    <div
                      class="form-check form-switch"
                    >
                      <input
                        id="menu-item-featured"
                        v-model="
                          form.is_featured
                        "
                        class="form-check-input"
                        type="checkbox"
                      />

                      <label
                        class="form-check-label"
                        for="menu-item-featured"
                      >
                        {{
                          form.is_featured
                            ? "Featured"
                            : "Normal"
                        }}
                      </label>
                    </div>
                  </div>
                </div>
              </div>
            </div>

            <footer
              class="menu-item-modal-footer"
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
  src="@/assets/css/menu-item-modal.css"
></style>