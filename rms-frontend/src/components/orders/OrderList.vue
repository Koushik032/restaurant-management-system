<template>
  <div class="order-list-card">
    <div class="order-list-header">
      <div>
        <h2>Order List</h2>

        <p>
          Showing
          {{ meta.from || 0 }}
          to
          {{ meta.to || 0 }}
          of
          {{ meta.total || 0 }}
          orders
        </p>
      </div>

      <div class="per-page-control">
        <label for="order-per-page">
          Rows
        </label>

        <select
          id="order-per-page"
          class="form-select form-select-sm"
          :value="meta.per_page || 10"
          @change="
            emit(
              'change-per-page',
              Number($event.target.value)
            )
          "
        >
          <option :value="10">10</option>
          <option :value="25">25</option>
          <option :value="50">50</option>
          <option :value="100">100</option>
        </select>
      </div>
    </div>

    <!-- Loading -->
    <div
      v-if="loading"
      class="order-loading-state"
    >
      <div class="spinner-border" role="status">
        <span class="visually-hidden">
          Loading orders...
        </span>
      </div>

      <p>Loading orders...</p>
    </div>


    <!-- Desktop Table -->
    <div class="order-table-wrapper">
        <table class="table order-table">
            <thead>
            <tr>
                <th class="sl-column">SL</th>
                <th>Order ID & Time</th>
                <th>Date</th>
                <th>Table</th>
                <th>Merged With</th>
                <th>Status</th>
                <th>Total</th>
                <th>Payment</th>
                <th class="action-column">
                Actions
                </th>
            </tr>
            </thead>

            <tbody>
            <tr
                v-for="(order, index) in orders"
                :key="order.id"
            >
                <td>
                <span class="order-serial">
                    {{
                    getSerialNumber(index)
                    }}
                </span>
                </td>

                <td>
                <button
                    type="button"
                    class="order-number-button"
                    @click="emit('view', order)"
                >
                    {{ order.order_number }}
                </button>

                <div class="order-time">
                    <i class="bi bi-clock"></i>
                    {{ order.time || "—" }}
                </div>

                <div
                    v-if="order.customer?.name"
                    class="order-customer"
                >
                    {{ order.customer.name }}
                </div>
                </td>

                <td>
                <div class="order-date">
                    {{ order.date || "—" }}
                </div>

                <small class="order-day">
                    {{ order.day || "" }}
                </small>
                </td>

                <td>
                <div
                    v-if="order.primary_table"
                    class="table-reference"
                >
                    <i class="bi bi-grid-3x3-gap"></i>

                    <span>
                    {{
                        order.primary_table
                        .table_name
                    }}
                    </span>
                </div>

                <span v-else class="text-muted">
                    No Table
                </span>
                </td>

                <td>
                <div
                    v-if="
                    order.merged_tables?.length
                    "
                    class="merged-table-list"
                >
                    <span
                    v-for="table in order.merged_tables"
                    :key="table.id"
                    class="merged-table-badge"
                    >
                    {{ table.table_name }}
                    </span>
                </div>

                <span v-else class="text-muted">
                    —
                </span>
                </td>

                <td>
                <div class="status-control">
                    <select
                    :value="order.status"
                    class="form-select order-status-select"
                    :class="
                        getStatusClass(
                        order.status
                        )
                    "
                    :disabled="
                        isUpdating(order) ||
                        isFinalized(order)
                    "
                    @change="
                        changeStatus(
                        order,
                        $event.target.value
                        )
                    "
                    >
                    <option
                        v-for="status in getAvailableStatuses(order)"
                        :key="status.value"
                        :value="status.value"
                    >
                        {{ status.label }}
                    </option>
                    </select>

                    <div
                    v-if="isUpdating(order)"
                    class="status-updating"
                    >
                    <span
                        class="spinner-border spinner-border-sm"
                    ></span>
                    </div>
                </div>
                </td>

                <td>
                <strong class="order-total">
                    {{
                    order.total_amount_formatted ||
                    formatMoney(
                        order.total_amount
                    )
                    }}
                </strong>

                <small
                    v-if="
                    Number(order.due_amount) > 0
                    "
                    class="order-due"
                >
                    Due:
                    {{
                    order.due_amount_formatted ||
                    formatMoney(
                        order.due_amount
                    )
                    }}
                </small>
                </td>

                <td>
                <span
                    class="payment-status-badge"
                    :class="
                    getPaymentClass(
                        order.payment_status
                    )
                    "
                >
                    {{
                    order.payment_status_label ||
                    formatPaymentStatus(
                        order.payment_status
                    )
                    }}
                </span>

                <small class="payment-method">
                    {{
                    order.payment_method_label ||
                    formatPaymentMethod(
                        order.payment_method
                    )
                    }}
                </small>
                </td>

                <td>
                <div class="order-action-group">
                    <button
    type="button"
    class="order-action-button view-action"
    title="Download Invoice"
    :disabled="
        downloadingInvoiceId ===
        Number(order.id)
    "
    @click="
        downloadInvoice(
            order
        )
    "
