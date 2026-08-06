<template>

<section class="attendance-section">

    <!-- Message -->

    <Transition name="attendance-message">

        <div
            v-if="message.show"
            class="attendance-message"
            :class="`attendance-message-${message.type}`"
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
                title="Close"
                @click="hideMessage"
            >
                <i class="bi bi-x-lg"></i>
            </button>

        </div>

    </Transition>


    <!-- Header -->

    <div class="attendance-section-header">

        <div>

            <h3>
                Daily Attendance
            </h3>

            <p>
                Monitor scheduled staff, check-in, breaks, checkout and
                calculated working hours
            </p>

        </div>


        <button
            type="button"
            class="attendance-sync-btn"
            :disabled="
                syncing
                ||
                loading
                ||
                isFutureDate
            "
            :title="
                isFutureDate
                    ? 'Future attendance cannot be synchronized.'
                    : 'Synchronize attendance'
            "
            @click="manualSync"
        >

            <span
                v-if="syncing"
                class="attendance-button-spinner"
            ></span>

            <i
                v-else
                class="bi bi-arrow-repeat"
            ></i>

            {{
                syncing
                    ? 'Syncing...'
                    : isFutureDate
                        ? 'Future Date'
                        : 'Sync Attendance'
            }}

        </button>

    </div>


    <!-- Date Navigation -->

    <div class="attendance-date-navigator">

        <div class="attendance-date-label">

            <div class="attendance-date-icon">

                <i class="bi bi-calendar-check"></i>

            </div>

            <div>

                <span>
                    Attendance Date
                </span>

                <strong>
                    {{ selectedDateLabel }}
                </strong>

            </div>

        </div>


        <div class="attendance-date-actions">

            <button
                type="button"
                title="Previous day"
                :disabled="loading"
                @click="changeAttendanceDate(-1)"
            >
                <i class="bi bi-chevron-left"></i>
            </button>


            <input
                v-model="filters.attendance_date"
                type="date"
                :disabled="loading"
                @change="applyFilters"
            />


            <button
                type="button"
                class="attendance-today-btn"
                :disabled="loading"
                @click="goToToday"
            >
                Today
            </button>


            <button
                type="button"
                title="Next day"
                :disabled="loading"
                @click="changeAttendanceDate(1)"
            >
                <i class="bi bi-chevron-right"></i>
            </button>

        </div>

    </div>


    <!-- Future Date Notice -->

    <div
        v-if="isFutureDate"
        class="attendance-future-notice"
    >

        <i class="bi bi-calendar-event"></i>

        <div>

            <strong>
                Future attendance is not generated
            </strong>

            <p>
                Future schedules remain available in Shift Schedule, but
                attendance records are generated only on or after the actual
                attendance date.
            </p>

        </div>

    </div>


    <!-- Summary Cards -->

    <div class="attendance-summary-grid">

        <!-- Total -->

        <div class="attendance-summary-card">

            <div class="attendance-summary-icon attendance-summary-total">

                <i class="bi bi-people"></i>

            </div>

            <div>

                <span>
                    Scheduled Staff
                </span>

                <strong>
                    {{ summary.total }}
                </strong>

                <small>
                    {{ summary.scheduled }} awaiting shift
                </small>

            </div>

        </div>


        <!-- Present -->

        <div class="attendance-summary-card">

            <div class="attendance-summary-icon attendance-summary-present">

                <i class="bi bi-person-check"></i>

            </div>

            <div>

                <span>
                    Present Now
                </span>

                <strong>
                    {{ presentNowCount }}
                </strong>

                <small>
                    Working and on break
                </small>

            </div>

        </div>


        <!-- Absent -->

        <div class="attendance-summary-card">

            <div class="attendance-summary-icon attendance-summary-absent">

                <i class="bi bi-person-x"></i>

            </div>

            <div>

                <span>
                    Absent
                </span>

                <strong>
                    {{ summary.absent }}
                </strong>

                <small>
                    Not checked in
                </small>

            </div>

        </div>


        <!-- Break -->

        <div class="attendance-summary-card">

            <div class="attendance-summary-icon attendance-summary-break">

                <i class="bi bi-cup-hot"></i>

            </div>

            <div>

                <span>
                    On Break
                </span>

                <strong>
                    {{ summary.on_break }}
                </strong>

                <small>
                    {{ formattedTotalBreak }}
                </small>

            </div>

        </div>


        <!-- Completed -->

        <div class="attendance-summary-card">

            <div class="attendance-summary-icon attendance-summary-completed">

                <i class="bi bi-box-arrow-right"></i>

            </div>

            <div>

                <span>
                    Checked Out
                </span>

                <strong>
                    {{ summary.completed }}
                </strong>

                <small>
                    {{ summary.auto_checked_out || 0 }}
                    automatic checkout
                </small>

            </div>

        </div>


        <!-- Worked Hours -->

        <div class="attendance-summary-card">

            <div class="attendance-summary-icon attendance-summary-hours">

                <i class="bi bi-stopwatch"></i>

            </div>

            <div>

                <span>
                    Worked Hours
                </span>

                <strong>
                    {{ totalWorkedHours }}
                </strong>

                <small>
                    Calculated after checkout
                </small>

            </div>

        </div>

    </div>


    <!-- Filters -->

    <div class="attendance-filter-card">

        <div class="attendance-filter-grid">

            <!-- Search -->

            <div class="attendance-filter-group">

                <label>
                    Search Employee
                </label>

                <input
                    v-model.trim="filters.search"
                    type="text"
                    placeholder="Name, username, phone or email"
                    @keyup.enter="applyFilters"
                />

            </div>


            <!-- Employee -->

            <div class="attendance-filter-group">

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

                        {{ employee.staff_name }}

                        —

                        {{
                            employee.role_label
                            ||
                            formatRoleName(
                                employee.role_name
                            )
                        }}

                    </option>

                </select>

            </div>


            <!-- Role -->

            <div class="attendance-filter-group">

                <label>
                    Role
                </label>

                <select v-model="filters.role_id">

                    <option value="">
                        All Roles
                    </option>

                    <option
                        v-for="role in roleOptions"
                        :key="role.id"
                        :value="String(role.id)"
                    >
                        {{ role.label }}
                    </option>

                </select>

            </div>


            <!-- Status -->

            <div class="attendance-filter-group">

                <label>
                    Attendance Status
                </label>

                <select v-model="filters.status">

                    <option value="">
                        All Statuses
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


            <!-- Filter Actions -->

            <div class="attendance-filter-actions">

                <button
                    type="button"
                    class="attendance-filter-btn"
                    :disabled="loading"
                    @click="applyFilters"
                >

                    <i class="bi bi-funnel"></i>

                    Apply

                </button>


                <button
                    type="button"
                    class="attendance-clear-btn"
                    :disabled="loading"
                    @click="clearFilters"
                >
                    Clear
                </button>

            </div>

        </div>

    </div>


    <!-- Auto Refresh Information -->

    <div
        v-if="isTodaySelected"
        class="attendance-auto-refresh-info"
    >

        <i class="bi bi-broadcast"></i>

        <span>
            Today's attendance automatically refreshes every minute.
        </span>

        <small v-if="lastUpdatedAt">
            Last updated: {{ lastUpdatedAt }}
        </small>

    </div>


    <!-- Attendance Table -->

    <div class="attendance-table-card">

        <div class="attendance-table-responsive">

            <table class="attendance-table">

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
                            Scheduled Shift
                        </th>

                        <th>
                            Check In
                        </th>

                        <th>
                            Check Out
                        </th>

                        <th>
                            Status
                        </th>

                        <th>
                            Late
                        </th>

                        <th>
                            Break
                        </th>

                        <th>
                            Worked
                        </th>

                        <th>
                            Overtime
                        </th>

                        <th>
                            Early Leave
                        </th>

                    </tr>

                </thead>


                <tbody>

                    <!-- Loading -->

                    <tr v-if="loading">

                        <td
                            colspan="12"
                            class="attendance-state-cell"
                        >

                            <div class="attendance-table-state">

                                <span class="attendance-table-spinner"></span>

                                Loading attendance records...

                            </div>

                        </td>

                    </tr>


                    <!-- Error -->

                    <tr v-else-if="errorMessage">

                        <td
                            colspan="12"
                            class="attendance-state-cell"
                        >

                            <div
                                class="
                                    attendance-table-state
                                    attendance-table-error
                                "
                            >

                                <i class="bi bi-exclamation-circle"></i>

                                {{ errorMessage }}

                            </div>

                        </td>

                    </tr>


                    <!-- Empty -->

                    <tr v-else-if="attendances.length === 0">

                        <td
                            colspan="12"
                            class="attendance-state-cell"
                        >

                            <div class="attendance-table-empty">

                                <div class="attendance-empty-icon">

                                    <i
                                        class="bi"
                                        :class="
                                            isFutureDate
                                                ? 'bi-calendar-event'
                                                : 'bi-calendar-x'
                                        "
                                    ></i>

                                </div>

                                <strong>

                                    {{
                                        isFutureDate
                                            ? 'Future attendance is not generated'
                                            : 'No attendance records found'
                                    }}

                                </strong>

                                <p>

                                    {{
                                        isFutureDate
                                            ? 'Check the Shift Schedule tab to view future employee schedules.'
                                            : 'No active shift schedule applies to the selected date or filters.'
                                    }}

                                </p>

                            </div>

                        </td>

                    </tr>


                    <!-- Attendance Rows -->

                    <template v-else>

                        <tr
                            v-for="(attendance, index) in attendances"
                            :key="attendance.id"
                            :class="
                                `attendance-row-${attendance.status}`
                            "
                        >

                            <!-- Serial -->

                            <td>
                                {{ getSerialNumber(index) }}
                            </td>


                            <!-- Employee -->

                            <td>

                                <div class="attendance-employee-cell">

                                    <div class="attendance-employee-avatar">

                                        {{
                                            getInitials(
                                                attendance.staff_name
                                            )
                                        }}

                                    </div>

                                    <div>

                                        <strong>
                                            {{
                                                attendance.staff_name
                                                ||
                                                'Unknown Staff'
                                            }}
                                        </strong>

                                        <small>
                                            @{{
                                                attendance.username
                                                ||
                                                'unknown'
                                            }}
                                        </small>

                                    </div>

                                </div>

                            </td>


                            <!-- Role -->

                            <td>

                                <span class="attendance-role-badge">

                                    {{
                                        attendance.role_label
                                        ||
                                        formatRoleName(
                                            attendance.role_name
                                        )
                                    }}

                                </span>

                            </td>


                            <!-- Scheduled Shift -->

                            <td>

                                <div class="attendance-shift-cell">

                                    <strong>

                                        {{
                                            attendance.scheduled_start_time_label
                                            ||
                                            formatTime(
                                                attendance.scheduled_start_time
                                            )
                                        }}

                                        —

                                        {{
                                            attendance.scheduled_end_time_label
                                            ||
                                            formatTime(
                                                attendance.scheduled_end_time
                                            )
                                        }}

                                    </strong>

                                    <small>

                                        {{
                                            attendance.scheduled_duration_label
                                            ||
                                            formatDuration(
                                                attendance.scheduled_minutes
                                            )
                                        }}

                                        <template v-if="attendance.is_overnight">
                                            · Overnight
                                        </template>

                                    </small>

                                </div>

                            </td>


                            <!-- Check In -->

                            <td>

                                <div
                                    v-if="attendance.check_in_at"
                                    class="
                                        attendance-time-cell
                                        attendance-check-in
                                    "
                                >

                                    <i class="bi bi-box-arrow-in-right"></i>

                                    <strong>

                                        {{
                                            attendance.check_in_time_label
                                            ||
                                            formatTime(
                                                attendance.check_in_time
                                            )
                                        }}

                                    </strong>

                                </div>


                                <span
                                    v-else
                                    class="attendance-empty-value"
                                >
                                    —
                                </span>

                            </td>


                            <!-- Check Out -->

                            <td>

                                <div
                                    v-if="attendance.check_out_at"
                                    class="attendance-checkout-wrapper"
                                >

                                    <div
                                        class="
                                            attendance-time-cell
                                            attendance-check-out
                                        "
                                    >

                                        <i class="bi bi-box-arrow-right"></i>

                                        <strong>

                                            {{
                                                attendance.check_out_time_label
                                                ||
                                                formatTime(
                                                    attendance.check_out_time
                                                )
                                            }}

                                        </strong>

                                    </div>


                                    <small
                                        v-if="attendance.auto_checked_out"
                                        class="attendance-auto-checkout-badge"
                                        :title="
                                            attendance.auto_checkout_reason
                                            ||
                                            'Automatically checked out by the system.'
                                        "
                                    >

                                        <i class="bi bi-cpu"></i>

                                        Auto

                                    </small>

                                </div>


                                <span
                                    v-else
                                    class="attendance-empty-value"
                                >
                                    —
                                </span>

                            </td>


                            <!-- Status -->

                            <td>

                                <span
                                    class="attendance-status-badge"
                                    :class="
                                        getStatusClass(
                                            attendance.status
                                        )
                                    "
                                >

                                    <span
                                        v-if="
                                            attendance.status ===
                                            'present'
                                        "
                                        class="attendance-live-dot"
                                    ></span>

                                    <i
                                        v-else
                                        class="bi"
                                        :class="
                                            getStatusIcon(
                                                attendance.status
                                            )
                                        "
                                    ></i>

                                    {{
                                        attendance.status_label
                                        ||
                                        formatStatus(
                                            attendance.status
                                        )
                                    }}

                                </span>


                                <small
                                    v-if="
                                        attendance.status ===
                                        'break'
                                        &&
                                        attendance.current_break_started_time_label
                                    "
                                    class="attendance-status-detail"
                                >

                                    Since

                                    {{
                                        attendance.current_break_started_time_label
                                    }}

                                </small>

                            </td>


                            <!-- Late -->

                            <td>

                                <span
                                    v-if="attendance.late_minutes > 0"
                                    class="
                                        attendance-metric-badge
                                        attendance-metric-danger
                                    "
                                >

                                    <i class="bi bi-clock"></i>

                                    {{
                                        attendance.late_duration_label
                                        ||
                                        formatDuration(
                                            attendance.late_minutes
                                        )
                                    }}

                                </span>


                                <span
                                    v-else-if="attendance.check_in_at"
                                    class="
                                        attendance-metric-badge
                                        attendance-metric-success
                                    "
                                >
                                    On time
                                </span>


                                <span
                                    v-else
                                    class="attendance-empty-value"
                                >
                                    —
                                </span>

                            </td>


                            <!-- Break -->

                            <td>

                                <div class="attendance-duration-cell">

                                    <strong>

                                        {{
                                            attendance.break_duration_label
                                            ||
                                            formatDuration(
                                                attendance.break_minutes
                                            )
                                        }}

                                    </strong>

                                    <small
                                        v-if="
                                            Array.isArray(
                                                attendance.breaks
                                            )
                                            &&
                                            attendance.breaks.length > 0
                                        "
                                    >

                                        {{ attendance.breaks.length }}

                                        {{
                                            attendance.breaks.length === 1
                                                ? 'break'
                                                : 'breaks'
                                        }}

                                    </small>

                                </div>

                            </td>


                            <!-- Worked -->

                            <td>

                                <div class="attendance-duration-cell">

                                    <strong>

                                        {{
                                            attendance.worked_duration_label
                                            ||
                                            formatDuration(
                                                attendance.worked_minutes
                                            )
                                        }}

                                    </strong>

                                    <small
                                        v-if="
                                            attendance.status !==
                                            'completed'
                                        "
                                    >
                                        Final after checkout
                                    </small>

                                </div>

                            </td>


                            <!-- Overtime -->

                            <td>

                                <span
                                    v-if="
                                        attendance.overtime_minutes > 0
                                    "
                                    class="
                                        attendance-metric-badge
                                        attendance-metric-overtime
                                    "
                                >

                                    +{{
                                        attendance.overtime_duration_label
                                        ||
                                        formatDuration(
                                            attendance.overtime_minutes
                                        )
                                    }}

                                </span>


                                <span
                                    v-else
                                    class="attendance-empty-value"
                                >
                                    —
                                </span>

                            </td>


                            <!-- Early Leave -->

                            <td>

                                <span
                                    v-if="
                                        attendance.early_leave_minutes > 0
                                    "
                                    class="
                                        attendance-metric-badge
                                        attendance-metric-warning
                                    "
                                >

                                    {{
                                        attendance.early_leave_duration_label
                                        ||
                                        formatDuration(
                                            attendance.early_leave_minutes
                                        )
                                    }}

                                </span>


                                <span
                                    v-else
                                    class="attendance-empty-value"
                                >
                                    —
                                </span>

                            </td>

                        </tr>

                    </template>

                </tbody>

            </table>

        </div>


        <!-- Pagination -->

        <div
            v-if="
                !loading
                &&
                meta.last_page > 1
            "
            class="attendance-pagination"
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

                <i class="bi bi-chevron-left"></i>

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

                <i class="bi bi-chevron-right"></i>

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

