<template>
    <Teleport to="body">

        <Transition name="po-form-modal">

            <div
                v-if="show"
                class="po-form-overlay"
                @click.self="closeModal"
            >

                <section
                    ref="modalRef"
                    class="po-form-modal"
                    role="dialog"
                    aria-modal="true"
                    aria-labelledby="purchase-order-form-title"
                    @keydown="handleModalKeydown"
                >

                    <!-- Header -->

                    <header class="po-form-header">

                        <div class="po-form-header-content">

                            <div class="po-form-header-icon">

                                <i
                                    class="bi"
                                    :class="
                                        isEditMode
                                            ? 'bi-pencil-square'
                                            : 'bi-cart-plus'
                                    "
                                    aria-hidden="true"
                                ></i>

                            </div>

                            <div>

                                <h2 id="purchase-order-form-title">

                                    {{
                                        isEditMode
                                            ? 'Edit Purchase Order'
                                            : 'Create Purchase Order'
                                    }}

                                </h2>

                                <p>

                                    {{
                                        isEditMode
                                            ? 'Update supplier and purchase item information.'
                                            : 'Create a supplier purchase order using inventory raw materials.'
                                    }}

                                </p>

                            </div>

                        </div>


                        <button
                            ref="closeButtonRef"
                            type="button"
                            class="po-form-close"
                            aria-label="Close purchase order form"
                            :disabled="loading"
                            @click="closeModal"
                        >

                            <i class="bi bi-x-lg" aria-hidden="true"></i>

                        </button>

                    </header>


                    <!-- Receive Started Lock -->

                    <div
                        v-if="receiveStarted"
                        class="po-form-lock-warning"
                        role="alert"
                    >

                        <i class="bi bi-lock-fill" aria-hidden="true"></i>

                        <div>

                            <strong>
                                Editing is locked
                            </strong>

                            <p>
                                This purchase order cannot be edited because
                                receiving has already started.
                            </p>

                        </div>

                    </div>


                    <form
                        novalidate
                        @submit.prevent="submitForm"
                    >

                        <div class="po-form-body">

                            <!-- Validation Error -->

                            <div
                                v-if="formError"
                                class="po-form-error"
                                role="alert"
                            >

                                <i class="bi bi-exclamation-circle-fill" aria-hidden="true"></i>

                                <span>
                                    {{ formError }}
                                </span>

                            </div>


                            <!-- Basic Information -->

                            <section class="po-form-section">

                                <div class="po-form-section-heading">

                                    <div>

                                        <h3>
                                            Order Information
                                        </h3>

                                        <p>
                                            Select supplier and purchase dates. New orders start as Ordered automatically.
                                        </p>

                                    </div>

                                </div>


                                <div class="po-form-grid">

                                    <!-- Supplier -->

                                    <div class="po-form-group">

                                        <label for="po-supplier">

                                            Supplier

                                            <span>*</span>

                                        </label>

                                        <select
                                            id="po-supplier"
                                            ref="supplierSelectRef"
                                            v-model="form.supplier_id"
                                            :disabled="
                                                formDisabled
                                            "
                                        >

                                            <option value="">
                                                Select supplier
                                            </option>

                                            <option
                                                v-for="supplier in suppliers"
                                                :key="supplier.id"
                                                :value="String(supplier.id)"
                                            >

                                                {{
                                                    supplier.company_name
                                                    ||
                                                    supplier.supplier_name
                                                    ||
                                                    supplier.name
                                                    ||
                                                    `Supplier #${supplier.id}`
                                                }}

                                            </option>

                                        </select>

                                    </div>


                                    <!-- Order Date -->

                                    <div class="po-form-group">

                                        <label for="po-order-date">

                                            Order Date

                                            <span>*</span>

                                        </label>

                                        <input
                                            id="po-order-date"
                                            v-model="form.order_date"
                                            type="date"
                                            :max="
                                                form.delivery_date
                                                ||
                                                undefined
                                            "
                                            :disabled="formDisabled"
                                        />

                                    </div>


                                    <!-- Delivery Date -->

                                    <div class="po-form-group">

                                        <label for="po-delivery-date">
                                            Expected Delivery
                                        </label>

                                        <input
                                            id="po-delivery-date"
                                            v-model="form.delivery_date"
                                            type="date"
                                            :min="form.order_date || undefined"
                                            :disabled="formDisabled"
                                        />

                                    </div>


                                    <!-- Status -->

                                    <div class="po-form-group">

                                        <label>
                                            Status
                                        </label>

                                        <div class="po-unit-display">

                                            <i class="bi bi-info-circle" aria-hidden="true"></i>

                                            <span>
                                                {{
                                                    isEditMode
                                                        ? statusLabel
                                                        : 'Ordered'
                                                }}
                                            </span>

                                        </div>

                                    </div>

                                </div>

                            </section>


                            <!-- Purchase Items -->

                            <section class="po-form-section">

                                <div class="po-form-section-heading po-item-heading">

                                    <div>

                                        <h3>
                                            Purchase Items
                                        </h3>

                                        <p>
                                            Select a raw material. Item name and unit
                                            will be filled automatically.
                                        </p>

                                    </div>


                                    <button
                                        type="button"
                                        class="po-add-item-button"
                                        :disabled="
                                            formDisabled
                                            ||
                                            rawMaterialsLoading
                                            ||
                                            !canAddItem
                                        "
                                        @click="addItem"
                                    >

                                        <i class="bi bi-plus-lg" aria-hidden="true"></i>

                                        Add Item

                                    </button>

                                </div>


                                <!-- Raw Material Loading -->

                                <div
                                    v-if="rawMaterialsLoading"
                                    class="po-material-loading"
                                >

                                    <span
                                        class="spinner-border spinner-border-sm"
                                        aria-hidden="true"
                                    ></span>

                                    Loading raw materials...

                                </div>


                                <!-- No Raw Materials -->

                                <div
                                    v-else-if="availableMaterialCount === 0"
                                    class="po-material-empty"
                                >

                                    <i class="bi bi-box-seam" aria-hidden="true"></i>

                                    <div>

                                        <strong>
                                            No active raw materials found
                                        </strong>

                                        <p>
                                            Create and activate raw materials from
                                            Inventory Management first.
                                        </p>

                                    </div>

                                </div>


                                <!-- Items -->

                                <div
                                    v-else
                                    class="po-item-list"
                                >

                                    <article
                                        v-for="(item, index) in form.items"
                                        :key="item.row_key"
                                        class="po-item-card"
                                    >

                                        <header class="po-item-card-header">

                                            <div>

                                                <span class="po-item-number">
                                                    {{ index + 1 }}
                                                </span>

                                                <div>

                                                    <strong>
                                                        Purchase Item
                                                    </strong>

                                                    <small>
                                                        Select an inventory raw material
                                                    </small>

                                                </div>

                                            </div>


                                            <button
                                                type="button"
                                                class="po-remove-item-button"
                                                title="Remove purchase item"
                                                :disabled="
                                                    formDisabled
                                                    ||
                                                    form.items.length <= 1
                                                "
                                                @click="removeItem(index)"
                                            >

                                                <i class="bi bi-trash3" aria-hidden="true"></i>

                                            </button>

                                        </header>


                                        <div class="po-item-fields">

                                            <!-- Raw Material -->

                                            <div class="po-form-group po-material-select-group">

                                                <label
                                                    :for="
                                                        `po-material-${item.row_key}`
                                                    "
                                                >

                                                    Raw Material

                                                    <span>*</span>

                                                </label>

                                                <select
                                                    :id="
                                                        `po-material-${item.row_key}`
                                                    "
                                                    v-model="item.raw_material_id"
                                                    :disabled="formDisabled"
                                                    @change="
                                                        handleRawMaterialChange(
                                                            item
                                                        )
                                                    "
                                                >

                                                    <option value="">
                                                        Select raw material
                                                    </option>

                                                    <option
                                                        v-if="
                                                            hasUnavailableItemMaterial(
                                                                item
                                                            )
                                                        "
                                                        :value="
                                                            String(
                                                                item.raw_material_id
                                                            )
                                                        "
                                                        disabled
                                                    >
                                                        {{
                                                            unavailableItemMaterialLabel(
                                                                item
                                                            )
                                                        }}
                                                    </option>

                                                    <option
                                                        v-for="material in activeRawMaterials"
                                                        :key="material.id"
                                                        :value="String(material.id)"
                                                        :disabled="
                                                            materialIsUsed(
                                                                material.id,
                                                                index
                                                            )
                                                        "
                                                    >

                                                        {{
                                                            rawMaterialOptionLabel(
                                                                material
                                                            )
                                                        }}

                                                        {{
                                                            materialIsUsed(
                                                                material.id,
                                                                index
                                                            )
                                                                ? ' — Already selected'
                                                                : ''
                                                        }}

                                                    </option>

                                                </select>

                                            </div>


                                            <!-- Item Name -->

                                            <div class="po-form-group">

                                                <label>
                                                    Item Name
                                                </label>

                                                <input
                                                    v-model="item.item_name"
                                                    type="text"
                                                    readonly
                                                    placeholder="Auto-filled"
                                                    class="po-readonly-input"
                                                />

                                            </div>


                                            <!-- Unit -->

                                            <div class="po-form-group">

                                                <label>
                                                    Purchase Unit
                                                </label>

                                                <div class="po-unit-display">

                                                    <i class="bi bi-rulers" aria-hidden="true"></i>

                                                    <span>
                                                        {{ item.unit || 'Not selected' }}
                                                    </span>

                                                </div>

                                            </div>


                                            <!-- Quantity -->

                                            <div class="po-form-group">

                                                <label
                                                    :for="
                                                        `po-quantity-${item.row_key}`
                                                    "
                                                >

                                                    Quantity

                                                    <span>*</span>

                                                </label>

                                                <div class="po-number-with-unit">

                                                    <input
                                                        :id="
                                                            `po-quantity-${item.row_key}`
                                                        "
                                                        v-model="item.quantity"
                                                        type="text"
                                                        inputmode="decimal"
                                                        autocomplete="off"
                                                        placeholder="0"
                                                        :disabled="formDisabled"
                                                        @input="
                                                            handleItemDecimalInput(
                                                                $event,
                                                                item,
                                                                'quantity',
                                                                4
                                                            )
                                                        "
                                                    />

                                                    <span>
                                                        {{ item.unit || 'Unit' }}
                                                    </span>

                                                </div>

                                            </div>


                                            <!-- Unit Price -->

                                            <div class="po-form-group">

                                                <label
                                                    :for="
                                                        `po-price-${item.row_key}`
                                                    "
                                                >

                                                    Unit Price

                                                    <span>*</span>

                                                </label>

                                                <div class="po-money-input">

                                                    <span>
                                                        ৳
                                                    </span>

                                                    <input
                                                        :id="
                                                            `po-price-${item.row_key}`
                                                        "
                                                        v-model="item.unit_price"
                                                        type="text"
                                                        inputmode="decimal"
                                                        autocomplete="off"
                                                        placeholder="0.00"
                                                        :disabled="formDisabled"
                                                        @input="
                                                            handleItemDecimalInput(
                                                                $event,
                                                                item,
                                                                'unit_price',
                                                                2
                                                            )
                                                        "
                                                    />

                                                </div>

                                            </div>


                                            <!-- Line Total -->

                                            <div class="po-form-group">

                                                <label>
                                                    Line Total
                                                </label>

                                                <div class="po-line-total">

                                                    {{
                                                        formatMoney(
                                                            itemLineTotal(item)
                                                        )
                                                    }}

                                                </div>

                                            </div>

                                        </div>

                                    </article>

                                </div>

                            </section>


                            <!-- Payment Calculation -->

                            <section class="po-form-section">

                                <div class="po-form-section-heading">

                                    <div>

                                        <h3>
                                            Amount and Payment
                                        </h3>

                                        <p>
                                            {{
                                                isEditMode
                                                    ? 'Update charges. Existing payments are managed from Purchase Order Details.'
                                                    : 'Add charges and optionally record an advance payment.'
                                            }}
                                        </p>

                                    </div>

                                </div>


                                <div class="po-form-grid">

                                    <!-- Tax -->

                                    <div class="po-form-group">

                                        <label for="po-tax">
                                            Tax
                                        </label>

                                        <div class="po-money-input">

                                            <span>
                                                ৳
                                            </span>

                                            <input
                                                id="po-tax"
                                                v-model="form.tax"
                                                type="text"
                                                inputmode="decimal"
                                                autocomplete="off"
                                                placeholder="0.00"
                                                :disabled="formDisabled"
                                                @input="
                                                    handleFormDecimalInput(
                                                        $event,
                                                        'tax',
                                                        2
                                                    )
                                                "
                                            />

                                        </div>

                                    </div>


                                    <!-- Service Charge -->

                                    <div class="po-form-group">

                                        <label for="po-service-charge">
                                            Service Charge
                                        </label>

                                        <div class="po-money-input">

                                            <span>
                                                ৳
                                            </span>

                                            <input
                                                id="po-service-charge"
                                                v-model="form.service_charge"
                                                type="text"
                                                inputmode="decimal"
                                                autocomplete="off"
                                                placeholder="0.00"
                                                :disabled="formDisabled"
                                                @input="
                                                    handleFormDecimalInput(
                                                        $event,
                                                        'service_charge',
                                                        2
                                                    )
                                                "
                                            />

                                        </div>

                                    </div>


                                    <!-- Initial / Advance Payment - Create Only -->

                                    <div
                                        v-if="!isEditMode"
                                        class="po-form-group"
                                    >

                                        <label for="po-paid-amount">
                                            Paid Amount
                                        </label>

                                        <div class="po-money-input">

                                            <span>
                                                ৳
                                            </span>

                                            <input
                                                id="po-paid-amount"
                                                v-model="form.paid_amount"
                                                type="text"
                                                inputmode="decimal"
                                                autocomplete="off"
                                                placeholder="0.00"
                                                :disabled="formDisabled"
                                                @input="
                                                    handleFormDecimalInput(
                                                        $event,
                                                        'paid_amount',
                                                        2
                                                    )
                                                "
                                            />

                                        </div>

                                    </div>


                                    <!-- Payment Method - Create Only -->

                                    <div
                                        v-if="!isEditMode"
                                        class="po-form-group"
                                    >

                                        <label for="po-payment-method">
                                            Payment Method
                                        </label>

                                        <select
                                            id="po-payment-method"
                                            v-model="form.payment_method"
                                            :disabled="formDisabled"
                                        >

                                            <option value="">
                                                Select payment method
                                            </option>

                                            <option value="cash">
                                                Cash
                                            </option>

                                            <option value="card">
                                                Card
                                            </option>

                                            <option value="bkash">
                                                bKash
                                            </option>

                                            <option value="nagad">
                                                Nagad
                                            </option>

                                            <option value="bank_transfer">
                                                Bank Transfer
                                            </option>

                                            <option value="other">
                                                Other
                                            </option>

                                        </select>

                                    </div>

                                </div>


                                <!-- Calculation Summary -->

                                <div class="po-calculation-summary">

                                    <article>

                                        <span>
                                            Subtotal
                                        </span>

                                        <strong>
                                            {{ formatMoney(subtotal) }}
                                        </strong>

                                    </article>

                                    <article>

                                        <span>
                                            Tax
                                        </span>

                                        <strong>
                                            {{ formatMoney(form.tax) }}
                                        </strong>

                                    </article>

                                    <article>

                                        <span>
                                            Service Charge
                                        </span>

                                        <strong>
                                            {{ formatMoney(form.service_charge) }}
                                        </strong>

                                    </article>

                                    <article class="po-calculation-total">

                                        <span>
                                            Total Amount
                                        </span>

                                        <strong>
                                            {{ formatMoney(totalAmount) }}
                                        </strong>

                                    </article>

                                    <article>

                                        <span>
                                            {{
                                                isEditMode
                                                    ? 'Already Paid'
                                                    : 'Advance Paid'
                                            }}
                                        </span>

                                        <strong class="po-paid-value">
                                            {{ formatMoney(form.paid_amount) }}
                                        </strong>

                                    </article>

                                    <article>

                                        <span>
                                            {{
                                                isEditMode
                                                    ? 'Current Due'
                                                    : 'Due After Advance'
                                            }}
                                        </span>

                                        <strong class="po-due-value">
                                            {{ formatMoney(dueAmount) }}
                                        </strong>

                                    </article>

                                </div>

                                <div
                                    v-if="isEditMode"
                                    class="po-form-lock-warning"
                                    style="margin-top: 1rem;"
                                >

                                    <i class="bi bi-cash-coin" aria-hidden="true"></i>

                                    <div>

                                        <strong>
                                            Payment ledger is separate
                                        </strong>

                                        <p>
                                            To add another partial payment, open Purchase Order Details
                                            and use Add Payment. Existing payment history is not edited here.
                                        </p>

                                    </div>

                                </div>

                            </section>


                            <!-- Notes -->

                            <section class="po-form-section">

                                <div class="po-form-group">

                                    <label for="po-notes">
                                        Notes
                                    </label>

                                    <textarea
                                        id="po-notes"
                                        v-model.trim="form.notes"
                                        rows="3"
                                        maxlength="2000"
                                        placeholder="Optional purchase order notes"
                                        :disabled="formDisabled"
                                    ></textarea>

                                    <small class="po-notes-count">
                                        {{ form.notes.length }}/2000
                                    </small>

                                </div>

                            </section>

                        </div>


                        <!-- Footer -->

                        <footer class="po-form-footer">

                            <button
                                type="button"
                                class="po-form-cancel-button"
                                :disabled="loading"
                                @click="closeModal"
                            >
                                Cancel
                            </button>


                            <button
                                type="submit"
                                class="po-form-submit-button"
                                :disabled="
                                    formDisabled
                                    ||
                                    rawMaterialsLoading
                                    ||
                                    activeRawMaterials.length === 0
                                "
                            >

                                <span
                                    v-if="loading"
                                    class="spinner-border spinner-border-sm"
                                    aria-hidden="true"
                                ></span>

                                <i
                                    v-else
                                    class="bi"
                                    :class="
                                        isEditMode
                                            ? 'bi-check2-circle'
                                            : 'bi-cart-plus'
                                    "
                                    aria-hidden="true"
                                ></i>

                                <span>

                                    {{
                                        loading
                                            ? 'Saving Purchase Order...'
                                            : isEditMode
                                                ? 'Update Purchase Order'
                                                : 'Create Purchase Order'
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


    order: {

        type: Object,

        default: null,

    },


    suppliers: {

        type: Array,

        default: () => [],

    },


    rawMaterials: {

        type: Array,

        default: () => [],

    },


    rawMaterialsLoading: {

        type: Boolean,

        default: false,

    },


    loading: {

        type: Boolean,

        default: false,

    },

})


