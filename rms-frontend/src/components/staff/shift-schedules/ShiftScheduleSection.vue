<template>

<section class="shift-schedule-section">

    <!-- Message -->

    <Transition name="shift-message">

        <div
            v-if="message.show"
            class="shift-message"
            :class="`shift-message-${message.type}`"
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


    <!-- Header -->

    <div class="shift-section-header">

        <div>

            <h3>
                Daily Shift Schedule
            </h3>

            <p>
                View the effective schedule for a selected date and manage
                recurring schedules or one-day changes
            </p>

        </div>


        <button
            type="button"
            class="shift-add-btn"
            @click="openAddSchedule"
        >

            <i class="bi bi-calendar-range"></i>

            Add Recurring Schedule

        </button>

    </div>


    <!-- Date Navigator -->

    <div class="shift-date-navigator">

        <div class="shift-date-navigator-label">

            <i class="bi bi-calendar3"></i>

            <div>

                <span>
                    Viewing Schedule For
                </span>

                <strong>
                    {{ selectedDateLabel }}
                </strong>

            </div>

        </div>


        <div class="shift-date-navigation-actions">

            <button
                type="button"
                title="Previous day"
                @click="changeScheduleDate(-1)"
            >
                <i class="bi bi-chevron-left"></i>
            </button>


            <input
                v-model="filters.schedule_date"
                type="date"
                @change="applyFilters"
            />


            <button
                type="button"
                class="shift-today-btn"
                @click="goToToday"
            >
                Today
            </button>


            <button
                type="button"
                title="Next day"
                @click="changeScheduleDate(1)"
            >
                <i class="bi bi-chevron-right"></i>
            </button>

        </div>

    </div>


    <!-- Summary -->

    <div class="shift-summary-grid">

        <div class="shift-summary-card">

            <div class="shift-summary-icon">
                <i class="bi bi-people"></i>
            </div>

            <div>

                <span>
                    Scheduled Staff
                </span>

                <strong>
                    {{ meta.total }}
                </strong>

            </div>

        </div>


        <div class="shift-summary-card">

            <div class="shift-summary-icon">
                <i class="bi bi-pencil-square"></i>
            </div>

            <div>

                <span>
                    Modified Shifts
                </span>

                <strong>
                    {{ modifiedScheduleCount }}
                </strong>

            </div>

        </div>


        <div class="shift-summary-card">

            <div class="shift-summary-icon">
                <i class="bi bi-calendar-x"></i>
            </div>

            <div>

                <span>
                    Day Off
                </span>

                <strong>
                    {{ dayOffCount }}
                </strong>

            </div>

        </div>


        <div class="shift-summary-card">

            <div class="shift-summary-icon">
                <i class="bi bi-clock-history"></i>
            </div>

            <div>

                <span>
                    Scheduled Hours
                </span>

                <strong>
                    {{ scheduledHoursOnPage }}
                </strong>

            </div>

        </div>

    </div>


    <!-- Filters -->

    <div class="shift-filter-card">

        <div class="shift-filter-grid">

            <div class="shift-filter-group">

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


            <div class="shift-filter-group">

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

                        {{ employee.role_label }}

                    </option>

                </select>

            </div>


            <div class="shift-filter-group">

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


            <div class="shift-filter-group">

                <label>
                    Schedule Type
                </label>

                <select v-model="filters.status">

                    <option value="">
                        All Types
                    </option>

                    <option value="active">
                        Active
                    </option>

                    <option value="inactive">
                        Inactive
                    </option>

                    <option value="regular">
                        Regular Schedule
                    </option>

                    <option value="modified">
                        Modified Shift
                    </option>

                    <option value="day_off">
                        Day Off
                    </option>

                </select>

            </div>


            <div class="shift-filter-actions">

                <button
                    type="button"
                    class="shift-filter-btn"
                    :disabled="loading"
                    @click="applyFilters"
                >

                    <i class="bi bi-funnel"></i>

                    Apply

                </button>


                <button
                    type="button"
                    class="shift-clear-btn"
                    :disabled="loading"
                    @click="clearFilters"
                >
                    Clear
                </button>

            </div>

        </div>

    </div>


    <!-- Table -->

    <div class="shift-table-card">

        <div class="shift-table-responsive">

            <table class="shift-table">

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
                            Date
                        </th>

                        <th>
                            Effective Shift
                        </th>

                        <th>
                            Duration
                        </th>

                        <th>
                            Grace
                        </th>

                        <th>
                            Type
                        </th>

                        <th>
                            Schedule Rule
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
                            colspan="11"
                            class="shift-table-state-cell"
                        >

                            <div class="shift-table-state">

                                <span class="shift-table-spinner"></span>

                                Loading daily schedules...

                            </div>

                        </td>

                    </tr>


                    <!-- Error -->

                    <tr v-else-if="errorMessage">

                        <td
                            colspan="11"
                            class="shift-table-state-cell"
                        >

                            <div class="shift-table-state shift-table-error">

                                <i class="bi bi-exclamation-circle"></i>

                                {{ errorMessage }}

                            </div>

                        </td>

                    </tr>


                    <!-- Empty -->

                    <tr v-else-if="schedules.length === 0">

                        <td
                            colspan="11"
                            class="shift-table-state-cell"
                        >

                            <div class="shift-table-state">

                                <i class="bi bi-calendar-x"></i>

                                No employee schedule applies to this date.

                            </div>

                        </td>

                    </tr>


                    <!-- Rows -->

                    <template v-else>

                        <tr
                            v-for="(schedule, index) in schedules"
                            :key="schedule.id"
                        >

                            <!-- Serial -->

                            <td>
                                {{ getSerialNumber(index) }}
                            </td>


                            <!-- Employee -->

                            <td>

                                <div class="shift-employee-cell">

                                    <div class="shift-employee-avatar">

                                        {{
                                            getInitials(
                                                schedule.staff_name
                                            )
                                        }}

                                    </div>

                                    <div>

                                        <strong>
                                            {{
                                                schedule.staff_name
                                                ||
                                                'Unknown Staff'
                                            }}
                                        </strong>

                                        <small>
                                            @{{
                                                schedule.username
                                                ||
                                                'unknown'
                                            }}
                                        </small>

                                    </div>

                                </div>

                            </td>


                            <!-- Role -->

                            <td>

                                <span class="shift-role-badge">
                                    {{
                                        schedule.role_label
                                        ||
                                        formatRoleName(
                                            schedule.role_name
                                        )
                                    }}
                                </span>

                            </td>


                            <!-- Date -->

                            <td>

                                <div class="shift-date-cell">

                                    <strong>
                                        {{
                                            schedule.schedule_date_label
                                            ||
                                            formatDate(
                                                filters.schedule_date
                                            )
                                        }}
                                    </strong>

                                    <small>
                                        {{
                                            schedule.day_name
                                            ||
                                            getDayName(
                                                filters.schedule_date
                                            )
                                        }}
                                    </small>

                                </div>

                            </td>


                            <!-- Effective Shift -->

                            <td>

                                <div
                                    v-if="!schedule.is_day_off"
                                    class="shift-effective-time"
                                >

                                    <strong>

                                        {{
                                            schedule.start_time_label
                                            ||
                                            formatTime(
                                                schedule.start_time
                                            )
                                        }}

                                        —

                                        {{
                                            schedule.end_time_label
                                            ||
                                            formatTime(
                                                schedule.end_time
                                            )
                                        }}

                                    </strong>

                                    <small v-if="schedule.is_overnight">
                                        Ends next day
                                    </small>

                                </div>


                                <span
                                    v-else
                                    class="shift-day-off-value"
                                >
                                    No Shift
                                </span>

                            </td>


                            <!-- Duration -->

                            <td>

                                <strong v-if="!schedule.is_day_off">

                                    {{
                                        schedule.scheduled_duration_label
                                        ||
                                        formatDuration(
                                            schedule.scheduled_minutes
                                        )
                                    }}

                                </strong>

                                <span v-else>
                                    —
                                </span>

                            </td>


                            <!-- Grace -->

                            <td>

                                <span
                                    v-if="!schedule.is_day_off"
                                    class="shift-grace-badge"
                                >

                                    {{
                                        schedule.grace_minutes > 0
                                            ? `${schedule.grace_minutes} min`
                                            : 'No grace'
                                    }}

                                </span>

                                <span v-else>
                                    —
                                </span>

                            </td>


                            <!-- Type -->

                            <td>

                                <span
                                    class="shift-type-badge"
                                    :class="
                                        getScheduleTypeClass(
                                            schedule
                                        )
                                    "
                                >

                                    {{
                                        schedule.override_type_label
                                        ||
                                        'Regular Schedule'
                                    }}

                                </span>

                            </td>


                            <!-- Schedule Rule -->

                            <td>

                                <div class="shift-rule-cell">

                                    <strong>
                                        {{
                                            schedule.date_range_label
                                            ||
                                            '-'
                                        }}
                                    </strong>

                                    <small>
                                        {{
                                            formatWorkingDays(
                                                schedule.working_day_labels
                                                ||
                                                schedule.working_days
                                            )
                                        }}
                                    </small>

                                </div>

                            </td>


                            <!-- Status -->

                            <td>

                                <button
                                    type="button"
                                    class="shift-status-btn"
                                    :class="{
                                        active:
                                            schedule.is_active,

                                        inactive:
                                            !schedule.is_active,
                                    }"
                                    :disabled="
                                        statusLoadingId === schedule.id
                                        ||
                                        deleteLoadingId === schedule.id
                                    "
                                    @click="
                                        toggleScheduleStatus(
                                            schedule
                                        )
                                    "
                                >

                                    <span
                                        v-if="
                                            statusLoadingId === schedule.id
                                        "
                                        class="shift-inline-spinner"
                                    ></span>

                                    {{
                                        statusLoadingId === schedule.id
                                            ? 'Updating'
                                            : schedule.is_active
                                                ? 'Active'
                                                : 'Inactive'
                                    }}

                                </button>

                            </td>


                            <!-- Actions -->

                            <td>

                                <div class="shift-actions">

                                    <!-- Edit Entire Rule -->

                                    <button
                                        type="button"
                                        class="
                                            shift-action-btn
                                            shift-edit-btn
                                        "
                                        title="Edit entire recurring schedule"
                                        :disabled="
                                            deleteLoadingId === schedule.id
                                        "
                                        @click="
                                            editSchedule(schedule)
                                        "
                                    >

                                        <i class="bi bi-pencil"></i>

                                    </button>


                                    <!-- One-Day Change -->

                                    <button
                                        type="button"
                                        class="
                                            shift-action-btn
                                            shift-override-btn
                                        "
                                        :title="
                                            schedule.has_override
                                                ? 'Edit one-day change'
                                                : 'Create one-day change'
                                        "
                                        :disabled="
                                            deleteLoadingId === schedule.id
                                        "
                                        @click="
                                            openOverride(schedule)
                                        "
                                    >

                                        <i class="bi bi-calendar-event"></i>

                                    </button>


                                    <!-- Restore Regular -->

                                    <button
                                        v-if="
                                            schedule.has_override
                                            &&
                                            schedule.override_id
                                        "
                                        type="button"
                                        class="
                                            shift-action-btn
                                            shift-restore-btn
                                        "
                                        title="Remove override and restore regular schedule"
                                        :disabled="
                                            overrideLoadingId ===
                                            schedule.id
                                        "
                                        @click="
                                            restoreRegularSchedule(
                                                schedule
                                            )
                                        "
                                    >

                                        <span
                                            v-if="
                                                overrideLoadingId ===
                                                schedule.id
                                            "
                                            class="shift-inline-spinner"
                                        ></span>

                                        <i
                                            v-else
                                            class="bi bi-arrow-counterclockwise"
                                        ></i>

                                    </button>


                                    <!-- Delete Rule -->

                                    <button
                                        type="button"
                                        class="
                                            shift-action-btn
                                            shift-delete-btn
                                        "
                                        title="Delete entire recurring schedule"
                                        :disabled="
                                            deleteLoadingId === schedule.id
                                        "
                                        @click="
                                            deleteSchedule(schedule)
                                        "
                                    >

                                        <span
                                            v-if="
                                                deleteLoadingId ===
                                                schedule.id
                                            "
                                            class="shift-inline-spinner"
                                        ></span>

                                        <i
                                            v-else
                                            class="bi bi-trash"
                                        ></i>

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
            v-if="
                !loading
                &&
                meta.last_page > 1
            "
            class="shift-pagination"
        >

            <button
                type="button"
                :disabled="
                    meta.current_page <= 1
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
                    ({{ meta.total }} schedules)
                </small>

            </span>


            <button
                type="button"
                :disabled="
                    meta.current_page >=
                    meta.last_page
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


    <!-- Recurring Schedule Modal -->

    <ShiftScheduleFormModal
        :show="showScheduleModal"
        :schedule="selectedSchedule"
        :employees="employees"
        @close="closeScheduleModal"
        @saved="scheduleSaved"
    />


    <!-- One-Day Override Modal -->

    <ShiftScheduleOverrideModal
        :show="showOverrideModal"
        :schedule="selectedOverrideSchedule"
        :schedule-date="filters.schedule_date"
        @close="closeOverrideModal"
        @saved="overrideSaved"
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

