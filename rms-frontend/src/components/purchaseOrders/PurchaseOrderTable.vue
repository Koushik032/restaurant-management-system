<template>
    <section
        class="purchase-order-table-card"
        :aria-busy="loading"
    >
        <!-- Table Header -->

        <div class="purchase-order-table-header">
            <div>
                <h3>
                    Purchase Order List
                </h3>

                <p>
                    View and manage supplier purchase orders
                </p>
            </div>
        </div>


        <!-- Responsive Table -->

        <div class="table-responsive">
            <table class="purchase-order-table">
                <caption class="visually-hidden">
                    Supplier purchase orders and available actions
                </caption>

                <thead>
                    <tr>
                        <th scope="col">
                            SL No
                        </th>

                        <th scope="col">
                            Supplier Name
                        </th>

                        <th scope="col">
                            Purchase Item
                        </th>

                        <th scope="col">
                            Amount
                        </th>

                        <th scope="col">
                            Status
                        </th>

                        <th scope="col">
                            Action
                        </th>
                    </tr>
                </thead>


                <tbody>
                    <!-- Loading -->

                    <tr v-if="loading">
                        <td
                            colspan="6"
                            class="po-loading"
                            role="status"
                            aria-live="polite"
                        >
                            Loading purchase orders...
                        </td>
                    </tr>


                    <!-- Empty -->

                    <tr
                        v-else-if="
                            safeOrders.length === 0
                        "
                    >
                        <td
                            colspan="6"
                            class="po-empty"
                        >
                            No purchase orders found.
                        </td>
                    </tr>


                    <!-- Purchase Orders -->

                    <tr
                        v-for="(
                            order,
                            index
                        ) in safeOrders"
                        v-else
                        :key="
                            orderRowKey(
                                order,
                                index
                            )
                        "
                    >
                        <!-- Serial -->

                        <td>
                            {{
                                getSerialNumber(
                                    index
                                )
                            }}
                        </td>


                        <!-- Supplier -->

                        <td>
                            <strong>
                                {{
                                    supplierName(
                                        order
                                    )
                                }}
                            </strong>
                        </td>


                        <!-- Purchase Items -->

                        <td>
                            <div class="item-list">
                                <span
                                    v-for="(
                                        item,
                                        itemIndex
                                    ) in getOrderItems(
                                        order
                                    )"
                                    :key="
                                        orderItemKey(
                                            order,
                                            item,
                                            itemIndex
                                        )
                                    "
                                >
                                    {{
                                        itemName(
                                            item
                                        )
                                    }}

                                    <small
                                        v-if="
                                            hasQuantityValue(
                                                item
                                            )
                                        "
                                    >
                                        (
                                        {{
                                            itemQuantityDisplay(
                                                item
                                            )
                                        }}
                                        )
                                    </small>
                                </span>

                                <span
                                    v-if="
                                        getOrderItems(
                                            order
                                        ).length === 0
                                    "
                                >
                                    —
                                </span>
                            </div>
                        </td>


                        <!-- Amount -->

                        <td>
                            <strong>
                                {{
                                    orderAmountDisplay(
                                        order
                                    )
                                }}
                            </strong>
                        </td>


                        <!-- Status -->

                        <td>
                            <div class="po-status-control">
                                <select
                                    class="po-status-select"
                                    :class="
                                        `po-status-select-${statusCssValue(order)}`
                                    "
                                    :value="
                                        getStatusValue(
                                            order
                                        )
                                    "
                                    :disabled="
                                        !canManage
                                        ||
                                        isStatusLoading(
                                            order
                                        )
                                        ||
                                        isStatusLocked(
                                            order
                                        )
                                        ||
                                        isDeleteLoading(
                                            order
                                        )
                                    "
                                    :title="
                                        getStatusSelectTitle(
                                            order
                                        )
                                    "
                                    :aria-label="
                                        statusAriaLabel(
                                            order
                                        )
                                    "
                                    @change="
                                        changeStatus(
                                            order,
                                            $event.target.value
                                        )
                                    "
                                >
                                    <option
                                        v-if="
                                            hasUnknownStatus(
                                                order
                                            )
                                        "
                                        :value="
                                            getStatusValue(
                                                order
                                            )
                                        "
                                        disabled
                                    >
                                        Unknown Status
                                    </option>

                                    <option value="ordered">
                                        Ordered
                                    </option>

                                    <!--
                                        Partially Received and Received
                                        are controlled by the receive flow.
                                    -->

                                    <option
                                        value="partially_received"
                                        disabled
                                    >
                                        Partially Received
                                    </option>

                                    <option
                                        value="received"
                                        disabled
                                    >
                                        Received
                                    </option>

                                    <option value="cancelled">
                                        Cancelled
                                    </option>
                                </select>


                                <span
                                    v-if="
                                        isStatusLoading(
                                            order
                                        )
                                    "
                                    class="po-status-loading"
                                    role="status"
                                    aria-label="Updating purchase order status"
                                >
                                    <i
                                        class="bi bi-arrow-repeat inventory-refresh-spin"
                                        aria-hidden="true"
                                    ></i>
                                </span>
                            </div>
                        </td>


                        <!-- Actions -->

                        <td>
                            <div class="purchase-order-actions">
                                <!-- View -->

                                <button
                                    type="button"
                                    class="action-btn view-btn"
                                    title="View purchase order"
                                    :aria-label="
                                        viewAriaLabel(
                                            order
                                        )
                                    "
                                    :disabled="
                                        isDeleteLoading(
                                            order
                                        )
                                    "
                                    @click="
                                        viewOrder(
                                            order
                                        )
                                    "
                                >
                                    <i
                                        class="bi bi-eye"
                                        aria-hidden="true"
                                    ></i>
                                </button>


                                <!-- Receive / Receive More / Received -->

                                <PurchaseReceiveAction
                                    :purchase-order="order"
                                    :can-manage="
                                        canManage
                                        &&
                                        !isRowMutationLoading(
                                            order
                                        )
                                    "
                                    @received="
                                        handlePurchaseReceived
                                    "
                                    @error="
                                        handlePurchaseReceiveError
                                    "
                                />


                                <!-- Edit -->

                                <button
                                    v-if="canManage"
                                    type="button"
                                    class="action-btn edit-btn"
                                    :title="
                                        editButtonTitle(
                                            order
                                        )
                                    "
                                    :aria-label="
                                        editAriaLabel(
                                            order
                                        )
                                    "
                                    :disabled="
                                        !canEditOrder(
                                            order
                                        )
                                        ||
                                        isRowMutationLoading(
                                            order
                                        )
                                    "
                                    @click="
                                        editOrder(
                                            order
                                        )
                                    "
                                >
                                    <i
                                        class="bi bi-pencil"
                                        aria-hidden="true"
                                    ></i>
                                </button>


                                <!-- Delete -->

                                <button
                                    v-if="canManage"
                                    type="button"
                                    class="action-btn delete-btn"
                                    :title="
                                        deleteButtonTitle(
                                            order
                                        )
                                    "
                                    :aria-label="
                                        deleteAriaLabel(
                                            order
                                        )
                                    "
                                    :disabled="
                                        isReceiveStarted(
                                            order
                                        )
                                        ||
                                        isStatusLoading(
                                            order
                                        )
                                        ||
                                        isDeleteLoading(
                                            order
                                        )
                                    "
                                    @click="
                                        deleteOrder(
                                            order
                                        )
                                    "
                                >
                                    <span
                                        v-if="
                                            isDeleteLoading(
                                                order
                                            )
                                        "
                                        class="spinner-border spinner-border-sm"
                                        aria-hidden="true"
                                    ></span>

                                    <i
                                        v-else
                                        class="bi bi-trash"
                                        aria-hidden="true"
                                    ></i>
                                </button>
                            </div>
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>


        <!-- Pagination -->

        <div
            v-if="totalPages > 1"
            class="purchase-order-pagination"
            aria-label="Purchase order pagination"
        >
            <button
                type="button"
                :disabled="
                    loading
                    ||
                    currentPage <= 1
                "
                aria-label="Go to previous purchase order page"
                @click="
                    changePage(
                        currentPage - 1
                    )
                "
            >
                Previous
            </button>

            <span
                aria-live="polite"
            >
                Page
                {{ currentPage }}
                of
                {{ totalPages }}
            </span>

            <button
                type="button"
                :disabled="
                    loading
                    ||
                    currentPage >= totalPages
                "
                aria-label="Go to next purchase order page"
                @click="
                    changePage(
                        currentPage + 1
                    )
                "
            >
                Next
            </button>
        </div>
    </section>
