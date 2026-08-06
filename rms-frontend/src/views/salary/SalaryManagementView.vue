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
          Generate, review and manage employee salary payments
        </p>

      </div>

    </div>

  </div>


  <!-- Tabs -->

  <div class="salary-tabs">

    <button
      type="button"
      class="salary-tab-button"
      :class="{
        active:
          activeTab === 'salary',
      }"
      @click="
        activeTab = 'salary'
      "
    >

      <i class="bi bi-wallet2"></i>

      Salary

    </button>


    <button
      type="button"
      class="salary-tab-button"
      :class="{
        active:
          activeTab === 'salary_details',
      }"
      @click="
        activeTab = 'salary_details'
      "
    >

      <i class="bi bi-receipt-cutoff"></i>

      Salary Details

    </button>

  </div>


  <!-- Salary Summary -->

  <template v-if="activeTab === 'salary'">

    <!-- Generation Card -->

    <div class="salary-generation-card">

      <div class="salary-generation-title">

        <div>

          <h3>
            Generate Salary
          </h3>

          <p>
            Select a date range and generate attendance-based salary
          </p>

        </div>

      </div>


      <div class="salary-generation-grid">

        <div class="salary-filter-group">

          <label>
            From Date
          </label>

          <input
            v-model="generateForm.from_date"
            type="date"
            :max="todayDate"
          />

        </div>


        <div class="salary-filter-group">

          <label>
            To Date
          </label>

          <input
            v-model="generateForm.to_date"
            type="date"
            :min="generateForm.from_date"
            :max="todayDate"
          />

        </div>


        <div class="salary-filter-group">

          <label>
            Employee
          </label>

          <select
            v-model="generateForm.employee_id"
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

              —

              {{ employee.hourly_rate_formatted }}

            </option>

          </select>

        </div>


        <button
          type="button"
          class="salary-generate-btn"
          :disabled="
            generating
            ||
            !generateForm.from_date
            ||
            !generateForm.to_date
          "
          @click="generateSalaries"
        >

          <span
            v-if="generating"
            class="salary-button-spinner"
          ></span>

          <i
            v-else
            class="bi bi-calculator"
          ></i>

          {{
            generating
              ? 'Calculating...'
              : 'Generate / Recalculate'
          }}

        </button>

      </div>

    </div>


    <!-- Summary Cards -->

    <div class="salary-summary-grid">

      <div class="salary-summary-card">

        <span>
          Total Payrolls
        </span>

        <strong>
          {{ summary.total_payrolls }}
        </strong>

      </div>


      <div class="salary-summary-card salary-summary-paid">

        <span>
          Paid Amount
        </span>

        <strong>
          {{ money(summary.paid_amount) }}
        </strong>

        <small>
          {{ summary.paid_count }} paid
        </small>

      </div>


      <div class="salary-summary-card salary-summary-unpaid">

        <span>
          Unpaid Amount
        </span>

        <strong>
          {{ money(summary.unpaid_amount) }}
        </strong>

        <small>
          {{ summary.unpaid_count }} unpaid
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


      <div class="salary-summary-card">

        <span>
          Overtime Salary
        </span>

        <strong>
          {{ money(summary.overtime_salary) }}
        </strong>

      </div>


      <div class="salary-summary-card salary-summary-total">

        <span>
          Total Amount
        </span>

        <strong>
          {{ money(summary.total_amount) }}
        </strong>

      </div>

    </div>


    <!-- Filters -->

    <div class="salary-filter-card">

      <div class="salary-filter-grid">

        <div class="salary-filter-group">

          <label>
            Search
          </label>

          <input
            v-model.trim="filters.search"
            type="text"
            placeholder="Employee name, phone or email"
            @keyup.enter="applyFilters"
          />

        </div>


        <div class="salary-filter-group">

          <label>
            Employee
          </label>

          <select v-model="filters.employee_id">

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

          <select v-model="filters.payment_status">

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
                Phone
              </th>

              <th>
                Email
              </th>

              <th>
                Period
              </th>

              <th>
                Hourly Rate
              </th>

              <th>
                Regular
              </th>

              <th>
                Overtime
              </th>

              <th>
                Adjustment
              </th>

              <th>
                Amount
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

            <tr v-if="loading">

              <td
                colspan="12"
                class="salary-table-state"
              >

                <span class="salary-table-spinner"></span>

                Loading salary payrolls...

              </td>

            </tr>


            <tr v-else-if="errorMessage">

              <td
                colspan="12"
                class="salary-table-state salary-table-error"
              >
                {{ errorMessage }}
              </td>

            </tr>


            <tr v-else-if="salaries.length === 0">

              <td
                colspan="12"
                class="salary-table-state"
              >
                No salary payroll found. Generate a salary period first.
              </td>

            </tr>


            <tr
              v-for="(salary, index) in salaries"
              v-else
              :key="salary.id"
            >

              <td>
                {{ serialNumber(index) }}
              </td>


              <td>

                <div class="salary-employee-cell">

                  <div class="salary-avatar">
                    {{ initials(salary.employee_name) }}
                  </div>

                  <strong>
                    {{ salary.employee_name }}
                  </strong>

                </div>

              </td>


              <td>
                {{ salary.employee_phone || '—' }}
              </td>


              <td>
                {{ salary.employee_email || '—' }}
              </td>


              <td>
                {{ salary.period_label }}
              </td>


              <td>
                {{ salary.hourly_rate_formatted }}
              </td>


              <td>
                {{ salary.regular_salary_formatted }}
              </td>


              <td>
                {{ salary.overtime_salary_formatted }}
              </td>


              <td>
                {{ salary.adjustment_amount_formatted }}
              </td>


              <td>

                <strong class="salary-total-amount">
                  {{ salary.total_amount_formatted }}
                </strong>

              </td>


              <td>

                <select
                  class="salary-payment-select"
                  :class="{
                    paid:
                      salary.payment_status === 'paid',

                    unpaid:
                      salary.payment_status === 'unpaid',
                  }"
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

                <small
                  v-if="salary.paid_at_label"
                  class="salary-paid-info"
                >
                  {{ salary.paid_at_label }}
                </small>

              </td>


              <td>

                <div class="salary-actions">

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
            ({{ meta.total }} payrolls)
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


  <!-- Salary Details -->

