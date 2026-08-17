<template>
  <section class="po-details-page">
    <!-- Loading -->
    <div
      v-if="loading"
      class="po-details-loading"
    >
      <span class="po-details-spinner"></span>
      Loading purchase order details...
    </div>

    <template v-else-if="order">
      <!-- Details Header -->
      <div class="po-details-page-header">
        <div class="po-details-title-area">
          <button
            type="button"
            class="po-back-btn"
            @click="closeDetails"
          >
            <i class="bi bi-arrow-left"></i>
          </button>

          <div>
            <h3>
              Purchase Order Details
            </h3>

            <p>
              Purchase, payment and receiving information
            </p>
          </div>
        </div>

        <div class="po-details-header-actions">
          <PurchaseReceiveAction
            :purchase-order="order"
            :can-manage="canManage"
            @received="handleReceived"
            @error="handleReceiveError"
          />

          <button
            v-if="canEdit"
            type="button"
            class="po-details-edit-btn"
            @click="editOrder"
          >
            <i class="bi bi-pencil"></i>
            Edit Order
          </button>
        </div>
      </div>

      <!-- Action Message -->
      <div
        v-if="actionMessage"
        class="po-ledger-alert po-ledger-alert-success"
        role="status"
      >
        <i class="bi bi-check-circle-fill"></i>
        <span>{{ actionMessage }}</span>

        <button
          type="button"
          aria-label="Dismiss message"
          @click="actionMessage = ''"
        >
          <i class="bi bi-x-lg"></i>
        </button>
      </div>

      <!-- Supplier Information -->
      <div class="po-details-card">
        <div class="po-details-card-header">
          <i class="bi bi-truck"></i>
          <h4>Supplier Information</h4>
        </div>

        <div class="po-details-grid">
          <div class="po-detail-item">
            <span>Supplier Name</span>
            <strong>{{ supplierName }}</strong>
          </div>

          <div class="po-detail-item">
            <span>Phone</span>
            <strong>{{ order.supplier?.phone || '-' }}</strong>
          </div>

          <div class="po-detail-item">
            <span>Email</span>
            <strong>{{ order.supplier?.email || '-' }}</strong>
          </div>

          <div class="po-detail-item">
            <span>Address</span>
            <strong>{{ order.supplier?.address || '-' }}</strong>
          </div>
        </div>
      </div>

      <!-- Order Information -->
      <div class="po-details-card">
        <div class="po-details-card-header">
          <i class="bi bi-receipt"></i>
          <h4>Order Information</h4>
        </div>

        <div class="po-details-grid">
          <div class="po-detail-item">
            <span>Order Date</span>
            <strong>
              {{ order.order_date_label || order.order_date || '-' }}
            </strong>
          </div>

          <div class="po-detail-item">
            <span>Delivery Date</span>
            <strong>
              {{
                order.delivery_date_label
                || order.delivery_date
                || '-'
              }}
            </strong>
          </div>

          <div class="po-detail-item">
            <span>Status</span>
            <strong
              class="po-status-badge"
              :class="`po-status-${statusValue}`"
            >
              {{ order.status_label || statusLabel(statusValue) }}
            </strong>
          </div>

          <div class="po-detail-item">
            <span>Ordered By</span>
            <strong>{{ order.ordered_by?.name || '-' }}</strong>
          </div>

          <div class="po-detail-item">
            <span>Total Receipts</span>
            <strong>{{ receiptHistory.length }}</strong>
          </div>

          <div class="po-detail-item">
            <span>Total Payments</span>
            <strong>{{ paymentHistory.length }}</strong>
          </div>
        </div>
      </div>

      <!-- Items -->
      <div class="po-details-card">
        <div class="po-details-card-header po-ledger-card-heading">
          <div class="po-ledger-heading-title">
            <i class="bi bi-box-seam"></i>
            <div>
              <h4>Purchase Items</h4>
              <small>
                Ordered, received and remaining quantities
              </small>
            </div>
          </div>
        </div>

        <div class="po-details-table-wrapper">
          <table class="po-details-table">
            <thead>
              <tr>
                <th>SL</th>
                <th>Item</th>
                <th>Unit</th>
                <th>Quantity</th>
                <th>Received</th>
                <th>Pending</th>
                <th>Unit Price</th>
                <th>Total</th>
              </tr>
            </thead>

            <tbody>
              <tr
                v-for="(item, index) in orderItems"
                :key="item.id || index"
              >
                <td>{{ index + 1 }}</td>

                <td>
                  <strong>{{ item.item_name || '-' }}</strong>
                </td>

                <td>{{ item.unit || '-' }}</td>

                <td>
                  {{ formatQuantity(item.quantity) }}
                </td>

                <td>
                  <span class="po-quantity-received">
                    {{ formatQuantity(item.received_quantity) }}
                  </span>
                </td>

                <td>
                  <span
                    :class="{
                      'po-quantity-pending':
                        pendingQuantity(item) > 0,
                    }"
                  >
                    {{ formatQuantity(pendingQuantity(item)) }}
                  </span>
                </td>

                <td>
                  {{
                    item.unit_price_formatted
                    || formatMoney(item.unit_price)
                  }}
                </td>

                <td>
                  <strong>
                    {{
                      item.total_price_formatted
                      || formatMoney(item.total_price)
                    }}
                  </strong>
                </td>
              </tr>

              <tr v-if="orderItems.length === 0">
                <td
                  colspan="8"
                  class="po-details-empty"
                >
                  No purchase items found.
                </td>
              </tr>
            </tbody>
          </table>
        </div>
      </div>

      <!-- Payment Summary -->
      <div class="po-details-card">
        <div class="po-details-card-header po-ledger-card-heading">
          <div class="po-ledger-heading-title">
            <i class="bi bi-cash-stack"></i>

            <div>
              <h4>Payment Summary</h4>
              <small>
                Payments are stored as an immutable ledger
              </small>
            </div>
          </div>

          <button
            v-if="canRecordPayment"
            type="button"
            class="po-ledger-primary-button"
            @click="togglePaymentForm"
          >
            <i
              class="bi"
              :class="showPaymentForm ? 'bi-x-lg' : 'bi-plus-circle'"
            ></i>

            {{ showPaymentForm ? 'Close Payment Form' : 'Add Payment' }}
          </button>
        </div>

        <div class="po-payment-grid">
          <div class="po-payment-row">
            <span>Subtotal</span>
            <strong>
              {{ order.subtotal_formatted || formatMoney(order.subtotal) }}
            </strong>
          </div>

          <div class="po-payment-row">
            <span>Tax</span>
            <strong>
              {{ order.tax_formatted || formatMoney(order.tax) }}
            </strong>
          </div>

          <div class="po-payment-row">
            <span>Service Charge</span>
            <strong>
              {{
                order.service_charge_formatted
                || formatMoney(order.service_charge)
              }}
            </strong>
          </div>

          <div class="po-payment-row po-payment-total">
            <span>Total Amount</span>
            <strong>
              {{
                order.total_amount_formatted
                || formatMoney(order.total_amount)
              }}
            </strong>
          </div>

          <div class="po-payment-row po-payment-paid">
            <span>Paid Amount</span>
            <strong>
              {{
                order.paid_amount_formatted
                || formatMoney(order.paid_amount)
              }}
            </strong>
          </div>

          <div class="po-payment-row po-payment-due">
            <span>Due Amount</span>
            <strong>
              {{
                order.due_amount_formatted
                || formatMoney(order.due_amount)
              }}
            </strong>
          </div>

          <div class="po-payment-row">
            <span>Payment Method Summary</span>
            <strong>
              {{
                order.payment_method_label
                || paymentMethodLabel(order.payment_method)
                || '-'
              }}
            </strong>
          </div>

          <div class="po-payment-row">
            <span>Payment Status</span>
            <strong
              :class="
                isFullyPaid
                  ? 'po-payment-status-paid'
                  : 'po-payment-status-due'
              "
            >
              {{ isFullyPaid ? 'Fully Paid' : 'Payment Due' }}
            </strong>
          </div>
        </div>

        <!-- Add Payment Form -->
        <form
          v-if="showPaymentForm"
          class="po-payment-entry-form"
          novalidate
          @submit.prevent="submitPayment"
        >
          <div class="po-ledger-form-header">
            <div>
              <strong>Record Partial / Full Payment</strong>
              <small>
                Current due:
                {{ order.due_amount_formatted || formatMoney(order.due_amount) }}
              </small>
            </div>
          </div>

          <div
            v-if="paymentError"
            class="po-ledger-alert po-ledger-alert-error"
            role="alert"
          >
            <i class="bi bi-exclamation-circle-fill"></i>
            <span>{{ paymentError }}</span>
          </div>

          <div class="po-ledger-form-grid">
            <div class="po-ledger-form-group">
              <label for="po-payment-amount">
                Amount <span>*</span>
              </label>

              <div class="po-money-field">
                <span>৳</span>
                <input
                  id="po-payment-amount"
                  v-model.number="paymentForm.amount"
                  type="number"
                  min="0.01"
                  :max="numericDueAmount"
                  step="0.01"
                  :disabled="paymentSubmitting"
                  placeholder="0.00"
                />
              </div>

              <small
                v-if="paymentFieldError('amount')"
                class="po-field-error"
              >
                {{ paymentFieldError('amount') }}
              </small>
            </div>

            <div class="po-ledger-form-group">
              <label for="po-payment-method-ledger">
                Payment Method <span>*</span>
              </label>

              <select
                id="po-payment-method-ledger"
                v-model="paymentForm.payment_method"
                :disabled="paymentSubmitting"
              >
                <option value="">Select payment method</option>
                <option value="cash">Cash</option>
                <option value="card">Card</option>
                <option value="bkash">Bkash</option>
                <option value="nagad">Nagad</option>
                <option value="bank_transfer">Bank Transfer</option>
                <option value="other">Other</option>
              </select>

              <small
                v-if="paymentFieldError('payment_method')"
                class="po-field-error"
              >
                {{ paymentFieldError('payment_method') }}
              </small>
            </div>

            <div class="po-ledger-form-group">
              <label for="po-payment-date">
                Payment Date
              </label>

              <input
                id="po-payment-date"
                v-model="paymentForm.payment_date"
                type="date"
                :disabled="paymentSubmitting"
              />
            </div>

            <div class="po-ledger-form-group">
              <label for="po-payment-reference">
                Transaction Reference
              </label>

              <input
                id="po-payment-reference"
                v-model.trim="paymentForm.transaction_reference"
                type="text"
                maxlength="255"
                :disabled="paymentSubmitting"
                placeholder="Optional reference"
              />
            </div>

            <div class="po-ledger-form-group po-ledger-form-full">
              <label for="po-payment-notes">
                Notes
              </label>

              <textarea
                id="po-payment-notes"
                v-model.trim="paymentForm.notes"
                rows="2"
                maxlength="2000"
                :disabled="paymentSubmitting"
                placeholder="Optional payment note"
              ></textarea>
            </div>
          </div>

          <div class="po-ledger-form-actions">
            <button
              type="button"
              class="po-ledger-secondary-button"
              :disabled="paymentSubmitting"
              @click="closePaymentForm"
            >
              Cancel
            </button>

            <button
              type="submit"
              class="po-ledger-primary-button"
              :disabled="paymentSubmitting"
            >
              <span
                v-if="paymentSubmitting"
                class="spinner-border spinner-border-sm"
              ></span>

              <i
                v-else
                class="bi bi-cash-coin"
              ></i>

              {{ paymentSubmitting ? 'Recording...' : 'Record Payment' }}
            </button>
          </div>
        </form>
      </div>

      <!-- Payment History -->
      <div class="po-details-card">
        <div class="po-details-card-header po-ledger-card-heading">
          <div class="po-ledger-heading-title">
            <i class="bi bi-clock-history"></i>

            <div>
              <h4>Payment History</h4>
              <small>
                {{ paymentHistory.length }} payment record(s)
              </small>
            </div>
          </div>

          <span class="po-immutable-badge">
            <i class="bi bi-shield-lock"></i>
            Immutable
          </span>
        </div>

        <div
          v-if="paymentHistory.length === 0"
          class="po-ledger-empty-state"
        >
          <i class="bi bi-wallet2"></i>
          <strong>No payment history yet</strong>
          <span>
            Initial advance and later payments will appear here.
          </span>
        </div>

        <div
          v-else
          class="po-details-table-wrapper"
        >
          <table class="po-details-table po-ledger-table">
            <thead>
              <tr>
                <th>SL</th>
                <th>Date</th>
                <th>Amount</th>
                <th>Method</th>
                <th>Reference</th>
                <th>Recorded By</th>
                <th>Note</th>
              </tr>
            </thead>

            <tbody>
              <tr
                v-for="(payment, index) in paymentHistory"
                :key="payment.id || index"
              >
                <td>{{ index + 1 }}</td>

                <td>
                  {{
                    payment.payment_date_label
                    || payment.payment_date
                    || '-'
                  }}
                </td>

                <td>
                  <strong class="po-payment-history-amount">
                    {{
                      payment.amount_formatted
                      || formatMoney(payment.amount)
                    }}
                  </strong>
                </td>

                <td>
                  {{
                    payment.payment_method_label
                    || paymentMethodLabel(payment.payment_method)
                    || '-'
                  }}
                </td>

                <td>
                  {{ payment.transaction_reference || '-' }}
                </td>

                <td>
                  {{
                    payment.created_by?.name
                    || payment.creator?.name
                    || '-'
                  }}
                </td>

                <td>
                  {{ payment.notes || '-' }}
                </td>
              </tr>
            </tbody>
          </table>
        </div>
      </div>

      <!-- GRN / Receiving History -->
      <div class="po-details-card">
        <div class="po-details-card-header po-ledger-card-heading">
          <div class="po-ledger-heading-title">
            <i class="bi bi-box-arrow-in-down"></i>

            <div>
              <h4>GRN / Receiving History</h4>
              <small>
                {{ receiptHistory.length }} receipt record(s)
              </small>
            </div>
          </div>

          <span class="po-immutable-badge po-grn-badge">
            <i class="bi bi-file-earmark-check"></i>
            Warehouse Receipt Ledger
          </span>
        </div>

        <div
          v-if="receiptHistory.length === 0"
          class="po-ledger-empty-state"
        >
          <i class="bi bi-box-seam"></i>
          <strong>No receiving history yet</strong>
          <span>
            Receive this purchase order to create the first GRN
            and increase warehouse stock.
          </span>
        </div>

        <div
          v-else
          class="po-grn-list"
        >
          <article
            v-for="(receipt, receiptIndex) in receiptHistory"
            :key="receipt.id || receiptIndex"
            class="po-grn-card"
          >
            <header class="po-grn-card-header">
              <div>
                <strong>
                  {{ receipt.receipt_no || `GRN #${receipt.id}` }}
                </strong>

                <span>
                  {{
                    receipt.received_at_label
                    || formatDateTime(receipt.received_at)
                  }}
                </span>
              </div>

              <div class="po-grn-meta">
                <span>
                  Received by:
                  <strong>
                    {{
                      receipt.received_by?.name
                      || receipt.receivedBy?.name
                      || '-'
                    }}
                  </strong>
                </span>

                <span>
                  {{ receiptItems(receipt).length }} item(s)
                </span>
              </div>
            </header>

            <div class="po-details-table-wrapper">
              <table class="po-details-table po-ledger-table">
                <thead>
                  <tr>
                    <th>Item</th>
                    <th>Quantity</th>
                    <th>Unit Cost</th>
                    <th>Total Cost</th>
                    <th>Note</th>
                  </tr>
                </thead>

                <tbody>
                  <tr
                    v-for="(item, itemIndex) in receiptItems(receipt)"
                    :key="item.id || itemIndex"
                  >
                    <td>
                      <strong>
                        {{
                          item.item_name
                          || item.raw_material?.material_name
                          || '-'
                        }}
                      </strong>
                    </td>

                    <td>
                      {{ formatQuantity(item.quantity) }}
                      {{ item.unit || '' }}
                    </td>

                    <td>
                      {{
                        item.unit_cost_formatted
                        || formatMoney(item.unit_cost)
                      }}
                    </td>

                    <td>
                      <strong>
                        {{
                          item.total_cost_formatted
                          || formatMoney(
                            item.total_cost
                            ?? Number(item.quantity || 0)
                              * Number(item.unit_cost || 0)
                          )
                        }}
                      </strong>
                    </td>

                    <td>{{ item.notes || '-' }}</td>
                  </tr>
                </tbody>
              </table>
            </div>

            <footer
              v-if="receipt.notes"
              class="po-grn-note"
            >
              <i class="bi bi-sticky"></i>
              {{ receipt.notes }}
            </footer>
          </article>
        </div>
      </div>

      <!-- Notes -->
      <div class="po-details-card">
        <div class="po-details-card-header">
          <i class="bi bi-journal-text"></i>
          <h4>Notes</h4>
        </div>

        <p class="po-details-notes">
          {{ order.notes || 'No notes added.' }}
        </p>
      </div>
    </template>

    <!-- No Data -->
    <div
      v-else
      class="po-details-empty-state"
    >
      <i class="bi bi-receipt"></i>
      <h4>Purchase order not found</h4>

      <button
        type="button"
        class="po-back-btn-text"
        @click="closeDetails"
      >
        Back to Purchase Orders
      </button>
    </div>
  </section>
