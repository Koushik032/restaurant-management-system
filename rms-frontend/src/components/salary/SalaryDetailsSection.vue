<template>

<section class="salary-details-section">

  <!-- Header -->

  <div class="salary-details-header">

    <div class="salary-details-header-left">

      <button
        type="button"
        class="salary-details-back-btn"
        @click="$emit('back')"
      >

        <i class="bi bi-arrow-left"></i>

        Back

      </button>


      <div>

        <span class="salary-details-eyebrow">
          Daily Salary Details
        </span>

        <h3>
          {{ pageTitle }}
        </h3>

        <p>
          {{ periodLabel }}
        </p>

      </div>

    </div>


    <button
      type="button"
      class="salary-details-refresh-btn"
      :disabled="loading"
      @click="refreshSalaryDetails"
    >

      <i class="bi bi-arrow-repeat"></i>

      Refresh

    </button>

  </div>


  <!-- Employee Information -->

  <div
    v-if="selectedEmployee"
    class="salary-details-employee-card"
  >

    <div class="salary-details-profile">

      <div class="salary-details-profile-avatar">

        {{
          initials(
            selectedEmployee
              ?.employee_name
          )
        }}

      </div>


      <div>

        <h4>
          {{
            selectedEmployee
              ?.employee_name
            ||
            'Employee'
          }}
        </h4>

        <div class="salary-details-profile-meta">

          <span>
            {{ selectedEmployee?.role_label || 'Staff' }}
          </span>

          <span>
            {{ selectedEmployee?.employee_phone || 'No phone' }}
          </span>

          <span>
            {{ selectedEmployee?.hourly_rate_formatted || '—' }}
          </span>

        </div>

      </div>

    </div>


    <div class="salary-details-period-box">

      <span>
        Salary Period
      </span>

      <strong>
        {{ periodLabel }}
      </strong>

    </div>

  </div>


  <!-- Employee Selector when no employee selected -->

  <div
    v-else
    class="salary-details-filter-card"
  >

    <div class="salary-details-filter-group">

      <label>
        Employee
      </label>

      <select
        v-model="filters.employee_id"
        :disabled="employeesLoading"
        @change="applyFilters"
      >

        <option value="">
          All Employees
        </option>

        <option
          v-for="employee in employees"
          :key="employee.id"
          :value="String(employee.id)"
        >

          {{ employee.employee_name }}

        </option>

      </select>

    </div>

  </div>


  <!-- Filters -->

  <div class="salary-details-filter-card">

    <div class="salary-details-filter-grid">

      <div class="salary-details-filter-group">

        <label>
          From Date
        </label>

        <input
          v-model="filters.from_date"
          type="date"
          :max="todayDate"
        />

      </div>


      <div class="salary-details-filter-group">

        <label>
          To Date
        </label>

        <input
          v-model="filters.to_date"
          type="date"
          :min="filters.from_date"
          :max="todayDate"
        />

      </div>


      <div class="salary-details-filter-group">

        <label>
          Salary Type
        </label>

        <select
          v-model="filters.salary_type"
        >

          <option value="">
            All Types
          </option>

          <option
            v-for="option in salaryTypeOptions"
            :key="option.value"
            :value="option.value"
          >

            {{ option.label }}

          </option>

        </select>

      </div>


      <div class="salary-details-filter-group">

        <label>
          Attendance
        </label>

        <select
          v-model="filters.attendance_status"
        >

          <option value="">
            All Attendance
          </option>

          <option value="scheduled">
            Scheduled
          </option>

          <option value="absent">
            Absent
          </option>

          <option value="present">
            Present
          </option>

          <option value="break">
            On Break
          </option>

          <option value="completed">
            Checked Out
          </option>

          <option value="leave">
            On Leave
          </option>

        </select>

      </div>


      <div class="salary-details-filter-group">

        <label>
          Payment
        </label>

        <select
          v-model="filters.payment_status"
        >

          <option value="">
            All Payments
          </option>

          <option value="paid">
            Paid
          </option>

          <option value="unpaid">
            Unpaid
          </option>

        </select>

      </div>


      <div class="salary-details-filter-actions">

        <button
          type="button"
          class="salary-details-apply-btn"
          :disabled="loading"
          @click="applyFilters"
        >

          <i class="bi bi-funnel"></i>

          Apply

        </button>


        <button
          type="button"
          class="salary-details-clear-btn"
          :disabled="loading"
          @click="clearFilters"
        >

          Reset

        </button>

      </div>

    </div>

  </div>


  <!-- Summary -->

  <div class="salary-details-summary-grid">

    <div class="salary-details-summary-card">

      <span>
        Working Days
      </span>

      <strong>
        {{ summary.completed_days }}
      </strong>

    </div>


    <div class="salary-details-summary-card">

      <span>
        Worked Time
      </span>

      <strong>
        {{ durationLabel(summary.worked_minutes) }}
      </strong>

    </div>


    <div class="salary-details-summary-card">

      <span>
        Overtime
      </span>

      <strong class="salary-details-summary-overtime">
        {{ durationLabel(summary.overtime_minutes) }}
      </strong>

    </div>


    <div class="salary-details-summary-card">

      <span>
        Regular Salary
      </span>

      <strong>
        {{ money(summary.regular_salary) }}
      </strong>

    </div>


    <div class="salary-details-summary-card">

      <span>
        Overtime Salary
      </span>

      <strong>
        {{ money(summary.overtime_salary) }}
      </strong>

    </div>


    <div class="salary-details-summary-card salary-details-total-card">

      <span>
        Total Salary
      </span>

      <strong>
        {{ money(summary.total_amount) }}
      </strong>

    </div>

  </div>


  <!-- Period Info -->

  <div class="salary-details-period">

    <i class="bi bi-calendar-range"></i>

    <span>

      Showing:

      <strong>
        {{ formattedFromDate }}
      </strong>

      to

      <strong>
        {{ formattedToDate }}
      </strong>

    </span>

  </div>


  <!-- Table -->

  <div class="salary-details-table-card">

    <div class="salary-details-table-header">

      <div>

        <h4>
          Daily Attendance & Salary
        </h4>

        <p>
          Every date's working time and salary calculation
        </p>

      </div>

      <span class="salary-details-count">
        {{ meta.total }} record(s)
      </span>

    </div>


    <div class="salary-details-table-responsive">

      <table class="salary-details-table">

        <thead>

          <tr>

            <th>SL</th>

            <th>Date</th>

            <th>Shift</th>

            <th>Check In</th>

            <th>Check Out</th>

            <th>Late</th>

            <th>Break</th>

            <th>Worked</th>

            <th>Overtime</th>

            <th>Salary Type</th>

            <th>Regular</th>

            <th>OT Salary</th>

            <th>Total</th>

          </tr>

        </thead>


        <tbody>

          <tr v-if="loading">

            <td
              colspan="13"
              class="salary-details-state-cell"
            >

              <span class="salary-details-spinner"></span>

              Loading daily salary details...

            </td>

          </tr>


          <tr v-else-if="errorMessage">

            <td
              colspan="13"
              class="salary-details-state-cell salary-details-error"
            >

              {{ errorMessage }}

            </td>

          </tr>


          <tr v-else-if="salaryDetails.length === 0">

            <td
              colspan="13"
              class="salary-details-state-cell"
            >

              <div class="salary-details-empty">

                <i class="bi bi-receipt"></i>

                <strong>
                  No daily salary records
                </strong>

                <span>
                  No salary details exist for this period.
                </span>

              </div>

            </td>

          </tr>


          <tr
            v-for="(detail, index) in salaryDetails"
            v-else
            :key="detail.id"
          >

            <!-- SL -->

            <td>
              {{ serialNumber(index) }}
            </td>


            <!-- Date -->

            <td>

              <div class="salary-details-date">

                <strong>
                  {{ detail.salary_date_label }}
                </strong>

                <small>
                  {{ detail.day_label }}
                </small>

              </div>

            </td>


            <!-- Shift -->

            <td>
              {{ detail.scheduled_shift_label }}
            </td>


            <!-- Check In -->

            <td>
              {{ detail.check_in_time_label || '—' }}
            </td>


            <!-- Check Out -->

            <td>

              <div class="salary-details-checkout">

                <span>
                  {{ detail.check_out_time_label || '—' }}
                </span>

                <small
                  v-if="detail.auto_checked_out"
                  class="salary-details-auto-badge"
                >
                  Auto
                </small>

              </div>

            </td>


            <!-- Late -->

            <td>

              <span
                :class="{
                  'salary-details-danger':
                    detail.late_minutes > 10
                }"
              >

                {{ detail.late_duration_label }}

              </span>

            </td>


            <!-- Break -->

            <td>
              {{ detail.break_duration_label }}
            </td>


            <!-- Worked -->

            <td>

              <strong class="salary-details-worked">

                {{ detail.worked_duration_label }}

              </strong>

            </td>


            <!-- Overtime -->

            <td>

              <span
                class="salary-details-overtime"
                :class="{
                  active:
                    detail.overtime_minutes > 0
                }"
              >

                {{ detail.overtime_duration_label }}

              </span>

            </td>


            <!-- Salary Type -->

            <td>

              <div class="salary-details-type-control">

                <select
                  class="salary-details-type-select"
                  :value="detail.salary_type"
                  :disabled="
                    updateLoadingId === detail.id
                    ||
                    !detail.can_update
                  "
                  @change="
                    updateSalaryType(
                      detail,
                      $event
                    )
                  "
                >

                  <option
                    v-for="option in salaryTypeOptions"
                    :key="option.value"
                    :value="option.value"
                  >

                    {{ option.label }}

                  </option>

                </select>


                <small
                  class="salary-details-source"
                  :class="{
                    manual:
                      detail.calculation_source === 'manual'
                  }"
                >

                  {{ detail.calculation_source_label }}

                </small>


                <small
                  v-if="!detail.can_update"
                  class="salary-details-locked"
                >

                  <i class="bi bi-lock"></i>

                  Paid

                </small>

              </div>

            </td>


            <!-- Regular Salary -->

            <td>

              {{ detail.regular_salary_formatted }}

            </td>


            <!-- Overtime Salary -->

            <td>

              {{ detail.overtime_salary_formatted }}

            </td>


            <!-- Total -->

            <td>

              <strong class="salary-details-total">

                {{ detail.total_amount_formatted }}

              </strong>

            </td>

          </tr>

        </tbody>

      </table>

    </div>


    <!-- Pagination -->

    <div
      v-if="meta.last_page > 1"
      class="salary-details-pagination"
    >

      <button
        type="button"
        :disabled="
          meta.current_page <= 1
          ||
          loading
        "
        @click="
          changePage(
            meta.current_page - 1
          )
        "
      >

        Previous

      </button>


      <span>

        Page {{ meta.current_page }}

        of {{ meta.last_page }}

        <small>
          ({{ meta.total }} records)
        </small>

      </span>


      <button
        type="button"
        :disabled="
          meta.current_page >= meta.last_page
          ||
          loading
        "
        @click="
          changePage(
            meta.current_page + 1
          )
        "
      >

        Next

      </button>

    </div>

  </div>

