<script setup>
defineProps({
  title: {
    type: String,
    required: true,
  },

  count: {
    type: Number,
    default: 0,
  },

  icon: {
    type: String,
    default: "bi bi-grid",
  },

  status: {
    type: String,
    default: "total",
  },

  active: {
    type: Boolean,
    default: false,
  },
});

const emit = defineEmits([
  "select",
]);
</script>

<template>
  <button
    type="button"
    class="table-status-filter"
    :class="[
      `table-status-filter--${status}`,
      {
        'table-status-filter--active':
          active,
      },
    ]"
    :aria-pressed="active"
    @click="
      emit(
        'select',
        status
      )
    "
  >
    <span
      class="table-status-filter__icon"
    >
      <i :class="icon"></i>
    </span>

    <span
      class="table-status-filter__title"
    >
      {{ title }}
    </span>

    <span
      class="table-status-filter__count"
    >
      {{ count }}
    </span>
  </button>
</template>

<style
  scoped
  src="@/assets/css/table-status-card.css"
></style>