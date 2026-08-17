<template>
  <Teleport to="body">
    <Transition name="inventory-modal">
      <div
        v-if="show"
        class="raw-material-action-backdrop"
        @click.self="requestClose"
      >
        <section
          ref="modalRef"
          class="raw-material-action-modal"
          role="dialog"
          aria-modal="true"
          aria-labelledby="raw-material-action-title"
          aria-describedby="raw-material-action-description"
        >
          <!-- Header -->

          <header class="raw-material-action-header">
            <div
              class="raw-material-action-icon"
              :class="actionIconClass"
            >
              <i
                class="bi"
                :class="actionIcon"
                aria-hidden="true"
              ></i>
            </div>


            <div class="raw-material-action-heading">
              <h2 id="raw-material-action-title">
                {{ actionTitle }}
              </h2>

              <p id="raw-material-action-description">
                {{ actionDescription }}
              </p>
            </div>


            <button
              ref="closeButtonRef"
              type="button"
              class="raw-material-action-close"
              aria-label="Close confirmation"
              :disabled="submitting"
              @click="requestClose"
            >
              <i
                class="bi bi-x-lg"
                aria-hidden="true"
              ></i>
            </button>
          </header>


          <!-- Body -->

          <div class="raw-material-action-body">
            <!-- API Error -->

            <div
              v-if="errorMessage"
              class="raw-material-action-error"
              role="alert"
            >
              <i
                class="bi bi-exclamation-circle-fill"
                aria-hidden="true"
              ></i>

              <span>
                {{ errorMessage }}
              </span>
            </div>


            <!-- Material Information -->

            <div class="raw-material-action-material">
              <div class="raw-material-action-material-icon">
                <i
                  class="bi bi-box-seam"
                  aria-hidden="true"
                ></i>
              </div>

              <div>
                <span>
                  Raw Material
                </span>

                <strong>
                  {{ materialName }}
                </strong>

                <small>
                  {{ materialCategory }}
                  ·
                  {{ materialUnit }}
                </small>
              </div>
            </div>


            <!-- Current State -->

            <div class="raw-material-action-details">
              <div>
                <span>
                  Current Quantity
                </span>

                <strong>
                  {{
                    stock?.quantity_formatted
                    ||
                    formatQuantity(
                      currentQuantity,
                      materialUnitValue,
                    )
                  }}
                </strong>
              </div>

              <div>
                <span>
                  Material Status
                </span>

                <strong
                  :class="materialStatusClass"
                >
                  {{ materialStatusLabel }}
                </strong>
              </div>

              <div>
                <span>
                  Warehouse Status
                </span>

                <strong>
                  {{
                    stock?.status_label
                    ||
                    formatStatus(
                      stock?.status,
                    )
                  }}
                </strong>
              </div>
            </div>


            <!-- Activate Information -->

            <div
              v-if="isActivate"
              class="raw-material-action-notice raw-material-action-notice-success"
            >
              <i
                class="bi bi-check-circle-fill"
                aria-hidden="true"
              ></i>

              <div>
                <strong>
                  Activate this material?
                </strong>

                <p v-if="canActivate">
                  After activation, warehouse adjustments
                  and other permitted inventory operations
                  will be available again.
                </p>

                <p v-else>
                  This material is not currently confirmed
                  as inactive. Refresh the warehouse stock
                  before trying to activate it.
                </p>
              </div>
            </div>


            <!-- Deactivate Information -->

            <div
              v-else-if="isDeactivate"
              class="raw-material-action-notice raw-material-action-notice-warning"
            >
              <i
                class="bi bi-exclamation-triangle-fill"
                aria-hidden="true"
              ></i>

              <div>
                <strong>
                  Deactivate this material?
                </strong>

                <p v-if="canAttemptDeactivate">
                  After deactivation, warehouse adjustments
                  and other inventory operations requiring
                  an active material will be blocked.
                  Final eligibility is verified by the server.
                </p>

                <p v-else-if="currentQuantity > 0">
                  Warehouse quantity must be zero before
                  this material can be deactivated.

                  Current quantity:
                  {{
                    stock?.quantity_formatted
                    ||
                    formatQuantity(
                      currentQuantity,
                      materialUnitValue,
                    )
                  }}.
                </p>

                <p v-else>
                  This material is not currently confirmed
                  as active. Refresh the warehouse stock
                  before trying to deactivate it.
                </p>
              </div>
            </div>


            <!-- Delete Information -->

            <template v-else-if="isDelete">
              <div
                v-if="canAttemptDelete"
                class="raw-material-action-notice raw-material-action-notice-danger"
              >
                <i
                  class="bi bi-trash3-fill"
                  aria-hidden="true"
                ></i>

                <div>
                  <strong>
                    Archive this raw material?
                  </strong>

                  <p>
                    The warehouse quantity is zero, so the
                    archive request can be submitted. The
                    server will still verify all remaining
                    inventory and transaction protections
                    before soft deleting the material.
                    Historical records remain preserved.
                  </p>
                </div>
              </div>

              <div
                v-else
                class="raw-material-delete-blocked"
              >
                <i
                  class="bi bi-shield-lock-fill"
                  aria-hidden="true"
                ></i>

                <div>
                  <strong>
                    This material cannot be archived yet
                  </strong>

                  <p v-if="currentQuantity > 0">
                    Warehouse quantity must be zero before
                    this material can be archived.

                    Current quantity:
                    {{
                      stock?.quantity_formatted
                      ||
                      formatQuantity(
                        currentQuantity,
                        materialUnitValue,
                      )
                    }}.
                  </p>

                  <p v-else>
                    Material information is incomplete.
                    Refresh the warehouse stock before
                    attempting to archive it.
                  </p>
                </div>
              </div>
            </template>


            <!-- Invalid Action -->

            <div
              v-else
              class="raw-material-delete-blocked"
              role="alert"
            >
              <i
                class="bi bi-shield-lock-fill"
                aria-hidden="true"
              ></i>

              <div>
                <strong>
                  Invalid material action
                </strong>

                <p>
                  Close this confirmation and try the
                  inventory action again.
                </p>
              </div>
            </div>
          </div>


          <!-- Footer -->

          <footer class="raw-material-action-footer">
            <button
              type="button"
              class="raw-material-action-cancel"
              :disabled="submitting"
              @click="requestClose"
            >
              Cancel
            </button>


            <button
              type="button"
              class="raw-material-action-confirm"
              :class="confirmButtonClass"
              :disabled="
                submitting
                ||
                !canConfirm
              "
              :aria-busy="submitting"
              @click="confirmAction"
            >
              <span
                v-if="submitting"
                class="spinner-border spinner-border-sm"
                aria-hidden="true"
              ></span>

              <i
                v-else
                class="bi"
                :class="actionIcon"
                aria-hidden="true"
              ></i>

              <span>
                {{
                  submitting
                    ? 'Processing...'
                    : confirmButtonText
                }}
              </span>
            </button>
          </footer>
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
  onMounted,
  ref,
  watch,
} from 'vue'