import attendanceService
    from '@/services/attendanceService'


/*
|--------------------------------------------------------------------------
| Date Helpers
|--------------------------------------------------------------------------
*/

function getLocalDate()
{
    const date = new Date()

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


/*
|--------------------------------------------------------------------------
| Main State
|--------------------------------------------------------------------------
*/

const attendances = ref([])

const employees = ref([])

const loading = ref(false)

const syncing = ref(false)

const employeesLoading = ref(false)

const errorMessage = ref('')

const lastUpdatedAt = ref('')


/*
|--------------------------------------------------------------------------
| Pagination
|--------------------------------------------------------------------------
*/

const meta = ref({

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

const summary = reactive({

    total: 0,

    scheduled: 0,

    absent: 0,

    present: 0,

    on_break: 0,

    completed: 0,

    leave: 0,

    worked_minutes: 0,

    break_minutes: 0,

    auto_checked_out: 0,

})


/*
|--------------------------------------------------------------------------
| Filters
|--------------------------------------------------------------------------
*/

const filters = reactive({

    attendance_date:
        getLocalDate(),

    search: '',

    employee_id: '',

    role_id: '',

    status: '',

    page: 1,

    per_page: 10,

})


/*
|--------------------------------------------------------------------------
| Notification Message
|--------------------------------------------------------------------------
*/

const message = reactive({

    show: false,

    type: 'success',

    text: '',

})


let messageTimer = null

let autoRefreshTimer = null


/*
|--------------------------------------------------------------------------
| Computed Values
|--------------------------------------------------------------------------
*/

const messageIcon = computed(() => {

    return message.type === 'error'
        ? 'bi-exclamation-circle'
        : 'bi-check-circle'

})


const isTodaySelected = computed(() => {

    return (
        filters.attendance_date ===
        getLocalDate()
    )

})


const isFutureDate = computed(() => {

    if (!filters.attendance_date) {
        return false
    }

    return (
        filters.attendance_date >
        getLocalDate()
    )

})


const selectedDateLabel = computed(() => {

    return formatDateWithDay(
        filters.attendance_date
    )

})


const presentNowCount = computed(() => {

    return (
        Number(
            summary.present
            ||
            0
        )
        +
        Number(
            summary.on_break
            ||
            0
        )
    )

})


const totalWorkedHours = computed(() => {

    return (
        Number(
            summary.worked_minutes
            ||
            0
        )
        /
        60
    ).toFixed(2)

})


const formattedTotalBreak = computed(() => {

    return (
        formatDuration(
            summary.break_minutes
        )
        +
        ' total'
    )

})


const roleOptions = computed(() => {

    const roleMap =
        new Map()

    employees.value.forEach(
        (employee) => {

            if (!employee.role_id) {
                return
            }

            roleMap.set(
                Number(employee.role_id),
                {
                    id:
                        Number(
                            employee.role_id
                        ),

                    label:
                        employee.role_label
                        ||
                        formatRoleName(
                            employee.role_name
                        ),
                }
            )

        }
    )

    return Array.from(
        roleMap.values()
    ).sort(
        (first, second) =>
            first.label.localeCompare(
                second.label
            )
    )

})


/*
|--------------------------------------------------------------------------
| Message Methods
|--------------------------------------------------------------------------
*/

function showMessage(
    text,
    type = 'success'
) {
    if (messageTimer) {
        clearTimeout(messageTimer)
    }

    message.text =
        text

    message.type =
        type

    message.show =
        true

    messageTimer = setTimeout(() => {

        message.show =
            false

    }, 3500)
}


function hideMessage()
{
    message.show =
        false

    if (messageTimer) {
        clearTimeout(messageTimer)
    }
}


/*
|--------------------------------------------------------------------------
| API Response Helpers
|--------------------------------------------------------------------------
*/

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


function extractMeta(response)
{
    const payload =
        extractPayload(response)

    return payload?.meta
        ??
        {
            current_page: 1,
            last_page: 1,
            per_page: filters.per_page,
            total: 0,
        }
}


function extractSummary(response)
{
    const payload =
        extractPayload(response)

    return payload?.summary
        ??
        {}
}


function getResponseMessage(
    response,
    fallback
) {
    const payload =
        extractPayload(response)

    return payload?.message
        ??
        fallback
}


function getErrorMessage(
    error,
    fallback = 'Something went wrong.'
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


/*
|--------------------------------------------------------------------------
| Load Employee Options
|--------------------------------------------------------------------------
*/

async function loadEmployees()
{
    employeesLoading.value =
        true

    try {

        const response =
            await attendanceService
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

        employeesLoading.value =
            false

    }
}


/*
|--------------------------------------------------------------------------
| Load Attendance Records
|--------------------------------------------------------------------------
*/

async function loadAttendances({
    silent = false,
} = {}) {
    if (!silent) {
        loading.value = true
    }

    errorMessage.value = ''

    try {

        const response =
            await attendanceService
                .getAttendances({

                    attendance_date:
                        filters.attendance_date,

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

                    role_id:
                        filters.role_id
                            ? Number(
                                filters.role_id
                            )
                            : undefined,

                    status:
                        filters.status
                        ||
                        undefined,

                    page:
                        filters.page,

                    per_page:
                        filters.per_page,

                })


        attendances.value =
            extractCollection(response)

        meta.value =
            extractMeta(response)


        const responseSummary =
            extractSummary(response)


        Object.assign(
            summary,
            {
                total:
                    Number(
                        responseSummary.total
                        ||
                        0
                    ),

                scheduled:
                    Number(
                        responseSummary.scheduled
                        ||
                        0
                    ),

                absent:
                    Number(
                        responseSummary.absent
                        ||
                        0
                    ),

                present:
                    Number(
                        responseSummary.present
                        ||
                        0
                    ),

                on_break:
                    Number(
                        responseSummary.on_break
                        ||
                        0
                    ),

                completed:
                    Number(
                        responseSummary.completed
                        ||
                        0
                    ),

                leave:
                    Number(
                        responseSummary.leave
                        ||
                        0
                    ),

                worked_minutes:
                    Number(
                        responseSummary.worked_minutes
                        ||
                        0
                    ),

                break_minutes:
                    Number(
                        responseSummary.break_minutes
                        ||
                        0
                    ),

                auto_checked_out:
                    Number(
                        responseSummary.auto_checked_out
                        ||
                        0
                    ),
            }
        )


        lastUpdatedAt.value =
            new Intl.DateTimeFormat(
                'en-US',
                {
                    hour: '2-digit',
                    minute: '2-digit',
                    second: '2-digit',
                    hour12: true,
                }
            ).format(
                new Date()
            )

    }

    catch (error) {

        attendances.value = []

        resetSummary()

        errorMessage.value =
            getErrorMessage(
                error,
                'Unable to load attendance records.'
            )

    }

    finally {

        if (!silent) {
            loading.value = false
        }

    }
}


function resetSummary()
{
    Object.assign(
        summary,
        {
            total: 0,
            scheduled: 0,
            absent: 0,
            present: 0,
            on_break: 0,
            completed: 0,
            leave: 0,
            worked_minutes: 0,
            break_minutes: 0,
            auto_checked_out: 0,
        }
    )
}


/*
|--------------------------------------------------------------------------
| Manual Attendance Sync
|--------------------------------------------------------------------------
*/

async function manualSync()
{
    if (isFutureDate.value) {

        showMessage(
            'Future attendance cannot be synchronized.',
            'error'
        )

        return
    }


    if (syncing.value) {
        return
    }


    syncing.value = true


    try {

        const response =
            await attendanceService
                .syncAttendance(
                    filters.attendance_date
                )


        await loadAttendances({
            silent: true,
        })


        showMessage(
            getResponseMessage(
                response,
                'Attendance synchronized successfully.'
            )
        )

    }

    catch (error) {

        showMessage(
            getErrorMessage(
                error,
                'Attendance synchronization failed.'
            ),
            'error'
        )

    }

    finally {

        syncing.value = false

    }
}


/*
|--------------------------------------------------------------------------
| Refresh Attendance Section
|--------------------------------------------------------------------------
*/

async function refreshAttendances()
{
    await Promise.all([
        loadAttendances(),
        loadEmployees(),
    ])

    if (!errorMessage.value) {

        showMessage(
            'Attendance refreshed successfully.'
        )

    }
}


defineExpose({
    refreshAttendances,
})


/*
|--------------------------------------------------------------------------
| Date Navigation
|--------------------------------------------------------------------------
*/

async function changeAttendanceDate(dayOffset)
{
    const date =
        new Date(
            `${filters.attendance_date}T00:00:00`
        )

    date.setDate(
        date.getDate() + Number(dayOffset)
    )

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


    filters.attendance_date =
        `${year}-${month}-${day}`

    filters.page = 1


    await loadAttendances()
}


async function goToToday()
{
    filters.attendance_date =
        getLocalDate()

    filters.page = 1

    await loadAttendances()
}


/*
|--------------------------------------------------------------------------
| Filters
|--------------------------------------------------------------------------
*/

async function applyFilters()
{
    filters.page = 1

    await loadAttendances()
}


async function clearFilters()
{
    filters.search = ''

    filters.employee_id = ''

    filters.role_id = ''

    filters.status = ''

    filters.page = 1

    await loadAttendances()
}


/*
|--------------------------------------------------------------------------
| Pagination
|--------------------------------------------------------------------------
*/

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

    await loadAttendances()
}


/*
|--------------------------------------------------------------------------
| Automatic Refresh
|--------------------------------------------------------------------------
*/

function startAutoRefresh()
{
    stopAutoRefresh()

    autoRefreshTimer = setInterval(
        async () => {

            if (
                isTodaySelected.value
                &&
                !loading.value
                &&
                !syncing.value
                &&
                document.visibilityState === 'visible'
            ) {
                await loadAttendances({
                    silent: true,
                })
            }

        },
        60000
    )
}


function stopAutoRefresh()
{
    if (!autoRefreshTimer) {
        return
    }

    clearInterval(
        autoRefreshTimer
    )

    autoRefreshTimer = null
}


async function handleWindowFocus()
{
    if (
        isTodaySelected.value
        &&
        !loading.value
        &&
        !syncing.value
    ) {
        await loadAttendances({
            silent: true,
        })
    }
}


/*
|--------------------------------------------------------------------------
| Formatting
|--------------------------------------------------------------------------
*/

function getSerialNumber(index)
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
            filters.per_page
        )
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
            (word) =>
                word.charAt(0)
        )
        .join('')
        .toUpperCase()
}


