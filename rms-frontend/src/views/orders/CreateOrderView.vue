<template>
  <div class="new-order-page">

    <!-- =====================================================
         PAGE HEADER
    ====================================================== -->
    <header class="new-order-header">

      <div class="new-order-header-content">

        <div class="new-order-title-wrapper">

          <div class="new-order-title-icon">
            <i class="bi bi-cart-plus"></i>
          </div>

          <div>
            <h1 class="new-order-title">
              {{ pageTitle }}
            </h1>

            <p class="new-order-subtitle">
              {{ pageSubtitle }}
            </p>
          </div>

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

      </div>

    </header>


    <!-- =====================================================
         LOADING
    ====================================================== -->
    <div
      v-if="isLoading"
      class="order-page-loading"
    >

      <span
        class="spinner-border"
        role="status"
      ></span>

      <p>
        Loading order information...
      </p>

    </div>


    <!-- =====================================================
         MAIN FORM
    ====================================================== -->
    <form
      v-else
      class="new-order-form"
      @submit.prevent="submitOrder"
    >

      <!-- =================================================
           GENERAL ERROR
      ================================================== -->
      <div
        v-if="generalError"
        class="order-alert order-alert-danger"
      >

        <div class="order-alert-content">

          <i class="bi bi-exclamation-circle"></i>

          <span>
            {{ generalError }}
          </span>

        </div>

        <button
          type="button"
          aria-label="Close"
          @click="generalError = ''"
        >
          <i class="bi bi-x"></i>
        </button>

      </div>


      <!-- =================================================
           COMPLETION RETRY MESSAGE
      ================================================== -->
      <div
        v-if="completionRetryMode"
        class="order-alert order-alert-info"
      >

        <div class="order-alert-content">

          <i class="bi bi-shield-check"></i>

          <span>
            The payment is already saved in the immutable ledger.
            <strong>Retry Complete</strong> will only retry the
            final completion endpoint and will not record another payment.
          </span>

        </div>

      </div>


      <!-- =================================================
           OPERATION COMMITTED MESSAGE
      ================================================== -->
      <div
        v-if="operationCommitted && !completionRetryMode"
        class="order-alert order-alert-info"
      >

        <div class="order-alert-content">

          <i class="bi bi-database-check"></i>

          <span>
            This request has already been saved on the server.
            Submit and Restore are locked to prevent duplicate payments
            or duplicate kitchen extensions.
          </span>

        </div>

      </div>


      <!-- =================================================
           CUSTOMER DETAILS
      ================================================= -->
      <section class="customer-details-card">

        <div class="section-heading">

          <div class="section-heading-left">

            <span class="section-heading-icon">
              <i class="bi bi-person-vcard"></i>
            </span>

            <div>

              <h2>
                Customer Details
              </h2>

              <p>
                Customer, table and order information
              </p>

            </div>

          </div>

          <span
            v-if="isEditMode"
            class="edit-mode-badge"
          >
            <i class="bi bi-pencil-square"></i>
            Edit Mode
          </span>

        </div>


        <div class="customer-details-grid">

          <!-- =============================================
               CUSTOMER NAME
          ============================================== -->
          <div class="order-form-group customer-name-group">

            <label
              for="customer_name"
              class="order-form-label"
            >
              Customer Name
            </label>

            <div class="customer-input-wrapper">

              <div class="input-icon">
                <i class="bi bi-person"></i>
              </div>

              <input
                id="customer_name"
                v-model.trim="form.customer_name"
                type="text"
                class="order-form-control with-left-icon"
                :class="{
                  invalid: getFieldError('customer_name'),
                }"
                placeholder="Enter customer name"
                autocomplete="off"
                :disabled="
                  isSubmitting ||
                  isServedEditMode ||
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


              <!-- Customer Search -->
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
                    {{ getCustomerInitial(customer) }}
                  </span>

                  <span class="customer-result-details">

                    <strong>
                      {{ customer.name }}
                    </strong>

                    <small>
                      {{ customer.phone || "No phone number" }}
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
              v-if="getFieldError('customer_name')"
              class="order-field-error"
            >
              {{ getFieldError("customer_name") }}
            </span>

          </div>


          <!-- =============================================
               CUSTOMER PHONE
          ============================================== -->
          <div class="order-form-group">

            <label
              for="customer_phone"
              class="order-form-label"
            >
              Customer Phone
            </label>

            <div class="input-with-icon-wrapper">

              <span class="input-icon">
                <i class="bi bi-telephone"></i>
              </span>

              <input
                id="customer_phone"
                v-model.trim="form.customer_phone"
                type="text"
                class="order-form-control with-left-icon"
                :class="{
                  invalid: getFieldError('customer_phone'),
                }"
                placeholder="01XXXXXXXXX"
                :disabled="
                  isSubmitting ||
                  isServedEditMode ||
                  Boolean(selectedCustomer)
                "
              />

            </div>

            <span
              v-if="getFieldError('customer_phone')"
              class="order-field-error"
            >
              {{ getFieldError("customer_phone") }}
            </span>

          </div>


          <!-- =============================================
               CUSTOMER EMAIL
          ============================================== -->
          <div class="order-form-group">

            <label
              for="customer_email"
              class="order-form-label"
            >
              Customer Email
            </label>

            <div class="input-with-icon-wrapper">

              <span class="input-icon">
                <i class="bi bi-envelope"></i>
              </span>

              <input
                id="customer_email"
                v-model.trim="form.customer_email"
                type="email"
                class="order-form-control with-left-icon"
                :class="{
                  invalid: getFieldError('customer_email'),
                }"
                placeholder="customer@example.com"
                :disabled="
                  isSubmitting ||
                  isServedEditMode ||
                  Boolean(selectedCustomer)
                "
              />

            </div>

            <span
              v-if="getFieldError('customer_email')"
              class="order-field-error"
            >
              {{ getFieldError("customer_email") }}
            </span>

          </div>


          <!-- =============================================
               TABLE
          ============================================== -->
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
                invalid: getFieldError('restaurant_table_id'),
              }"
              :disabled="
                isSubmitting ||
                isServedEditMode
              "
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
              v-if="getFieldError('restaurant_table_id')"
              class="order-field-error"
            >
              {{ getFieldError("restaurant_table_id") }}
            </span>

          </div>


          <!-- =============================================
               MERGED TABLES
          ============================================== -->
          <div
            ref="mergedDropdownRef"
            class="order-form-group merged-dropdown-group"
          >

            <label class="order-form-label">
              Merged Tables
            </label>

            <button
              type="button"
              class="merged-dropdown-trigger"
              :class="{
                active: showMergedDropdown,
                invalid: getFieldError('merged_table_ids'),
              }"
              :disabled="
                isSubmitting ||
                isServedEditMode ||
                !form.restaurant_table_id
              "
              @click="toggleMergedDropdown"
            >

              <span
                :class="{
                  placeholder: !selectedMergedTableNames,
                }"
              >
                {{ selectedMergedTableNames || "Select tables" }}
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

                <span>
                  Select tables
                </span>

                <button
                  v-if="form.merged_table_ids.length"
                  type="button"
                  @click="clearMergedTables"
                >
                  Clear
                </button>

              </div>


              <div
                v-if="availableMergeTables.length"
                class="merged-dropdown-list"
              >

                <label
                  v-for="table in availableMergeTables"
                  :key="table.id"
                  class="merged-dropdown-option"
                  :class="{
                    selected: isMergeTableSelected(table.id),
                  }"
                >

                  <input
                    v-model="form.merged_table_ids"
                    type="checkbox"
                    :value="String(table.id)"
                    :disabled="
                      isSubmitting ||
                      isServedEditMode
                    "
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
                      {{
                        Number(table.capacity) === 1
                          ? "seat"
                          : "seats"
                      }}
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
              v-if="getFieldError('merged_table_ids')"
              class="order-field-error"
            >
              {{ getFieldError("merged_table_ids") }}
            </span>

          </div>


          <!-- =============================================
               WAITER
          ============================================== -->
          <div class="order-form-group">

            <label class="order-form-label">
              Waiter
            </label>

            <div class="readonly-select-control">

              <div class="readonly-control-content">

                <span class="readonly-control-icon">
                  <i class="bi bi-person-badge"></i>
                </span>

                <input
                  id="waiter"
                  type="text"
                  :value="waiterName"
                  readonly
                  disabled
                />

              </div>

            </div>

          </div>


          <!-- =============================================
               STATUS
          ============================================== -->
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
                invalid: getFieldError('status'),
              }"
              :disabled="
                isSubmitting ||
                isEditMode
              "
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


          <!-- =============================================
               CAPACITY
          ============================================== -->
          <div class="order-form-group">

            <label class="order-form-label">
              Capacity
            </label>

            <div class="capacity-display-control">

              <span class="capacity-icon">
                <i class="bi bi-people"></i>
              </span>

              <div>

                <strong>
                  {{ selectedCapacity }}
                </strong>

                <span>
                  {{
                    selectedCapacity === 1
                      ? "seat"
                      : "seats"
                  }}
                </span>

              </div>

            </div>

          </div>


          <!-- =============================================
               ORDER NOTE
          ============================================== -->
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
                invalid: getFieldError('order_note'),
              }"
              placeholder="Add any special order note..."
              rows="3"
              :disabled="isSubmitting"
            ></textarea>

            <span
              v-if="getFieldError('order_note')"
              class="order-field-error"
            >
              {{ getFieldError("order_note") }}
            </span>

          </div>

        </div>

      </section>


      <!-- =================================================
           SERVED ORDER INFORMATION
      ================================================== -->
      <div
        v-if="isServedEditMode"
        class="order-alert order-alert-info served-order-alert"
      >

        <div class="order-alert-content">

          <i class="bi bi-info-circle"></i>

          <span>

            <template v-if="completionIntent">

              <template v-if="completionReadyWithoutPayment">

                This served order is fully paid.
                Existing items, customer and table are locked.
                Complete the bill below; no new payment will be recorded.

              </template>

              <template v-else>

                This served order has an outstanding due.
                Existing items, customer and table are locked.
                Settle the payment below to complete the bill.

              </template>

            </template>

            <template v-else>

              This order has already been served.
              Existing items, customer and table are locked.
              Use the menu area to add the next kitchen batch,
              or update the payment below.

            </template>

          </span>

        </div>

      </div>


      <!-- =================================================
           MAIN POS WORKSPACE
      ================================================== -->
      <div class="order-pos-workspace">


        <!-- =================================================
             LEFT SIDE — MENU AREA
        ================================================== -->
        <main class="menu-area-panel">

          <!-- MENU HEADER -->
          <div class="menu-area-header">

            <div>

              <div class="menu-area-title-row">

                <span class="menu-area-icon">
                  <i class="bi bi-grid-3x3-gap"></i>
                </span>

                <h2>
                  Menu
                </h2>

              </div>

              <p>
                Select food and drinks to add to the order
              </p>

            </div>

            <span class="menu-item-count">
              {{ menuItems.length }}
              items
            </span>

          </div>


          <!-- =================================================
               MENU CONTENT
          ================================================== -->
          <div class="menu-content-area">

            <!-- ============================================
                 CATEGORY SIDEBAR
            ============================================= -->
            <aside class="menu-category-sidebar">

              <!-- ALL MENU -->
              <button
                type="button"
                class="menu-category-button"
                :class="{
                  active: selectedMenuCategory === 'all',
                }"
                :disabled="isSubmitting"
                @click="selectMenuCategory('all')"
              >

                <i class="bi bi-grid-3x3-gap"></i>

                <span>
                  All Menu
                </span>

              </button>


              <!-- CATEGORIES -->
              <button
                v-for="category in categories"
                :key="category.id"
                type="button"
                class="menu-category-button"
                :class="{
                  active:
                    selectedMenuCategory ===
                    String(category.id),
                }"
                :disabled="isSubmitting"
                @click="
                  selectMenuCategory(category.id)
                "
              >

                <span>
                  {{ category.category_name }}
                </span>

              </button>

            </aside>


            <!-- ============================================
                 MENU ITEMS
            ============================================= -->
            <div class="menu-scroll-area">

              <!-- EMPTY STATE -->
              <div
                v-if="!filteredMenuItems.length"
                class="menu-empty-state"
              >

                <span class="menu-empty-icon">
                  <i class="bi bi-inbox"></i>
                </span>

                <h3>
                  No menu items found
                </h3>

                <p>
                  No items are available in this category.
                </p>

              </div>


              <!-- MENU ITEMS GRID -->
              <div
                v-else
                class="menu-items-grid"
              >

                <button
                  v-for="menuItem in filteredMenuItems"
                  :key="menuItem.id"
                  type="button"
                  class="menu-item-card"
                  :class="{
                    'menu-item-disabled': isSubmitting,
                  }"
                  :disabled="isSubmitting"
                  @click="addMenuItemToOrder(menuItem)"
                >

                  <!-- IMAGE -->
                  <div class="menu-item-card-top">

                    <div class="menu-item-image">

                      <img
                        v-if="
                          menuItem.image ||
                          menuItem.image_url ||
                          menuItem.photo
                        "
                        :src="
                          menuItem.image ||
                          menuItem.image_url ||
                          menuItem.photo
                        "
                        :alt="menuItem.menu_name"
                      />

                      <span
                        v-else
                        class="menu-item-placeholder"
                      >
                        <i class="bi bi-cup-hot"></i>
                      </span>

                    </div>

                    <span class="menu-item-add-icon">
                      <i class="bi bi-plus"></i>
                    </span>

                  </div>


                  <!-- BODY -->
                  <div class="menu-item-card-body">

                    <h3>
                      {{ menuItem.menu_name }}
                    </h3>

                    <p
                      v-if="menuItem.description"
                      class="menu-item-description"
                    >
                      {{ menuItem.description }}
                    </p>


                    <div class="menu-item-card-footer">

                      <strong class="menu-item-price">
                        {{
                          formatCurrency(
                            menuItem.price ??
                            menuItem.base_price ??
                            0
                          )
                        }}
                      </strong>


                      <span
                        v-if="
                          getMenuItemVariants(menuItem).length
                        "
                        class="menu-item-option-badge"
                      >

                        <i class="bi bi-sliders"></i>

                        Options

                      </span>

                    </div>

                  </div>

                </button>

              </div>

            </div>

          </div>


          <!-- =================================================
               SELECTED ITEMS QUICK VIEW
          ================================================== -->
          <div class="selected-items-footer">

            <div class="selected-items-footer-left">

              <span class="selected-items-footer-icon">
                <i class="bi bi-basket-check"></i>
              </span>

              <div>

                <strong>
                  {{ totalItemQuantity }}
                  {{
                    totalItemQuantity === 1
                      ? "item"
                      : "items"
                  }}
                  selected
                </strong>

                <span>
                  Tap a menu item to add it
                </span>

              </div>

            </div>

            <div class="selected-items-footer-total">

              <span>
                Current Total
              </span>

              <strong>
                {{ formatCurrency(grandTotal) }}
              </strong>

            </div>

          </div>

        </main>


        <!-- =================================================
             RIGHT SIDE — ORDER SUMMARY
        ================================================== -->
        <aside class="order-summary-panel">

          <div class="order-summary-inner">


            <!-- =============================================
                 SUMMARY HEADER
            ============================================== -->
            <div class="summary-panel-heading">

              <div class="summary-heading-left">

                <span class="summary-heading-icon">
                  <i class="bi bi-receipt-cutoff"></i>
                </span>

                <div>

                  <h2>
                    Order Summary
                  </h2>

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

              <span class="summary-total-mini">
                {{ formatCurrency(grandTotal) }}
              </span>

            </div>


            <!-- =============================================
                 SUMMARY ITEM LIST
            ============================================== -->
            <div class="summary-items-scroll">

              <!-- EMPTY -->
              <div
                v-if="!summaryItems.length"
                class="summary-empty-content"
              >

                <span class="summary-empty-icon">
                  <i class="bi bi-basket"></i>
                </span>

                <h3>
                  No items added yet
                </h3>

                <p>
                  Select items from the menu
                  to build the order.
                </p>

              </div>


              <!-- ITEMS -->
              <div
                v-else
                class="summary-order-items"
              >

                <article
                  v-for="summaryItem in summaryItems"
                  :key="summaryItem.row_id"
                  class="summary-order-item"
                >

                  <!-- ITEM HEADER -->
                  <div class="summary-item-main">

                    <div class="summary-item-number">
                      <span>
                        {{ summaryItem.quantity }}
                      </span>
                    </div>

                    <div class="summary-item-info">

                      <strong>
                        {{ summaryItem.name }}
                      </strong>

                      <small>
                        {{
                          formatCurrency(
                            summaryItem.unit_price
                          )
                        }}
                        each
                      </small>

                    </div>

                    <strong class="summary-item-total">
                      {{
                        formatCurrency(
                          summaryItem.total
                        )
                      }}
                    </strong>

                  </div>


                  <!-- ITEM OPTIONS -->
                  <template
                    v-for="(item, index) in form.items"
                    :key="`${item.row_id}-summary`"
                  >

                    <template
                      v-if="
                        item.row_id ===
                        summaryItem.row_id
                      "
                    >

                      <!-- VARIANT -->
                      <div
                        v-if="
                          getItemVariants(item).length
                        "
                        class="summary-item-option-group"
                      >

                        <label>
                          Variant
                        </label>

                        <select
                          v-model="
                            item.menu_item_variant_id
                          "
                          class="summary-item-option-control"
                          :disabled="
                            isSubmitting ||
                            isItemLocked(item)
                          "
                        >

                          <option value="">
                            Base item
                          </option>

                          <option
                            v-for="
                              variant in
                              getItemVariants(item)
                            "
                            :key="variant.id"
                            :value="String(variant.id)"
                          >

                            {{ variant.variant_name }}
                            —
                            {{
                              formatCurrency(
                                variant.price
                              )
                            }}

                          </option>

                        </select>

                      </div>


                      <!-- ADDONS -->
                      <div
                        v-if="
                          getItemAddons(item).length
                        "
                        class="summary-item-option-group"
                      >

                        <label>
                          Add-ons
                        </label>

                        <div class="summary-addon-wrapper">

                          <button
                            type="button"
                            class="summary-item-option-control summary-addon-trigger"
                            :disabled="
                              isSubmitting ||
                              isItemLocked(item)
                            "
                            @click="
                              toggleAddonDropdown(index)
                            "
                          >

                            <span>
                              {{
                                getSelectedAddonLabel(item)
                              }}
                            </span>

                            <i
                              class="bi bi-chevron-down"
                              :class="{
                                rotated:
                                  activeAddonDropdown ===
                                  index,
                              }"
                            ></i>

                          </button>


                          <!-- ADDON DROPDOWN -->
                          <div
                            v-if="
                              activeAddonDropdown ===
                              index
                            "
                            class="summary-addon-menu"
                          >

                            <label
                              v-for="
                                addon in
                                getItemAddons(item)
                              "
                              :key="addon.id"
                              class="summary-addon-option"
                            >

                              <input
                                v-model="item.addon_ids"
                                type="checkbox"
                                :value="String(addon.id)"
                                :disabled="
                                  isSubmitting ||
                                  isItemLocked(item)
                                "
                              />

                              <span class="summary-addon-check">
                                <i class="bi bi-check"></i>
                              </span>

                              <span class="summary-addon-details">

                                <strong>
                                  {{
                                    addon.add_on_name ??
                                    addon.name
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

                      </div>


                      <!-- QUANTITY + REMOVE -->
                      <div class="summary-item-bottom-row">

                        <div class="summary-quantity">

                          <button
                            type="button"
                            aria-label="Decrease quantity"
                            :disabled="
                              isSubmitting ||
                              isItemLocked(item) ||
                              Number(item.quantity) <= 1
                            "
                            @click="
                              decreaseQuantity(index)
                            "
                          >
                            <i class="bi bi-dash"></i>
                          </button>

                          <span>
                            {{ item.quantity }}
                          </span>

                          <button
                            type="button"
                            aria-label="Increase quantity"
                            :disabled="
                              isSubmitting ||
                              isItemLocked(item)
                            "
                            @click="
                              increaseQuantity(index)
                            "
                          >
                            <i class="bi bi-plus"></i>
                          </button>

                        </div>


                        <input
                          v-model.trim="
                            item.kitchen_note
                          "
                          type="text"
                          class="summary-item-note-input"
                          placeholder="Item note..."
                          :disabled="
                            isSubmitting ||
                            isItemLocked(item)
                          "
                        />


                        <button
                          type="button"
                          class="summary-item-remove"
                          aria-label="Remove item"
                          :disabled="
                            isSubmitting ||
                            isItemLocked(item) ||
                            form.items.length === 1
                          "
                          @click="
                            removeOrderItem(index)
                          "
                        >
                          <i class="bi bi-trash3"></i>
                        </button>

                      </div>


                      <!-- LOCK MESSAGE -->
                      <small
                        v-if="isItemLocked(item)"
                        class="served-item-lock-note"
                      >

                        <i class="bi bi-lock-fill"></i>

                        Served item — locked

                      </small>

                    </template>

                  </template>

                </article>

              </div>

            </div>


            <!-- =============================================
                 PRICE SUMMARY
            ============================================== -->
            <div class="summary-price-section">

              <!-- SUBTOTAL -->
              <div class="summary-price-row">

                <span>
                  Subtotal
                </span>

                <strong>
                  {{ formatCurrency(subtotal) }}
                </strong>

              </div>


              <!-- DISCOUNT -->
              <div class="summary-discount-block">

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

                  <span>
                    ৳
                  </span>

                  <input
                    id="discount_amount"
                    v-model.number="
                      form.discount_amount
                    "
                    type="number"
                    min="0"
                    :max="subtotal"
                    step="0.01"
                    placeholder="0"
                    :disabled="
                      isSubmitting ||
                      completionIntent
                    "
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


              <!-- TAX -->
              <div class="summary-price-row">

                <span>
                  VAT / Tax
                </span>

                <strong>
                  {{
                    formatCurrency(
                      Number(form.tax_amount) || 0
                    )
                  }}
                </strong>

              </div>


              <!-- SERVICE CHARGE -->
              <div class="summary-price-row">

                <span>
                  Service Charge
                </span>

                <strong>
                  {{
                    formatCurrency(
                      Number(form.service_charge) || 0
                    )
                  }}
                </strong>

              </div>

            </div>


            <!-- =============================================
                 GRAND TOTAL
            ============================================== -->
            <div class="summary-grand-total">

              <div>

                <span>
                  Total
                </span>

                <small>
                  Final payable amount
                </small>

              </div>

              <strong>
                {{ formatCurrency(grandTotal) }}
              </strong>

            </div>


            <!-- =============================================
                 PAYMENT
            ============================================== -->
            <section class="summary-payment-section">

              <div class="summary-payment-heading">

                <div>

                  <h3>
                    Payment
                  </h3>

                  <p>
                    {{
                      isEditMode
                        ? "Update payment only when receiving additional money."
                        : "Record customer payment"
                    }}
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


              <!-- PAID AMOUNT -->
              <div class="summary-payment-group">

                <label
                  for="paid_amount"
                  class="summary-payment-label"
                >
                  {{
                    isEditMode
                      ? "Total Paid"
                      : "Paid Amount"
                  }}
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

                  <span>
                    ৳
                  </span>

                  <input
                    id="paid_amount"
                    v-model.number="form.paid_amount"
                    type="number"
                    :min="
                      isEditMode
                        ? recordedPaidAmount
                        : 0
                    "
                    :max="grandTotal"
                    step="0.01"
                    placeholder="0.00"
                    :disabled="
                      isSubmitting ||
                      completionReadyWithoutPayment
                    "
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


              <!-- PAYMENT METHOD -->
              <div class="summary-payment-group">

                <label
                  for="payment_method"
                  class="summary-payment-label"
                  :class="{
                    required:
                      requiresNewPaymentMethod,
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
                    !requiresNewPaymentMethod
                  "
                  @change="
                    clearValidationField(
                      'payment_method'
                    )
                  "
                >

                  <option value="">
                    {{
                      requiresNewPaymentMethod
                        ? "Select payment method"
                        : isEditMode
                          ? "No new payment"
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


              <!-- PAYMENT REFERENCE -->
              <div
                v-if="
                  requiresNewPaymentMethod &&
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
                  placeholder="Transaction ID / reference"
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


              <!-- PAYMENT CALCULATION -->
              <div class="summary-payment-calculation">

                <div class="summary-payment-row">

                  <span>
                    Total Amount
                  </span>

                  <strong>
                    {{ formatCurrency(grandTotal) }}
                  </strong>

                </div>


                <!-- ALREADY RECORDED -->
                <div
                  v-if="isEditMode"
                  class="summary-payment-row"
                >

                  <span>
                    Already Recorded
                  </span>

                  <strong>
                    {{
                      formatCurrency(
                        recordedPaidAmount
                      )
                    }}
                  </strong>

                </div>


                <!-- NEW PAYMENT -->
                <div
                  v-if="isEditMode"
                  class="summary-payment-row"
                >

                  <span>
                    New Payment
                  </span>

                  <strong class="paid-amount-value">
                    {{
                      formatCurrency(
                        additionalPaymentAmount
                      )
                    }}
                  </strong>

                </div>


                <!-- TOTAL PAID -->
                <div class="summary-payment-row">

                  <span>
                    {{
                      isEditMode
                        ? "Total Paid"
                        : "Paid Amount"
                    }}
                  </span>

                  <strong class="paid-amount-value">
                    {{
                      formatCurrency(
                        normalizedPaidAmount
                      )
                    }}
                  </strong>

                </div>


                <div class="summary-payment-divider"></div>


                <!-- DUE -->
                <div class="summary-payment-row due-row">

                  <span>
                    Due Amount
                  </span>

                  <strong>
                    {{ formatCurrency(dueAmount) }}
                  </strong>

                </div>

              </div>


              <!-- PAYMENT WARNING -->
              <div
                v-if="
                  isEditMode &&
                  normalizedPaidAmount <
                    recordedPaidAmount
                "
                class="payment-warning-message"
              >

                <i class="bi bi-exclamation-triangle"></i>

                Paid amount cannot be lower than the
                amount already recorded in payment history.

              </div>


              <!-- OVERPAYMENT WARNING -->
              <div
                v-if="
                  normalizedPaidAmount >
                  grandTotal
                "
                class="payment-warning-message"
              >

                <i class="bi bi-exclamation-triangle"></i>

                Paid amount cannot be greater than
                the total amount.

              </div>


              <!-- SERVED ORDER DUE WARNING -->
              <div
                v-if="
                  isServedEditMode &&
                  dueAmount > 0
                "
                class="payment-warning-message"
              >

                <i class="bi bi-wallet2"></i>

                Outstanding due after this update:
                {{ formatCurrency(dueAmount) }}.

                Set the total paid amount to
                {{ formatCurrency(grandTotal) }}
                to settle the bill completely.

              </div>

            </section>


            <!-- =============================================
                 ACTION BUTTONS
            ============================================== -->
            <div class="summary-actions">

              <!-- SUBMIT -->
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


              <!-- RESET -->
              <button
                type="button"
                class="reset-order-button"
                :disabled="
                  isSubmitting ||
                  operationCommitted
                "
                @click="resetForm"
              >

                <i class="bi bi-arrow-clockwise"></i>

                <span>
                  {{
                    isEditMode
                      ? "Restore Original"
                      : "Reset Order"
                  }}
                </span>

              </button>

            </div>

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