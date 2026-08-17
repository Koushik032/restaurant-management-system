<template>
  <Teleport to="body">
    <Transition name="inventory-modal">
      <div
        v-if="show"
        class="movement-details-backdrop"
        @click.self="requestClose"
      >
        <section
          ref="modalRef"
          class="movement-details-modal"
          role="dialog"
          aria-modal="true"
          aria-labelledby="movement-details-title"
          aria-describedby="movement-details-description"
          @keydown="handleModalKeydown"
        >
          <!-- Header -->

          <header class="movement-details-header">
            <div
              class="movement-details-header-icon"
              :class="{
                'movement-details-header-in':
                  direction === 'in',

                'movement-details-header-out':
                  direction === 'out',
              }"
            >
              <i
                class="bi"
                :class="directionIcon"
                aria-hidden="true"
              ></i>
            </div>


            <div>
              <h2 id="movement-details-title">
                Stock Movement Details
              </h2>

              <p id="movement-details-description">
                Complete stock movement and
                audit information.
              </p>
            </div>


            <button
              ref="closeButtonRef"
              type="button"
              aria-label="Close movement details"
              @click="requestClose"
            >
              <i
                class="bi bi-x-lg"
                aria-hidden="true"
              ></i>
            </button>
          </header>


          <!-- Body -->

          <div class="movement-details-body">
            <!-- Missing Movement -->

            <div
              v-if="!hasMovement"
              class="movement-details-notes"
              role="status"
            >
              <span>
                Movement Information
              </span>

              <p>
                Stock movement details are not available.
              </p>
            </div>


            <template v-else>
              <!-- Material -->

              <section class="movement-details-material">
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
                    {{ movementUnitLabel }}
                  </small>
                </div>


                <span
                  class="movement-details-type"
                  :class="{
                    'movement-details-type-in':
                      direction === 'in',

                    'movement-details-type-out':
                      direction === 'out',
                  }"
                >
                  {{ movementTypeLabel }}
                </span>
              </section>


              <!-- Quantity Flow -->

              <section class="movement-details-flow">
                <div>
                  <span>
                    Quantity Before
                  </span>

                  <strong>
                    {{ quantityBeforeDisplay }}
                  </strong>
                </div>


                <i
                  class="bi bi-arrow-right"
                  aria-hidden="true"
                ></i>


                <div>
                  <span>
                    Movement
                  </span>

                  <strong
                    :class="movementValueClass"
                  >
                    <template v-if="movementSign">
                      {{ movementSign }}
                    </template>

                    {{ movementQuantityDisplay }}
                  </strong>
                </div>


                <i
                  class="bi bi-arrow-right"
                  aria-hidden="true"
                ></i>


                <div>
                  <span>
                    Quantity After
                  </span>

                  <strong>
                    {{ quantityAfterDisplay }}
                  </strong>
                </div>
              </section>


              <!-- Audit Grid -->

              <section class="movement-details-grid">
                <div>
                  <span>
                    Movement ID
                  </span>

                  <strong>
                    {{ movementIdDisplay }}
                  </strong>
                </div>


                <div>
                  <span>
                    Location
                  </span>

                  <strong>
                    {{ locationDisplay }}
                  </strong>
                </div>


                <div>
                  <span>
                    Direction
                  </span>

                  <strong>
                    {{ directionLabel }}
                  </strong>
                </div>


                <div>
                  <span>
                    Unit Cost
                  </span>

                  <strong>
                    {{ unitCostDisplay }}
                  </strong>
                </div>


                <div>
                  <span>
                    Reference Type
                  </span>

                  <strong>
                    {{
                      shortReferenceType(
                        movement?.reference_type,
                      )
                    }}
                  </strong>
                </div>


                <div>
                  <span>
                    Reference ID
                  </span>

                  <strong>
                    {{ referenceIdDisplay }}
                  </strong>
                </div>


                <div>
                  <span>
                    Created By
                  </span>

                  <strong>
                    {{ createdByDisplay }}
                  </strong>
                </div>


                <div>
                  <span>
                    Created At
                  </span>

                  <strong>
                    {{ createdAtDisplay }}
                  </strong>
                </div>
              </section>


              <!-- Notes -->

              <section class="movement-details-notes">
                <span>
                  Movement Notes
                </span>

                <p>
                  {{ movementNotes }}
                </p>
              </section>
            </template>
          </div>


          <!-- Footer -->

          <footer class="movement-details-footer">
            <button
              ref="footerCloseButtonRef"
              type="button"
              @click="requestClose"
            >
              Close
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


  movement: {
    type: Object,
    default: null,
  },
})


