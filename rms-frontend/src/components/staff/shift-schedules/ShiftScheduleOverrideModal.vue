<template>

<div
    v-if="show"
    class="shift-modal-overlay"
    @mousedown.self="closeModal"
>

    <div class="shift-modal shift-override-modal">

        <!-- Header -->

        <div class="shift-modal-header">

            <div>

                <h3>

                    <i class="bi bi-calendar-event"></i>

                    One-Day Schedule Change

                </h3>

                <p>
                    Change or cancel this employee's schedule for one specific date
                </p>

            </div>


            <button
                type="button"
                class="shift-modal-close"
                :disabled="saving"
                @click="closeModal"
            >
                <i class="bi bi-x-lg"></i>
            </button>

        </div>


        <form @submit.prevent="submitForm">

            <div class="shift-modal-body">

                <div
                    v-if="generalError"
                    class="shift-form-alert"
                >

                    <i class="bi bi-exclamation-circle"></i>

                    <span>
                        {{ generalError }}
                    </span>

                </div>


                <!-- Employee Summary -->

                <div class="shift-override-employee">

                    <div class="shift-employee-avatar">
                        {{ employeeInitials }}
                    </div>

                    <div>

                        <strong>
                            {{
                                schedule?.staff_name
                                ||
                                'Unknown Staff'
                            }}
                        </strong>

                        <span>
                            {{
                                schedule?.role_label
                                ||
                                formatRoleName(
                                    schedule?.role_name
                                )
                            }}
                        </span>

                    </div>

                </div>


                <!-- Selected Date -->

                <div class="shift-override-date">

                    <i class="bi bi-calendar3"></i>

                    <div>

                        <span>
                            Selected Date
                        </span>

                        <strong>
                            {{ formattedScheduleDate }}
                        </strong>

                    </div>

                </div>


                <!-- Regular Schedule -->

                <div class="shift-regular-schedule-box">

                    <span>
                        Regular Schedule
                    </span>

                    <strong>

                        {{
                            schedule?.base_start_time_label
                            ||
                            formatTime(
                                schedule?.base_start_time
                            )
                        }}

                        —

                        {{
                            schedule?.base_end_time_label
                            ||
                            formatTime(
                                schedule?.base_end_time
                            )
                        }}

                    </strong>

                    <small>

                        Grace:

                        {{
                            schedule?.base_grace_minutes
                            ??
                            0
                        }}

                        minutes

                    </small>

                </div>


                <!-- Override Type -->

                <div class="shift-form-section">

                    <div class="shift-form-section-title">

                        <i class="bi bi-sliders"></i>

                        Change Type

                    </div>


                    <div class="shift-override-types">

                        <label
                            class="shift-override-type"
                            :class="{
                                selected:
                                    form.override_type ===
                                    'modified',
                            }"
                        >

                            <input
                                v-model="form.override_type"
                                type="radio"
                                value="modified"
                            />

                            <i class="bi bi-clock"></i>

                            <div>

                                <strong>
                                    Modified Shift
                                </strong>

                                <span>
                                    Use a different start and end time for this date
                                </span>

                            </div>

                        </label>


                        <label
                            class="shift-override-type"
                            :class="{
                                selected:
                                    form.override_type ===
                                    'day_off',
                            }"
                        >

                            <input
                                v-model="form.override_type"
                                type="radio"
                                value="day_off"
                            />

                            <i class="bi bi-calendar-x"></i>

                            <div>

                                <strong>
                                    Day Off
                                </strong>

                                <span>
                                    Cancel this employee's shift for this date
                                </span>

                            </div>

                        </label>

                    </div>

                </div>


                <!-- Modified Time -->

                <div
                    v-if="
                        form.override_type ===
                        'modified'
                    "
                    class="shift-form-section"
                >

                    <div class="shift-form-section-title">

                        <i class="bi bi-clock-history"></i>

                        Modified Shift Time

                    </div>


                    <div class="shift-form-grid">

                        <div class="shift-form-group">

                            <label>

                                Start Time

                                <span class="shift-required">
                                    *
                                </span>

                            </label>

                            <input
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

                            <label>

                                End Time

                                <span class="shift-required">
                                    *
                                </span>

                            </label>

                            <input
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
                                Modified duration
                            </span>

                        </div>

                        <strong>
                            {{ durationPreview }}
                        </strong>

                        <small v-if="isOvernightPreview">
                            Ends on the following day.
                        </small>

                    </div>


                    <div class="shift-form-group">

                        <label>
                            Grace Period
                        </label>

                        <div class="shift-number-field">

                            <input
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

                    </div>

                </div>


                <!-- Day Off Information -->

                <div
                    v-else
                    class="shift-day-off-warning"
                >

                    <i class="bi bi-info-circle"></i>

                    <div>

                        <strong>
                            This date will become a day off.
                        </strong>

                        <p>
                            No attendance or absent record should be generated
                            from this schedule for the selected date.
                        </p>

                    </div>

                </div>


                <!-- Notes -->

                <div class="shift-form-section">

                    <div class="shift-form-section-title">

                        <i class="bi bi-journal-text"></i>

                        Reason or Notes

                    </div>


                    <div class="shift-form-group">

                        <textarea
                            v-model.trim="form.notes"
                            rows="3"
                            maxlength="1000"
                            placeholder="Example: Special event, approved leave or changed duty time"
                        ></textarea>

                        <div class="shift-notes-meta">

                            <small>
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
                        class="bi bi-check-lg"
                    ></i>

                    {{
                        saving
                            ? 'Saving...'
                            : isEditing
                                ? 'Update One-Day Change'
                                : 'Save One-Day Change'
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

    scheduleDate: {
        type: String,
        default: '',
    },

})


