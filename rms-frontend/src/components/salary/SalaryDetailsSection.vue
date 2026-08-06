<template>

<section class="salary-details-section">

  <!-- Message -->

  <Transition name="salary-detail-message">

    <div
      v-if="message.show"
      class="salary-detail-message"
      :class="`salary-detail-message-${message.type}`"
    >

      <i
        class="bi"
        :class="
          message.type === 'error'
            ? 'bi-exclamation-circle'
            : 'bi-check-circle'
        "
      ></i>

      <span>
        {{ message.text }}
      </span>

      <button
        type="button"
        @click="hideMessage"
      >
        <i class="bi bi-x-lg"></i>
      </button>

    </div>

  </Transition>


  <!-- Header -->

  <div class="salary-details-header">

    <div>

      <h3>
        Daily Salary Details
      </h3>

      <p>
        Review attendance-based salary calculations and update daily salary types
      </p>

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
          Employee
        </label>

        <select
          v-model="filters.employee_id"
          :disabled="employeesLoading"
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


      <div class="salary-details-filter-group">

        <label>
          Salary Type
        </label>

        <select v-model="filters.salary_type">

          <option value="">
            All Salary Types
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

        <select v-model="filters.attendance_status">

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
          Payment Status
        </label>

        <select v-model="filters.payment_status">

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


  <!-- Current Period -->

  <div class="salary-details-period">

    <i class="bi bi-calendar-range"></i>

    <span>
      Showing salary details from
      <strong>{{ formattedFromDate }}</strong>
      to
      <strong>{{ formattedToDate }}</strong>
    </span>

  </div>


  <!-- Summary -->

  <div class="salary-details-summary-grid">

    <div class="salary-details-summary-card">

      <span>
        Total Days
      </span>

      <strong>
        {{ summary.total_details }}
      </strong>

    </div>


    <div class="salary-details-summary-card">

      <span>
        Full Salary
      </span>

      <strong>
        {{ summary.full_salary_count }}
      </strong>

    </div>


    <div class="salary-details-summary-card">

      <span>
        Half Salary
      </span>

      <strong>
        {{ summary.half_salary_count }}
      </strong>

    </div>


    <div class="salary-details-summary-card">

      <span>
        No Salary
      </span>

      <strong>
        {{ summary.no_salary_count }}
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
        Total Amount
      </span>

      <strong>
        {{ money(summary.total_amount) }}
      </strong>

    </div>

  </div>


  <!-- Table -->

  <div class="salary-details-table-card">

    <div class="salary-details-table-responsive">

      <table class="salary-details-table">

        <thead>

          <tr>

            <th>SL</th>

            <th>Employee</th>

            <th>Phone</th>

            <th>Date</th>

            <th>Shift</th>

            <th>Check In</th>

            <th>Check Out</th>

            <th>Late</th>

            <th>Break</th>

            <th>Overtime</th>

            <th>Working Time</th>

            <th>Salary Update</th>

            <th>Amount</th>

          </tr>

        </thead>


        <tbody>

          <tr v-if="loading">

            <td
              colspan="13"
              class="salary-details-state-cell"
            >

              <span class="salary-details-spinner"></span>

              Loading salary details...

            </td>

          </tr>


          <tr v-else-if="errorMessage">

            <td
              colspan="13"
              class="
                salary-details-state-cell
                salary-details-error
              "
            >
              {{ errorMessage }}
            </td>

          </tr>


          <tr v-else-if="salaryDetails.length === 0">

            <td
              colspan="13"
              class="salary-details-state-cell"
            >
              No salary details found for the selected date range.
            </td>

          </tr>


          <tr
            v-for="(detail, index) in salaryDetails"
            v-else
            :key="detail.id"
          >

            <td>
              {{ serialNumber(index) }}
            </td>


            <td>

              <div class="salary-details-employee">

                <div class="salary-details-avatar">

                  {{
                    initials(
                      detail.employee_name
                    )
                  }}

                </div>

                <div>

                  <strong>
                    {{ detail.employee_name }}
                  </strong>

                  <small>
                    {{ detail.employee_email || '—' }}
                  </small>

                </div>

              </div>

            </td>


            <td>
              {{ detail.employee_phone || '—' }}
            </td>


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


            <td>
              {{ detail.scheduled_shift_label }}
            </td>


            <td>
              {{ detail.check_in_time_label || '—' }}
            </td>


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


            <td>

              <span
                :class="{
                  'salary-details-danger':
                    detail.late_minutes > 10,
                }"
              >
                {{ detail.late_duration_label }}
              </span>

            </td>


            <td>
              {{ detail.break_duration_label }}
            </td>


            <td>

              <span
                :class="{
                  'salary-details-overtime':
                    detail.overtime_minutes > 0,
                }"
              >
                {{ detail.overtime_duration_label }}
              </span>

            </td>


            <td>
              {{ detail.worked_duration_label }}
            </td>


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
                  :title="
                    detail.can_update
                      ? 'Update daily salary calculation'
                      : 'Paid payroll is locked'
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
                      detail.calculation_source === 'manual',
                  }"
                >

                  {{
                    detail.calculation_source_label
                  }}

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


            <td>

              <div class="salary-details-amount">

                <strong>
                  {{ detail.total_amount_formatted }}
                </strong>

                <small>
                  Regular:
                  {{ detail.regular_salary_formatted }}
                </small>

                <small
                  v-if="detail.overtime_salary > 0"
                >
                  Overtime:
                  {{ detail.overtime_salary_formatted }}
                </small>

              </div>

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
          ({{ meta.total }} details)
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
} from 'vue'

