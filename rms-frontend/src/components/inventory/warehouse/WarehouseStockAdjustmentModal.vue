<template>
  <Teleport to="body">
    <Transition name="inventory-modal">
      <div
        v-if="show"
        class="warehouse-adjustment-backdrop"
        @click.self="requestClose"
      >
        <section
          class="warehouse-adjustment-modal"
          role="dialog"
          aria-modal="true"
          aria-labelledby="warehouse-adjustment-title"
        >
          <!-- Header -->

          <header class="warehouse-adjustment-header">
            <div class="warehouse-adjustment-heading">
              <div
                class="warehouse-adjustment-header-icon"
                :class="{
                  'warehouse-adjustment-header-in':
                    !isDecrease,

                  'warehouse-adjustment-header-out':
                    isDecrease,
                }"
              >
                <i
                  class="bi"
                  :class="
                    isDecrease
                      ? 'bi-box-arrow-up'
                      : 'bi-box-arrow-in-down'
                  "
                ></i>
              </div>

              <div>
                <h2 id="warehouse-adjustment-title">
                  Warehouse Stock Adjustment
                </h2>

                <p>
                  Increase or decrease the current
                  warehouse quantity.
                </p>
              </div>
            </div>

            <button
              type="button"
              class="warehouse-adjustment-close"
              aria-label="Close adjustment form"
              :disabled="submitting"
              @click="requestClose"
            >
              <i class="bi bi-x-lg" aria-hidden="true"></i>
            </button>
          </header>

          <!-- Material Summary -->

          <div class="warehouse-adjustment-material">
            <div class="warehouse-adjustment-material-icon">
              <i class="bi bi-box-seam" aria-hidden="true"></i>
            </div>

            <div class="warehouse-adjustment-material-info">
              <span>
                Raw Material
              </span>

              <strong>
                {{
                  stock?.material_name
                  ||
                  'Unknown Material'
                }}
              </strong>

              <small>
                {{
                  stock?.category
                  ||
                  'No category'
                }}
                ·
                {{
                  stock?.unit_label
                  ||
                  stock?.unit
                  ||
                  'Unit not available'
                }}
              </small>
            </div>

            <div class="warehouse-adjustment-current">
              <span>
                Current Stock
              </span>

              <strong>
                {{
                  stock?.quantity_formatted
                  ||
                  formatQuantity(
                    currentQuantity,
                  )
                }}
              </strong>

              <small
                class="warehouse-adjustment-current-status"
                :class="
                  statusClass(
                    stock?.status,
                  )
                "
              >
                {{
                  stock?.status_label
                  ||
                  formatStatus(
                    stock?.status,
                  )
                }}
              </small>
            </div>
          </div>

          <form
            novalidate
            @submit.prevent="submitForm"
          >
            <div class="warehouse-adjustment-body">
              <!-- General Error -->

              <div
                v-if="errorMessage"
                class="warehouse-adjustment-error-message"
                role="alert"
              >
                <i class="bi bi-exclamation-circle-fill"></i>

                <span>
                  {{ errorMessage }}
                </span>
              </div>

              <!-- Adjustment Type -->

              <div class="warehouse-adjustment-form-group">
                <label>
                  Adjustment Type
                  <span>*</span>
                </label>

                <div class="warehouse-adjustment-type-grid">
                  <label
                    class="warehouse-adjustment-type-option"
                    :class="{
                      'warehouse-adjustment-type-selected-in':
                        form.adjustment_type ===
                        'increase',
                    }"
                  >
                    <input
                      v-model="form.adjustment_type"
                      type="radio"
                      value="increase"
                      :disabled="submitting"
                    />

                    <span class="warehouse-adjustment-type-icon">
                      <i class="bi bi-plus-lg"></i>
                    </span>

                    <span>
                      <strong>
                        Increase Stock
                      </strong>

                      <small>
                        Add new warehouse quantity
                      </small>
                    </span>
                  </label>

                  <label
                    class="warehouse-adjustment-type-option"
                    :class="{
                      'warehouse-adjustment-type-selected-out':
                        form.adjustment_type ===
                        'decrease',
                    }"
                  >
                    <input
                      v-model="form.adjustment_type"
                      type="radio"
                      value="decrease"
                      :disabled="submitting"
                    />

                    <span class="warehouse-adjustment-type-icon">
                      <i class="bi bi-dash-lg"></i>
                    </span>

                    <span>
                      <strong>
                        Decrease Stock
                      </strong>

                      <small>
                        Remove warehouse quantity
                      </small>
                    </span>
                  </label>
                </div>

                <small
                  v-if="fieldError('adjustment_type')"
                  class="warehouse-adjustment-field-error"
                >
                  {{ fieldError('adjustment_type') }}
                </small>
              </div>

              <div class="warehouse-adjustment-form-grid">
                <!-- Quantity -->

                <div class="warehouse-adjustment-form-group">
                  <label for="warehouse-adjustment-quantity">
                    Adjustment Quantity
                    <span>*</span>
                  </label>

                  <div class="warehouse-adjustment-number-input">
                    <input
                      id="warehouse-adjustment-quantity"
                      v-model="form.quantity"
                      type="text"
                      inputmode="decimal"
                      autocomplete="off"
                      placeholder="0"
                      :disabled="submitting"
                      :class="{
                        'warehouse-adjustment-input-error':
                          fieldError(
                            'quantity',
                          ),
                      }"
                      @input="
                        handleDecimalInput(
                          $event,
                          'quantity',
                          4,
                        )
                      "
                    />

                    <span>
                      {{ stockUnit }}
                    </span>
                  </div>

                  <small
                    v-if="isDecrease"
                    class="warehouse-adjustment-field-help"
                  >
                    Maximum available:
                    {{ formatQuantity(currentQuantity) }}
                  </small>

                  <small
                    v-if="fieldError('quantity')"
                    class="warehouse-adjustment-field-error"
                  >
                    {{ fieldError('quantity') }}
                  </small>
                </div>

                <!-- Unit Cost -->

                <div
                  v-if="!isDecrease"
                  class="warehouse-adjustment-form-group"
                >
                  <label for="warehouse-adjustment-unit-cost">
                    New Unit Cost
                  </label>

                  <div class="warehouse-adjustment-money-input">
                    <span>
                      ৳
                    </span>

                    <input
                      id="warehouse-adjustment-unit-cost"
                      v-model="form.unit_cost"
                      type="text"
                      inputmode="decimal"
                      autocomplete="off"
                      placeholder="0.0000"
                      :disabled="submitting"
                      :class="{
                        'warehouse-adjustment-input-error':
                          fieldError(
                            'unit_cost',
                          ),
                      }"
                      @input="
                        handleDecimalInput(
                          $event,
                          'unit_cost',
                          4,
                        )
                      "
                    />
                  </div>

                  <small class="warehouse-adjustment-field-help">
                    Used to calculate the new weighted
                    average unit cost.
                  </small>

                  <small
                    v-if="fieldError('unit_cost')"
                    class="warehouse-adjustment-field-error"
                  >
                    {{ fieldError('unit_cost') }}
                  </small>
                </div>

                <!-- Current Average Cost -->

                <div
                  v-else
                  class="warehouse-adjustment-form-group"
                >
                  <label>
                    Current Average Cost
                  </label>

                  <div class="warehouse-adjustment-readonly">
                    {{
                      stock
                        ?.average_unit_cost_formatted
                      ||
                      formatMoney(
                        stock?.average_unit_cost,
                      )
                    }}
                    / {{ stockUnit }}
                  </div>

                  <small class="warehouse-adjustment-field-help">
                    Decreasing stock does not change
                    average unit cost.
                  </small>
                </div>

                <!-- Notes -->

                <div class="warehouse-adjustment-form-group warehouse-adjustment-full-field">
                  <label for="warehouse-adjustment-notes">
                    Adjustment Reason
                    <span>*</span>
                  </label>

                  <textarea
                    id="warehouse-adjustment-notes"
                    v-model.trim="form.notes"
                    rows="3"
                    maxlength="2000"
                    :placeholder="
                      isDecrease
                        ? 'Example: Physical stock correction'
                        : 'Example: Additional stock received manually'
                    "
                    :disabled="submitting"
                    :class="{
                      'warehouse-adjustment-input-error':
                        fieldError(
                          'notes',
                        ),
                    }"
                  ></textarea>

                  <div class="warehouse-adjustment-notes-meta">
                    <small
                      v-if="fieldError('notes')"
                      class="warehouse-adjustment-field-error"
                    >
                      {{ fieldError('notes') }}
                    </small>

                    <small v-else>
                      A movement history record will
                      be created automatically.
                    </small>

                    <small>
                      {{ form.notes.length }}/2000
                    </small>
                  </div>
                </div>
              </div>

              <!-- Quantity Preview -->

              <section
                class="warehouse-adjustment-preview"
                :class="{
                  'warehouse-adjustment-preview-in':
                    !isDecrease,

                  'warehouse-adjustment-preview-out':
                    isDecrease,

                  'warehouse-adjustment-preview-invalid':
                    projectedQuantity < 0,
                }"
              >
                <div>
                  <span>
                    Before
                  </span>

                  <strong>
                    {{
                      formatQuantity(
                        currentQuantity,
                      )
                    }}
                  </strong>
                </div>

                <i
                  class="bi bi-arrow-right"
                  aria-hidden="true"
                ></i>

                <div>
                  <span>
                    Adjustment
                  </span>

                  <strong>
                    {{
                      isDecrease
                        ? '−'
                        : '+'
                    }}
                    {{
                      formatQuantity(
                        adjustmentQuantity,
                      )
                    }}
                  </strong>
                </div>

                <i
                  class="bi bi-arrow-right"
                  aria-hidden="true"
                ></i>

                <div>
                  <span>
                    After
                  </span>

                  <strong>
                    {{
                      projectedQuantity < 0
                        ? 'Invalid'
                        : formatQuantity(
                            projectedQuantity,
                          )
                    }}
                  </strong>
                </div>
              </section>

              <div
                v-if="
                  isDecrease &&
                  projectedQuantity < 0
                "
                class="warehouse-adjustment-negative-warning"
              >
                <i class="bi bi-shield-exclamation"></i>

                <span>
                  This adjustment would create negative
                  warehouse stock and cannot be submitted.
                </span>
              </div>
            </div>

            <!-- Footer -->

            <footer class="warehouse-adjustment-footer">
              <button
                type="button"
                class="warehouse-adjustment-cancel"
                :disabled="submitting"
                @click="requestClose"
              >
                Cancel
              </button>

              <button
                type="submit"
                class="warehouse-adjustment-submit"
                :class="{
                  'warehouse-adjustment-submit-out':
                    isDecrease,
                }"
                :disabled="
                  submitting ||
                  projectedQuantity < 0
                "
              >
                <span
                  v-if="submitting"
                  class="spinner-border spinner-border-sm"
                  aria-hidden="true"
                ></span>

                <i
                  v-else
                  class="bi"
                  :class="
                    isDecrease
                      ? 'bi-dash-circle'
                      : 'bi-plus-circle'
                  "
                ></i>

                <span>
                  {{
                    submitting
                      ? 'Saving Adjustment...'
                      : isDecrease
                        ? 'Decrease Stock'
                        : 'Increase Stock'
                  }}
                </span>
              </button>
            </footer>
          </form>
        </section>
      </div>
    </Transition>
  </Teleport>
