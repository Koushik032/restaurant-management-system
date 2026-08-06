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

      originalOrder: null,

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
        {
          value: "mixed",
          label: "Mixed Payment",
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
        return this.isEditMode
          ? "Update the existing restaurant order"
          : "Create a new dine-in restaurant order";
      },

      submitButtonLabel() {
        if (this.isSubmitting) {
          return this.isEditMode
            ? "Updating Order..."
            : "Creating Order...";
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

      return Math.min(
        amount,
        this.grandTotal
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

      const paymentValid =
        this.normalizedPaidAmount <=
        this.grandTotal;

      return (
        hasTable &&
        hasItems &&
        allItemsValid &&
        paymentValid
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
      structuredClone
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

  this.form.paid_amount =
    Number(order.paid_amount ?? 0);

  this.form.payment_method =
    order.payment_method ?? "";

  this.form.payment_reference =
    order.payment_reference ?? "";

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
    | Table methods
    |--------------------------------------------------------------------------
    */

    handlePrimaryTableChange() {
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
      if (
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

      if (!item) {
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

  if (!item || this.isSubmitting) {
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

  if (!item || this.isSubmitting) {
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

  if (!item) {
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
      let amount = Number(this.form.paid_amount);

      if (!Number.isFinite(amount) || amount < 0) {
        amount = 0;
      }

      this.form.paid_amount = amount;

      if (amount <= 0) {
        this.form.payment_method = "";
        this.form.payment_reference = "";
      }

      this.clearValidationField("paid_amount");
      this.clearValidationField("payment_method");
      this.clearValidationField("payment_reference");
    },

    normalizePaidAmount() {
      let amount = Number(this.form.paid_amount);

      if (!Number.isFinite(amount) || amount < 0) {
        amount = 0;
      }

      if (amount > this.grandTotal) {
        amount = this.grandTotal;
      }

      this.form.paid_amount = Number(
        amount.toFixed(2)
      );

      if (this.form.paid_amount <= 0) {
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
        (id) =>
          Number(id)
      ),



    /*
    |--------------------------------------------------------------------------
    | Customer
    |--------------------------------------------------------------------------
    */

    customer_id:
      this.form.customer_id
      ?
      Number(
        this.form.customer_id
      )
      :
      null,


    customer_name:
      this.form.customer_name
      || null,


    customer_phone:
      this.form.customer_phone
      || null,


    customer_email:
      this.form.customer_email
      || null,



    /*
    |--------------------------------------------------------------------------
    | Status
    |--------------------------------------------------------------------------
    */

    status:
      this.form.status,



    /*
    |--------------------------------------------------------------------------
    | Amount
    |--------------------------------------------------------------------------
    */

    discount_amount:
      Number(
        this.normalizedDiscount
      ) || 0,


    tax_amount:
      Number(
        this.form.tax_amount
      ) || 0,


    service_charge:
      Number(
        this.form.service_charge
      ) || 0,



    order_note:
      this.form.order_note
      || null,



    /*
    |--------------------------------------------------------------------------
    | Payment
    |--------------------------------------------------------------------------
    */

    paid_amount:
      Number(
        this.normalizedPaidAmount
      ) || 0,


    payment_method:
      this.normalizedPaidAmount > 0
        ?
        this.form.payment_method
        :
        null,


    payment_reference:
      this.normalizedPaidAmount > 0
        ?
        this.form.payment_reference
        :
        null,



    /*
    |--------------------------------------------------------------------------
    | Items
    |--------------------------------------------------------------------------
    */

    items:

      this.form.items.map(
        (item) => ({

          menu_item_id:
            Number(
              item.menu_item_id
            ),


          menu_item_variant_id:
            item.menu_item_variant_id
            ?
            Number(
              item.menu_item_variant_id
            )
            :
            null,


          addon_ids:

            (item.addon_ids || [])
              .map(
                (id) =>
                  Number(id)
              ),


          quantity:
            Math.max(
              Number(
                item.quantity
              ) || 1,
              1
            ),


          kitchen_note:
            item.kitchen_note
            || null,

        })
      ),

  };
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

    const order =
      response?.data?.data ??
      response?.data ??
      response;

    const successMessage =
      this.isEditMode
        ? "Order updated successfully."
        : "Order added successfully.";

    /*
    |--------------------------------------------------------------------------
    | Toast
    |--------------------------------------------------------------------------
    */

    if (this.$toast) {
      this.$toast.success(
        successMessage
      );
    }

    /*
    |--------------------------------------------------------------------------
    | Redirect to order management
    |--------------------------------------------------------------------------
    */

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
  | Edit mode: reload original order data
  |--------------------------------------------------------------------------
  */

  if (this.isEditMode) {
    this.validationErrors = {};
    this.generalError = "";
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