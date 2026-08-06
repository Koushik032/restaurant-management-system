/*
|--------------------------------------------------------------------------
| Order Details API Service
|--------------------------------------------------------------------------
*/

import {
  getOrderDetails,
  getOrderApiErrorMessage,
} from "@/services/orderDetailsService";

/*
|--------------------------------------------------------------------------
| Main Order Service
|--------------------------------------------------------------------------
|
| Invoice PDF download করার জন্য orderService ব্যবহার হবে।
|
*/

import orderService from "@/services/orderService";

/*
|--------------------------------------------------------------------------
| Order Details View
|--------------------------------------------------------------------------
*/

export default {
  name: "OrderDetailsView",

  /*
  |--------------------------------------------------------------------------
  | Component State
  |--------------------------------------------------------------------------
  */

  data() {
    return {
      /*
      |--------------------------------------------------------------------------
      | Page Loading State
      |--------------------------------------------------------------------------
      */

      isLoading: true,

      /*
      |--------------------------------------------------------------------------
      | Invoice Download State
      |--------------------------------------------------------------------------
      */

      downloadingInvoice: false,

      /*
      |--------------------------------------------------------------------------
      | Page Messages
      |--------------------------------------------------------------------------
      */

      errorMessage: "",

      successMessage: "",

      /*
      |--------------------------------------------------------------------------
      | Current Order
      |--------------------------------------------------------------------------
      */

      order: null,

      /*
      |--------------------------------------------------------------------------
      | Message Timer
      |--------------------------------------------------------------------------
      |
      | Success message automatically clear করার জন্য timer reference।
      |
      */

      messageTimer: null,
    };
  },

  /*
  |--------------------------------------------------------------------------
  | Computed Properties
  |--------------------------------------------------------------------------
  */

  computed: {
    /*
    |--------------------------------------------------------------------------
    | Route Order ID
    |--------------------------------------------------------------------------
    |
    | Current URL-এর :id parameter থেকে order ID resolve করে।
    |
    */

    orderId() {
      const routeOrderId =
        Number(
          this.$route.params.id
        );

      if (
        !Number.isInteger(
          routeOrderId
        ) ||
        routeOrderId <= 0
      ) {
        return null;
      }

      return routeOrderId;
    },

    /*
    |--------------------------------------------------------------------------
    | Order Item Quantity
    |--------------------------------------------------------------------------
    |
    | সব ordered item-এর quantity যোগ করে total quantity return করে।
    |
    */

    totalItemQuantity() {
      if (
        !Array.isArray(
          this.order?.items
        )
      ) {
        return 0;
      }

      return this.order.items.reduce(
        (
          total,
          item
        ) => {
          return (
            total +
            this.toNumber(
              item?.quantity
            )
          );
        },
        0
      );
    },

    /*
    |--------------------------------------------------------------------------
    | Total Item Lines
    |--------------------------------------------------------------------------
    |
    | Order-এ কয়টি আলাদা menu item line আছে সেটা return করে।
    |
    */

    totalItemLines() {
      if (
        !Array.isArray(
          this.order?.items
        )
      ) {
        return 0;
      }

      return this.order.items.length;
    },

    /*
    |--------------------------------------------------------------------------
    | Created Date
    |--------------------------------------------------------------------------
    */

    formattedCreatedDate() {
      return this.formatDate(
        this.order?.created_at
      );
    },

    /*
    |--------------------------------------------------------------------------
    | Created Time
    |--------------------------------------------------------------------------
    */

    formattedCreatedTime() {
      return this.formatTime(
        this.order?.created_at
      );
    },

    /*
    |--------------------------------------------------------------------------
    | Created Day
    |--------------------------------------------------------------------------
    */

    formattedCreatedDay() {
      const createdAt =
        this.order?.created_at;

      if (!createdAt) {
        return "Not available";
      }

      const date =
        new Date(createdAt);

      if (
        Number.isNaN(
          date.getTime()
        )
      ) {
        return "Not available";
      }

      return new Intl.DateTimeFormat(
        "en-GB",
        {
          weekday: "long",
        }
      ).format(date);
    },

    /*
    |--------------------------------------------------------------------------
    | Can Download Invoice
    |--------------------------------------------------------------------------
    |
    | Valid order load হলে এবং invoice download চলমান না থাকলে button active।
    |
    */

    canDownloadInvoice() {
      return Boolean(
        this.order?.id
      ) &&
        !this.downloadingInvoice;
    },

    /*
    |--------------------------------------------------------------------------
    | Invoice Button Label
    |--------------------------------------------------------------------------
    */

    invoiceButtonLabel() {
      return this.downloadingInvoice
        ? "Downloading..."
        : "Download Invoice";
    },

    /*
    |--------------------------------------------------------------------------
    | Customer Display Name
    |--------------------------------------------------------------------------
    */

    customerDisplayName() {
      return (
        this.order?.customer?.name ||
        "Walk-in Customer"
      );
    },

    /*
    |--------------------------------------------------------------------------
    | Primary Table Name
    |--------------------------------------------------------------------------
    */

    primaryTableName() {
      return (
        this.order
          ?.primary_table
          ?.table_name ||
        "No Table"
      );
    },

    /*
    |--------------------------------------------------------------------------
    | Merged Table Names
    |--------------------------------------------------------------------------
    */

    mergedTableNames() {
      if (
        this.order
          ?.merged_table_names
      ) {
        return this.order
          .merged_table_names;
      }

      if (
        !Array.isArray(
          this.order
            ?.merged_tables
        )
      ) {
        return "";
      }

      return this.order
        .merged_tables
        .map(
          (table) =>
            table?.table_name
        )
        .filter(Boolean)
        .join(", ");
    },

    /*
    |--------------------------------------------------------------------------
    | Payment Progress
    |--------------------------------------------------------------------------
    |
    | Paid amount মোট amount-এর কত শতাংশ, সেটা calculate করে।
    |
    */

    paymentProgress() {
      const total =
        this.toNumber(
          this.order
            ?.total_amount
        );

      const paid =
        this.toNumber(
          this.order
            ?.paid_amount
        );

      if (total <= 0) {
        return 0;
      }

      return Math.min(
        Math.max(
          Math.round(
            (
              paid /
              total
            ) * 100
          ),
          0
        ),
        100
      );
    },

    /*
    |--------------------------------------------------------------------------
    | Timeline Steps
    |--------------------------------------------------------------------------
    |
    | Order lifecycle timeline-এর জন্য ready-to-render array return করে।
    |
    */

    timelineSteps() {
      if (!this.order) {
        return [];
      }

      return [
        {
          key: "created",
          label:
            "Order Created",
          icon:
            "bi bi-receipt",
          date:
            this.order
              .created_at,
          completed:
            Boolean(
              this.order
                .created_at
            ),
        },

        {
          key: "kitchen",
          label:
            "Sent to Kitchen",
          icon:
            "bi bi-fire",
          date:
            this.order
              .sent_to_kitchen_at,
          completed:
            Boolean(
              this.order
                .sent_to_kitchen_at
            ),
        },

        {
          key: "preparing",
          label:
            "Preparing",
          icon:
            "bi bi-hourglass-split",
          date:
            this.order
              .preparing_at,
          completed:
            Boolean(
              this.order
                .preparing_at
            ),
        },

        {
          key: "ready",
          label:
            "Ready",
          icon:
            "bi bi-check2-circle",
          date:
            this.order
              .ready_at,
          completed:
            Boolean(
              this.order
                .ready_at
            ),
        },

        {
          key: "served",
          label:
            "Order Served",
          icon:
            "bi bi-cup-hot",
          date:
            this.order
              .served_at,
          completed:
            Boolean(
              this.order
                .served_at
            ),
        },

        {
          key: "completed",
          label:
            "Completed",
          icon:
            "bi bi-patch-check",
          date:
            this.order
              .completed_at,
          completed:
            Boolean(
              this.order
                .completed_at
            ),
        },

        {
          key: "canceled",
          label:
            "Canceled",
          icon:
            "bi bi-x-circle",
          date:
            this.order
              .canceled_at,
          completed:
            Boolean(
              this.order
                .canceled_at
            ),
        },
      ];
    },
  },
    /*
  |--------------------------------------------------------------------------
  | Initial Page Load
  |--------------------------------------------------------------------------
  |
  | Component তৈরি হলে current route ID অনুযায়ী order details load করবে।
  |
  */

  async created() {
    await this.loadOrderDetails();
  },

  /*
  |--------------------------------------------------------------------------
  | Route Watcher
  |--------------------------------------------------------------------------
  |
  | একই component active থাকা অবস্থায় route-এর order ID পরিবর্তন হলে
  | নতুন order details আবার load করবে।
  |
  */

  watch: {
    "$route.params.id": {
      async handler(
        newOrderId,
        oldOrderId
      ) {
        if (
          String(newOrderId) ===
          String(oldOrderId)
        ) {
          return;
        }

        await this.loadOrderDetails();
      },
    },
  },

  /*
  |--------------------------------------------------------------------------
  | Component Cleanup
  |--------------------------------------------------------------------------
  |
  | Component destroy হওয়ার আগে success message timer clear করবে।
  |
  */

  beforeUnmount() {
    if (this.messageTimer) {
      clearTimeout(
        this.messageTimer
      );

      this.messageTimer = null;
    }
  },

  /*
  |--------------------------------------------------------------------------
  | Component Methods
  |--------------------------------------------------------------------------
  */

  methods: {
    /*
    |--------------------------------------------------------------------------
    | Load Order Details
    |--------------------------------------------------------------------------
    |
    | Backend থেকে current order-এর complete details load করে।
    |
    */

    async loadOrderDetails() {
      if (!this.orderId) {
        this.order = null;

        this.errorMessage =
          "Order ID was not found.";

        this.isLoading = false;

        return;
      }

      this.isLoading = true;
      this.errorMessage = "";
      this.successMessage = "";
      this.order = null;

      try {
        const order =
          await getOrderDetails(
            this.orderId
          );

        if (
          !order ||
          !order.id
        ) {
          throw new Error(
            "Order information was not found."
          );
        }

        this.order =
          this.normalizeOrder(
            order
          );
      } catch (error) {
        console.error(
          "Order details load failed:",
          error
        );

        this.errorMessage =
          getOrderApiErrorMessage(
            error,
            "Unable to load order details."
          );
      } finally {
        this.isLoading = false;
      }
    },

    /*
    |--------------------------------------------------------------------------
    | Normalize Order Response
    |--------------------------------------------------------------------------
    |
    | API response-এর missing field, array এবং numeric valueগুলো safe format-এ
    | convert করে, যেন template-এ undefined error না আসে।
    |
    */

    normalizeOrder(order) {
      return {
        ...order,

        customer: {
          id:
            order?.customer?.id ??
            null,

          name:
            order?.customer?.name ??
            "",

          phone:
            order?.customer?.phone ??
            "",

          email:
            order?.customer?.email ??
            "",
        },

        primary_table:
          order?.primary_table ??
          null,

        merged_tables:
          Array.isArray(
            order?.merged_tables
          )
            ? order.merged_tables
            : [],

        merged_table_names:
          order?.merged_table_names ??
          "",

        creator:
          order?.creator &&
          typeof order.creator ===
            "object"
            ? order.creator
            : null,

        items:
          Array.isArray(
            order?.items
          )
            ? order.items.map(
                (item) =>
                  this.normalizeOrderItem(
                    item
                  )
              )
            : [],

        payments:
          Array.isArray(
            order?.payments
          )
            ? order.payments.map(
                (payment) =>
                  this.normalizePayment(
                    payment
                  )
              )
            : [],

        subtotal:
          this.toNumber(
            order?.subtotal
          ),

        discount_amount:
          this.toNumber(
            order?.discount_amount
          ),

        tax_amount:
          this.toNumber(
            order?.tax_amount
          ),

        service_charge:
          this.toNumber(
            order?.service_charge
          ),

        total_amount:
          this.toNumber(
            order?.total_amount
          ),

        paid_amount:
          this.toNumber(
            order?.paid_amount
          ),

        due_amount:
          this.toNumber(
            order?.due_amount
          ),

        payment_breakdown:
          order?.payment_breakdown &&
          typeof order.payment_breakdown ===
            "object"
            ? order.payment_breakdown
            : null,

        order_note:
          order?.order_note ??
          "",

        kitchen_note:
          order?.kitchen_note ??
          "",

        cancellation_reason:
          order?.cancellation_reason ??
          "",
      };
    },

    /*
    |--------------------------------------------------------------------------
    | Normalize Order Item
    |--------------------------------------------------------------------------
    |
    | Item price, quantity, add-ons এবং line total safe numeric format-এ রাখে।
    |
    */

    normalizeOrderItem(item) {
      return {
        ...item,

        id:
          item?.id ??
          null,

        menu_item_id:
          item?.menu_item_id ??
          null,

        menu_item_variant_id:
          item?.menu_item_variant_id ??
          null,

        item_name:
          item?.item_name ??
          "Menu Item",

        variant_name:
          item?.variant_name ??
          "",

        unit_price:
          this.toNumber(
            item?.unit_price
          ),

        quantity:
          Math.max(
            this.toNumber(
              item?.quantity
            ),
            0
          ),

        addon_total:
          this.toNumber(
            item?.addon_total
          ),

        line_total:
          this.toNumber(
            item?.line_total
          ),

        status:
          item?.status ??
          "",

        kitchen_note:
          item?.kitchen_note ??
          "",

        addons:
          Array.isArray(
            item?.addons
          )
            ? item.addons.map(
                (addon) =>
                  this.normalizeOrderAddon(
                    addon
                  )
              )
            : [],
      };
    },

    /*
    |--------------------------------------------------------------------------
    | Normalize Order Item Add-on
    |--------------------------------------------------------------------------
    */

    normalizeOrderAddon(addon) {
      return {
        ...addon,

        id:
          addon?.id ??
          null,

        menu_addon_id:
          addon?.menu_addon_id ??
          null,

        addon_name:
          addon?.addon_name ??
          "Add-on",

        unit_price:
          this.toNumber(
            addon?.unit_price
          ),

        quantity:
          Math.max(
            this.toNumber(
              addon?.quantity
            ),
            0
          ),

        total_price:
          this.toNumber(
            addon?.total_price
          ),
      };
    },

    /*
    |--------------------------------------------------------------------------
    | Normalize Payment
    |--------------------------------------------------------------------------
    */

    normalizePayment(payment) {
      return {
        ...payment,

        id:
          payment?.id ??
          null,

        order_id:
          payment?.order_id ??
          null,

        amount:
          this.toNumber(
            payment?.amount
          ),

        payment_method:
          payment?.payment_method ??
          "",

        reference:
          payment?.reference ??
          "",

        note:
          payment?.note ??
          "",

        received_by:
          payment?.received_by ??
          null,

        received_by_name:
          payment?.received_by_name ??
          "",

        created_at:
          payment?.created_at ??
          "",
      };
    },

    /*
    |--------------------------------------------------------------------------
    | Safe Numeric Conversion
    |--------------------------------------------------------------------------
    |
    | Invalid, null বা undefined numeric value-এর পরিবর্তে 0 return করে।
    |
    */

    toNumber(value) {
      const numericValue =
        Number(value);

      return Number.isFinite(
        numericValue
      )
        ? numericValue
        : 0;
    },
        /*
    |--------------------------------------------------------------------------
    | Download Customer Invoice
    |--------------------------------------------------------------------------
    |
    | Backend invoice endpoint থেকে PDF download করে।
    |
    */

    async downloadInvoice() {
      if (
        this.downloadingInvoice ||
        !this.order?.id
      ) {
        return;
      }

      this.downloadingInvoice = true;
      this.errorMessage = "";
      this.successMessage = "";

      try {
        const response =
          await orderService.downloadInvoice(
            this.order.id
          );

        this.showSuccess(
          response?.fileName
            ? `Invoice downloaded: ${response.fileName}`
            : "Invoice downloaded successfully."
        );
      } catch (error) {
        console.error(
          "Invoice download failed:",
          error
        );

        this.errorMessage =
          getOrderApiErrorMessage(
            error,
            "Unable to download the invoice."
          );
      } finally {
        this.downloadingInvoice = false;
      }
    },

    /*
    |--------------------------------------------------------------------------
    | Show Success Message
    |--------------------------------------------------------------------------
    |
    | Success message 4 seconds পরে automatically clear করে।
    |
    */

    showSuccess(message) {
      this.successMessage =
        String(message || "");

      if (this.messageTimer) {
        clearTimeout(
          this.messageTimer
        );
      }

      this.messageTimer =
        setTimeout(() => {
          this.successMessage = "";
          this.messageTimer = null;
        }, 4000);
    },

    /*
    |--------------------------------------------------------------------------
    | Go Back
    |--------------------------------------------------------------------------
    |
    | Previous page থাকলে সেখানে যায়, না থাকলে Order Management page open করে।
    |
    */

    goBack() {
      if (
        window.history.length > 1
      ) {
        this.$router.back();

        return;
      }

      this.$router.push({
        name:
          "order-management",
      });
    },

    /*
    |--------------------------------------------------------------------------
    | Print Order Details
    |--------------------------------------------------------------------------
    */

    printOrder() {
      window.print();
    },

    /*
    |--------------------------------------------------------------------------
    | Currency Formatter
    |--------------------------------------------------------------------------
    |
    | Amount-কে Bangladeshi Taka format-এ দেখায়।
    |
    */

    formatCurrency(value) {
      const amount =
        this.toNumber(value);

      return new Intl.NumberFormat(
        "en-BD",
        {
          style: "currency",
          currency: "BDT",
          minimumFractionDigits: 2,
          maximumFractionDigits: 2,
        }
      ).format(amount);
    },

    /*
    |--------------------------------------------------------------------------
    | Readable Label Formatter
    |--------------------------------------------------------------------------
    |
    | snake_case value-কে readable title format-এ convert করে।
    |
    */

    formatLabel(value) {
      if (
        value === null ||
        value === undefined ||
        value === ""
      ) {
        return "";
      }

      return String(value)
        .replaceAll("_", " ")
        .replace(
          /\b\w/g,
          (letter) =>
            letter.toUpperCase()
        );
    },

    /*
    |--------------------------------------------------------------------------
    | Order Status CSS Class
    |--------------------------------------------------------------------------
    */

    getOrderStatusClass(status) {
      const classes = {
        pending:
          "status-pending",

        preparing:
          "status-preparing",

        ready:
          "status-ready",

        served:
          "status-served",

        completed:
          "status-completed",

        canceled:
          "status-canceled",
      };

      return (
        classes[status] ||
        "status-default"
      );
    },

    /*
    |--------------------------------------------------------------------------
    | Order Status Icon
    |--------------------------------------------------------------------------
    */

    getOrderStatusIcon(status) {
      const icons = {
        pending:
          "bi bi-hourglass",

        preparing:
          "bi bi-fire",

        ready:
          "bi bi-check2-circle",

        served:
          "bi bi-cup-hot",

        completed:
          "bi bi-patch-check",

        canceled:
          "bi bi-x-circle",
      };

      return (
        icons[status] ||
        "bi bi-circle"
      );
    },

    /*
    |--------------------------------------------------------------------------
    | Payment Status CSS Class
    |--------------------------------------------------------------------------
    */

    getPaymentStatusClass(status) {
      const classes = {
        due:
          "payment-due",

        partially_paid:
          "payment-partial",

        paid:
          "payment-paid",
      };

      return (
        classes[status] ||
        "payment-default"
      );
    },

    /*
    |--------------------------------------------------------------------------
    | Payment Status Icon
    |--------------------------------------------------------------------------
    */

    getPaymentStatusIcon(status) {
      const icons = {
        due:
          "bi bi-exclamation-circle",

        partially_paid:
          "bi bi-wallet2",

        paid:
          "bi bi-check-circle",
      };

      return (
        icons[status] ||
        "bi bi-circle"
      );
    },

    /*
    |--------------------------------------------------------------------------
    | Payment Method Icon
    |--------------------------------------------------------------------------
    */

    getPaymentMethodIcon(method) {
      const icons = {
        cash:
          "bi bi-cash-stack",

        card:
          "bi bi-credit-card",

        bkash:
          "bi bi-wallet2",

        nagad:
          "bi bi-wallet2",

        bank_transfer:
          "bi bi-bank",

        mixed:
          "bi bi-cash-coin",
      };

      return (
        icons[method] ||
        "bi bi-wallet"
      );
    },
        /*
    |--------------------------------------------------------------------------
    | Format Date & Time
    |--------------------------------------------------------------------------
    */

    formatDateTime(value) {
      if (!value) {
        return "Not available";
      }

      const date = new Date(value);

      if (Number.isNaN(date.getTime())) {
        return "Not available";
      }

      return new Intl.DateTimeFormat(
        "en-GB",
        {
          day: "2-digit",
          month: "short",
          year: "numeric",
          hour: "2-digit",
          minute: "2-digit",
        }
      ).format(date);
    },

    /*
    |--------------------------------------------------------------------------
    | Format Date
    |--------------------------------------------------------------------------
    */

    formatDate(value) {
      if (!value) {
        return "Not available";
      }

      const date = new Date(value);

      if (Number.isNaN(date.getTime())) {
        return "Not available";
      }

      return new Intl.DateTimeFormat(
        "en-GB",
        {
          day: "2-digit",
          month: "long",
          year: "numeric",
        }
      ).format(date);
    },

    /*
    |--------------------------------------------------------------------------
    | Format Time
    |--------------------------------------------------------------------------
    */

    formatTime(value) {
      if (!value) {
        return "Not available";
      }

      const date = new Date(value);

      if (Number.isNaN(date.getTime())) {
        return "Not available";
      }

      return new Intl.DateTimeFormat(
        "en-GB",
        {
          hour: "2-digit",
          minute: "2-digit",
          hour12: true,
        }
      ).format(date);
    },

    /*
    |--------------------------------------------------------------------------
    | Timeline Helper
    |--------------------------------------------------------------------------
    */

    hasTimelineDate(step) {
      return Boolean(step?.date);
    },

    /*
    |--------------------------------------------------------------------------
    | Timeline Badge Class
    |--------------------------------------------------------------------------
    */

    getTimelineStatus(step) {
      return step.completed
        ? "timeline-completed"
        : "timeline-pending";
    },
  },
};