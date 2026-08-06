<template>

<section class="employee-section">

    <!-- Message -->

    <Transition name="employee-message">

        <div
            v-if="message.show"
            class="employee-message"
            :class="`employee-message-${message.type}`"
        >

            <i
                class="bi"
                :class="messageIcon"
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


    <!-- Employee Header -->

    <div class="employee-section-header">

        <div>

            <h3>
                Employee List
            </h3>

            <p>
                Manage staff accounts, hourly rates and attendance status
            </p>

        </div>


        <button
            type="button"
            class="employee-add-btn"
            @click="openAddEmployee"
        >

            <i class="bi bi-plus-lg"></i>

            Add Employee

        </button>

    </div>


    <!-- Attendance Status Information -->

    <div class="employee-attendance-notice">

        <i class="bi bi-info-circle"></i>

        <div>

            <strong>
                Attendance Status Flow
            </strong>

            <p>
                Working creates check-in, On Break starts a break, Working ends
                the break, and Check Out records the current checkout time.
            </p>

        </div>

    </div>


    <!-- Filter -->

    <div class="employee-filter-card">

        <div class="employee-filter-grid">

            <div class="employee-filter-group">

                <label>
                    Search
                </label>

                <input
                    v-model.trim="filters.search"
                    type="text"
                    placeholder="Name, email or username"
                    @keyup.enter="applyFilters"
                />

            </div>


            <div class="employee-filter-group">

                <label>
                    Working Status
                </label>

                <select v-model="filters.status">

                    <option value="">
                        All Statuses
                    </option>

                    <option value="none">
                        Not Checked In
                    </option>

                    <option value="present">
                        Working
                    </option>

                    <option value="break">
                        On Break
                    </option>

                    <option value="absent">
                        Absent
                    </option>

                    <option value="leave">
                        On Leave
                    </option>

                </select>

            </div>


            <div class="employee-filter-group">

                <label>
                    Account Status
                </label>

                <select v-model="filters.account_status">

                    <option value="">
                        All Accounts
                    </option>

                    <option value="active">
                        Active
                    </option>

                    <option value="blocked">
                        Blocked
                    </option>

                </select>

            </div>


            <div class="employee-filter-actions">

                <button
                    type="button"
                    class="employee-filter-btn"
                    :disabled="loading"
                    @click="applyFilters"
                >

                    <i class="bi bi-funnel"></i>

                    Apply

                </button>

                <button
                    type="button"
                    class="employee-clear-btn"
                    :disabled="loading"
                    @click="clearFilters"
                >
                    Clear
                </button>

            </div>

        </div>

    </div>


    <!-- Employee Table -->

    <div class="employee-table-card">

        <div class="employee-table-responsive">

            <table class="employee-table">

                <thead>

                    <tr>

                        <th>SL No</th>
                        <th>Staff Name</th>
                        <th>Role</th>
                        <th>Phone</th>
                        <th>Email</th>
                        <th>Joining Date</th>
                        <th>Hourly Rate</th>
                        <th>Current Status</th>
                        <th>Account</th>
                        <th>Action</th>

                    </tr>

                </thead>


                <tbody>

                    <!-- Loading -->

                    <tr v-if="loading">

                        <td
                            colspan="10"
                            class="employee-loading"
                        >
                            Loading employees...
                        </td>

                    </tr>


                    <!-- Error -->

                    <tr v-else-if="errorMessage">

                        <td
                            colspan="10"
                            class="employee-error"
                        >
                            {{ errorMessage }}
                        </td>

                    </tr>


                    <!-- Empty -->

                    <tr v-else-if="employees.length === 0">

                        <td
                            colspan="10"
                            class="employee-empty"
                        >
                            No employees found.
                        </td>

                    </tr>


                    <!-- Data -->

                    <template v-else>

                        <tr
                            v-for="(employee, index) in employees"
                            :key="employee.id"
                            :class="{
                                'employee-row-blocked':
                                    isEmployeeBlocked(employee),
                            }"
                        >

                            <td>
                                {{ getSerialNumber(index) }}
                            </td>


                            <td>

                                <div class="employee-name-cell">

                                    <div class="employee-avatar">

                                        {{
                                            getInitials(
                                                employee.staff_name
                                            )
                                        }}

                                    </div>

                                    <div>

                                        <strong>
                                            {{ employee.staff_name || 'Unknown Staff' }}
                                        </strong>

                                        <small>
                                            @{{ employee.username || 'unknown' }}
                                        </small>

                                    </div>

                                </div>

                            </td>


                            <td>

                                <span class="employee-role-badge">
                                    {{ employee.role_label || '-' }}
                                </span>

                            </td>


                            <td>
                                {{ employee.phone || '-' }}
                            </td>


                            <td>
                                {{ employee.email || '-' }}
                            </td>


                            <td>
                                {{
                                    employee.joining_date_label
                                    ||
                                    employee.joining_date
                                    ||
                                    '-'
                                }}
                            </td>


                            <td>

                                <strong>
                                    {{
                                        employee.hourly_rate_formatted
                                        ||
                                        '৳ 0.00 / hour'
                                    }}
                                </strong>

                            </td>


                            <!-- Attendance Status -->

                            <td>

                                <div class="employee-status-control">

                                    <select
                                        class="employee-status-select"
                                        :class="
                                            `employee-status-${employee.current_status}`
                                        "
                                        :value="employee.current_status"
                                        :disabled="
                                            statusLoadingId === employee.id
                                            ||
                                            isEmployeeBlocked(employee)
                                        "
                                        :title="
                                            isEmployeeBlocked(employee)
                                                ? 'Unblock the account before changing attendance status.'
                                                : 'Change employee attendance status'
                                        "
                                        @change="handleStatusChange(employee, $event)"
                                    >

                                        <option value="none">
                                            {{
                                                isCheckedIn(employee)
                                                    ? 'Check Out'
                                                    : 'Not Checked In'
                                            }}
                                        </option>

                                        <option value="present">
                                            {{
                                                employee.current_status === 'break'
                                                    ? 'Return to Working'
                                                    : 'Working'
                                            }}
                                        </option>

                                        <option value="break">
                                            On Break
                                        </option>

                                        <option value="absent">
                                            Absent
                                        </option>

                                        <option value="leave">
                                            On Leave
                                        </option>

                                    </select>


                                    <small
                                        v-if="statusLoadingId === employee.id"
                                        class="employee-status-note"
                                    >
                                        Updating attendance...
                                    </small>

                                    <small
                                        v-else-if="isEmployeeBlocked(employee)"
                                        class="employee-status-note employee-status-locked"
                                    >
                                        <i class="bi bi-lock"></i>
                                        Status locked
                                    </small>

                                </div>

                            </td>


                            <!-- Account -->

                            <td>

                                <button
                                    type="button"
                                    class="employee-account-btn"
                                    :class="{
                                        active:
                                            !isEmployeeBlocked(employee),

                                        blocked:
                                            isEmployeeBlocked(employee),
                                    }"
                                    :disabled="
                                        accountLoadingId === employee.id
                                    "
                                    @click="toggleAccountStatus(employee)"
                                >

                                    {{
                                        employee.account_status_label
                                        ||
                                        (
                                            isEmployeeBlocked(employee)
                                                ? 'Blocked'
                                                : 'Active'
                                        )
                                    }}

                                </button>

                            </td>


                            <!-- Actions -->

                            <td>

                                <div class="employee-actions">

                                    <button
                                        type="button"
                                        class="employee-action-btn employee-edit-btn"
                                        title="Edit"
                                        @click="editEmployee(employee)"
                                    >
                                        <i class="bi bi-pencil"></i>
                                    </button>


                                    <button
                                        type="button"
                                        class="employee-action-btn employee-delete-btn"
                                        title="Delete"
                                        @click="deleteEmployee(employee)"
                                    >
                                        <i class="bi bi-trash"></i>
                                    </button>

                                </div>

                            </td>

                        </tr>

                    </template>

                </tbody>

            </table>

        </div>


        <!-- Pagination -->

        <div
            v-if="meta.last_page > 1"
            class="employee-pagination"
        >

            <button
                type="button"
                :disabled="meta.current_page <= 1 || loading"
                @click="changePage(meta.current_page - 1)"
            >
                Previous
            </button>

            <span>
                Page {{ meta.current_page }} of {{ meta.last_page }}
            </span>

            <button
                type="button"
                :disabled="
                    meta.current_page >= meta.last_page
                    ||
                    loading
                "
                @click="changePage(meta.current_page + 1)"
            >
                Next
            </button>

        </div>

    </div>


    <!-- Form Modal -->

    <EmployeeFormModal
        :show="showEmployeeModal"
        :employee="selectedEmployee"
        @close="closeEmployeeModal"
        @saved="employeeSaved"
    />

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