const emit = defineEmits([
  'close',
])


/*
|--------------------------------------------------------------------------
| Modal Focus
|--------------------------------------------------------------------------
*/


const modalRef =
  ref(null)


const closeButtonRef =
  ref(null)


const footerCloseButtonRef =
  ref(null)


let previouslyFocusedElement =
  null


/*
|--------------------------------------------------------------------------
| Movement State
|--------------------------------------------------------------------------
*/


const hasMovement = computed(() => {
  return Boolean(
    props.movement
    &&
    typeof props.movement ===
      'object',
  )
})


const direction = computed(() => {
  const value =
    props.movement?.direction


  return [
    'in',
    'out',
  ].includes(value)
    ? value
    : null
})


const directionIcon = computed(() => {
  if (direction.value === 'out') {
    return 'bi-dash-circle'
  }


  if (direction.value === 'in') {
    return 'bi-plus-circle'
  }


  return 'bi-question-circle'
})


const directionLabel = computed(() => {
  if (direction.value === 'out') {
    return 'Stock Out'
  }


  if (direction.value === 'in') {
    return 'Stock In'
  }


  return 'Not available'
})


const movementSign = computed(() => {
  if (direction.value === 'out') {
    return '−'
  }


  if (direction.value === 'in') {
    return '+'
  }


  return ''
})


const movementValueClass = computed(() => {
  return {
    'movement-details-out-value':
      direction.value === 'out',

    'movement-details-in-value':
      direction.value === 'in',
  }
})


/*
|--------------------------------------------------------------------------
| Material
|--------------------------------------------------------------------------
*/


const materialName = computed(() => {
  return (
    props.movement?.material_name
    ||
    props.movement?.raw_material
      ?.material_name
    ||
    'Unknown Material'
  )
})


const materialCategory = computed(() => {
  return (
    props.movement?.raw_material
      ?.category
    ||
    props.movement?.category
    ||
    'No category'
  )
})


const movementUnit = computed(() => {
  return (
    props.movement?.unit
    ||
    props.movement?.raw_material
      ?.base_unit
    ||
    ''
  )
})


const movementUnitLabel = computed(() => {
  return (
    props.movement?.unit_label
    ||
    movementUnit.value
    ||
    'No unit'
  )
})


const movementTypeLabel = computed(() => {
  return (
    props.movement
      ?.movement_type_label
    ||
    formatLabel(
      props.movement
        ?.movement_type,
    )
  )
})


/*
|--------------------------------------------------------------------------
| Quantity
|--------------------------------------------------------------------------
*/


const quantityBeforeDisplay = computed(() => {
  return (
    props.movement
      ?.quantity_before_formatted
    ||
    formatQuantity(
      props.movement
        ?.quantity_before,
      movementUnit.value,
    )
  )
})


const movementQuantityDisplay = computed(() => {
  return (
    props.movement
      ?.quantity_formatted
    ||
    formatQuantity(
      props.movement?.quantity,
      movementUnit.value,
    )
  )
})


const quantityAfterDisplay = computed(() => {
  return (
    props.movement
      ?.quantity_after_formatted
    ||
    formatQuantity(
      props.movement
        ?.quantity_after,
      movementUnit.value,
    )
  )
})


/*
|--------------------------------------------------------------------------
| Audit Fields
|--------------------------------------------------------------------------
*/


const movementIdDisplay = computed(() => {
  const id =
    props.movement?.id


  if (
    id === null
    ||
    id === undefined
    ||
    id === ''
  ) {
    return '—'
  }


  return `#${id}`
})


const locationDisplay = computed(() => {
  return (
    props.movement?.location_label
    ||
    formatLabel(
      props.movement?.location,
    )
  )
})


const unitCostDisplay = computed(() => {
  const value =
    props.movement?.unit_cost


  if (
    value === null
    ||
    value === undefined
    ||
    value === ''
  ) {
    return 'Not provided'
  }


  const formatted =
    props.movement
      ?.unit_cost_formatted


  if (
    typeof formatted ===
      'string'
    &&
    formatted.trim() !== ''
  ) {
    return formatted
  }


  return formatMoney(
    value,
  )
})


const referenceIdDisplay = computed(() => {
  const value =
    props.movement?.reference_id


  if (
    value === null
    ||
    value === undefined
    ||
    value === ''
  ) {
    return 'Not linked'
  }


  return String(value)
})


