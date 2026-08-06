<template>

<div
    v-if="show"
    class="employee-modal-overlay"
    @mousedown.self="closeModal"
>

    <div class="employee-modal">

        <!-- Header -->

        <div class="employee-modal-header">

            <div>

                <h3>

                    <i
                        class="bi"
                        :class="
                            isEditing
                                ? 'bi-pencil-square'
                                : 'bi-person-plus'
                        "
                    ></i>

                    {{
                        isEditing
                            ? 'Edit Employee'
                            : 'Add Employee'
                    }}

                </h3>

                <p>
                    Manage employee profile and login information
                </p>

            </div>


            <button
                type="button"
                class="employee-close-btn"
                :disabled="saving"
                @click="closeModal"
            >
                <i class="bi bi-x-lg"></i>
            </button>

        </div>


        <form @submit.prevent="submitForm">

            <div class="employee-modal-body">

                <!-- Error -->

                <div
                    v-if="generalError"
                    class="employee-form-alert"
                >

                    <i class="bi bi-exclamation-circle"></i>

                    <span>
                        {{ generalError }}
                    </span>

                </div>


                <!-- Integration Notice -->

                <div
                    v-if="isEditing"
                    class="employee-integration-notice"
                >

                    <i class="bi bi-info-circle"></i>

                    <div>

                        <strong>
                            Attendance controls are managed separately
                        </strong>

                        <p>
                            Current working status is changed from the Employee
                            table. Account blocking is also managed from the
                            Employee table.
                        </p>

                    </div>

                </div>


                <!-- Personal Information -->

                <div class="employee-form-section">

                    <div class="employee-form-section-title">

                        <i class="bi bi-person"></i>

                        Personal Information

                    </div>


                    <div class="employee-form-group">

                        <label for="employee-name">

                            Staff Name

                            <span>*</span>

                        </label>

                        <input
                            id="employee-name"
                            v-model.trim="form.name"
                            type="text"
                            autocomplete="name"
                            placeholder="Enter staff name"
                            :class="{
                                'employee-input-error':
                                    hasError('name'),
                            }"
                        />

                        <small
                            v-if="hasError('name')"
                            class="employee-field-error"
                        >
                            {{ firstError('name') }}
                        </small>

                    </div>


                    <div class="employee-form-grid">

                        <div class="employee-form-group">

                            <label for="employee-phone">
                                Phone
                            </label>

                            <input
                                id="employee-phone"
                                v-model.trim="form.phone"
                                type="text"
                                autocomplete="tel"
                                placeholder="Example: 01700000000"
                                :class="{
                                    'employee-input-error':
                                        hasError('phone'),
                                }"
                            />

                            <small
                                v-if="hasError('phone')"
                                class="employee-field-error"
                            >
                                {{ firstError('phone') }}
                            </small>

                        </div>


                        <div class="employee-form-group">

                            <label for="employee-email">

                                Email

                                <span>*</span>

                            </label>

                            <input
                                id="employee-email"
                                v-model.trim="form.email"
                                type="email"
                                autocomplete="email"
                                placeholder="employee@example.com"
                                :class="{
                                    'employee-input-error':
                                        hasError('email'),
                                }"
                            />

                            <small
                                v-if="hasError('email')"
                                class="employee-field-error"
                            >
                                {{ firstError('email') }}
                            </small>

                        </div>

                    </div>

                </div>


                <!-- Account Information -->

                <div class="employee-form-section">

                    <div class="employee-form-section-title">

                        <i class="bi bi-person-lock"></i>

                        Account Information

                    </div>


                    <div class="employee-form-grid">

                        <div class="employee-form-group">

                            <label for="employee-username">

                                Username

                                <span>*</span>

                            </label>

                            <input
                                id="employee-username"
                                v-model.trim="form.username"
                                type="text"
                                autocomplete="username"
                                placeholder="Enter username"
                                :class="{
                                    'employee-input-error':
                                        hasError('username'),
                                }"
                            />

                            <small
                                v-if="hasError('username')"
                                class="employee-field-error"
                            >
                                {{ firstError('username') }}
                            </small>

                        </div>


                        <div class="employee-form-group">

                            <label for="employee-role">

                                Role

                                <span>*</span>

                            </label>

                            <select
                                id="employee-role"
                                v-model="form.role_id"
                                :disabled="rolesLoading"
                                :class="{
                                    'employee-input-error':
                                        hasError('role_id'),
                                }"
                            >

                                <option value="">

                                    {{
                                        rolesLoading
                                            ? 'Loading roles...'
                                            : 'Select role'
                                    }}

                                </option>

                                <option
                                    v-for="role in roles"
                                    :key="role.id"
                                    :value="String(role.id)"
                                >
                                    {{
                                        role.label
                                        ||
                                        formatRoleName(
                                            role.name
                                        )
                                    }}
                                </option>

                            </select>

                            <small
                                v-if="hasError('role_id')"
                                class="employee-field-error"
                            >
                                {{ firstError('role_id') }}
                            </small>

                            <small
                                v-else
                                class="employee-form-help"
                            >
                                Admin role is never available for staff.
                            </small>

                        </div>

                    </div>

                </div>


                <!-- Employment -->

                <div class="employee-form-section">

                    <div class="employee-form-section-title">

                        <i class="bi bi-briefcase"></i>

                        Employment Information

                    </div>


                    <div class="employee-form-grid">

                        <div class="employee-form-group">

                            <label for="employee-joining-date">

                                Joining Date

                                <span>*</span>

                            </label>

                            <input
                                id="employee-joining-date"
                                v-model="form.joining_date"
                                type="date"
                                :class="{
                                    'employee-input-error':
                                        hasError('joining_date'),
                                }"
                            />

                            <small
                                v-if="hasError('joining_date')"
                                class="employee-field-error"
                            >
                                {{ firstError('joining_date') }}
                            </small>

                        </div>


                        <div class="employee-form-group">

                            <label for="employee-hourly-rate">

                                Hourly Rate

                                <span>*</span>

                            </label>

                            <div class="employee-money-input">

                                <span>
                                    ৳
                                </span>

                                <input
                                    id="employee-hourly-rate"
                                    v-model="form.hourly_rate"
                                    type="number"
                                    min="0"
                                    step="0.01"
                                    placeholder="0.00"
                                    :class="{
                                        'employee-input-error':
                                            hasError('hourly_rate'),
                                    }"
                                />

                            </div>

                            <small
                                v-if="hasError('hourly_rate')"
                                class="employee-field-error"
                            >
                                {{ firstError('hourly_rate') }}
                            </small>

                        </div>

                    </div>

                </div>


                <!-- Password -->

                <div class="employee-form-section">

                    <div class="employee-form-section-title">

                        <i class="bi bi-key"></i>

                        {{
                            isEditing
                                ? 'Change Password'
                                : 'Login Password'
                        }}

                    </div>


                    <div class="employee-form-grid">

                        <div class="employee-form-group">

                            <label for="employee-password">

                                Password

                                <span v-if="!isEditing">
                                    *
                                </span>

                            </label>

                            <input
                                id="employee-password"
                                v-model="form.password"
                                type="password"
                                :autocomplete="
                                    isEditing
                                        ? 'new-password'
                                        : 'new-password'
                                "
                                :placeholder="
                                    isEditing
                                        ? 'Leave blank to keep current password'
                                        : 'Enter password'
                                "
                                :class="{
                                    'employee-input-error':
                                        hasError('password'),
                                }"
                            />

                            <small
                                v-if="hasError('password')"
                                class="employee-field-error"
                            >
                                {{ firstError('password') }}
                            </small>

                        </div>


                        <div class="employee-form-group">

                            <label for="employee-password-confirmation">

                                Confirm Password

                                <span v-if="!isEditing">
                                    *
                                </span>

                            </label>

                            <input
                                id="employee-password-confirmation"
                                v-model="form.password_confirmation"
                                type="password"
                                autocomplete="new-password"
                                placeholder="Confirm password"
                            />

                        </div>

                    </div>

                </div>

            </div>


            <!-- Footer -->

            <div class="employee-modal-footer">

                <button
                    type="button"
                    class="employee-cancel-btn"
                    :disabled="saving"
                    @click="closeModal"
                >
                    Cancel
                </button>


                <button
                    type="submit"
                    class="employee-save-btn"
                    :disabled="saving || rolesLoading"
                >

                    <span
                        v-if="saving"
                        class="employee-button-spinner"
                    ></span>

                    <i
                        v-else
                        class="bi"
                        :class="
                            isEditing
                                ? 'bi-check-lg'
                                : 'bi-person-plus'
                        "
                    ></i>

                    {{
                        saving
                            ? 'Saving...'
                            : isEditing
                                ? 'Update Employee'
                                : 'Create Employee'
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

import employeeService
    from '@/services/employeeService'


const props = defineProps({

    show: {
        type: Boolean,
        default: false,
    },

    employee: {
        type: Object,
        default: null,
    },

})


const emit = defineEmits([
    'close',
    'saved',
])


const roles = ref([])

const rolesLoading = ref(false)

const saving = ref(false)

const generalError = ref('')

const validationErrors = ref({})


const isEditing = computed(() => {
    return Boolean(
        props.employee?.id
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


function createDefaultForm()
{
    return {

        name: '',

        username: '',

        email: '',

        phone: '',

        role_id: '',

        joining_date:
            getLocalDate(),

        hourly_rate: '',

        password: '',

        password_confirmation: '',

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

    validationErrors.value = {}

    generalError.value = ''
}


function fillForm()
{
    resetForm()

    if (!props.employee) {
        return
    }

    form.name =
        props.employee.staff_name
        ??
        props.employee.user?.name
        ??
        ''

    form.username =
        props.employee.username
        ??
        props.employee.user?.username
        ??
        ''

    form.email =
        props.employee.email
        ??
        props.employee.user?.email
        ??
        ''

    form.phone =
        props.employee.phone
        ??
        ''

    form.role_id =
        props.employee.role_id
            ? String(
                props.employee.role_id
            )
            : ''

    form.joining_date =
        props.employee.joining_date
        ??
        getLocalDate()

    form.hourly_rate =
        props.employee.hourly_rate
        ??
        ''

    form.password = ''

    form.password_confirmation = ''
}


watch(
    () => props.show,
    async (isOpen) => {

        if (!isOpen) {
            return
        }

        fillForm()

        if (roles.value.length === 0) {
            await loadRoles()
        }

    }
)


watch(
    () => props.employee,
    () => {

        if (props.show) {
            fillForm()
        }

    }
)


async function loadRoles()
{
    rolesLoading.value = true

    try {

        const response =
            await employeeService
                .getRoles()

        roles.value =
            extractCollection(response)
                .filter(
                    (role) =>
                        String(
                            role.name
                            ??
                            ''
                        ).toLowerCase()
                        !==
                        'admin'
                )

    }

    catch (error) {

        roles.value = []

        generalError.value =
            getErrorMessage(
                error,
                'Unable to load employee roles.'
            )

    }

    finally {

        rolesLoading.value = false

    }
}


function extractCollection(response)
{
    const payload =
        response?.data
        ??
        response

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
    return (
        error?.response?.data?.message
        ??
        error?.message
        ??
        fallback
    )
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

    if (!form.name) {

        errors.name = [
            'Staff name is required.',
        ]

    }

    if (!form.username) {

        errors.username = [
            'Username is required.',
        ]

    }

    if (!form.email) {

        errors.email = [
            'Email address is required.',
        ]

    }

    if (!form.role_id) {

        errors.role_id = [
            'Please select an employee role.',
        ]

    }

    if (!form.joining_date) {

        errors.joining_date = [
            'Joining date is required.',
        ]

    }

    if (
        form.hourly_rate === ''
        ||
        Number.isNaN(
            Number(
                form.hourly_rate
            )
        )
        ||
        Number(
            form.hourly_rate
        ) < 0
    ) {
        errors.hourly_rate = [
            'Please enter a valid hourly rate.',
        ]
    }

    if (
        !isEditing.value
        &&
        !form.password
    ) {
        errors.password = [
            'Password is required for a new employee.',
        ]
    }

    if (
        form.password
        &&
        form.password !==
        form.password_confirmation
    ) {
        errors.password = [
            'Password confirmation does not match.',
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
    const payload = {

        name:
            form.name,

        username:
            form.username,

        email:
            form.email,

        phone:
            form.phone
            ||
            null,

        role_id:
            Number(
                form.role_id
            ),

        joining_date:
            form.joining_date,

        hourly_rate:
            Number(
                form.hourly_rate
            ),

    }


    if (form.password) {

        payload.password =
            form.password

        payload.password_confirmation =
            form.password_confirmation

    }


    return payload
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

        let response

        const payload =
            buildPayload()


        if (isEditing.value) {

            response =
                await employeeService
                    .updateEmployee(
                        props.employee.id,
                        payload
                    )

        }

        else {

            response =
                await employeeService
                    .createEmployee(
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
            (
                isEditing.value
                    ? 'Employee updated successfully.'
                    : 'Employee created successfully.'
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
                    ? 'Unable to update employee.'
                    : 'Unable to create employee.'
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


function closeModal()
{
    if (saving.value) {
        return
    }

    resetForm()

    emit('close')
}

</script>