const emit = defineEmits([

    'close',

    'submit',

])


const MAX_PURCHASE_ITEMS =
    200

const MAX_PURCHASE_QUANTITY =
    999999999999.9999

const MAX_PURCHASE_MONEY =
    999999999999.99

const PAYMENT_METHODS = [
    'cash',
    'card',
    'bkash',
    'nagad',
    'bank_transfer',
    'other',
]


const modalRef =
    ref(null)

const closeButtonRef =
    ref(null)

const supplierSelectRef =
    ref(null)

let previouslyFocusedElement =
    null

let previousBodyOverflow =
    null

let rowCounter = 0


const form = reactive({

    supplier_id: '',

    order_date: '',

    delivery_date: '',

    status: 'ordered',

    tax: '0',

    service_charge: '0',

    paid_amount: '0',

    payment_method: '',

    notes: '',

    items: [],

})


const formState = reactive({

    error: '',

})


/*
|--------------------------------------------------------------------------
| Computed State
|--------------------------------------------------------------------------
*/

const isEditMode = computed(() => {

    return Boolean(
        props.order?.id
    )

})


const statusLabel = computed(() => {

    const labels = {
        ordered: 'Ordered',
        partially_received: 'Partially Received',
        received: 'Received',
        cancelled: 'Cancelled',
    }

    return (
        labels[
            orderStatus.value
        ]
        ||
        orderStatus.value
        ||
        'Ordered'
    )
})


