<template>
  <div class="order-summary-grid">
    <button
      v-for="card in cards"
      :key="card.key"
      type="button"
      class="order-summary-card"
      :class="{
        active:
          card.status &&
          activeStatus === card.status,
      }"
      :disabled="loading"
      @click="handleClick(card)"
    >
      <div
        class="summary-icon"
        :class="card.iconClass"
      >
        <i :class="card.icon"></i>
      </div>

      <div class="summary-content">
        <span class="summary-label">
          {{ card.label }}
        </span>

        <strong class="summary-value">
          <span
            v-if="loading"
            class="summary-loading"
          ></span>

          <template v-else>
            {{ card.value }}
          </template>
        </strong>
      </div>
    </button>
  </div>
</template>

<script setup>
import { computed } from "vue";

const props = defineProps({
  summary: {
    type: Object,
    required: true,
  },

  loading: {
    type: Boolean,
    default: false,
  },

  activeStatus: {
    type: String,
    default: "",
  },
});

const emit = defineEmits([
  "filter-status",
]);

const cards = computed(() => [
  {
    key: "total",
    label: "Total Orders",
    value: props.summary.total_orders ?? 0,
    icon: "bi bi-receipt",
    iconClass: "summary-icon-dark",
    status: "",
  },
  {
    key: "pending",
    label: "Pending",
    value:
      props.summary.pending_orders ?? 0,
    icon: "bi bi-hourglass-split",
    iconClass: "summary-icon-warning",
    status: "pending",
  },
  {
    key: "kitchen",
    label: "Kitchen Active",
    value:
      props.summary.kitchen_active ?? 0,
    icon: "bi bi-fire",
    iconClass: "summary-icon-info",
    status: "preparing",
  },
  {
    key: "served",
    label: "Served",
    value:
      props.summary.served_orders ?? 0,
    icon: "bi bi-check2-circle",
    iconClass: "summary-icon-primary",
    status: "served",
  },
  {
    key: "completed",
    label: "Completed",
    value:
      props.summary.completed_orders ?? 0,
    icon: "bi bi-patch-check",
    iconClass: "summary-icon-success",
    status: "completed",
  },
  {
    key: "sales",
    label: "Completed Sales",
    value:
      props.summary
        .total_sales_formatted ??
      "৳ 0.00",
    icon: "bi bi-cash-stack",
    iconClass: "summary-icon-success",
    status: "",
  },
  {
    key: "due",
    label: "Outstanding Due",
    value:
      props.summary
        .due_amount_formatted ??
      "৳ 0.00",
    icon: "bi bi-wallet2",
    iconClass: "summary-icon-danger",
    status: "",
  },
]);

const handleClick = (card) => {
  if (!card.status) {
    return;
  }

  emit(
    "filter-status",
    card.status
  );
};
</script>