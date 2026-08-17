<template>

<section class="salary-management-page">

  <!-- Notification -->

  <Transition name="salary-message">

    <div
      v-if="message.show"
      class="salary-message"
      :class="`salary-message-${message.type}`"
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

  <div class="salary-page-header">

    <div class="salary-page-title">

      <div class="salary-page-icon">
        <i class="bi bi-cash-stack"></i>
      </div>

      <div>

        <h2>
          Salary Management
        </h2>

        <p>
          Monthly employee salary overview and payment management
        </p>

      </div>

    </div>


    <div class="salary-period-header">

      <span>
        Current Period
      </span>

      <strong>
        {{ formattedPeriod }}
      </strong>

    </div>

  </div>


  <!-- Tabs -->

  <div class="salary-tabs">

    <button
      type="button"
      class="salary-tab-button"
      :class="{
        active: activeTab === 'salary'
      }"
      @click="activeTab = 'salary'"
    >

      <i class="bi bi-wallet2"></i>

      Monthly Salary

    </button>


    <button
      type="button"
      class="salary-tab-button"
      :class="{
        active: activeTab === 'salary_details'
      }"
      @click="activeTab = 'salary_details'"
    >

      <i class="bi bi-receipt-cutoff"></i>

      Daily Salary Details

    </button>

  </div>


  <!-- Monthly Salary -->

  <template v-if="activeTab === 'salary'">

    <!-- Period / Recalculate -->

    <div class="salary-control-card">

      <div class="salary-control-period">

        <div class="salary-control-icon">
          <i class="bi bi-calendar3"></i>
        </div>

        <div>

          <span>
            Salary Period
          </span>

          <strong>
            {{ formattedPeriod }}
          </strong>

          <small>
            Salary is calculated from the 1st day of this month up to today.
          </small>

        </div>

      </div>


      <button
        type="button"
        class="salary-recalculate-btn"
        :disabled="generating"
        @click="recalculateToday"
      >

        <span
          v-if="generating"
          class="salary-button-spinner"
        ></span>

        <i
          v-else
          class="bi bi-arrow-repeat"
        ></i>

        {{
          generating
            ? 'Recalculating...'
            : 'Recalculate Today'
        }}

      </button>

    </div>


    <!-- Summary -->

    <div class="salary-summary-grid">

      <div class="salary-summary-card">

        <span>
          Employees
        </span>

        <strong>
          {{ summary.total_payrolls }}
        </strong>

        <small>
          Salary records
        </small>

      </div>


      <div class="salary-summary-card">

        <span>
          Worked Time
        </span>

        <strong>
          {{ durationLabel(summary.worked_minutes) }}
        </strong>

        <small>
          Total working time
        </small>

      </div>


      <div class="salary-summary-card salary-summary-overtime">

        <span>
          Overtime
        </span>

        <strong>
          {{ durationLabel(summary.overtime_minutes) }}
        </strong>

        <small>
          Total overtime
        </small>

      </div>


      <div class="salary-summary-card">

        <span>
          Regular Salary
        </span>

        <strong>
          {{ money(summary.regular_salary) }}
        </strong>

      </div>


      <div class="salary-summary-card salary-summary-paid">

        <span>
          Paid
        </span>

        <strong>
          {{ money(summary.paid_amount) }}
        </strong>

        <small>
          {{ summary.paid_count }} employee(s)
        </small>

      </div>


      <div class="salary-summary-card salary-summary-total">

        <span>
          Total Salary
        </span>

        <strong>
          {{ money(summary.total_amount) }}
        </strong>

        <small>
          {{ summary.unpaid_count }} unpaid
        </small>

      </div>

    </div>


    <!-- Filters -->

    <div class="salary-filter-card">

      <div class="salary-filter-grid">

        <div class="salary-filter-group salary-search-group">

          <label>
            Search Employee
          </label>

          <div class="salary-search-input">

            <i class="bi bi-search"></i>

            <input
              v-model.trim="filters.search"
              type="text"
              placeholder="Name, phone or email"
              @keyup.enter="applyFilters"
            />

          </div>

        </div>


        <div class="salary-filter-group">

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


        <div class="salary-filter-group">

          <label>
            Payment Status
          </label>

          <select
            v-model="filters.payment_status"
          >

            <option value="">
              All Statuses
            </option>

            <option value="paid">
              Paid
            </option>

            <option value="unpaid">
              Unpaid
            </option>

          </select>

        </div>


        <div class="salary-filter-actions">

          <button
            type="button"
            class="salary-filter-btn"
            :disabled="loading"
            @click="applyFilters"
          >

            <i class="bi bi-funnel"></i>

            Apply

          </button>


          <button
            type="button"
            class="salary-clear-btn"
            :disabled="loading"
            @click="clearFilters"
          >
            Clear
          </button>

        </div>

      </div>

    </div>


    <!-- Table -->

    <div class="salary-table-card">

      <div class="salary-table-title">

        <div>

          <h3>
            Employee Salary Summary
          </h3>

          <p>
            {{ formattedPeriod }}
          </p>

        </div>

        <span class="salary-result-count">
          {{ meta.total }} employee(s)
        </span>

      </div>


      <div class="salary-table-responsive">

        <table class="salary-table">

          <thead>

            <tr>

              <th>
                SL
              </th>

              <th>
                Employee
              </th>

              <th>
                Role
              </th>

              <th>
                Hourly Rate
              </th>

              <th>
                Worked
              </th>

              <th>
                Overtime
              </th>

              <th>
                Regular
              </th>

              <th>
                OT Salary
              </th>

              <th>
                Adjustment
              </th>

              <th>
                Total
              </th>

              <th>
                Status
              </th>

              <th>
                Action
              </th>

            </tr>

          </thead>


          <tbody>

            <!-- Loading -->

            <tr v-if="loading">

              <td
                colspan="12"
                class="salary-table-state"
              >

                <span class="salary-table-spinner"></span>

                Loading monthly salary...

              </td>

            </tr>


            <!-- Error -->

            <tr v-else-if="errorMessage">

              <td
                colspan="12"
                class="salary-table-state salary-table-error"
              >

                {{ errorMessage }}

              </td>

            </tr>


            <!-- Empty -->

            <tr v-else-if="salaries.length === 0">

              <td
                colspan="12"
                class="salary-table-state"
              >

                <div class="salary-empty-state">

                  <i class="bi bi-wallet2"></i>

                  <strong>
                    No salary records found
                  </strong>

                  <span>
                    Run today's salary calculation or adjust your filters.
                  </span>

                </div>

              </td>

            </tr>


            <!-- Data -->

            <tr
              v-for="(salary, index) in salaries"
              v-else
              :key="salary.id"
            >

              <!-- SL -->

              <td>
                {{ serialNumber(index) }}
              </td>


              <!-- Employee -->

              <td>

                <div class="salary-employee-cell">

                  <div class="salary-avatar">
                    {{ initials(salary.employee_name) }}
                  </div>

                  <div>

                    <strong>
                      {{ salary.employee_name }}
                    </strong>

                    <small>
                      {{ salary.employee_phone || 'No phone' }}
                    </small>

                  </div>

                </div>

              </td>


              <!-- Role -->

              <td>

                <span class="salary-role-badge">
                  {{ salary.role_label || salary.role_name || 'Staff' }}
                </span>

              </td>


              <!-- Hourly Rate -->

              <td>
                {{ salary.hourly_rate_formatted }}
              </td>


              <!-- Worked -->

              <td>

                <div class="salary-time-cell">

                  <strong>
                    {{ salary.worked_duration_label }}
                  </strong>

                  <small>
                    {{ salary.working_days }} day(s)
                  </small>

                </div>

              </td>


              <!-- Overtime -->

              <td>

                <span
                  class="salary-overtime-value"
                  :class="{
                    active:
                      salary.overtime_minutes > 0
                  }"
                >

                  {{ salary.overtime_duration_label }}

                </span>

              </td>


              <!-- Regular -->

              <td>
                {{ salary.regular_salary_formatted }}
              </td>


              <!-- OT Salary -->

              <td>
                {{ salary.overtime_salary_formatted }}
              </td>


              <!-- Adjustment -->

              <td>
                {{ salary.adjustment_amount_formatted }}
              </td>


              <!-- Total -->

              <td>

                <div class="salary-total-cell">

                  <strong>
                    {{ salary.total_amount_formatted }}
                  </strong>

                </div>

              </td>


              <!-- Status -->

              <td>

                <div class="salary-status-wrapper">

                  <span
                    class="salary-status-badge"
                    :class="
                      salary.payment_status === 'paid'
                        ? 'paid'
                        : 'unpaid'
                    "
                  >

                    <i
                      class="bi"
                      :class="
                        salary.payment_status === 'paid'
                          ? 'bi-check-circle'
                          : 'bi-clock'
                      "
                    ></i>

                    {{ salary.payment_status_label }}

                  </span>


                  <select
                    class="salary-payment-select"
                    :value="salary.payment_status"
                    :disabled="
                      paymentLoadingId === salary.id
                    "
                    @change="
                      updatePaymentStatus(
                        salary,
                        $event
                      )
                    "
                  >

                    <option value="unpaid">
                      Unpaid
                    </option>

                    <option value="paid">
                      Paid
                    </option>

                  </select>

                </div>

              </td>


              <!-- Actions -->

              <td>

                <div class="salary-actions">

                  <button
                    type="button"
                    class="salary-action-btn salary-view-btn"
                    title="View employee salary details"
                    @click="openDetails(salary)"
                  >

                    <i class="bi bi-eye"></i>

                  </button>


                  <button
                    type="button"
                    class="salary-action-btn salary-edit-btn"
                    title="Edit adjustment and notes"
                    :disabled="!salary.can_edit"
                    @click="openEditModal(salary)"
                  >

                    <i class="bi bi-pencil"></i>

                  </button>


                  <button
                    type="button"
                    class="salary-action-btn salary-delete-btn"
                    title="Delete unpaid salary"
                    :disabled="
                      !salary.can_delete
                      ||
                      deleteLoadingId === salary.id
                    "
                    @click="deleteSalary(salary)"
                  >

                    <span
                      v-if="
                        deleteLoadingId === salary.id
                      "
                      class="salary-inline-spinner"
                    ></span>

                    <i
                      v-else
                      class="bi bi-trash"
                    ></i>

                  </button>

                </div>

              </td>

            </tr>

          </tbody>

        </table>

      </div>


      <!-- Pagination -->

      <div
        v-if="meta.last_page > 1"
        class="salary-pagination"
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
            ({{ meta.total }} employees)
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

  </template>


  <!-- Daily Details -->

  <SalaryDetailsSection
  v-else
  :employee-id="selectedDetailEmployeeId"
  @back="activeTab = 'salary'"