</template>

<script setup>
import {
  computed,
  reactive,
  ref,
  watch,
} from 'vue'

import PurchaseReceiveAction
  from './PurchaseReceiveAction.vue'

import purchaseOrderPaymentService
  from '@/services/purchaseOrderPaymentService'

const props = defineProps({
  order: {
    type: Object,
    default: null,
  },

  loading: {
    type: Boolean,
    default: false,
  },

  canManage: {
    type: Boolean,
    default: false,
  },
})

const emit = defineEmits([
  'close',
  'edit',
  'changed',
  'received',
  'receive-error',
])

const showPaymentForm =
  ref(false)

const paymentSubmitting =
  ref(false)

const paymentError =
  ref('')

const paymentServerErrors =
  ref({})

const actionMessage =
  ref('')

const paymentForm = reactive({
  amount: '',
  payment_method: '',
  payment_date: todayDate(),
  transaction_reference: '',
  notes: '',
})

const statusValue = computed(() => {
  const value =
    props.order?.status?.value
    ?? props.order?.status
    ?? ''

  return String(value)
    .trim()
    .toLowerCase()
})

const supplierName = computed(() => {
  return (
    props.order?.supplier_name
    || props.order?.supplier?.company_name
    || props.order?.supplier?.supplier_name
    || props.order?.supplier?.name
    || '-'
  )
})