import shiftScheduleService
    from '@/services/shiftScheduleService'

import ShiftScheduleFormModal
    from './ShiftScheduleFormModal.vue'

import ShiftScheduleOverrideModal
    from './ShiftScheduleOverrideModal.vue'


/*
|--------------------------------------------------------------------------
| Helpers
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
| State
|--------------------------------------------------------------------------
*/

const schedules = ref([])

const employees = ref([])

const loading = ref(false)

const employeesLoading = ref(false)

const errorMessage = ref('')

const statusLoadingId = ref(null)

const deleteLoadingId = ref(null)

const overrideLoadingId = ref(null)


/*
|--------------------------------------------------------------------------
| Modals
|--------------------------------------------------------------------------
*/

const showScheduleModal = ref(false)

const selectedSchedule = ref(null)

const showOverrideModal = ref(false)

const selectedOverrideSchedule = ref(null)


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
| Filters
|--------------------------------------------------------------------------
*/

const filters = reactive({

    schedule_date:
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
| Message
|--------------------------------------------------------------------------
*/

const message = reactive({

    show: false,

    type: 'success',

    text: '',

})


let messageTimer = null


const messageIcon = computed(() => {

    return message.type === 'error'
        ? 'bi-exclamation-circle'
        : 'bi-check-circle'

})


const selectedDateLabel = computed(() => {

    return formatDateWithDay(
        filters.schedule_date
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


const modifiedScheduleCount = computed(() => {

    return schedules.value.filter(
        (schedule) =>
            schedule.override_type ===
            'modified'
    ).length

})


const dayOffCount = computed(() => {

    return schedules.value.filter(
        (schedule) =>
            schedule.is_day_off
    ).length

})


const scheduledHoursOnPage = computed(() => {

    const totalHours =
        schedules.value.reduce(
            (total, schedule) => {

                if (schedule.is_day_off) {
                    return total
                }

                return (
                    total
                    +
                    Number(
                        schedule.scheduled_hours
                        ||
                        0
                    )
                )

            },
            0
        )

    return totalHours.toFixed(2)
})


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
| Response Helpers
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


function extractItem(response)
{
    const payload =
        extractPayload(response)

    return payload?.data
        ??
        payload
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
            await shiftScheduleService
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

        employeesLoading.value =
            false

    }
}


/*
|--------------------------------------------------------------------------
| Load Daily Schedule
|--------------------------------------------------------------------------
*/

async function loadSchedules()
{
    loading.value =
        true

    errorMessage.value =
        ''

    try {

        const response =
            await shiftScheduleService
                .getSchedules({

                    schedule_date:
                        filters.schedule_date,

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

        schedules.value =
            extractCollection(response)

        meta.value =
            extractMeta(response)

    }

    catch (error) {

        schedules.value = []

        errorMessage.value =
            getErrorMessage(
                error,
                'Unable to load daily shift schedules.'
            )

    }

    finally {

        loading.value =
            false

    }
}


/*
|--------------------------------------------------------------------------
| Refresh
|--------------------------------------------------------------------------
*/

async function refreshShiftSchedules()
{
    await Promise.all([
        loadSchedules(),
        loadEmployees(),
    ])

    if (!errorMessage.value) {

        showMessage(
            'Shift schedules refreshed successfully.'
        )

    }
}


defineExpose({
    refreshShiftSchedules,
})


/*
|--------------------------------------------------------------------------
| Date Navigation
|--------------------------------------------------------------------------
*/

async function changeScheduleDate(dayOffset)
{
    const date =
        new Date(
            `${filters.schedule_date}T00:00:00`
        )

    date.setDate(
        date.getDate() + dayOffset
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

    filters.schedule_date =
        `${year}-${month}-${day}`

    filters.page = 1

    await loadSchedules()
}


async function goToToday()
{
    filters.schedule_date =
        getLocalDate()

    filters.page = 1

    await loadSchedules()
}


/*
|--------------------------------------------------------------------------
| Filters
|--------------------------------------------------------------------------
*/

async function applyFilters()
{
    filters.page = 1

    await loadSchedules()
}


async function clearFilters()
{
    filters.search = ''

    filters.employee_id = ''

    filters.role_id = ''

    filters.status = ''

    filters.page = 1

    await loadSchedules()
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

    await loadSchedules()
}


/*
|--------------------------------------------------------------------------
| Add / Edit Recurring Schedule
|--------------------------------------------------------------------------
*/

async function openAddSchedule()
{
    selectedSchedule.value = null

    if (employees.value.length === 0) {
        await loadEmployees()
    }

    showScheduleModal.value = true
}


function editSchedule(schedule)
{
    selectedSchedule.value = {
        ...schedule,

        working_days:
            Array.isArray(
                schedule.working_days
            )
                ? [
                    ...schedule.working_days,
                ]
                : [],
    }

    showScheduleModal.value = true
}


function closeScheduleModal()
{
    showScheduleModal.value = false

    selectedSchedule.value = null
}


async function scheduleSaved(savedMessage)
{
    closeScheduleModal()

    await loadSchedules()

    showMessage(
        savedMessage
        ||
        'Recurring schedule saved successfully.'
    )
}


/*
|--------------------------------------------------------------------------
| One-Day Override
|--------------------------------------------------------------------------
*/

function openOverride(schedule)
{
    selectedOverrideSchedule.value = {
        ...schedule,
    }

    showOverrideModal.value = true
}


function closeOverrideModal()
{
    showOverrideModal.value = false

    selectedOverrideSchedule.value = null
}


async function overrideSaved(savedMessage)
{
    closeOverrideModal()

    await loadSchedules()

    showMessage(
        savedMessage
        ||
        'One-day schedule change saved successfully.'
    )
}


async function restoreRegularSchedule(schedule)
{
    if (
        !schedule.override_id
        ||
        overrideLoadingId.value !== null
    ) {
        return
    }

    const confirmed =
        window.confirm(
            `Remove the one-day change for ${schedule.staff_name} on ${formatDate(filters.schedule_date)} and restore the regular schedule?`
        )

    if (!confirmed) {
        return
    }

    overrideLoadingId.value =
        schedule.id

    try {

        const response =
            await shiftScheduleService
                .deleteOverride(
                    schedule.override_id
                )

        await loadSchedules()

        showMessage(
            getResponseMessage(
                response,
                'Regular schedule restored successfully.'
            )
        )

    }

    catch (error) {

        showMessage(
            getErrorMessage(
                error,
                'Unable to restore regular schedule.'
            ),
            'error'
        )

    }

    finally {

        overrideLoadingId.value =
            null

    }
}


/*
|--------------------------------------------------------------------------
| Activate / Deactivate
|--------------------------------------------------------------------------
*/

async function toggleScheduleStatus(schedule)
{
    if (
        statusLoadingId.value !== null
    ) {
        return
    }

    const makeActive =
        !schedule.is_active

    statusLoadingId.value =
        schedule.id

    try {

        const response =
            await shiftScheduleService
                .updateStatus(
                    schedule.id,
                    makeActive
                )

        const updatedSchedule =
            extractItem(response)

        if (updatedSchedule?.id) {

            Object.assign(
                schedule,
                updatedSchedule
            )

        }

        await loadSchedules()

        showMessage(
            getResponseMessage(
                response,
                makeActive
                    ? 'Schedule activated successfully.'
                    : 'Schedule deactivated successfully.'
            )
        )

    }

    catch (error) {

        showMessage(
            getErrorMessage(
                error,
                'Unable to update schedule status.'
            ),
            'error'
        )

    }

    finally {

        statusLoadingId.value =
            null

    }
}


/*
|--------------------------------------------------------------------------
| Delete Entire Schedule
|--------------------------------------------------------------------------
*/

async function deleteSchedule(schedule)
{
    const confirmed =
        window.confirm(
            `Delete the entire recurring schedule for ${schedule.staff_name}? This will affect the full date range, not only ${formatDate(filters.schedule_date)}.`
        )

    if (!confirmed) {
        return
    }

    deleteLoadingId.value =
        schedule.id

    try {

        const response =
            await shiftScheduleService
                .deleteSchedule(
                    schedule.id
                )

        if (
            schedules.value.length === 1
            &&
            filters.page > 1
        ) {
            filters.page -= 1
        }

        await loadSchedules()

        showMessage(
            getResponseMessage(
                response,
                'Recurring schedule deleted successfully.'
            )
        )

    }

    catch (error) {

        showMessage(
            getErrorMessage(
                error,
                'Unable to delete recurring schedule.'
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


function formatDate(date)
{
    if (!date) {
        return '-'
    }

    const parsedDate =
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
    ).format(parsedDate)
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


function getDayName(date)
{
    if (!date) {
        return ''
    }

    const parsedDate =
        new Date(
            `${date}T00:00:00`
        )

    return new Intl.DateTimeFormat(
        'en-US',
        {
            weekday: 'long',
        }
    ).format(parsedDate)
}


function formatTime(time)
{
    if (!time) {
        return '-'
    }

    const [
        hours,
        minutes,
    ] = String(time)
        .split(':')
        .map(Number)

    const date = new Date()

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
        Number(minutes || 0)

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


function formatWorkingDays(days)
{
    if (
        !Array.isArray(days)
        ||
        days.length === 0
    ) {
        return 'No working days'
    }

    return days
        .map(
            (day) =>
                String(day)
                    .charAt(0)
                    .toUpperCase()
                +
                String(day).slice(1)
        )
        .join(', ')
}


function getScheduleTypeClass(schedule)
{
    if (schedule.is_day_off) {
        return 'shift-type-day-off'
    }

    if (
        schedule.override_type ===
        'modified'
    ) {
        return 'shift-type-modified'
    }

    return 'shift-type-regular'
}


/*
|--------------------------------------------------------------------------
| Mounted
|--------------------------------------------------------------------------
*/

onMounted(async () => {

    await Promise.all([
        loadSchedules(),
        loadEmployees(),
    ])

})


onBeforeUnmount(() => {

    if (messageTimer) {
        clearTimeout(messageTimer)
    }

})

</script>


<style>

@import '@/assets/css/staff/shift-schedules/shift-schedule.css';

</style>