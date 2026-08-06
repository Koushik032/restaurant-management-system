<template>
  <section class="customer-edit-page">
    <!-- ==================================================
         Page Header
    =================================================== -->

    <header class="customer-edit-header">
      <div class="customer-edit-heading">
        <button
          type="button"
          class="customer-edit-back-button"
          aria-label="Return to customer list"
          @click="goBack"
        >
          <i
            class="bi bi-arrow-left"
            aria-hidden="true"
          ></i>
        </button>

        <div>
          <p class="customer-edit-eyebrow">
            Customer Management
          </p>

          <h1>
            Edit Customer
          </h1>

          <p>
            Update customer contact information and status.
          </p>
        </div>
      </div>

      <span
        v-if="customerCode"
        class="customer-edit-code"
      >
        {{ customerCode }}
      </span>
    </header>

    <!-- ==================================================
         Success Message
    =================================================== -->

    <div
      v-if="successMessage"
      class="customer-alert customer-success-alert"
      role="status"
      aria-live="polite"
    >
      <span
        class="customer-alert-icon"
        aria-hidden="true"
      >
        <i class="bi bi-check-circle-fill"></i>
      </span>

      <div class="customer-alert-content">
        <strong>
          Customer updated
        </strong>

        <p>
          {{ successMessage }}
        </p>
      </div>

      <button
        type="button"
        class="customer-alert-close"
        aria-label="Close success message"
        @click="successMessage = ''"
      >
        <i
          class="bi bi-x-lg"
          aria-hidden="true"
        ></i>
      </button>
    </div>

    <!-- ==================================================
         Error Message
    =================================================== -->

    <div
      v-if="globalError"
      class="customer-alert customer-error-alert"
      role="alert"
      aria-live="assertive"
    >
      <span
        class="customer-alert-icon"
        aria-hidden="true"
      >
        <i class="bi bi-exclamation-triangle-fill"></i>
      </span>

      <div class="customer-alert-content">
        <strong>
          Unable to update customer
        </strong>

        <p>
          {{ globalError }}
        </p>
      </div>

      <button
        type="button"
        class="customer-alert-close"
        aria-label="Close error message"
        @click="globalError = ''"
      >
        <i
          class="bi bi-x-lg"
          aria-hidden="true"
        ></i>
      </button>
    </div>

    <!-- ==================================================
         Initial Loading State
    =================================================== -->

    <div
      v-if="pageLoading"
      class="customer-edit-loading"
      role="status"
      aria-live="polite"
    >
      <span
        class="spinner-border"
        aria-hidden="true"
      ></span>

      <div>
        <strong>
          Loading customer information
        </strong>

        <p>
          Please wait while the customer profile is being loaded.
        </p>
      </div>
    </div>

    <!-- ==================================================
         Edit Form
    =================================================== -->

    <form
      v-else
      class="customer-edit-form-card"
      novalidate
      @submit.prevent="submitForm"
    >
      <!-- ==================================================
           Form Header
      =================================================== -->

      <div class="customer-edit-form-header">
        <div>
          <h2>
            Customer Information
          </h2>

          <p>
            Update the fields below and save your changes.
          </p>
        </div>

        <span
          class="customer-edit-status-preview"
          :class="{
            'customer-edit-status-active':
              form.is_active,

            'customer-edit-status-inactive':
              !form.is_active,
          }"
        >
          {{
            form.is_active
              ? 'Active'
              : 'Inactive'
          }}
        </span>
      </div>

      <!-- ==================================================
           Form Fields
      =================================================== -->

      <div class="customer-edit-form-grid">
        <!-- Customer Name -->

        <label class="customer-edit-field customer-edit-field-full">
          <span class="customer-edit-label">
            Customer Name
            <strong aria-hidden="true">
              *
            </strong>
          </span>

          <span class="customer-edit-input-wrapper">
            <i
              class="bi bi-person"
              aria-hidden="true"
            ></i>

            <input
              v-model.trim="form.name"
              type="text"
              maxlength="150"
              autocomplete="name"
              placeholder="Enter customer name"
              :disabled="submitting"
              :aria-invalid="Boolean(errors.name)"
              @input="clearFieldError('name')"
            />
          </span>

          <small
            v-if="errors.name"
            class="customer-edit-field-error"
          >
            {{ errors.name }}
          </small>
        </label>

        <!-- Phone Number -->

        <label class="customer-edit-field">
          <span class="customer-edit-label">
            Phone Number
            <strong aria-hidden="true">
              *
            </strong>
          </span>

          <span class="customer-edit-input-wrapper">
            <i
              class="bi bi-telephone"
              aria-hidden="true"
            ></i>

            <input
              v-model.trim="form.phone"
              type="tel"
              maxlength="30"
              autocomplete="tel"
              placeholder="Enter phone number"
              :disabled="submitting"
              :aria-invalid="Boolean(errors.phone)"
              @input="clearFieldError('phone')"
            />
          </span>

          <small
            v-if="errors.phone"
            class="customer-edit-field-error"
          >
            {{ errors.phone }}
          </small>
        </label>

        <!-- Email Address -->

        <label class="customer-edit-field">
          <span class="customer-edit-label">
            Email Address

            <small>
              Optional
            </small>
          </span>

          <span class="customer-edit-input-wrapper">
            <i
              class="bi bi-envelope"
              aria-hidden="true"
            ></i>

            <input
              v-model.trim="form.email"
              type="email"
              maxlength="150"
              autocomplete="email"
              placeholder="Enter email address"
              :disabled="submitting"
              :aria-invalid="Boolean(errors.email)"
              @input="clearFieldError('email')"
            />
          </span>

          <small
            v-if="errors.email"
            class="customer-edit-field-error"
          >
            {{ errors.email }}
          </small>
        </label>

        <!-- Customer Status -->

        <fieldset
          class="customer-edit-field customer-edit-field-full customer-edit-status-field"
        >
          <legend class="customer-edit-label">
            Customer Status
          </legend>

          <label class="customer-edit-switch-row">
            <span>
              <strong>
                Active Customer
              </strong>

              <small>
                Inactive customers will not appear in active customer search.
              </small>
            </span>

            <input
              v-model="form.is_active"
              type="checkbox"
              class="customer-edit-switch-input"
              :disabled="submitting"
            />
          </label>

          <small
            v-if="errors.is_active"
            class="customer-edit-field-error"
          >
            {{ errors.is_active }}
          </small>
        </fieldset>
      </div>

      <!-- ==================================================
           Form Actions
      =================================================== -->

      <footer class="customer-edit-form-actions">
        <button
          type="button"
          class="customer-edit-button customer-edit-cancel-button"
          :disabled="submitting"
          @click="goBack"
        >
          <i
            class="bi bi-x-circle"
            aria-hidden="true"
          ></i>

          <span>
            Cancel
          </span>
        </button>

        <button
          type="submit"
          class="customer-edit-button customer-edit-submit-button"
          :disabled="
            submitting ||
            !isFormReady
          "
          :aria-busy="submitting"
        >
          <span
            v-if="submitting"
            class="spinner-border spinner-border-sm"
            aria-hidden="true"
          ></span>

          <i
            v-else
            class="bi bi-check2-circle"
            aria-hidden="true"
          ></i>

          <span>
            {{
              submitting
                ? 'Updating...'
                : 'Update Customer'
            }}
          </span>
        </button>
      </footer>
    </form>
  </section>