const orderStatus = computed(() => {

    const status =
        props.order?.status?.value
        ??
        props.order?.status
        ??
        ''

    return String(status)
        .trim()
        .toLowerCase()

})


const receiveStarted = computed(() => {

    if (
        [
            'partially_received',
            'received',
        ].includes(orderStatus.value)
    ) {
        return true
    }

    const items =
        getOrderItems(
            props.order
        )

    return items.some(
        (item) =>
            Number(
                item?.received_quantity || 0
            ) > 0
    )

})


const formDisabled = computed(() => {

    return (
        props.loading
        ||
        receiveStarted.value
    )

})


const activeRawMaterials = computed(() => {

    if (
        !Array.isArray(
            props.rawMaterials
        )
    ) {
        return []
    }

    return props.rawMaterials.filter(
        (material) => {

            return (
                material
                &&
                material.id
                &&
                materialIsActive(
                    material
                )
            )

        }
    )

})


const availableMaterialCount = computed(() => {

    return activeRawMaterials.value.length

})


const canAddItem = computed(() => {

    if (
        form.items.length >=
        MAX_PURCHASE_ITEMS
    ) {
        return false
    }

    const selectedIds =
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

    return activeRawMaterials.value.some(
        (material) =>
            !selectedIds.has(
                String(
                    material.id
                )
            )
    )

})