/>


  <!-- Edit Modal -->

  <div
    v-if="showEditModal"
    class="salary-modal-overlay"
    @mousedown.self="closeEditModal"
  >

    <div class="salary-modal">

      <div class="salary-modal-header">

        <div>

          <h3>
            Edit Salary
          </h3>

          <p>
            {{ selectedSalary?.employee_name }}
          </p>

        </div>

        <button
          type="button"
          @click="closeEditModal"
        >
          <i class="bi bi-x-lg"></i>
        </button>

      </div>


      <form @submit.prevent="saveSalary">

        <div class="salary-modal-body">

          <div class="salary-edit-summary">

            <div>

              <span>
                Regular Salary
              </span>

              <strong>
                {{ selectedSalary?.regular_salary_formatted }}
              </strong>

            </div>


            <div>

              <span>
                Overtime Salary
              </span>

              <strong>
                {{ selectedSalary?.overtime_salary_formatted }}
              </strong>

            </div>


            <div>

              <span>
                Worked Time
              </span>

              <strong>
                {{ selectedSalary?.worked_duration_label || '0m' }}
              </strong>

            </div>


            <div>

              <span>
                Overtime
              </span>

              <strong>
                {{ selectedSalary?.overtime_duration_label || '0m' }}
              </strong>

            </div>

          </div>


          <div class="salary-filter-group">

            <label>
              Adjustment Amount
            </label>

            <input
              v-model="editForm.adjustment_amount"
              type="number"
              step="0.01"
              placeholder="Example: 500 or -200"
            />

            <small>
              Positive value adds a bonus. Negative value applies a deduction.
            </small>

          </div>


          <div class="salary-filter-group">

            <label>
              Notes
            </label>

            <textarea
              v-model.trim="editForm.notes"
              rows="4"
              maxlength="2000"
              placeholder="Optional salary notes"
            ></textarea>

          </div>


          <div class="salary-final-preview">

            <span>
              Final Amount
            </span>

            <strong>
              {{ editTotalPreview }}
            </strong>

          </div>

        </div>


        <div class="salary-modal-footer">

          <button
            type="button"
            class="salary-clear-btn"
            :disabled="savingEdit"
            @click="closeEditModal"
          >
            Cancel
          </button>


          <button
            type="submit"
            class="salary-generate-btn"
            :disabled="savingEdit"
          >

            <span
              v-if="savingEdit"
              class="salary-button-spinner"
            ></span>

            <i
              v-else
              class="bi bi-check-lg"
            ></i>

            {{
              savingEdit
                ? 'Saving...'
                : 'Save Salary'
            }}

          </button>

        </div>

      </form>

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