const orderItems = computed(() => {
  return normalizeCollection(
    props.order?.items,
  )
})

const paymentHistory = computed(() => {
  return normalizeCollection(
    props.order?.payments,
  )
})

const receiptHistory = computed(() => {
  return normalizeCollection(
    props.order?.receipts,
  )
})

const numericDueAmount = computed(() => {
  return Math.max(
    0,
    Number(
      props.order?.due_amount || 0,
    ),
  )
})

const isFullyPaid = computed(() => {
  if (
    props.order?.is_fully_paid === true
  ) {
    return true
  }

  return (
    Number(props.order?.total_amount || 0) > 0
    && numericDueAmount.value <= 0
  )
})

const canRecordPayment = computed(() => {
  return (
    props.canManage
    && Boolean(props.order?.id)
    && statusValue.value !== 'cancelled'
    && numericDueAmount.value > 0
  )
})

const canEdit = computed(() => {
  return (
    props.canManage
    && ![
      'partially_received',
      'received',
      'cancelled',
    ].includes(statusValue.value)
  )
})

function closeDetails() {
  emit('close')
}

function editOrder() {
  if (!canEdit.value) {
    return
  }

  emit('edit')
}

function handleReceived(result) {
  actionMessage.value =
    result?.message
    || 'Purchase order received successfully.'

  closePaymentForm()

  emit(
    'received',
    result,
  )
}