// const selectedMaterialIds = computed(() => {

//     return form.items
//         .map(
//             (item) =>
//                 String(
//                     item.raw_material_id || ''
//                 )
//         )
//         .filter(Boolean)

// })


const subtotal = computed(() => {

    return roundMoney(

        form.items.reduce(
            (
                total,
                item
            ) => {

                return (
                    total
                    +
                    itemLineTotal(item)
                )

            },
            0
        )

    )

})


const totalAmount = computed(() => {

    return roundMoney(

        subtotal.value
        +
        decimalOrZero(
            form.tax,
            2
        )
        +
        decimalOrZero(
            form.service_charge,
            2
        )

    )

})


const existingPaidAmount = computed(() => {

    return roundMoney(
        decimalOrZero(
            form.paid_amount,
            2
        )
    )

})


const dueAmount = computed(() => {

    return roundMoney(

        Math.max(
            0,
            totalAmount.value
            -
            existingPaidAmount.value
        )

    )

})

const formError = computed(() => {

    return formState.error

})


/*
|--------------------------------------------------------------------------
| Initialize Form
|--------------------------------------------------------------------------
*/

function initializeForm()
{
    formState.error = ''

    const sourceOrder =
        props.order

    form.supplier_id =
        sourceOrder?.supplier_id
            ? String(sourceOrder.supplier_id)
            : sourceOrder?.supplier?.id
                ? String(sourceOrder.supplier.id)
                : ''

    form.order_date =
        toDateInput(
            sourceOrder?.order_date
        )
        ||
        todayDate()

    form.delivery_date =
        toDateInput(
            sourceOrder?.delivery_date
        )

    form.status =
        orderStatus.value
        ||
        'ordered'

    form.tax =
        decimalInputValue(
            sourceOrder?.tax,
            2
        )

    form.service_charge =
        decimalInputValue(
            sourceOrder?.service_charge,
            2
        )

    form.paid_amount =
        decimalInputValue(
            sourceOrder?.paid_amount,
            2
        )

    form.payment_method =
        String(
            sourceOrder?.payment_method || ''
        )

    form.notes =
        String(
            sourceOrder?.notes || ''
        )

    const sourceItems =
        getOrderItems(
            sourceOrder
        )

    form.items =
        sourceItems.length > 0
            ? sourceItems.map(
                normalizeExistingItem
            )
            : [
                createEmptyItem(),
            ]
}


