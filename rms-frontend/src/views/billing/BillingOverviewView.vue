<template>
  <section class="billing-page">
    <!-- ==================================================
         Billing Page Header
    =================================================== -->

    <BillingPageHeader
      :date-from="filters.dateFrom"
      :date-to="filters.dateTo"
      :today="today"
      :loading="isAnyLoading"
      @update:date-from="handleDateFromChange"
      @update:date-to="handleDateToChange"
      @refresh="refreshAll"
    />

    <!-- ==================================================
         Global Error Message
    =================================================== -->

    <div
      v-if="globalError"
      class="billing-alert billing-error-alert"
      role="alert"
      aria-live="assertive"
    >
      <span
        class="billing-alert-icon"
        aria-hidden="true"
      >
        <i class="bi bi-exclamation-triangle-fill"></i>
      </span>

      <div class="billing-alert-content">
        <strong>
          Unable to load billing information
        </strong>

        <p>
          {{ globalError }}
        </p>
      </div>

      <button
        type="button"
        class="billing-alert-close"
        aria-label="Close billing error"
        @click="globalError = ''"
      >
        <i
          class="bi bi-x-lg"
          aria-hidden="true"
        ></i>
      </button>
    </div>

    <!-- ==================================================
         Billing Summary Cards
    =================================================== -->

    <BillingSummaryCards
      :summary="summary"
      :loading="summaryLoading"
    />

    <!-- ==================================================
         Settlement Orders
    =================================================== -->

    <SettlementOrderTable
      :orders="settlements"
      :meta="settlementMeta"
      :totals="settlementTotals"
      :loading="settlementLoading"
      :error-message="settlementError"
      :status="filters.settlementStatus"
      :status-options="settlementStatusOptions"
      :date-from="filters.dateFrom"
      :date-to="filters.dateTo"
      @update:status="handleSettlementStatusChange"
      @page-change="changeSettlementPage"
      @retry="loadSettlements"
      @extract="exportSettlements"
      @print="printSettlementSection"
    />

    <!-- ==================================================
         Payment Mode
    =================================================== -->

    <PaymentModeTable
      :payments="paymentModes"
      :meta="paymentModeMeta"
      :totals="paymentModeTotals"
      :loading="paymentModeLoading"
      :error-message="paymentModeError"
      :payment-method="filters.paymentMethod"
      :payment-method-options="paymentMethodOptions"
      :date-from="filters.dateFrom"
      :date-to="filters.dateTo"
      @update:payment-method="handlePaymentMethodChange"
      @page-change="changePaymentModePage"
      @retry="loadPaymentModes"
      @extract="exportPaymentModes"
      @print="printPaymentModeSection"
    />

    <!-- ==================================================
         Payment Activity
    =================================================== -->

    <PaymentActivityTable
      :activities="activities"
      :meta="activityMeta"
      :totals="activityTotals"
      :loading="activityLoading"
      :error-message="activityError"
      :users="users"
      :user-id="filters.userId"
      :user-type="filters.userType"
      :date-from="filters.dateFrom"
      :date-to="filters.dateTo"
      @update:user-id="handleActivityUserChange"
      @update:user-type="handleActivityTypeChange"
      @page-change="changeActivityPage"
      @retry="loadActivities"
      @extract="exportActivities"
      @print="printActivitySection"
    />
  </section>
</template>

<script setup>
/*
|--------------------------------------------------------------------------
| Vue Imports
|--------------------------------------------------------------------------
*/

import {
  computed,
  onMounted,
  reactive,
  ref,
} from 'vue'

/*
|--------------------------------------------------------------------------
| Billing API Service
|--------------------------------------------------------------------------
*/

import billingService from '@/services/billingService'

/*
|--------------------------------------------------------------------------
| Billing Components
|--------------------------------------------------------------------------
*/

import BillingPageHeader
  from '@/components/billing/BillingPageHeader.vue'

import BillingSummaryCards
  from '@/components/billing/BillingSummaryCards.vue'

import SettlementOrderTable
  from '@/components/billing/SettlementOrderTable.vue'

import PaymentModeTable
  from '@/components/billing/PaymentModeTable.vue'

import PaymentActivityTable
  from '@/components/billing/PaymentActivityTable.vue'

/*
|--------------------------------------------------------------------------
| Billing Styles
|--------------------------------------------------------------------------
*/

import '@/assets/css/billing/billing-overview.css'
import '@/assets/css/billing/billing-header.css'
import '@/assets/css/billing/billing-summary.css'
import '@/assets/css/billing/billing-table.css'
import '@/assets/css/billing/billing-responsive.css'