function handleReceiveError(result) {
  emit(
    'receive-error',
    result,
  )
}

function togglePaymentForm() {
  if (!canRecordPayment.value) {
    return
  }

  if (showPaymentForm.value) {
    closePaymentForm()
    return
  }

  resetPaymentForm()
  showPaymentForm.value = true
}

function closePaymentForm() {
  if (paymentSubmitting.value) {
    return
  }

  showPaymentForm.value = false
  resetPaymentForm()
}

function resetPaymentForm() {
  paymentForm.amount = ''
  paymentForm.payment_method = ''
  paymentForm.payment_date = todayDate()
  paymentForm.transaction_reference = ''
  paymentForm.notes = ''

  paymentError.value = ''
  paymentServerErrors.value = {}
}

async function submitPayment() {
  if (
    paymentSubmitting.value
    || !canRecordPayment.value
    || !props.order?.id
  ) {
    return
  }

  paymentError.value = ''
  paymentServerErrors.value = {}

  const amount =
    Number(paymentForm.amount)

  if (
    !Number.isFinite(amount)
    || amount <= 0
  ) {
    paymentError.value =
      'Payment amount must be greater than zero.'

    return
  }

  if (
    amount > numericDueAmount.value
  ) {
    paymentError.value =
      `Payment amount cannot exceed the current due amount of ${formatMoney(numericDueAmount.value)}.`

    return
  }

  if (!paymentForm.payment_method) {
    paymentError.value =
      'Please select a payment method.'

    return
  }

  paymentSubmitting.value = true

  try {
    const response =
      await purchaseOrderPaymentService
        .recordPayment(
          props.order.id,
          {
            amount,
            payment_method:
              paymentForm.payment_method,
            payment_date:
              paymentForm.payment_date || null,
            transaction_reference:
              paymentForm.transaction_reference.trim()
              || null,
            notes:
              paymentForm.notes.trim()
              || null,
          },
        )

    const message =
      response?.message
      || 'Purchase payment recorded successfully.'

    paymentSubmitting.value = false
    showPaymentForm.value = false
    resetPaymentForm()

    actionMessage.value = message

    emit(
      'changed',
      {
        purchaseOrderId:
          props.order.id,
        message,
      },
    )
  } catch (error) {
    paymentServerErrors.value =
      purchaseOrderPaymentService
        .getValidationErrors(error)

    paymentError.value =
      purchaseOrderPaymentService
        .getErrorMessage(
          error,
          'Unable to record purchase payment.',
        )
  } finally {
    paymentSubmitting.value = false
  }
}