</template>

<script setup>
import {
  computed,
  reactive,
  watch,
} from 'vue'

const props = defineProps({
  show: {
    type: Boolean,
    default: false,
  },

  stock: {
    type: Object,
    default: null,
  },

  submitting: {
    type: Boolean,
    default: false,
  },

  serverErrors: {
    type: Object,
    default: () => ({}),
  },

  errorMessage: {
    type: String,
    default: '',
  },
})

const emit = defineEmits([
  'close',
  'submit',
])

const form = reactive(
  createDefaultForm(),
)

const localErrors = reactive({})

const currentQuantity = computed(() => {
  const value =
    Number(
      props.stock?.quantity,
    )


  if (
    !Number.isFinite(value)
    ||
    value < 0
  ) {
    return 0
  }


  return roundQuantity(
    value,
  )
})

const adjustmentQuantity = computed(() => {
  return decimalValue(
    form.quantity,
  )
})

const isDecrease = computed(() => {
  return (
    form.adjustment_type ===
    'decrease'
  )
})

const stockUnit = computed(() => {
  return (
    props.stock?.unit
    ||
    props.stock
      ?.raw_material
      ?.base_unit
    ||
    'unit'
  )
})

const projectedQuantity = computed(() => {
  const result =
    isDecrease.value
      ? currentQuantity.value
        -
        adjustmentQuantity.value
      : currentQuantity.value
        +
        adjustmentQuantity.value

  return roundQuantity(
    result,
  )
})