</template>


<script setup>
import {
    computed,
} from 'vue'


import PurchaseReceiveAction
    from './PurchaseReceiveAction.vue'


/*
|--------------------------------------------------------------------------
| Props / Emits
|--------------------------------------------------------------------------
*/


const props = defineProps({
    orders: {
        type: Array,
        default: () => [],
    },


    loading: {
        type: Boolean,
        default: false,
    },


    meta: {
        type: Object,
        default: () => ({}),
    },


    statusLoadingId: {
        type: [
            Number,
            String,
        ],

        default: null,
    },


    deleteLoadingId: {
        type: [
            Number,
            String,
        ],

        default: null,
    },


    canManage: {
        type: Boolean,
        default: false,
    },
})


const emit = defineEmits([
    'view',
    'edit',
    'delete',
    'page-change',
    'status-change',
    'received',
    'receive-error',
])


/*
|--------------------------------------------------------------------------
| Normalized Orders
|--------------------------------------------------------------------------
*/


const safeOrders = computed(() => {

    return Array.isArray(
        props.orders
    )
        ? props.orders.filter(
            (order) =>
                order
                &&
                typeof order ===
                    'object'
        )
        : []

})


/*
|--------------------------------------------------------------------------
| Pagination
|--------------------------------------------------------------------------
*/