/*
|--------------------------------------------------------------------------
| Current Local Date
|--------------------------------------------------------------------------
*/

const today =
  getLocalDateValue()

/*
|--------------------------------------------------------------------------
| Global Billing Filters
|--------------------------------------------------------------------------
|
| Initial load-এ From এবং To—দুইটিতেই আজকের date থাকবে।
|
| - From only: ওই একদিন
| - To only: ওই একদিন
| - From + To: সম্পূর্ণ range
| - Date পরিবর্তনের পরে Refresh button চাপলে data load হবে
|
*/

const filters = reactive({
  dateFrom:
    today,

  dateTo:
    today,

  settlementStatus:
    '',

  paymentMethod:
    '',

  userId:
    '',

  userType:
    'all',
})

/*
|--------------------------------------------------------------------------
| Global Error State
|--------------------------------------------------------------------------
*/

const globalError =
  ref('')

/*
|--------------------------------------------------------------------------
| Billing Summary State
|--------------------------------------------------------------------------
*/

const summaryLoading =
  ref(false)

const summary = reactive(
  createDefaultSummary(),
)

/*
|--------------------------------------------------------------------------
| Settlement State
|--------------------------------------------------------------------------
*/

const settlementLoading =
  ref(false)

const settlementError =
  ref('')

const settlements =
  ref([])

const settlementMeta = reactive(
  createDefaultMeta(),
)

const settlementTotals = reactive(
  createDefaultTotals(),
)

const settlementStatusOptions =
  ref([
    {
      value: '',
      label: 'All Statuses',
    },
  ])

/*
|--------------------------------------------------------------------------
| Payment Mode State
|--------------------------------------------------------------------------
*/

const paymentModeLoading =
  ref(false)

const paymentModeError =
  ref('')

const paymentModes =
  ref([])

const paymentModeMeta = reactive(
  createDefaultMeta(),
)

const paymentModeTotals = reactive(
  createDefaultTotals(),
)

const paymentMethodOptions =
  ref([
    {
      value: '',
      label: 'All Methods',
    },
  ])

/*
|--------------------------------------------------------------------------
| Payment Activity State
|--------------------------------------------------------------------------
*/

const activityLoading =
  ref(false)

const activityError =
  ref('')

const activities =
  ref([])

const activityMeta = reactive(
  createDefaultMeta(),
)

const activityTotals = reactive(
  createDefaultTotals(),
)

const users =
  ref([])

/*
|--------------------------------------------------------------------------
| Combined Loading State
|--------------------------------------------------------------------------
*/

const isAnyLoading =
  computed(() => {
    return (
      summaryLoading.value ||
      settlementLoading.value ||
      paymentModeLoading.value ||
      activityLoading.value
    )
  })
  /*
|--------------------------------------------------------------------------
| Load Billing Summary
|--------------------------------------------------------------------------
*/

async function loadSummary() {
  summaryLoading.value = true

  try {
    const response =
      await billingService.getSummary({
        dateFrom:
          filters.dateFrom,

        dateTo:
          filters.dateTo,
      })

    Object.assign(
      summary,
      response?.data ||
        createDefaultSummary(),
    )
  } catch (error) {
    Object.assign(
      summary,
      createDefaultSummary(),
    )

    globalError.value =
      billingService.getBillingErrorMessage(
        error,
        'Unable to load billing summary.',
      )
  } finally {
    summaryLoading.value = false
  }
}

/*
|--------------------------------------------------------------------------
| Load Settlement Orders
|--------------------------------------------------------------------------
*/

async function loadSettlements() {
  settlementLoading.value = true
  settlementError.value = ''

  try {
    const response =
      await billingService.getSettlements({
        dateFrom:
          filters.dateFrom,

        dateTo:
          filters.dateTo,

        status:
          filters.settlementStatus,

        page:
          settlementMeta.current_page,

        perPage:
          settlementMeta.per_page,
      })

    settlements.value =
      Array.isArray(
        response?.data,
      )
        ? response.data
        : []

    Object.assign(
      settlementMeta,
      response?.meta ||
        createDefaultMeta(),
    )

    Object.assign(
      settlementTotals,
      response?.totals ||
        createDefaultTotals(),
    )

    const statusOptions =
      response?.filters
        ?.statuses

    if (
      Array.isArray(
        statusOptions,
      ) &&
      statusOptions.length > 0
    ) {
      settlementStatusOptions.value =
        statusOptions
    }
  } catch (error) {
    settlements.value = []

    Object.assign(
      settlementMeta,
      createDefaultMeta(),
    )

    Object.assign(
      settlementTotals,
      createDefaultTotals(),
    )

    settlementError.value =
      billingService.getBillingErrorMessage(
        error,
        'Unable to load settlement orders.',
      )
  } finally {
    settlementLoading.value = false
  }
}

