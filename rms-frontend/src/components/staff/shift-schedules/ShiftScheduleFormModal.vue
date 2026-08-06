<template>

<div
    v-if="show"
    class="shift-modal-overlay"
    @mousedown.self="closeModal"
>

    <div class="shift-modal shift-recurring-modal">

        <!-- Header -->

        <div class="shift-modal-header">

            <div>

                <h3>

                    <i
                        class="bi"
                        :class="
                            isEditing
                                ? 'bi-pencil-square'
                                : 'bi-calendar-range'
                        "
                    ></i>

                    {{
                        isEditing
                            ? 'Edit Recurring Schedule'
                            : 'Add Recurring Schedule'
                    }}

                </h3>

                <p>
                    Assign one schedule across a date range and selected working days
                </p>

            </div>


            <button
                type="button"
                class="shift-modal-close"
                :disabled="saving"
                title="Close"
                @click="closeModal"
            >
                <i class="bi bi-x-lg"></i>
            </button>

        </div>


        <form @submit.prevent="submitForm">

            <div class="shift-modal-body">

                <!-- General Error -->

                <div
                    v-if="generalError"
                    class="shift-form-alert"
                >

                    <i class="bi bi-exclamation-circle"></i>

                    <span>
                        {{ generalError }}
                    </span>

                </div>


                <!-- Employee -->

                <div class="shift-form-section">

                    <div class="shift-form-section-title">

                        <i class="bi bi-person-badge"></i>

                        Employee

                    </div>


                    <div class="shift-form-group">

                        <label for="schedule-employee">

                            Employee

                            <span class="shift-required">
                                *
                            </span>

                        </label>

                        <select
                            id="schedule-employee"
                            v-model="form.employee_id"
                            :class="{
                                'shift-input-error':
                                    hasError('employee_id'),
                            }"
                        >

                            <option value="">
                                Select employee
                            </option>

                            <option
                                v-for="employee in employeeOptions"
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

                        <small
                            v-if="hasError('employee_id')"
                            class="shift-field-error"
                        >
                            {{ firstError('employee_id') }}
                        </small>

                        <small
                            v-else
                            class="shift-form-help"
                        >
                            Admin accounts are excluded from employee scheduling.
                        </small>

                    </div>

                </div>


                <!-- Date Range -->

                <div class="shift-form-section">

                    <div class="shift-form-section-title">

                        <i class="bi bi-calendar-range"></i>

                        Effective Date Range

                    </div>


                    <div class="shift-form-grid">

                        <div class="shift-form-group">

                            <label for="schedule-start-date">

                                Effective From

                                <span class="shift-required">
                                    *
                                </span>

                            </label>

                            <input
                                id="schedule-start-date"
                                v-model="form.start_date"
                                type="date"
                                :class="{
                                    'shift-input-error':
                                        hasError('start_date'),
                                }"
                            />

                            <small
                                v-if="hasError('start_date')"
                                class="shift-field-error"
                            >
                                {{ firstError('start_date') }}
                            </small>

                        </div>


                        <div class="shift-form-group">

                            <label for="schedule-end-date">

                                Effective To

                                <span class="shift-required">
                                    *
                                </span>

                            </label>

                            <input
                                id="schedule-end-date"
                                v-model="form.end_date"
                                type="date"
                                :min="form.start_date"
                                :class="{
                                    'shift-input-error':
                                        hasError('end_date'),
                                }"
                            />

                            <small
                                v-if="hasError('end_date')"
                                class="shift-field-error"
                            >
                                {{ firstError('end_date') }}
                            </small>

                        </div>

                    </div>


                    <div
                        v-if="dateRangePreview"
                        class="shift-range-preview"
                    >

                        <i class="bi bi-calendar3"></i>

                        <span>
                            {{ dateRangePreview }}
                        </span>

                    </div>

                </div>


                <!-- Working Days -->

                <div class="shift-form-section">

                    <div class="shift-form-section-title">

                        <i class="bi bi-calendar-week"></i>

                        Working Days

                    </div>


                    <div class="shift-day-presets">

                        <button
                            type="button"
                            @click="selectEveryDay"
                        >
                            Every Day
                        </button>

                        <button
                            type="button"
                            @click="selectSundayToThursday"
                        >
                            Sunday–Thursday
                        </button>

                        <button
                            type="button"
                            @click="selectFridaySaturday"
                        >
                            Friday–Saturday
                        </button>

                        <button
                            type="button"
                            @click="clearWorkingDays"
                        >
                            Clear
                        </button>

                    </div>


                    <div class="shift-working-days">

                        <label
                            v-for="day in dayOptions"
                            :key="day.value"
                            class="shift-day-option"
                            :class="{
                                selected:
                                    form.working_days.includes(
                                        day.value
                                    ),
                            }"
                        >

                            <input
                                type="checkbox"
                                :value="day.value"
                                v-model="form.working_days"
                            />

                            <span class="shift-day-short">
                                {{ day.short }}
                            </span>

                            <span class="shift-day-full">
                                {{ day.label }}
                            </span>

                        </label>

                    </div>


                    <small
                        v-if="hasError('working_days')"
                        class="shift-field-error"
                    >
                        {{ firstError('working_days') }}
                    </small>

                    <small
                        v-else
                        class="shift-form-help"
                    >
                        The employee will only appear on schedule dates matching
                        these selected days.
                    </small>

                </div>


                <!-- Shift Time -->

                <div class="shift-form-section">

                    <div class="shift-form-section-title">

                        <i class="bi bi-clock-history"></i>

                        Shift Time

                    </div>


                    <div class="shift-form-grid">

                        <div class="shift-form-group">

                            <label for="schedule-start-time">

                                Start Time

                                <span class="shift-required">
                                    *
                                </span>

                            </label>

                            <input
                                id="schedule-start-time"
                                v-model="form.start_time"
                                type="time"
                                :class="{
                                    'shift-input-error':
                                        hasError('start_time'),
                                }"
                            />

                            <small
                                v-if="hasError('start_time')"
                                class="shift-field-error"
                            >
                                {{ firstError('start_time') }}
                            </small>

                        </div>


                        <div class="shift-form-group">

                            <label for="schedule-end-time">

                                End Time

                                <span class="shift-required">
                                    *
                                </span>

                            </label>

                            <input
                                id="schedule-end-time"
                                v-model="form.end_time"
                                type="time"
                                :class="{
                                    'shift-input-error':
                                        hasError('end_time'),
                                }"
                            />

                            <small
                                v-if="hasError('end_time')"
                                class="shift-field-error"
                            >
                                {{ firstError('end_time') }}
                            </small>

                        </div>

                    </div>


                    <div
                        v-if="durationPreview"
                        class="shift-duration-preview"
                    >

                        <div>

                            <i class="bi bi-stopwatch"></i>

                            <span>
                                Scheduled duration
                            </span>

                        </div>

                        <strong>
                            {{ durationPreview }}
                        </strong>

                        <small v-if="isOvernightPreview">
                            This shift ends on the following day.
                        </small>

                    </div>


                    <div class="shift-form-grid">

                        <div class="shift-form-group">

                            <label for="schedule-grace">
                                Grace Period
                            </label>

                            <div class="shift-number-field">

                                <input
                                    id="schedule-grace"
                                    v-model="form.grace_minutes"
                                    type="number"
                                    min="0"
                                    max="180"
                                    step="1"
                                    :class="{
                                        'shift-input-error':
                                            hasError('grace_minutes'),
                                    }"
                                />

                                <span>
                                    minutes
                                </span>

                            </div>

                            <small
                                v-if="hasError('grace_minutes')"
                                class="shift-field-error"
                            >
                                {{ firstError('grace_minutes') }}
                            </small>

                            <small
                                v-else
                                class="shift-form-help"
                            >
                                Grace time will be used for late attendance calculation.
                            </small>

                        </div>


                        <div class="shift-form-group">

                            <label for="schedule-status">
                                Schedule Status
                            </label>

                            <select
                                id="schedule-status"
                                v-model="form.is_active"
                            >

                                <option :value="true">
                                    Active
                                </option>

                                <option :value="false">
                                    Inactive
                                </option>

                            </select>

                            <small class="shift-form-help">
                                Inactive schedules will not generate attendance.
                            </small>

                        </div>

                    </div>

                </div>


                <!-- Notes -->

                <div class="shift-form-section">

                    <div class="shift-form-section-title">

                        <i class="bi bi-journal-text"></i>

                        Notes

                    </div>


                    <div class="shift-form-group">

                        <textarea
                            v-model.trim="form.notes"
                            rows="3"
                            maxlength="1000"
                            placeholder="Optional notes about this recurring schedule"
                            :class="{
                                'shift-input-error':
                                    hasError('notes'),
                            }"
                        ></textarea>

                        <div class="shift-notes-meta">

                            <small
                                v-if="hasError('notes')"
                                class="shift-field-error"
                            >
                                {{ firstError('notes') }}
                            </small>

                            <small v-else>
                                Optional
                            </small>

                            <small>
                                {{ form.notes.length }}/1000
                            </small>

                        </div>

                    </div>

                </div>

            </div>


            <!-- Footer -->

            <div class="shift-modal-footer">

                <button
                    type="button"
                    class="shift-cancel-btn"
                    :disabled="saving"
                    @click="closeModal"
                >
                    Cancel
                </button>


                <button
                    type="submit"
                    class="shift-save-btn"
                    :disabled="saving"
                >

                    <span
                        v-if="saving"
                        class="shift-button-spinner"
                    ></span>

                    <i
                        v-else
                        class="bi"
                        :class="
                            isEditing
                                ? 'bi-check-lg'
                                : 'bi-calendar-plus'
                        "
                    ></i>

                    {{
                        saving
                            ? 'Saving...'
                            : isEditing
                                ? 'Update Entire Schedule'
                                : 'Create Recurring Schedule'
                    }}

                </button>

            </div>

        </form>

    </div>

