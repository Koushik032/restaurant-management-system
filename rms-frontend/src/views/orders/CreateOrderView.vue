<template>
  <div class="new-order-page">
    <!-- ==============================
         Page Header
    =============================== -->
    <header class="new-order-header">
      <div>
        <h1 class="new-order-title">
          {{ pageTitle }}
        </h1>

        <p class="new-order-subtitle">
          {{ pageSubtitle }}
        </p>
      </div>

      <button
        type="button"
        class="close-order-button"
        aria-label="Close"
        :disabled="isSubmitting"
        @click="goBack"
      >
        <i class="bi bi-x-lg"></i>
      </button>
    </header>

    <!-- ==============================
         Loading
    =============================== -->
    <div
      v-if="isLoading"
      class="order-page-loading"
    >
      <span
        class="spinner-border"
        role="status"
      ></span>

      <p>Loading order information...</p>
    </div>

    <!-- ==============================
         Main Form
    =============================== -->
    <form
      v-else
      class="new-order-form"
      @submit.prevent="submitOrder"
    >
      <!-- General Error -->
      <div
        v-if="generalError"
        class="order-alert order-alert-danger"
      >
        <div class="order-alert-content">
          <i class="bi bi-exclamation-circle"></i>

          <span>{{ generalError }}</span>
        </div>

        <button
          type="button"
          aria-label="Close"
          @click="generalError = ''"
        >
          <i class="bi bi-x"></i>
        </button>
      </div>

      <div class="new-order-layout">
        <!-- ==============================
             Left Content
        =============================== -->
        <main class="new-order-main">
          <!-- ==============================
               Order Information
          =============================== -->
          <section class="order-information-card">
            <div class="order-form-grid">
              <!-- Primary Table -->
              <div class="order-form-group">
                <label
                  for="restaurant_table_id"
                  class="order-form-label required"
                >
                  Table
                </label>

                <select
                  id="restaurant_table_id"
                  v-model="form.restaurant_table_id"
                  class="order-form-control"
                  :class="{
                    invalid:
                      getFieldError(
                        'restaurant_table_id'
                      ),
                  }"
                  :disabled="isSubmitting"
                  @change="handlePrimaryTableChange"
                >
                  <option value="">
                    Select table
                  </option>

                  <option
                    v-for="table in tables"
                    :key="table.id"
                    :value="String(table.id)"
                  >
                    {{ getTableDisplayName(table) }}
                  </option>
                </select>

                <span
                  v-if="
                    getFieldError(
                      'restaurant_table_id'
                    )
                  "
                  class="order-field-error"
                >
                  {{
                    getFieldError(
                      "restaurant_table_id"
                    )
                  }}
                </span>
              </div>

              <!-- Merged Tables -->
              <div
                ref="mergedDropdownRef"
                class="order-form-group merged-dropdown-group"
              >
                <label class="order-form-label">
                  Merged With
                </label>

                <button
                  type="button"
                  class="merged-dropdown-trigger"
                  :class="{
                    active: showMergedDropdown,
                    invalid:
                      getFieldError(
                        'merged_table_ids'
                      ),
                  }"
                  :disabled="
                    isSubmitting ||
                    !form.restaurant_table_id
                  "
                  @click="toggleMergedDropdown"
                >
                  <span
                    :class="{
                      placeholder:
                        !selectedMergedTableNames,
                    }"
                  >
                    {{
                      selectedMergedTableNames ||
                      "Select tables to merge"
                    }}
                  </span>

                  <i
                    class="bi bi-chevron-down"
                    :class="{
                      rotated: showMergedDropdown,
                    }"
                  ></i>
                </button>

                <div
                  v-if="showMergedDropdown"
                  class="merged-dropdown-menu"
                >
                  <div class="merged-dropdown-header">
                    <span>Select tables</span>

                    <button
                      v-if="
                        form.merged_table_ids.length
                      "
                      type="button"
                      @click="clearMergedTables"
                    >
                      Clear
                    </button>
                  </div>

                  <div
                    v-if="
                      availableMergeTables.length
                    "
                    class="merged-dropdown-list"
                  >
                    <label
                      v-for="table in availableMergeTables"
                      :key="table.id"
                      class="merged-dropdown-option"
                      :class="{
                        selected:
                          isMergeTableSelected(
                            table.id
                          ),
                      }"
                    >
                      <input
                        v-model="form.merged_table_ids"
                        type="checkbox"
                        :value="String(table.id)"
                        :disabled="isSubmitting"
                      />

                      <span class="merged-option-check">
                        <i class="bi bi-check"></i>
                      </span>

                      <span class="merged-option-info">
                        <strong>
                          {{ table.table_name }}
                        </strong>

                        <small>
                          {{
                            table.section_label ||
                            table.section ||
                            "Restaurant"
                          }}
                          ·
                          {{ table.capacity || 0 }}
                          seats
                        </small>
                      </span>
                    </label>
                  </div>

                  <div
                    v-else
                    class="merged-dropdown-empty"
                  >
                    No additional tables available
                  </div>
                </div>

                <span
                  v-if="
                    getFieldError(
                      'merged_table_ids'
                    )
                  "
                  class="order-field-error"
                >
                  {{
                    getFieldError(
                      "merged_table_ids"
                    )
                  }}
                </span>
              </div>

              <!-- Customer Name -->
              <div class="order-form-group">
                <label
                  for="customer_name"
                  class="order-form-label"
                >
                  Customer Name
                </label>

                <div class="customer-input-wrapper">
                  <input
                    id="customer_name"
                    v-model.trim="form.customer_name"
                    type="text"
                    class="order-form-control"
                    :class="{
                      invalid:
                        getFieldError(
                          'customer_name'
                        ),
                    }"
                    placeholder="Enter customer name"
                    autocomplete="off"
                    :disabled="
                      isSubmitting ||
                      Boolean(selectedCustomer)
                    "
                    @input="handleCustomerNameInput"
                    @focus="handleCustomerSearchFocus"
                  />

                  <div
                    v-if="isSearchingCustomers"
                    class="customer-input-loader"
                  >
                    <span
                      class="spinner-border spinner-border-sm"
                    ></span>
                  </div>

                  <div
                    v-if="showCustomerResults"
                    class="customer-results-dropdown"
                  >
                    <button
                      v-for="customer in customerResults"
                      :key="customer.id"
                      type="button"
                      class="customer-result-option"
                      @click="selectCustomer(customer)"
                    >
                      <span class="customer-avatar">
                        {{
                          getCustomerInitial(
                            customer
                          )
                        }}
                      </span>

                      <span class="customer-result-details">
                        <strong>
                          {{ customer.name }}
                        </strong>

                        <small>
                          {{
                            customer.phone ||
                            "No phone number"
                          }}
                        </small>
                      </span>
                    </button>

                    <div
                      v-if="
                        !isSearchingCustomers &&
                        !customerResults.length
                      "
                      class="customer-results-empty"
                    >
                      No matching customer found
                    </div>
                  </div>
                </div>

                <span
                  v-if="
                    getFieldError(
                      'customer_name'
                    )
                  "
                  class="order-field-error"
                >
                  {{
                    getFieldError(
                      "customer_name"
                    )
                  }}
                </span>
              </div>

              <!-- Customer Phone -->
              <div class="order-form-group">
                <label
                  for="customer_phone"
                  class="order-form-label"
                >
                  Customer Phone
                </label>

                <input
                  id="customer_phone"
                  v-model.trim="form.customer_phone"
                  type="text"
                  class="order-form-control"
                  :class="{
                    invalid:
                      getFieldError(
                        'customer_phone'
                      ),
                  }"
                  placeholder="Enter customer phone"
                  :disabled="
                    isSubmitting ||
                    Boolean(selectedCustomer)
                  "
                />

                <span
                  v-if="
                    getFieldError(
                      'customer_phone'
                    )
                  "
                  class="order-field-error"
                >
                  {{
                    getFieldError(
                      "customer_phone"
                    )
                  }}
                </span>
              </div>

              <!-- Customer Email -->
              <div class="order-form-group">
                <label
                  for="customer_email"
                  class="order-form-label"
                >
                  Customer Email
                </label>

                <input
                  id="customer_email"
                  v-model.trim="form.customer_email"
                  type="email"
                  class="order-form-control"
                  :class="{
                    invalid:
                      getFieldError(
                        'customer_email'
                      ),
                  }"
                  placeholder="Enter customer email"
                  :disabled="
                    isSubmitting ||
                    Boolean(selectedCustomer)
                  "
                />

                <span
                  v-if="
                    getFieldError(
                      'customer_email'
                    )
                  "
                  class="order-field-error"
                >
                  {{
                    getFieldError(
                      "customer_email"
                    )
                  }}
                </span>
              </div>

              <!-- Waiter -->
              <div class="order-form-group">
                <label
                  for="waiter"
                  class="order-form-label"
                >
                  Waiter
                </label>

                <div class="readonly-select-control">
                  <input
                    id="waiter"
                    type="text"
                    :value="waiterName"
                    readonly
                    disabled
                  />

                  <i class="bi bi-person"></i>
                </div>
              </div>

              <!-- Status -->
              <div class="order-form-group">
                <label
                  for="status"
                  class="order-form-label required"
                >
                  Status
                </label>

                <select
                  id="status"
                  v-model="form.status"
                  class="order-form-control"
                  :class="{
                    invalid:
                      getFieldError('status'),
                  }"
                  :disabled="isSubmitting"
                >
                  <option
                    v-for="status in statuses"
                    :key="status.value"
                    :value="status.value"
                  >
                    {{ status.label }}
                  </option>
                </select>

                <span
                  v-if="getFieldError('status')"
                  class="order-field-error"
                >
                  {{ getFieldError("status") }}
                </span>
              </div>

              <!-- Capacity -->
              <div class="order-form-group">
                <label class="order-form-label">
                  Total Capacity
                </label>

                <div class="capacity-display-control">
                  <i class="bi bi-people"></i>

                  <span>
                    <strong>
                      {{ selectedCapacity }}
                    </strong>

                    {{
                      selectedCapacity === 1
                        ? "seat"
                        : "seats"
                    }}
                  </span>
                </div>
              </div>

              <!-- Order Notes -->
              <div class="order-form-group order-form-group-full">
                <label
                  for="order_note"
                  class="order-form-label"
                >
                  Order Notes
                </label>

                <textarea
                  id="order_note"
                  v-model.trim="form.order_note"
                  class="order-form-control order-notes-control"
                  :class="{
                    invalid:
                      getFieldError(
                        'order_note'
                      ),
                  }"
                  placeholder="Enter any order notes (optional)"
                  rows="4"
                  :disabled="isSubmitting"
                ></textarea>

                <span
                  v-if="
                    getFieldError(
                      'order_note'
                    )
                  "
                  class="order-field-error"
                >
                  {{
                    getFieldError(
                      "order_note"
                    )
                  }}
                </span>
              </div>
            </div>
          </section>

          <!-- ==============================
               Items Card
          =============================== -->
          <section class="order-items-card">
            <header class="order-items-header">
              <div>
                <h2>Items</h2>

                <p>
                  Add menu items to this order
                </p>
              </div>

              <button
                type="button"
                class="add-item-button"
                :disabled="isSubmitting"
                @click="addOrderItem"
              >
                <i class="bi bi-plus-lg"></i>

                <span>Add Item</span>
              </button>
            </header>

            <div
              v-if="getFieldError('items')"
              class="items-general-error"
            >
              {{ getFieldError("items") }}
            </div>

            <div class="order-items-table-wrapper">
              <table class="order-items-table">
                <thead>
                  <tr>
                    <th class="item-column">
                      Item
                    </th>

                    <th class="variant-column">
                      Variant
                    </th>

                    <th class="addons-column">
                      Add-ons
                    </th>

                    <th class="quantity-column">
                      Qty
                    </th>

                    <th class="notes-column">
                      Notes
                    </th>

                    <th class="total-column">
                      Total
                    </th>

                    <th class="action-column">
                      <span class="visually-hidden">
                        Action
                      </span>
                    </th>
                  </tr>
                </thead>

                <tbody>
                  <tr
                    v-for="(item, index) in form.items"
                    :key="item.row_id"
                  >
                    <!-- Menu Item -->
                    <td data-label="Item">
                      <select
                        v-model="item.menu_item_id"
                        class="item-table-control"
                        :class="{
                          invalid:
                            getItemFieldError(
                              index,
                              'menu_item_id'
                            ),
                        }"
                        :disabled="isSubmitting"
                        @change="
                          handleMenuItemChange(
                            index
                          )
                        "
                      >
                        <option value="">
                          Select item
                        </option>

                        <option
                          v-for="menuItem in menuItems"
                          :key="menuItem.id"
                          :value="String(menuItem.id)"
                        >
                          {{ menuItem.menu_name }}
                        </option>
                      </select>

                      <span
                        v-if="
                          getItemFieldError(
                            index,
                            'menu_item_id'
                          )
                        "
                        class="table-field-error"
                      >
                        {{
                          getItemFieldError(
                            index,
                            "menu_item_id"
                          )
                        }}
                      </span>
                    </td>

                    <!-- Variant -->
                    <td data-label="Variant">
                      <select
                        v-model="
                          item.menu_item_variant_id
                        "
                        class="item-table-control"
                        :disabled="
                          isSubmitting ||
                          !getItemVariants(item)
                            .length
                        "
                      >
                        <option value="">
                          {{
                            getItemVariants(item)
                              .length
                              ? "Base item"
                              : "No variant"
                          }}
                        </option>

                        <option
                          v-for="variant in getItemVariants(
                            item
                          )"
                          :key="variant.id"
                          :value="String(variant.id)"
                        >
                          {{
                            variant.variant_name
                          }}
                        </option>
                      </select>
                    </td>

                    <!-- Add-ons -->
                    <td data-label="Add-ons">
                      <div class="item-addon-dropdown">
                        <button
                          type="button"
                          class="item-table-control addon-trigger"
                          :disabled="isSubmitting"
                          @click="
                            toggleAddonDropdown(
                              index
                            )
                          "
                        >
                          <span>
                            {{
                              getSelectedAddonLabel(
                                item
                              )
                            }}
                          </span>

                          <i class="bi bi-chevron-down"></i>
                        </button>

                        <div
                          v-if="
                            activeAddonDropdown ===
                            index
                          "
                          class="item-addon-menu"
                        >
                          <label
                            v-for="addon in getItemAddons(
                              item
                            )"
                            :key="addon.id"
                            class="item-addon-option"
                          >
                            <input
                              v-model="
                                item.addon_ids
                              "
                              type="checkbox"
                              :value="String(addon.id)"
                            />

                            <span>
                              <strong>
                                {{
                                  addon.add_on_name
                                }}
                              </strong>

                              <small>
                                +
                                {{
                                  formatCurrency(
                                    addon.price
                                  )
                                }}
                              </small>
                            </span>
                          </label>
                        </div>
                      </div>
                    </td>

                    <!-- Quantity -->
                    <td data-label="Qty">
                      <div
  class="table-quantity-control"
  @click.stop
