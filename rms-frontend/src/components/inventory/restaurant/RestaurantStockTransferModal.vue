<template>
  <Teleport to="body">
    <Transition name="inventory-modal">
      <div
        v-if="show"
        class="restaurant-transfer-backdrop"
        @click.self="requestClose"
      >
        <section
          ref="modalRef"
          class="restaurant-transfer-modal"
          role="dialog"
          aria-modal="true"
          aria-labelledby="restaurant-transfer-title"
          aria-describedby="restaurant-transfer-description"
          @keydown="handleModalKeydown"
        >
          <!-- Header -->

          <header class="restaurant-transfer-header">
            <div class="restaurant-transfer-header-icon">
              <i
                class="bi bi-arrow-left-right"
                aria-hidden="true"
              ></i>
            </div>

            <div>
              <h2 id="restaurant-transfer-title">
                Warehouse to Restaurant Transfer
              </h2>

              <p id="restaurant-transfer-description">
                Move available raw-material stock from the
                warehouse into restaurant stock.
              </p>
            </div>

            <button
              ref="closeButtonRef"
              type="button"
              class="restaurant-transfer-close"
              aria-label="Close stock transfer form"
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

          <div class="restaurant-transfer-body">
            <!-- General Error -->

            <div
              v-if="displayErrorMessage"
              class="restaurant-transfer-error"
              role="alert"
            >
              <i
                class="bi bi-exclamation-triangle"
                aria-hidden="true"
              ></i>

              <span>
                {{ displayErrorMessage }}
              </span>
            </div>


            <!-- Warehouse Loading -->

            <div
              v-if="warehouseStocksLoading"
              class="restaurant-transfer-loading"
              role="status"
              aria-live="polite"
            >
              <span
                class="spinner-border spinner-border-sm"
                aria-hidden="true"
              ></span>

              <span>
                Loading available warehouse stock...
              </span>
            </div>


            <!-- No Warehouse Stock -->

            <div
              v-else-if="availableStocks.length === 0"
              class="restaurant-transfer-empty"
              role="status"
            >
              <i
                class="bi bi-box-seam"
                aria-hidden="true"
              ></i>

              <div>
                <strong>
                  No transferable warehouse stock
                </strong>

                <p>
                  Active raw materials with warehouse quantity
                  greater than zero will appear here.
                </p>
              </div>

              <button
                type="button"
                :disabled="submitting"
                @click="emit('refresh-warehouse')"
              >
                Refresh Warehouse Stock
              </button>
            </div>


            <template v-else>
              <!-- Transfer Information -->

              <section class="restaurant-transfer-section">
                <div class="restaurant-transfer-section-heading">
                  <div>
                    <h3>
                      Transfer Information
                    </h3>

                    <p>
                      Transfer date is optional. If left blank,
                      the server will use the current transfer time.
                    </p>
                  </div>
                </div>

                <div class="restaurant-transfer-general-grid">
                  <div class="restaurant-transfer-field">
                    <label for="restaurant-transfer-date">
                      Transfer Date &amp; Time
                    </label>

                    <input
                      id="restaurant-transfer-date"
                      v-model="form.transferred_at"
                      type="datetime-local"
                      :disabled="submitting"
                    />

                    <small
                      v-if="fieldError('transferred_at')"
                      class="restaurant-transfer-field-error"
                    >
                      {{ fieldError('transferred_at') }}
                    </small>
                  </div>


                  <div class="restaurant-transfer-field">
                    <label for="restaurant-transfer-notes">
                      General Notes
                    </label>

                    <textarea
                      id="restaurant-transfer-notes"
                      v-model="form.notes"
                      rows="3"
                      maxlength="2000"
                      placeholder="Optional notes for this transfer"
                      :disabled="submitting"
                    ></textarea>

                    <div class="restaurant-transfer-field-meta">
                      <small>
                        Optional
                      </small>

                      <small>
                        {{ form.notes.length }}/2000
                      </small>
                    </div>

                    <small
                      v-if="fieldError('notes')"
                      class="restaurant-transfer-field-error"
                    >
                      {{ fieldError('notes') }}
                    </small>
                  </div>
                </div>
              </section>


              <!-- Items -->

              <section class="restaurant-transfer-section">
                <div class="restaurant-transfer-section-heading">
                  <div>
                    <h3>
                      Transfer Items
                    </h3>

                    <p>
                      Quantity cannot exceed the currently available
                      warehouse quantity.
                    </p>
                  </div>

                  <button
                    type="button"
                    class="restaurant-transfer-add-item"
                    :disabled="
                      submitting
                      ||
                      !canAddItem
                    "
                    @click="addItem"
                  >
                    <i
                      class="bi bi-plus-lg"
                      aria-hidden="true"
                    ></i>

                    Add Item
                  </button>
                </div>


                <div class="restaurant-transfer-items">
                  <article
                    v-for="(item, index) in form.items"
                    :key="item.row_key"
                    class="restaurant-transfer-item"
                  >
                    <div class="restaurant-transfer-item-header">
                      <strong>
                        Item {{ index + 1 }}
                      </strong>

                      <button
                        type="button"
                        class="restaurant-transfer-remove-item"
                        :disabled="
                          submitting
                          ||
                          form.items.length <= 1
                        "
                        :aria-label="
                          `Remove transfer item ${index + 1}`
                        "
                        @click="removeItem(index)"
                      >
                        <i
                          class="bi bi-trash3"
                          aria-hidden="true"
                        ></i>
                      </button>
                    </div>


                    <div class="restaurant-transfer-item-grid">
                      <!-- Material -->

                      <div class="restaurant-transfer-field">
                        <label
                          :for="
                            `restaurant-transfer-material-${item.row_key}`
                          "
                        >
                          Raw Material
                          <span>*</span>
                        </label>

                        <select
                          :id="
                            `restaurant-transfer-material-${item.row_key}`
                          "
                          v-model="item.raw_material_id"
                          :disabled="submitting"
                          @change="handleMaterialChange(item)"
                        >
                          <option value="">
                            Select warehouse material
                          </option>

                          <option
                            v-if="hasUnavailableSelectedMaterial(item)"
                            :value="String(item.raw_material_id)"
                            disabled
                          >
                            {{
                              unavailableSelectedMaterialLabel(item)
                            }}
                          </option>

                          <option
                            v-for="stock in availableStocks"
                            :key="stockOptionKey(stock)"
                            :value="String(rawMaterialId(stock))"
                            :disabled="
                              materialSelectedElsewhere(
                                rawMaterialId(stock),
                                item.row_key
                              )
                            "
                          >
                            {{ stockOptionLabel(stock) }}
                          </option>
                        </select>

                        <small
                          v-if="
                            itemFieldError(
                              index,
                              'raw_material_id'
                            )
                          "
                          class="restaurant-transfer-field-error"
                        >
                          {{
                            itemFieldError(
                              index,
                              'raw_material_id'
                            )
                          }}
                        </small>
                      </div>


                      <!-- Available -->

                      <div class="restaurant-transfer-readonly-field">
                        <span>
                          Warehouse Available
                        </span>

                        <strong>
                          {{
                            itemAvailableDisplay(item)
                          }}
                        </strong>
                      </div>


                      <!-- Quantity -->

                      <div class="restaurant-transfer-field">
                        <label
                          :for="
                            `restaurant-transfer-quantity-${item.row_key}`
                          "
                        >
                          Transfer Quantity
                          <span>*</span>
                        </label>

                        <div class="restaurant-transfer-quantity-input">
                          <input
                            :id="
                              `restaurant-transfer-quantity-${item.row_key}`
                            "
                            v-model="item.quantity"
                            type="text"
                            inputmode="decimal"
                            autocomplete="off"
                            placeholder="0"
                            :disabled="
                              submitting
                              ||
                              !item.raw_material_id
                            "
                            @input="
                              handleQuantityInput(
                                $event,
                                item
                              )
                            "
                          />

                          <span>
                            {{ item.unit || 'unit' }}
                          </span>
                        </div>

                        <small
                          v-if="
                            itemFieldError(
                              index,
                              'quantity'
                            )
                          "
                          class="restaurant-transfer-field-error"
                        >
                          {{
                            itemFieldError(
                              index,
                              'quantity'
                            )
                          }}
                        </small>
                      </div>


                      <!-- Item Notes -->

                      <div class="restaurant-transfer-field restaurant-transfer-item-notes">
                        <label
                          :for="
                            `restaurant-transfer-item-notes-${item.row_key}`
                          "
                        >
                          Item Notes
                        </label>

                        <input
                          :id="
                            `restaurant-transfer-item-notes-${item.row_key}`
                          "
                          v-model="item.notes"
                          type="text"
                          maxlength="2000"
                          autocomplete="off"
                          placeholder="Optional item note"
                          :disabled="submitting"
                        />

                        <small
                          v-if="
                            itemFieldError(
                              index,
                              'notes'
                            )
                          "
                          class="restaurant-transfer-field-error"
                        >
                          {{
                            itemFieldError(
                              index,
                              'notes'
                            )
                          }}
                        </small>
                      </div>
                    </div>


                    <div
                      v-if="item.raw_material_id"
                      class="restaurant-transfer-item-summary"
                    >
                      <span>
                        After transfer, warehouse balance:
                      </span>

                      <strong>
                        {{ warehouseAfterDisplay(item) }}
                      </strong>
                    </div>
                  </article>
                </div>


                <small
                  v-if="fieldError('items')"
                  class="restaurant-transfer-field-error"
                >
                  {{ fieldError('items') }}
                </small>
              </section>
            </template>
          </div>


          <!-- Footer -->

          <footer class="restaurant-transfer-footer">
            <button
              type="button"
              class="restaurant-transfer-cancel"
              :disabled="submitting"
              @click="requestClose"
            >
              Cancel
            </button>

            <button
              type="button"
              class="restaurant-transfer-submit"
              :disabled="
                submitting
                ||
                warehouseStocksLoading
                ||
                availableStocks.length === 0
              "
              @click="submitTransfer"
            >
              <span
                v-if="submitting"
                class="spinner-border spinner-border-sm"
                aria-hidden="true"
              ></span>

              <i
                v-else
                class="bi bi-arrow-right-circle"
                aria-hidden="true"
              ></i>

              {{
                submitting
                  ? 'Transferring...'
                  : 'Transfer to Restaurant'
              }}
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
  reactive,
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


  warehouseStocks: {
    type: Array,
    default: () => [],
  },


  warehouseStocksLoading: {
    type: Boolean,
    default: false,
  },


  submitting: {
    type: Boolean,
    default: false,
  },


  errorMessage: {
    type: String,
    default: '',
  },


  serverErrors: {
    type: Object,
    default: () => ({}),
  },
})