</section>
</template>
<script setup>

import {
  computed,
  onBeforeUnmount,
  onMounted,
  reactive,
  ref,
  watch,
} from 'vue'

import salaryService
  from '@/services/salaryService'


const props =
  defineProps({

    employeeId: {
      type: Number,
      default: null,
    },

  })


defineEmits([
  'back',
])


/*
|--------------------------------------------------------------------------
| State
|--------------------------------------------------------------------------
*/

const salaryDetails =
  ref([])

const employees =
  ref([])

const selectedEmployee =
  ref(null)

const loading =
  ref(false)

const employeesLoading =
  ref(false)

const updateLoadingId =
  ref(null)

const errorMessage =
  ref('')


/*
|--------------------------------------------------------------------------
| Date Helpers
|--------------------------------------------------------------------------
*/

function localDate(
  date = new Date()
) {

  const year =
    date.getFullYear()

  const month =
    String(
      date.getMonth() + 1
    ).padStart(
      2,
      '0'
    )

  const day =
    String(
      date.getDate()
    ).padStart(
      2,
      '0'
    )

  return `${year}-${month}-${day}`
}


function firstDayOfMonth()
{

  const date =
    new Date()

  date.setDate(1)

  return localDate(
    date
  )

}


const todayDate =
  localDate()