>
  <button
    type="button"
    aria-label="Decrease quantity"
    :disabled="
      isSubmitting ||
      Number(item.quantity) <= 1
    "
    @click.stop.prevent="
      decreaseQuantity(index)
    "
  >
    <i class="bi bi-dash"></i>
  </button>

  <input
    v-model.number="item.quantity"
    type="number"
    min="1"
    max="100"
    inputmode="numeric"
    :disabled="isSubmitting"
    @click.stop
    @input="normalizeQuantity(index)"
    @blur="normalizeQuantity(index)"
  />

  <button
    type="button"
    aria-label="Increase quantity"
    :disabled="isSubmitting"
    @click.stop.prevent="
      increaseQuantity(index)
    "
  >
    <i class="bi bi-plus"></i>
  </button>
</div>
                    </td>

                    <!-- Notes -->
                    <td data-label="Notes">
                      <input
                        v-model.trim="
                          item.kitchen_note
                        "
                        type="text"
                        class="item-table-control"
                        placeholder="No onion, extra spicy"
                        :disabled="isSubmitting"
                      />
                    </td>

                    <!-- Total -->
                    <td data-label="Total">
                      <strong class="item-row-total">
                        {{
                          formatCurrency(
                            calculateItemTotal(
                              item
                            )
                          )
                        }}
                      </strong>
                    </td>

                    <!-- Delete -->
                    <td data-label="Action">
                      <button
                        type="button"
                        class="remove-item-button"
                        aria-label="Remove item"
                        :disabled="
                          isSubmitting ||
                          form.items.length === 1
                        "
                        @click="
                          removeOrderItem(index)
                        "
                      >
                        <i class="bi bi-trash3"></i>
                      </button>
                    </td>
                  </tr>
                </tbody>
              </table>
            </div>
          </section>
        </main>

        <!-- ==============================
             Right Order Summary
        =============================== -->
        <aside class="order-summary-panel">
          <div class="summary-panel-heading">
            <span class="summary-heading-icon">
              <i class="bi bi-basket2"></i>
            </span>

            <div>
              <h2>Order Summary</h2>

              <p>
                {{ totalItemQuantity }}
                {{
                  totalItemQuantity === 1
                    ? "item"
                    : "items"
                }}
              </p>
            </div>
          </div>

          <!-- Empty Summary -->
          <div
            v-if="!summaryItems.length"
            class="summary-empty-content"
          >
            <span class="summary-empty-icon">
              <i class="bi bi-basket"></i>
            </span>

            <h3>No items added yet</h3>

            <p>
              Add items to see the order summary
            </p>
          </div>

          <!-- Summary Items -->
          <div
            v-else
            class="summary-products"
          >
            <article
              v-for="summaryItem in summaryItems"
              :key="summaryItem.row_id"
              class="summary-product"
            >
              <div class="summary-product-content">
                <strong>
                  {{ summaryItem.name }}
                </strong>

                <small>
                  {{ summaryItem.quantity }}
                  ×
                  {{
                    formatCurrency(
                      summaryItem.unit_price
                    )
                  }}
                </small>

                <small
                  v-if="
                    summaryItem.addon_names
                  "
                  class="summary-addon-text"
                >
                  {{ summaryItem.addon_names }}
                </small>
              </div>

              <span>
                {{
                  formatCurrency(
                    summaryItem.total
                  )
                }}
              </span>
            </article>
          </div>

          <!-- Price Summary -->
          <div class="summary-price-section">
            <div class="summary-price-row">
              <span>Subtotal</span>

              <strong>
                {{ formatCurrency(subtotal) }}
              </strong>
            </div>

            <div class="summary-discount-control">
              <div class="summary-price-row">
                <label for="discount_amount">
                  Discount
                </label>

                <span class="discount-value">
                  −
                  {{
                    formatCurrency(
                      normalizedDiscount
                    )
                  }}
                </span>
              </div>

              <div class="discount-input-wrapper">
                <span>৳</span>

                <input
                  id="discount_amount"
                  v-model.number="
                    form.discount_amount
                  "
                  type="number"
                  min="0"
                  :max="subtotal"
                  step="0.01"
                  placeholder="0.00"
                  :disabled="isSubmitting"
                  @change="normalizeDiscount"
                />
              </div>

              <span
                v-if="
                  getFieldError(
                    'discount_amount'
                  )
                "
                class="order-field-error"
              >
                {{
                  getFieldError(
                    "discount_amount"
                  )
                }}
              </span>
            </div>

            <div class="summary-price-row">
              <span>Tax</span>

              <strong>
                {{
                  formatCurrency(
                    Number(form.tax_amount) ||
                    0
                  )
                }}
              </strong>
            </div>

            <div class="summary-price-row">
              <span>Service Charge</span>

              <strong>
                {{
                  formatCurrency(
                    Number(
                      form.service_charge
                    ) || 0
                  )
                }}
              </strong>
            </div>
          </div>

          <!-- Grand Total -->
          <div class="summary-grand-total">
            <span>Total Amount</span>

            <strong>
              {{ formatCurrency(grandTotal) }}
            </strong>
          </div>

          <!-- ==============================
               Payment Information
          =============================== -->
          <section class="summary-payment-section">
            <div class="summary-payment-heading">
              <div>
                <h3>Payment Information</h3>

                <p>
                  Record customer payment during
                  order creation
                </p>
              </div>

              <span
                class="payment-status-indicator"
                :class="
                  getPaymentStatusClass(
                    paymentStatus
                  )
                "
              >
                {{ paymentStatusLabel }}
              </span>
            </div>

            <!-- Paid Amount -->
            <div class="summary-payment-group">
              <label
                for="paid_amount"
                class="summary-payment-label"
              >
                Paid Amount
              </label>

              <div
                class="summary-payment-input"
                :class="{
                  invalid:
                    getFieldError(
                      'paid_amount'
                    ),
                }"
              >
                <span>৳</span>

                <input
                  id="paid_amount"
                  v-model.number="form.paid_amount"
                  type="number"
                  min="0"
                  :max="grandTotal"
                  step="0.01"
                  placeholder="0.00"
                  :disabled="isSubmitting"
                  @input="handlePaidAmountInput"
                  @change="normalizePaidAmount"
                />
              </div>

              <span
                v-if="
                  getFieldError(
                    'paid_amount'
                  )
                "
                class="order-field-error"
              >
                {{
                  getFieldError(
                    "paid_amount"
                  )
                }}
              </span>
            </div>

            <!-- Payment Method -->
            <div class="summary-payment-group">
              <label
                for="payment_method"
                class="summary-payment-label"
                :class="{
                  required:
                    normalizedPaidAmount > 0,
                }"
              >
                Payment Method
              </label>

              <select
                id="payment_method"
                v-model="form.payment_method"
                class="summary-payment-select"
                :class="{
                  invalid:
                    getFieldError(
                      'payment_method'
                    ),
                }"
                :disabled="
                  isSubmitting ||
                  normalizedPaidAmount <= 0
                "
                @change="
                  clearValidationField(
                    'payment_method'
                  )
                "
              >
                <option value="">
                  {{
                    normalizedPaidAmount > 0
                      ? "Select payment method"
                      : "No payment received"
                  }}
                </option>

                <option
                  v-for="method in paymentMethods"
                  :key="method.value"
                  :value="method.value"
                >
                  {{ method.label }}
                </option>
              </select>

              <span
                v-if="
                  getFieldError(
                    'payment_method'
                  )
                "
                class="order-field-error"
              >
                {{
                  getFieldError(
                    "payment_method"
                  )
                }}
              </span>
            </div>

            <!-- Payment Reference -->
            <div
              v-if="
                normalizedPaidAmount > 0 &&
                form.payment_method &&
                form.payment_method !== 'cash'
              "
              class="summary-payment-group"
            >
              <label
                for="payment_reference"
                class="summary-payment-label"
              >
                Payment Reference
              </label>

              <input
                id="payment_reference"
                v-model.trim="
                  form.payment_reference
                "
                type="text"
                class="summary-payment-reference"
                :class="{
                  invalid:
                    getFieldError(
                      'payment_reference'
                    ),
                }"
                placeholder="Transaction ID or reference"
                maxlength="150"
                :disabled="isSubmitting"
                @input="
                  clearValidationField(
                    'payment_reference'
                  )
                "
              />

              <span
                v-if="
                  getFieldError(
                    'payment_reference'
                  )
                "
                class="order-field-error"
              >
                {{
                  getFieldError(
                    "payment_reference"
                  )
                }}
              </span>
            </div>

            <!-- Payment Calculation -->
            <div class="summary-payment-calculation">
              <div class="summary-payment-row">
                <span>Total Amount</span>

                <strong>
                  {{ formatCurrency(grandTotal) }}
                </strong>
              </div>

              <div class="summary-payment-row">
                <span>Paid Amount</span>

                <strong class="paid-amount-value">
                  {{
                    formatCurrency(
                      normalizedPaidAmount
                    )
                  }}
                </strong>
              </div>

              <div class="summary-payment-divider"></div>

              <div class="summary-payment-row due-row">
                <span>Due Amount</span>

                <strong>
                  {{ formatCurrency(dueAmount) }}
                </strong>
              </div>
            </div>

            <div
              v-if="normalizedPaidAmount > grandTotal"
              class="payment-warning-message"
            >
              <i class="bi bi-exclamation-triangle"></i>

              Paid amount cannot be greater than
              the total amount.
            </div>
          </section>

          <!-- Actions -->
          <div class="summary-actions">
            <button
              type="submit"
              class="create-order-button"
              :disabled="
                isSubmitting ||
                !canSubmit
              "
            >
              <span
                v-if="isSubmitting"
                class="spinner-border spinner-border-sm"
              ></span>

              <i
                v-else
                :class="
                  isEditMode
                    ? 'bi bi-save2'
                    : 'bi bi-clipboard-check'
                "
              ></i>

              <span>
                {{ submitButtonLabel }}
              </span>
            </button>

            <button
              type="button"
              class="reset-order-button"
              :disabled="isSubmitting"
              @click="resetForm"
            >
              <i class="bi bi-arrow-clockwise"></i>

              <span>
                {{
                  isEditMode
                    ? "Restore Original"
                    : "Reset"
                }}
              </span>
            </button>
          </div>
        </aside>
      </div>
    </form>
  </div>
</template>

<script src="./CreateOrderView.js"></script>

<style
  scoped
  src="./CreateOrderView.css"
></style>