import SalaryDetailsSection
  from '@/components/salary/SalaryDetailsSection.vue'


/*
|--------------------------------------------------------------------------
| Page State
|--------------------------------------------------------------------------
*/
const selectedDetailEmployeeId =
  ref(null)

const activeTab =
  ref('salary')

const salaries =
  ref([])

const employees =
  ref([])

const loading =
  ref(false)

const generating =
  ref(false)

const employeesLoading =
  ref(false)

const paymentLoadingId =
  ref(null)

const deleteLoadingId =
  ref(null)

const errorMessage =
  ref('')

const showEditModal =
  ref(false)

const selectedSalary =
  ref(null)

const savingEdit =
  ref(false)


/*
|--------------------------------------------------------------------------
| Current Date Helpers
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

  return localDate(date)
}


const todayDate =
  localDate()


const currentMonthStart =
  firstDayOfMonth()


/*
|--------------------------------------------------------------------------
| Filters
|--------------------------------------------------------------------------
*/

const filters = reactive({

  search: '',

  employee_id: '',

  payment_status: '',

  page: 1,

  per_page: 10,

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

    per_page: 10,

    total: 0,

  })


/*
|--------------------------------------------------------------------------
| Summary
|--------------------------------------------------------------------------
*/

const summary =
  reactive({

    total_payrolls: 0,

    paid_count: 0,

    unpaid_count: 0,

    regular_salary: 0,

    overtime_salary: 0,

    adjustment_amount: 0,

    total_amount: 0,

    paid_amount: 0,

    unpaid_amount: 0,

    scheduled_minutes: 0,

    worked_minutes: 0,

    break_minutes: 0,

    overtime_minutes: 0,

  })