/*
|--------------------------------------------------------------------------
| Filters
|--------------------------------------------------------------------------
*/

const filters =
  reactive({

    from_date:
      firstDayOfMonth(),

    to_date:
      todayDate,

    employee_id:
      props.employeeId
        ? String(
            props.employeeId
          )
        : '',

    salary_type: '',

    attendance_status: '',

    payment_status: '',

    page: 1,

    per_page: 15,

  })


/*
|--------------------------------------------------------------------------
| Summary
|--------------------------------------------------------------------------
*/

const summary =
  reactive({

    total_details: 0,

    completed_days: 0,

    regular_salary: 0,

    overtime_salary: 0,

    total_amount: 0,

    worked_minutes: 0,

    overtime_minutes: 0,

    break_minutes: 0,

  })


/*
|--------------------------------------------------------------------------
| Pagination
|--------------------------------------------------------------------------
*/

const meta =
  ref({

    current_page: 1,

    last_page: 1,

    per_page: 15,

    total: 0,

  })


/*
|--------------------------------------------------------------------------
| Salary Types
|--------------------------------------------------------------------------
*/

const salaryTypeOptions = [

  {
    value: 'full_salary',
    label: 'Full Salary',
  },

  {
    value: 'half_salary',
    label: 'Half Salary',
  },

  {
    value: 'full_salary_overtime',
    label: 'Full + Overtime',
  },

  {
    value: 'half_salary_overtime',
    label: 'Half + Overtime',
  },

  {
    value: 'overtime_only',
    label: 'Overtime Only',
  },

  {
    value: 'no_salary',
    label: 'No Salary',
  },

]


