<template>
  <Teleport to="body">
    <div
      v-if="show"
      class="order-modal-backdrop"
      @click.self="handleClose"
    >
      <div
        class="order-modal cancel-order-modal"
        role="dialog"
        aria-modal="true"
      >
        <div class="order-modal-header">
          <div>
            <span class="modal-danger-icon">
              <i class="bi bi-x-octagon"></i>
            </span>

            <div>
              <h3>Cancel Order</h3>

              <p>
                {{
                  order?.order_number ||
                  "Selected order"
                }}
              </p>
            </div>
          </div>

          <button
            type="button"
            class="modal-close-button"
            :disabled="submitting"
            @click="handleClose"
          >
            <i class="bi bi-x-lg"></i>
          </button>
        </div>

        <form @submit.prevent="submit">
          <div class="order-modal-body">
            <div class="cancel-warning">
              <i
                class="bi bi-exclamation-triangle"
              ></i>

              <p>
                Canceling this order will release
                its table if no other active order
                is using that table.
              </p>
            </div>

            <label class="form-label">
              Cancellation Reason
              <span class="text-danger">*</span>
            </label>

            <select
              v-model="selectedReason"
              class="form-select mb-3"
              :disabled="submitting"
            >
              <option value="">
                Select a reason
              </option>

              <option value="Customer request">
                Customer request
              </option>

              <option value="Wrong order">
                Wrong order
              </option>

              <option value="Item unavailable">
                Item unavailable
              </option>

              <option value="Duplicate order">
                Duplicate order
              </option>

              <option value="Payment issue">
                Payment issue
              </option>

              <option value="Other">
                Other
              </option>
            </select>

            <textarea
              v-model="customReason"
              class="form-control"
              rows="4"
              :placeholder="
                selectedReason === 'Other'
                  ? 'Write the cancellation reason...'
                  : 'Additional details (optional)'
              "
              :disabled="submitting"
            ></textarea>

            <p
              v-if="validationError"
              class="modal-validation-error"
            >
              {{ validationError }}
            </p>
          </div>

          <div class="order-modal-footer">
            <button
              type="button"
              class="btn modal-secondary-button"
              :disabled="submitting"
              @click="handleClose"
            >
              Keep Order
            </button>

            <button
              type="submit"
              class="btn modal-danger-button"
              :disabled="submitting"
            >
              <span
                v-if="submitting"
                class="spinner-border spinner-border-sm"
              ></span>

              <i
                v-else
                class="bi bi-x-circle"
              ></i>

              Cancel Order
            </button>
          </div>
        </form>
      </div>
    </div>
  </Teleport>
</template>

<script setup>
import {
  ref,
  watch,
} from "vue";

const props = defineProps({
  show: {
    type: Boolean,
    default: false,
  },

  order: {
    type: Object,
    default: null,
  },

  submitting: {
    type: Boolean,
    default: false,
  },
});

const emit = defineEmits([
  "close",
  "confirm",
]);

const selectedReason = ref("");
const customReason = ref("");
const validationError = ref("");

watch(
  () => props.show,
  (show) => {
    if (show) {
      selectedReason.value = "";
      customReason.value = "";
      validationError.value = "";
      document.body.style.overflow =
        "hidden";
    } else {
      document.body.style.overflow =
        "";
    }
  }
);

const submit = () => {
  validationError.value = "";

  if (!selectedReason.value) {
    validationError.value =
      "Please select a cancellation reason.";

    return;
  }

  const customText =
    customReason.value.trim();

  if (
    selectedReason.value === "Other" &&
    customText.length < 3
  ) {
    validationError.value =
      "Please write the cancellation reason.";

    return;
  }

  const reason =
    selectedReason.value === "Other"
      ? customText
      : customText
        ? `${selectedReason.value}: ${customText}`
        : selectedReason.value;

  emit("confirm", reason);
};

const handleClose = () => {
  if (props.submitting) {
    return;
  }

  emit("close");
};
</script>