function createDefaultForm() {
  return {
    adjustment_type: 'increase',
    quantity: '',
    unit_cost: '',
    notes: '',
  }
}

function resetForm() {
  clearLocalErrors()

  Object.assign(
    form,
    createDefaultForm(),
  )
}

const MAX_INVENTORY_DECIMAL =
  99999999999999.9999


function sanitizeDecimalInput(
  value,
  decimalPlaces = 4,
) {
  let normalized =
    String(
      value ?? '',
    )
      .replace(
        /,/g,
        '.',
      )
      .replace(
        /[^0-9.]/g,
        '',
      )


  const firstDotIndex =
    normalized.indexOf(
      '.',
    )


  if (
    firstDotIndex !== -1
  ) {
    normalized =
      normalized.slice(
        0,
        firstDotIndex + 1,
      )
      +
      normalized
        .slice(
          firstDotIndex + 1,
        )
        .replace(
          /\./g,
          '',
        )
  }


  const [
    integerPart = '',
    decimalPart,
  ] = normalized.split(
    '.',
  )


  const safeIntegerPart =
    integerPart
      .replace(
        /^0+(?=\d)/,
        '',
      )
      .slice(
        0,
        14,
      )


  if (
    decimalPart === undefined
  ) {
    return safeIntegerPart
  }


  return (
    `${safeIntegerPart || '0'}.`
    +
    decimalPart.slice(
      0,
      decimalPlaces,
    )
  )
}