>

    <span
        v-if="
            downloadingInvoiceId ===
            Number(order.id)
        "
        class="spinner-border spinner-border-sm"
        role="status"
        aria-hidden="true"
    ></span>


    <i
        v-else
        class="bi bi-file-earmark-pdf"
    ></i>

</button>

                    <button
                    v-if="order.can_edit"
                    type="button"
                    class="order-action-button edit-action"
                    title="Edit order"
                    :disabled="isUpdating(order)"
                    @click="goToEdit(order)"
                    >
                    <i class="bi bi-pencil-square"></i>
                    </button>

                    <button
                      v-if="
                      order.can_send_to_kitchen ||
                      ['preparing','ready','served','completed','canceled']
                      .includes(order.status)
                      "
                    type="button"
                    class="order-action-button chef-action"
                    :title="
                        order.status === 'pending'
                        ? 'Send to kitchen'
                        : 'View kitchen order'
                    "
                    :disabled="isUpdating(order)"
                    @click="emit('chef', order)"
                    >
                    <i class="bi bi-fire"></i>
                    </button>

                    <button
                    v-if="order.can_cancel"
                    type="button"
                    class="order-action-button cancel-action"
                    title="Cancel order"
                    :disabled="isUpdating(order)"
                    @click="emit('cancel', order)"
                    >
                    <i class="bi bi-x-circle"></i>
                    </button>

                    <button
                    v-if="order.status === 'served'"
                    type="button"
                    class="order-complete-button"
                    :title="
                      Number(order.due_amount) > 0
                        ? 'Settle outstanding payment and complete'
                        : 'Complete order'
                    "
                    :disabled="isUpdating(order)"
                    @click="emit('complete', order)"
                    >
                    <span
                        v-if="isUpdating(order)"
                        class="spinner-border spinner-border-sm"
                    ></span>

                    <i
                        v-else
                        class="bi bi-check2-circle"
                    ></i>

                    {{
                      Number(order.due_amount) > 0
                        ? "Pay & Complete"
                        : "Complete"
                    }}
                    </button>
                </div>
                </td>
            </tr>

            <tr v-if="!orders || orders.length === 0">
                <td
                colspan="9"
                class="text-center py-5 text-muted"
                >
                <i class="bi bi-receipt fs-2 d-block mb-2"></i>

                No orders found.
                </td>
            </tr>
            </tbody>
        </table>
        </div>

    <!-- Pagination -->
    <div
      v-if="
        !loading &&
        orders.length &&
        meta.last_page > 1
      "
      class="order-pagination"
    >
      <button
        type="button"
        class="pagination-button"
        :disabled="meta.current_page <= 1"
        @click="
          emit(
            'change-page',
            meta.current_page - 1
          )
        "
      >
        <i class="bi bi-chevron-left"></i>
        Previous
      </button>

      <div class="pagination-pages">
        <button
          v-for="page in visiblePages"
          :key="page"
          type="button"
          class="pagination-page"
          :class="{
            active:
              page === meta.current_page,
          }"
          @click="
            emit(
              'change-page',
              page
            )
          "
        >
          {{ page }}
        </button>
      </div>

      <button
        type="button"
        class="pagination-button"
        :disabled="
          meta.current_page >=
          meta.last_page
        "
        @click="
          emit(
            'change-page',
            meta.current_page + 1
          )
        "
      >
        Next
        <i class="bi bi-chevron-right"></i>
      </button>
    </div>
  </div>
</template>

<script setup>

import {
  computed,
  ref,
} from "vue";

import {
  useRouter,
} from "vue-router";

import orderService
  from "@/services/orderService";

const router = useRouter();

const downloadingInvoiceId =
  ref(null);

const goToEdit = (order) => {

    router.push({

        name:"order-edit",

        params:{
            id:order.id
        }

    });

};

/*
|--------------------------------------------------------------------------
| Download Invoice
|--------------------------------------------------------------------------
*/

const downloadInvoice = async (
  order
) => {

  if (
    downloadingInvoiceId.value !==
    null
  ) {
    return;
  }


  const orderId =
    Number(
      order?.id
    );


  if (
    !Number.isInteger(orderId) ||
    orderId <= 0
  ) {

    window.alert(
      "A valid order ID is required."
    );

    return;

  }


  downloadingInvoiceId.value =
    orderId;


  try {

    await orderService
      .downloadInvoice(
        orderId
      );

  }
  catch (error) {

    console.error(
      "Invoice download failed:",
      error
    );


    const message =
      orderService
        .getOrderErrorMessage
        ? orderService
            .getOrderErrorMessage(
              error,
              "Unable to download the invoice."
            )
        : (
            error
              ?.response
              ?.data
              ?.message
            ||
            error?.message
            ||
            "Unable to download the invoice."
          );


    window.alert(
      message
    );

  }
  finally {

    downloadingInvoiceId.value =
      null;

  }

};