const totalPages = computed(() => {

    return normalizePositiveInteger(
        props.meta?.last_page,
        1
    )

})


const currentPage = computed(() => {

    return Math.min(
        normalizePositiveInteger(
            props.meta?.current_page,
            1
        ),
        totalPages.value
    )

})


const perPage = computed(() => {

    return normalizePositiveInteger(
        props.meta?.per_page,
        10
    )

})


function getSerialNumber(index)
{
    return (
        (
            currentPage.value - 1
        )
        *
        perPage.value
    )
    +
    index
    +
    1
}


/*
|--------------------------------------------------------------------------
| Purchase Item Helpers
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
        ? items.filter(
            (item) =>
                item
                &&
                typeof item ===
                    'object'
        )
        : []
}


function itemName(item)
{
    const name =
        String(
            item?.item_name
            ??
            item?.raw_material
                ?.material_name
            ??
            ''
        ).trim()

    return name
        ||
        'Unknown Item'
}


function hasQuantityValue(item)
{
    return !(
        item?.quantity === null
        ||
        item?.quantity === undefined
        ||
        item?.quantity === ''
    )
}


function itemQuantityDisplay(item)
{
    const formatted =
        item?.quantity_formatted

    if (
        typeof formatted ===
            'string'
        &&
        formatted.trim() !== ''
    ) {
        return formatted
    }

    const unit =
        String(
            item?.unit
            ??
            item?.raw_material
                ?.base_unit
            ??
            ''
        ).trim()

    return formatQuantity(
        item?.quantity,
        unit
    )
}


/*
|--------------------------------------------------------------------------
| Supplier / Amount
|--------------------------------------------------------------------------
*/


function supplierName(order)
{
    return (
        order?.supplier_name
        ||
        order?.supplier
            ?.company_name
        ||
        order?.supplier
            ?.supplier_name
        ||
        order?.supplier?.name
        ||
        'Unknown Supplier'
    )
}


function orderAmountDisplay(order)
{
    const formatted =
        order
            ?.total_amount_formatted

    if (
        typeof formatted ===
            'string'
        &&
        formatted.trim() !== ''
    ) {
        return formatted
    }

    return formatMoney(
        order?.total_amount
    )
}