const emit = defineEmits([
    'close',
    'saved',
])


const saving = ref(false)

const generalError = ref('')

const validationErrors = ref({})


const isEditing = computed(() => {
    return Boolean(
        props.schedule?.override_id
    )
})


const employeeInitials = computed(() => {

    const name =
        props.schedule?.staff_name

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

})


const formattedScheduleDate = computed(() => {
    return formatDate(
        props.scheduleDate
    )
})


function createDefaultForm()
{
    return {

        override_type:
            'modified',

        start_time:
            props.schedule?.start_time
            ??
            props.schedule?.base_start_time
            ??
            '09:00',

        end_time:
            props.schedule?.end_time
            ??
            props.schedule?.base_end_time
            ??
            '17:00',

        grace_minutes:
            props.schedule?.grace_minutes
            ??
            props.schedule?.base_grace_minutes
            ??
            0,

        notes: '',

    }
}


const form = reactive(
    createDefaultForm()
)


function resetForm()
{
    Object.assign(
        form,
        createDefaultForm()
    )

    generalError.value = ''

    validationErrors.value = {}
}


function fillForm()
{
    resetForm()

    if (
        !props.schedule?.has_override
        ||
        !props.schedule?.override
    ) {
        return
    }

    const override =
        props.schedule.override

    form.override_type =
        override.override_type
        ??
        'modified'

    form.start_time =
        override.start_time
        ??
        props.schedule.base_start_time
        ??
        '09:00'

    form.end_time =
        override.end_time
        ??
        props.schedule.base_end_time
        ??
        '17:00'

    form.grace_minutes =
        override.grace_minutes
        ??
        props.schedule.base_grace_minutes
        ??
        0

    form.notes =
        override.notes
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


function timeToMinutes(time)
{
    if (!time) {
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


const isOvernightPreview = computed(() => {

    const start =
        timeToMinutes(
            form.start_time
        )

    const end =
        timeToMinutes(
            form.end_time
        )

    if (
        start === null
        ||
        end === null
    ) {
        return false
    }

    return end < start
})


const durationPreview = computed(() => {

    if (
        form.override_type !==
        'modified'
    ) {
        return ''
    }

    const start =
        timeToMinutes(
            form.start_time
        )

    let end =
        timeToMinutes(
            form.end_time
        )

    if (
        start === null
        ||
        end === null
        ||
        start === end
    ) {
        return ''
    }

    if (end < start) {
        end += 1440
    }

    return formatDuration(
        end - start
    )
})


function validateForm()
{
    const errors = {}

    if (!props.scheduleDate) {

        errors.override_date = [
            'Override date is required.',
        ]

    }

    if (
        form.override_type ===
        'modified'
    ) {
        if (!form.start_time) {

            errors.start_time = [
                'Modified start time is required.',
            ]

        }

        if (!form.end_time) {

            errors.end_time = [
                'Modified end time is required.',
            ]

        }

        if (
            form.start_time
            &&
            form.end_time
            &&
            form.start_time ===
            form.end_time
        ) {
            errors.end_time = [
                'Modified start and end time cannot be the same.',
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
    }

    validationErrors.value =
        errors

    return (
        Object.keys(errors).length === 0
    )
}


function buildPayload()
{
    const payload = {

        override_date:
            props.scheduleDate,

        override_type:
            form.override_type,

        notes:
            form.notes
            ||
            null,

    }

    if (
        form.override_type ===
        'modified'
    ) {
        payload.start_time =
            form.start_time

        payload.end_time =
            form.end_time

        payload.grace_minutes =
            Number(
                form.grace_minutes
                ||
                0
            )
    }

    return payload
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
                    .updateOverride(
                        props.schedule.override_id,
                        payload
                    )

        }

        else {

            response =
                await shiftScheduleService
                    .createOverride(
                        props.schedule.id,
                        payload
                    )

        }

        const responsePayload =
            response?.data
            ??
            response

        emit(
            'saved',
            responsePayload?.message
            ??
            'One-day schedule change saved successfully.'
        )

    }

    catch (error) {

        validationErrors.value =
            error?.response?.data?.errors
            ??
            {}

        generalError.value =
            error?.response?.data?.message
            ??
            error?.message
            ??
            'Unable to save one-day schedule change.'

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
    const hours =
        Math.floor(
            minutes / 60
        )

    const remainingMinutes =
        minutes % 60

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