/*
|--------------------------------------------------------------------------
| Load Payment Modes
|--------------------------------------------------------------------------
*/

async function loadPaymentModes() {
  paymentModeLoading.value = true
  paymentModeError.value = ''

  try {
    const response =
      await billingService.getPaymentModes({
        dateFrom:
          filters.dateFrom,

        dateTo:
          filters.dateTo,

        paymentMethod:
          filters.paymentMethod,

        page:
          paymentModeMeta.current_page,

        perPage:
          paymentModeMeta.per_page,
      })

    paymentModes.value =
      Array.isArray(
        response?.data,
      )
        ? response.data
        : []

    Object.assign(
      paymentModeMeta,
      response?.meta ||
        createDefaultMeta(),
    )

    Object.assign(
      paymentModeTotals,
      response?.totals ||
        createDefaultTotals(),
    )

    const paymentMethods =
      response?.filters
        ?.payment_methods

    if (
      Array.isArray(
        paymentMethods,
      ) &&
      paymentMethods.length > 0
    ) {
      paymentMethodOptions.value =
        paymentMethods
    }
  } catch (error) {
    paymentModes.value = []

    Object.assign(
      paymentModeMeta,
      createDefaultMeta(),
    )

    Object.assign(
      paymentModeTotals,
      createDefaultTotals(),
    )

    paymentModeError.value =
      billingService.getBillingErrorMessage(
        error,
        'Unable to load payment mode report.',
      )
  } finally {
    paymentModeLoading.value = false
  }
}

/*
|--------------------------------------------------------------------------
| Load Payment Activities
|--------------------------------------------------------------------------
*/

async function loadActivities() {
  activityLoading.value = true
  activityError.value = ''

  try {
    const response =
      await billingService.getPaymentActivities({
        dateFrom:
          filters.dateFrom,

        dateTo:
          filters.dateTo,

        userId:
          filters.userId,

        userType:
          filters.userType,

        page:
          activityMeta.current_page,

        perPage:
          activityMeta.per_page,
      })

    activities.value =
      Array.isArray(
        response?.data,
      )
        ? response.data
        : []

    Object.assign(
      activityMeta,
      response?.meta ||
        createDefaultMeta(),
    )

    Object.assign(
      activityTotals,
      response?.totals ||
        createDefaultTotals(),
    )
  } catch (error) {
    activities.value = []

    Object.assign(
      activityMeta,
      createDefaultMeta(),
    )

    Object.assign(
      activityTotals,
      createDefaultTotals(),
    )

    activityError.value =
      billingService.getBillingErrorMessage(
        error,
        'Unable to load payment activity.',
      )
  } finally {
    activityLoading.value = false
  }
}

/*
|--------------------------------------------------------------------------
| Load Billing Users
|--------------------------------------------------------------------------
*/

async function loadUsers() {
  try {
    const response =
      await billingService.getUsers()

    users.value =
      Array.isArray(
        response?.data,
      )
        ? response.data
        : []
  } catch (error) {
    users.value = []

    console.error(
      'Unable to load billing users:',
      error,
    )
  }
}

/*
|--------------------------------------------------------------------------
| Refresh All Billing Data
|--------------------------------------------------------------------------
|
| সব report একই সময়ে load হবে।
| একটি request fail করলেও অন্যগুলো complete হতে পারবে।
|
*/

async function refreshAll() {
  globalError.value = ''

  resetAllPages()

  await Promise.allSettled([
    loadSummary(),
    loadSettlements(),
    loadPaymentModes(),
    loadActivities(),
  ])
}
/*
|--------------------------------------------------------------------------
| Handle From Date Change
|--------------------------------------------------------------------------
|
| শুধু filter state update হবে।
| নতুন data load করতে Refresh button ব্যবহার করবে।
|
*/

/*
|--------------------------------------------------------------------------
| Handle From Date Change
|--------------------------------------------------------------------------
*/

async function handleDateFromChange(
  dateFrom,
) {
  const resolvedDate =
    normaliseDateInput(
      dateFrom,
    )

  if (
    resolvedDate ===
    filters.dateFrom
  ) {
    return
  }

  filters.dateFrom =
    resolvedDate

  resetAllPages()

  await refreshAll()
}