/*
|--------------------------------------------------------------------------
| Status Helpers
|--------------------------------------------------------------------------
*/


const knownStatuses = [
    'ordered',
    'partially_received',
    'received',
    'cancelled',
]


function getStatusValue(order)
{
    const status =
        order?.status?.value
        ??
        order?.status
        ??
        ''

    return String(status)
        .trim()
        .toLowerCase()
}


function hasUnknownStatus(order)
{
    const status =
        getStatusValue(order)

    return (
        !status
        ||
        !knownStatuses.includes(
            status
        )
    )
}


function statusCssValue(order)
{
    const status =
        getStatusValue(order)

    return knownStatuses.includes(
        status
    )
        ? status
        : 'unknown'
}


function isReceiveStarted(order)
{
    return [
        'partially_received',
        'received',
    ].includes(
        getStatusValue(order)
    )
}


function isCancelled(order)
{
    return (
        getStatusValue(order)
        ===
        'cancelled'
    )
}


function canEditOrder(order)
{
    return (
        Boolean(
            order?.id
        )
        &&
        !isReceiveStarted(
            order
        )
        &&
        !isCancelled(
            order
        )
    )
}


function isStatusLocked(order)
{
    /*
    |--------------------------------------------------------------------------
    | Only an Ordered PO can be manually changed.
    |--------------------------------------------------------------------------
    |
    | Partially Received / Received belong to the receive process.
    | Cancelled is terminal.
    | Unknown status fails closed.
    |
    */

    return (
        getStatusValue(order)
        !==
        'ordered'
    )
}


function getStatusSelectTitle(order)
{
    const status =
        getStatusValue(order)

    if (
        status ===
        'partially_received'
    ) {
        return 'Receiving has started. Status is controlled by the receive process.'
    }

    if (
        status ===
        'received'
    ) {
        return 'This purchase order is fully received.'
    }

    if (
        status ===
        'cancelled'
    ) {
        return 'This purchase order is cancelled and its status is locked.'
    }

    if (
        status !==
        'ordered'
    ) {
        return 'Purchase order status is unavailable. Refresh the purchase order list.'
    }

    return 'Change purchase order status.'
}


function statusAriaLabel(order)
{
    const id =
        order?.id

    return id
        ? `Change status for purchase order ${id}`
        : 'Change purchase order status'
}


function isStatusLoading(order)
{
    if (
        props.statusLoadingId === null
        ||
        props.statusLoadingId === undefined
    ) {
        return false
    }

    return (
        String(
            props.statusLoadingId
        )
        ===
        String(
            order?.id
        )
    )
}


function isDeleteLoading(order)
{
    if (
        props.deleteLoadingId === null
        ||
        props.deleteLoadingId === undefined
    ) {
        return false
    }

    return (
        String(
            props.deleteLoadingId
        )
        ===
        String(
            order?.id
        )
    )
}


function isRowMutationLoading(order)
{
    return (
        isStatusLoading(order)
        ||
        isDeleteLoading(order)
    )
}


/*
|--------------------------------------------------------------------------
| Action Presentation
|--------------------------------------------------------------------------
*/


function editButtonTitle(order)
{
    if (
        isReceiveStarted(order)
    ) {
        return 'Purchase receiving has started. Editing is locked.'
    }

    if (
        isCancelled(order)
    ) {
        return 'A cancelled purchase order cannot be edited.'
    }

    return 'Edit purchase order'
}


function deleteButtonTitle(order)
{
    if (
        isReceiveStarted(order)
    ) {
        return 'Purchase receiving has started. Deletion is locked.'
    }

    if (
        isStatusLoading(order)
    ) {
        return 'Wait for the status update to finish before deleting this purchase order.'
    }

    return 'Delete purchase order'
}


function viewAriaLabel(order)
{
    return actionAriaLabel(
        'View',
        order
    )
}


function editAriaLabel(order)
{
    return actionAriaLabel(
        'Edit',
        order
    )
}


function deleteAriaLabel(order)
{
    return actionAriaLabel(
        'Delete',
        order
    )
}


