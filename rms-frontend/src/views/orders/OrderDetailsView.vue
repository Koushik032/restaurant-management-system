<template>
  <div class="order-details-page">
    <!-- ==================================================
         Page Header
    =================================================== -->
    <header class="order-details-header">
      <div class="order-header-content">
        <button
          type="button"
          class="back-button"
          aria-label="Go back"
          title="Back to orders"
          @click="goBack"
        >
          <i class="bi bi-arrow-left"></i>
        </button>

        <div class="order-header-copy">
          <div class="order-title-row">
            <h1 class="order-details-title">
              Order Details
            </h1>

            <span
              v-if="order"
              class="order-number-badge"
            >
              <i class="bi bi-receipt"></i>

              {{ order.order_number }}
            </span>
          </div>

          <p class="order-details-subtitle">
            Complete information about this order,
            customer, table and payments
          </p>
        </div>
      </div>

      <button
        type="button"
        class="print-order-button"
        :disabled="isLoading || !order"
        @click="printOrder"
      >
        <i class="bi bi-printer"></i>

        <span>Print Order</span>
      </button>
    </header>

    <!-- ==================================================
         Loading State
    =================================================== -->
    <div
      v-if="isLoading"
      class="order-details-loading"
    >
      <span
        class="spinner-border"
        role="status"
      ></span>

      <div>
        <strong>Loading order</strong>

        <p>
          Please wait while order information is
          being prepared.
        </p>
      </div>
    </div>

    <!-- ==================================================
         Error State
    =================================================== -->
    <div
      v-else-if="errorMessage"
      class="order-details-error"
    >
      <span class="error-icon">
        <i class="bi bi-exclamation-circle"></i>
      </span>

      <h2>Unable to load order</h2>

      <p>{{ errorMessage }}</p>

      <button
        type="button"
        class="retry-button"
        @click="loadOrderDetails"
      >
        <i class="bi bi-arrow-clockwise"></i>

        <span>Try Again</span>
      </button>
    </div>

    <!-- ==================================================
         Order Details
    =================================================== -->
    <div
      v-else-if="order"
      class="order-details-content"
    >
      <!-- ==================================================
           Order Hero / Overview
      =================================================== -->
      <section class="order-overview-card">
        <div class="overview-main">
          <div class="overview-icon">
            <i class="bi bi-receipt-cutoff"></i>
          </div>

          <div class="overview-information">
            <span class="overview-label">
              Restaurant Order
            </span>

            <h2>
              {{ order.order_number }}
            </h2>

            <div class="overview-meta-list">
              <span>
                <i class="bi bi-calendar3"></i>

                {{
                  order.date ||
                  formattedCreatedDate
                }}
              </span>

              <span>
                <i class="bi bi-clock"></i>

                {{
                  order.time ||
                  formattedCreatedTime
                }}
              </span>

              <span>
                <i class="bi bi-calendar-week"></i>

                {{
                  order.day ||
                  formattedCreatedDay
                }}
              </span>

              <span v-if="order.creator?.name">
                <i class="bi bi-person-badge"></i>

                Created by
                {{ order.creator.name }}
              </span>
            </div>
          </div>
        </div>

        <div class="overview-statuses">
          <div class="overview-status-item">
            <span class="status-item-label">
              Order Status
            </span>

            <span
              class="status-badge"
              :class="
                getOrderStatusClass(
                  order.status
                )
              "
            >
              <i
                :class="
                  getOrderStatusIcon(
                    order.status
                  )
                "
              ></i>

              {{
                order.status_label ||
                formatLabel(order.status)
              }}
            </span>
          </div>

          <div class="overview-status-item">
            <span class="status-item-label">
              Payment Status
            </span>

            <span
              class="status-badge"
              :class="
                getPaymentStatusClass(
                  order.payment_status
                )
              "
            >
              <i
                :class="
                  getPaymentStatusIcon(
                    order.payment_status
                  )
                "
              ></i>

              {{
                order.payment_status_label ||
                formatLabel(
                  order.payment_status
                )
              }}
            </span>
          </div>
        </div>
      </section>
        <!-- ==================================================
             Main Column
        =================================================== -->
        <main class="order-details-main">
          <!-- ==================================================
               Customer and Table Information
          =================================================== -->
          <div class="information-grid">
            <!-- Customer -->
            <section class="details-card">
              <header class="details-card-header">
                <span class="details-card-icon">
                  <i class="bi bi-person"></i>
                </span>

                <div>
                  <h2>Customer Information</h2>

                  <p>
                    Customer identity and contact details
                  </p>
                </div>
              </header>

              <div class="details-card-body">
                <div class="profile-overview">
                  <span class="profile-avatar">
                    {{
                      (
                        order.customer?.name ||
                        "W"
                      )
                        .charAt(0)
                        .toUpperCase()
                    }}
                  </span>

                  <div>
                    <strong>
                      {{
                        order.customer?.name ||
                        "Walk-in Customer"
                      }}
                    </strong>

                    <span>
                      {{
                        order.customer?.id
                          ? `Customer #${order.customer.id}`
                          : "Guest customer"
                      }}
                    </span>
                  </div>
                </div>

                <div class="information-list">
                  <div class="information-row">
                    <span class="information-label">
                      <i class="bi bi-person"></i>

                      Customer Name
                    </span>

                    <strong>
                      {{
                        order.customer?.name ||
                        "Walk-in Customer"
                      }}
                    </strong>
                  </div>

                  <div class="information-row">
                    <span class="information-label">
                      <i class="bi bi-telephone"></i>

                      Phone Number
                    </span>

                    <strong>
                      {{
                        order.customer?.phone ||
                        "Not provided"
                      }}
                    </strong>
                  </div>

                  <div class="information-row">
                    <span class="information-label">
                      <i class="bi bi-envelope"></i>

                      Email Address
                    </span>

                    <strong>
                      {{
                        order.customer?.email ||
                        "Not provided"
                      }}
                    </strong>
                  </div>
                </div>
              </div>
            </section>

            <!-- Table -->
            <section class="details-card">
              <header class="details-card-header">
                <span class="details-card-icon">
                  <i class="bi bi-grid-3x3-gap"></i>
                </span>

                <div>
                  <h2>Table Information</h2>

                  <p>
                    Assigned table and seating details
                  </p>
                </div>
              </header>

              <div class="details-card-body">
                <div class="table-overview">
                  <span class="table-overview-icon">
                    <i class="bi bi-shop-window"></i>
                  </span>

                  <div>
                    <strong>
                      {{
                        order.primary_table
                          ?.table_name ||
                        "No Table"
                      }}
                    </strong>

                    <span>
                      {{
                        order.primary_table
                          ?.section ||
                        "Restaurant Floor"
                      }}
                    </span>
                  </div>
                </div>

                <div class="information-list">
                  <div class="information-row">
                    <span class="information-label">
                      <i class="bi bi-grid"></i>

                      Primary Table
                    </span>

                    <strong>
                      {{
                        order.primary_table
                          ?.table_name ||
                        "Not assigned"
                      }}
                    </strong>
                  </div>

                  <div class="information-row">
                    <span class="information-label">
                      <i class="bi bi-link-45deg"></i>

                      Merged Tables
                    </span>

                    <strong>
                      {{
                        order.merged_table_names ||
                        "None"
                      }}
                    </strong>
                  </div>

                  <div class="information-row">
                    <span class="information-label">
                      <i class="bi bi-people"></i>

                      Total Capacity
                    </span>

                    <strong>
                      {{
                        order.total_table_capacity ||
                        order.primary_table
                          ?.capacity ||
                        0
                      }}
                      seats
                    </strong>
                  </div>

                  <div class="information-row">
                    <span class="information-label">
                      <i class="bi bi-activity"></i>

                      Table Status
                    </span>

                    <strong class="table-status-value">
                      {{
                        formatLabel(
                          order.primary_table
                            ?.status
                        ) ||
                        "Not available"
                      }}
                    </strong>
                  </div>
                </div>
              </div>
            </section>
          </div>

          <!-- ==================================================
               Ordered Items
          =================================================== -->
          <section class="details-card items-details-card">
            <header class="details-card-header">
              <span class="details-card-icon">
                <i class="bi bi-basket2"></i>
              </span>

              <div class="card-heading-flex">
                <div>
                  <h2>Ordered Items</h2>

                  <p>
                    Food items, variants and selected
                    add-ons
                  </p>
                </div>

                <span class="item-count-badge">
                  {{ totalItemQuantity }}
                  {{
                    totalItemQuantity === 1
                      ? "item"
                      : "items"
                  }}
                </span>
              </div>
            </header>

            <div class="order-items-wrapper">
              <table class="details-items-table">
                <thead>
                  <tr>
                    <th>Item Details</th>

                    <th>Variant</th>

                    <th>Add-ons</th>

                    <th class="quantity-cell">
                      Qty
                    </th>

                    

                    <th class="price-cell">
                      Line Total
                    </th>
                  </tr>
                </thead>

                <tbody>
                  <tr
                    v-for="item in order.items"
                    :key="item.id"
                  >
                    <td data-label="Item Details">
                      <div class="item-name-content">
                        <div class="item-title-line">
                          <span class="item-index-icon">
                            <i class="bi bi-cup-hot"></i>
                          </span>

                          <div>
                            <strong>
                              {{ item.item_name }}
                            </strong>

                            <small>
                              Item #{{ item.id }}
                            </small>
                          </div>
                        </div>

                        <div
                          v-if="item.kitchen_note"
                          class="item-kitchen-note"
                        >
                          <i class="bi bi-chat-left-text"></i>

                          <span>
                            {{ item.kitchen_note }}
                          </span>
                        </div>
                      </div>
                    </td>

                    <td data-label="Variant">
                      <span
                        v-if="item.variant_name"
                        class="variant-badge"
                      >
                        {{ item.variant_name }}
                      </span>

                      <span
                        v-else
                        class="empty-value"
                      >
                        Base item
                      </span>
                    </td>

                    <td data-label="Add-ons">
                      <div
                        v-if="item.addons?.length"
                        class="addon-list"
                      >
                        <span
                          v-for="addon in item.addons"
                          :key="addon.id"
                          class="addon-badge"
                        >
                          <i class="bi bi-plus-circle"></i>

                          {{ addon.addon_name }}

                          <small>
                            × {{ addon.quantity }}
                          </small>
                        </span>
                      </div>

                      <span
                        v-else
                        class="empty-value"
                      >
                        No add-ons
                      </span>
                    </td>

                    <td
                      data-label="Qty"
                      class="quantity-cell"
                    >
                      <span class="quantity-badge">
                        {{ item.quantity }}
                      </span>
                    </td>

                    <td
                      data-label="Line Total"
                      class="price-cell item-total-cell"
                    >
                      {{
                        item.line_total_formatted ||
                        formatCurrency(
                          item.line_total
                        )
                      }}
                    </td>
                  </tr>

                  <tr
                    v-if="!order.items?.length"
                  >
                    <td
                      colspan="6"
                      class="empty-table-state"
                    >
                      <i class="bi bi-basket"></i>

                      <strong>
                        No order items found
                      </strong>

                      <span>
                        This order does not contain any
                        menu items.
                      </span>
                    </td>
                  </tr>
                </tbody>
              </table>
            </div>
          </section>

          <!-- ==================================================
               Payment History
          =================================================== -->
          <section class="details-card">
            <header class="details-card-header">
              <span class="details-card-icon">
                <i class="bi bi-clock-history"></i>
              </span>

              <div class="card-heading-flex">
                <div>
                  <h2>Payment History</h2>

                  <p>
                    All recorded transactions for this
                    order
                  </p>
                </div>

                <span class="payment-count-badge">
                  {{ order.payments?.length || 0 }}
                  transactions
                </span>
              </div>
            </header>

            <div
              v-if="order.payments?.length"
              class="payment-history-list"
            >
              <article
                v-for="payment in order.payments"
                :key="payment.id"
                class="payment-history-item"
              >
                <div class="payment-method-icon">
                  <i
                    :class="
                      getPaymentMethodIcon(
                        payment.payment_method
                      )
                    "
                  ></i>
                </div>

                <div class="payment-history-content">
                  <div class="payment-history-heading">
                    <div>
                      <strong>
                        {{
                          payment.payment_method_label ||
                          formatLabel(
                            payment.payment_method
                          )
                        }}
                      </strong>

                      <span>
                        <i class="bi bi-clock"></i>

                        {{
                          payment.date_time ||
                          formatDateTime(
                            payment.created_at
                          )
                        }}
                      </span>
                    </div>

                    <strong class="payment-history-amount">
                      {{
                        payment.amount_formatted ||
                        formatCurrency(
                          payment.amount
                        )
                      }}
                    </strong>
                  </div>

                  <div
                    v-if="
                      payment.reference ||
                      payment.received_by_name ||
                      payment.note
                    "
                    class="payment-history-meta"
                  >
                    <span
                      v-if="payment.reference"
                    >
                      <i class="bi bi-hash"></i>

                      Reference:
                      {{ payment.reference }}
                    </span>

                    <span
                      v-if="
                        payment.received_by_name
                      "
                    >
                      <i class="bi bi-person-check"></i>

                      Received by
                      {{ payment.received_by_name }}
                    </span>

                    <span
                      v-if="payment.note"
                    >
                      <i class="bi bi-chat-left"></i>

                      {{ payment.note }}
                    </span>
                  </div>
                </div>
              </article>
            </div>

            <div
              v-else
              class="payment-history-empty"
            >
              <span>
                <i class="bi bi-wallet2"></i>
              </span>

              <h3>No payment recorded</h3>

              <p>
                This order does not have any payment
                transaction yet.
              </p>
            </div>
          </section>

          <!-- ==================================================
               Notes
          =================================================== -->
          <section
            v-if="
              order.order_note ||
              order.kitchen_note ||
              order.cancellation_reason
            "
            class="details-card"
          >
            <header class="details-card-header">
              <span class="details-card-icon">
                <i class="bi bi-sticky"></i>
              </span>

              <div>
                <h2>Notes & Instructions</h2>

                <p>
                  Additional order and kitchen
                  information
                </p>
              </div>
            </header>

            <div class="notes-grid">
              <article
                v-if="order.order_note"
                class="note-item"
              >
                <span class="note-icon">
                  <i class="bi bi-receipt"></i>
                </span>

                <div>
                  <span class="note-item-label">
                    Order Note
                  </span>

                  <p>{{ order.order_note }}</p>
                </div>
              </article>

              <article
                v-if="order.kitchen_note"
                class="note-item"
              >
                <span class="note-icon">
                  <i class="bi bi-fire"></i>
                </span>

                <div>
                  <span class="note-item-label">
                    Kitchen Note
                  </span>

                  <p>{{ order.kitchen_note }}</p>
                </div>
              </article>

              <article
                v-if="order.cancellation_reason"
                class="note-item cancellation-note"
              >
                <span class="note-icon">
                  <i class="bi bi-x-circle"></i>
                </span>

                <div>
                  <span class="note-item-label">
                    Cancellation Reason
                  </span>

                  <p>
                    {{ order.cancellation_reason }}
                  </p>
                </div>
              </article>
            </div>
          </section>

          <aside class="order-summary-card">
  <!-- ==========================================
       Header
  =========================================== -->
  <header class="summary-card-header">
    <div class="summary-card-heading">
      <span class="summary-card-icon">
        <i class="bi bi-receipt-cutoff"></i>
      </span>

      <div>
        <h2>Order Summary</h2>

        <p>
          Complete financial and order overview
        </p>
      </div>
    </div>
  </header>

  <!-- ==========================================
       Order Identity
  =========================================== -->
  <section class="summary-section summary-identity-section">
    <span class="summary-section-label">
      Order ID
    </span>

    <strong class="summary-order-number">
      {{ order.order_number }}
    </strong>

    <div class="summary-status-pair">
      <span
        class="summary-status-pill"
        :class="
          getOrderStatusClass(
            order.status
          )
        "
      >
        <i
          :class="
            getOrderStatusIcon(
              order.status
            )
          "
        ></i>

        {{
          order.status_label ||
          formatLabel(order.status)
        }}
      </span>

      <span
        class="summary-status-pill"
        :class="
          getPaymentStatusClass(
            order.payment_status
          )
        "
      >
        <i
          :class="
            getPaymentStatusIcon(
              order.payment_status
            )
          "
        ></i>

        {{
          order.payment_status_label ||
          formatLabel(
            order.payment_status
          )
        }}
      </span>
    </div>
  </section>

  <!-- ==========================================
       Financial Overview
  =========================================== -->
  <section class="summary-section">
    <div class="summary-section-title">
      <span>
        <i class="bi bi-calculator"></i>

        Financial Overview
      </span>
    </div>

    <div class="summary-financial-list">
      <div class="summary-financial-row">
        <span>Subtotal</span>

        <strong>
          {{
            order.subtotal_formatted ||
            formatCurrency(
              order.subtotal
            )
          }}
        </strong>
      </div>

      <div class="summary-financial-row">
        <span>Discount</span>

        <strong class="summary-discount-amount">
          −
          {{
            order.discount_amount_formatted ||
            formatCurrency(
              order.discount_amount
            )
          }}
        </strong>
      </div>

      <div class="summary-financial-row">
        <span>Tax</span>

        <strong>
          {{
            order.tax_amount_formatted ||
            formatCurrency(
              order.tax_amount
            )
          }}
        </strong>
      </div>

      <div class="summary-financial-row">
        <span>Service Charge</span>

        <strong>
          {{
            order.service_charge_formatted ||
            formatCurrency(
              order.service_charge
            )
          }}
        </strong>
      </div>
    </div>
  </section>

  <!-- ==========================================
       Grand Total
  =========================================== -->
  <section class="summary-grand-total-card">
    <div>
      <span>Total Amount</span>

      <small>
        Final payable amount
      </small>
    </div>

    <strong>
      {{
        order.total_amount_formatted ||
        formatCurrency(
          order.total_amount
        )
      }}
    </strong>
  </section>

  <!-- ==========================================
       Payment Overview
  =========================================== -->
  <section class="summary-section">
    <div class="summary-section-title">
      <span>
        <i class="bi bi-wallet2"></i>

        Payment Overview
      </span>
    </div>

    <div class="summary-payment-grid">
      <article class="summary-payment-box paid-box">
        <span>
          <i class="bi bi-check-circle"></i>

          Paid
        </span>

        <strong>
          {{
            order.paid_amount_formatted ||
            formatCurrency(
              order.paid_amount
            )
          }}
        </strong>
      </article>

      <article class="summary-payment-box due-box">
        <span>
          <i class="bi bi-exclamation-circle"></i>

          Due
        </span>

        <strong>
          {{
            order.due_amount_formatted ||
            formatCurrency(
              order.due_amount
            )
          }}
        </strong>
      </article>
    </div>

    <div class="summary-payment-progress">
      <div class="summary-progress-header">
        <span>Payment Progress</span>

        <strong>
          {{
            order.total_amount > 0
              ? Math.round(
                  Math.min(
                    (
                      order.paid_amount /
                      order.total_amount
                    ) * 100,
                    100
                  )
                )
              : 0
          }}%
        </strong>
      </div>

      <div class="payment-progress-track">
        <span
          :style="{
            width:
              order.total_amount > 0
                ? `${Math.min(
                    (
                      order.paid_amount /
                      order.total_amount
                    ) * 100,
                    100
                  )}%`
                : '0%',
          }"
        ></span>
      </div>
    </div>
  </section>

  <!-- ==========================================
       Payment Method
  =========================================== -->
  <section class="summary-section">
    <div class="summary-section-title">
      <span>
        <i class="bi bi-credit-card"></i>

        Payment Method
      </span>
    </div>

    <div class="summary-payment-method-card">
      <span class="summary-payment-method-icon">
        <i
          :class="
            getPaymentMethodIcon(
              order.payment_method
            )
          "
        ></i>
      </span>

      <div>
        <strong>
          {{
            order.payment_method_label ||
            formatLabel(
              order.payment_method
            ) ||
            "No payment"
          }}
        </strong>

        <small
          v-if="order.payment_reference"
        >
          Reference:
          {{ order.payment_reference }}
        </small>

        <small v-else>
          No payment reference
        </small>
      </div>
    </div>
  </section>

  <!-- ==========================================
       Order Information
  =========================================== -->
  <section class="summary-section summary-meta-section">
    <div class="summary-section-title">
      <span>
        <i class="bi bi-info-circle"></i>

        Order Information
      </span>
    </div>

    <div class="summary-meta-list">
      <div class="summary-meta-row">
        <span>
          <i class="bi bi-calendar3"></i>

          Order Date
        </span>

        <strong>
          {{
            order.date ||
            formattedCreatedDate
          }}
        </strong>
      </div>

      <div class="summary-meta-row">
        <span>
          <i class="bi bi-clock"></i>

          Order Time
        </span>

        <strong>
          {{
            order.time ||
            formattedCreatedTime
          }}
        </strong>
      </div>

      <div class="summary-meta-row">
        <span>
          <i class="bi bi-calendar-week"></i>

          Order Day
        </span>

        <strong>
          {{
            order.day ||
            formattedCreatedDay
          }}
        </strong>
      </div>

      <div
        v-if="order.creator?.name"
        class="summary-meta-row"
      >
        <span>
          <i class="bi bi-person-badge"></i>

          Created By
        </span>

        <strong>
          {{ order.creator.name }}
        </strong>
      </div>
    </div>
  </section>
</aside>
        </main>
    </div>
  </div>
</template>

<script src="./OrderDetailsView.js"></script>

<style
  scoped
  src="./OrderDetailsView.css"
></style>