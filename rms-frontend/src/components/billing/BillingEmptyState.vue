<template>
  <div
    class="billing-empty-state"
    :role="isError ? 'alert' : 'status'"
    :aria-live="isError ? 'assertive' : 'polite'"
  >
    <!-- ==================================================
         Empty State Icon
    =================================================== -->

    <span
      class="billing-empty-state-icon"
      :class="{
        'billing-empty-state-error-icon':
          isError,
      }"
      aria-hidden="true"
    >
      <i :class="icon"></i>
    </span>

    <!-- ==================================================
         Empty State Content
    =================================================== -->

    <div class="billing-empty-state-content">
      <strong>
        {{ title }}
      </strong>

      <p v-if="description">
        {{ description }}
      </p>
    </div>

    <!-- ==================================================
         Optional Action Button
    =================================================== -->

    <button
      v-if="buttonText"
      type="button"
      class="billing-empty-state-button"
      :disabled="loading"
      @click="handleAction"
    >
      <span
        v-if="loading"
        class="spinner-border spinner-border-sm"
        aria-hidden="true"
      ></span>

      <i
        v-else
        :class="buttonIcon"
        aria-hidden="true"
      ></i>

      <span>
        {{
          loading
            ? loadingText
            : buttonText
        }}
      </span>
    </button>
  </div>
</template>

<script setup>
/*
|--------------------------------------------------------------------------
| Component Props
|--------------------------------------------------------------------------
*/

const props = defineProps({
  /*
  |--------------------------------------------------------------------------
  | Empty State Icon
  |--------------------------------------------------------------------------
  */

  icon: {
    type: String,
    default:
      "bi bi-inbox",
  },

  /*
  |--------------------------------------------------------------------------
  | Empty State Title
  |--------------------------------------------------------------------------
  */

  title: {
    type: String,
    default:
      "No records found",
  },

  /*
  |--------------------------------------------------------------------------
  | Empty State Description
  |--------------------------------------------------------------------------
  */

  description: {
    type: String,
    default:
      "No billing information is available for the selected filters.",
  },

  /*
  |--------------------------------------------------------------------------
  | Optional Action Button Text
  |--------------------------------------------------------------------------
  |
  | Empty string হলে button render হবে না।
  |
  */

  buttonText: {
    type: String,
    default: "",
  },

  /*
  |--------------------------------------------------------------------------
  | Action Button Icon
  |--------------------------------------------------------------------------
  */

  buttonIcon: {
    type: String,
    default:
      "bi bi-arrow-clockwise",
  },

  /*
  |--------------------------------------------------------------------------
  | Loading State
  |--------------------------------------------------------------------------
  */

  loading: {
    type: Boolean,
    default: false,
  },

  /*
  |--------------------------------------------------------------------------
  | Loading Button Text
  |--------------------------------------------------------------------------
  */

  loadingText: {
    type: String,
    default:
      "Retrying...",
  },

  /*
  |--------------------------------------------------------------------------
  | Error State
  |--------------------------------------------------------------------------
  |
  | true হলে alert role এবং error icon style ব্যবহার হবে।
  |
  */

  isError: {
    type: Boolean,
    default: false,
  },
});

/*
|--------------------------------------------------------------------------
| Component Events
|--------------------------------------------------------------------------
*/

const emit = defineEmits([
  "retry",
  "action",
]);

/*
|--------------------------------------------------------------------------
| Handle Action Button
|--------------------------------------------------------------------------
*/

function handleAction() {
  if (props.loading) {
    return;
  }

  /*
  |--------------------------------------------------------------------------
  | Backward Compatibility
  |--------------------------------------------------------------------------
  |
  | Existing table components @retry ব্যবহার করছে।
  | Future component চাইলে @action-ও ব্যবহার করতে পারবে।
  |
  */

  emit("retry");

  emit("action");
}
</script>