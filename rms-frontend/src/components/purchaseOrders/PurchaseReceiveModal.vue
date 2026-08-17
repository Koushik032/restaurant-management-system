<template>
  <Teleport to="body">
    <Transition name="purchase-receive-modal">
      <div
        v-if="show"
        class="purchase-receive-backdrop"
        @click.self="closeModal"
      >
        <section
          class="purchase-receive-modal"
          role="dialog"
          aria-modal="true"
          aria-labelledby="purchase-receive-title"
        >
          <!-- Header -->

          <header class="purchase-receive-header">
            <div class="purchase-receive-heading">
              <div class="purchase-receive-heading-icon">
                <i class="bi bi-box-arrow-in-down"></i>
              </div>

              <div>
                <h2 id="purchase-receive-title">
                  Receive Purchase Order
                </h2>

                <p>
                  Receive all or part of the remaining
                  purchase quantities.
                </p>
              </div>
            </div>

            <button
              type="button"
              class="purchase-receive-close"
              aria-label="Close modal"
              :disabled="submitting"
              @click="closeModal"
            >
              <i class="bi bi-x-lg"></i>
            </button>
          </header>

          <!-- Purchase Information -->

          <section class="purchase-receive-summary">
            <article>
              <span>
                Purchase Order
              </span>

              <strong>
                #{{ purchaseOrderId }}
              </strong>
            </article>

            <article>
              <span>
                Supplier
              </span>

              <strong>
                {{ supplierName }}
              </strong>
            </article>

            <article>
              <span>
                Current Status
              </span>

              <strong
                class="purchase-receive-status"
                :class="
                  statusClass(
                    purchaseStatus,
                  )
                "
              >
                {{ purchaseStatusLabel }}
              </strong>
            </article>

            <article>
              <span>
                Delivery Date
              </span>

              <strong>
                {{
                  formatDate(
                    purchaseOrder
                      ?.delivery_date,
                  )
                }}
              </strong>
            </article>
          </section>

          <form
            novalidate
            @submit.prevent="submitReceive"
          >
            <div class="purchase-receive-body">
              <!-- API Error -->

              <div
                v-if="errorMessage"
                class="purchase-receive-error"
                role="alert"
              >
                <i class="bi bi-exclamation-circle-fill"></i>

                <span>
                  {{ errorMessage }}
                </span>
              </div>

              <!-- Purchase Validation Error -->

              <div
                v-if="purchaseServerError"
                class="purchase-receive-error"
                role="alert"
              >
                <i class="bi bi-shield-exclamation"></i>

                <span>
                  {{ purchaseServerError }}
                </span>
              </div>

              <!-- Toolbar -->

              <section class="purchase-receive-toolbar">
                <div>
                  <h3>
                    Purchase Items
                  </h3>

                  <p>
                    Select the items received in this
                    delivery.
                  </p>
                </div>

                <div class="purchase-receive-toolbar-actions">
                  <button
                    type="button"
                    class="purchase-receive-select-all"
                    :disabled="
                      submitting
                      ||
                      receivableItems.length === 0
                    "
                    @click="selectAllRemaining"
                  >
                    <i class="bi bi-check2-all"></i>

                    Receive All Remaining
                  </button>

                  <button
                    type="button"
                    class="purchase-receive-clear-all"
                    :disabled="
                      submitting
                      ||
                      selectedItems.length === 0
                    "
                    @click="clearSelection"
                  >
                    <i class="bi bi-arrow-counterclockwise"></i>

                    Clear
                  </button>
                </div>
              </section>

              <!-- Empty State -->

              <div
                v-if="formItems.length === 0"
                class="purchase-receive-empty"
              >
                <i class="bi bi-inbox"></i>

                <h3>
                  No purchase items found
                </h3>

                <p>
                  This purchase order does not contain
                  any items.
                </p>
              </div>

              <!-- Purchase Items -->

              <section
                v-else
                class="purchase-receive-items"
              >
                <article
                  v-for="item in formItems"
                  :key="item.id"
                  class="purchase-receive-item"
                  :class="{
                    'purchase-receive-item-selected':
                      item.selected,

                    'purchase-receive-item-complete':
                      item.remainingQuantity <= 0,

                    'purchase-receive-item-disabled':
                      !item.canReceive,
                  }"
                >
                  <!-- Item Header -->

                  <header class="purchase-receive-item-header">
                    <label
                      class="purchase-receive-item-selector"
                      :for="
                        `receive-item-${item.id}`
                      "
                    >
                      <input
                        :id="
                          `receive-item-${item.id}`
                        "
                        v-model="item.selected"
                        type="checkbox"
                        :disabled="
                          submitting
                          ||
                          !item.canReceive
                        "
                        @change="
                          handleItemSelection(
                            item,
                          )
                        "
                      />

                      <span class="purchase-receive-checkbox">
                        <i class="bi bi-check-lg"></i>
                      </span>

                      <span class="purchase-receive-item-name">
                        <strong>
                          {{ item.itemName }}
                        </strong>

                        <small>
                          {{ item.rawMaterialName }}
                          ·
                          {{ item.unit }}
                        </small>
                      </span>
                    </label>

                    <span
                      class="purchase-receive-item-state"
                      :class="
                        itemStateClass(
                          item,
                        )
                      "
                    >
                      {{
                        itemStateLabel(
                          item,
                        )
                      }}
                    </span>
                  </header>

                  <!-- Progress Bar -->

                  <div class="purchase-receive-progress-section">
                    <div class="purchase-receive-progress-label">
                      <span>
                        Receive Progress
                      </span>

                      <strong>
                        {{ item.progressPercentage }}%
                      </strong>
                    </div>

                    <div class="purchase-receive-progress">
                      <span
                        :style="{
                          width:
                            `${item.progressPercentage}%`,
                        }"
                      ></span>
                    </div>
                  </div>

                  <!-- Quantity Summary -->

                  <div class="purchase-receive-quantity-summary">
                    <div>
                      <span>
                        Ordered
                      </span>

                      <strong>
                        {{
                          formatQuantity(
                            item.orderedQuantity,
                            item.unit,
                          )
                        }}
                      </strong>
                    </div>

                    <div>
                      <span>
                        Already Received
                      </span>

                      <strong class="purchase-receive-already-value">
                        {{
                          formatQuantity(
                            item.receivedQuantity,
                            item.unit,
                          )
                        }}
                      </strong>
                    </div>

                    <div>
                      <span>
                        Remaining
                      </span>

                      <strong class="purchase-receive-remaining-value">
                        {{
                          formatQuantity(
                            item.remainingQuantity,
                            item.unit,
                          )
                        }}
                      </strong>
                    </div>

                    <div>
                      <span>
                        After Receive
                      </span>

                      <strong class="purchase-receive-after-value">
                        {{
                          formatQuantity(
                            calculateAfterReceive(
                              item,
                            ),
                            item.unit,
                          )
                        }}
                      </strong>
                    </div>
                  </div>

                  <!-- Mapping Warning -->

                  <div
                    v-if="!item.rawMaterialId"
                    class="purchase-receive-item-warning"
                  >
                    <i class="bi bi-link-45deg"></i>

                    <span>
                      This item is not connected to a
                      raw material.
                    </span>
                  </div>

                  <!-- Fully Received -->

                  <div
                    v-else-if="
                      item.remainingQuantity <= 0
                    "
                    class="purchase-receive-complete-notice"
                  >
                    <i class="bi bi-check-circle-fill"></i>

                    <span>
                      This item has already been fully
                      received.
                    </span>
                  </div>

                  <!-- Receive Inputs -->

                  <div
                    v-else
                    class="purchase-receive-item-fields"
                  >
                    <!-- Receive Quantity -->

                    <div class="purchase-receive-form-group">
                      <label
                        :for="
                          `receive-quantity-${item.id}`
                        "
                      >
                        Receive Quantity

                        <span v-if="item.selected">
                          *
                        </span>
                      </label>

                      <div class="purchase-receive-number-field">
                        <input
                          :id="
                            `receive-quantity-${item.id}`
                          "
                          v-model.number="item.receiveQuantity"
                          type="number"
                          min="0"
                          :max="item.remainingQuantity"
                          step="0.01"
                          placeholder="0"
                          :disabled="
                            submitting
                            ||
                            !item.selected
                          "
                          :class="{
                            'purchase-receive-input-error':
                              itemError(
                                item,
                                'receive_quantity',
                              ),
                          }"
                          @input="
                            handleQuantityInput(
                              item,
                            )
                          "
                        />

                        <span>
                          {{ item.unit }}
                        </span>
                      </div>

                      <small class="purchase-receive-field-help">
                        Maximum:
                        {{
                          formatQuantity(
                            item.remainingQuantity,
                            item.unit,
                          )
                        }}
                      </small>

                      <small
                        v-if="
                          itemError(
                            item,
                            'receive_quantity',
                          )
                        "
                        class="purchase-receive-field-error"
                      >
                        {{
                          itemError(
                            item,
                            'receive_quantity',
                          )
                        }}
                      </small>
                    </div>

                    <!-- Unit Cost -->

                    <div class="purchase-receive-form-group">
                      <label
                        :for="
                          `receive-cost-${item.id}`
                        "
                      >
                        Unit Cost
                      </label>

                      <div class="purchase-receive-money-field">
                        <span>
                          ৳
                        </span>

                        <input
                          :id="
                            `receive-cost-${item.id}`
                          "
                          v-model.number="item.unitCost"
                          type="number"
                          min="0"
                          step="0.0001"
                          placeholder="0.00"
                          :disabled="
                            submitting
                            ||
                            !item.selected
                          "
                          :class="{
                            'purchase-receive-input-error':
                              itemError(
                                item,
                                'unit_cost',
                              ),
                          }"
                        />
                      </div>

                      <small class="purchase-receive-field-help">
                        Used for average warehouse cost.
                      </small>

                      <small
                        v-if="
                          itemError(
                            item,
                            'unit_cost',
                          )
                        "
                        class="purchase-receive-field-error"
                      >
                        {{
                          itemError(
                            item,
                            'unit_cost',
                          )
                        }}
                      </small>
                    </div>

                    <!-- Receive Value -->

                    <div class="purchase-receive-form-group">
                      <label>
                        Receive Value
                      </label>

                      <div class="purchase-receive-readonly-field">
                        {{
                          formatMoney(
                            itemReceiveValue(
                              item,
                            ),
                          )
                        }}
                      </div>

                      <small class="purchase-receive-field-help">
                        Quantity × Unit cost
                      </small>
                    </div>

                    <!-- Item Notes -->

                    <div class="purchase-receive-form-group purchase-receive-item-note">
                      <label
                        :for="
                          `receive-note-${item.id}`
                        "
                      >
                        Item Note
                      </label>

                      <textarea
                        :id="
                          `receive-note-${item.id}`
                        "
                        v-model.trim="item.notes"
                        rows="2"
                        maxlength="1000"
                        placeholder="Condition or delivery note"
                        :disabled="
                          submitting
                          ||
                          !item.selected
                        "
                        :class="{
                          'purchase-receive-input-error':
                            itemError(
                              item,
                              'notes',
                            ),
                        }"
                      ></textarea>

                      <small
                        v-if="
                          itemError(
                            item,
                            'notes',
                          )
                        "
                        class="purchase-receive-field-error"
                      >
                        {{
                          itemError(
                            item,
                            'notes',
                          )
                        }}
                      </small>
                    </div>
                  </div>
                </article>
              </section>

              <!-- Receive Calculation -->

              <section class="purchase-receive-calculation">
                <article>
                  <span>
                    Selected Items
                  </span>

                  <strong>
                    {{ selectedItems.length }}
                  </strong>
                </article>

                <article>
                  <span>
                    Receivable Items
                  </span>

                  <strong>
                    {{ receivableItems.length }}
                  </strong>
                </article>

                <article>
                  <span>
                    Receive Value
                  </span>

                  <strong>
                    {{
                      formatMoney(
                        totalReceiveValue,
                      )
                    }}
                  </strong>
                </article>

                <article>
                  <span>
                    Status After Receive
                  </span>

                  <strong
                    class="purchase-receive-status-preview"
                    :class="{
                      'purchase-receive-preview-complete':
                        willFullyReceive,

                      'purchase-receive-preview-partial':
                        !willFullyReceive
                        &&
                        selectedItems.length > 0,
                    }"
                  >
                    {{
                      selectedItems.length === 0
                        ? 'No Change'
                        : willFullyReceive
                          ? 'Received'
                          : 'Partially Received'
                    }}
                  </strong>
                </article>
              </section>

              <!-- Supplier Payment During Receive -->

              <section class="purchase-receive-payment-card">
                <div class="purchase-receive-payment-heading">
                  <div>
                    <div class="purchase-receive-payment-title">
                      <i class="bi bi-cash-coin"></i>

                      <span>
                        Supplier Payment
                      </span>
                    </div>

                    <p>
                      Optional. Record a partial or full supplier payment
                      together with this receive.
                    </p>
                  </div>

                  <label
                    v-if="currentDueAmount > 0"
                    class="purchase-receive-payment-toggle"
                  >
                    <input
                      v-model="includePayment"
                      type="checkbox"
                      :disabled="submitting"
                    />

                    <span>
                      Pay supplier now
                    </span>
                  </label>
                </div>

                <div class="purchase-receive-payment-summary">
                  <article>
                    <span>
                      Order Total
                    </span>

                    <strong>
                      {{ formatMoney(currentTotalAmount) }}
                    </strong>
                  </article>

                  <article>
                    <span>
                      Already Paid
                    </span>

                    <strong class="purchase-receive-paid-value">
                      {{ formatMoney(currentPaidAmount) }}
                    </strong>
                  </article>

                  <article>
                    <span>
                      Current Due
                    </span>

                    <strong class="purchase-receive-due-value">
                      {{ formatMoney(currentDueAmount) }}
                    </strong>
                  </article>
                </div>

                <div
                  v-if="currentDueAmount <= 0"
                  class="purchase-receive-payment-complete"
                >
                  <i class="bi bi-check-circle-fill"></i>

                  <span>
                    This purchase order is already fully paid.
                  </span>
                </div>

                <div
                  v-else-if="includePayment"
                  class="purchase-receive-payment-fields"
                >
                  <div class="purchase-receive-form-group">
                    <label for="receive-payment-amount">
                      Payment Amount
                      <span>*</span>
                    </label>

                    <div class="purchase-receive-money-field">
                      <span>৳</span>

                      <input
                        id="receive-payment-amount"
                        v-model.number="paymentForm.amount"
                        type="number"
                        min="0.01"
                        :max="currentDueAmount"
                        step="0.01"
                        placeholder="0.00"
                        :disabled="submitting"
                        :class="{
                          'purchase-receive-input-error':
                            paymentError('amount'),
                        }"
                      />
                    </div>

                    <small class="purchase-receive-field-help">
                      Maximum payable now:
                      {{ formatMoney(currentDueAmount) }}
                    </small>

                    <small
                      v-if="paymentError('amount')"
                      class="purchase-receive-field-error"
                    >
                      {{ paymentError('amount') }}
                    </small>
                  </div>

                  <div class="purchase-receive-form-group">
                    <label for="receive-payment-method">
                      Payment Method
                      <span>*</span>
                    </label>

                    <select
                      id="receive-payment-method"
                      v-model="paymentForm.payment_method"
                      :disabled="submitting"
                      :class="{
                        'purchase-receive-input-error':
                          paymentError('payment_method'),
                      }"
                    >
                      <option value="">
                        Select payment method
                      </option>

                      <option
                        v-for="method in paymentMethods"
                        :key="method.value"
                        :value="method.value"
                      >
                        {{ method.label }}
                      </option>
                    </select>

                    <small
                      v-if="paymentError('payment_method')"
                      class="purchase-receive-field-error"
                    >
                      {{ paymentError('payment_method') }}
                    </small>
                  </div>

                  <div class="purchase-receive-form-group">
                    <label for="receive-payment-date">
                      Payment Date
                    </label>

                    <input
                      id="receive-payment-date"
                      v-model="paymentForm.payment_date"
                      type="date"
                      :disabled="submitting"
                      :class="{
                        'purchase-receive-input-error':
                          paymentError('payment_date'),
                      }"
                    />

                    <small
                      v-if="paymentError('payment_date')"
                      class="purchase-receive-field-error"
                    >
                      {{ paymentError('payment_date') }}
                    </small>
                  </div>

                  <div class="purchase-receive-form-group">
                    <label for="receive-payment-reference">
                      Transaction Reference
                    </label>

                    <input
                      id="receive-payment-reference"
                      v-model.trim="paymentForm.transaction_reference"
                      type="text"
                      maxlength="255"
                      placeholder="Txn / cheque / bank reference"
                      :disabled="submitting"
                      :class="{
                        'purchase-receive-input-error':
                          paymentError('transaction_reference'),
                      }"
                    />

                    <small
                      v-if="paymentError('transaction_reference')"
                      class="purchase-receive-field-error"
                    >
                      {{ paymentError('transaction_reference') }}
                    </small>
                  </div>

                  <div class="purchase-receive-form-group purchase-receive-payment-note">
                    <label for="receive-payment-notes">
                      Payment Note
                    </label>

                    <textarea
                      id="receive-payment-notes"
                      v-model.trim="paymentForm.notes"
                      rows="2"
                      maxlength="2000"
                      placeholder="Optional note about this supplier payment"
                      :disabled="submitting"
                      :class="{
                        'purchase-receive-input-error':
                          paymentError('notes'),
                      }"
                    ></textarea>

                    <small
                      v-if="paymentError('notes')"
                      class="purchase-receive-field-error"
                    >
                      {{ paymentError('notes') }}
                    </small>
                  </div>

                  <div class="purchase-receive-payment-preview">
                    <article>
                      <span>
                        Paid After Payment
                      </span>

                      <strong>
                        {{ formatMoney(paidAfterPayment) }}
                      </strong>
                    </article>

                    <article>
                      <span>
                        Due After Payment
                      </span>

                      <strong>
                        {{ formatMoney(dueAfterPayment) }}
                      </strong>
                    </article>
                  </div>
                </div>
              </section>

              <!-- General Notes -->

              <div class="purchase-receive-general-notes">
                <label for="purchase-receive-notes">
                  Receive Notes
                </label>

                <textarea
                  id="purchase-receive-notes"
                  v-model.trim="generalNotes"
                  rows="3"
                  maxlength="2000"
                  placeholder="General note about this delivery"
                  :disabled="submitting"
                  :class="{
                    'purchase-receive-input-error':
                      generalNotesError,
                  }"
                ></textarea>

                <div class="purchase-receive-notes-meta">
                  <small
                    v-if="generalNotesError"
                    class="purchase-receive-field-error"
                  >
                    {{ generalNotesError }}
                  </small>

                  <small v-else>
                    A stock movement will be created
                    for every received item.
                  </small>

                  <small>
                    {{ generalNotes.length }}/2000
                  </small>
                </div>
              </div>
            </div>

            <!-- Footer -->

            <footer class="purchase-receive-footer">
              <button
                type="button"
                class="purchase-receive-cancel"
                :disabled="submitting"
                @click="closeModal"
              >
                Cancel
              </button>

              <button
                type="submit"
                class="purchase-receive-submit"
                :disabled="
                  submitting
                  ||
                  selectedItems.length === 0
                "
              >
                <span
                  v-if="submitting"
                  class="spinner-border spinner-border-sm"
                  aria-hidden="true"
                ></span>

                <i
                  v-else
                  class="bi bi-box-arrow-in-down"
                ></i>

                <span>
                  {{
                    submitting
                      ? 'Receiving Purchase...'
                      : willFullyReceive
                        ? 'Receive Full Order'
                        : 'Receive Selected Items'
                  }}
                </span>
              </button>
            </footer>
          </form>
        </section>
      </div>
    </Transition>
  </Teleport>
