import orderService from "@/services/orderService";

export default {
  name: "CreateOrderView",

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

      /*
      |--------------------------------------------------------------------------
      | Existing payment snapshot
      |--------------------------------------------------------------------------
      |
      | In edit mode, paid_amount is cumulative because the backend payment ledger
      | is immutable. These values let the UI distinguish money already recorded
      | from a new payment being entered in this edit session.
      |
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
    computed: {
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
      pageMode() {
        return this.isEditMode
          ? "edit"
          : "create";
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

      /*
      |--------------------------------------------------------------------------
      | Completion-Only Retry
      |--------------------------------------------------------------------------
      |
      | When the immutable ledger already covers the full bill, completion must
      | call only the dedicated /complete endpoint. This prevents a retry from
      | re-running the order update/payment path.
      |
      */

      completionReadyWithoutPayment() {
        return (
          this.isServedEditMode &&
          this.completionIntent &&
          !this.hasNewExtensionItems &&
          this.dueAmount <= 0 &&
          this.additionalPaymentAmount <= 0
        );
      },

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
      return this.mergeTables.filter((table) =>
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
            total + Number(table.capacity || 0),
          0
        );

      return primaryCapacity + mergedCapacity;
    },

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
            : Number(menuItem.price || 0);

          return {
            row_id: item.row_id,

            name: variant
              ? `${menuItem.menu_name} (${variant.variant_name})`
              : menuItem.menu_name,

            quantity:
              Number(item.quantity) || 1,

            unit_price: unitPrice,

            addon_names: selectedAddons
              .map(
                (addon) => addon.add_on_name
              )
              .join(", "),

            total:
              this.calculateItemTotal(item),
          };
        })
        .filter(Boolean);
    },

    totalItemQuantity() {
      return this.summaryItems.reduce(
        (total, item) =>
          total + Number(item.quantity || 0),
        0
      );
    },

    subtotal() {
      return Number(
        this.form.items
          .reduce(
            (sum, item) =>
              sum + this.calculateItemTotal(item),
            0
          )
          .toFixed(2)
      );
    },

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
    | Payment
    |--------------------------------------------------------------------------
    */

    normalizedPaidAmount() {
      const amount = Number(
        this.form.paid_amount
      );

      if (
        !Number.isFinite(amount) ||
        amount <= 0
      ) {
        return 0;
      }

      // Do not silently clamp overpayment. The UI must show it as invalid
      // and the backend remains the final authority.
      return Number(
        amount.toFixed(2)
      );
    },

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

    paymentStatus() {
      if (
        this.normalizedPaidAmount <= 0
      ) {
        return "due";
      }

      if (this.dueAmount <= 0) {
        return "paid";
      }

      return "partially_paid";
    },

    paymentStatusLabel() {
      return {
        due: "Due",
        partially_paid:
          "Partially Paid",
        paid: "Paid",
      }[this.paymentStatus];
    },

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

      /*
      |--------------------------------------------------------------------------
      | Already-Committed Request Guard
      |--------------------------------------------------------------------------
      |
      | A successful API write must never be submitted again only because the
      | router/navigation failed afterwards. The only allowed follow-up after a
      | committed payment is the completion-only retry path.
      |
      */

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