</template>

<script setup>
import {
  computed,
  onMounted,
  reactive,
  ref,
} from 'vue'

import {
  useRoute,
  useRouter,
} from 'vue-router'

import customerService
  from '@/services/customerService'

import '@/assets/css/customers/customer-overview.css'
import '@/assets/css/customers/customer-edit.css'
import '@/assets/css/customers/customer-responsive.css'

/*
|--------------------------------------------------------------------------
| Router
|--------------------------------------------------------------------------
*/

const route =
  useRoute()

const router =
  useRouter()

/*
|--------------------------------------------------------------------------
| Page State
|--------------------------------------------------------------------------
*/

const pageLoading =
  ref(true)

const submitting =
  ref(false)

const globalError =
  ref('')

const successMessage =
  ref('')

const customerCode =
  ref('')

/*
|--------------------------------------------------------------------------
| Customer Form
|--------------------------------------------------------------------------
*/

const form = reactive({
  name:
    '',

  phone:
    '',

  email:
    '',

  is_active:
    true,
})

/*
|--------------------------------------------------------------------------
| Validation Errors
|--------------------------------------------------------------------------
*/

const errors = reactive({
  name:
    '',

  phone:
    '',

  email:
    '',

  is_active:
    '',
})

/*
|--------------------------------------------------------------------------
| Customer ID
|--------------------------------------------------------------------------
*/

const customerId =
  computed(() => {
    const id =
      Number(
        route.params.id,
      )

    return (
      Number.isInteger(id) &&
      id > 0
    )
      ? id
      : null
  })

/*
|--------------------------------------------------------------------------
| Form Ready State
|--------------------------------------------------------------------------
*/

const isFormReady =
  computed(() => {
    return (
      form.name.trim().length >= 2 &&
      form.phone.trim() !== ''
    )
  })
  /*
|--------------------------------------------------------------------------
| Load Customer
|--------------------------------------------------------------------------
*/

async function loadCustomer() {
  if (!customerId.value) {
    pageLoading.value = false

    globalError.value =
      'A valid customer ID is required.'

    return
  }

  pageLoading.value = true
  globalError.value = ''
  successMessage.value = ''

  clearAllErrors()

  try {
    const response =
      await customerService.getCustomer(
        customerId.value,
      )

    const customer =
      response?.data
        ?.customer ||
      response?.data

    if (!customer) {
      throw new Error(
        'Customer information was not found.',
      )
    }

    fillForm(
      customer,
    )
  } catch (error) {
    globalError.value =
      customerService
        .getCustomerErrorMessage(
          error,
          'Unable to load customer information.',
        )
  } finally {
    pageLoading.value = false
  }
}