</template>

<script setup>
import {
  computed,
  onBeforeUnmount,
  reactive,
  ref,
  watch,
} from 'vue'

const props = defineProps({
  show: {
    type: Boolean,
    default: false,
  },

  purchaseOrder: {
    type: Object,
    default: null,
  },

  submitting: {
    type: Boolean,
    default: false,
  },

  errorMessage: {
    type: String,
    default: '',
  },

  serverErrors: {
    type: Object,
    default: () => ({}),
  },
})

const emit = defineEmits([
  'close',
  'submit',
])

const formItems =
  ref([])

const generalNotes =
  ref('')

const includePayment =
  ref(false)

const paymentForm = reactive({
  amount: '',
  payment_method: '',
  payment_date: todayDate(),
  transaction_reference: '',
  notes: '',
})

const paymentMethods = [
  {
    value: 'cash',
    label: 'Cash',
  },
  {
    value: 'card',
    label: 'Card',
  },
  {
    value: 'bkash',
    label: 'bKash',
  },
  {
    value: 'nagad',
    label: 'Nagad',
  },
  {
    value: 'bank_transfer',
    label: 'Bank Transfer',
  },
  {
    value: 'other',
    label: 'Other',
  },
]

const localErrors =
  reactive({})

/*
|--------------------------------------------------------------------------
| Purchase Information
|--------------------------------------------------------------------------
*/