/*
|--------------------------------------------------------------------------
| Props / Emits
|--------------------------------------------------------------------------
*/


const props = defineProps({
  show: {
    type: Boolean,
    default: false,
  },


  actionType: {
    type: String,
    default: '',

    validator(value) {
      return [
        '',
        'activate',
        'deactivate',
        'delete',
      ].includes(value)
    },
  },


  stock: {
    type: Object,
    default: null,
  },


  submitting: {
    type: Boolean,
    default: false,
  },


  errorMessage: {
    type: String,
    default: '',
  },
})


const emit = defineEmits([
  'close',
  'confirm',
])


/*
|--------------------------------------------------------------------------
| Modal Focus State
|--------------------------------------------------------------------------
*/


const modalRef =
  ref(null)


const closeButtonRef =
  ref(null)


let previouslyFocusedElement =
  null


/*
|--------------------------------------------------------------------------
| Action Type
|--------------------------------------------------------------------------
*/


const isActivate = computed(() => {
  return props.actionType ===
    'activate'
})


const isDeactivate = computed(() => {
  return props.actionType ===
    'deactivate'
})


const isDelete = computed(() => {
  return props.actionType ===
    'delete'
})


const isValidAction = computed(() => {
  return (
    isActivate.value
    ||
    isDeactivate.value
    ||
    isDelete.value
  )
})