const emit = defineEmits([
  'close',
  'submit',
  'refresh-warehouse',
])


/*
|--------------------------------------------------------------------------
| Constants
|--------------------------------------------------------------------------
*/


const MAX_TRANSFER_ITEMS =
  200

const MAX_TRANSFER_QUANTITY =
  9999999999.9999

const MAX_QUANTITY_INTEGER_DIGITS =
  10


/*
|--------------------------------------------------------------------------
| Modal State
|--------------------------------------------------------------------------
*/


const modalRef =
  ref(null)

const closeButtonRef =
  ref(null)

let previouslyFocusedElement =
  null

let previousBodyOverflow =
  null

let rowCounter =
  0


const localErrorMessage =
  ref('')


const form = reactive({
  transferred_at: '',
  notes: '',
  items: [],
})


/*
|--------------------------------------------------------------------------
| Available Warehouse Stock
|--------------------------------------------------------------------------
*/


const availableStocks = computed(() => {

  if (
    !Array.isArray(
      props.warehouseStocks
    )
  ) {
    return []
  }

  const unique =
    new Map()

  props.warehouseStocks.forEach(
    (stock) => {

      const id =
        rawMaterialId(stock)

      const quantity =
        warehouseQuantity(stock)

      if (
        !id
        ||
        !rawMaterialIsActive(stock)
        ||
        !Number.isFinite(quantity)
        ||
        quantity <= 0
      ) {
        return
      }

      unique.set(
        String(id),
        stock
      )

    }
  )

  return Array.from(
    unique.values()
  )
    .sort(
      (first, second) =>
        rawMaterialName(first)
          .localeCompare(
            rawMaterialName(second)
          )
    )

})