function handleDecimalInput(
  event,
  field,
  decimalPlaces = 4,
) {
  const sanitizedValue =
    sanitizeDecimalInput(
      event?.target?.value,
      decimalPlaces,
    )


  form[field] =
    sanitizedValue


  if (
    event?.target
    &&
    event.target.value
      !==
      sanitizedValue
  ) {
    event.target.value =
      sanitizedValue
  }


  delete localErrors[field]
}


function decimalValue(
  value,
) {
  const numericValue =
    Number(
      value,
    )


  return Number.isFinite(
    numericValue,
  )
    ? numericValue
    : 0
}


function hasTooManyDecimals(
  value,
  maxDecimals = 4,
) {
  const normalized =
    String(
      value ?? '',
    )


  const decimalPart =
    normalized.split(
      '.',
    )[1]


  return (
    decimalPart !== undefined
    &&
    decimalPart.length
      >
      maxDecimals
  )
}


function validateForm() {
  clearLocalErrors()


  if (
    ![
      'increase',
      'decrease',
    ].includes(
      form.adjustment_type,
    )
  ) {
    localErrors.adjustment_type =
      'Please select a valid adjustment type.'
  }


  const quantity =
    decimalValue(
      form.quantity,
    )


  if (
    form.quantity === ''
    ||
    !Number.isFinite(
      Number(
        form.quantity,
      ),
    )
    ||
    quantity <= 0
  ) {
    localErrors.quantity =
      'Adjustment quantity must be greater than zero.'
  } else if (
    hasTooManyDecimals(
      form.quantity,
      4,
    )
  ) {
    localErrors.quantity =
      'Adjustment quantity can have a maximum of 4 decimal places.'
  } else if (
    quantity >
    MAX_INVENTORY_DECIMAL
  ) {
    localErrors.quantity =
      'Adjustment quantity is too large.'
  }


  if (
    isDecrease.value
    &&
    quantity >
    currentQuantity.value
  ) {
    localErrors.quantity =
      `Available warehouse quantity is ${formatQuantity(
        currentQuantity.value,
      )}.`
  }


  if (
    !isDecrease.value
    &&
    form.unit_cost !== ''
  ) {
    const unitCost =
      decimalValue(
        form.unit_cost,
      )


    if (
      !Number.isFinite(
        Number(
          form.unit_cost,
        ),
      )
      ||
      unitCost < 0
    ) {
      localErrors.unit_cost =
        'Unit cost cannot be negative.'
    } else if (
      hasTooManyDecimals(
        form.unit_cost,
        4,
      )
    ) {
      localErrors.unit_cost =
        'Unit cost can have a maximum of 4 decimal places.'
    } else if (
      unitCost >
      MAX_INVENTORY_DECIMAL
    ) {
      localErrors.unit_cost =
        'Unit cost is too large.'
    }
  }


  if (!form.notes.trim()) {
    localErrors.notes =
      'Adjustment reason is required.'
  }


  return (
    Object.keys(
      localErrors,
    ).length === 0
  )
}