/*
|--------------------------------------------------------------------------
| Edit Form
|--------------------------------------------------------------------------
*/

const editForm =
  reactive({

    adjustment_amount: 0,

    notes: '',

  })


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

const formattedPeriod =
  computed(() => {

    return (
      formatDate(
        currentMonthStart
      )
      +
      ' — '
      +
      formatDate(
        todayDate
      )
    )

  })


const editTotalPreview =
  computed(() => {

    if (!selectedSalary.value) {
      return money(0)
    }

    return money(

      Number(
        selectedSalary
          .value
          .regular_salary
        ||
        0
      )

      +

      Number(
        selectedSalary
          .value
          .overtime_salary
        ||
        0
      )

      +

      Number(
        editForm
          .adjustment_amount
        ||
        0
      )

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
| Notification
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
| Load Employees
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

  }

  catch (error) {

    employees.value =
      []

    showMessage(
      getErrorMessage(
        error,
        'Unable to load employees.'
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
| Load Salaries
|--------------------------------------------------------------------------
*/

async function loadSalaries()
{

  loading.value =
    true

  errorMessage.value =
    ''

  try {

    const response =
      await salaryService
        .getSalaries({

          search:
            filters.search
            ||
            undefined,

          employee_id:
            filters.employee_id
              ? Number(
                  filters.employee_id
                )
              : undefined,

          payment_status:
            filters.payment_status
            ||
            undefined,

          from_date:
            currentMonthStart,

          to_date:
            todayDate,

          page:
            filters.page,

          per_page:
            filters.per_page,

        })


    const payload =
      extractPayload(
        response
      )


    salaries.value =
      extractCollection(
        response
      )


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

        total_payrolls:
          Number(
            payload
              ?.summary
              ?.total_payrolls
            ??
            0
          ),

        paid_count:
          Number(
            payload
              ?.summary
              ?.paid_count
            ??
            0
          ),

        unpaid_count:
          Number(
            payload
              ?.summary
              ?.unpaid_count
            ??
            0
          ),

        regular_salary:
          Number(
            payload
              ?.summary
              ?.regular_salary
            ??
            0
          ),

        overtime_salary:
          Number(
            payload
              ?.summary
              ?.overtime_salary
            ??
            0
          ),

        adjustment_amount:
          Number(
            payload
              ?.summary
              ?.adjustment_amount
            ??
            0
          ),

        total_amount:
          Number(
            payload
              ?.summary
              ?.total_amount
            ??
            0
          ),

        paid_amount:
          Number(
            payload
              ?.summary
              ?.paid_amount
            ??
            0
          ),

        unpaid_amount:
          Number(
            payload
              ?.summary
              ?.unpaid_amount
            ??
            0
          ),

        scheduled_minutes:
          Number(
            payload
              ?.summary
              ?.scheduled_minutes
            ??
            0
          ),

        worked_minutes:
          Number(
            payload
              ?.summary
              ?.worked_minutes
            ??
            0
          ),

        break_minutes:
          Number(
            payload
              ?.summary
              ?.break_minutes
            ??
            0
          ),

        overtime_minutes:
          Number(
            payload
              ?.summary
              ?.overtime_minutes
            ??
            0
          ),

      }
    )

  }

  catch (error) {

    salaries.value =
      []

    errorMessage.value =
      getErrorMessage(
        error,
        'Unable to load monthly salary.'
      )

  }

  finally {

    loading.value =
      false

  }

}


/*
|--------------------------------------------------------------------------
| Recalculate Today
|--------------------------------------------------------------------------
*/

async function recalculateToday()
{

  generating.value =
    true

  try {

    const response =
      await salaryService
        .generateSalaries({

          from_date:
            todayDate,

          to_date:
            todayDate,

          employee_id:
            null,

        })


    filters.page =
      1

    await loadSalaries()

    showMessage(
      extractPayload(
        response
      )?.message
      ??
      'Salary recalculation completed successfully.'
    )

  }

  catch (error) {

    showMessage(
      getErrorMessage(
        error,
        'Salary recalculation failed.'
      ),
      'error'
    )

  }

  finally {

    generating.value =
      false

  }

}


/*
|--------------------------------------------------------------------------
| Filters
|--------------------------------------------------------------------------
*/

async function applyFilters()
{

  filters.page =
    1

  await loadSalaries()

}


async function clearFilters()
{

  filters.search =
    ''

  filters.employee_id =
    ''

  filters.payment_status =
    ''

  filters.page =
    1

  await loadSalaries()

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

  await loadSalaries()

}


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
      10
    )

  )
  +
  index
  +
  1

}