const canAddItem = computed(() => {

  if (
    form.items.length >=
    MAX_TRANSFER_ITEMS
  ) {
    return false
  }

  const selected =
    new Set(
      form.items
        .map(
          (item) =>
            String(
              item.raw_material_id
              ||
              ''
            )
        )
        .filter(Boolean)
    )

  return availableStocks.value.some(
    (stock) =>
      !selected.has(
        String(
          rawMaterialId(stock)
        )
      )
  )

})


const displayErrorMessage = computed(() => {

  return (
    localErrorMessage.value
    ||
    props.errorMessage
    ||
    ''
  )

})


/*
|--------------------------------------------------------------------------
| Form Initialization
|--------------------------------------------------------------------------
*/


function resetForm()
{
  form.transferred_at =
    ''

  form.notes =
    ''

  form.items.splice(
    0,
    form.items.length
  )

  addItem()

  localErrorMessage.value =
    ''
}


function createItem()
{
  rowCounter +=
    1

  return {
    row_key:
      `restaurant-transfer-${rowCounter}`,

    raw_material_id:
      '',

    quantity:
      '',

    notes:
      '',

    item_name:
      '',

    unit:
      '',

    available_quantity:
      null,
  }
}


/*
|--------------------------------------------------------------------------
| Item Actions
|--------------------------------------------------------------------------
*/