function paymentFieldError(field) {
  const value =
    paymentServerErrors.value?.[field]

  if (Array.isArray(value)) {
    return value[0] || ''
  }

  return value
    ? String(value)
    : ''
}

function normalizeCollection(value) {
  const resolved =
    value?.data ?? value ?? []

  return Array.isArray(resolved)
    ? resolved
    : []
}

function receiptItems(receipt) {
  return normalizeCollection(
    receipt?.items,
  )
}

function pendingQuantity(item) {
  if (
    item?.pending_quantity !== undefined
    && item?.pending_quantity !== null
  ) {
    return Math.max(
      0,
      Number(item.pending_quantity || 0),
    )
  }

  return Math.max(
    0,
    Number(item?.quantity || 0)
      - Number(item?.received_quantity || 0),
  )
}

function statusLabel(status) {
  return {
    ordered: 'Ordered',
    partially_received:
      'Partially Received',
    received: 'Received',
    cancelled: 'Cancelled',
  }[status] || status || '-'
}

function paymentMethodLabel(method) {
  return {
    cash: 'Cash',
    card: 'Card',
    bkash: 'Bkash',
    nagad: 'Nagad',
    bank_transfer: 'Bank Transfer',
    mixed: 'Mixed',
    other: 'Other',
  }[method] || (
    method
      ? String(method)
        .replaceAll('_', ' ')
        .replace(
          /\b\w/g,
          (character) =>
            character.toUpperCase(),
        )
      : ''
  )
}