/*
|--------------------------------------------------------------------------
| Notification
|--------------------------------------------------------------------------
*/

const message =
  reactive({

    show: false,

    type: 'success',

    text: '',

  })


let messageTimer =
  null


/*
|--------------------------------------------------------------------------
| Computed
|--------------------------------------------------------------------------
*/

const formattedFromDate =
  computed(() => {

    return formatDate(
      filters.from_date
    )

  })


const formattedToDate =
  computed(() => {

    return formatDate(
      filters.to_date
    )

  })


const periodLabel =
  computed(() => {

    return (
      formattedFromDate.value
      +
      ' — '
      +
      formattedToDate.value
    )

  })


const pageTitle =
  computed(() => {

    return (
      selectedEmployee
        .value
        ?.employee_name

      ??

      'Daily Salary Details'
    )

  })


/*
|--------------------------------------------------------------------------
| Response Helpers
|--------------------------------------------------------------------------
*/

function extractPayload(
  response
) {

  return response?.data
    ??
    response

}


function extractCollection(
  response
) {

  const payload =
    extractPayload(
      response
    )

  if (
    Array.isArray(
      payload?.data
    )
  ) {
    return payload.data
  }

  if (
    Array.isArray(
      payload
    )
  ) {
    return payload
  }

  return []

}


function getErrorMessage(
  error,
  fallback
) {

  const errors =
    error
      ?.response
      ?.data
      ?.errors

  if (errors) {

    return Object
      .values(errors)
      .flat()
      .join(' ')

  }

  return (
    error
      ?.response
      ?.data
      ?.message

    ??

    error?.message

    ??

    fallback
  )

}


/*
|--------------------------------------------------------------------------
| Notifications
|--------------------------------------------------------------------------
*/

function showMessage(
  text,
  type = 'success'
) {

  if (messageTimer) {

    clearTimeout(
      messageTimer
    )

  }

  message.text =
    text

  message.type =
    type

  message.show =
    true

  messageTimer =
    setTimeout(
      () => {
        message.show = false
      },
      4000
    )

}


function hideMessage()
{

  message.show =
    false

  if (messageTimer) {

    clearTimeout(
      messageTimer
    )

  }

}


/*
|--------------------------------------------------------------------------
| Employees
|--------------------------------------------------------------------------
*/

async function loadEmployees()
{

  employeesLoading.value =
    true

  try {

    const response =
      await salaryService
        .getEmployees()

    employees.value =
      extractCollection(
        response
      )

    syncSelectedEmployee()

  }

  catch (error) {

    employees.value =
      []

    showMessage(
      getErrorMessage(
        error,
        'Unable to load employee options.'
      ),
      'error'
    )

  }

  finally {

    employeesLoading.value =
      false

  }

}