async created() {
  /*
  |--------------------------------------------------------------------------
  | Detect create or edit route
  |--------------------------------------------------------------------------
  */

  const routeOrderId =
    this.$route.params.id ?? null;

  this.completionIntent =
    String(
      this.$route.query?.complete ?? ""
    ) === "1";

  if (routeOrderId) {
    this.isEditMode = true;

    this.editingOrderId = Number(
      routeOrderId
    );
  }

  /*
  |--------------------------------------------------------------------------
  | Add blank row only in create mode
  |--------------------------------------------------------------------------
  */

  if (!this.isEditMode) {
    this.form.items.push(
      this.createEmptyOrderItem()
    );
  }

  /*
  |--------------------------------------------------------------------------
  | Load tables, menu items, variants and add-ons
  |--------------------------------------------------------------------------
  */

  await this.loadCreateOptions();

  /*
  |--------------------------------------------------------------------------
  | Existing order data will be loaded in the next step
  |--------------------------------------------------------------------------
  */

  if (this.isEditMode) {
    await this.loadOrderForEdit();
  }
},

  mounted() {
    document.addEventListener(
      "click",
      this.handleDocumentClick
    );
  },

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

  methods: {

async loadOrderForEdit() {
  if (!this.editingOrderId) {
    return;
  }

  this.isLoading = true;
  this.generalError = "";
  this.completionRetryMode = false;
  this.operationCommitted = false;

  try {
    /*
    |--------------------------------------------------------------------------
    | Load edit options
    |--------------------------------------------------------------------------
    */

    const response =
      await orderService.getEditOptions(
        this.editingOrderId
      );

    const responseData =
      response?.data?.data ??
      response?.data ??
      {};

    /*
    |--------------------------------------------------------------------------
    | Order
    |--------------------------------------------------------------------------
    */

    const order =
      responseData.order ??
      null;

    if (!order) {
      throw new Error(
        "Order information was not found."
      );
    }

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

    this.tables = Array.isArray(
      responseData.tables
    )
      ? responseData.tables
      : [];

    this.mergeTables = Array.isArray(
      responseData.merge_tables
    )
      ? responseData.merge_tables
      : this.tables;

    /*
    |--------------------------------------------------------------------------
    | Menu items
    |--------------------------------------------------------------------------
    */

    this.menuItems = Array.isArray(
      responseData.menu_items
    )
      ? responseData.menu_items
      : [];

    /*
    |--------------------------------------------------------------------------
    | Global add-ons
    |--------------------------------------------------------------------------
    */

    this.addons = Array.isArray(
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
    | Fill form
    |--------------------------------------------------------------------------
    */

    this.fillOrderForm(order);
  } catch (error) {
    console.error(
      "Order edit load failed:",
      error
    );

    this.generalError =
      this.getErrorMessage(
        error,
        "Unable to load order edit information."
      );
  } finally {
    this.isLoading = false;
  }
},
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
  | Table
  |--------------------------------------------------------------------------
  */

  this.form.restaurant_table_id =
    order.primary_table?.id
      ? String(order.primary_table.id)
      : "";

  this.form.merged_table_ids =
    Array.isArray(order.merged_tables)
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
    Number(order.discount_amount ?? 0);

  this.form.tax_amount =
    Number(order.tax_amount ?? 0);

  this.form.service_charge =
    Number(order.service_charge ?? 0);

  /*
  |--------------------------------------------------------------------------
  | Payment
  |--------------------------------------------------------------------------
  */

  this.recordedPaidAmount =
    Number(order.paid_amount ?? 0);

  this.originalDueAmount =
    Number(order.due_amount ?? 0);

  this.form.paid_amount =
    this.recordedPaidAmount;

  /*
  | Existing order.payment_method may be "mixed", which is only a summary value.
  | A new immutable payment row must use one concrete transaction method.
  | Therefore edit mode starts with an empty method/reference and only requires
  | them when paid_amount is increased above recordedPaidAmount.
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

  this.form.items = orderItems.map(
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
            ? String(item.menu_item_id)
            : "",

        menu_item_variant_id:
          item.menu_item_variant_id
            ? String(
                item.menu_item_variant_id
              )
            : "",

        addon_ids: addonIds,

        quantity: Math.max(
          Number(item.quantity) || 1,
          1
        ),

        kitchen_note:
          item.kitchen_note ?? "",
      };
    }
  );

  if (!this.form.items.length) {
    this.form.items.push(
      this.createEmptyOrderItem()
    );
  }

  /*
  |--------------------------------------------------------------------------
  | UI states
  |--------------------------------------------------------------------------
  */

  this.showMergedDropdown = false;
  this.activeAddonDropdown = null;
  this.validationErrors = {};
  this.generalError = "";
},
     /*
    |--------------------------------------------------------------------------
    | Empty order item
    |--------------------------------------------------------------------------
    */

    createEmptyOrderItem() {
      return {
        row_id: `${Date.now()}-${Math.random()
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
    | Load create-order options
    |--------------------------------------------------------------------------
    */

    async loadCreateOptions() {
  this.isLoading = true;
  this.generalError = "";

  try {
    const response =
      await orderService.getCreateOptions();

    const responseData =
      response?.data?.data ??
      response?.data ??
      {};

    /*
    |--------------------------------------------------------------------------
    | Available tables
    |--------------------------------------------------------------------------
    */

    this.tables = Array.isArray(
      responseData.tables
    )
      ? responseData.tables
      : [];

    /*
    |--------------------------------------------------------------------------
    | Merge tables
    |--------------------------------------------------------------------------
    */

    this.mergeTables = Array.isArray(
      responseData.merge_tables
    )
      ? responseData.merge_tables
      : Array.isArray(responseData.tables)
        ? responseData.tables
        : [];

    /*
    |--------------------------------------------------------------------------
    | Menu items
    |--------------------------------------------------------------------------
    */

    this.menuItems = Array.isArray(
      responseData.menu_items
    )
      ? responseData.menu_items
      : [];

    /*
    |--------------------------------------------------------------------------
    | Global add-ons
    |--------------------------------------------------------------------------
    */

    this.addons = Array.isArray(
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
    | Logged-in waiter
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
  } catch (error) {
    this.tables = [];
    this.mergeTables = [];
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

    normalizeStatuses(statuses) {
      if (Array.isArray(statuses)) {
        return statuses
          .map((status) => {
            if (
              typeof status === "string"
            ) {
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
        return Object.entries(statuses).map(
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

    formatStatusLabel(status) {
      return String(status || "")
        .replace(/_/g, " ")
        .replace(/\b\w/g, (letter) =>
          letter.toUpperCase()
        );
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

      this.form.merged_table_ids =
        this.form.merged_table_ids.filter(
          (tableId) =>
            String(tableId) !==
            String(
              this.form.restaurant_table_id
            )
        );

      this.showMergedDropdown = false;
    },

    getTableDisplayName(table) {
      if (!table) {
        return "";
      }

      const name =
        table.table_name ??
        table.name ??
        `Table ${table.id}`;

      const section =
        table.section_label ??
        table.section_name ??
        table.section?.section_name ??
        table.section?.name ??
        "";

      const capacity = Number(
        table.capacity || 0
      );

      const details = [];

      if (section) {
        details.push(section);
      }

      if (capacity > 0) {
        details.push(
          `${capacity} ${
            capacity === 1
              ? "seat"
              : "seats"
          }`
        );
      }

      return details.length
        ? `${name} — ${details.join(" · ")}`
        : name;
    },

    toggleMergedDropdown() {
      if (
        this.isServedEditMode ||
        !this.form.restaurant_table_id ||
        this.isSubmitting
      ) {
        return;
      }

      this.showMergedDropdown =
        !this.showMergedDropdown;

      this.activeAddonDropdown = null;
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

    isMergeTableSelected(tableId) {
      return this.form.merged_table_ids.some(
        (selectedId) =>
          String(selectedId) ===
          String(tableId)
      );
    },

    /*
    |--------------------------------------------------------------------------
    | Customer methods
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
        this.form.customer_name.trim();

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
        this.form.customer_name.trim()
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

        const customers = Array.isArray(
          responseData
        )
          ? responseData
          : Array.isArray(
                responseData.customers
              )
            ? responseData.customers
            : [];

        this.customerResults = customers;

        this.showCustomerResults = true;
      } catch (error) {
        this.customerResults = [];
        this.showCustomerResults = false;
      } finally {
        this.isSearchingCustomers = false;
      }
    },

    selectCustomer(customer) {
      if (this.isServedEditMode) {
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
      const name = String(
        customer?.name || "C"
      ).trim();

      return name
        .charAt(0)
        .toUpperCase();
    },

    /*
    |--------------------------------------------------------------------------
    | Item row methods
    |--------------------------------------------------------------------------
    */

    addOrderItem() {

  this.form.items.push(
    this.createEmptyOrderItem()
  );


  this.clearValidationField(
    "items"
  );


  this.$nextTick(() => {

    const tableWrapper =
      document.querySelector(
        ".order-items-table-wrapper"
      );


    if (tableWrapper) {

      tableWrapper.scrollLeft =
        tableWrapper.scrollWidth;

    }

  });

},

    removeOrderItem(index) {
      const item = this.form.items[index];

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

      this.removeItemValidationErrors(
        index
      );
    },

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

    getItemVariants(item) {
      const menuItem =
        this.getSelectedMenuItem(item);

      if (!menuItem) {
        return [];
      }

      const variants =
        menuItem.variants ??
        menuItem.menu_item_variants ??
        [];

      return Array.isArray(variants)
        ? variants.filter(
            (variant) =>
              variant.is_available !==
                false &&
              variant.status !==
                "unavailable"
          )
        : [];
    },

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

getItemAddons() {

  return Array.isArray(this.addons)
    ? this.addons
    : [];

},

    getSelectedAddons(item) {
      if (
        !Array.isArray(item?.addon_ids) ||
        !item.addon_ids.length
      ) {
        return [];
      }

      return this.getItemAddons(
        item
      ).filter((addon) =>
        item.addon_ids.some(
          (addonId) =>
            String(addonId) ===
            String(addon.id)
        )
      );
    },

toggleAddonDropdown(index) {
  const item = this.form.items[index];

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

    getSelectedAddonLabel(item) {
      const selectedAddons =
        this.getSelectedAddons(item);

      if (!selectedAddons.length) {
        return this.getItemAddons(item)
          .length
          ? "Select add-ons"
          : "No add-ons";
      }

      if (
        selectedAddons.length === 1
      ) {
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
    | Quantity methods
    |--------------------------------------------------------------------------
    */

increaseQuantity(index) {
  const item = this.form.items[index];

  if (
    !item ||
    this.isItemLocked(item) ||
    this.isSubmitting
  ) {
    return;
  }

  const currentQuantity = Number(
    item.quantity
  );

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

decreaseQuantity(index) {
  const item = this.form.items[index];

  if (
    !item ||
    this.isItemLocked(item) ||
    this.isSubmitting
  ) {
    return;
  }

  const currentQuantity = Number(
    item.quantity
  );

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

    normalizeQuantity(index) {
  const item = this.form.items[index];

  if (
    !item ||
    this.isItemLocked(item)
  ) {
    return;
  }

  let quantity = Number(item.quantity);

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
    | Price calculation methods
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

    getItemAddonsTotal(item) {
      return this.getSelectedAddons(
        item
      ).reduce(
        (total, addon) =>
          total +
          Number(addon.price || 0),
        0
      );
    },

    calculateItemTotal(item) {
      if (!item?.menu_item_id) {
        return 0;
      }

      const basePrice =
        this.getItemBasePrice(item);

      const addonsTotal =
        this.getItemAddonsTotal(item);

      const quantity = Math.max(
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

    normalizeDiscount() {
      let discount = Number(
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
    | Payment methods
    |--------------------------------------------------------------------------
    */

    handlePaidAmountInput() {
      let amount = Number(
        this.form.paid_amount
      );

      if (
        !Number.isFinite(amount) ||
        amount < 0
      ) {
        amount = 0;
      }

      this.form.paid_amount = amount;

      if (
        !this.requiresNewPaymentMethod
      ) {
        this.form.payment_method = "";
        this.form.payment_reference = "";
      }

      this.clearValidationField("paid_amount");
      this.clearValidationField("payment_method");
      this.clearValidationField("payment_reference");
    },

    normalizePaidAmount() {
      let amount = Number(
        this.form.paid_amount
      );

      if (
        !Number.isFinite(amount) ||
        amount < 0
      ) {
        amount = 0;
      }

      this.form.paid_amount =
        Number(amount.toFixed(2));

      if (
        !this.requiresNewPaymentMethod
      ) {
        this.form.payment_method = "";
        this.form.payment_reference = "";
      }

      this.clearValidationField("paid_amount");
    },

    getPaymentStatusClass(status) {
      switch (status) {
        case "paid":
          return "status-paid";

        case "partially_paid":
          return "status-partial";

        default:
          return "status-due";
      }
    },

    /*
    |--------------------------------------------------------------------------
    | Validation methods
    |--------------------------------------------------------------------------
    */

    getFieldError(field) {
      if (!this.validationErrors) {
        return "";
      }

      return (
        this.validationErrors[field] ||
        ""
      );
    },

    clearValidationField(field) {
      if (
        this.validationErrors &&
        this.validationErrors[field]
      ) {
        delete this.validationErrors[field];

        this.validationErrors = {
          ...this.validationErrors,
        };
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

      this.validationErrors =
        updated;
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
    | Helper methods
    |--------------------------------------------------------------------------
    */

    getErrorMessage(
      error,
      fallback =
        "Something went wrong."
    ) {
      return (
        error?.response?.data?.message ||
        error?.message ||
        fallback
      );
    },

    /*
    |--------------------------------------------------------------------------
    | Dropdown handlers
    |--------------------------------------------------------------------------
    */

    handleDocumentClick(event) {
      /*
      -----------------------
      Merge table dropdown
      -----------------------
      */

      if (
        this.showMergedDropdown &&
        this.$refs.mergedDropdownRef &&
        !this.$refs.mergedDropdownRef.contains(
          event.target
        )
      ) {
        this.showMergedDropdown =
          false;
      }

      /*
      -----------------------
      Customer dropdown
      -----------------------
      */

      const customerWrapper =
        event.target.closest(
          ".customer-input-wrapper"
        );

      if (!customerWrapper) {
        this.showCustomerResults =
          false;
      }

      /*
      -----------------------
      Add-on dropdown
      -----------------------
      */

      const addonWrapper =
        event.target.closest(
          ".item-addon-dropdown"
        );

      if (!addonWrapper) {
        this.activeAddonDropdown =
          null;
      }
    },

    goBack() {
      this.$router.back();
    },

    /*
    |--------------------------------------------------------------------------
    | Payload builder
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
          this.form.merged_table_ids.map(
            (id) => Number(id)
          ),

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
        |
        | The backend remains authoritative. For a served extension, adding a new
        | item creates the next pending kitchen batch automatically.
        |
        */

        status: this.form.status,

        /*
        |--------------------------------------------------------------------------
        | Amount
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
        | paid_amount is cumulative. The backend subtracts the immutable ledger
        | total and creates only the new payment difference.
        |
        */

        paid_amount:
          Number(this.normalizedPaidAmount) || 0,

        payment_method:
          this.requiresNewPaymentMethod
            ? this.form.payment_method
            : null,

        payment_reference:
          this.requiresNewPaymentMethod
            ? this.form.payment_reference || null
            : null,

        /*
        |--------------------------------------------------------------------------
        | Items
        |--------------------------------------------------------------------------
        |
        | Existing served rows carry order_item_id and must be returned unchanged.
        | New extension rows intentionally have no order_item_id, so the backend
        | assigns them to a newly created kitchen batch.
        |
        */

        items:
          this.form.items.map(
            (item) => ({
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
                (item.addon_ids || [])
                  .map((id) => Number(id)),

              quantity:
                Math.max(
                  Number(item.quantity) || 1,
                  1
                ),

              kitchen_note:
                item.kitchen_note || null,
            })
          ),
      };
    },

        /*
    |--------------------------------------------------------------------------
    | Sync Persisted Payment State
    |--------------------------------------------------------------------------
    |
    | updateOrder() commits before completeOrder() runs. If completion then
    | fails, the payment ledger must be treated as already saved locally too.
    | This prevents a retry from being represented as a second payment.
    |
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
      | Never Re-submit A Summary/Old Payment Method
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
      | Update Reset Snapshot
      |--------------------------------------------------------------------------
      |
      | "Restore Original" must now restore the already-persisted payment, not
      | the stale pre-payment value.
      |
      */

      this.originalOrder = {
        ...(this.originalOrder || {}),
        ...order,

        paid_amount:
          normalizedPaid,

        due_amount:
          normalizedDue,

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
    | Finalize Completion Only
    |--------------------------------------------------------------------------
    |
    | Used when the bill is already fully covered by the immutable ledger.
    | No order update and no payment write occurs here.
    |
    */

    async completeServedOrderOnly() {
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
    | Completion Failure After Persisted Payment
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
        `Payment state is safe and no duplicate payment will be recorded. ` +
        `${backendMessage} Click "Retry Complete" to retry only the final completion step.`;
    },

    /*
    |--------------------------------------------------------------------------
    | Mark Server Mutation Committed
    |--------------------------------------------------------------------------
    |
    | Once create/update succeeds, a later UI/navigation error must not make the
    | same business mutation submit again.
    |
    */

    markOperationCommitted(
      order = null
    ) {
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
    | Mark Final Completion Committed
    |--------------------------------------------------------------------------
    */

    markCompletionCommitted(
      order = null
    ) {
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
            order.status ||
            "completed",
        };
      } else if (
        this.originalOrder
      ) {
        this.originalOrder = {
          ...this.originalOrder,
          status: "completed",
        };
      }
    },

    /*
    |--------------------------------------------------------------------------
    | Redirect After Success
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
            success:
              successMessage,

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
          `${successMessage} The server operation is already saved. ` +
          `Navigation failed, so do not submit this form again. ` +
          `Open Order Management to view the latest server state.`;

        return false;
      }
    },


    /*
    |--------------------------------------------------------------------------
    | Submit order
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
    | Completion-only path
    |--------------------------------------------------------------------------
    |
    | If the immutable ledger already covers the total, do NOT run updateOrder
    | again. This is especially important after a successful payment followed
    | by a failed completion request.
    |
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
    | Create or update order
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
    | Extract order data safely
    |--------------------------------------------------------------------------
    */

    let order =
      response?.data?.data ??
      response?.data ??
      response;

    /*
    |--------------------------------------------------------------------------
    | Server Write Is Already Committed
    |--------------------------------------------------------------------------
    |
    | From this point onward, a router/toast/UI failure must never cause the
    | create/update mutation to be submitted again.
    |
    */

    this.markOperationCommitted(
      order
    );

    /*
    |--------------------------------------------------------------------------
    | Completion intent after payment
    |--------------------------------------------------------------------------
    |
    | updateOrder() is already committed at this point. Therefore the returned
    | finance snapshot is immediately synchronized locally BEFORE calling the
    | separate /complete endpoint.
    |
    */

    let completedFromPaymentFlow = false;

    if (
      this.isEditMode &&
      this.completionIntent &&
      !this.hasNewExtensionItems &&
      String(order?.status || "") ===
        "served" &&
      Number(
        order?.due_amount ??
        this.dueAmount
      ) <= 0
    ) {
      this.syncPersistedPaymentState(
        order
      );

      try {
        order =
          await this.completeServedOrderOnly();

        completedFromPaymentFlow = true;

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

    if (completedFromPaymentFlow) {
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
          : this.additionalPaymentAmount > 0;

      if (
        addedKitchenItems &&
        addedPayment
      ) {
        successMessage =
          "Order extended and payment updated successfully.";
      } else if (addedKitchenItems) {
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
    | Laravel validation errors
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
    | Reset form
    |--------------------------------------------------------------------------
    */

    async resetForm() {
  /*
  |--------------------------------------------------------------------------
  | Already-Committed Request Protection
  |--------------------------------------------------------------------------
  */

  if (this.operationCommitted) {
    this.generalError =
      "This server operation is already saved. Reload or open Order Management instead of restoring and submitting the same request again.";

    return;
  }

  /*
  |--------------------------------------------------------------------------
  | Edit mode: reload original order data
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
  | Create mode: clear the form
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

    items: [
      this.createEmptyOrderItem(),
    ],
  };

  this.recordedPaidAmount = 0;
  this.originalDueAmount = 0;
  this.operationCommitted = false;
  this.completionRetryMode = false;

  this.selectedCustomer = null;
  this.customerResults = [];
  this.showCustomerResults = false;
  this.showMergedDropdown = false;
  this.activeAddonDropdown = null;
  this.validationErrors = {};
  this.generalError = "";
},


    /*
    |--------------------------------------------------------------------------
    | Money formatter
    |--------------------------------------------------------------------------
    */

    formatMoney(value) {

      return Number(
        value || 0
      ).toLocaleString(
        "en-BD",
        {
          minimumFractionDigits:
            2,

          maximumFractionDigits:
            2,
        }
      );
    },

  },
};