function addItem()
{
  if (
    props.submitting
    ||
    form.items.length >=
      MAX_TRANSFER_ITEMS
  ) {
    return
  }

  form.items.push(
    createItem()
  )

  localErrorMessage.value =
    ''
}


function removeItem(index)
{
  if (
    props.submitting
    ||
    form.items.length <= 1
    ||
    index < 0
    ||
    index >= form.items.length
  ) {
    return
  }

  form.items.splice(
    index,
    1
  )

  localErrorMessage.value =
    ''
}


function handleMaterialChange(item)
{
  localErrorMessage.value =
    ''

  const stock =
    findAvailableStock(
      item.raw_material_id
    )

  if (!stock) {

    item.item_name =
      ''

    item.unit =
      ''

    item.available_quantity =
      null

    item.quantity =
      ''

    return
  }

  item.item_name =
    rawMaterialName(stock)

  item.unit =
    rawMaterialUnit(stock)

  item.available_quantity =
    warehouseQuantity(stock)

  item.quantity =
    ''
}


/*
|--------------------------------------------------------------------------
| Quantity Input
|--------------------------------------------------------------------------
*/


function handleQuantityInput(
  event,
  item
)
{
  const value =
    sanitizeDecimalInput(
      event?.target?.value
    )

  item.quantity =
    value

  if (
    event?.target
    &&
    event.target.value !==
      value
  ) {
    event.target.value =
      value
  }

  localErrorMessage.value =
    ''
}