const purchaseOrderId = computed(() => {
  return (
    props.purchaseOrder?.id
    ??
    props.purchaseOrder
      ?.purchase_order_id
    ??
    '—'
  )
})

const supplierName = computed(() => {
  return (
    props.purchaseOrder
      ?.supplier
      ?.company_name
    ??
    props.purchaseOrder
      ?.supplier
      ?.supplier_name
    ??
    props.purchaseOrder
      ?.supplier
      ?.name
    ??
    props.purchaseOrder
      ?.supplier_name
    ??
    'Supplier unavailable'
  )
})

const purchaseStatus = computed(() => {
  const status =
    props.purchaseOrder
      ?.status
      ?.value
    ??
    props.purchaseOrder
      ?.status
    ??
    ''

  return String(status)
    .trim()
    .toLowerCase()
})

const purchaseStatusLabel = computed(() => {
  return (
    props.purchaseOrder
      ?.status_label
    ??
    formatLabel(
      purchaseStatus.value,
    )
  )
})

/*
|--------------------------------------------------------------------------
| Form Calculations
|--------------------------------------------------------------------------
*/

const receivableItems = computed(() => {
  return formItems.value.filter(
    (item) =>
      item.canReceive
      &&
      item.remainingQuantity > 0,
  )
})

const selectedItems = computed(() => {
  return formItems.value.filter(
    (item) =>
      item.selected
      &&
      item.canReceive
      &&
      Number(
        item.receiveQuantity,
      ) > 0,
  )
})