function normalizeExistingItem(item)
{
    const rawMaterialId =
        item?.raw_material_id
        ??
        item?.raw_material?.id
        ??
        ''

    const material =
        getRawMaterial(
            rawMaterialId
        )

    return {

        row_key:
            nextRowKey(),

        id:
            item?.id
            ??
            null,

        raw_material_id:
            rawMaterialId
                ? String(rawMaterialId)
                : '',

        item_name:
            item?.item_name
            ??
            material?.material_name
            ??
            '',

        unit:
            item?.unit
            ??
            material?.base_unit
            ??
            material?.unit
            ??
            '',

        quantity:
            decimalInputValue(
                item?.quantity,
                4
            ),

        unit_price:
            decimalInputValue(
                item?.unit_price,
                2
            ),

    }
}


function createEmptyItem()
{
    return {

        row_key:
            nextRowKey(),

        id: null,

        raw_material_id: '',

        item_name: '',

        unit: '',

        quantity: '1',

        unit_price: '0',

    }
}


function nextRowKey()
{
    rowCounter += 1

    return `purchase-item-${Date.now()}-${rowCounter}`
}


/*
|--------------------------------------------------------------------------
| Raw Material
|--------------------------------------------------------------------------
*/

function getRawMaterial(materialId)
{
    if (
        materialId === null
        ||
        materialId === undefined
        ||
        materialId === ''
    ) {
        return null
    }

    return props.rawMaterials.find(
        (material) => {

            return (
                String(material?.id)
                ===
                String(materialId)
            )

        }
    )
    ??
    null
}


