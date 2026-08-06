<template>
  <section class="kitchen-details-page">
    <!-- ==================================================
         Page Header
    =================================================== -->

    <header class="kitchen-details-header">
      <div class="kitchen-details-heading">
        <button
          type="button"
          class="kitchen-details-back-button"
          aria-label="Back to kitchen display"
          @click="goBack"
        >
          <i class="bi bi-arrow-left"></i>
        </button>

        <div class="kitchen-details-title">
          <p class="kitchen-details-eyebrow">
            Kitchen Order
          </p>

          <h1>
            {{
              order?.order_number ||
              "Kitchen Order Details"
            }}
          </h1>

          <p>
            Review ingredients, kitchen notes and
            preparation progress.
          </p>
        </div>
      </div>

      <!-- ==================================================
           Header Actions
      =================================================== -->

      <div class="kitchen-details-actions">
        <!-- Download Invoice -->

        <button
          type="button"
          class="kitchen-details-action-button invoice-button"
          :disabled="
            downloadingInvoice ||
            !order?.id
          "
          @click="downloadInvoice"
        >
          <span
            v-if="downloadingInvoice"
            class="spinner-border spinner-border-sm"
            role="status"
            aria-hidden="true"
          ></span>

          <i
            v-else
            class="bi bi-file-earmark-pdf"
          ></i>

          <span>
            {{
              downloadingInvoice
                ? "Downloading..."
                : "Download Invoice"
            }}
          </span>
        </button>

        <!-- Print -->

        <button
          type="button"
          class="kitchen-details-action-button print-button"
          :disabled="
            loading ||
            !order
          "
          @click="printOrder"
        >
          <i class="bi bi-printer"></i>

          <span>Print</span>
        </button>
      </div>
    </header>

    <!-- ==================================================
         Success Message
    =================================================== -->

    <div
      v-if="successMessage"
      class="kitchen-details-alert success-alert"
      role="alert"
    >
      <span class="kitchen-details-alert-icon">
        <i class="bi bi-check-circle-fill"></i>
      </span>

      <div class="kitchen-details-alert-content">
        <strong>
          Success
        </strong>

        <p>
          {{ successMessage }}
        </p>
      </div>

      <button
        type="button"
        class="kitchen-details-alert-close"
        aria-label="Close success message"
        @click="successMessage = ''"
      >
        <i class="bi bi-x-lg"></i>
      </button>
    </div>

    <!-- ==================================================
         Loading State
    =================================================== -->

    <KitchenLoading
      v-if="loading && !order"
    />

    <!-- ==================================================
         Initial Error State
    =================================================== -->

    <section
      v-else-if="errorMessage && !order"
      class="kitchen-details-error"
      role="alert"
    >
      <span class="kitchen-details-error-icon">
        <i class="bi bi-exclamation-triangle"></i>
      </span>

      <h2>
        Unable to load kitchen order
      </h2>

      <p>
        {{ errorMessage }}
      </p>

      <div class="kitchen-details-error-actions">
        <button
          type="button"
          class="kitchen-details-secondary-button"
          @click="goBack"
        >
          <i class="bi bi-arrow-left"></i>

          Back to Kitchen
        </button>

        <button
          type="button"
          class="kitchen-details-primary-button"
          @click="loadOrder"
        >
          <i class="bi bi-arrow-clockwise"></i>

          Try Again
        </button>
      </div>
    </section>

    <!-- ==================================================
         Kitchen Order Details
    =================================================== -->

    <section
      v-else-if="order"
      class="kitchen-details-content"
    >
      <!-- ==================================================
           Background Error
      =================================================== -->

      <div
        v-if="errorMessage"
        class="kitchen-details-alert error-alert"
        role="alert"
      >
        <span class="kitchen-details-alert-icon">
          <i class="bi bi-exclamation-circle-fill"></i>
        </span>

        <div class="kitchen-details-alert-content">
          <strong>
            Something went wrong
          </strong>

          <p>
            {{ errorMessage }}
          </p>
        </div>

        <button
          type="button"
          class="kitchen-details-alert-close"
          aria-label="Close error message"
          @click="errorMessage = ''"
        >
          <i class="bi bi-x-lg"></i>
        </button>
      </div>

      <!-- ==================================================
           Existing Detailed Kitchen Card
      =================================================== -->

      <KitchenOrderCard
        :order="order"
        @accepted="handleOrderUpdated"
        @started="handleOrderUpdated"
        @ready="handleOrderUpdated"
      />
    </section>
  </section>
</template>

<script setup>
import {
  onMounted,
  ref,
  watch,
} from "vue";

import {
  useRoute,
  useRouter,
} from "vue-router";

import kitchenOrderService from "@/services/kitchenOrderService";
import orderService from "@/services/orderService";

import KitchenOrderCard from "@/components/kitchen/KitchenOrderCard.vue";
import KitchenLoading from "@/components/kitchen/KitchenLoading.vue";

/*
|--------------------------------------------------------------------------
| Router
|--------------------------------------------------------------------------
*/

const route = useRoute();

const router = useRouter();

/*
|--------------------------------------------------------------------------
| Reactive State
|--------------------------------------------------------------------------
*/

const loading = ref(false);

const downloadingInvoice = ref(false);

const errorMessage = ref("");

const successMessage = ref("");

const order = ref(null);

let successTimer = null;

/*
|--------------------------------------------------------------------------
| Resolve Route Order ID
|--------------------------------------------------------------------------
*/