const totalReceiveValue = computed(() => {
  return selectedItems.value.reduce(
    (
      total,
      item,
    ) => {
      return (
        total
        +
        itemReceiveValue(
          item,
        )
      )
    },
    0,
  )
})

const willFullyReceive = computed(() => {
  if (
    selectedItems.value.length === 0
    ||
    receivableItems.value.length === 0
  ) {
    return false
  }

  return receivableItems.value.every(
    (item) => {
      if (!item.selected) {
        return false
      }

      return (
        roundQuantity(
          item.receiveQuantity,
        )
        >=
        roundQuantity(
          item.remainingQuantity,
        )
      )
    },
  )
})

const currentTotalAmount = computed(() => {
  return roundMoney(
    Number(
      props.purchaseOrder?.total_amount
      ?? 0,
    ),
  )
})

const currentPaidAmount = computed(() => {
  return roundMoney(
    Number(
      props.purchaseOrder?.paid_amount
      ?? 0,
    ),
  )
})

const currentDueAmount = computed(() => {
  const suppliedDue =
    props.purchaseOrder?.due_amount

  if (
    suppliedDue !== undefined
    &&
    suppliedDue !== null
  ) {
    return Math.max(
      0,
      roundMoney(
        Number(suppliedDue || 0),
      ),
    )
  }

  return Math.max(
    0,
    roundMoney(
      currentTotalAmount.value
      -
      currentPaidAmount.value,
    ),
  )
})