/*
|--------------------------------------------------------------------------
| Material Display
|--------------------------------------------------------------------------
*/


const materialName = computed(() => {
  return (
    props.stock?.material_name
    ||
    props.stock?.raw_material
      ?.material_name
    ||
    'Unknown Material'
  )
})


const materialCategory = computed(() => {
  return (
    props.stock?.category
    ||
    props.stock?.raw_material
      ?.category
    ||
    'No category'
  )
})


const materialUnitValue = computed(() => {
  return (
    props.stock?.unit
    ||
    props.stock?.base_unit
    ||
    props.stock?.raw_material
      ?.base_unit
    ||
    ''
  )
})


const materialUnit = computed(() => {
  return (
    materialUnitValue.value
    ||
    'No unit'
  )
})


/*
|--------------------------------------------------------------------------
| Material Status
|--------------------------------------------------------------------------
*/


const materialActiveState = computed(() => {
  if (
    typeof props.stock
      ?.raw_material
      ?.is_active ===
      'boolean'
  ) {
    return props.stock
      .raw_material
      .is_active
  }


  if (
    typeof props.stock?.is_active ===
      'boolean'
  ) {
    return props.stock.is_active
  }


  return null
})


const materialStatusLabel = computed(() => {
  if (
    materialActiveState.value ===
    true
  ) {
    return 'Active'
  }


  if (
    materialActiveState.value ===
    false
  ) {
    return 'Inactive'
  }


  return 'Unknown'
})


const materialStatusClass = computed(() => {
  if (
    materialActiveState.value ===
    true
  ) {
    return 'raw-material-current-active'
  }


  if (
    materialActiveState.value ===
    false
  ) {
    return 'raw-material-current-inactive'
  }


  return ''
})


/*
|--------------------------------------------------------------------------
| Current Quantity
|--------------------------------------------------------------------------
*/


const currentQuantity = computed(() => {
  const value =
    Number(
      props.stock?.quantity,
    )


  if (
    !Number.isFinite(value)
    ||
    value <= 0
  ) {
    return 0
  }


  return Math.round(
    (
      value
      +
      Number.EPSILON
    )
    *
    10000,
  ) / 10000
})


/*
|--------------------------------------------------------------------------
| Local Action Guards
|--------------------------------------------------------------------------
|
| These checks only use facts available in this modal. The backend remains
| authoritative for restaurant stock, purchase-order and other protections.
|
*/


const hasUsableStockRecord = computed(() => {
  return Boolean(
    props.stock
    &&
    (
      props.stock.raw_material_id
      ||
      props.stock.raw_material?.id
    ),
  )
})


const canActivate = computed(() => {
  return (
    hasUsableStockRecord.value
    &&
    materialActiveState.value ===
      false
  )
})


const canAttemptDeactivate = computed(() => {
  return (
    hasUsableStockRecord.value
    &&
    materialActiveState.value ===
      true
    &&
    currentQuantity.value <= 0
  )
})


const canAttemptDelete = computed(() => {
  return (
    hasUsableStockRecord.value
    &&
    currentQuantity.value <= 0
  )
})


const canConfirm = computed(() => {
  if (!isValidAction.value) {
    return false
  }


  if (isActivate.value) {
    return canActivate.value
  }


  if (isDeactivate.value) {
    return canAttemptDeactivate.value
  }


  return canAttemptDelete.value
})


/*
|--------------------------------------------------------------------------
| Action Presentation
|--------------------------------------------------------------------------
*/