function sanitizeDecimalInput(value)
{
  let normalized =
    String(
      value
      ??
      ''
    )
      .replace(
        /,/g,
        '.'
      )
      .replace(
        /[^0-9.]/g,
        ''
      )

  const firstDot =
    normalized.indexOf('.')

  if (firstDot !== -1) {

    normalized =
      normalized.slice(
        0,
        firstDot + 1
      )
      +
      normalized
        .slice(
          firstDot + 1
        )
        .replace(
          /\./g,
          ''
        )

  }

  const [
    integerPart = '',
    decimalPart,
  ] =
    normalized.split('.')

  const safeInteger =
    integerPart
      .replace(
        /^0+(?=\d)/,
        ''
      )
      .slice(
        0,
        MAX_QUANTITY_INTEGER_DIGITS
      )

  if (
    decimalPart ===
    undefined
  ) {
    return safeInteger
  }

  return (
    `${safeInteger || '0'}.`
    +
    decimalPart.slice(
      0,
      4
    )
  )
}


/*
|--------------------------------------------------------------------------
| Validation
|--------------------------------------------------------------------------
*/


function validateForm()
{
  localErrorMessage.value =
    ''

  if (
    form.items.length < 1
    ||
    form.items.length >
      MAX_TRANSFER_ITEMS
  ) {

    localErrorMessage.value =
      `Transfer must contain between 1 and ${MAX_TRANSFER_ITEMS} items.`

    return false
  }

  const selectedIds =
    new Set()

  for (
    let index = 0;
    index < form.items.length;
    index += 1
  ) {
    const item =
      form.items[index]

    const row =
      index + 1

    const id =
      Number(
        item.raw_material_id
      )

    if (
      !Number.isInteger(id)
      ||
      id <= 0
    ) {

      localErrorMessage.value =
        `Please select a raw material in item ${row}.`

      return false
    }

    if (
      selectedIds.has(id)
    ) {

      localErrorMessage.value =
        `The same raw material cannot be transferred more than once. Check item ${row}.`

      return false
    }

    selectedIds.add(id)

    const stock =
      findAvailableStock(id)

    if (!stock) {

      localErrorMessage.value =
        `The selected raw material in item ${row} is unavailable, inactive or out of stock.`

      return false
    }

    const quantity =
      parseQuantity(
        item.quantity
      )

    if (
      quantity === null
      ||
      quantity <= 0
    ) {

      localErrorMessage.value =
        `Transfer quantity must be greater than zero with at most 4 decimal places in item ${row}.`

      return false
    }

    if (
      quantity >
      MAX_TRANSFER_QUANTITY
    ) {

      localErrorMessage.value =
        `Transfer quantity is too large in item ${row}.`

      return false
    }

    const available =
      warehouseQuantity(stock)

    if (
      !Number.isFinite(available)
      ||
      quantity >
        available
    ) {

      localErrorMessage.value =
        `Transfer quantity cannot exceed warehouse availability in item ${row}.`

      return false
    }

    if (
      String(
        item.notes
        ??
        ''
      ).length > 2000
    ) {

      localErrorMessage.value =
        `Item notes cannot exceed 2000 characters in item ${row}.`

      return false
    }
  }

  if (
    form.notes.length >
    2000
  ) {

    localErrorMessage.value =
      'Transfer notes cannot exceed 2000 characters.'

    return false
  }

  return true
}


function parseQuantity(value)
{
  const raw =
    String(
      value
      ??
      ''
    ).trim()

  if (
    !/^\d+(?:\.\d{0,4})?$/.test(
      raw
    )
  ) {
    return null
  }

  const number =
    Number(raw)

  return Number.isFinite(number)
    ? number
    : null
}


/*
|--------------------------------------------------------------------------
| Submit
|--------------------------------------------------------------------------
*/


function submitTransfer()
{
  if (
    props.submitting
    ||
    props.warehouseStocksLoading
    ||
    !validateForm()
  ) {
    return
  }

  const payload = {
    items:
      form.items.map(
        (item) => ({
          raw_material_id:
            Number(
              item.raw_material_id
            ),

          quantity:
            normalizedQuantityString(
              item.quantity
            ),

          notes:
            cleanNullableText(
              item.notes
            ),
        })
      ),

    notes:
      cleanNullableText(
        form.notes
      ),
  }

  if (
    form.transferred_at
  ) {
    payload.transferred_at =
      form.transferred_at
  }

  emit(
    'submit',
    payload
  )
}


