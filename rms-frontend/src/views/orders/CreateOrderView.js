import orderService from "@/services/orderService";

export default {
  name: "CreateOrderView",

  /*
  |--------------------------------------------------------------------------
  | DATA
  |--------------------------------------------------------------------------
  */

  data() {
    return {
      /*
      |--------------------------------------------------------------------------
      | Page states
      |--------------------------------------------------------------------------
      */

      isLoading: true,
      isSubmitting: false,
      isSearchingCustomers: false,

      isEditMode: false,
      editingOrderId: null,
      editingOrderNumber: "",

      completionIntent: false,
      completionRetryMode: false,

      operationCommitted: false,

      originalOrder: null,

      selectedMenuCategory: "all",
      categories: [],

      /*
      |--------------------------------------------------------------------------
      | Existing payment snapshot
      |--------------------------------------------------------------------------
      */

      recordedPaidAmount: 0,
      originalDueAmount: 0,

      /*
      |--------------------------------------------------------------------------
      | Error states
      |--------------------------------------------------------------------------
      */

      generalError: "",
      validationErrors: {},

      /*
      |--------------------------------------------------------------------------
      | Create order options
      |--------------------------------------------------------------------------
      */

      tables: [],
      mergeTables: [],
      menuItems: [],
      addons: [],
      statuses: [],

      /*
      |--------------------------------------------------------------------------
      | Payment methods
      |--------------------------------------------------------------------------
      */

      paymentMethods: [
        {
          value: "cash",
          label: "Cash",
        },
        {
          value: "card",
          label: "Card",
        },
        {
          value: "bkash",
          label: "bKash",
        },
        {
          value: "nagad",
          label: "Nagad",
        },
        {
          value: "bank_transfer",
          label: "Bank Transfer",
        },
      ],

      /*
      |--------------------------------------------------------------------------
      | Current waiter
      |--------------------------------------------------------------------------
      */

      waiter: null,

      /*
      |--------------------------------------------------------------------------
      | Customer search
      |--------------------------------------------------------------------------
      */

      selectedCustomer: null,
      customerResults: [],
      showCustomerResults: false,

      /*
      |--------------------------------------------------------------------------
      | Dropdown states
      |--------------------------------------------------------------------------
      */

      showMergedDropdown: false,
      activeAddonDropdown: null,

      /*
      |--------------------------------------------------------------------------
      | Timers
      |--------------------------------------------------------------------------
      */

      customerSearchTimer: null,

      /*
      |--------------------------------------------------------------------------
      | Order form
      |--------------------------------------------------------------------------
      */

      form: {
        /*
        |--------------------------------------------------------------------------
        | Table information
        |--------------------------------------------------------------------------
        */

        restaurant_table_id: "",
        merged_table_ids: [],

        /*
        |--------------------------------------------------------------------------
        | Customer information
        |--------------------------------------------------------------------------
        */

        customer_id: null,
        customer_name: "",
        customer_phone: "",
        customer_email: "",

        /*
        |--------------------------------------------------------------------------
        | Order information
        |--------------------------------------------------------------------------
        */

        status: "pending",

        discount_amount: 0,
        tax_amount: 0,
        service_charge: 0,

        order_note: "",

        /*
        |--------------------------------------------------------------------------
        | Payment information
        |--------------------------------------------------------------------------
        */

        paid_amount: 0,
        payment_method: "",
        payment_reference: "",

        /*
        |--------------------------------------------------------------------------
        | Order items
        |--------------------------------------------------------------------------
        */

        items: [],
      },
    };
  },

  /*
  |--------------------------------------------------------------------------
  | COMPUTED
  |--------------------------------------------------------------------------
  */

  computed: {
    /*
    |--------------------------------------------------------------------------
    | Basic page mode
    |--------------------------------------------------------------------------
    */

    isEdit() {
      return this.isEditMode;
    },

    pageTitle() {
      if (!this.isEditMode) {
        return "New Order";
      }

      if (this.editingOrderNumber) {
        return `Edit Order ${this.editingOrderNumber}`;
      }

      return `Edit Order #${this.editingOrderId}`;
    },

    pageSubtitle() {
      if (!this.isEditMode) {
        return "Create a new dine-in restaurant order";
      }

      if (
        this.isServedEditMode &&
        this.completionIntent
      ) {
        return "Settle the outstanding due and complete this served order";
      }

      if (this.isServedEditMode) {
        return "Add new items or collect outstanding payment without changing served history";
      }

      return "Update the existing restaurant order";
    },

    pageMode() {
      return this.isEditMode ? "edit" : "create";
    },

    /*
    |--------------------------------------------------------------------------
    | Submit button
    |--------------------------------------------------------------------------
    */

    submitButtonLabel() {
      if (this.isSubmitting) {
        if (
          this.isServedEditMode &&
          this.completionIntent
        ) {
          return this.completionReadyWithoutPayment
            ? "Completing Order..."
            : "Saving Payment & Completing...";
        }

        return this.isEditMode
          ? "Updating Order..."
          : "Creating Order...";
      }

      if (
        this.isServedEditMode &&
        this.completionIntent
      ) {
        if (this.completionRetryMode) {
          return "Retry Complete";
        }

        if (this.completionReadyWithoutPayment) {
          return "Complete Order";
        }

        return "Pay & Complete Order";
      }

      return this.isEditMode
        ? "Update Order"
        : "Create Order";
    },

    /*
    |--------------------------------------------------------------------------
    | Served extension mode
    |--------------------------------------------------------------------------
    */

    isServedEditMode() {
      return (
        this.isEditMode &&
        this.originalOrder?.status === "served"
      );
    },

    hasNewExtensionItems() {
      if (!this.isServedEditMode) {
        return false;
      }

      return this.form.items.some(
        (item) => !item?.order_item_id
      );
    },

    historicalItemCount() {
      return this.form.items.filter(
        (item) => Boolean(item?.order_item_id)
      ).length;
    },

    newItemCount() {
      return this.form.items.filter(
        (item) => !item?.order_item_id
      ).length;
    },

    /*
    |--------------------------------------------------------------------------
    | Payment
    |--------------------------------------------------------------------------
    */

    additionalPaymentAmount() {
      if (!this.isEditMode) {
        return this.normalizedPaidAmount;
      }

      return Math.max(
        Number(
          (
            this.normalizedPaidAmount -
            this.recordedPaidAmount
          ).toFixed(2)
        ),
        0
      );
    },

    requiresNewPaymentMethod() {
      return this.additionalPaymentAmount > 0;
    },

    completionReadyWithoutPayment() {
      return (
        this.isServedEditMode &&
        this.completionIntent &&
        !this.hasNewExtensionItems &&
        this.dueAmount <= 0 &&
        this.additionalPaymentAmount <= 0
      );
    },

    /*
    |--------------------------------------------------------------------------
    | Table / merged table
    |--------------------------------------------------------------------------
    */

    waiterName() {
      return (
        this.waiter?.name ||
        this.waiter?.username ||
        this.waiter?.email ||
        "Current User"
      );
    },

    availableMergeTables() {
      return this.mergeTables.filter(
        (table) =>
          String(table.id) !==
          String(this.form.restaurant_table_id)
      );
    },

    selectedPrimaryTable() {
      return this.tables.find(
        (table) =>
          String(table.id) ===
          String(this.form.restaurant_table_id)
      );
    },

    selectedMergedTables() {
      return this.mergeTables.filter(
        (table) =>
          this.form.merged_table_ids.some(
            (tableId) =>
              String(tableId) ===
              String(table.id)
          )
      );
    },

    selectedMergedTableNames() {
      return this.selectedMergedTables
        .map((table) => table.table_name)
        .join(", ");
    },

    selectedCapacity() {
      const primaryCapacity = Number(
        this.selectedPrimaryTable?.capacity || 0
      );

      const mergedCapacity =
        this.selectedMergedTables.reduce(
          (total, table) =>
            total +
            Number(table.capacity || 0),
          0
        );

      return primaryCapacity + mergedCapacity;
    },

    /*
    |--------------------------------------------------------------------------
    | FILTERED MENU ITEMS
    |--------------------------------------------------------------------------
    */

    filteredMenuItems() {
      if (this.selectedMenuCategory === "all") {
        return this.menuItems;
      }

      return this.menuItems.filter((item) => {
        const categoryId =
          item.menu_category_id ??
          item.category_id ??
          null;

        return (
          categoryId !== null &&
          String(categoryId) ===
            String(this.selectedMenuCategory)
        );
      });
    },

    /*
    |--------------------------------------------------------------------------
    | Summary items
    |--------------------------------------------------------------------------
    */

    summaryItems() {
      return this.form.items
        .map((item) => {
          const menuItem =
            this.getSelectedMenuItem(item);

          if (!menuItem) {
            return null;
          }

          const variant =
            this.getSelectedVariant(item);

          const selectedAddons =
            this.getSelectedAddons(item);

          const unitPrice = variant
            ? Number(variant.price || 0)
            : Number(
                menuItem.price ??
                menuItem.base_price ??
                0
              );

          return {
            row_id: item.row_id,

            name: variant
              ? `${menuItem.menu_name} (${variant.variant_name})`
              : menuItem.menu_name,

            quantity:
              Number(item.quantity) || 1,

            unit_price: unitPrice,

            addon_names:
              selectedAddons
                .map(
                  (addon) =>
                    addon.add_on_name ??
                    addon.name ??
                    ""
                )
                .filter(Boolean)
                .join(", "),

            total:
              this.calculateItemTotal(item),
          };
        })
        .filter(Boolean);
    },

    /*
    |--------------------------------------------------------------------------
    | Total item quantity
    |--------------------------------------------------------------------------
    */

    totalItemQuantity() {
      return this.summaryItems.reduce(
        (total, item) =>
          total +
          Number(item.quantity || 0),
        0
      );
    },

    /*
    |--------------------------------------------------------------------------
    | Subtotal
    |--------------------------------------------------------------------------
    */

    subtotal() {
      return Number(
        this.form.items
          .reduce(
            (sum, item) =>
              sum +
              this.calculateItemTotal(item),
            0
          )
          .toFixed(2)
      );
    },

    /*
    |--------------------------------------------------------------------------
    | Discount
    |--------------------------------------------------------------------------
    */

    normalizedDiscount() {
      const discount = Number(
        this.form.discount_amount
      );

      if (
        !Number.isFinite(discount) ||
        discount <= 0
      ) {
        return 0;
      }

      return Math.min(
        discount,
        this.subtotal
      );
    },

    /*
    |--------------------------------------------------------------------------
    | Grand total
    |--------------------------------------------------------------------------
    */

    grandTotal() {
      const tax =
        Number(this.form.tax_amount) || 0;

      const service =
        Number(this.form.service_charge) || 0;

      const total =
        this.subtotal -
        this.normalizedDiscount +
        tax +
        service;

      return Math.max(
        Number(total.toFixed(2)),
        0
      );
    },

    /*
    |--------------------------------------------------------------------------
    | Payment amount
    |--------------------------------------------------------------------------
    */

    normalizedPaidAmount() {
      const amount =
        Number(this.form.paid_amount);

      if (
        !Number.isFinite(amount) ||
        amount <= 0
      ) {
        return 0;
      }

      return Number(
        amount.toFixed(2)
      );
    },

    /*
    |--------------------------------------------------------------------------
    | Due
    |--------------------------------------------------------------------------
    */

    dueAmount() {
      return Math.max(
        Number(
          (
            this.grandTotal -
            this.normalizedPaidAmount
          ).toFixed(2)
        ),
        0
      );
    },

    /*
    |--------------------------------------------------------------------------
    | Payment status
    |--------------------------------------------------------------------------
    */

    paymentStatus() {
      if (
        this.normalizedPaidAmount <= 0
      ) {
        return "due";
      }

      if (
        this.dueAmount <= 0
      ) {
        return "paid";
      }

      return "partially_paid";
    },

    paymentStatusLabel() {
      return {
        due: "Due",
        partially_paid: "Partially Paid",
        paid: "Paid",
      }[this.paymentStatus];
    },

    /*
    |--------------------------------------------------------------------------
    | Can submit
    |--------------------------------------------------------------------------
    */

    canSubmit() {
      const hasTable =
        Boolean(
          this.form.restaurant_table_id
        );

      const hasItems =
        this.form.items.length > 0;

      const allItemsValid =
        this.form.items.every(
          (item) =>
            Boolean(item.menu_item_id) &&
            Number(item.quantity) >= 1
        );

      const paymentNotReduced =
        !this.isEditMode ||
        this.normalizedPaidAmount >=
          this.recordedPaidAmount;

      const paymentNotOverTotal =
        this.normalizedPaidAmount <=
        this.grandTotal;

      const paymentMethodValid =
        !this.requiresNewPaymentMethod ||
        Boolean(this.form.payment_method);

      const completionReady =
        !this.completionIntent ||
        !this.isServedEditMode ||
        (
          this.dueAmount <= 0 &&
          !this.hasNewExtensionItems
        );

      const requestNotAlreadyCommitted =
        !this.operationCommitted ||
        this.completionReadyWithoutPayment;

      return (
        hasTable &&
        hasItems &&
        allItemsValid &&
        paymentNotReduced &&
        paymentNotOverTotal &&
        paymentMethodValid &&
        completionReady &&
        requestNotAlreadyCommitted
      );
    },
  },

  /*
  |--------------------------------------------------------------------------
  | CREATED
  |--------------------------------------------------------------------------
  */

  async created() {
    const routeOrderId =
      this.$route.params.id ?? null;

    this.completionIntent =
      String(
        this.$route.query?.complete ?? ""
      ) === "1";

    if (routeOrderId) {
      this.isEditMode = true;

      this.editingOrderId =
        Number(routeOrderId);
    }

    await this.loadCreateOptions();

    if (this.isEditMode) {
      await this.loadOrderForEdit();
    } else if (!this.form.items.length) {
      this.form.items.push(
        this.createEmptyOrderItem()
      );
    }
  },

  /*
  |--------------------------------------------------------------------------
  | MOUNTED
  |--------------------------------------------------------------------------
  */

  mounted() {
    document.addEventListener(
      "click",
      this.handleDocumentClick
    );
  },

  /*
  |--------------------------------------------------------------------------
  | BEFORE UNMOUNT
  |--------------------------------------------------------------------------
  */

  beforeUnmount() {
    document.removeEventListener(
      "click",
      this.handleDocumentClick
    );

    if (this.customerSearchTimer) {
      clearTimeout(
        this.customerSearchTimer
      );
    }
  },

  /*
  |--------------------------------------------------------------------------
  | METHODS
  |--------------------------------------------------------------------------
  */

  methods: {
    /*
    |--------------------------------------------------------------------------
    | Empty order item
    |--------------------------------------------------------------------------
    */

    createEmptyOrderItem() {
      return {
        row_id:
          typeof crypto !== "undefined" &&
          typeof crypto.randomUUID === "function"
            ? crypto.randomUUID()
            : `${Date.now()}-${Math.random()
                .toString(36)
                .slice(2, 10)}`,

        order_item_id: null,
        order_kitchen_batch_id: null,

        is_historical: false,
        is_locked: false,

        menu_item_id: "",
        menu_item_variant_id: "",

        addon_ids: [],

        quantity: 1,

        kitchen_note: "",
      };
    },

    /*
    |--------------------------------------------------------------------------
    | Load create options
    |--------------------------------------------------------------------------
    */

    async loadCreateOptions() {
      this.isLoading = true;
      this.generalError = "";

      try {
        const response =
          await orderService.getCreateOptions();

        /*
        |--------------------------------------------------------------------------
        | Normalize response
        |--------------------------------------------------------------------------
        */

        const responseData =
          response?.data?.data ??
          response?.data ??
          {};

        /*
        |--------------------------------------------------------------------------
        | Tables
        |--------------------------------------------------------------------------
        */

        this.tables =
          Array.isArray(responseData.tables)
            ? responseData.tables
            : [];

        /*
        |--------------------------------------------------------------------------
        | Merge tables
        |--------------------------------------------------------------------------
        */

        this.mergeTables =
          Array.isArray(
            responseData.merge_tables
          )
            ? responseData.merge_tables
            : this.tables;

        /*
        |--------------------------------------------------------------------------
        | Categories
        |--------------------------------------------------------------------------
        */

        this.categories =
          Array.isArray(
            responseData.categories
          )
            ? responseData.categories
            : [];

        /*
        |--------------------------------------------------------------------------
        | Menu items
        |--------------------------------------------------------------------------
        */

        this.menuItems =
          Array.isArray(
            responseData.menu_items
          )
            ? responseData.menu_items
            : [];

        /*
        |--------------------------------------------------------------------------
        | Add-ons
        |--------------------------------------------------------------------------
        */

        this.addons =
          Array.isArray(
            responseData.addons
          )
            ? responseData.addons
            : [];

        /*
        |--------------------------------------------------------------------------
        | Statuses
        |--------------------------------------------------------------------------
        */

        this.statuses =
          this.normalizeStatuses(
            responseData.statuses
          );

        /*
        |--------------------------------------------------------------------------
        | Waiter
        |--------------------------------------------------------------------------
        */

        this.waiter =
          responseData.waiter ??
          responseData.user ??
          null;

        /*
        |--------------------------------------------------------------------------
        | Fallback statuses
        |--------------------------------------------------------------------------
        */

        if (!this.statuses.length) {
          this.statuses = [
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
          ];
        }

        /*
        |--------------------------------------------------------------------------
        | Default status
        |--------------------------------------------------------------------------
        */

        const hasPendingStatus =
          this.statuses.some(
            (status) =>
              status.value === "pending"
          );

        if (hasPendingStatus) {
          this.form.status = "pending";
        } else if (this.statuses.length) {
          this.form.status =
            this.statuses[0].value;
        }

        /*
        |--------------------------------------------------------------------------
        | Always start menu on All Menu
        |--------------------------------------------------------------------------
        */

        this.selectedMenuCategory = "all";
      } catch (error) {
        console.error(
          "Create order options loading failed:",
          error
        );

        this.tables = [];
        this.mergeTables = [];
        this.categories = [];
        this.menuItems = [];
        this.addons = [];
        this.statuses = [];
        this.waiter = null;

        this.generalError =
          this.getErrorMessage(
            error,
            "Could not load the order form information."
          );
      } finally {
        this.isLoading = false;
      }
    },

    /*
    |--------------------------------------------------------------------------
    | Normalize statuses
    |--------------------------------------------------------------------------
    */

    normalizeStatuses(statuses) {
      if (Array.isArray(statuses)) {
        return statuses
          .map((status) => {
            if (typeof status === "string") {
              return {
                value: status,
                label:
                  this.formatStatusLabel(
                    status
                  ),
              };
            }

            if (
              status &&
              typeof status === "object"
            ) {
              const value =
                status.value ??
                status.status ??
                status.key ??
                "";

              if (!value) {
                return null;
              }

              return {
                value,
                label:
                  status.label ??
                  status.name ??
                  this.formatStatusLabel(
                    value
                  ),
              };
            }

            return null;
          })
          .filter(Boolean);
      }

      if (
        statuses &&
        typeof statuses === "object"
      ) {
        return Object.entries(
          statuses
        ).map(
          ([value, label]) => ({
            value,
            label:
              typeof label === "string"
                ? label
                : this.formatStatusLabel(
                    value
                  ),
          })
        );
      }

      return [];
    },

    /*
    |--------------------------------------------------------------------------
    | Format status
    |--------------------------------------------------------------------------
    */

    formatStatusLabel(status) {
      return String(status || "")
        .replace(/_/g, " ")
        .replace(
          /\b\w/g,
          (letter) =>
            letter.toUpperCase()
        );
    },

    /*
    |--------------------------------------------------------------------------
    | Load order for edit
    |--------------------------------------------------------------------------
    */

    async loadOrderForEdit() {
      if (!this.editingOrderId) {
        return;
      }

      this.isLoading = true;
      this.generalError = "";

      this.completionRetryMode = false;
      this.operationCommitted = false;

      try {
        const response =
          await orderService.getEditOptions(
            this.editingOrderId
          );

        const responseData =
          response?.data?.data ??
          response?.data ??
          {};

        const order =
          responseData.order ?? null;

        if (!order) {
          throw new Error(
            "Order information was not found."
          );
        }

        /*
        |--------------------------------------------------------------------------
        | Clone original order
        |--------------------------------------------------------------------------
        */

        this.originalOrder =
          typeof structuredClone === "function"
            ? structuredClone(order)
            : JSON.parse(
                JSON.stringify(order)
              );

        /*
        |--------------------------------------------------------------------------
        | Tables
        |--------------------------------------------------------------------------
        */

        this.tables =
          Array.isArray(
            responseData.tables
          )
            ? responseData.tables
            : [];

        /*
        |--------------------------------------------------------------------------
        | Merge tables
        |--------------------------------------------------------------------------
        */

        this.mergeTables =
          Array.isArray(
            responseData.merge_tables
          )
            ? responseData.merge_tables
            : this.tables;

        /*
        |--------------------------------------------------------------------------
        | Categories
        |--------------------------------------------------------------------------
        */

        this.categories =
          Array.isArray(
            responseData.categories
          )
            ? responseData.categories
            : [];

        /*
        |--------------------------------------------------------------------------
        | Menu items
        |--------------------------------------------------------------------------
        */

        this.menuItems =
          Array.isArray(
            responseData.menu_items
          )
            ? responseData.menu_items
            : [];

        /*
        |--------------------------------------------------------------------------
        | Add-ons
        |--------------------------------------------------------------------------
        */

        this.addons =
          Array.isArray(
            responseData.addons
          )
            ? responseData.addons
            : [];

        /*
        |--------------------------------------------------------------------------
        | Statuses
        |--------------------------------------------------------------------------
        */

        this.statuses =
          this.normalizeStatuses(
            responseData.statuses
          );

        if (!this.statuses.length) {
          this.statuses = [
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
          ];
        }

        /*
        |--------------------------------------------------------------------------
        | Waiter
        |--------------------------------------------------------------------------
        */

        this.waiter =
          responseData.waiter ??
          responseData.user ??
          this.waiter;

        /*
        |--------------------------------------------------------------------------
        | Reset category
        |--------------------------------------------------------------------------
        */

        this.selectedMenuCategory = "all";

        /*
        |--------------------------------------------------------------------------
        | Fill form
        |--------------------------------------------------------------------------
        */

        this.fillOrderForm(order);
      } catch (error) {
        console.error(
          "Order edit load failed:",
          error
        );

        this.categories = [];

        this.generalError =
          this.getErrorMessage(
            error,
            "Unable to load order edit information."
          );
      } finally {
        this.isLoading = false;
      }
    },

    /*
    |--------------------------------------------------------------------------
    | Fill edit form
    |--------------------------------------------------------------------------
    */

    fillOrderForm(order) {
      if (!order) {
        return;
      }

      /*
      |--------------------------------------------------------------------------
      | Header
      |--------------------------------------------------------------------------
      */

      this.editingOrderNumber =
        order.order_number ?? "";

      /*
      |--------------------------------------------------------------------------
      | Primary table
      |--------------------------------------------------------------------------
      */

      this.form.restaurant_table_id =
        order.primary_table?.id
          ? String(order.primary_table.id)
          : "";

      /*
      |--------------------------------------------------------------------------
      | Merged tables
      |--------------------------------------------------------------------------
      */

      this.form.merged_table_ids =
        Array.isArray(
          order.merged_tables
        )
          ? order.merged_tables
              .map((table) =>
                table?.id
                  ? String(table.id)
                  : null
              )
              .filter(Boolean)
          : [];

      /*
      |--------------------------------------------------------------------------
      | Customer
      |--------------------------------------------------------------------------
      */

      const customer =
        order.customer ?? null;

      this.form.customer_id =
        customer?.id ?? null;

      this.form.customer_name =
        customer?.name ?? "";

      this.form.customer_phone =
        customer?.phone ?? "";

      this.form.customer_email =
        customer?.email ?? "";

      this.selectedCustomer =
        customer?.id
          ? {
              id: customer.id,
              name: customer.name ?? "",
              phone: customer.phone ?? "",
              email: customer.email ?? "",
            }
          : null;

      this.customerResults = [];
      this.showCustomerResults = false;

      /*
      |--------------------------------------------------------------------------
      | Status
      |--------------------------------------------------------------------------
      */

      this.form.status =
        order.status ?? "pending";

      /*
      |--------------------------------------------------------------------------
      | Amounts
      |--------------------------------------------------------------------------
      */

      this.form.discount_amount =
        Number(
          order.discount_amount ?? 0
        );

      this.form.tax_amount =
        Number(
          order.tax_amount ?? 0
        );

      this.form.service_charge =
        Number(
          order.service_charge ?? 0
        );

      /*
      |--------------------------------------------------------------------------
      | Payment snapshot
      |--------------------------------------------------------------------------
      */

      this.recordedPaidAmount =
        Number(
          order.paid_amount ?? 0
        );

      this.originalDueAmount =
        Number(
          order.due_amount ?? 0
        );

      this.form.paid_amount =
        this.recordedPaidAmount;

      /*
      |--------------------------------------------------------------------------
      | Existing payment method is NOT reused
      |--------------------------------------------------------------------------
      */

      this.form.payment_method = "";
      this.form.payment_reference = "";

      /*
      |--------------------------------------------------------------------------
      | Notes
      |--------------------------------------------------------------------------
      */

      this.form.order_note =
        order.order_note ?? "";

      /*
      |--------------------------------------------------------------------------
      | Items
      |--------------------------------------------------------------------------
      */

      const orderItems =
        Array.isArray(order.items)
          ? order.items
          : [];

      this.form.items =
        orderItems.map(
          (item, index) => {
            const itemAddons =
              Array.isArray(item.addons)
                ? item.addons
                : [];

            const addonIds =
              itemAddons
                .map((addon) => {
                  const addonId =
                    addon.menu_addon_id ??
                    addon.add_on_id ??
                    addon.addon_id ??
                    addon.id ??
                    null;

                  return addonId
                    ? String(addonId)
                    : null;
                })
                .filter(Boolean);

            const rowId =
              typeof crypto !== "undefined" &&
              typeof crypto.randomUUID ===
                "function"
                ? crypto.randomUUID()
                : `${Date.now()}-${index}-${Math.random()
                    .toString(36)
                    .slice(2, 10)}`;

            return {
              row_id: rowId,

              order_item_id:
                item.id
                  ? Number(item.id)
                  : null,

              order_kitchen_batch_id:
                item.order_kitchen_batch_id
                  ? Number(
                      item.order_kitchen_batch_id
                    )
                  : null,

              is_historical:
                order.status === "served",

              is_locked:
                order.status === "served",

              menu_item_id:
                item.menu_item_id
                  ? String(
                      item.menu_item_id
                    )
                  : "",

              menu_item_variant_id:
                item.menu_item_variant_id
                  ? String(
                      item.menu_item_variant_id
                    )
                  : "",

              addon_ids: addonIds,

              quantity:
                Math.max(
                  Number(item.quantity) || 1,
                  1
                ),

              kitchen_note:
                item.kitchen_note ?? "",
            };
          }
        );

      /*
      |--------------------------------------------------------------------------
      | Ensure one row
      |--------------------------------------------------------------------------
      */

      if (!this.form.items.length) {
        this.form.items.push(
          this.createEmptyOrderItem()
        );
      }

      /*
      |--------------------------------------------------------------------------
      | Reset UI states
      |--------------------------------------------------------------------------
      */

      this.showMergedDropdown = false;
      this.activeAddonDropdown = null;

      this.validationErrors = {};
      this.generalError = "";
    },

    /*
    |--------------------------------------------------------------------------
    | Historical item protection
    |--------------------------------------------------------------------------
    */

    isItemLocked(item) {
      return Boolean(
        this.isServedEditMode &&
        item?.order_item_id
      );
    },

    /*
    |--------------------------------------------------------------------------
    | Table methods
    |--------------------------------------------------------------------------
    */

    handlePrimaryTableChange() {
      if (this.isServedEditMode) {
        return;
      }

      this.clearValidationField(
        "restaurant_table_id"
      );

      this.form.merged_table_ids = [];

      this.showMergedDropdown = false;
      this.activeAddonDropdown = null;
    },

    /*
    |--------------------------------------------------------------------------
    | Select menu category
    |--------------------------------------------------------------------------
    */

    selectMenuCategory(categoryId) {
      this.selectedMenuCategory =
        String(categoryId);
    },

    getTableDisplayName(table) {
      if (!table) {
        return "Table";
      }

      const name =
        table.table_name ??
        table.name ??
        `Table ${table.id}`;

      const section =
        table.section_label ??
        table.section ??
        "";

      return section
        ? `${name} · ${section}`
        : name;
    },

    isMergeTableSelected(tableId) {
      return this.form.merged_table_ids.some(
        (id) =>
          String(id) ===
          String(tableId)
      );
    },

    toggleMergedDropdown() {
      if (
        this.isSubmitting ||
        this.isServedEditMode ||
        !this.form.restaurant_table_id
      ) {
        return;
      }

      this.showMergedDropdown =
        !this.showMergedDropdown;

      if (this.showMergedDropdown) {
        this.activeAddonDropdown = null;
      }
    },

    clearMergedTables() {
      if (this.isServedEditMode) {
        return;
      }

      this.form.merged_table_ids = [];

      this.clearValidationField(
        "merged_table_ids"
      );
    },

    /*
    |--------------------------------------------------------------------------
    | MENU CARD FLOW
    |--------------------------------------------------------------------------
    */

    addMenuItemToOrder(menuItem) {
      if (
        !menuItem ||
        this.isSubmitting
      ) {
        return;
      }

      /*
      |--------------------------------------------------------------------------
      | Reuse empty row
      |--------------------------------------------------------------------------
      */

      const emptyItem =
        this.form.items.find(
          (item) =>
            !item.menu_item_id &&
            !item.order_item_id
        );

      if (emptyItem) {
        emptyItem.menu_item_id =
          String(menuItem.id);

        emptyItem.quantity = 1;

        emptyItem.menu_item_variant_id =
          "";

        emptyItem.addon_ids = [];

        emptyItem.kitchen_note = "";

        this.activeAddonDropdown = null;
        this.showMergedDropdown = false;

        this.clearValidationField("items");

        return;
      }

      /*
      |--------------------------------------------------------------------------
      | Existing NEW item
      |--------------------------------------------------------------------------
      */

      const existingItem =
        this.form.items.find(
          (item) =>
            !item.order_item_id &&
            String(item.menu_item_id) ===
              String(menuItem.id)
        );

      if (existingItem) {
        const index =
          this.form.items.indexOf(
            existingItem
          );

        this.increaseQuantity(index);

        this.activeAddonDropdown = null;

        return;
      }

      /*
      |--------------------------------------------------------------------------
      | Create new item
      |--------------------------------------------------------------------------
      */

      const newItem =
        this.createEmptyOrderItem();

      newItem.menu_item_id =
        String(menuItem.id);

      newItem.quantity = 1;
      newItem.menu_item_variant_id = "";
      newItem.addon_ids = [];

      this.form.items.push(newItem);

      this.clearValidationField("items");

      this.activeAddonDropdown = null;
      this.showMergedDropdown = false;

      /*
      |--------------------------------------------------------------------------
      | Scroll summary
      |--------------------------------------------------------------------------
      */

      this.$nextTick(() => {
        const summaryList =
          document.querySelector(
            ".summary-items-scroll"
          );

        if (summaryList) {
          summaryList.scrollTop =
            summaryList.scrollHeight;
        }
      });
    },

    /*
    |--------------------------------------------------------------------------
    | Get menu item variants
    |--------------------------------------------------------------------------
    */

    getMenuItemVariants(menuItem) {
      if (!menuItem) {
        return [];
      }

      const variants =
        menuItem.variants ??
        menuItem.menu_item_variants ??
        [];

      if (!Array.isArray(variants)) {
        return [];
      }

      return variants.filter(
        (variant) =>
          variant?.is_available !== false &&
          variant?.status !== "unavailable"
      );
    },

    /*
    |--------------------------------------------------------------------------
    | Add blank item
    |--------------------------------------------------------------------------
    */

    addOrderItem() {
      if (this.isSubmitting) {
        return;
      }

      this.form.items.push(
        this.createEmptyOrderItem()
      );

      this.clearValidationField("items");

      this.activeAddonDropdown = null;

      this.$nextTick(() => {
        const summaryList =
          document.querySelector(
            ".summary-items-scroll"
          );

        if (summaryList) {
          summaryList.scrollTop =
            summaryList.scrollHeight;
        }
      });
    },

    /*
    |--------------------------------------------------------------------------
    | Remove item
    |--------------------------------------------------------------------------
    */

    removeOrderItem(index) {
      const item =
        this.form.items[index];

      if (
        !item ||
        this.isItemLocked(item) ||
        this.form.items.length <= 1
      ) {
        return;
      }

      this.form.items.splice(index, 1);

      if (
        this.activeAddonDropdown === index
      ) {
        this.activeAddonDropdown = null;
      } else if (
        this.activeAddonDropdown > index
      ) {
        this.activeAddonDropdown -= 1;
      }

      this.removeItemValidationErrors(index);
    },

    /*
    |--------------------------------------------------------------------------
    | Menu item change
    |--------------------------------------------------------------------------
    */

    handleMenuItemChange(index) {
      const item =
        this.form.items[index];

      if (
        !item ||
        this.isItemLocked(item)
      ) {
        return;
      }

      item.menu_item_variant_id = "";
      item.addon_ids = [];

      this.activeAddonDropdown = null;

      this.clearValidationField(
        `items.${index}.menu_item_id`
      );

      this.clearValidationField(
        `items.${index}.menu_item_variant_id`
      );

      this.clearValidationField(
        `items.${index}.addon_ids`
      );

      this.clearValidationField("items");
    },

    /*
    |--------------------------------------------------------------------------
    | Get selected menu item
    |--------------------------------------------------------------------------
    */

    getSelectedMenuItem(item) {
      if (!item?.menu_item_id) {
        return null;
      }

      return (
        this.menuItems.find(
          (menuItem) =>
            String(menuItem.id) ===
            String(item.menu_item_id)
        ) ?? null
      );
    },

    /*
    |--------------------------------------------------------------------------
    | Get item variants
    |--------------------------------------------------------------------------
    */

    getItemVariants(item) {
      const menuItem =
        this.getSelectedMenuItem(item);

      if (!menuItem) {
        return [];
      }

      return this.getMenuItemVariants(
        menuItem
      );
    },

    /*
    |--------------------------------------------------------------------------
    | Get selected variant
    |--------------------------------------------------------------------------
    */

    getSelectedVariant(item) {
      if (
        !item?.menu_item_variant_id
      ) {
        return null;
      }

      return (
        this.getItemVariants(item).find(
          (variant) =>
            String(variant.id) ===
            String(
              item.menu_item_variant_id
            )
        ) ?? null
      );
    },

    /*
    |--------------------------------------------------------------------------
    | Get all available add-ons
    |--------------------------------------------------------------------------
    */

    getItemAddons() {
      return Array.isArray(this.addons)
        ? this.addons.filter(
            (addon) =>
              addon?.is_available !== false &&
              addon?.status !== "unavailable"
          )
        : [];
    },

    /*
    |--------------------------------------------------------------------------
    | Get selected add-ons
    |--------------------------------------------------------------------------
    */

    getSelectedAddons(item) {
      if (
        !Array.isArray(item?.addon_ids) ||
        !item.addon_ids.length
      ) {
        return [];
      }

      return this.getItemAddons().filter(
        (addon) =>
          item.addon_ids.some(
            (addonId) =>
              String(addonId) ===
              String(addon.id)
          )
      );
    },

    /*
    |--------------------------------------------------------------------------
    | Toggle add-on dropdown
    |--------------------------------------------------------------------------
    */

    toggleAddonDropdown(index) {
      const item =
        this.form.items[index];

      if (
        !item ||
        this.isItemLocked(item) ||
        this.isSubmitting
      ) {
        return;
      }

      this.activeAddonDropdown =
        this.activeAddonDropdown === index
          ? null
          : index;

      this.showMergedDropdown = false;
    },

    /*
    |--------------------------------------------------------------------------
    | Selected add-on label
    |--------------------------------------------------------------------------
    */

    getSelectedAddonLabel(item) {
      const selectedAddons =
        this.getSelectedAddons(item);

      if (!selectedAddons.length) {
        return this.getItemAddons().length
          ? "Select add-ons"
          : "No add-ons";
      }

      if (selectedAddons.length === 1) {
        return (
          selectedAddons[0].add_on_name ??
          selectedAddons[0].name ??
          "1 add-on"
        );
      }

      return `${selectedAddons.length} add-ons`;
    },

    /*
    |--------------------------------------------------------------------------
    | Quantity: increase
    |--------------------------------------------------------------------------
    */

    increaseQuantity(index) {
      const item =
        this.form.items[index];

      if (
        !item ||
        this.isItemLocked(item) ||
        this.isSubmitting
      ) {
        return;
      }

      const currentQuantity =
        Number(item.quantity);

      const safeQuantity =
        Number.isFinite(currentQuantity) &&
        currentQuantity >= 1
          ? currentQuantity
          : 1;

      this.form.items[index].quantity =
        Math.min(
          Math.floor(safeQuantity) + 1,
          100
        );

      this.clearValidationField(
        `items.${index}.quantity`
      );
    },

    /*
    |--------------------------------------------------------------------------
    | Quantity: decrease
    |--------------------------------------------------------------------------
    */

    decreaseQuantity(index) {
      const item =
        this.form.items[index];

      if (
        !item ||
        this.isItemLocked(item) ||
        this.isSubmitting
      ) {
        return;
      }

      const currentQuantity =
        Number(item.quantity);

      const safeQuantity =
        Number.isFinite(currentQuantity) &&
        currentQuantity >= 1
          ? currentQuantity
          : 1;

      this.form.items[index].quantity =
        Math.max(
          Math.floor(safeQuantity) - 1,
          1
        );

      this.clearValidationField(
        `items.${index}.quantity`
      );
    },

    /*
    |--------------------------------------------------------------------------
    | Normalize quantity
    |--------------------------------------------------------------------------
    */

    normalizeQuantity(index) {
      const item =
        this.form.items[index];

      if (
        !item ||
        this.isItemLocked(item)
      ) {
        return;
      }

      let quantity =
        Number(item.quantity);

      if (
        !Number.isFinite(quantity) ||
        quantity < 1
      ) {
        quantity = 1;
      }

      this.form.items[index].quantity =
        Math.min(
          Math.floor(quantity),
          100
        );

      this.clearValidationField(
        `items.${index}.quantity`
      );
    },

    /*
    |--------------------------------------------------------------------------
    | Item base price
    |--------------------------------------------------------------------------
    */

    getItemBasePrice(item) {
      const menuItem =
        this.getSelectedMenuItem(item);

      if (!menuItem) {
        return 0;
      }

      const variant =
        this.getSelectedVariant(item);

      if (variant) {
        return Number(
          variant.price ?? 0
        );
      }

      return Number(
        menuItem.price ??
        menuItem.base_price ??
        0
      );
    },

    /*
    |--------------------------------------------------------------------------
    | Add-on total
    |--------------------------------------------------------------------------
    */

    getItemAddonsTotal(item) {
      const selectedAddons =
        this.getSelectedAddons(item);

      return selectedAddons.reduce(
        (total, addon) =>
          total +
          Number(addon.price ?? 0),
        0
      );
    },

    /*
    |--------------------------------------------------------------------------
    | Full item total
    |--------------------------------------------------------------------------
    */

    calculateItemTotal(item) {
      if (!item) {
        return 0;
      }

      const basePrice =
        this.getItemBasePrice(item);

      const addonsTotal =
        this.getItemAddonsTotal(item);

      const quantity =
        Math.max(
          Number(item.quantity) || 1,
          1
        );

      return Number(
        (
          (basePrice + addonsTotal) *
          quantity
        ).toFixed(2)
      );
    },

    /*
    |--------------------------------------------------------------------------
    | Discount
    |--------------------------------------------------------------------------
    */

    normalizeDiscount() {
      let discount =
        Number(
          this.form.discount_amount
        );

      if (
        !Number.isFinite(discount) ||
        discount < 0
      ) {
        discount = 0;
      }

      this.form.discount_amount =
        Math.min(
          Number(discount.toFixed(2)),
          this.subtotal
        );

      this.clearValidationField(
        "discount_amount"
      );
    },

    /*
    |--------------------------------------------------------------------------
    | Currency
    |--------------------------------------------------------------------------
    */

    formatCurrency(amount) {
      const numericAmount =
        Number(amount) || 0;

      return new Intl.NumberFormat(
        "en-BD",
        {
          style: "currency",
          currency: "BDT",
          minimumFractionDigits: 2,
          maximumFractionDigits: 2,
        }
      ).format(numericAmount);
    },

    /*
    |--------------------------------------------------------------------------
    | Payment input
    |--------------------------------------------------------------------------
    */

    handlePaidAmountInput() {
      let amount =
        Number(this.form.paid_amount);

      if (
        !Number.isFinite(amount) ||
        amount < 0
      ) {
        amount = 0;
      }

      this.form.paid_amount = amount;

      if (!this.requiresNewPaymentMethod) {
        this.form.payment_method = "";
        this.form.payment_reference = "";
      }

      this.clearValidationField(
        "paid_amount"
      );

      this.clearValidationField(
        "payment_method"
      );

      this.clearValidationField(
        "payment_reference"
      );
    },

    /*
    |--------------------------------------------------------------------------
    | Normalize paid amount
    |--------------------------------------------------------------------------
    */

    normalizePaidAmount() {
      let amount =
        Number(this.form.paid_amount);

      if (
        !Number.isFinite(amount) ||
        amount < 0
      ) {
        amount = 0;
      }

      this.form.paid_amount =
        Number(amount.toFixed(2));

      if (!this.requiresNewPaymentMethod) {
        this.form.payment_method = "";
        this.form.payment_reference = "";
      }

      this.clearValidationField(
        "paid_amount"
      );
    },

    /*
    |--------------------------------------------------------------------------
    | Payment status CSS
    |--------------------------------------------------------------------------
    */

    getPaymentStatusClass(status) {
      switch (status) {
        case "paid":
          return "payment-status-paid status-paid";

        case "partially_paid":
          return "payment-status-partially-paid status-partial";

        default:
          return "payment-status-due status-due";
      }
    },

    /*
    |--------------------------------------------------------------------------
    | CUSTOMER SEARCH
    |--------------------------------------------------------------------------
    */

    handleCustomerNameInput() {
      if (this.isServedEditMode) {
        return;
      }

      this.form.customer_id = null;
      this.selectedCustomer = null;

      this.clearValidationField(
        "customer_name"
      );

      if (this.customerSearchTimer) {
        clearTimeout(
          this.customerSearchTimer
        );
      }

      const keyword =
        String(
          this.form.customer_name || ""
        ).trim();

      if (keyword.length < 2) {
        this.customerResults = [];
        this.showCustomerResults = false;
        this.isSearchingCustomers = false;

        return;
      }

      this.customerSearchTimer =
        setTimeout(() => {
          this.searchCustomers(keyword);
        }, 350);
    },

    handleCustomerSearchFocus() {
      if (this.isServedEditMode) {
        return;
      }

      if (
        String(
          this.form.customer_name || ""
        )
          .trim()
          .length >= 2 &&
        !this.selectedCustomer
      ) {
        this.showCustomerResults = true;
      }
    },

    async searchCustomers(keyword) {
      this.isSearchingCustomers = true;

      try {
        const response =
          await orderService.searchCustomers({
            search: keyword,
          });

        const responseData =
          response?.data?.data ??
          response?.data ??
          [];

        const customers =
          Array.isArray(responseData)
            ? responseData
            : Array.isArray(
                responseData.customers
              )
              ? responseData.customers
              : [];

        this.customerResults = customers;
        this.showCustomerResults = true;
      } catch (error) {
        console.error(
          "Customer search failed:",
          error
        );

        this.customerResults = [];
        this.showCustomerResults = false;
      } finally {
        this.isSearchingCustomers = false;
      }
    },

    selectCustomer(customer) {
      if (
        this.isServedEditMode ||
        !customer
      ) {
        return;
      }

      this.selectedCustomer = customer;

      this.form.customer_id =
        customer.id ?? null;

      this.form.customer_name =
        customer.name ?? "";

      this.form.customer_phone =
        customer.phone ?? "";

      this.form.customer_email =
        customer.email ?? "";

      this.customerResults = [];
      this.showCustomerResults = false;

      this.clearValidationField(
        "customer_id"
      );

      this.clearValidationField(
        "customer_name"
      );

      this.clearValidationField(
        "customer_phone"
      );

      this.clearValidationField(
        "customer_email"
      );
    },

    clearSelectedCustomer() {
      if (this.isServedEditMode) {
        return;
      }

      this.selectedCustomer = null;

      this.form.customer_id = null;
      this.form.customer_name = "";
      this.form.customer_phone = "";
      this.form.customer_email = "";

      this.customerResults = [];
      this.showCustomerResults = false;
    },

    getCustomerInitial(customer) {
      const name =
        String(
          customer?.name || "C"
        ).trim();

      return (
        name.charAt(0).toUpperCase() ||
        "C"
      );
    },

    /*
    |--------------------------------------------------------------------------
    | VALIDATION
    |--------------------------------------------------------------------------
    */

    getFieldError(field) {
      if (!this.validationErrors) {
        return "";
      }

      const error =
        this.validationErrors[field];

      if (Array.isArray(error)) {
        return error[0] || "";
      }

      return error || "";
    },

    clearValidationField(field) {
      if (
        this.validationErrors &&
        Object.prototype.hasOwnProperty.call(
          this.validationErrors,
          field
        )
      ) {
        const updated = {
          ...this.validationErrors,
        };

        delete updated[field];

        this.validationErrors = updated;
      }
    },

    getItemFieldError(index, field) {
      return this.getFieldError(
        `items.${index}.${field}`
      );
    },

    removeItemValidationErrors(index) {
      if (!this.validationErrors) {
        return;
      }

      const updated = {};

      Object.keys(
        this.validationErrors
      ).forEach((key) => {
        if (
          !key.startsWith(
            `items.${index}.`
          )
        ) {
          updated[key] =
            this.validationErrors[key];
        }
      });

      this.validationErrors = updated;
    },

    applyValidationErrors(error) {
      this.validationErrors = {};

      const errors =
        error?.response?.data?.errors;

      if (!errors) {
        return;
      }

      Object.keys(errors).forEach(
        (field) => {
          this.validationErrors[field] =
            Array.isArray(errors[field])
              ? errors[field][0]
              : errors[field];
        }
      );
    },

    /*
    |--------------------------------------------------------------------------
    | GENERAL ERROR
    |--------------------------------------------------------------------------
    */

    getErrorMessage(
      error,
      fallback = "Something went wrong."
    ) {
      return (
        error?.response?.data?.message ||
        error?.message ||
        fallback
      );
    },

    /*
    |--------------------------------------------------------------------------
    | GLOBAL DROPDOWN HANDLER
    |--------------------------------------------------------------------------
    */

    handleDocumentClick(event) {
      /*
      |--------------------------------------------------------------------------
      | Merge table dropdown
      |--------------------------------------------------------------------------
      */

      if (
        this.showMergedDropdown &&
        this.$refs.mergedDropdownRef &&
        !this.$refs.mergedDropdownRef.contains(
          event.target
        )
      ) {
        this.showMergedDropdown = false;
      }

      /*
      |--------------------------------------------------------------------------
      | Customer dropdown
      |--------------------------------------------------------------------------
      */

      const customerWrapper =
        event.target.closest(
          ".customer-input-wrapper"
        );

      if (!customerWrapper) {
        this.showCustomerResults = false;
      }

      /*
      |--------------------------------------------------------------------------
      | Add-on dropdown
      |--------------------------------------------------------------------------
      */

      const addonWrapper =
        event.target.closest(
          ".summary-addon-wrapper"
        ) ||
        event.target.closest(
          ".item-addon-dropdown"
        );

      if (!addonWrapper) {
        this.activeAddonDropdown = null;
      }
    },

    /*
    |--------------------------------------------------------------------------
    | GO BACK
    |--------------------------------------------------------------------------
    */

    goBack() {
      this.$router.back();
    },

    /*
    |--------------------------------------------------------------------------
    | PAYLOAD BUILDER
    |--------------------------------------------------------------------------
    */

    buildPayload() {
      return {
        /*
        |--------------------------------------------------------------------------
        | Table
        |--------------------------------------------------------------------------
        */

        restaurant_table_id:
          Number(
            this.form.restaurant_table_id
          ) || null,

        merged_table_ids:
          (
            this.form.merged_table_ids ||
            []
          )
            .map((id) => Number(id))
            .filter((id) => id > 0),

        /*
        |--------------------------------------------------------------------------
        | Customer
        |--------------------------------------------------------------------------
        */

        customer_id:
          this.form.customer_id
            ? Number(this.form.customer_id)
            : null,

        customer_name:
          this.form.customer_name || null,

        customer_phone:
          this.form.customer_phone || null,

        customer_email:
          this.form.customer_email || null,

        /*
        |--------------------------------------------------------------------------
        | Status
        |--------------------------------------------------------------------------
        */

        status: this.form.status,

        /*
        |--------------------------------------------------------------------------
        | Amounts
        |--------------------------------------------------------------------------
        */

        discount_amount:
          Number(this.normalizedDiscount) || 0,

        tax_amount:
          Number(this.form.tax_amount) || 0,

        service_charge:
          Number(this.form.service_charge) || 0,

        order_note:
          this.form.order_note || null,

        /*
        |--------------------------------------------------------------------------
        | Payment
        |--------------------------------------------------------------------------
        |
        | paid_amount is cumulative.
        | Backend calculates actual new payment.
        |--------------------------------------------------------------------------
        */

        paid_amount:
          Number(
            this.normalizedPaidAmount
          ) || 0,

        payment_method:
          this.requiresNewPaymentMethod
            ? this.form.payment_method
            : null,

        payment_reference:
          this.requiresNewPaymentMethod
            ? (
                this.form.payment_reference ||
                null
              )
            : null,

        /*
        |--------------------------------------------------------------------------
        | Items
        |--------------------------------------------------------------------------
        */

        items:
          this.form.items.map((item) => ({
            order_item_id:
              item.order_item_id
                ? Number(item.order_item_id)
                : null,

            menu_item_id:
              Number(item.menu_item_id),

            menu_item_variant_id:
              item.menu_item_variant_id
                ? Number(
                    item.menu_item_variant_id
                  )
                : null,

            addon_ids:
              (
                item.addon_ids || []
              )
                .map((id) => Number(id))
                .filter((id) => id > 0),

            quantity:
              Math.max(
                Number(item.quantity) || 1,
                1
              ),

            kitchen_note:
              item.kitchen_note || null,
          })),
      };
    },

    /*
    |--------------------------------------------------------------------------
    | SYNC PERSISTED PAYMENT STATE
    |--------------------------------------------------------------------------
    */

    syncPersistedPaymentState(order) {
      if (
        !order ||
        typeof order !== "object"
      ) {
        return;
      }

      const paidAmount =
        Number(order.paid_amount);

      const dueAmount =
        Number(order.due_amount);

      const normalizedPaid =
        Number.isFinite(paidAmount)
          ? Math.max(
              Number(
                paidAmount.toFixed(2)
              ),
              0
            )
          : this.normalizedPaidAmount;

      const normalizedDue =
        Number.isFinite(dueAmount)
          ? Math.max(
              Number(
                dueAmount.toFixed(2)
              ),
              0
            )
          : Math.max(
              Number(
                (
                  this.grandTotal -
                  normalizedPaid
                ).toFixed(2)
              ),
              0
            );

      this.recordedPaidAmount =
        normalizedPaid;

      this.originalDueAmount =
        normalizedDue;

      this.form.paid_amount =
        normalizedPaid;

      /*
      |--------------------------------------------------------------------------
      | Never submit old payment method again
      |--------------------------------------------------------------------------
      */

      this.form.payment_method = "";
      this.form.payment_reference = "";

      this.clearValidationField(
        "paid_amount"
      );

      this.clearValidationField(
        "payment_method"
      );

      this.clearValidationField(
        "payment_reference"
      );

      /*
      |--------------------------------------------------------------------------
      | Update original snapshot
      |--------------------------------------------------------------------------
      */

      this.originalOrder = {
        ...(this.originalOrder || {}),
        ...order,

        paid_amount: normalizedPaid,

        due_amount: normalizedDue,

        payment_status:
          order.payment_status ??
          (
            normalizedDue <= 0
              ? "paid"
              : normalizedPaid > 0
                ? "partially_paid"
                : "due"
          ),
      };
    },

    /*
    |--------------------------------------------------------------------------
    | COMPLETE SERVED ORDER ONLY
    |--------------------------------------------------------------------------
    */

    async completeServedOrderOnly() {
      if (!this.editingOrderId) {
        throw new Error(
          "Order ID is missing."
        );
      }

      const completeResponse =
        await orderService.completeOrder(
          this.editingOrderId
        );

      return (
        completeResponse?.data?.data ??
        completeResponse?.data ??
        completeResponse
      );
    },

    /*
    |--------------------------------------------------------------------------
    | COMPLETION FAILURE
    |--------------------------------------------------------------------------
    */

    handleCompletionFailure(
      error,
      order = null
    ) {
      if (order) {
        this.syncPersistedPaymentState(
          order
        );
      }

      this.completionRetryMode = true;

      if (
        error?.response?.status === 422
      ) {
        this.applyValidationErrors(
          error
        );
      }

      const backendMessage =
        this.getErrorMessage(
          error,
          "The final completion request could not be finished."
        );

      this.generalError =
        "Payment state is safe and no duplicate payment will be recorded. " +
        `${backendMessage} ` +
        'Click "Retry Complete" to retry only the final completion step.';
    },

    /*
    |--------------------------------------------------------------------------
    | MARK SERVER MUTATION COMMITTED
    |--------------------------------------------------------------------------
    */

    markOperationCommitted(order = null) {
      this.operationCommitted = true;

      if (
        this.isEditMode &&
        order &&
        typeof order === "object"
      ) {
        const serverPaid =
          Number(order.paid_amount);

        const serverDue =
          Number(order.due_amount);

        if (
          Number.isFinite(serverPaid) ||
          Number.isFinite(serverDue)
        ) {
          this.syncPersistedPaymentState(
            order
          );
        }
      }
    },

    /*
    |--------------------------------------------------------------------------
    | MARK COMPLETION COMMITTED
    |--------------------------------------------------------------------------
    */

    markCompletionCommitted(order = null) {
      this.operationCommitted = true;

      this.completionRetryMode = false;
      this.completionIntent = false;

      if (
        order &&
        typeof order === "object"
      ) {
        this.originalOrder = {
          ...(this.originalOrder || {}),
          ...order,

          status:
            order.status || "completed",
        };
      } else if (this.originalOrder) {
        this.originalOrder = {
          ...this.originalOrder,
          status: "completed",
        };
      }
    },

    /*
    |--------------------------------------------------------------------------
    | REDIRECT AFTER SUCCESS
    |--------------------------------------------------------------------------
    */

    async redirectAfterOrderSuccess(
      order,
      successMessage
    ) {
      if (this.$toast) {
        this.$toast.success(
          successMessage
        );
      }

      try {
        await this.$router.push({
          name: "order-management",

          query: {
            success: successMessage,

            order_id:
              order?.id ??
              this.editingOrderId ??
              "",
          },
        });

        return true;
      } catch (navigationError) {
        console.error(
          "Order saved but navigation failed:",
          navigationError
        );

        this.generalError =
          `${successMessage} ` +
          "The server operation is already saved. " +
          "Navigation failed, so do not submit this form again. " +
          "Open Order Management to view the latest server state.";

        return false;
      }
    },

    /*
    |--------------------------------------------------------------------------
    | SUBMIT ORDER
    |--------------------------------------------------------------------------
    */

    async submitOrder() {
      if (
        this.isSubmitting ||
        !this.canSubmit
      ) {
        return;
      }

      this.isSubmitting = true;

      this.generalError = "";
      this.validationErrors = {};

      try {
        /*
        |--------------------------------------------------------------------------
        | Completion-only retry
        |--------------------------------------------------------------------------
        */

        if (
          this.completionReadyWithoutPayment
        ) {
          try {
            const completedOrder =
              await this.completeServedOrderOnly();

            this.markCompletionCommitted(
              completedOrder
            );

            await this.redirectAfterOrderSuccess(
              completedOrder,
              "Order completed successfully."
            );
          } catch (completionError) {
            console.error(
              "Order completion retry failed:",
              completionError
            );

            this.handleCompletionFailure(
              completionError
            );
          }

          return;
        }

        /*
        |--------------------------------------------------------------------------
        | Build payload
        |--------------------------------------------------------------------------
        */

        const payload =
          this.buildPayload();

        let response;

        /*
        |--------------------------------------------------------------------------
        | Create / Update
        |--------------------------------------------------------------------------
        */

        if (this.isEditMode) {
          response =
            await orderService.updateOrder(
              this.editingOrderId,
              payload
            );
        } else {
          response =
            await orderService.createOrder(
              payload
            );
        }

        /*
        |--------------------------------------------------------------------------
        | Extract order
        |--------------------------------------------------------------------------
        */

        let order =
          response?.data?.data ??
          response?.data ??
          response;

        /*
        |--------------------------------------------------------------------------
        | Server mutation committed
        |--------------------------------------------------------------------------
        */

        this.markOperationCommitted(
          order
        );

        /*
        |--------------------------------------------------------------------------
        | Completion after payment
        |--------------------------------------------------------------------------
        */

        let completedFromPaymentFlow =
          false;

        if (
          this.isEditMode &&
          this.completionIntent &&
          !this.hasNewExtensionItems &&
          String(
            order?.status || ""
          ) === "served" &&
          Number(
            order?.due_amount ??
            this.dueAmount
          ) <= 0
        ) {
          /*
          |--------------------------------------------------------------------------
          | Sync payment before completion
          |--------------------------------------------------------------------------
          */

          this.syncPersistedPaymentState(
            order
          );

          try {
            order =
              await this.completeServedOrderOnly();

            completedFromPaymentFlow =
              true;

            this.markCompletionCommitted(
              order
            );
          } catch (completionError) {
            console.error(
              "Payment saved but order completion failed:",
              completionError
            );

            this.handleCompletionFailure(
              completionError,
              order
            );

            return;
          }
        }

        /*
        |--------------------------------------------------------------------------
        | Success message
        |--------------------------------------------------------------------------
        */

        let successMessage =
          "Order added successfully.";

        if (
          completedFromPaymentFlow
        ) {
          successMessage =
            "Payment received and order completed successfully.";
        } else if (this.isEditMode) {
          const addedKitchenItems =
            this.isServedEditMode &&
            this.hasNewExtensionItems;

          const serverPaidAmount =
            Number(order?.paid_amount);

          const addedPayment =
            Number.isFinite(
              serverPaidAmount
            )
              ? serverPaidAmount >
                this.recordedPaidAmount
              : this.additionalPaymentAmount >
                0;

          if (
            addedKitchenItems &&
            addedPayment
          ) {
            successMessage =
              "Order extended and payment updated successfully.";
          } else if (
            addedKitchenItems
          ) {
            successMessage =
              "Order extended successfully. New items were added to a new kitchen batch.";
          } else if (addedPayment) {
            successMessage =
              "Payment updated successfully.";
          } else {
            successMessage =
              "Order updated successfully.";
          }
        }

        /*
        |--------------------------------------------------------------------------
        | Redirect
        |--------------------------------------------------------------------------
        */

        await this.redirectAfterOrderSuccess(
          order,
          successMessage
        );
      } catch (error) {
        console.error(
          this.isEditMode
            ? "Order update failed:"
            : "Order create failed:",
          error
        );

        /*
        |--------------------------------------------------------------------------
        | Laravel validation
        |--------------------------------------------------------------------------
        */

        if (
          error?.response?.status === 422
        ) {
          this.applyValidationErrors(
            error
          );
        }

        /*
        |--------------------------------------------------------------------------
        | General error
        |--------------------------------------------------------------------------
        */

        this.generalError =
          this.getErrorMessage(
            error,
            this.isEditMode
              ? "Unable to update order."
              : "Unable to create order."
          );
      } finally {
        this.isSubmitting = false;
      }
    },

    /*
    |--------------------------------------------------------------------------
    | RESET FORM
    |--------------------------------------------------------------------------
    */

    async resetForm() {
      /*
      |--------------------------------------------------------------------------
      | Prevent reset after server mutation
      |--------------------------------------------------------------------------
      */

      if (this.operationCommitted) {
        this.generalError =
          "This server operation is already saved. Reload or open Order Management instead of restoring and submitting the same request again.";

        return;
      }

      /*
      |--------------------------------------------------------------------------
      | EDIT MODE
      |--------------------------------------------------------------------------
      */

      if (this.isEditMode) {
        this.validationErrors = {};
        this.generalError = "";

        this.completionRetryMode = false;

        this.selectedCustomer = null;
        this.customerResults = [];
        this.showCustomerResults = false;

        this.showMergedDropdown = false;
        this.activeAddonDropdown = null;

        if (this.originalOrder) {
          this.fillOrderForm(
            this.originalOrder
          );
        } else {
          await this.loadOrderForEdit();
        }

        return;
      }

      /*
      |--------------------------------------------------------------------------
      | CREATE MODE
      |--------------------------------------------------------------------------
      */

      this.form = {
        restaurant_table_id: "",

        merged_table_ids: [],

        customer_id: null,

        customer_name: "",

        customer_phone: "",

        customer_email: "",

        status: "pending",

        discount_amount: 0,

        tax_amount: 0,

        service_charge: 0,

        paid_amount: 0,

        payment_method: "",

        payment_reference: "",

        order_note: "",

        items: [],
      };

      /*
      |--------------------------------------------------------------------------
      | Reset payment state
      |--------------------------------------------------------------------------
      */

      this.recordedPaidAmount = 0;
      this.originalDueAmount = 0;

      /*
      |--------------------------------------------------------------------------
      | Reset operation states
      |--------------------------------------------------------------------------
      */

      this.operationCommitted = false;
      this.completionRetryMode = false;

      /*
      |--------------------------------------------------------------------------
      | Reset customer
      |--------------------------------------------------------------------------
      */

      this.selectedCustomer = null;
      this.customerResults = [];
      this.showCustomerResults = false;

      /*
      |--------------------------------------------------------------------------
      | Reset dropdowns
      |--------------------------------------------------------------------------
      */

      this.showMergedDropdown = false;
      this.activeAddonDropdown = null;

      /*
      |--------------------------------------------------------------------------
      | Reset errors
      |--------------------------------------------------------------------------
      */

      this.validationErrors = {};
      this.generalError = "";

      /*
      |--------------------------------------------------------------------------
      | Add empty item
      |--------------------------------------------------------------------------
      */

      this.form.items.push(
        this.createEmptyOrderItem()
      );
    },

    /*
    |--------------------------------------------------------------------------
    | MONEY FORMATTER
    |--------------------------------------------------------------------------
    */

    formatMoney(value) {
      return Number(
        value || 0
      ).toLocaleString(
        "en-BD",
        {
          minimumFractionDigits: 2,
          maximumFractionDigits: 2,
        }
      );
    },
  },
};