const actionTitle = computed(() => {
  if (isDelete.value) {
    return 'Archive Raw Material'
  }


  if (isActivate.value) {
    return 'Activate Raw Material'
  }


  if (isDeactivate.value) {
    return 'Deactivate Raw Material'
  }


  return 'Raw Material Action'
})


const actionDescription = computed(() => {
  if (isDelete.value) {
    return 'Review the material before requesting archive.'
  }


  if (isActivate.value) {
    return 'Enable inventory operations for this material.'
  }


  if (isDeactivate.value) {
    return 'Temporarily disable inventory operations for this material.'
  }


  return 'The requested material action is invalid.'
})


const actionIcon = computed(() => {
  if (isDelete.value) {
    return 'bi-trash3'
  }


  if (isActivate.value) {
    return 'bi-check-circle'
  }


  if (isDeactivate.value) {
    return 'bi-pause-circle'
  }


  return 'bi-shield-exclamation'
})


const actionIconClass = computed(() => {
  return {
    'raw-material-action-icon-delete':
      isDelete.value,

    'raw-material-action-icon-activate':
      isActivate.value,

    'raw-material-action-icon-deactivate':
      isDeactivate.value,
  }
})


const confirmButtonClass = computed(() => {
  return {
    'raw-material-confirm-delete':
      isDelete.value,

    'raw-material-confirm-activate':
      isActivate.value,

    'raw-material-confirm-deactivate':
      isDeactivate.value,
  }
})


const confirmButtonText = computed(() => {
  if (isDelete.value) {
    return 'Archive Material'
  }


  if (isActivate.value) {
    return 'Activate Material'
  }


  if (isDeactivate.value) {
    return 'Deactivate Material'
  }


  return 'Confirm'
})


/*
|--------------------------------------------------------------------------
| Actions
|--------------------------------------------------------------------------
*/


function confirmAction() {
  if (
    props.submitting
    ||
    !canConfirm.value
  ) {
    return
  }


  emit(
    'confirm',
  )
}


function requestClose() {
  if (props.submitting) {
    return
  }


  emit(
    'close',
  )
}


/*
|--------------------------------------------------------------------------
| Keyboard / Focus
|--------------------------------------------------------------------------
*/


function handleDocumentKeydown(
  event,
) {
  if (
    !props.show
    ||
    props.submitting
  ) {
    return
  }


  if (event.key === 'Escape') {
    event.preventDefault()

    requestClose()
  }
}


watch(
  () => props.show,

  async (
    show,
  ) => {
    if (show) {
      previouslyFocusedElement =
        document.activeElement


      await nextTick()


      closeButtonRef.value
        ?.focus()

      return
    }


    const target =
      previouslyFocusedElement


    previouslyFocusedElement =
      null


    if (
      target
      &&
      typeof target.focus ===
        'function'
      &&
      document.contains(
        target,
      )
    ) {
      target.focus()
    }
  },
)


onMounted(() => {
  document.addEventListener(
    'keydown',
    handleDocumentKeydown,
  )
})


onBeforeUnmount(() => {
  document.removeEventListener(
    'keydown',
    handleDocumentKeydown,
  )
})


/*
|--------------------------------------------------------------------------
| Formatters
|--------------------------------------------------------------------------
*/


function formatQuantity(
  quantity,
  unit,
) {
  const numericValue =
    Number(
      quantity,
    )


  const value =
    Number.isFinite(
      numericValue,
    )
      ? numericValue
      : 0


  return `${value.toLocaleString(
    'en-BD',
    {
      maximumFractionDigits: 4,
    },
  )} ${unit || ''}`.trim()
}


function formatStatus(
  status,
) {
  if (!status) {
    return 'Unknown'
  }


  return String(status)
    .replaceAll(
      '_',
      ' ',
    )
    .replace(
      /\b\w/g,
      (letter) =>
        letter.toUpperCase(),
    )
}
</script>