const paymentAmountNumber = computed(() => {
  if (!includePayment.value) {
    return 0
  }

  const amount =
    Number(paymentForm.amount)

  return Number.isFinite(amount)
    ? roundMoney(amount)
    : 0
})

const paidAfterPayment = computed(() => {
  return Math.min(
    currentTotalAmount.value,
    roundMoney(
      currentPaidAmount.value
      +
      paymentAmountNumber.value,
    ),
  )
})

const dueAfterPayment = computed(() => {
  return Math.max(
    0,
    roundMoney(
      currentTotalAmount.value
      -
      paidAfterPayment.value,
    ),
  )
})

const purchaseServerError = computed(() => {
  return (
    firstError(
      props.serverErrors
        ?.purchase_order,
    )
    ||
    firstError(
      props.serverErrors
        ?.items,
    )
    ||
    ''
  )
})

const generalNotesError = computed(() => {
  return (
    localErrors.notes
    ||
    firstError(
      props.serverErrors
        ?.notes,
    )
    ||
    ''
  )
})

/*
|--------------------------------------------------------------------------
| Initialize Form
|--------------------------------------------------------------------------
*/

function initializeForm() {
  clearLocalErrors()

  generalNotes.value = ''

  includePayment.value = false

  paymentForm.amount = ''
  paymentForm.payment_method = ''
  paymentForm.payment_date = todayDate()
  paymentForm.transaction_reference = ''
  paymentForm.notes = ''

  const purchaseItems =
    props.purchaseOrder
      ?.items
      ?.data
    ??
    props.purchaseOrder
      ?.items
    ??
    []

  if (
    !Array.isArray(
      purchaseItems,
    )
  ) {
    formItems.value = []

    return
  }

  formItems.value =
    purchaseItems.map(
      normalizeItem,
    )
}