function formatMoney(value) {
  return `৳ ${Number(value || 0).toLocaleString(
    'en-BD',
    {
      minimumFractionDigits: 2,
      maximumFractionDigits: 2,
    },
  )}`
}

function formatQuantity(value) {
  return Number(value || 0).toLocaleString(
    'en-BD',
    {
      maximumFractionDigits: 4,
    },
  )
}

function formatDateTime(value) {
  if (!value) {
    return '-'
  }

  const date = new Date(value)

  if (
    Number.isNaN(
      date.getTime(),
    )
  ) {
    return String(value)
  }

  return date.toLocaleString(
    'en-BD',
    {
      dateStyle: 'medium',
      timeStyle: 'short',
    },
  )
}

function todayDate() {
  const now = new Date()
  const offset =
    now.getTimezoneOffset()
    * 60000

  return new Date(
    now.getTime() - offset,
  )
    .toISOString()
    .slice(0, 10)
}

watch(
  () => props.order?.id,
  () => {
    showPaymentForm.value = false
    actionMessage.value = ''
    resetPaymentForm()
  },
)

watch(
  numericDueAmount,
  (dueAmount) => {
    if (
      dueAmount <= 0
      && showPaymentForm.value
    ) {
      closePaymentForm()
    }
  },
)
</script>