import salaryService
  from '@/services/salaryService'


const emit = defineEmits([
  'salary-updated',
])


const salaryDetails =
  ref([])

const employees =
  ref([])

const loading =
  ref(false)

const employeesLoading =
  ref(false)

const updateLoadingId =
  ref(null)

const errorMessage =
  ref('')


function localDate(
  date = new Date()
) {
  const year =
    date.getFullYear()

  const month =
    String(
      date.getMonth() + 1
    ).padStart(2, '0')

  const day =
    String(
      date.getDate()
    ).padStart(2, '0')

  return `${year}-${month}-${day}`
}


function firstDayOfMonth()
{
  const date =
    new Date()

  date.setDate(1)

  return localDate(date)
}


const todayDate =
  localDate()


const filters = reactive({

  from_date:
    firstDayOfMonth(),

  to_date:
    todayDate,

  employee_id: '',

  salary_type: '',

  attendance_status: '',

  payment_status: '',

  page: 1,

  per_page: 10,

})


const summary = reactive({

  total_details: 0,

  full_salary_count: 0,

  half_salary_count: 0,

  overtime_only_count: 0,

  no_salary_count: 0,

  regular_salary: 0,

  overtime_salary: 0,

  total_amount: 0,

})


const meta = ref({

  current_page: 1,

  last_page: 1,

  per_page: 10,

  total: 0,

})


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
    label: 'Full Salary + Overtime',
  },

  {
    value: 'half_salary_overtime',
    label: 'Half Salary + Overtime',
  },

  {
    value: 'overtime_only',
    label: 'Overtime Salary Only',
  },

  {
    value: 'no_salary',
    label: 'No Salary',
  },

]


const message = reactive({

  show: false,

  type: 'success',

  text: '',

})


let messageTimer = null


const formattedFromDate = computed(() => {

  return formatDate(
    filters.from_date
  )

})


const formattedToDate = computed(() => {

  return formatDate(
    filters.to_date
  )

})


function extractPayload(response)
{
  return response?.data
    ??
    response
}


function extractCollection(response)
{
  const payload =
    extractPayload(response)

  if (Array.isArray(payload?.data)) {
    return payload.data
  }

  if (Array.isArray(payload)) {
    return payload
  }

  return []
}


function getErrorMessage(
  error,
  fallback
) {
  const errors =
    error?.response?.data?.errors

  if (errors) {
    return Object.values(errors)
      .flat()
      .join(' ')
  }

  return (
    error?.response?.data?.message
    ??
    error?.message
    ??
    fallback
  )
}


function showMessage(
  text,
  type = 'success'
) {
  if (messageTimer) {
    clearTimeout(messageTimer)
  }

  message.text = text

  message.type = type

  message.show = true

  messageTimer =
    setTimeout(() => {

      message.show = false

    }, 4000)
}


function hideMessage()
{
  message.show = false

  if (messageTimer) {
    clearTimeout(messageTimer)
  }
}


async function loadEmployees()
{
  employeesLoading.value = true

  try {
    const response =
      await salaryService
        .getEmployees()

    employees.value =
      extractCollection(response)
  }
  catch (error) {
    employees.value = []

    showMessage(
      getErrorMessage(
        error,
        'Unable to load employee options.'
      ),
      'error'
    )
  }
  finally {
    employeesLoading.value = false
  }
}