function getActiveRawMaterial(materialId)
{
    const material =
        getRawMaterial(
            materialId
        )

    return (
        material
        &&
        materialIsActive(
            material
        )
    )
        ? material
        : null
}

function handleRawMaterialChange(item)
{
    formState.error = ''

    const material =
        getActiveRawMaterial(
            item.raw_material_id
        )

    if (!material) {

        item.item_name = ''

        item.unit = ''

        return

    }

    item.item_name =
        material.material_name
        ??
        material.name
        ??
        ''

    item.unit =
        material.base_unit
        ??
        material.unit
        ??
        ''
}


function materialIsUsed(
    materialId,
    currentIndex
) {
    return form.items.some(
        (
            item,
            index
        ) => {

            if (
                index === currentIndex
            ) {
                return false
            }

            return (
                String(
                    item.raw_material_id || ''
                )
                ===
                String(materialId)
            )

        }
    )
}


function rawMaterialOptionLabel(material)
{
    const materialName =
        material.material_name
        ??
        material.name
        ??
        `Material #${material.id}`

    const category =
        material.category
        ??
        material.category_name
        ??
        ''

    const unit =
        material.base_unit
        ??
        material.unit
        ??
        ''

    return [
        materialName,
        category
            ? `— ${category}`
            : '',
        unit
            ? `(${unit})`
            : '',
    ]
        .filter(Boolean)
        .join(' ')
}


function materialIsActive(material)
{
    if (
        typeof material?.is_active ===
        'boolean'
    ) {
        return material.is_active
    }

    if (
        typeof material
            ?.raw_material
            ?.is_active ===
        'boolean'
    ) {
        return material
            .raw_material
            .is_active
    }

    return false
}


function hasUnavailableItemMaterial(item)
{
    return Boolean(
        item?.raw_material_id
        &&
        !getActiveRawMaterial(
            item.raw_material_id
        )
    )
}


function unavailableItemMaterialLabel(item)
{
    const name =
        String(
            item?.item_name
            ||
            'Selected material'
        ).trim()

    const unit =
        String(
            item?.unit
            ||
            ''
        ).trim()

    return [
        name,
        unit
            ? `(${unit})`
            : '',
        '— Inactive / unavailable',
    ]
        .filter(Boolean)
        .join(' ')
}


/*
|--------------------------------------------------------------------------
| Item Actions
|--------------------------------------------------------------------------
*/

function addItem()
{
    if (
        formDisabled.value
        ||
        !canAddItem.value
    ) {
        return
    }

    form.items.push(
        createEmptyItem()
    )
}


function removeItem(index)
{
    if (
        formDisabled.value
        ||
        form.items.length <= 1
    ) {
        return
    }

    form.items.splice(
        index,
        1
    )
}


function sanitizeDecimalInput(
    value,
    decimalPlaces
) {
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
                12
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
            decimalPlaces
        )
    )
}


function handleItemDecimalInput(
    event,
    item,
    field,
    decimalPlaces
) {
    const value =
        sanitizeDecimalInput(
            event?.target?.value,
            decimalPlaces
        )

    item[field] =
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

    formState.error = ''
}


function handleFormDecimalInput(
    event,
    field,
    decimalPlaces
) {
    const value =
        sanitizeDecimalInput(
            event?.target?.value,
            decimalPlaces
        )

    form[field] =
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

    formState.error = ''
}


function parseDecimal(
    value,
    decimalPlaces
) {
    const raw =
        String(
            value
            ??
            ''
        ).trim()

    if (!raw) {
        return null
    }

    const pattern =
        new RegExp(
            `^\\d+(?:\\.\\d{0,${decimalPlaces}})?$`
        )

    if (!pattern.test(raw)) {
        return null
    }

    const number =
        Number(raw)

    return Number.isFinite(number)
        ? number
        : null
}


function decimalOrZero(
    value,
    decimalPlaces
) {
    return (
        parseDecimal(
            value,
            decimalPlaces
        )
        ??
        0
    )
}


function decimalInputValue(
    value,
    decimalPlaces
) {
    const number =
        Number(value)

    if (
        !Number.isFinite(number)
        ||
        number < 0
    ) {
        return '0'
    }

    const factor =
        10 ** decimalPlaces

    return String(
        Math.round(
            (
                number
                +
                Number.EPSILON
            )
            *
            factor
        )
        /
        factor
    )
}


/*
|--------------------------------------------------------------------------
| Validation
|--------------------------------------------------------------------------
*/