</div>

</template>


<script setup>

import {
    computed,
    reactive,
    ref,
    watch,
} from 'vue'

import shiftScheduleService
    from '@/services/shiftScheduleService'


const props = defineProps({

    show: {
        type: Boolean,
        default: false,
    },

    schedule: {
        type: Object,
        default: null,
    },

    employees: {
        type: Array,
        default: () => [],
    },

})


const emit = defineEmits([
    'close',
    'saved',
])


const saving = ref(false)

const generalError = ref('')

const validationErrors = ref({})


const dayOptions = [

    {
        value: 'saturday',
        label: 'Saturday',
        short: 'Sat',
    },

    {
        value: 'sunday',
        label: 'Sunday',
        short: 'Sun',
    },

    {
        value: 'monday',
        label: 'Monday',
        short: 'Mon',
    },

    {
        value: 'tuesday',
        label: 'Tuesday',
        short: 'Tue',
    },

    {
        value: 'wednesday',
        label: 'Wednesday',
        short: 'Wed',
    },

    {
        value: 'thursday',
        label: 'Thursday',
        short: 'Thu',
    },

    {
        value: 'friday',
        label: 'Friday',
        short: 'Fri',
    },

]


const isEditing = computed(() => {
    return Boolean(
        props.schedule?.id
    )
})


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