function normalizedQuantityString(value)
{
  const number =
    parseQuantity(value)

  if (
    number === null
  ) {
    return ''
  }

  return number
    .toFixed(4)
    .replace(
      /0+$/,
      ''
    )
    .replace(
      /\.$/,
      ''
    )
}


function cleanNullableText(value)
{
  const normalized =
    String(
      value
      ??
      ''
    ).trim()

  return normalized
    ||
    null
}


/*
|--------------------------------------------------------------------------
| Warehouse Helpers
|--------------------------------------------------------------------------
*/


function rawMaterialId(stock)
{
  const value =
    stock?.raw_material_id
    ??
    stock?.raw_material?.id
    ??
    null

  const number =
    Number(value)

  return (
    Number.isInteger(number)
    &&
    number > 0
  )
    ? number
    : null
}


function rawMaterialName(stock)
{
  return (
    stock?.raw_material
      ?.material_name
    ||
    stock?.material_name
    ||
    'Unknown Material'
  )
}


function rawMaterialUnit(stock)
{
  return (
    stock?.raw_material
      ?.base_unit
    ||
    stock?.unit
    ||
    stock?.base_unit
    ||
    ''
  )
}


function rawMaterialIsActive(stock)
{
  const value =
    stock?.raw_material
      ?.is_active
    ??
    stock?.is_active
    ??
    null

  if (
    value === true
    ||
    value === 1
    ||
    value === '1'
  ) {
    return true
  }

  return (
    typeof value ===
      'string'
    &&
    value
      .trim()
      .toLowerCase()
    ===
    'true'
  )
}


function warehouseQuantity(stock)
{
  const number =
    Number(
      stock?.quantity
    )

  return Number.isFinite(number)
    ? roundQuantity(number)
    : Number.NaN
}


function findAvailableStock(
  rawMaterialIdValue
)
{
  const id =
    Number(
      rawMaterialIdValue
    )

  if (
    !Number.isInteger(id)
    ||
    id <= 0
  ) {
    return null
  }

  return (
    availableStocks.value.find(
      (stock) =>
        rawMaterialId(stock)
        ===
        id
    )
    ??
    null
  )
}


function stockOptionKey(stock)
{
  return `warehouse-transfer-stock-${rawMaterialId(stock)}`
}


function stockOptionLabel(stock)
{
  const quantity =
    warehouseQuantity(stock)

  const formatted =
    formatQuantity(
      quantity,
      rawMaterialUnit(stock)
    )

  return `${rawMaterialName(stock)} — Available: ${formatted}`
}


function materialSelectedElsewhere(
  rawMaterialIdValue,
  rowKey
)
{
  if (!rawMaterialIdValue) {
    return false
  }

  return form.items.some(
    (item) =>
      item.row_key !==
        rowKey
      &&
      String(
        item.raw_material_id
      )
      ===
      String(
        rawMaterialIdValue
      )
  )
}


function hasUnavailableSelectedMaterial(item)
{
  return Boolean(
    item?.raw_material_id
    &&
    !findAvailableStock(
      item.raw_material_id
    )
  )
}


function unavailableSelectedMaterialLabel(item)
{
  return (
    item?.item_name
      ? `${item.item_name} — Unavailable`
      : `Material #${item.raw_material_id} — Unavailable`
  )
}


/*
|--------------------------------------------------------------------------
| Item Display Helpers
|--------------------------------------------------------------------------
*/


function itemAvailableDisplay(item)
{
  const stock =
    findAvailableStock(
      item.raw_material_id
    )

  if (!stock) {
    return '—'
  }

  return formatQuantity(
    warehouseQuantity(stock),
    rawMaterialUnit(stock)
  )
}