function formatRoleName(name)
{
    if (!name) {
        return 'No Role'
    }

    return String(name)
        .replace(/[_-]+/g, ' ')
        .replace(
            /\b\w/g,
            (character) =>
                character.toUpperCase()
        )
}


function formatDateWithDay(date)
{
    if (!date) {
        return '-'
    }

    const parsedDate =
        new Date(
            `${date}T00:00:00`
        )

    if (
        Number.isNaN(
            parsedDate.getTime()
        )
    ) {
        return date
    }

    return new Intl.DateTimeFormat(
        'en-GB',
        {
            weekday: 'long',
            day: '2-digit',
            month: 'long',
            year: 'numeric',
        }
    ).format(parsedDate)
}


function formatTime(time)
{
    if (!time) {
        return '—'
    }

    const [
        hours,
        minutes,
    ] = String(time)
        .split(':')
        .map(Number)

    if (
        Number.isNaN(hours)
        ||
        Number.isNaN(minutes)
    ) {
        return time
    }

    const date =
        new Date()

    date.setHours(
        hours,
        minutes,
        0,
        0
    )

    return new Intl.DateTimeFormat(
        'en-US',
        {
            hour: '2-digit',
            minute: '2-digit',
            hour12: true,
        }
    ).format(date)
}


function formatDuration(minutes)
{
    const totalMinutes =
        Math.max(
            0,
            Number(minutes || 0)
        )

    const hours =
        Math.floor(
            totalMinutes / 60
        )

    const remainingMinutes =
        totalMinutes % 60

    if (
        hours > 0
        &&
        remainingMinutes > 0
    ) {
        return `${hours}h ${remainingMinutes}m`
    }

    if (hours > 0) {
        return `${hours}h`
    }

    return `${remainingMinutes}m`
}