<style scoped>
.po-details-header-actions {
  display: flex;
  align-items: center;
  justify-content: flex-end;
  gap: 0.7rem;
  flex-wrap: wrap;
}

.po-ledger-card-heading {
  display: flex;
  align-items: center;
  justify-content: space-between;
  gap: 1rem;
  flex-wrap: wrap;
}

.po-ledger-heading-title {
  display: flex;
  align-items: center;
  gap: 0.75rem;
}

.po-ledger-heading-title h4 {
  margin: 0;
}

.po-ledger-heading-title small {
  display: block;
  margin-top: 0.15rem;
  color: #6b7280;
}

.po-ledger-primary-button,
.po-ledger-secondary-button {
  border: 0;
  border-radius: 8px;
  padding: 0.65rem 0.9rem;
  display: inline-flex;
  align-items: center;
  justify-content: center;
  gap: 0.45rem;
  font-weight: 700;
  cursor: pointer;
}

.po-ledger-primary-button {
  background: #0d6efd;
  color: #fff;
}

.po-ledger-secondary-button {
  border: 1px solid #d1d5db;
  background: #fff;
  color: #374151;
}

.po-ledger-primary-button:disabled,
.po-ledger-secondary-button:disabled {
  opacity: 0.65;
  cursor: not-allowed;
}

.po-ledger-alert {
  margin-bottom: 1rem;
  padding: 0.8rem 1rem;
  border-radius: 8px;
  display: flex;
  align-items: center;
  gap: 0.65rem;
}

.po-ledger-alert > span {
  flex: 1;
}

.po-ledger-alert > button {
  border: 0;
  background: transparent;
  color: inherit;
}

.po-ledger-alert-success {
  color: #166534;
  background: #dcfce7;
  border: 1px solid #bbf7d0;
}

.po-ledger-alert-error {
  color: #991b1b;
  background: #fee2e2;
  border: 1px solid #fecaca;
}

.po-payment-entry-form {
  margin-top: 1rem;
  padding: 1rem;
  border: 1px solid #dbeafe;
  border-radius: 10px;
  background: #f8fbff;
}

