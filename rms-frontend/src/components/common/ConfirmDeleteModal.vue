<script setup>
import { computed } from "vue";

const props = defineProps({
  show: {
    type: Boolean,
    default: false,
  },

  title: {
    type: String,
    default: "Delete Record",
  },

  itemName: {
    type: String,
    default: "",
  },

  message: {
    type: String,
    default:
      "This action cannot be undone.",
  },

  loading: {
    type: Boolean,
    default: false,
  },
});

const emit = defineEmits([
  "close",
  "confirm",
]);

const displayMessage = computed(() => {
  if (props.itemName) {
    return `Are you sure you want to delete "${props.itemName}"?`;
  }

  return props.message;
});

const closeModal = () => {
  if (props.loading) {
    return;
  }

  emit("close");
};

const confirmDelete = () => {
  if (props.loading) {
    return;
  }

  emit("confirm");
};
</script>

<template>
  <Teleport to="body">
    <div
      v-if="show"
      class="delete-backdrop"
      @click.self="closeModal"
    >
      <div
        class="delete-dialog"
        role="alertdialog"
        aria-modal="true"
        :aria-labelledby="
          'confirm-delete-title'
        "
      >
        <div class="delete-content">
          <div class="delete-icon">
            <i
              class="bi bi-trash3"
            ></i>
          </div>

          <h2 id="confirm-delete-title">
            {{ title }}
          </h2>

          <p class="delete-message">
            {{ displayMessage }}
          </p>

          <p
            v-if="itemName"
            class="delete-warning"
          >
            {{ message }}
          </p>

          <div class="delete-actions">
            <button
              type="button"
              class="btn btn-outline-secondary"
              :disabled="loading"
              @click="closeModal"
            >
              Cancel
            </button>

            <button
              type="button"
              class="btn btn-danger"
              :disabled="loading"
              @click="confirmDelete"
            >
              <span
                v-if="loading"
                class="spinner-border spinner-border-sm me-2"
              ></span>

              <i
                v-else
                class="bi bi-trash3 me-2"
              ></i>

              {{
                loading
                  ? "Deleting..."
                  : "Delete"
              }}
            </button>
          </div>
        </div>
      </div>
    </div>
  </Teleport>
</template>

<style
  scoped
  src="@/assets/css/confirm-delete-modal.css"
></style>