function validateForm()
{
    formState.error = ''

    if (!form.supplier_id) {

        return setFormError(
            'Please select a supplier.'
        )

    }

    if (
        !isValidDateInput(
            form.order_date
        )
    ) {

        return setFormError(
            'Please select a valid order date.'
        )

    }

    if (
        form.delivery_date
        &&
        !isValidDateInput(
            form.delivery_date
        )
    ) {

        return setFormError(
            'Please select a valid delivery date.'
        )

    }

    if (
        form.delivery_date
        &&
        form.delivery_date
            <
            form.order_date
    ) {

        return setFormError(
            'Delivery date cannot be before order date.'
        )

    }

    if (
        form.items.length === 0
    ) {

        return setFormError(
            'Please add at least one purchase item.'
        )

    }

    if (
        form.items.length >
        MAX_PURCHASE_ITEMS
    ) {

        return setFormError(
            `A purchase order cannot contain more than ${MAX_PURCHASE_ITEMS} items.`
        )

    }

    const uniqueMaterials =
        new Set()

    for (
        let index = 0;
        index < form.items.length;
        index += 1
    ) {
        const item =
            form.items[index]

        const rowNumber =
            index + 1

        if (
            !item.raw_material_id
        ) {

            return setFormError(
                `Please select a raw material in row ${rowNumber}.`
            )

        }

        const materialId =
            String(
                item.raw_material_id
            )

        if (
            uniqueMaterials.has(
                materialId
            )
        ) {

            return setFormError(
                `The same raw material cannot be selected more than once. Check row ${rowNumber}.`
            )

        }

        uniqueMaterials.add(
            materialId
        )

        const material =
            getActiveRawMaterial(
                item.raw_material_id
            )

        if (!material) {

            return setFormError(
                `The selected raw material in row ${rowNumber} is unavailable or inactive. Select an active material.`
            )

        }

        if (
            !item.item_name
            ||
            !item.unit
        ) {

            return setFormError(
                `Raw material information is incomplete in row ${rowNumber}.`
            )

        }

        const quantity =
            parseDecimal(
                item.quantity,
                4
            )

        if (
            quantity === null
            ||
            quantity <= 0
        ) {

            return setFormError(
                `Quantity must be greater than zero and contain at most 4 decimal places in row ${rowNumber}.`
            )

        }

        if (
            quantity >
            MAX_PURCHASE_QUANTITY
        ) {

            return setFormError(
                `Quantity is too large in row ${rowNumber}.`
            )

        }

        const unitPrice =
            parseDecimal(
                item.unit_price,
                2
            )

        if (
            unitPrice === null
            ||
            unitPrice < 0
        ) {

            return setFormError(
                `Unit price is required and may contain at most 2 decimal places in row ${rowNumber}.`
            )

        }

        if (
            unitPrice >
            MAX_PURCHASE_MONEY
        ) {

            return setFormError(
                `Unit price is too large in row ${rowNumber}.`
            )

        }

    }

    const tax =
        parseOptionalMoney(
            form.tax
        )

    const serviceCharge =
        parseOptionalMoney(
            form.service_charge
        )

    if (
        tax === null
        ||
        serviceCharge === null
    ) {

        return setFormError(
            'Tax and service charge must be valid non-negative amounts with at most 2 decimal places.'
        )

    }

    if (
        tax > MAX_PURCHASE_MONEY
        ||
        serviceCharge >
        MAX_PURCHASE_MONEY
    ) {

        return setFormError(
            'Tax or service charge is too large.'
        )

    }

    if (isEditMode.value) {

        if (
            totalAmount.value
            <
            existingPaidAmount.value
        ) {

            return setFormError(
                'Total amount cannot be reduced below the amount already paid.'
            )

        }

        return true
    }

    const paidAmount =
        parseOptionalMoney(
            form.paid_amount
        )

    if (paidAmount === null) {

        return setFormError(
            'Advance payment must be a valid non-negative amount with at most 2 decimal places.'
        )

    }

    if (
        paidAmount >
        MAX_PURCHASE_MONEY
    ) {

        return setFormError(
            'Advance payment is too large.'
        )

    }

    if (
        paidAmount >
        totalAmount.value
    ) {

        return setFormError(
            'Advance payment cannot be greater than total amount.'
        )

    }

    if (
        paidAmount > 0
        &&
        !PAYMENT_METHODS.includes(
            form.payment_method
        )
    ) {

        return setFormError(
            'Please select a valid payment method for the advance payment.'
        )

    }

    return true
}


function parseOptionalMoney(value)
{
    const raw =
        String(
            value
            ??
            ''
        ).trim()

    if (!raw) {
        return 0
    }

    const number =
        parseDecimal(
            raw,
            2
        )

    if (
        number === null
        ||
        number < 0
    ) {
        return null
    }

    return number
}

function setFormError(message)
{
    formState.error =
        message

    return false
}


/*
|--------------------------------------------------------------------------
| Submit
|--------------------------------------------------------------------------
*/