/*
|--------------------------------------------------------------------------
| Fill Form with Existing Data
|--------------------------------------------------------------------------
*/

function fillForm(
  customer,
) {
  form.name =
    String(
      customer?.name || '',
    ).trim()

  form.phone =
    String(
      customer?.phone || '',
    ).trim()

  form.email =
    String(
      customer?.email || '',
    ).trim()

  form.is_active =
    resolveBoolean(
      customer?.is_active,
      true,
    )

  customerCode.value =
    String(
      customer?.customer_code ||
      `CUST-${String(
        customer?.id ||
        customerId.value,
      ).padStart(
        4,
        '0',
      )}`,
    )
}

/*
|--------------------------------------------------------------------------
| Submit Form
|--------------------------------------------------------------------------
*/

async function submitForm() {
  if (
    submitting.value ||
    !customerId.value
  ) {
    return
  }

  globalError.value = ''
  successMessage.value = ''

  clearAllErrors()

  if (!validateForm()) {
    return
  }

  submitting.value = true

  try {
    const response =
      await customerService.updateCustomer(
        customerId.value,
        {
          name:
            form.name.trim(),

          phone:
            form.phone.trim(),

          email:
            form.email.trim() ||
            null,

          is_active:
            Boolean(
              form.is_active,
            ),
        },
      )

    const updatedCustomer =
      response?.data

    if (updatedCustomer) {
      fillForm(
        updatedCustomer,
      )
    }

    successMessage.value =
      response?.message ||
      'Customer updated successfully.'

    /*
    |--------------------------------------------------------------------------
    | Return to Customer List
    |--------------------------------------------------------------------------
    |
    | Update success হওয়ার পরে অল্প সময় success message দেখিয়ে list page-এ
    | ফিরে যাবে।
    |
    */

    window.setTimeout(
      () => {
        router.push({
          name: 'customers',
        })
      },
      700,
    )
  } catch (error) {
    const validationErrors =
      customerService
        .getValidationErrors(
          error,
        )

    assignValidationErrors(
      validationErrors,
    )

    globalError.value =
      customerService
        .getCustomerErrorMessage(
          error,
          'Unable to update customer.',
        )
  } finally {
    submitting.value = false
  }
}

/*
|--------------------------------------------------------------------------
| Frontend Form Validation
|--------------------------------------------------------------------------
*/

function validateForm() {
  let isValid =
    true

  const name =
    form.name.trim()

  const phone =
    form.phone.trim()

  const email =
    form.email.trim()

  if (name.length < 2) {
    errors.name =
      'Customer name must be at least 2 characters.'

    isValid = false
  }

  if (!phone) {
    errors.phone =
      'Phone number is required.'

    isValid = false
  }

  if (
    email &&
    !isValidEmail(
      email,
    )
  ) {
    errors.email =
      'Please enter a valid email address.'

    isValid = false
  }

  return isValid
}

/*
|--------------------------------------------------------------------------
| Email Validation
|--------------------------------------------------------------------------
*/

function isValidEmail(
  value,
) {
  return /^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(
    String(
      value || '',
    ).trim(),
  )
}

/*
|--------------------------------------------------------------------------
| Assign API Validation Errors
|--------------------------------------------------------------------------
*/

function assignValidationErrors(
  validationErrors,
) {
  if (
    !validationErrors ||
    typeof validationErrors !==
      'object'
  ) {
    return
  }

  Object.keys(
    errors,
  ).forEach(
    (field) => {
      if (
        validationErrors[field]
      ) {
        errors[field] =
          String(
            validationErrors[field],
          )
      }
    },
  )
}

/*
|--------------------------------------------------------------------------
| Clear One Field Error
|--------------------------------------------------------------------------
*/

function clearFieldError(
  field,
) {
  if (
    Object.prototype
      .hasOwnProperty
      .call(
        errors,
        field,
      )
  ) {
    errors[field] = ''
  }

  if (globalError.value) {
    globalError.value = ''
  }
}

/*
|--------------------------------------------------------------------------
| Clear All Validation Errors
|--------------------------------------------------------------------------
*/

function clearAllErrors() {
  Object.keys(
    errors,
  ).forEach(
    (field) => {
      errors[field] = ''
    },
  )
}

/*
|--------------------------------------------------------------------------
| Resolve Boolean
|--------------------------------------------------------------------------
*/

function resolveBoolean(
  value,
  fallback = false,
) {
  if (
    typeof value ===
      'boolean'
  ) {
    return value
  }

  if (
    value === 1 ||
    value === '1' ||
    value === 'true'
  ) {
    return true
  }

  if (
    value === 0 ||
    value === '0' ||
    value === 'false'
  ) {
    return false
  }

  return fallback
}

/*
|--------------------------------------------------------------------------
| Go Back
|--------------------------------------------------------------------------
*/

function goBack() {
  if (submitting.value) {
    return
  }

  router.push({
    name: 'customers',
  })
}

/*
|--------------------------------------------------------------------------
| Mounted Lifecycle
|--------------------------------------------------------------------------
*/

onMounted(() => {
  loadCustomer()
})
</script>