/*
|--------------------------------------------------------------------------
| Handle To Date Change
|--------------------------------------------------------------------------
*/

async function handleDateToChange(
  dateTo,
) {
  const resolvedDate =
    normaliseDateInput(
      dateTo,
    )

  if (
    resolvedDate ===
    filters.dateTo
  ) {
    return
  }

  filters.dateTo =
    resolvedDate

  resetAllPages()

  await refreshAll()
}

/*
|--------------------------------------------------------------------------
| Handle Settlement Status Change
|--------------------------------------------------------------------------
*/

function handleSettlementStatusChange(
  status,
) {
  const resolvedStatus =
    String(
      status || '',
    ).trim()

  if (
    resolvedStatus ===
    filters.settlementStatus
  ) {
    return
  }

  filters.settlementStatus =
    resolvedStatus

  settlementMeta.current_page = 1

  loadSettlements()
}

/*
|--------------------------------------------------------------------------
| Handle Payment Method Change
|--------------------------------------------------------------------------
*/

function handlePaymentMethodChange(
  paymentMethod,
) {
  const resolvedMethod =
    String(
      paymentMethod || '',
    ).trim()

  if (
    resolvedMethod ===
    filters.paymentMethod
  ) {
    return
  }

  filters.paymentMethod =
    resolvedMethod

  paymentModeMeta.current_page = 1

  loadPaymentModes()
}

/*
|--------------------------------------------------------------------------
| Handle Activity User Change
|--------------------------------------------------------------------------
*/

function handleActivityUserChange(
  userId,
) {
  const resolvedUserId =
    userId === null ||
    userId === undefined
      ? ''
      : String(
          userId,
        ).trim()

  if (
    resolvedUserId ===
    String(
      filters.userId || '',
    )
  ) {
    return
  }

  filters.userId =
    resolvedUserId

  activityMeta.current_page = 1

  loadActivities()
}

/*
|--------------------------------------------------------------------------
| Handle Activity User Type Change
|--------------------------------------------------------------------------
*/

function handleActivityTypeChange(
  userType,
) {
  const resolvedUserType =
    String(
      userType || 'all',
    )
      .trim()
      .toLowerCase()

  if (
    resolvedUserType ===
    filters.userType
  ) {
    return
  }

  filters.userType =
    resolvedUserType || 'all'

  activityMeta.current_page = 1

  loadActivities()
}

/*
|--------------------------------------------------------------------------
| Change Settlement Page
|--------------------------------------------------------------------------
*/

function changeSettlementPage(
  page,
) {
  if (
    !isValidPage(
      page,
      settlementMeta,
    )
  ) {
    return
  }

  settlementMeta.current_page =
    Number(page)

  loadSettlements()
}

/*
|--------------------------------------------------------------------------
| Change Payment Mode Page
|--------------------------------------------------------------------------
*/

function changePaymentModePage(
  page,
) {
  if (
    !isValidPage(
      page,
      paymentModeMeta,
    )
  ) {
    return
  }

  paymentModeMeta.current_page =
    Number(page)

  loadPaymentModes()
}

/*
|--------------------------------------------------------------------------
| Change Payment Activity Page
|--------------------------------------------------------------------------
*/

function changeActivityPage(
  page,
) {
  if (
    !isValidPage(
      page,
      activityMeta,
    )
  ) {
    return
  }

  activityMeta.current_page =
    Number(page)

  loadActivities()
}

/*
|--------------------------------------------------------------------------
| Initial Billing Data Load
|--------------------------------------------------------------------------
|
| Users, summary এবং তিনটি report একই সময়ে load হবে।
| একটি request fail করলেও অন্যগুলো complete হতে পারবে।
|
*/

function loadInitialBillingData() {
  Promise.allSettled([
    loadUsers(),
    loadSummary(),
    loadSettlements(),
    loadPaymentModes(),
    loadActivities(),
  ])
}

/*
|--------------------------------------------------------------------------
| Mounted Lifecycle
|--------------------------------------------------------------------------
*/

onMounted(() => {
  loadInitialBillingData()
})
/*
|--------------------------------------------------------------------------
| Print Settlement Orders
|--------------------------------------------------------------------------
*/