const props = defineProps({
  orders: {
    type: Array,
    default: () => [],
  },

  loading: {
    type: Boolean,
    default: false,
  },

  updatingOrderId: {
    type: [
      Number,
      String,
    ],
    default: null,
  },

  meta: {
    type: Object,
    required: true,
  },
});

const emit = defineEmits([
  "change-status",
  "edit",
  "chef",
  "cancel",
  "complete",
  "view",
  "change-page",
  "change-per-page",
  "retry",
]);

const statusFlow = [
  {
    value: "pending",
    label: "Pending",
  },
  {
    value: "preparing",
    label: "Preparing",
  },
  {
    value: "ready",
    label: "Ready",
  },
  {
    value: "served",
    label: "Served",
  },
  {
    value: "completed",
    label: "Completed",
  },
  {
    value: "canceled",
    label: "Canceled",
  },
];

const visiblePages = computed(() => {
  const current =
    Number(props.meta.current_page) || 1;

  const last =
    Number(props.meta.last_page) || 1;

  const start = Math.max(
    1,
    current - 2
  );

  const end = Math.min(
    last,
    current + 2
  );

  const pages = [];

  for (
    let page = start;
    page <= end;
    page += 1
  ) {
    pages.push(page);
  }

  return pages;
});

const getSerialNumber = (index) => {
  const currentPage =
    Number(props.meta.current_page) || 1;

  const perPage =
    Number(props.meta.per_page) || 10;

  return (
    (currentPage - 1) *
      perPage +
    index +
    1
  )
    .toString()
    .padStart(2, "0");
};

const isUpdating = (order) => {
  return (
    String(props.updatingOrderId) ===
    String(order.id)
  );
};

const isFinalized = (order) => {
  return [
    "completed",
    "canceled",
  ].includes(order.status);
};

const getAvailableStatuses = (order) => {
  const currentStatus =
    String(order?.status || "");

  switch (currentStatus) {
    case "pending":
      return statusFlow.filter(
        (status) =>
          [
            "pending",
            "canceled",
          ].includes(status.value)
      );

    case "preparing":
      return statusFlow.filter(
        (status) =>
          [
            "preparing",
            "canceled",
          ].includes(status.value)
      );

    case "ready":
      return statusFlow.filter(
        (status) =>
          [
            "ready",
            "served",
            "canceled",
          ].includes(status.value)
      );

    case "served":
      return statusFlow.filter(
        (status) =>
          [
            "served",
            "completed",
            "canceled",
          ].includes(status.value)
      );

    case "completed":
      return statusFlow.filter(
        (status) =>
          status.value ===
            "completed"
      );

    case "canceled":
      return statusFlow.filter(
        (status) =>
          status.value ===
            "canceled"
      );

    default:
      return statusFlow.filter(
        (status) =>
          status.value ===
            currentStatus
      );
  }
};

const changeStatus = (
  order,
  status
) => {
  if (
    !status ||
    status === order.status ||
    isUpdating(order)
  ) {
    return;
  }

  /*
  |--------------------------------------------------------------------------
  | Completed uses the dedicated complete API
  |--------------------------------------------------------------------------
  */

  if (status === "completed") {
    emit("complete", order);
    return;
  }

  /*
  |--------------------------------------------------------------------------
  | Canceled uses the dedicated cancel API
  |--------------------------------------------------------------------------
  */

  if (status === "canceled") {
    emit("cancel", order);
    return;
  }

  /*
  |--------------------------------------------------------------------------
  | Normal status update
  |--------------------------------------------------------------------------
  */

  emit("change-status", {
    order,
    status,
  });
};

const getStatusClass = (status) => {
  return {
    pending: "status-pending",
    preparing: "status-preparing",
    ready: "status-ready",
    served: "status-served",
    completed: "status-completed",
    canceled: "status-canceled",
  }[status] ?? "";
};

const getPaymentClass = (status) => {
  return {
    due: "payment-due",
    partially_paid:
      "payment-partial",
    paid: "payment-paid",
  }[status] ?? "";
};

const formatPaymentStatus = (
  status
) => {
  if (!status) {
    return "Due";
  }

  return status
    .replaceAll("_", " ")
    .replace(/\b\w/g, (character) =>
      character.toUpperCase()
    );
};



const formatPaymentMethod = (
  method
) => {
  if (!method) {
    return "No payment";
  }

  const labels = {
    cash: "Cash",
    card: "Card",
    bkash: "bKash",
    nagad: "Nagad",
    bank_transfer:
      "Bank Transfer",
    mixed: "Mixed Payment",
  };

  return labels[method] ?? method;
};

const formatMoney = (amount) => {
  return `৳ ${Number(
    amount || 0
  ).toLocaleString("en-BD", {
    minimumFractionDigits: 2,
    maximumFractionDigits: 2,
  })}`;
};
</script>