/*
|--------------------------------------------------------------------------
| Selected Employee
|--------------------------------------------------------------------------
*/

function syncSelectedEmployee()
{

  if (
    !filters.employee_id
  ) {

    selectedEmployee.value =
      null

    return

  }


  const id =
    Number(
      filters.employee_id
    )


  selectedEmployee.value =
    employees.value.find(
      employee =>
        Number(
          employee.id
        ) === id
    )
    ??
    null

}


/*
|--------------------------------------------------------------------------
| Salary Details
|--------------------------------------------------------------------------
*/

async function loadSalaryDetails()
{

  loading.value =
    true

  errorMessage.value =
    ''

  try {

    const response =
      await salaryService
        .getSalaryDetails({

          from_date:
            filters.from_date,

          to_date:
            filters.to_date,

          employee_id:
            filters.employee_id
              ? Number(
                  filters.employee_id
                )
              : undefined,

          salary_type:
            filters.salary_type
            ||
            undefined,

          attendance_status:
            filters.attendance_status
            ||
            undefined,

          payment_status:
            filters.payment_status
            ||
            undefined,

          page:
            filters.page,

          per_page:
            filters.per_page,

        })


    const payload =
      extractPayload(
        response
      )


    salaryDetails.value =
      extractCollection(
        response
      )


    meta.value =
      payload?.meta
      ??
      {
        current_page: 1,
        last_page: 1,
        per_page: 15,
        total: 0,
      }


    const apiSummary =
      payload?.summary
      ??
      {}


    Object.assign(
      summary,
      {

        total_details:
          Number(
            apiSummary.total_details
            ??
            0
          ),

        completed_days:
          Number(
            apiSummary.completed_days
            ??
            0
          ),

        regular_salary:
          Number(
            apiSummary.regular_salary
            ??
            0
          ),

        overtime_salary:
          Number(
            apiSummary.overtime_salary
            ??
            0
          ),

        total_amount:
          Number(
            apiSummary.total_amount
            ??
            0
          ),

        worked_minutes:
          Number(
            apiSummary.worked_minutes
            ??
            0
          ),

        overtime_minutes:
          Number(
            apiSummary.overtime_minutes
            ??
            0
          ),

        break_minutes:
          Number(
            apiSummary.break_minutes
            ??
            0
          ),

      }
    )


    syncSelectedEmployee()

  }

  catch (error) {

    salaryDetails.value =
      []

    resetSummary()

    errorMessage.value =
      getErrorMessage(
        error,
        'Unable to load salary details.'
      )

  }

  finally {

    loading.value =
      false

  }

}


function resetSummary()
{

  Object.assign(
    summary,
    {

      total_details: 0,

      completed_days: 0,

      regular_salary: 0,

      overtime_salary: 0,

      total_amount: 0,

      worked_minutes: 0,

      overtime_minutes: 0,

      break_minutes: 0,

    }
  )

}


/*
|--------------------------------------------------------------------------
| Filters
|--------------------------------------------------------------------------
*/

async function applyFilters()
{

  if (
    filters.to_date <
    filters.from_date
  ) {

    showMessage(
      'To date cannot be before from date.',
      'error'
    )

    return

  }


  filters.page =
    1

  syncSelectedEmployee()

  await loadSalaryDetails()

}


async function clearFilters()
{

  filters.from_date =
    firstDayOfMonth()

  filters.to_date =
    todayDate

  filters.salary_type =
    ''

  filters.attendance_status =
    ''

  filters.payment_status =
    ''

  filters.page =
    1

  await loadSalaryDetails()

}


async function refreshSalaryDetails()
{

  await Promise.all([

    loadSalaryDetails(),

    loadEmployees(),

  ])

  if (
    !errorMessage.value
  ) {

    showMessage(
      'Salary details refreshed successfully.'
    )

  }

}


/*
|--------------------------------------------------------------------------
| Employee Watch
|--------------------------------------------------------------------------
*/