function actionAriaLabel(
    action,
    order
)
{
    const id =
        order?.id

    return id
        ? `${action} purchase order ${id}`
        : `${action} purchase order`
}


/*
|--------------------------------------------------------------------------
| Events
|--------------------------------------------------------------------------
*/


function viewOrder(order)
{
    if (
        !order
        ||
        typeof order !==
            'object'
        ||
        !order.id
        ||
        isDeleteLoading(order)
    ) {
        return
    }

    emit(
        'view',
        order
    )
}


function editOrder(order)
{
    if (
        !props.canManage
        ||
        !canEditOrder(order)
        ||
        isRowMutationLoading(
            order
        )
    ) {
        return
    }

    emit(
        'edit',
        order
    )
}


function deleteOrder(order)
{
    if (
        !props.canManage
        ||
        !order?.id
        ||
        isReceiveStarted(
            order
        )
        ||
        isStatusLoading(
            order
        )
        ||
        isDeleteLoading(
            order
        )
    ) {
        return
    }

    emit(
        'delete',
        order
    )
}


function changePage(page)
{
    if (props.loading) {
        return
    }

    const targetPage =
        Number(page)

    if (
        !Number.isFinite(
            targetPage
        )
        ||
        !Number.isInteger(
            targetPage
        )
        ||
        targetPage < 1
        ||
        targetPage >
            totalPages.value
        ||
        targetPage ===
            currentPage.value
    ) {
        return
    }

    emit(
        'page-change',
        targetPage
    )
}


function changeStatus(
    order,
    status
) {
    if (
        !props.canManage
        ||
        !order?.id
        ||
        isRowMutationLoading(
            order
        )
        ||
        isStatusLocked(
            order
        )
    ) {
        return
    }

    const currentStatus =
        getStatusValue(order)

    const nextStatus =
        String(
            status
            ??
            ''
        )
            .trim()
            .toLowerCase()

    if (
        ![
            'ordered',
            'cancelled',
        ].includes(
            nextStatus
        )
        ||
        nextStatus ===
            currentStatus
    ) {
        return
    }

    emit(
        'status-change',
        {
            order,
            status:
                nextStatus,
        }
    )
}


function handlePurchaseReceived(result)
{
    emit(
        'received',
        result
    )
}


function handlePurchaseReceiveError(result)
{
    emit(
        'receive-error',
        result
    )
}


/*
|--------------------------------------------------------------------------
| Stable Keys
|--------------------------------------------------------------------------
*/


function orderRowKey(
    order,
    index
)
{
    const id =
        order?.id

    return (
        id !== null
        &&
        id !== undefined
        &&
        id !== ''
    )
        ? `purchase-order-${id}`
        : `purchase-order-row-${getSerialNumber(index)}`
}


function orderItemKey(
    order,
    item,
    index
)
{
    const itemId =
        item?.id

    if (
        itemId !== null
        &&
        itemId !== undefined
        &&
        itemId !== ''
    ) {
        return `purchase-item-${itemId}`
    }

    return [
        'purchase-item',
        order?.id
            ??
            'unknown-order',
        item?.raw_material_id
            ??
            'unknown-material',
        index,
    ].join('-')
}


/*
|--------------------------------------------------------------------------
| Formatters
|--------------------------------------------------------------------------
*/


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
        return unit
            ? `Not available ${unit}`
            : 'Not available'
    }

    return `${number.toLocaleString(
        'en-BD',
        {
            maximumFractionDigits: 4,
        }
    )} ${unit}`.trim()
}


function formatMoney(value)
{
    const number =
        Number(value)

    if (
        !Number.isFinite(number)
    ) {
        return 'Not available'
    }

    return `৳ ${number.toLocaleString(
        'en-BD',
        {
            minimumFractionDigits: 2,
            maximumFractionDigits: 2,
        }
    )}`
}


function normalizePositiveInteger(
    value,
    fallback
)
{
    const number =
        Number(value)

    if (
        !Number.isFinite(number)
        ||
        number < 1
    ) {
        return fallback
    }

    return Math.floor(
        number
    )
}
</script>