import employeeService
    from '@/services/employeeService'

import EmployeeFormModal
    from './EmployeeFormModal.vue'


/*
|--------------------------------------------------------------------------
| State
|--------------------------------------------------------------------------
*/

const employees = ref([])

const loading = ref(false)

const errorMessage = ref('')

const statusLoadingId = ref(null)

const accountLoadingId = ref(null)

const showEmployeeModal = ref(false)

const selectedEmployee = ref(null)

const meta = ref({
    current_page: 1,
    last_page: 1,
    per_page: 10,
    total: 0,
})

const filters = reactive({
    search: '',
    status: '',
    account_status: '',
    page: 1,
    per_page: 10,
})

const message = reactive({
    show: false,
    type: 'success',
    text: '',
})

let messageTimer = null


/*
|--------------------------------------------------------------------------
| Message
|--------------------------------------------------------------------------
*/

const messageIcon = computed(() => {
    return message.type === 'error'
        ? 'bi-exclamation-circle'
        : 'bi-check-circle'
})


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

    messageTimer = setTimeout(() => {
        message.show = false
    }, 3500)
}


function hideMessage()
{
    message.show = false

    if (messageTimer) {
        clearTimeout(messageTimer)
    }
}


/*
|--------------------------------------------------------------------------
| Response Helpers
|--------------------------------------------------------------------------
*/