function normalizeItem(sourceItem) {
  const orderedQuantity =
    roundQuantity(
      sourceItem?.quantity
      ??
      sourceItem
        ?.ordered_quantity
      ??
      0,
    )

  const receivedQuantity =
    roundQuantity(
      sourceItem
        ?.received_quantity
      ??
      0,
    )

  const remainingQuantity =
    roundQuantity(
      sourceItem
        ?.remaining_quantity
      ??
      Math.max(
        0,
        orderedQuantity
        -
        receivedQuantity,
      ),
    )

  const rawMaterialId =
    sourceItem
      ?.raw_material_id
    ??
    sourceItem
      ?.raw_material
      ?.id
    ??
    null

  const canReceive =
    sourceItem?.can_receive
      !== false
    &&
    Boolean(rawMaterialId)
    &&
    remainingQuantity > 0

  const progressPercentage =
    orderedQuantity > 0
      ? Math.min(
          100,
          Math.max(
            0,
            Math.round(
              (
                receivedQuantity
                /
                orderedQuantity
              )
              *
              10000,
            )
            /
            100,
          ),
        )
      : 0

  return {
    id:
      sourceItem?.id,

    purchaseOrderItemId:
      sourceItem?.id,

    rawMaterialId,

    rawMaterialName:
      sourceItem
        ?.raw_material
        ?.material_name
      ??
      sourceItem
        ?.material_name
      ??
      sourceItem
        ?.item_name
      ??
      'Unknown Material',

    itemName:
      sourceItem?.item_name
      ??
      sourceItem
        ?.raw_material
        ?.material_name
      ??
      'Unknown Item',

    unit:
      sourceItem?.unit
      ??
      sourceItem
        ?.raw_material
        ?.base_unit
      ??
      '',

    orderedQuantity,

    receivedQuantity,

    remainingQuantity,

    progressPercentage,

    selected: false,

    receiveQuantity: 0,

    unitCost:
      Number(
        sourceItem?.unit_price
        ??
        sourceItem?.unit_cost
        ??
        0,
      ),

    notes: '',

    canReceive,

    submissionIndex: null,
  }
}

/*
|--------------------------------------------------------------------------
| Selection
|--------------------------------------------------------------------------
*/

function handleItemSelection(item) {
  clearItemErrors(item)

  if (item.selected) {
    item.receiveQuantity =
      item.remainingQuantity
  } else {
    item.receiveQuantity = 0
    item.submissionIndex = null
  }
}

function handleQuantityInput(item) {
  clearItemErrors(item)

  const quantity =
    Number(
      item.receiveQuantity,
    )

  if (
    Number.isFinite(quantity)
    &&
    quantity > 0
  ) {
    item.selected = true
  }
}

function selectAllRemaining() {
  clearLocalErrors()

  receivableItems.value.forEach(
    (item) => {
      item.selected = true

      item.receiveQuantity =
        item.remainingQuantity
    },
  )
}

function clearSelection() {
  clearLocalErrors()

  formItems.value.forEach(
    (item) => {
      item.selected = false
      item.receiveQuantity = 0
      item.submissionIndex = null
    },
  )
}

/*
|--------------------------------------------------------------------------
| Validation
|--------------------------------------------------------------------------
*/

function validateForm() {
  clearLocalErrors()

  const checkedItems =
    formItems.value.filter(
      (item) =>
        item.selected,
    )

  if (checkedItems.length === 0) {
    localErrors.items =
      'Select at least one item to receive.'

    return false
  }

  checkedItems.forEach(
    (item) => {
      const prefix =
        `item.${item.id}`

      const receiveQuantity =
        Number(
          item.receiveQuantity,
        )

      const unitCost =
        Number(
          item.unitCost,
        )

      if (!item.rawMaterialId) {
        localErrors[
          `${prefix}.purchase_order_item_id`
        ] =
          'This item is not connected to a raw material.'
      }

      if (
        !Number.isFinite(
          receiveQuantity,
        )
        ||
        receiveQuantity <= 0
      ) {
        localErrors[
          `${prefix}.receive_quantity`
        ] =
          'Receive quantity must be greater than zero.'
      } else if (
        roundQuantity(
          receiveQuantity,
        )
        >
        roundQuantity(
          item.remainingQuantity,
        )
      ) {
        localErrors[
          `${prefix}.receive_quantity`
        ] =
          `Only ${formatQuantity(
            item.remainingQuantity,
            item.unit,
          )} remains to receive.`
      }

      if (
        !Number.isFinite(unitCost)
        ||
        unitCost < 0
      ) {
        localErrors[
          `${prefix}.unit_cost`
        ] =
          'Unit cost cannot be negative.'
      }

      if (
        String(
          item.notes || '',
        ).length > 1000
      ) {
        localErrors[
          `${prefix}.notes`
        ] =
          'Item note cannot exceed 1000 characters.'
      }
    },
  )

  if (
    generalNotes.value.length >
      2000
  ) {
    localErrors.notes =
      'Receive notes cannot exceed 2000 characters.'
  }

  if (includePayment.value) {
    const paymentAmount =
      Number(paymentForm.amount)

    if (currentDueAmount.value <= 0) {
      localErrors['payment.amount'] =
        'This purchase order is already fully paid.'
    } else if (
      !Number.isFinite(paymentAmount)
      ||
      paymentAmount <= 0
    ) {
      localErrors['payment.amount'] =
        'Payment amount must be greater than zero.'
    } else if (
      roundMoney(paymentAmount)
      >
      currentDueAmount.value
    ) {
      localErrors['payment.amount'] =
        `Payment amount cannot exceed the current due amount of ${formatMoney(currentDueAmount.value)}.`
    }

    if (!paymentForm.payment_method) {
      localErrors['payment.payment_method'] =
        'Please select a payment method.'
    }

    if (
      paymentForm.transaction_reference.length
      > 255
    ) {
      localErrors['payment.transaction_reference'] =
        'Transaction reference cannot exceed 255 characters.'
    }

    if (
      paymentForm.notes.length
      > 2000
    ) {
      localErrors['payment.notes'] =
        'Payment note cannot exceed 2000 characters.'
    }
  }

  return (
    Object.keys(
      localErrors,
    ).length === 0
  )
}