.po-ledger-form-header {
  margin-bottom: 1rem;
}

.po-ledger-form-header > div {
  display: grid;
  gap: 0.15rem;
}

.po-ledger-form-header small {
  color: #6b7280;
}

.po-ledger-form-grid {
  display: grid;
  grid-template-columns:
    repeat(2, minmax(0, 1fr));
  gap: 1rem;
}

.po-ledger-form-group {
  display: grid;
  gap: 0.4rem;
}

.po-ledger-form-group label {
  font-size: 0.85rem;
  font-weight: 700;
  color: #374151;
}

.po-ledger-form-group label span {
  color: #dc2626;
}

.po-ledger-form-group input,
.po-ledger-form-group select,
.po-ledger-form-group textarea {
  width: 100%;
  border: 1px solid #d1d5db;
  border-radius: 7px;
  padding: 0.65rem 0.75rem;
  background: #fff;
}

.po-ledger-form-group input:focus,
.po-ledger-form-group select:focus,
.po-ledger-form-group textarea:focus {
  outline: none;
  border-color: #86b7fe;
  box-shadow: 0 0 0 3px rgba(13, 110, 253, 0.12);
}

.po-ledger-form-full {
  grid-column: 1 / -1;
}

.po-money-field {
  display: flex;
  align-items: stretch;
}

.po-money-field > span {
  padding: 0.65rem 0.75rem;
  border: 1px solid #d1d5db;
  border-right: 0;
  border-radius: 7px 0 0 7px;
  background: #f3f4f6;
}

.po-money-field input {
  border-radius: 0 7px 7px 0;
}

.po-field-error {
  color: #dc2626;
  font-size: 0.78rem;
}

.po-ledger-form-actions {
  margin-top: 1rem;
  display: flex;
  justify-content: flex-end;
  gap: 0.7rem;
}

.po-immutable-badge {
  padding: 0.38rem 0.65rem;
  border-radius: 999px;
  background: #eef2ff;
  color: #4338ca;
  display: inline-flex;
  align-items: center;
  gap: 0.35rem;
  font-size: 0.76rem;
  font-weight: 700;
}

.po-grn-badge {
  background: #ecfdf5;
  color: #047857;
}

.po-ledger-empty-state {
  min-height: 160px;
  padding: 1.5rem;
  display: flex;
  flex-direction: column;
  align-items: center;
  justify-content: center;
  gap: 0.4rem;
  text-align: center;
  color: #6b7280;
}

.po-ledger-empty-state i {
  font-size: 1.7rem;
}

.po-payment-history-amount,
.po-quantity-received,
.po-payment-status-paid {
  color: #15803d;
}

.po-quantity-pending,
.po-payment-status-due {
  color: #c2410c;
}

.po-grn-list {
  display: grid;
  gap: 1rem;
}

.po-grn-card {
  border: 1px solid #e5e7eb;
  border-radius: 10px;
  overflow: hidden;
}

.po-grn-card-header {
  padding: 0.9rem 1rem;
  background: #f8fafc;
  display: flex;
  justify-content: space-between;
  align-items: flex-start;
  gap: 1rem;
}

.po-grn-card-header > div:first-child {
  display: grid;
  gap: 0.15rem;
}

.po-grn-card-header span,
.po-grn-meta {
  color: #6b7280;
  font-size: 0.82rem;
}

.po-grn-meta {
  display: grid;
  gap: 0.2rem;
  text-align: right;
}

.po-grn-note {
  padding: 0.75rem 1rem;
  border-top: 1px solid #e5e7eb;
  color: #6b7280;
  display: flex;
  gap: 0.5rem;
}

@media (max-width: 767.98px) {
  .po-details-header-actions {
    width: 100%;
    justify-content: flex-start;
  }

  .po-ledger-form-grid {
    grid-template-columns: 1fr;
  }

  .po-ledger-form-full {
    grid-column: auto;
  }

  .po-grn-card-header {
    flex-direction: column;
  }

  .po-grn-meta {
    text-align: left;
  }
}
</style>