function printSettlementSection() {
  const rows =
    settlements.value.map(
      (item) => [
        item.order_number ||
          `#${item.id}`,

        item.customer?.name ||
          'Walk-in Customer',

        item.order_status_label ||
          formatLabel(
            item.order_status,
          ),

        item.payment_status_label ||
          formatLabel(
            item.payment_status,
          ),

        item.total_amount_formatted ||
          formatCurrency(
            item.total_amount,
          ),

        item.due_amount_formatted ||
          formatCurrency(
            item.due_amount,
          ),
      ],
    )

  printReport({
    title:
      'Settlement Orders',

    dateText:
      getFilterDateText(),

    columns: [
      'Order ID',
      'Customer',
      'Order Status',
      'Payment Status',
      'Total Amount',
      'Due Amount',
    ],

    rows,

    totalLabel:
      'Filtered Total',

    totalValue:
      settlementTotals
        .amount_formatted ||
      formatCurrency(
        settlementTotals.amount,
      ),
  })
}

/*
|--------------------------------------------------------------------------
| Print Payment Mode
|--------------------------------------------------------------------------
*/

function printPaymentModeSection() {
  const rows =
    paymentModes.value.map(
      (item) => [
        item.order_number ||
          `#${item.order_id}`,

        item.customer?.name ||
          'Walk-in Customer',

        item.payment_method_label ||
          formatLabel(
            item.payment_method,
          ),

        item.reference ||
          '—',

        [
          item.date || '',
          item.time || '',
        ]
          .filter(Boolean)
          .join(' '),

        item.amount_formatted ||
          formatCurrency(
            item.amount,
          ),
      ],
    )

  printReport({
    title:
      'Payment Mode',

    dateText:
      getFilterDateText(),

    columns: [
      'Order ID',
      'Customer',
      'Payment Method',
      'Reference',
      'Date & Time',
      'Amount',
    ],

    rows,

    totalLabel:
      'Filtered Total',

    totalValue:
      paymentModeTotals
        .amount_formatted ||
      formatCurrency(
        paymentModeTotals.amount,
      ),
  })
}

/*
|--------------------------------------------------------------------------
| Print Payment Activity
|--------------------------------------------------------------------------
*/

function printActivitySection() {
  const rows =
    activities.value.map(
      (item) => [
        item.order_number ||
          `#${item.order_id}`,

        [
          item.date || '',
          item.time || '',
        ]
          .filter(Boolean)
          .join(' '),

        item.customer?.name ||
          'Walk-in Customer',

        item.payment_method_label ||
          formatLabel(
            item.payment_method,
          ),

        item.amount_formatted ||
          formatCurrency(
            item.amount,
          ),

        resolveDisplayName(
          item.waiter,
        ),

        resolveDisplayName(
          item.chef,
        ),

        resolveDisplayName(
          item.receiver,
        ),
      ],
    )

  printReport({
    title:
      'Payment Activity',

    dateText:
      getFilterDateText(),

    columns: [
      'Order ID',
      'Date & Time',
      'Customer',
      'Payment Method',
      'Amount',
      'Order Creator',
      'Chef',
      'Received By',
    ],

    rows,

    totalLabel:
      'Filtered Total',

    totalValue:
      activityTotals
        .amount_formatted ||
      formatCurrency(
        activityTotals.amount,
      ),
  })
}

/*
|--------------------------------------------------------------------------
| Resolve User Display Name
|--------------------------------------------------------------------------
*/

function resolveDisplayName(
  user,
) {
  return (
    user?.display_name ||
    user?.username ||
    user?.name ||
    'Not Assigned'
  )
}

/*
|--------------------------------------------------------------------------
| Generic Print Report
|--------------------------------------------------------------------------
*/