watch(
  () => props.employeeId,
  async (
    employeeId
  ) => {

    filters.employee_id =
      employeeId
        ? String(
            employeeId
          )
        : ''

    filters.page =
      1

    syncSelectedEmployee()

    await loadSalaryDetails()

  }
)


watch(
  () => filters.employee_id,
  () => {

    syncSelectedEmployee()

  }
)


/*
|--------------------------------------------------------------------------
| Salary Type Update
|--------------------------------------------------------------------------
*/

async function updateSalaryType(
  detail,
  event
) {

  const selectElement =
    event.target

  const previousType =
    detail.salary_type

  const newType =
    selectElement.value


  if (
    !detail.can_update
  ) {

    selectElement.value =
      previousType

    showMessage(
      'Paid payroll details are locked.',
      'error'
    )

    return

  }


  if (
    previousType ===
      newType
    ||
    updateLoadingId.value !==
      null
  ) {

    selectElement.value =
      previousType

    return

  }


  const confirmed =
    window.confirm(

      `Change daily salary type for ${detail.salary_date_label}?`

    )


  if (!confirmed) {

    selectElement.value =
      previousType

    return

  }


  updateLoadingId.value =
    detail.id


  try {

    const response =
      await salaryService
        .updateSalaryDetail(
          detail.id,
          {

            salary_type:
              newType,

            notes:
              detail.notes
              ??
              null,

          }
        )


    await loadSalaryDetails()


    showMessage(
      extractPayload(
        response
      )?.message
      ??
      'Daily salary updated successfully.'
    )

  }

  catch (error) {

    selectElement.value =
      previousType

    showMessage(
      getErrorMessage(
        error,
        'Daily salary update failed.'
      ),
      'error'
    )

  }

  finally {

    updateLoadingId.value =
      null

  }

}


/*
|--------------------------------------------------------------------------
| Pagination
|--------------------------------------------------------------------------
*/

async function changePage(
  page
) {

  const targetPage =
    Number(page)

  if (
    targetPage < 1
    ||
    targetPage >
      meta.value.last_page
  ) {

    return

  }

  filters.page =
    targetPage

  await loadSalaryDetails()

}


/*
|--------------------------------------------------------------------------
| Formatters
|--------------------------------------------------------------------------
*/

function serialNumber(
  index
) {

  return (

    (
      Number(
        meta.value
          .current_page
        ||
        1
      )
      -
      1
    )

    *

    Number(
      meta.value
        .per_page
      ||
      15
    )

  )
  +
  index
  +
  1

}


function initials(
  name
) {

  if (!name) {
    return 'NA'
  }

  return String(name)
    .split(' ')
    .filter(Boolean)
    .slice(0, 2)
    .map(
      word =>
        word.charAt(0)
    )
    .join('')
    .toUpperCase()

}


function durationLabel(
  minutes
) {

  const totalMinutes =
    Math.max(
      0,
      Number(
        minutes
        ||
        0
      )
    )

  const hours =
    Math.floor(
      totalMinutes /
      60
    )

  const remaining =
    totalMinutes %
    60

  if (
    hours > 0
    &&
    remaining > 0
  ) {

    return `${hours}h ${remaining}m`

  }

  if (hours > 0) {

    return `${hours}h`

  }

  return `${remaining}m`

}


function formatDate(
  date
) {

  if (!date) {
    return '—'
  }

  const parsed =
    new Date(
      `${date}T00:00:00`
    )

  return new Intl.DateTimeFormat(
    'en-GB',
    {

      day: '2-digit',

      month: 'short',

      year: 'numeric',

    }
  ).format(parsed)

}


function money(
  amount
) {

  return new Intl.NumberFormat(
    'en-BD',
    {

      style: 'currency',

      currency: 'BDT',

      minimumFractionDigits: 2,

    }
  ).format(
    Number(
      amount
      ||
      0
    )
  )

}


/*
|--------------------------------------------------------------------------
| Initial Load
|--------------------------------------------------------------------------
*/

onMounted(
  async () => {

    await Promise.all([

      loadEmployees(),

      loadSalaryDetails(),

    ])

  }
)


onBeforeUnmount(
  () => {

    if (messageTimer) {

      clearTimeout(
        messageTimer
      )

    }

  }
)

</script>