function resolveOrderId() {
  const orderId = Number(
    route.params.id
  );

  if (
    !Number.isInteger(orderId) ||
    orderId <= 0
  ) {
    return null;
  }

  return orderId;
}

/*
|--------------------------------------------------------------------------
| Load Kitchen Order
|--------------------------------------------------------------------------
*/

async function loadOrder() {
  if (loading.value) {
    return;
  }

  const orderId =
    resolveOrderId();

  if (!orderId) {
    order.value = null;

    errorMessage.value =
      "A valid kitchen order ID is required.";

    return;
  }

  loading.value = true;
  errorMessage.value = "";

  try {
    const response =
      await kitchenOrderService
        .getKitchenOrder(
          orderId
        );

    if (
      !response?.data ||
      typeof response.data !==
        "object"
    ) {
      throw new Error(
        "Kitchen order data was not returned."
      );
    }

    order.value =
      response.data;
  } catch (error) {
    console.error(
      "Unable to load kitchen order:",
      error
    );

    errorMessage.value =
      resolveErrorMessage(
        error,
        "Unable to load this kitchen order."
      );
  } finally {
    loading.value = false;
  }
}

/*
|--------------------------------------------------------------------------
| Handle Kitchen Status Update
|--------------------------------------------------------------------------
|
| Child component updated order return করলে current order সঙ্গে সঙ্গে update
| হবে। Updated data না এলে fresh API request করা হবে।
|
*/

function handleOrderUpdated(
  updatedOrder
) {
  if (
    updatedOrder &&
    typeof updatedOrder ===
      "object"
  ) {
    order.value = {
      ...order.value,
      ...updatedOrder,
    };

    return;
  }

  loadOrder();
}

/*
|--------------------------------------------------------------------------
| Download Customer Invoice
|--------------------------------------------------------------------------
*/

async function downloadInvoice() {
  if (downloadingInvoice.value) {
    return;
  }

  const orderId =
    Number(
      order.value?.id ||
      route.params.id
    );

  if (
    !Number.isInteger(orderId) ||
    orderId <= 0
  ) {
    errorMessage.value =
      "A valid order ID is required to download the invoice.";

    return;
  }

  downloadingInvoice.value = true;
  errorMessage.value = "";
  successMessage.value = "";

  try {
    const response =
      await orderService
        .downloadInvoice(
          orderId
        );

    showSuccess(
      response?.fileName
        ? `Invoice downloaded: ${response.fileName}`
        : "Invoice downloaded successfully."
    );
  } catch (error) {
    console.error(
      "Invoice download failed:",
      error
    );

    errorMessage.value =
      resolveErrorMessage(
        error,
        "Unable to download the invoice."
      );
  } finally {
    downloadingInvoice.value = false;
  }
}

/*
|--------------------------------------------------------------------------
| Print Kitchen Order
|--------------------------------------------------------------------------
*/

function printOrder() {
  if (!order.value) {
    errorMessage.value =
      "Order information is not ready for printing.";

    return;
  }

  window.requestAnimationFrame(
    () => {
      window.print();
    }
  );
}

/*
|--------------------------------------------------------------------------
| Show Success Message
|--------------------------------------------------------------------------
*/

function showSuccess(message) {
  successMessage.value =
    String(message || "");

  if (successTimer) {
    clearTimeout(
      successTimer
    );
  }

  successTimer =
    setTimeout(() => {
      successMessage.value = "";
      successTimer = null;
    }, 4000);
}

/*
|--------------------------------------------------------------------------
| Back Navigation
|--------------------------------------------------------------------------
*/

function goBack() {
  router.push({
    name: "kitchen-display",
  });
}

/*
|--------------------------------------------------------------------------
| Resolve API Error
|--------------------------------------------------------------------------
*/

function resolveErrorMessage(
  error,
  fallbackMessage
) {
  if (
    typeof kitchenOrderService
      .getKitchenErrorMessage ===
    "function"
  ) {
    return kitchenOrderService
      .getKitchenErrorMessage(
        error,
        fallbackMessage
      );
  }

  const validationErrors =
    error?.response?.data
      ?.errors;

  if (
    validationErrors &&
    typeof validationErrors ===
      "object"
  ) {
    const firstError =
      Object.values(
        validationErrors
      )
        .flat()
        .find(Boolean);

    if (firstError) {
      return String(
        firstError
      );
    }
  }

  return (
    error?.response?.data
      ?.message ||
    error?.message ||
    fallbackMessage
  );
}

/*
|--------------------------------------------------------------------------
| Watch Route Order ID
|--------------------------------------------------------------------------
|
| একই component open থাকা অবস্থায় route ID change হলে নতুন order load করবে।
|
*/

watch(
  () => route.params.id,
  (
    newOrderId,
    oldOrderId
  ) => {
    if (
      String(newOrderId) ===
      String(oldOrderId)
    ) {
      return;
    }

    order.value = null;
    successMessage.value = "";
    errorMessage.value = "";

    loadOrder();
  }
);

/*
|--------------------------------------------------------------------------
| Initial Page Load
|--------------------------------------------------------------------------
*/

onMounted(() => {
  loadOrder();
});
</script>

<style
  src="@/assets/css/kitchen/kitchen-details.css"
></style>

<style
  src="@/assets/css/kitchen/kitchen-card.css"
></style>

<style
  src="@/assets/css/kitchen/kitchen-responsive.css"
></style>