function printReport({
  title,
  dateText,
  columns,
  rows,
  totalLabel,
  totalValue,
}) {
  if (
    !Array.isArray(rows) ||
    rows.length === 0
  ) {
    globalError.value =
      'No records are available to print.'

    return
  }

  const printWindow =
    window.open(
      '',
      '_blank',
      'width=1100,height=760',
    )

  if (!printWindow) {
    globalError.value =
      'Please allow pop-ups to print this report.'

    return
  }

  const tableHeader =
    columns
      .map(
        (column) =>
          `<th>${escapeHtml(
            column,
          )}</th>`,
      )
      .join('')

  const tableRows =
    rows
      .map(
        (row) => `
          <tr>
            ${row
              .map(
                (value) =>
                  `<td>${escapeHtml(
                    value,
                  )}</td>`,
              )
              .join('')}
          </tr>
        `,
      )
      .join('')

  const totalRow =
    totalLabel &&
    totalValue
      ? `
        <tr class="print-total-row">
          <td colspan="${Math.max(
            columns.length - 1,
            1,
          )}">
            ${escapeHtml(
              totalLabel,
            )}
          </td>

          <td>
            ${escapeHtml(
              totalValue,
            )}
          </td>
        </tr>
      `
      : ''

  printWindow.document.write(`
    <!DOCTYPE html>
    <html lang="en">
      <head>
        <meta charset="UTF-8">

        <meta
          name="viewport"
          content="width=device-width, initial-scale=1.0"
        >

        <title>
          ${escapeHtml(title)}
        </title>

        <style>
          * {
            box-sizing: border-box;
          }

          body {
            margin: 28px;
            color: #0f172a;
            font-family:
              Arial,
              sans-serif;
          }

          h1 {
            margin: 0;
            font-size: 23px;
          }

          p {
            margin: 6px 0 20px;
            color: #64748b;
            font-size: 12px;
          }

          table {
            width: 100%;
            border-collapse: collapse;
          }

          th,
          td {
            padding: 9px;
            border: 1px solid #dbe2ea;
            font-size: 10px;
            text-align: left;
            vertical-align: top;
          }

          th {
            background: #f1f5f9;
            font-weight: 700;
          }

          .print-total-row td {
            background: #f8fafc;
            font-weight: 700;
          }

          .print-total-row td:last-child {
            text-align: right;
            white-space: nowrap;
          }

          @media print {
            body {
              margin: 14px;
            }
          }
        </style>
      </head>

      <body>
        <h1>
          ${escapeHtml(title)}
        </h1>

        <p>
          Statement period:
          ${escapeHtml(dateText)}
        </p>

        <table>
          <thead>
            <tr>
              ${tableHeader}
            </tr>
          </thead>

          <tbody>
            ${tableRows}
          </tbody>

          <tfoot>
            ${totalRow}
          </tfoot>
        </table>
      </body>
    </html>
  `)

  printWindow.document.close()
  printWindow.focus()

  printWindow.onload = () => {
    printWindow.print()
    printWindow.close()
  }
}
/*
|--------------------------------------------------------------------------
| Export Settlement Orders
|--------------------------------------------------------------------------
*/

function exportSettlements() {
  downloadCsv({
    filename:
      `settlements-${getFileDateText()}.csv`,

    headers: [
      "Order ID",
      "Customer",
      "Order Status",
      "Payment Status",
      "Total Amount",
      "Due Amount",
    ],

    rows:
      settlements.value.map(
        (item) => [
          item.order_number ||
            `#${item.id}`,

          item.customer?.name ||
            "Walk-in Customer",

          item.order_status_label ||
            formatLabel(
              item.order_status,
            ),

          item.payment_status_label ||
            formatLabel(
              item.payment_status,
            ),

          item.total_amount,

          item.due_amount,
        ],
      ),
  })
}

/*
|--------------------------------------------------------------------------
| Export Payment Modes
|--------------------------------------------------------------------------
*/

function exportPaymentModes() {
  downloadCsv({
    filename:
      `payment-modes-${getFileDateText()}.csv`,

    headers: [
      "Order ID",
      "Customer",
      "Payment Method",
      "Reference",
      "Date",
      "Time",
      "Amount",
    ],

    rows:
      paymentModes.value.map(
        (item) => [
          item.order_number ||
            `#${item.order_id}`,

          item.customer?.name ||
            "Walk-in Customer",

          item.payment_method_label ||
            formatLabel(
              item.payment_method,
            ),

          item.reference ||
            "",

          item.date ||
            "",

          item.time ||
            "",

          item.amount,
        ],
      ),
  })
}

/*
|--------------------------------------------------------------------------
| Export Payment Activities
|--------------------------------------------------------------------------
*/

function exportActivities() {
  downloadCsv({
    filename:
      `payment-activity-${getFileDateText()}.csv`,

    headers: [
      "Order ID",
      "Customer",
      "Payment Method",
      "Amount",
      "Order Creator",
      "Chef",
      "Received By",
    ],

    rows:
      activities.value.map(
        (item) => [
          item.order_number ||
            `#${item.order_id}`,

          item.customer?.name ||
            "Walk-in Customer",

          item.payment_method_label ||
            formatLabel(
              item.payment_method,
            ),

          item.amount,

          resolveDisplayName(
            item.waiter,
          ),

          resolveDisplayName(
            item.chef,
          ),

          resolveDisplayName(
            item.receiver,
          ),
        ],
      ),
  })
}

/*
|--------------------------------------------------------------------------
| Generic CSV Downloader
|--------------------------------------------------------------------------
*/