/*
|--------------------------------------------------------------------------
| Employee Details
|--------------------------------------------------------------------------
*/

function openDetails(
  salary
) {

  selectedDetailEmployeeId.value =
    Number(
      salary.employee_id
    )

  activeTab.value =
    'salary_details'
}


/*
|--------------------------------------------------------------------------
| Payment Status
|--------------------------------------------------------------------------
*/

async function updatePaymentStatus(
  salary,
  event
) {

  const selectElement =
    event.target

  const previousStatus =
    salary.payment_status

  const newStatus =
    selectElement.value

  if (
    newStatus ===
      previousStatus
    ||
    paymentLoadingId.value !==
      null
  ) {

    selectElement.value =
      previousStatus

    return

  }


  const confirmed =
    window.confirm(

      newStatus === 'paid'

        ?

        `Mark ${salary.employee_name}'s salary as paid?`

        :

        `Change ${salary.employee_name}'s salary back to unpaid?`

    )


  if (!confirmed) {

    selectElement.value =
      previousStatus

    return

  }


  paymentLoadingId.value =
    salary.id


  try {

    const response =
      await salaryService
        .updatePaymentStatus(
          salary.id,
          newStatus
        )

    await loadSalaries()

    showMessage(
      extractPayload(
        response
      )?.message
      ??
      'Payment status updated successfully.'
    )

  }

  catch (error) {

    selectElement.value =
      previousStatus

    showMessage(
      getErrorMessage(
        error,
        'Payment status update failed.'
      ),
      'error'
    )

  }

  finally {

    paymentLoadingId.value =
      null

  }

}