function formatStatus(status)
{
    const labels = {

        scheduled:
            'Scheduled',

        absent:
            'Absent',

        present:
            'Present',

        break:
            'On Break',

        completed:
            'Checked Out',

        leave:
            'On Leave',

    }

    return (
        labels[status]
        ??
        String(status || '')
            .replace(/[_-]+/g, ' ')
            .replace(
                /\b\w/g,
                (character) =>
                    character.toUpperCase()
            )
    )
}


function getStatusClass(status)
{
    const classes = {

        scheduled:
            'attendance-status-scheduled',

        absent:
            'attendance-status-absent',

        present:
            'attendance-status-present',

        break:
            'attendance-status-break',

        completed:
            'attendance-status-completed',

        leave:
            'attendance-status-leave',

    }

    return (
        classes[status]
        ??
        'attendance-status-scheduled'
    )
}


function getStatusIcon(status)
{
    const icons = {

        scheduled:
            'bi-clock',

        absent:
            'bi-person-x',

        break:
            'bi-cup-hot',

        completed:
            'bi-check-circle',

        leave:
            'bi-calendar-minus',

    }

    return (
        icons[status]
        ??
        'bi-circle'
    )
}


/*
|--------------------------------------------------------------------------
| Lifecycle
|--------------------------------------------------------------------------
*/

onMounted(async () => {

    await Promise.all([
        loadAttendances(),
        loadEmployees(),
    ])

    startAutoRefresh()

    window.addEventListener(
        'focus',
        handleWindowFocus
    )

})


onBeforeUnmount(() => {

    stopAutoRefresh()

    window.removeEventListener(
        'focus',
        handleWindowFocus
    )

    if (messageTimer) {
        clearTimeout(messageTimer)
    }

})

</script>


<style>

@import '@/assets/css/staff/attendance/attendance.css';

</style>