function submitForm()
{
    if (
        formDisabled.value
        ||
        !validateForm()
    ) {
        return
    }

    const payload = {

        supplier_id:
            Number(
                form.supplier_id
            ),

        order_date:
            form.order_date,

        delivery_date:
            form.delivery_date
            ||
            null,

        tax:
            roundMoney(
                parseOptionalMoney(
                    form.tax
                )
                ??
                0
            ),

        service_charge:
            roundMoney(
                parseOptionalMoney(
                    form.service_charge
                )
                ??
                0
            ),

        notes:
            form.notes.trim()
            ||
            null,

        items:
            form.items.map(
                (item) => ({

                    raw_material_id:
                        Number(
                            item.raw_material_id
                        ),

                    item_name:
                        String(
                            item.item_name
                        ).trim(),

                    unit:
                        String(
                            item.unit
                        )
                            .trim()
                            .toLowerCase(),

                    quantity:
                        roundDecimal(
                            parseDecimal(
                                item.quantity,
                                4
                            )
                            ??
                            0,
                            4
                        ),

                    unit_price:
                        roundMoney(
                            parseDecimal(
                                item.unit_price,
                                2
                            )
                            ??
                            0
                        ),

                })
            ),

    }

    /*
    |--------------------------------------------------------------------------
    | Initial Payment Belongs Only To Create
    |--------------------------------------------------------------------------
    */

    if (!isEditMode.value) {

        const paidAmount =
            roundMoney(
                parseOptionalMoney(
                    form.paid_amount
                )
                ??
                0
            )

        payload.paid_amount =
            paidAmount

        payload.payment_method =
            paidAmount > 0
                ? form.payment_method
                : null

    }

    emit(
        'submit',
        payload
    )
}

/*
|--------------------------------------------------------------------------
| Close
|--------------------------------------------------------------------------
*/

function closeModal()
{
    if (props.loading) {
        return
    }

    emit('close')
}


/*
|--------------------------------------------------------------------------
| Helpers
|--------------------------------------------------------------------------
*/

function getOrderItems(order)
{
    const items =
        order?.items?.data
        ??
        order?.items
        ??
        []

    return Array.isArray(items)
        ? items
        : []
}


function itemLineTotal(item)
{
    return roundMoney(

        decimalOrZero(
            item?.quantity,
            4
        )
        *
        decimalOrZero(
            item?.unit_price,
            2
        )

    )
}


function roundDecimal(
    value,
    decimalPlaces
)
{
    const number =
        Number(value)

    if (
        !Number.isFinite(number)
    ) {
        return 0
    }

    const factor =
        10 ** decimalPlaces

    return Math.round(
        (
            number
            +
            Number.EPSILON
        )
        *
        factor
    ) / factor
}


function roundMoney(value)
{
    return roundDecimal(
        value,
        2
    )
}


function formatMoney(value)
{
    const number =
        Number(value)

    const safeValue =
        Number.isFinite(number)
            ? number
            : 0

    return `৳ ${safeValue.toLocaleString(
        'en-BD',
        {
            minimumFractionDigits: 2,
            maximumFractionDigits: 2,
        }
    )}`
}


function isValidDateInput(value)
{
    if (
        !/^\d{4}-\d{2}-\d{2}$/.test(
            String(value || '')
        )
    ) {
        return false
    }

    const [
        year,
        month,
        day,
    ] =
        String(value)
            .split('-')
            .map(Number)

    const date =
        new Date(
            year,
            month - 1,
            day
        )

    return (
        date.getFullYear() === year
        &&
        date.getMonth() ===
            month - 1
        &&
        date.getDate() === day
    )
}

function todayDate()
{
    const date =
        new Date()

    const timezoneOffset =
        date.getTimezoneOffset()
        *
        60000

    return new Date(
        date.getTime()
        -
        timezoneOffset
    )
        .toISOString()
        .slice(0, 10)
}


function toDateInput(value)
{
    if (!value) {
        return ''
    }

    const stringValue =
        String(value)

    const directDate =
        stringValue.match(
            /^\d{4}-\d{2}-\d{2}/
        )

    if (directDate) {
        return directDate[0]
    }

    const date =
        new Date(value)

    if (
        Number.isNaN(
            date.getTime()
        )
    ) {
        return ''
    }

    return date
        .toISOString()
        .slice(0, 10)
}


/*
|--------------------------------------------------------------------------
| Modal Focus / Keyboard / Scroll Lock
|--------------------------------------------------------------------------
*/

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
                    'input:not([disabled]):not([readonly])',
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

    if (event.key === 'Escape') {

        event.preventDefault()

        closeModal()

        return
    }

    if (event.key !== 'Tab') {
        return
    }

    const focusable =
        focusableElements()

    if (focusable.length === 0) {
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

    document.body.style.overflow =
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

    document.body.style.overflow =
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
        document.contains(
            target
        )
    ) {
        target.focus()
    }
}


watch(
    [
        () => props.show,
        () => props.order,
    ],

    async (
        [
            visible,
        ],
        [
            previousVisible,
        ] = []
    ) => {

        if (visible) {

            if (!previousVisible) {

                previouslyFocusedElement =
                    document.activeElement

                lockBodyScroll()

            }

            initializeForm()

            await nextTick()

            if (
                !formDisabled.value
            ) {

                supplierSelectRef.value
                    ?.focus()

            }
            else {

                closeButtonRef.value
                    ?.focus()

            }

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