function extractPayload(response)
{
    return response?.data ?? response
}


function extractEmployees(response)
{
    const payload = extractPayload(response)

    if (Array.isArray(payload?.data)) {
        return payload.data
    }

    if (Array.isArray(payload)) {
        return payload
    }

    return []
}


function extractEmployee(response)
{
    const payload = extractPayload(response)

    return payload?.data ?? payload
}


function extractMeta(response)
{
    const payload = extractPayload(response)

    return payload?.meta
        ??
        {
            current_page: 1,
            last_page: 1,
            per_page: filters.per_page,
            total: 0,
        }
}


function getResponseMessage(
    response,
    fallback
) {
    const payload = extractPayload(response)

    return payload?.message ?? fallback
}


function getErrorMessage(
    error,
    fallback = 'Something went wrong.'
) {
    const errors = error?.response?.data?.errors

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


/*
|--------------------------------------------------------------------------
| Employee Helpers
|--------------------------------------------------------------------------
*/

function isEmployeeBlocked(employee)
{
    return (
        employee?.is_blocked === true
        ||
        employee?.is_active === false
        ||
        employee?.account_status === 'blocked'
    )
}


function isCheckedIn(employee)
{
    return [
        'present',
        'break',
    ].includes(
        employee?.current_status
    )
}


/*
|--------------------------------------------------------------------------
| Load Employees
|--------------------------------------------------------------------------
*/

async function loadEmployees()
{
    loading.value = true
    errorMessage.value = ''

    try {
        const response =
            await employeeService
                .getEmployees({
                    search:
                        filters.search || undefined,

                    status:
                        filters.status || undefined,

                    account_status:
                        filters.account_status || undefined,

                    page:
                        filters.page,

                    per_page:
                        filters.per_page,
                })

        employees.value =
            extractEmployees(response)

        meta.value =
            extractMeta(response)
    }
    catch (error) {
        employees.value = []

        errorMessage.value =
            getErrorMessage(
                error,
                'Unable to load employees.'
            )
    }
    finally {
        loading.value = false
    }
}


/*
|--------------------------------------------------------------------------
| Refresh
|--------------------------------------------------------------------------
*/

async function refreshEmployees()
{
    await loadEmployees()

    if (!errorMessage.value) {
        showMessage(
            'Employee list refreshed successfully.'
        )
    }
}


defineExpose({
    refreshEmployees,
})


/*
|--------------------------------------------------------------------------
| Filters
|--------------------------------------------------------------------------
*/

async function applyFilters()
{
    filters.page = 1

    await loadEmployees()
}


async function clearFilters()
{
    filters.search = ''
    filters.status = ''
    filters.account_status = ''
    filters.page = 1

    await loadEmployees()
}


/*
|--------------------------------------------------------------------------
| Pagination
|--------------------------------------------------------------------------
*/

async function changePage(page)
{
    const targetPage = Number(page)

    if (
        targetPage < 1
        ||
        targetPage > meta.value.last_page
    ) {
        return
    }

    filters.page = targetPage

    await loadEmployees()
}


/*
|--------------------------------------------------------------------------
| Serial and Initials
|--------------------------------------------------------------------------
*/

function getSerialNumber(index)
{
    return (
        (
            Number(meta.value.current_page || 1)
            -
            1
        )
        *
        Number(meta.value.per_page || filters.per_page)
    )
    +
    index
    +
    1
}


function getInitials(name)
{
    if (!name) {
        return 'NA'
    }

    return String(name)
        .split(' ')
        .filter(Boolean)
        .slice(0, 2)
        .map(
            (word) => word.charAt(0)
        )
        .join('')
        .toUpperCase()
}


/*
|--------------------------------------------------------------------------
| Add and Edit Employee
|--------------------------------------------------------------------------
*/

function openAddEmployee()
{
    selectedEmployee.value = null
    showEmployeeModal.value = true
}


function editEmployee(employee)
{
    selectedEmployee.value = {
        ...employee,

        user:
            employee?.user
                ? {
                    ...employee.user,
                }
                : null,
    }

    showEmployeeModal.value = true
}


function closeEmployeeModal()
{
    showEmployeeModal.value = false
    selectedEmployee.value = null
}


async function employeeSaved(savedMessage)
{
    closeEmployeeModal()

    await loadEmployees()

    showMessage(
        savedMessage
        ||
        'Employee saved successfully.'
    )
}


/*
|--------------------------------------------------------------------------
| Attendance Status Update
|--------------------------------------------------------------------------
*/

async function handleStatusChange(
    employee,
    event
) {
    const selectElement = event?.target

    if (!selectElement) {
        return
    }

    const newStatus = selectElement.value
    const previousStatus = employee.current_status

    if (
        statusLoadingId.value !== null
        ||
        newStatus === previousStatus
    ) {
        selectElement.value = previousStatus
        return
    }

    if (isEmployeeBlocked(employee)) {
        selectElement.value = previousStatus

        showMessage(
            'Unblock the employee account before changing attendance status.',
            'error'
        )

        return
    }

    const isCheckout =
        newStatus === 'none'
        &&
        [
            'present',
            'break',
        ].includes(previousStatus)

    if (isCheckout) {
        const confirmed = window.confirm(
            `Check out ${employee.staff_name} now?`
        )

        if (!confirmed) {
            selectElement.value = previousStatus
            return
        }
    }

    if (
        newStatus === 'break'
        &&
        previousStatus !== 'present'
    ) {
        selectElement.value = previousStatus

        showMessage(
            'Employee must be working before starting a break.',
            'error'
        )

        return
    }

    statusLoadingId.value = employee.id
    selectElement.disabled = true

    try {
        const response =
            await employeeService
                .updateStatus(
                    employee.id,
                    newStatus
                )

        const updatedEmployee =
            extractEmployee(response)

        if (updatedEmployee?.id) {
            Object.assign(
                employee,
                updatedEmployee
            )
        }
        else {
            await loadEmployees()
        }

        showMessage(
            getResponseMessage(
                response,
                isCheckout
                    ? `${employee.staff_name} checked out successfully.`
                    : 'Employee attendance status updated successfully.'
            )
        )

        window.dispatchEvent(
            new CustomEvent(
                'staff-attendance-updated',
                {
                    detail: {
                        employee_id:
                            employee.id,

                        status:
                            employee.current_status,
                    },
                }
            )
        )
    }
    catch (error) {
        employee.current_status = previousStatus
        selectElement.value = previousStatus

        showMessage(
            getErrorMessage(
                error,
                isCheckout
                    ? 'Employee checkout failed.'
                    : 'Employee status update failed.'
            ),
            'error'
        )

        await loadEmployees()
    }
    finally {
        statusLoadingId.value = null
        selectElement.disabled = false
    }
}


/*
|--------------------------------------------------------------------------
| Block / Unblock
|--------------------------------------------------------------------------
*/

async function toggleAccountStatus(employee)
{
    if (accountLoadingId.value !== null) {
        return
    }

    const makeActive =
        isEmployeeBlocked(employee)

    if (
        !makeActive
        &&
        isCheckedIn(employee)
    ) {
        showMessage(
            'Check out this employee before blocking the account.',
            'error'
        )

        return
    }

    const confirmed = window.confirm(
        makeActive
            ? `Unblock ${employee.staff_name}'s account?`
            : `Block ${employee.staff_name}'s account?`
    )

    if (!confirmed) {
        return
    }

    accountLoadingId.value = employee.id

    try {
        const response =
            await employeeService
                .updateAccountStatus(
                    employee.id,
                    makeActive
                )

        const updatedEmployee =
            extractEmployee(response)

        if (updatedEmployee?.id) {
            Object.assign(
                employee,
                updatedEmployee
            )
        }
        else {
            await loadEmployees()
        }

        showMessage(
            getResponseMessage(
                response,
                makeActive
                    ? 'Employee account unblocked successfully.'
                    : 'Employee account blocked successfully.'
            )
        )
    }
    catch (error) {
        showMessage(
            getErrorMessage(
                error,
                'Account status update failed.'
            ),
            'error'
        )
    }
    finally {
        accountLoadingId.value = null
    }
}


/*
|--------------------------------------------------------------------------
| Delete Employee
|--------------------------------------------------------------------------
*/

async function deleteEmployee(employee)
{
    if (isCheckedIn(employee)) {
        showMessage(
            'Check out this employee before deleting the employee profile.',
            'error'
        )

        return
    }

    const confirmed = window.confirm(
        `Are you sure you want to delete ${employee.staff_name}?`
    )

    if (!confirmed) {
        return
    }

    try {
        const response =
            await employeeService
                .deleteEmployee(
                    employee.id
                )

        if (
            employees.value.length === 1
            &&
            filters.page > 1
        ) {
            filters.page -= 1
        }

        await loadEmployees()

        showMessage(
            getResponseMessage(
                response,
                'Employee deleted successfully.'
            )
        )
    }
    catch (error) {
        showMessage(
            getErrorMessage(
                error,
                'Employee deletion failed.'
            ),
            'error'
        )
    }
}


/*
|--------------------------------------------------------------------------
| Lifecycle
|--------------------------------------------------------------------------
*/

onMounted(
    loadEmployees
)


onBeforeUnmount(() => {
    if (messageTimer) {
        clearTimeout(messageTimer)
    }
})

</script>


<style>

@import '@/assets/css/staff/staff-header.css';

@import '@/assets/css/staff/staff-tabs.css';

@import '@/assets/css/staff/staff-responsive.css';

@import '@/assets/css/staff/employee-form-modal.css';

@import '@/assets/css/staff/employees/employee-section.css';

@import '@/assets/css/staff/employee-final-integration.css';

</style>