function warehouseAfterDisplay(item)
{
  const stock =
    findAvailableStock(
      item.raw_material_id
    )

  if (!stock) {
    return 'Not available'
  }

  const available =
    warehouseQuantity(stock)

  const quantity =
    parseQuantity(
      item.quantity
    )

  if (
    quantity === null
  ) {
    return formatQuantity(
      available,
      rawMaterialUnit(stock)
    )
  }

  const after =
    Math.max(
      0,
      roundQuantity(
        available - quantity
      )
    )

  return formatQuantity(
    after,
    rawMaterialUnit(stock)
  )
}


/*
|--------------------------------------------------------------------------
| Server Errors
|--------------------------------------------------------------------------
*/


function fieldError(field)
{
  return firstError(
    props.serverErrors?.[field]
  )
}


function itemFieldError(
  index,
  field
)
{
  return firstError(
    props.serverErrors?.[
      `items.${index}.${field}`
    ]
  )
}


function firstError(value)
{
  if (
    Array.isArray(value)
  ) {
    return value
      .find(Boolean)
      ??
      ''
  }

  if (
    typeof value ===
      'string'
  ) {
    return value
  }

  return ''
}


/*
|--------------------------------------------------------------------------
| Formatting
|--------------------------------------------------------------------------
*/


function roundQuantity(value)
{
  return (
    Math.round(
      (
        Number(value)
        +
        Number.EPSILON
      )
      *
      10000
    )
    /
    10000
  )
}


function formatQuantity(
  value,
  unit = ''
)
{
  const number =
    Number(value)

  if (
    !Number.isFinite(number)
  ) {
    return 'Not available'
  }

  const formatted =
    number.toLocaleString(
      'en-BD',
      {
        maximumFractionDigits: 4,
      }
    )

  return `${formatted} ${unit}`.trim()
}


/*
|--------------------------------------------------------------------------
| Close / Focus / Keyboard
|--------------------------------------------------------------------------
*/


function requestClose()
{
  if (props.submitting) {
    return
  }

  emit('close')
}


function focusableElements()
{
  if (!modalRef.value) {
    return []
  }

  return Array.from(
    modalRef.value
      .querySelectorAll(
        [
          'button:not([disabled])',
          'select:not([disabled])',
          'input:not([disabled])',
          'textarea:not([disabled])',
          '[href]',
          '[tabindex]:not([tabindex="-1"])',
        ].join(',')
      )
  )
}


function handleModalKeydown(event)
{
  if (!props.show) {
    return
  }

  if (
    event.key ===
    'Escape'
  ) {

    event.preventDefault()

    requestClose()

    return
  }

  if (
    event.key !==
    'Tab'
  ) {
    return
  }

  const focusable =
    focusableElements()

  if (
    focusable.length === 0
  ) {
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


function lockBodyScroll()
{
  if (
    previousBodyOverflow ===
    null
  ) {
    previousBodyOverflow =
      document.body.style
        .overflow
  }

  document.body.style
    .overflow =
    'hidden'
}


function restoreBodyScroll()
{
  if (
    previousBodyOverflow ===
    null
  ) {
    return
  }

  document.body.style
    .overflow =
    previousBodyOverflow

  previousBodyOverflow =
    null
}


function restorePreviousFocus()
{
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
    document.contains(target)
  ) {
    target.focus()
  }
}


/*
|--------------------------------------------------------------------------
| Watch
|--------------------------------------------------------------------------
*/


watch(
  () => props.show,

  async (
    visible,
    previousVisible
  ) => {

    if (visible) {

      previouslyFocusedElement =
        document.activeElement

      lockBodyScroll()

      resetForm()

      await nextTick()

      closeButtonRef.value
        ?.focus()

      return
    }

    if (previousVisible) {

      restoreBodyScroll()

      restorePreviousFocus()

    }

  },

  {
    immediate: true,
  }
)


onBeforeUnmount(() => {

  restoreBodyScroll()

  previouslyFocusedElement =
    null

})
</script>