function addDaysToDate(
    dateString,
    numberOfDays
) {
    const date =
        new Date(
            `${dateString}T00:00:00`
        )

    date.setDate(
        date.getDate() + numberOfDays
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

    return `${year}-${month}-${day}`
}


function createDefaultForm()
{
    const startDate =
        getLocalDate()

    return {

        employee_id: '',

        start_date:
            startDate,

        end_date:
            addDaysToDate(
                startDate,
                30
            ),

        working_days: [
            'saturday',
            'sunday',
            'monday',
            'tuesday',
            'wednesday',
            'thursday',
            'friday',
        ],

        start_time: '09:00',

        end_time: '17:00',

        grace_minutes: 0,

        is_active: true,

        notes: '',

    }
}


const form = reactive(
    createDefaultForm()
)


const employeeOptions = computed(() => {

    const employeeMap =
        new Map()

    props.employees.forEach(
        (employee) => {

            employeeMap.set(
                Number(employee.id),
                {
                    ...employee,
                }
            )

        }
    )

    /*
    |--------------------------------------------------------------------------
    | Existing blocked employee editing support
    |--------------------------------------------------------------------------
    */

    if (
        props.schedule?.employee_id
        &&
        !employeeMap.has(
            Number(
                props.schedule.employee_id
            )
        )
    ) {
        employeeMap.set(
            Number(
                props.schedule.employee_id
            ),
            {
                id:
                    Number(
                        props.schedule.employee_id
                    ),

                staff_name:
                    props.schedule.staff_name
                    ??
                    'Unknown Staff',

                role_name:
                    props.schedule.role_name
                    ??
                    null,

                role_label:
                    props.schedule.role_label
                    ??
                    'No Role',
            }
        )
    }

    return Array.from(
        employeeMap.values()
    ).sort(
        (first, second) =>
            String(
                first.staff_name
                ??
                ''
            ).localeCompare(
                String(
                    second.staff_name
                    ??
                    ''
                )
            )
    )

})


function timeToMinutes(time)
{
    if (
        !time
        ||
        !String(time).includes(':')
    ) {
        return null
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
        return null
    }

    return (
        hours * 60
        +
        minutes
    )
}


const scheduledMinutesPreview = computed(() => {

    const startMinutes =
        timeToMinutes(
            form.start_time
        )

    const endMinutes =
        timeToMinutes(
            form.end_time
        )

    if (
        startMinutes === null
        ||
        endMinutes === null
        ||
        startMinutes === endMinutes
    ) {
        return null
    }

    if (endMinutes < startMinutes) {

        return (
            endMinutes
            +
            1440
            -
            startMinutes
        )

    }

    return (
        endMinutes
        -
        startMinutes
    )

})


const isOvernightPreview = computed(() => {

    const startMinutes =
        timeToMinutes(
            form.start_time
        )

    const endMinutes =
        timeToMinutes(
            form.end_time
        )

    if (
        startMinutes === null
        ||
        endMinutes === null
    ) {
        return false
    }

    return endMinutes < startMinutes
})


const durationPreview = computed(() => {

    const minutes =
        scheduledMinutesPreview.value

    if (!minutes) {
        return ''
    }

    return formatDuration(minutes)
})


const dateRangePreview = computed(() => {

    if (
        !form.start_date
        ||
        !form.end_date
    ) {
        return ''
    }

    const start =
        formatDate(
            form.start_date
        )

    const end =
        formatDate(
            form.end_date
        )

    if (
        form.start_date ===
        form.end_date
    ) {
        return start
    }

    return `${start} — ${end}`
})


function resetForm()
{
    Object.assign(
        form,
        createDefaultForm()
    )

    validationErrors.value = {}

    generalError.value = ''
}


function fillForm()
{
    resetForm()

    if (!props.schedule) {
        return
    }

    form.employee_id =
        props.schedule.employee_id
            ? String(
                props.schedule.employee_id
            )
            : ''

    form.start_date =
        props.schedule.start_date
        ??
        getLocalDate()

    form.end_date =
        props.schedule.end_date
        ??
        form.start_date

    form.working_days =
        Array.isArray(
            props.schedule.working_days
        )
            ? [
                ...props.schedule.working_days,
            ]
            : []

    form.start_time =
        props.schedule.base_start_time
        ??
        props.schedule.start_time
        ??
        '09:00'

    form.end_time =
        props.schedule.base_end_time
        ??
        props.schedule.end_time
        ??
        '17:00'

    form.grace_minutes =
        props.schedule.base_grace_minutes
        ??
        props.schedule.grace_minutes
        ??
        0

    form.is_active =
        props.schedule.is_active
        ??
        true

    form.notes =
        props.schedule.base_notes
        ??
        props.schedule.notes
        ??
        ''
}


watch(
    () => props.show,
    (isOpen) => {

        if (isOpen) {
            fillForm()
        }

    }
)


watch(
    () => props.schedule,
    () => {

        if (props.show) {
            fillForm()
        }

    }
)


function selectEveryDay()
{
    form.working_days =
        dayOptions.map(
            (day) => day.value
        )
}


function selectSundayToThursday()
{
    form.working_days = [

        'sunday',

        'monday',

        'tuesday',

        'wednesday',

        'thursday',

    ]
}


function selectFridaySaturday()
{
    form.working_days = [

        'friday',

        'saturday',

    ]
}


function clearWorkingDays()
{
    form.working_days = []
}


function hasError(field)
{
    return Boolean(
        validationErrors.value?.[field]?.length
    )
}


function firstError(field)
{
    return (
        validationErrors.value?.[field]?.[0]
        ??
        ''
    )
}


function validateForm()
{
    const errors = {}

    if (!form.employee_id) {

        errors.employee_id = [
            'Please select an employee.',
        ]

    }

    if (!form.start_date) {

        errors.start_date = [
            'Schedule start date is required.',
        ]

    }

    if (!form.end_date) {

        errors.end_date = [
            'Schedule end date is required.',
        ]

    }

    if (
        form.start_date
        &&
        form.end_date
        &&
        form.end_date < form.start_date
    ) {
        errors.end_date = [
            'Schedule end date cannot be before the start date.',
        ]
    }

    if (
        !Array.isArray(
            form.working_days
        )
        ||
        form.working_days.length === 0
    ) {
        errors.working_days = [
            'Please select at least one working day.',
        ]
    }

    if (!form.start_time) {

        errors.start_time = [
            'Shift start time is required.',
        ]

    }

    if (!form.end_time) {

        errors.end_time = [
            'Shift end time is required.',
        ]

    }

    if (
        form.start_time
        &&
        form.end_time
        &&
        form.start_time === form.end_time
    ) {
        errors.end_time = [
            'Shift start time and end time cannot be the same.',
        ]
    }

    const graceMinutes =
        Number(
            form.grace_minutes
        )

    if (
        Number.isNaN(graceMinutes)
        ||
        graceMinutes < 0
        ||
        graceMinutes > 180
    ) {
        errors.grace_minutes = [
            'Grace period must be between 0 and 180 minutes.',
        ]
    }

    validationErrors.value =
        errors

    return (
        Object.keys(errors).length === 0
    )
}


function buildPayload()
{
    return {

        employee_id:
            Number(
                form.employee_id
            ),

        start_date:
            form.start_date,

        end_date:
            form.end_date,

        working_days: [
            ...form.working_days,
        ],

        start_time:
            form.start_time,

        end_time:
            form.end_time,

        grace_minutes:
            Number(
                form.grace_minutes
                ||
                0
            ),

        is_active:
            Boolean(
                form.is_active
            ),

        notes:
            form.notes
            ||
            null,

    }
}


function extractPayload(response)
{
    return response?.data
        ??
        response
}


function getErrorMessage(
    error,
    fallback
) {
    return (
        error?.response?.data?.message
        ??
        error?.message
        ??
        fallback
    )
}


async function submitForm()
{
    generalError.value = ''

    validationErrors.value = {}

    if (!validateForm()) {
        return
    }

    saving.value = true

    try {

        const payload =
            buildPayload()

        let response

        if (isEditing.value) {

            response =
                await shiftScheduleService
                    .updateSchedule(
                        props.schedule.id,
                        payload
                    )

        }

        else {

            response =
                await shiftScheduleService
                    .createSchedule(
                        payload
                    )

        }

        const responsePayload =
            extractPayload(response)

        emit(
            'saved',
            responsePayload?.message
            ??
            (
                isEditing.value
                    ? 'Recurring shift schedule updated successfully.'
                    : 'Recurring shift schedule created successfully.'
            )
        )

    }

    catch (error) {

        validationErrors.value =
            error?.response?.data?.errors
            ??
            {}

        generalError.value =
            getErrorMessage(
                error,
                isEditing.value
                    ? 'Unable to update recurring schedule.'
                    : 'Unable to create recurring schedule.'
            )

    }

    finally {

        saving.value = false

    }
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


function closeModal()
{
    if (saving.value) {
        return
    }

    resetForm()

    emit('close')
}

</script>