/*
|--------------------------------------------------------------------------
| Submit
|--------------------------------------------------------------------------
*/

function buildPayload() {
  const items = []

  formItems.value.forEach(
    (item) => {
      item.submissionIndex =
        null
    },
  )

  selectedItems.value.forEach(
    (
      item,
      index,
    ) => {
      item.submissionIndex =
        index

      items.push({
        purchase_order_item_id:
          Number(
            item.purchaseOrderItemId,
          ),

        receive_quantity:
          roundQuantity(
            item.receiveQuantity,
          ),

        unit_cost:
          Number.isFinite(
            Number(item.unitCost),
          )
            ? Number(item.unitCost)
            : null,

        notes:
          String(
            item.notes || '',
          ).trim()
          ||
          null,
      })
    },
  )

  const payload = {
    items,

    notes:
      generalNotes.value.trim()
      ||
      null,
  }

  if (includePayment.value) {
    payload.payment = {
      amount:
        roundMoney(
          paymentForm.amount,
        ),

      payment_method:
        paymentForm.payment_method,

      payment_date:
        paymentForm.payment_date
        ||
        null,

      transaction_reference:
        paymentForm.transaction_reference.trim()
        ||
        null,

      notes:
        paymentForm.notes.trim()
        ||
        null,
    }
  }

  return payload
}

function submitReceive() {
  if (
    props.submitting
    ||
    !validateForm()
  ) {
    return
  }

  emit(
    'submit',
    buildPayload(),
  )
}

/*
|--------------------------------------------------------------------------
| Error Helpers
|--------------------------------------------------------------------------
*/

function itemError(
  item,
  field,
) {
  const localErrorKey =
    `item.${item.id}.${field}`

  if (
    localErrors[
      localErrorKey
    ]
  ) {
    return localErrors[
      localErrorKey
    ]
  }

  if (
    item.submissionIndex ===
      null
    ||
    item.submissionIndex ===
      undefined
  ) {
    return ''
  }

  const serverErrorKey =
    `items.${item.submissionIndex}.${field}`

  return firstError(
    props.serverErrors
      ?.[serverErrorKey],
  )
}

function paymentError(field) {
  const localKey =
    `payment.${field}`

  if (localErrors[localKey]) {
    return localErrors[localKey]
  }

  return firstError(
    props.serverErrors
      ?.[localKey],
  )
}

function clearLocalErrors() {
  Object
    .keys(
      localErrors,
    )
    .forEach(
      (key) => {
        delete localErrors[key]
      },
    )
}

function clearItemErrors(item) {
  const itemPrefix =
    `item.${item.id}.`

  Object
    .keys(
      localErrors,
    )
    .forEach(
      (key) => {
        if (
          key.startsWith(
            itemPrefix,
          )
        ) {
          delete localErrors[key]
        }
      },
    )
}

function firstError(value) {
  if (Array.isArray(value)) {
    return value[0] || ''
  }

  return (
    typeof value === 'string'
      ? value
      : ''
  )
}

/*
|--------------------------------------------------------------------------
| Display Helpers
|--------------------------------------------------------------------------
*/

function calculateAfterReceive(item) {
  const receiveQuantity =
    item.selected
      ? Number(
          item.receiveQuantity,
        ) || 0
      : 0

  return roundQuantity(
    item.receivedQuantity
    +
    receiveQuantity,
  )
}

function itemReceiveValue(item) {
  if (!item.selected) {
    return 0
  }

  return roundMoney(
    (
      Number(
        item.receiveQuantity,
      ) || 0
    )
    *
    (
      Number(
        item.unitCost,
      ) || 0
    ),
  )
}

function itemStateLabel(item) {
  if (!item.rawMaterialId) {
    return 'Not Mapped'
  }

  if (
    item.remainingQuantity <= 0
  ) {
    return 'Fully Received'
  }

  if (
    item.receivedQuantity > 0
  ) {
    return 'Partially Received'
  }

  return 'Pending'
}

function itemStateClass(item) {
  return {
    'purchase-receive-state-unmapped':
      !item.rawMaterialId,

    'purchase-receive-state-complete':
      item.rawMaterialId
      &&
      item.remainingQuantity <= 0,

    'purchase-receive-state-partial':
      item.rawMaterialId
      &&
      item.receivedQuantity > 0
      &&
      item.remainingQuantity > 0,

    'purchase-receive-state-pending':
      item.rawMaterialId
      &&
      item.receivedQuantity <= 0
      &&
      item.remainingQuantity > 0,
  }
}

function statusClass(status) {
  return {
    'purchase-status-ordered':
      status === 'ordered',

    'purchase-status-partial':
      status ===
        'partially_received',

    'purchase-status-received':
      status === 'received',

    'purchase-status-cancelled':
      status === 'cancelled',
  }
}

function formatLabel(value) {
  if (!value) {
    return 'Unknown'
  }

  return String(value)
    .replaceAll(
      '_',
      ' ',
    )
    .replace(
      /\b\w/g,
      (letter) =>
        letter.toUpperCase(),
    )
}