/*
|--------------------------------------------------------------------------
| Edit Salary
|--------------------------------------------------------------------------
*/

function openEditModal(
  salary
) {

  if (!salary.can_edit) {
    return
  }

  selectedSalary.value = {
    ...salary,
  }

  editForm.adjustment_amount =
    Number(
      salary.adjustment_amount
      ||
      0
    )

  editForm.notes =
    salary.notes
    ||
    ''

  showEditModal.value =
    true

}


function closeEditModal()
{

  if (savingEdit.value) {
    return
  }

  showEditModal.value =
    false

  selectedSalary.value =
    null

  editForm.adjustment_amount =
    0

  editForm.notes =
    ''

}


async function saveSalary()
{

  if (!selectedSalary.value) {
    return
  }

  savingEdit.value =
    true

  try {

    const response =
      await salaryService
        .updateSalary(
          selectedSalary.value.id,
          {

            adjustment_amount:
              Number(
                editForm
                  .adjustment_amount
                ||
                0
              ),

            notes:
              editForm.notes
              ||
              null,

          }
        )

    closeEditModal()

    await loadSalaries()

    showMessage(
      extractPayload(
        response
      )?.message
      ??
      'Salary updated successfully.'
    )

  }

  catch (error) {

    showMessage(
      getErrorMessage(
        error,
        'Salary update failed.'
      ),
      'error'
    )

  }

  finally {

    savingEdit.value =
      false

  }

}


/*
|--------------------------------------------------------------------------
| Delete Salary
|--------------------------------------------------------------------------
*/

async function deleteSalary(
  salary
) {

  if (!salary.can_delete) {
    return
  }


  const confirmed =
    window.confirm(
      `Delete the unpaid salary payroll for ${salary.employee_name}?`
    )


  if (!confirmed) {
    return
  }


  deleteLoadingId.value =
    salary.id


  try {

    const response =
      await salaryService
        .deleteSalary(
          salary.id
        )

    if (
      salaries.value.length === 1
      &&
      filters.page > 1
    ) {

      filters.page -=
        1

    }

    await loadSalaries()

    showMessage(
      extractPayload(
        response
      )?.message
      ??
      'Salary deleted successfully.'
    )

  }

  catch (error) {

    showMessage(
      getErrorMessage(
        error,
        'Salary deletion failed.'
      ),
      'error'
    )

  }

  finally {

    deleteLoadingId.value =
      null

  }

}


/*
|--------------------------------------------------------------------------
| Formatters
|--------------------------------------------------------------------------
*/

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
      totalMinutes / 60
    )

  const remaining =
    totalMinutes % 60

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


/*
|--------------------------------------------------------------------------
| Initial Load
|--------------------------------------------------------------------------
*/

onMounted(
  async () => {

    await Promise.all([
      loadEmployees(),
      loadSalaries(),
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
<style
  src="@/assets/css/salary/salary-management.css"
></style>
<style
  src="@/assets/css/salary/salary-details.css"
></style>