const createdByDisplay = computed(() => {
  return (
    props.movement?.created_by
      ?.name
    ||
    props.movement?.creator
      ?.name
    ||
    props.movement
      ?.created_by_name
    ||
    'System'
  )
})


const createdAtDisplay = computed(() => {
  return (
    props.movement
      ?.created_at_label
    ||
    formatDate(
      props.movement
        ?.created_at,
    )
  )
})


const movementNotes = computed(() => {
  const notes =
    props.movement?.notes


  if (
    notes === null
    ||
    notes === undefined
  ) {
    return 'No movement notes were provided.'
  }


  const value =
    String(notes).trim()


  return (
    value
    ||
    'No movement notes were provided.'
  )
})


/*
|--------------------------------------------------------------------------
| Close
|--------------------------------------------------------------------------
*/


function requestClose() {
  emit(
    'close',
  )
}


/*
|--------------------------------------------------------------------------
| Keyboard / Focus
|--------------------------------------------------------------------------
*/


function focusableElements() {
  const modal =
    modalRef.value


  if (!modal) {
    return []
  }


  return Array.from(
    modal.querySelectorAll(
      [
        'button:not([disabled])',
        '[href]',
        'input:not([disabled])',
        'select:not([disabled])',
        'textarea:not([disabled])',
        '[tabindex]:not([tabindex="-1"])',
      ].join(','),
    ),
  )
}


function handleModalKeydown(
  event,
) {
  if (!props.show) {
    return
  }


  if (event.key === 'Escape') {
    event.preventDefault()

    requestClose()

    return
  }


  if (event.key !== 'Tab') {
    return
  }


  const focusable =
    focusableElements()


  if (focusable.length === 0) {
    event.preventDefault()

    modalRef.value
      ?.focus?.()

    return
  }


  const first =
    focusable[0]


  const last =
    focusable[
      focusable.length - 1
    ]


  if (
    event.shiftKey
    &&
    document.activeElement ===
      first
  ) {
    event.preventDefault()

    last.focus()

    return
  }


  if (
    !event.shiftKey
    &&
    document.activeElement ===
      last
  ) {
    event.preventDefault()

    first.focus()
  }
}


watch(
  () => props.show,

  async (
    show,
    previousShow,
  ) => {
    if (show) {
      previouslyFocusedElement =
        document.activeElement


      await nextTick()


      closeButtonRef.value
        ?.focus()

      return
    }


    if (!previousShow) {
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


onBeforeUnmount(() => {
  previouslyFocusedElement =
    null
})


/*
|--------------------------------------------------------------------------
| Formatters
|--------------------------------------------------------------------------
*/


function formatQuantity(
  quantity,
  unit = '',
) {
  const number =
    Number(
      quantity,
    )


  if (
    !Number.isFinite(
      number,
    )
  ) {
    return (
      unit
        ? `Not available ${unit}`
        : 'Not available'
    )
  }


  return `${number.toLocaleString(
    'en-BD',
    {
      maximumFractionDigits: 4,
    },
  )} ${unit || ''}`.trim()
}


function formatMoney(
  amount,
) {
  const number =
    Number(
      amount,
    )


  if (
    !Number.isFinite(
      number,
    )
  ) {
    return 'Not available'
  }


  return `৳ ${number.toLocaleString(
    'en-BD',
    {
      minimumFractionDigits: 2,
      maximumFractionDigits: 4,
    },
  )}`
}


function formatLabel(
  value,
) {
  if (
    value === null
    ||
    value === undefined
    ||
    value === ''
  ) {
    return 'Not available'
  }


  return String(value)
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


function shortReferenceType(
  value,
) {
  if (
    value === null
    ||
    value === undefined
    ||
    value === ''
  ) {
    return 'Not linked'
  }


  const parts =
    String(value).split(
      '\\',
    )


  return (
    parts[
      parts.length - 1
    ]
    ||
    'Not linked'
  )
}


function formatDate(
  value,
) {
  if (!value) {
    return 'Date unavailable'
  }


  const date =
    new Date(
      value,
    )


  if (
    Number.isNaN(
      date.getTime(),
    )
  ) {
    return 'Date unavailable'
  }


  return date.toLocaleString(
    'en-BD',
    {
      dateStyle:
        'medium',

      timeStyle:
        'short',
    },
  )
}
</script>