function formatQuantity(
  quantity,
  unit = '',
) {
  return `${Number(
    quantity || 0,
  ).toLocaleString(
    'en-BD',
    {
      maximumFractionDigits: 4,
    },
  )} ${unit || ''}`.trim()
}

function formatMoney(amount) {
  return `৳ ${Number(
    amount || 0,
  ).toLocaleString(
    'en-BD',
    {
      minimumFractionDigits: 2,
      maximumFractionDigits: 2,
    },
  )}`
}

function todayDate() {
  const date = new Date()

  const localDate = new Date(
    date.getTime()
    -
    date.getTimezoneOffset()
      * 60000,
  )

  return localDate
    .toISOString()
    .slice(0, 10)
}

function formatDate(value) {
  if (!value) {
    return 'Not specified'
  }

  const date =
    new Date(value)

  if (
    Number.isNaN(
      date.getTime(),
    )
  ) {
    return String(value)
  }

  return date.toLocaleDateString(
    'en-BD',
    {
      dateStyle: 'medium',
    },
  )
}

function roundQuantity(value) {
  return Math.round(
    (
      Number(value || 0)
      +
      Number.EPSILON
    )
    *
    10000,
  ) / 10000
}

function roundMoney(value) {
  return Math.round(
    (
      Number(value || 0)
      +
      Number.EPSILON
    )
    *
    100,
  ) / 100
}

/*
|--------------------------------------------------------------------------
| Close Modal
|--------------------------------------------------------------------------
*/

function closeModal() {
  if (props.submitting) {
    return
  }

  emit('close')
}

/*
|--------------------------------------------------------------------------
| Watch Modal
|--------------------------------------------------------------------------
*/

watch(
  () => [
    props.show,
    props.purchaseOrder,
  ],

  ([modalVisible]) => {
    if (modalVisible) {
      initializeForm()

      document.body.style
        .overflow = 'hidden'
    } else {
      document.body.style
        .overflow = ''
    }
  },

  {
    immediate: true,
    deep: true,
  },
)

watch(
  currentDueAmount,
  (due) => {
    if (due <= 0) {
      includePayment.value = false
      paymentForm.amount = ''
      paymentForm.payment_method = ''
    }
  },
)

onBeforeUnmount(() => {
  document.body.style
    .overflow = ''
})
</script>

<style scoped>
.purchase-receive-payment-card {
  margin-top: 1rem;
  padding: 1rem;
  border: 1px solid var(--bs-border-color, #dee2e6);
  border-radius: 0.9rem;
  background: var(--bs-tertiary-bg, #f8f9fa);
}

.purchase-receive-payment-heading {
  display: flex;
  align-items: flex-start;
  justify-content: space-between;
  gap: 1rem;
  flex-wrap: wrap;
}

.purchase-receive-payment-title {
  display: flex;
  align-items: center;
  gap: 0.5rem;
  font-weight: 700;
}

.purchase-receive-payment-heading p {
  margin: 0.3rem 0 0;
  color: var(--bs-secondary-color, #6c757d);
  font-size: 0.85rem;
}

.purchase-receive-payment-toggle {
  display: inline-flex;
  align-items: center;
  gap: 0.55rem;
  cursor: pointer;
  font-weight: 600;
}

.purchase-receive-payment-summary,
.purchase-receive-payment-preview {
  display: grid;
  grid-template-columns: repeat(3, minmax(0, 1fr));
  gap: 0.75rem;
  margin-top: 1rem;
}

.purchase-receive-payment-preview {
  grid-template-columns: repeat(2, minmax(0, 1fr));
  grid-column: 1 / -1;
}

.purchase-receive-payment-summary article,
.purchase-receive-payment-preview article {
  padding: 0.8rem;
  border-radius: 0.7rem;
  background: var(--bs-body-bg, #fff);
  border: 1px solid var(--bs-border-color, #dee2e6);
  display: grid;
  gap: 0.2rem;
}

.purchase-receive-payment-summary span,
.purchase-receive-payment-preview span {
  color: var(--bs-secondary-color, #6c757d);
  font-size: 0.78rem;
}

.purchase-receive-paid-value {
  color: var(--bs-success, #198754);
}

.purchase-receive-due-value {
  color: var(--bs-danger, #dc3545);
}

.purchase-receive-payment-fields {
  display: grid;
  grid-template-columns: repeat(2, minmax(0, 1fr));
  gap: 1rem;
  margin-top: 1rem;
  padding-top: 1rem;
  border-top: 1px solid var(--bs-border-color, #dee2e6);
}

.purchase-receive-payment-fields select,
.purchase-receive-payment-fields input,
.purchase-receive-payment-fields textarea {
  width: 100%;
}

.purchase-receive-payment-note {
  grid-column: 1 / -1;
}

.purchase-receive-payment-complete {
  display: flex;
  align-items: center;
  gap: 0.5rem;
  margin-top: 1rem;
  padding: 0.75rem;
  border-radius: 0.65rem;
  background: rgba(25, 135, 84, 0.1);
  color: var(--bs-success, #198754);
  font-weight: 600;
}

@media (max-width: 767.98px) {
  .purchase-receive-payment-summary,
  .purchase-receive-payment-preview,
  .purchase-receive-payment-fields {
    grid-template-columns: 1fr;
  }

  .purchase-receive-payment-note,
  .purchase-receive-payment-preview {
    grid-column: auto;
  }
}
</style>
