<template>
  <Teleport to="body">
    <Transition name="inventory-modal">
      <div
        v-if="show"
        class="raw-material-modal-backdrop"
        @click.self="requestClose"
      >
        <section
          ref="modalRef"
          class="raw-material-modal"
          role="dialog"
          aria-modal="true"
          :aria-labelledby="
            isEditMode
              ? 'edit-raw-material-title'
              : 'create-raw-material-title'
          "
        >
          <!-- Header -->

          <header class="raw-material-modal-header">
            <div class="raw-material-modal-heading">
              <div class="raw-material-modal-icon">
                <i
                  class="bi"
                  :class="
                    isEditMode
                      ? 'bi-pencil-square'
                      : 'bi-plus-circle'
                  "
                  aria-hidden="true"
                ></i>
              </div>

              <div>
                <h2
                  :id="
                    isEditMode
                      ? 'edit-raw-material-title'
                      : 'create-raw-material-title'
                  "
                >
                  {{
                    isEditMode
                      ? 'Edit Raw Material'
                      : 'Add Raw Material'
                  }}
                </h2>

                <p>
                  {{
                    isEditMode
                      ? 'Update material information and minimum stock levels.'
                      : 'Create a new material with optional opening warehouse stock.'
                  }}
                </p>
              </div>
            </div>

            <button
              ref="closeButtonRef"
              type="button"
              class="raw-material-modal-close"
              aria-label="Close modal"
              :disabled="submitting"
              @click="requestClose"
            >
              <i class="bi bi-x-lg" aria-hidden="true"></i>
            </button>
          </header>

          <!-- Form -->

          <form
            novalidate
            @submit.prevent="submitForm"
          >
            <div class="raw-material-modal-body">
              <!-- General Error -->

              <div
                v-if="errorMessage"
                class="raw-material-form-error-message"
                role="alert"
              >
                <i class="bi bi-exclamation-circle" aria-hidden="true"></i>

                <span>
                  {{ errorMessage }}
                </span>
              </div>

              <!-- Material Information -->

              <section class="raw-material-form-section">
                <div class="raw-material-form-section-heading">
                  <div>
                    <h3>
                      Material Information
                    </h3>

                    <p>
                      Enter the raw material name,
                      category and storage unit.
                    </p>
                  </div>

                  <i class="bi bi-box-seam" aria-hidden="true"></i>
                </div>

                <div class="raw-material-form-grid">
                  <!-- Material Name -->

                  <div class="raw-material-form-group raw-material-field-wide">
                    <label for="raw-material-name">
                      Material Name
                      <span>*</span>
                    </label>

                    <input
                      id="raw-material-name"
                      ref="nameInputRef"
                      v-model.trim="form.material_name"
                      type="text"
                      autocomplete="off"
                      maxlength="180"
                      placeholder="Example: Chicken Breast"
                      :disabled="submitting"
                      :class="{
                        'raw-material-input-error':
                          fieldError(
                            'material_name',
                          ),
                      }"
                    />

                    <small
                      v-if="
                        fieldError(
                          'material_name',
                        )
                      "
                      class="raw-material-field-error"
                    >
                      {{
                        fieldError(
                          'material_name',
                        )
                      }}
                    </small>
                  </div>

                  <!-- Category -->

                  <div class="raw-material-form-group">
                    <label for="raw-material-category">
                      Category
                    </label>

                    <input
                      id="raw-material-category"
                      v-model.trim="form.category"
                      type="text"
                      maxlength="100"
                      autocomplete="off"
                      placeholder="Example: Meat"
                      list="raw-material-category-options"
                      :disabled="submitting"
                      :class="{
                        'raw-material-input-error':
                          fieldError(
                            'category',
                          ),
                      }"
                    />

                    <datalist id="raw-material-category-options">
                      <option
                        v-for="category in categoryOptions"
                        :key="category"
                        :value="category"
                      ></option>
                    </datalist>

                    <small
                      v-if="
                        fieldError(
                          'category',
                        )
                      "
                      class="raw-material-field-error"
                    >
                      {{
                        fieldError(
                          'category',
                        )
                      }}
                    </small>
                  </div>

                  <!-- Base Unit -->

                  <div class="raw-material-form-group">
                    <label for="raw-material-unit">
                      Base Unit
                      <span>*</span>
                    </label>

                    <select
                      id="raw-material-unit"
                      v-model="form.base_unit"
                      :disabled="submitting"
                      :class="{
                        'raw-material-input-error':
                          fieldError(
                            'base_unit',
                          ),
                      }"
                    >
                      <option value="">
                        Select a unit
                      </option>

                      <option
                        v-for="unit in unitOptions"
                        :key="unit.value"
                        :value="unit.value"
                      >
                        {{ unit.label }}
                      </option>
                    </select>

                    <small
                      v-if="
                        isEditMode &&
                        !fieldError(
                          'base_unit',
                        )
                      "
                      class="raw-material-field-help"
                    >
                      Unit changes may be blocked when
                      stock or transaction history exists.
                    </small>

                    <small
                      v-if="
                        fieldError(
                          'base_unit',
                        )
                      "
                      class="raw-material-field-error"
                    >
                      {{
                        fieldError(
                          'base_unit',
                        )
                      }}
                    </small>
                  </div>

                  <!-- Active Status -->

                  <div class="raw-material-form-group raw-material-active-group">
                    <label>
                      Material Status
                    </label>

                    <label
                      class="raw-material-status-switch"
                      for="raw-material-active"
                    >
                      <input
                        id="raw-material-active"
                        v-model="form.is_active"
                        type="checkbox"
                        :disabled="
                          submitting
                          ||
                          isEditMode
                        "
                      />

                      <span class="raw-material-switch-track">
                        <span class="raw-material-switch-thumb"></span>
                      </span>

                      <strong>
                        {{
                          form.is_active
                            ? 'Active'
                            : 'Inactive'
                        }}
                      </strong>
                    </label>

                    <small class="raw-material-field-help">
                      {{
                        isEditMode
                          ? 'Use the Activate / Disable action in the warehouse table to change material status.'
                          : 'Inactive materials cannot receive warehouse adjustments.'
                      }}
                    </small>

                    <small
                      v-if="fieldError('is_active')"
                      class="raw-material-field-error"
                    >
                      {{ fieldError('is_active') }}
                    </small>
                  </div>
                </div>
              </section>

              <!-- Minimum Levels -->

              <section class="raw-material-form-section">
                <div class="raw-material-form-section-heading">
                  <div>
                    <h3>
                      Minimum Stock Levels
                    </h3>

                    <p>
                      These values control automatic
                      low-stock alerts.
                    </p>
                  </div>

                  <i class="bi bi-exclamation-triangle" aria-hidden="true"></i>
                </div>

                <div class="raw-material-form-grid">
                  <!-- Warehouse Minimum -->

                  <div class="raw-material-form-group">
                    <label for="warehouse-minimum-quantity">
                      Warehouse Minimum
                      <span>*</span>
                    </label>

                    <div class="raw-material-number-input">
                      <input
                        id="warehouse-minimum-quantity"
                        v-model="form.warehouse_minimum_quantity"
                        type="text"
                        inputmode="decimal"
                        autocomplete="off"
                        placeholder="0"
                        :disabled="submitting"
                        :class="{
                          'raw-material-input-error':
                            fieldError(
                              'warehouse_minimum_quantity',
                            ),
                        }"
                        @input="
                          handleDecimalInput(
                            $event,
                            'warehouse_minimum_quantity',
                            4,
                          )
                        "
                      />

                      <span>
                        {{
                          form.base_unit ||
                          'unit'
                        }}
                      </span>
                    </div>

                    <small
                      v-if="
                        fieldError(
                          'warehouse_minimum_quantity',
                        )
                      "
                      class="raw-material-field-error"
                    >
                      {{
                        fieldError(
                          'warehouse_minimum_quantity',
                        )
                      }}
                    </small>
                  </div>

                  <!-- Restaurant Minimum -->

                  <div class="raw-material-form-group">
                    <label for="restaurant-minimum-quantity">
                      Restaurant Minimum
                      <span>*</span>
                    </label>

                    <div class="raw-material-number-input">
                      <input
                        id="restaurant-minimum-quantity"
                        v-model="form.restaurant_minimum_quantity"
                        type="text"
                        inputmode="decimal"
                        autocomplete="off"
                        placeholder="0"
                        :disabled="submitting"
                        :class="{
                          'raw-material-input-error':
                            fieldError(
                              'restaurant_minimum_quantity',
                            ),
                        }"
                        @input="
                          handleDecimalInput(
                            $event,
                            'restaurant_minimum_quantity',
                            4,
                          )
                        "
                      />

                      <span>
                        {{
                          form.base_unit ||
                          'unit'
                        }}
                      </span>
                    </div>

                    <small
                      v-if="
                        fieldError(
                          'restaurant_minimum_quantity',
                        )
                      "
                      class="raw-material-field-error"
                    >
                      {{
                        fieldError(
                          'restaurant_minimum_quantity',
                        )
                      }}
                    </small>
                  </div>
                </div>
              </section>

              <!-- Opening Stock -->

              <section
                v-if="!isEditMode"
                class="raw-material-form-section raw-material-opening-section"
              >
                <div class="raw-material-form-section-heading">
                  <div>
                    <h3>
                      Opening Warehouse Stock
                    </h3>

                    <p>
                      Optional initial quantity and
                      unit cost for this material.
                    </p>
                  </div>

                  <i class="bi bi-box-arrow-in-down" aria-hidden="true"></i>
                </div>

                <div class="raw-material-form-grid">
                  <!-- Opening Quantity -->

                  <div class="raw-material-form-group">
                    <label for="opening-quantity">
                      Opening Quantity
                    </label>

                    <div class="raw-material-number-input">
                      <input
                        id="opening-quantity"
                        v-model="form.opening_quantity"
                        type="text"
                        inputmode="decimal"
                        autocomplete="off"
                        placeholder="0"
                        :disabled="submitting"
                        :class="{
                          'raw-material-input-error':
                            fieldError(
                              'opening_quantity',
                            ),
                        }"
                        @input="
                          handleDecimalInput(
                            $event,
                            'opening_quantity',
                            4,
                          )
                        "
                      />

                      <span>
                        {{
                          form.base_unit ||
                          'unit'
                        }}
                      </span>
                    </div>

                    <small
                      v-if="
                        fieldError(
                          'opening_quantity',
                        )
                      "
                      class="raw-material-field-error"
                    >
                      {{
                        fieldError(
                          'opening_quantity',
                        )
                      }}
                    </small>
                  </div>

                  <!-- Opening Unit Cost -->

                  <div class="raw-material-form-group">
                    <label for="opening-unit-cost">
                      Opening Unit Cost
                    </label>

                    <div class="raw-material-number-input raw-material-money-input">
                      <span>
                        ৳
                      </span>

                      <input
                        id="opening-unit-cost"
                        v-model="form.opening_unit_cost"
                        type="text"
                        inputmode="decimal"
                        autocomplete="off"
                        placeholder="0.0000"
                        :disabled="submitting"
                        :class="{
                          'raw-material-input-error':
                            fieldError(
                              'opening_unit_cost',
                            ),
                        }"
                        @input="
                          handleDecimalInput(
                            $event,
                            'opening_unit_cost',
                            4,
                          )
                        "
                      />
                    </div>

                    <small
                      v-if="
                        fieldError(
                          'opening_unit_cost',
                        )
                      "
                      class="raw-material-field-error"
                    >
                      {{
                        fieldError(
                          'opening_unit_cost',
                        )
                      }}
                    </small>
                  </div>

                  <!-- Opening Notes -->

                  <div class="raw-material-form-group raw-material-field-full">
                    <label for="opening-stock-notes">
                      Opening Stock Notes
                    </label>

                    <textarea
                      id="opening-stock-notes"
                      v-model.trim="form.opening_stock_notes"
                      rows="3"
                      maxlength="2000"
                      placeholder="Reason or reference for opening stock"
                      :disabled="submitting"
                      :class="{
                        'raw-material-input-error':
                          fieldError(
                            'opening_stock_notes',
                          ),
                      }"
                    ></textarea>

                    <small
                      v-if="
                        fieldError(
                          'opening_stock_notes',
                        )
                      "
                      class="raw-material-field-error"
                    >
                      {{
                        fieldError(
                          'opening_stock_notes',
                        )
                      }}
                    </small>
                  </div>
                </div>
              </section>
            </div>

            <!-- Footer -->

            <footer class="raw-material-modal-footer">
              <button
                type="button"
                class="raw-material-cancel-button"
                :disabled="submitting"
                @click="requestClose"
              >
                Cancel
              </button>

              <button
                type="submit"
                class="raw-material-submit-button"
                :disabled="submitting"
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
                    isEditMode
                      ? 'bi-check-circle'
                      : 'bi-plus-circle'
                  "
                  aria-hidden="true"
                ></i>

                <span>
                  {{
                    submitting
                      ? 'Saving...'
                      : isEditMode
                        ? 'Update Material'
                        : 'Create Material'
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
  nextTick,
  onBeforeUnmount,
  reactive,
  ref,
  watch,
} from 'vue'

const props = defineProps({
  show: {
    type: Boolean,
    default: false,
  },

  material: {
    type: Object,
    default: null,
  },

  options: {
    type: Object,
    default: () => ({
      categories: [],
      units: [],
    }),
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

const modalRef =
  ref(null)

const closeButtonRef =
  ref(null)

const nameInputRef =
  ref(null)

let previouslyFocusedElement =
  null

const form = reactive(
  createDefaultForm(),
)

const localErrors = reactive({})

const isEditMode = computed(() => {
  return Boolean(
    props.material?.id,
  )
})

const categoryOptions = computed(() => {
  return Array.isArray(
    props.options?.categories,
  )
    ? props.options.categories
    : []
})

const unitOptions = computed(() => {
  return Array.isArray(
    props.options?.units,
  )
    ? props.options.units
    : []
})

function createDefaultForm() {
  return {
    material_name: '',
    category: '',
    base_unit: '',
    warehouse_minimum_quantity: '0',
    restaurant_minimum_quantity: '0',
    opening_quantity: '0',
    opening_unit_cost: '0',
    opening_stock_notes: '',
    is_active: true,
  }
}

function resetForm() {
  clearLocalErrors()

  Object.assign(
    form,
    createDefaultForm(),
  )

  if (!props.material) {
    return
  }

  Object.assign(form, {
    material_name:
      String(
        props.material.material_name
        ??
        '',
      ),

    category:
      String(
        props.material.category
        ??
        '',
      ),

    base_unit:
      String(
        props.material.base_unit
        ??
        '',
      ),

    warehouse_minimum_quantity:
      decimalInputValue(
        props.material
          .warehouse_minimum_quantity,
      ),

    restaurant_minimum_quantity:
      decimalInputValue(
        props.material
          .restaurant_minimum_quantity,
      ),

    is_active:
      resolveMaterialActiveState(
        props.material,
      ),
  })
}

function clearLocalErrors() {
  Object.keys(
    localErrors,
  ).forEach((key) => {
    delete localErrors[key]
  })
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

const MAX_INVENTORY_DECIMAL =
  99999999999999.9999

function resolveMaterialActiveState(material) {
  if (typeof material?.is_active === 'boolean') {
    return material.is_active
  }

  if (
    typeof material?.raw_material?.is_active
      === 'boolean'
  ) {
    return material.raw_material.is_active
  }

  return false
}

function decimalInputValue(value) {
  const number = Number(value)

  if (!Number.isFinite(number) || number < 0) {
    return '0'
  }

  return String(
    Math.round(
      (number + Number.EPSILON) * 10000,
    ) / 10000,
  )
}

function sanitizeDecimalInput(
  value,
  decimalPlaces = 4,
) {
  let normalized =
    String(value ?? '')
      .replace(/,/g, '.')
      .replace(/[^0-9.]/g, '')

  const dot = normalized.indexOf('.')

  if (dot !== -1) {
    normalized =
      normalized.slice(0, dot + 1)
      +
      normalized
        .slice(dot + 1)
        .replace(/\./g, '')
  }

  const [integerPart = '', decimalPart] =
    normalized.split('.')

  const safeInteger =
    integerPart
      .replace(/^0+(?=\d)/, '')
      .slice(0, 14)

  if (decimalPart === undefined) {
    return safeInteger
  }

  return (
    `${safeInteger || '0'}.`
    +
    decimalPart.slice(0, decimalPlaces)
  )
}

function handleDecimalInput(
  event,
  field,
  decimalPlaces = 4,
) {
  const value =
    sanitizeDecimalInput(
      event?.target?.value,
      decimalPlaces,
    )

  form[field] = value

  if (
    event?.target
    &&
    event.target.value !== value
  ) {
    event.target.value = value
  }

  delete localErrors[field]
}

function decimalValue(value) {
  const raw = String(value ?? '').trim()

  if (raw === '') {
    return null
  }

  const number = Number(raw)

  return Number.isFinite(number)
    ? number
    : null
}

function validateRequiredDecimal(
  field,
  label,
) {
  const number = decimalValue(form[field])

  if (number === null) {
    localErrors[field] =
      `${label} is required.`
    return null
  }

  if (number < 0) {
    localErrors[field] =
      `${label} cannot be negative.`
    return null
  }

  if (number > MAX_INVENTORY_DECIMAL) {
    localErrors[field] =
      `${label} is too large.`
    return null
  }

  return number
}

function validateOptionalDecimal(
  field,
  label,
) {
  const raw =
    String(form[field] ?? '').trim()

  if (raw === '') {
    return 0
  }

  const number = decimalValue(raw)

  if (number === null) {
    localErrors[field] =
      `${label} must be numeric.`
    return null
  }

  if (number < 0) {
    localErrors[field] =
      `${label} cannot be negative.`
    return null
  }

  if (number > MAX_INVENTORY_DECIMAL) {
    localErrors[field] =
      `${label} is too large.`
    return null
  }

  return number
}

function roundInventoryDecimal(value) {
  return Math.round(
    (Number(value) + Number.EPSILON) * 10000,
  ) / 10000
}

function validateForm() {
  clearLocalErrors()

  const materialName =
    form.material_name.trim()

  if (!materialName) {
    localErrors.material_name =
      'Raw material name is required.'
  } else if (materialName.length > 180) {
    localErrors.material_name =
      'Raw material name cannot exceed 180 characters.'
  }

  const category =
    form.category.trim()

  if (category.length > 100) {
    localErrors.category =
      'Category cannot exceed 100 characters.'
  }

  if (!form.base_unit) {
    localErrors.base_unit =
      'Base unit is required.'
  } else if (
    unitOptions.value.length > 0
    &&
    !unitOptions.value.some(
      (unit) =>
        String(unit?.value)
        ===
        String(form.base_unit),
    )
  ) {
    localErrors.base_unit =
      'Please select a valid base unit.'
  }

  validateRequiredDecimal(
    'warehouse_minimum_quantity',
    'Warehouse minimum quantity',
  )

  validateRequiredDecimal(
    'restaurant_minimum_quantity',
    'Restaurant minimum quantity',
  )

  if (!isEditMode.value) {
    const openingQuantity =
      validateOptionalDecimal(
        'opening_quantity',
        'Opening quantity',
      )

    const openingUnitCost =
      validateOptionalDecimal(
        'opening_unit_cost',
        'Opening unit cost',
      )

    if (
      openingQuantity !== null
      &&
      openingUnitCost !== null
      &&
      openingQuantity <= 0
      &&
      openingUnitCost > 0
    ) {
      localErrors.opening_unit_cost =
        'Opening unit cost must be zero when opening quantity is zero.'
    }
  }

  return (
    Object.keys(localErrors).length === 0
  )
}

function buildPayload() {
  const payload = {
    material_name:
      form.material_name.trim(),

    category:
      form.category.trim()
        ||
        null,

    base_unit:
      form.base_unit,

    warehouse_minimum_quantity:
      roundInventoryDecimal(
        decimalValue(
          form.warehouse_minimum_quantity,
        )
        ??
        0,
      ),

    restaurant_minimum_quantity:
      roundInventoryDecimal(
        decimalValue(
          form.restaurant_minimum_quantity,
        )
        ??
        0,
      ),
  }

  if (!isEditMode.value) {
    const openingQuantity =
      roundInventoryDecimal(
        decimalValue(
          form.opening_quantity,
        )
        ??
        0,
      )

    payload.opening_quantity =
      openingQuantity

    payload.opening_unit_cost =
      openingQuantity > 0
        ? roundInventoryDecimal(
            decimalValue(
              form.opening_unit_cost,
            )
            ??
            0,
          )
        : 0

    payload.opening_stock_notes =
      form.opening_stock_notes
        .trim()
        ||
        null

    payload.is_active =
      Boolean(form.is_active)
  }

  return payload
}

function submitForm() {
  if (
    props.submitting ||
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

function handleDocumentKeydown(event) {
  if (!props.show || props.submitting) {
    return
  }

  if (event.key === 'Escape') {
    event.preventDefault()
    requestClose()
  }
}

watch(
  () => [
    props.show,
    props.material,
  ],

  async (
    [show],
    previousValues = [],
  ) => {
    const previousShow =
      previousValues?.[0]

    if (show) {
      if (!previousShow) {
        previouslyFocusedElement =
          document.activeElement
      }

      resetForm()

      await nextTick()

      nameInputRef.value?.focus()
      return
    }

    if (previousShow) {
      const target =
        previouslyFocusedElement

      previouslyFocusedElement = null

      if (
        target
        &&
        typeof target.focus === 'function'
        &&
        document.contains(target)
      ) {
        target.focus()
      }
    }
  },

  {
    immediate: true,
  },
)

document.addEventListener(
  'keydown',
  handleDocumentKeydown,
)

onBeforeUnmount(() => {
  document.removeEventListener(
    'keydown',
    handleDocumentKeydown,
  )
})

</script>
