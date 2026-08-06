<template>
  <div
    class="billing-table-loading"
    role="status"
    aria-live="polite"
    :aria-busy="true"
  >
    <!-- ==================================================
         Loading Spinner
    =================================================== -->

    <span
      class="spinner-border"
      :class="spinnerSizeClass"
      aria-hidden="true"
    ></span>

    <!-- ==================================================
         Loading Text
    =================================================== -->

    <p
      v-if="text"
      class="billing-loading-text"
    >
      {{ text }}
    </p>

    <!-- ==================================================
         Optional Description
    =================================================== -->

    <small
      v-if="description"
      class="billing-loading-description"
    >
      {{ description }}
    </small>
  </div>
</template>

<script setup>
import { computed } from "vue";

/*
|--------------------------------------------------------------------------
| Component Props
|--------------------------------------------------------------------------
*/

const props = defineProps({
  /*
  |--------------------------------------------------------------------------
  | Loading Message
  |--------------------------------------------------------------------------
  */

  text: {
    type: String,
    default:
      "Loading billing information...",
  },

  /*
  |--------------------------------------------------------------------------
  | Optional Description
  |--------------------------------------------------------------------------
  */

  description: {
    type: String,
    default: "",
  },

  /*
  |--------------------------------------------------------------------------
  | Spinner Size
  |--------------------------------------------------------------------------
  |
  | sm
  | md
  | lg
  |
  */

  size: {
    type: String,
    default: "md",
  },
});

/*
|--------------------------------------------------------------------------
| Spinner Size Class
|--------------------------------------------------------------------------
*/

const spinnerSizeClass = computed(() => {
  switch (props.size) {
    case "sm":
      return "spinner-border-sm";

    case "lg":
      return "billing-spinner-lg";

    default:
      return "";
  }
});
</script>