function downloadCsv({
  filename,
  headers,
  rows,
}) {
  const csvRows = [
    headers,
    ...rows,
  ]

  const csvContent =
    csvRows
      .map(
        (row) =>
          row
            .map(
              escapeCsv,
            )
            .join(","),
      )
      .join("\n")

  const blob =
    new Blob(
      [csvContent],
      {
        type:
          "text/csv;charset=utf-8;",
      },
    )

  const url =
    URL.createObjectURL(
      blob,
    )

  const link =
    document.createElement(
      "a",
    )

  link.href = url

  link.download =
    filename

  document.body.appendChild(
    link,
  )

  link.click()

  document.body.removeChild(
    link,
  )

  URL.revokeObjectURL(
    url,
  )
}

/*
|--------------------------------------------------------------------------
| Escape CSV Value
|--------------------------------------------------------------------------
*/

function escapeCsv(
  value,
) {
  const text =
    String(
      value ?? "",
    )

  return `"${text.replaceAll(
    '"',
    '""',
  )}"`
}
/*
|--------------------------------------------------------------------------
| Get Filter Date Text
|--------------------------------------------------------------------------
|
| Print report-এর জন্য readable date/date-range তৈরি করবে।
|
*/

function getFilterDateText() {
  const dateFrom =
    filters.dateFrom

  const dateTo =
    filters.dateTo

  if (
    dateFrom &&
    dateTo
  ) {
    if (
      dateFrom ===
      dateTo
    ) {
      return formatDateForDisplay(
        dateFrom,
      )
    }

    return `${formatDateForDisplay(
      dateFrom,
    )} to ${formatDateForDisplay(
      dateTo,
    )}`
  }

  const selectedDate =
    dateFrom ||
    dateTo ||
    today

  return formatDateForDisplay(
    selectedDate,
  )
}

/*
|--------------------------------------------------------------------------
| Get File Date Text
|--------------------------------------------------------------------------
|
| CSV filename-এর জন্য safe date/date-range text তৈরি করবে।
|
*/

function getFileDateText() {
  const dateFrom =
    filters.dateFrom

  const dateTo =
    filters.dateTo

  if (
    dateFrom &&
    dateTo
  ) {
    if (
      dateFrom ===
      dateTo
    ) {
      return dateFrom
    }

    return `${dateFrom}-to-${dateTo}`
  }

  return (
    dateFrom ||
    dateTo ||
    today
  )
}

/*
|--------------------------------------------------------------------------
| Format Date for Display
|--------------------------------------------------------------------------
*/

function formatDateForDisplay(
  value,
) {
  if (
    !isValidDateValue(
      value,
    )
  ) {
    return (
      value ||
      'Selected date'
    )
  }

  const [
    year,
    month,
    day,
  ] = value
    .split('-')
    .map(Number)

  const date =
    new Date(
      year,
      month - 1,
      day,
    )

  return new Intl.DateTimeFormat(
    'en-GB',
    {
      day: '2-digit',
      month: 'short',
      year: 'numeric',
    },
  ).format(date)
}

/*
|--------------------------------------------------------------------------
| Normalise Date Input
|--------------------------------------------------------------------------
|
| - Empty value allowed
| - Invalid date হলে today fallback
| - Future date হলে today fallback
|
*/

function normaliseDateInput(
  value,
) {
  const resolvedValue =
    String(
      value || '',
    ).trim()

  if (!resolvedValue) {
    return ''
  }

  if (
    !isValidDateValue(
      resolvedValue,
    )
  ) {
    return today
  }

  if (
    resolvedValue > today
  ) {
    return today
  }

  return resolvedValue
}

/*
|--------------------------------------------------------------------------
| Validate Date Value
|--------------------------------------------------------------------------
*/

function isValidDateValue(
  value,
) {
  if (
    !/^\d{4}-\d{2}-\d{2}$/.test(
      String(value || ''),
    )
  ) {
    return false
  }

  const [
    year,
    month,
    day,
  ] = String(value)
    .split('-')
    .map(Number)

  const date =
    new Date(
      year,
      month - 1,
      day,
    )

  return (
    date.getFullYear() ===
      year &&
    date.getMonth() ===
      month - 1 &&
    date.getDate() ===
      day
  )
}

/*
|--------------------------------------------------------------------------
| Reset All Pagination
|--------------------------------------------------------------------------
*/

function resetAllPages() {
  settlementMeta.current_page =
    1

  paymentModeMeta.current_page =
    1

  activityMeta.current_page =
    1
}

/*
|--------------------------------------------------------------------------
| Validate Pagination Page
|--------------------------------------------------------------------------
*/