<SalaryDetailsSection
  v-else
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


const generateForm = reactive({

  from_date:
    firstDayOfMonth(),

  to_date:
    todayDate,

  employee_id: '',

})


const filters = reactive({

  search: '',

  employee_id: '',

  payment_status: '',

  page: 1,

  per_page: 10,

})


const meta = ref({

  current_page: 1,

  last_page: 1,

  per_page: 10,

  total: 0,

})


const summary = reactive({

  total_payrolls: 0,

  paid_count: 0,

  unpaid_count: 0,

  regular_salary: 0,

  overtime_salary: 0,

  adjustment_amount: 0,

  total_amount: 0,

  paid_amount: 0,

  unpaid_amount: 0,

})


const editForm = reactive({

  adjustment_amount: 0,

  notes: '',

})


const message = reactive({

  show: false,

  type: 'success',

  text: '',

})


let messageTimer = null


const editTotalPreview = computed(() => {

  if (!selectedSalary.value) {
    return money(0)
  }

  return money(
    Number(
      selectedSalary.value
        .regular_salary
      ||
      0
    )
    +
    Number(
      selectedSalary.value
        .overtime_salary
      ||
      0
    )
    +
    Number(
      editForm.adjustment_amount
      ||
      0
    )
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
        'Unable to load employees.'
      ),
      'error'
    )

  }

  finally {

    employeesLoading.value = false

  }
}


async function loadSalaries()
{
  loading.value = true

  errorMessage.value = ''

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

          page:
            filters.page,

          per_page:
            filters.per_page,

        })


    const payload =
      extractPayload(response)


    salaries.value =
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
      payload?.summary
      ??
      {}
    )

  }

  catch (error) {

    salaries.value = []

    errorMessage.value =
      getErrorMessage(
        error,
        'Unable to load salary payrolls.'
      )

  }

  finally {

    loading.value = false

  }
}


async function generateSalaries()
{
  if (
    !generateForm.from_date
    ||
    !generateForm.to_date
  ) {
    return
  }


  if (
    generateForm.to_date <
    generateForm.from_date
  ) {
    showMessage(
      'To date cannot be before from date.',
      'error'
    )

    return
  }


  generating.value = true


  try {

    const response =
      await salaryService
        .generateSalaries({

          from_date:
            generateForm.from_date,

          to_date:
            generateForm.to_date,

          employee_id:
            generateForm.employee_id
              ? Number(
                  generateForm.employee_id
                )
              : null,

        })


    filters.page = 1

    await loadSalaries()


    showMessage(
      extractPayload(response)
        ?.message
      ??
      'Salary generation completed successfully.'
    )

  }

  catch (error) {

    showMessage(
      getErrorMessage(
        error,
        'Salary generation failed.'
      ),
      'error'
    )

  }

  finally {

    generating.value = false

  }
}


async function applyFilters()
{
  filters.page = 1

  await loadSalaries()
}


async function clearFilters()
{
  filters.search = ''

  filters.employee_id = ''

  filters.payment_status = ''

  filters.page = 1

  await loadSalaries()
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

  filters.page = targetPage

  await loadSalaries()
}


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
    newStatus === previousStatus
    ||
    paymentLoadingId.value !== null
  ) {
    selectElement.value =
      previousStatus

    return
  }


  const confirmed =
    window.confirm(
      newStatus === 'paid'
        ? `Mark ${salary.employee_name}'s salary as paid?`
        : `Change ${salary.employee_name}'s salary back to unpaid?`
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
      extractPayload(response)
        ?.message
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

    paymentLoadingId.value = null

  }
}


function openEditModal(salary)
{
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

  showEditModal.value = true
}


function closeEditModal()
{
  if (savingEdit.value) {
    return
  }

  showEditModal.value = false

  selectedSalary.value = null

  editForm.adjustment_amount = 0

  editForm.notes = ''
}


async function saveSalary()
{
  if (!selectedSalary.value) {
    return
  }

  savingEdit.value = true

  try {

    const response =
      await salaryService
        .updateSalary(
          selectedSalary.value.id,
          {
            adjustment_amount:
              Number(
                editForm.adjustment_amount
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
      extractPayload(response)
        ?.message
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

    savingEdit.value = false

  }
}


async function deleteSalary(salary)
{
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
      filters.page -= 1
    }


    await loadSalaries()


    showMessage(
      extractPayload(response)
        ?.message
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

    deleteLoadingId.value = null

  }
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
    loadSalaries(),
  ])

})


onBeforeUnmount(() => {

  if (messageTimer) {
    clearTimeout(messageTimer)
  }

})

</script>


<style>

@import '@/assets/css/salary/salary-management.css';

</style>