function buildPayload() {
  const payload = {
    adjustment_type:
      form.adjustment_type,

    quantity:
      roundQuantity(
        adjustmentQuantity.value,
      ),

    notes:
      form.notes.trim(),
  }


  if (!isDecrease.value) {
    payload.unit_cost =
      form.unit_cost === ''
        ? null
        : roundQuantity(
            decimalValue(
              form.unit_cost,
            ),
          )
  }


  return payload
}

function submitForm() {
  if (
    props.submitting
    ||
    !validateForm()
  ) {
    return
  }

  emit(
    'submit',
    buildPayload(),
  )
}

function requestClose() {
  if (props.submitting) {
    return
  }

  emit('close')
}

function fieldError(
  field,
) {
  if (localErrors[field]) {
    return localErrors[field]
  }

  const serverError =
    props.serverErrors?.[field]

  if (Array.isArray(serverError)) {
    return serverError[0] || ''
  }

  return serverError
    ? String(serverError)
    : ''
}

function clearLocalErrors() {
  Object.keys(
    localErrors,
  ).forEach((key) => {
    delete localErrors[key]
  })
}

function roundQuantity(
  value,
) {
  return Math.round(
    (
      Number(value) +
      Number.EPSILON
    )
    *
    10000,
  ) / 10000
}

function formatQuantity(
  quantity,
) {
  const numericValue =
    Number(
      quantity,
    )


  const safeValue =
    Number.isFinite(
      numericValue,
    )
      ? numericValue
      : 0


  return `${safeValue.toLocaleString(
    'en-BD',
    {
      maximumFractionDigits: 4,
    },
  )} ${stockUnit.value}`
}

function formatMoney(
  amount,
) {
  const numericValue =
    Number(
      amount,
    )


  const safeValue =
    Number.isFinite(
      numericValue,
    )
      ? numericValue
      : 0


  return `৳ ${safeValue.toLocaleString(
    'en-BD',
    {
      minimumFractionDigits: 2,
      maximumFractionDigits: 4,
    },
  )}`
}

function formatStatus(
  status,
) {
  if (!status) {
    return 'Unknown'
  }

  return String(status)
    .replaceAll('_', ' ')
    .replace(
      /\b\w/g,
      (letter) =>
        letter.toUpperCase(),
    )
}

function statusClass(
  status,
) {
  return {
    'warehouse-adjustment-status-available':
      status === 'available',

    'warehouse-adjustment-status-limited':
      status === 'limited',

    'warehouse-adjustment-status-out':
      status ===
      'out_of_stock',
  }
}

watch(
  () => props.show,

  (show) => {
    if (show) {
      resetForm()
    }
  },

  {
    immediate: true,
  },
)

watch(
  () => form.adjustment_type,

  () => {
    delete localErrors.quantity
    delete localErrors.unit_cost

    if (isDecrease.value) {
      form.unit_cost = ''
    }
  },
)
</script>