function isValidPage(
  page,
  meta,
) {
  const resolvedPage =
    Number(page)

  const currentPage =
    Number(
      meta?.current_page ||
      1,
    )

  const lastPage =
    Number(
      meta?.last_page ||
      1,
    )

  return (
    Number.isInteger(
      resolvedPage,
    ) &&
    resolvedPage >= 1 &&
    resolvedPage <=
      lastPage &&
    resolvedPage !==
      currentPage
  )
}

/*
|--------------------------------------------------------------------------
| Default Billing Summary
|--------------------------------------------------------------------------
*/

function createDefaultSummary() {
  return {
    /*
    |--------------------------------------------------------------------------
    | Raw Values
    |--------------------------------------------------------------------------
    */

    gross_sales:
      0,

    discount_amount:
      0,

    net_sales:
      0,

    tax:
      0,

    service_charge:
      0,

    total_billed:
      0,

    collected_amount:
      0,

    expenses:
      0,

    cash_collection:
      0,

    outstanding_due:
      0,

    total_orders:
      0,

    /*
    |--------------------------------------------------------------------------
    | Formatted Values
    |--------------------------------------------------------------------------
    */

    gross_sales_formatted:
      '৳ 0.00',

    discount_amount_formatted:
      '৳ 0.00',

    net_sales_formatted:
      '৳ 0.00',

    tax_formatted:
      '৳ 0.00',

    service_charge_formatted:
      '৳ 0.00',

    total_billed_formatted:
      '৳ 0.00',

    collected_amount_formatted:
      '৳ 0.00',

    expenses_formatted:
      '৳ 0.00',

    cash_collection_formatted:
      '৳ 0.00',

    outstanding_due_formatted:
      '৳ 0.00',

    /*
    |--------------------------------------------------------------------------
    | Date Range
    |--------------------------------------------------------------------------
    */

    date_range: {
      from: '',
      to: '',
      from_label: '',
      to_label: '',
    },
  }
}

/*
|--------------------------------------------------------------------------
| Default Pagination Metadata
|--------------------------------------------------------------------------
*/

function createDefaultMeta() {
  return {
    current_page:
      1,

    last_page:
      1,

    per_page:
      5,

    total:
      0,

    from:
      null,

    to:
      null,
  }
}

/*
|--------------------------------------------------------------------------
| Default Filtered Totals
|--------------------------------------------------------------------------
*/

function createDefaultTotals() {
  return {
    amount:
      0,

    amount_formatted:
      '৳ 0.00',
  }
}

/*
|--------------------------------------------------------------------------
| Get Browser Local Date
|--------------------------------------------------------------------------
|
| YYYY-MM-DD format return করবে।
|
*/

function getLocalDateValue(
  date = new Date(),
) {
  const year =
    date.getFullYear()

  const month =
    String(
      date.getMonth() + 1,
    ).padStart(
      2,
      '0',
    )

  const day =
    String(
      date.getDate(),
    ).padStart(
      2,
      '0',
    )

  return `${year}-${month}-${day}`
}

/*
|--------------------------------------------------------------------------
| Format Currency
|--------------------------------------------------------------------------
*/

function formatCurrency(
  value,
) {
  const amount =
    Number(value)

  const resolvedAmount =
    Number.isFinite(
      amount,
    )
      ? amount
      : 0

  return `৳ ${resolvedAmount.toLocaleString(
    'en-GB',
    {
      minimumFractionDigits:
        2,

      maximumFractionDigits:
        2,
    },
  )}`
}

/*
|--------------------------------------------------------------------------
| Format Readable Label
|--------------------------------------------------------------------------
*/

function formatLabel(
  value,
) {
  const resolvedValue =
    String(
      value || '',
    ).trim()

  if (!resolvedValue) {
    return 'Not Available'
  }

  return resolvedValue
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

/*
|--------------------------------------------------------------------------
| Escape CSV Value
|--------------------------------------------------------------------------
*/

function escapeCsvValue(
  value,
) {
  const text =
    String(
      value ?? '',
    )

  return `"${text.replaceAll(
    '"',
    '""',
  )}"`
}

/*
|--------------------------------------------------------------------------
| Escape HTML
|--------------------------------------------------------------------------
|
| Print window-এ unsafe HTML render হওয়া prevent করবে।
|
*/

function escapeHtml(
  value,
) {
  return String(
    value ?? '',
  )
    .replaceAll(
      '&',
      '&amp;',
    )
    .replaceAll(
      '<',
      '&lt;',
    )
    .replaceAll(
      '>',
      '&gt;',
    )
    .replaceAll(
      '"',
      '&quot;',
    )
    .replaceAll(
      "'",
      '&#039;',
    )
}
</script>