async function loadSalaryDetails()
{
  loading.value = true

  errorMessage.value = ''

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
      extractPayload(response)

    salaryDetails.value =
      extractCollection(response)

    meta.value =
      payload?.meta
      ??
      {
        current_page: 1,
        last_page: 1,
        per_page: 10,
        total: 0,
      }

    Object.assign(
      summary,
      {
        total_details:
          Number(
            payload?.summary
              ?.total_details
            ||
            0
          ),

        full_salary_count:
          Number(
            payload?.summary
              ?.full_salary_count
            ||
            0
          ),

        half_salary_count:
          Number(
            payload?.summary
              ?.half_salary_count
            ||
            0
          ),

        overtime_only_count:
          Number(
            payload?.summary
              ?.overtime_only_count
            ||
            0
          ),

        no_salary_count:
          Number(
            payload?.summary
              ?.no_salary_count
            ||
            0
          ),

        regular_salary:
          Number(
            payload?.summary
              ?.regular_salary
            ||
            0
          ),

        overtime_salary:
          Number(
            payload?.summary
              ?.overtime_salary
            ||
            0
          ),

        total_amount:
          Number(
            payload?.summary
              ?.total_amount
            ||
            0
          ),
      }
    )
  }
  catch (error) {
    salaryDetails.value = []

    resetSummary()

    errorMessage.value =
      getErrorMessage(
        error,
        'Unable to load salary details.'
      )
  }
  finally {
    loading.value = false
  }
}


function resetSummary()
{
  Object.assign(
    summary,
    {
      total_details: 0,
      full_salary_count: 0,
      half_salary_count: 0,
      overtime_only_count: 0,
      no_salary_count: 0,
      regular_salary: 0,
      overtime_salary: 0,
      total_amount: 0,
    }
  )
}


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

  filters.page = 1

  await loadSalaryDetails()
}


async function clearFilters()
{
  filters.from_date =
    firstDayOfMonth()

  filters.to_date =
    todayDate

  filters.employee_id = ''

  filters.salary_type = ''

  filters.attendance_status = ''

  filters.payment_status = ''

  filters.page = 1

  await loadSalaryDetails()
}


async function refreshSalaryDetails()
{
  await Promise.all([
    loadSalaryDetails(),
    loadEmployees(),
  ])

  if (!errorMessage.value) {
    showMessage(
      'Salary details refreshed successfully.'
    )
  }
}


defineExpose({
  refreshSalaryDetails,
})


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

  if (!detail.can_update) {
    selectElement.value =
      previousType

    showMessage(
      'Paid payroll details are locked.',
      'error'
    )

    return
  }

  if (
    previousType === newType
    ||
    updateLoadingId.value !== null
  ) {
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
              ||
              null,
          }
        )

    await loadSalaryDetails()

    emit(
      'salary-updated'
    )

    showMessage(
      extractPayload(response)
        ?.message
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
    updateLoadingId.value = null
  }
}


async function changePage(page)
{
  const targetPage =
    Number(page)

  if (
    targetPage < 1
    ||
    targetPage > meta.value.last_page
  ) {
    return
  }

  filters.page =
    targetPage

  await loadSalaryDetails()
}


function serialNumber(index)
{
  return (
    (
      Number(
        meta.value.current_page
        ||
        1
      )
      -
      1
    )
    *
    Number(
      meta.value.per_page
      ||
      10
    )
  )
  +
  index
  +
  1
}


function initials(name)
{
  if (!name) {
    return 'NA'
  }

  return String(name)
    .split(' ')
    .filter(Boolean)
    .slice(0, 2)
    .map(
      (word) =>
        word.charAt(0)
    )
    .join('')
    .toUpperCase()
}


function formatDate(date)
{
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


function money(amount)
{
  return new Intl.NumberFormat(
    'en-BD',
    {
      style: 'currency',
      currency: 'BDT',
      minimumFractionDigits: 2,
    }
  ).format(
    Number(amount || 0)
  )
}


onMounted(async () => {
  await Promise.all([
    loadEmployees(),
    loadSalaryDetails(),
  ])
})


onBeforeUnmount(() => {
  if (messageTimer) {
    clearTimeout(messageTimer)
  }
})

</script>


<style>

@import '@/assets/css/salary/salary-details.css';

</style>