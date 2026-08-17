<template>
    <section class="purchase-order-section">

        <!-- Toast Message -->

        <Transition name="po-toast">

            <div
                v-if="toast.show"
                class="po-toast-message"
                :class="`po-toast-${toast.type}`"
            >

                <i
                    class="bi"
                    :class="toastIcon"
                ></i>

                <span>
                    {{ toast.message }}
                </span>

                <button
                    type="button"
                    aria-label="Close notification"
                    @click="hideToast"
                >
                    <i class="bi bi-x-lg"></i>
                </button>

            </div>

        </Transition>


        <!-- Main Header -->

        <PurchaseOrderHeader
            @refresh="refreshPage"
            @add="openAddPurchaseOrder"
        />


        <!-- Purchase Order Details -->

        <PurchaseOrderViewModal
            v-if="showViewPanel"
            :order="selectedOrder"
            :loading="detailsLoading"
            :can-manage="canManagePurchase"
            @close="closeViewPanel"
            @edit="openEditFromDetails"
            @changed="handlePurchaseDetailsChanged"
            @received="handlePurchaseReceived"
            @receive-error="handlePurchaseReceiveError"
        />


        <!-- Purchase Order List Area -->

        <template v-else>

            <!-- Filters -->

            <PurchaseOrderFilters
                v-model:filters="filters"
                :suppliers="suppliers"
                @apply="applyFilters"
                @clear="clearFilters"
            />


            <!-- Table -->

            <PurchaseOrderTable
                :orders="purchaseOrders"
                :loading="loading"
                :meta="meta"
                :status-loading-id="statusLoadingId"
                :delete-loading-id="deletingId"
                :can-manage="canManagePurchase"
                @view="openViewPurchaseOrder"
                @edit="openEditPurchaseOrder"
                @delete="deletePurchaseOrder"
                @page-change="changePage"
                @status-change="updatePurchaseOrderStatus"
                @received="handlePurchaseReceived"
                @receive-error="handlePurchaseReceiveError"
            />

        </template>


        <!-- Add / Edit Modal -->

<PurchaseOrderFormModal
    :show="showFormModal"
    :order="selectedOrder"
    :suppliers="suppliers"
    :raw-materials="rawMaterials"
    :raw-materials-loading="rawMaterialsLoading"
    :loading="saving"
    @close="closeFormModal"
    @submit="savePurchaseOrder"
/>

    </section>
</template>


<script setup>
import {
    computed,
    onBeforeUnmount,
    onMounted,
    reactive,
    ref,
} from 'vue'

import {
    useAuthStore,
} from '@/stores/auth'

import purchaseOrderService
    from '@/services/purchaseOrderService'

import PurchaseOrderHeader
    from './PurchaseOrderHeader.vue'

import PurchaseOrderFilters
    from './PurchaseOrderFilters.vue'

import PurchaseOrderTable
    from './PurchaseOrderTable.vue'

import PurchaseOrderFormModal
    from './PurchaseOrderFormModal.vue'

import PurchaseOrderViewModal
    from './PurchaseOrderViewModal.vue'
import inventoryService
    from '@/services/inventoryService'


/*
|--------------------------------------------------------------------------
| Auth
|--------------------------------------------------------------------------
*/

const authStore =
    useAuthStore()


const canManagePurchase = computed(() => {

    const currentUser =
        authStore.user
        ??
        authStore.currentUser
        ??
        null

    const roleName =
        String(
            currentUser?.role?.name
            ??
            currentUser?.role_name
            ??
            ''
        )
            .trim()
            .toLowerCase()

    if (
        [
            'admin',
            'manager',
        ].includes(roleName)
    ) {
        return true
    }

    if (
        typeof authStore.hasPermission
        ===
        'function'
    ) {
        return (
            authStore.hasPermission(
                'inventory.manage'
            )
            ||
            authStore.hasPermission(
                'suppliers.manage'
            )
        )
    }

    return false
})


/*
|--------------------------------------------------------------------------
| Main State
|--------------------------------------------------------------------------
*/

const statusLoadingId =
    ref(null)

const purchaseOrders =
    ref([])

const suppliers =
    ref([])

const meta =
    ref({
        current_page: 1,
        last_page: 1,
        per_page: 10,
        total: 0,
    })
const rawMaterials =
    ref([])

const rawMaterialsLoading =
    ref(false)

const loading =
    ref(false)

const saving =
    ref(false)

const detailsLoading =
    ref(false)

const deletingId =
    ref(null)

const showFormModal =
    ref(false)

const showViewPanel =
    ref(false)

const selectedOrder =
    ref(null)

let toastTimer = null


/*
|--------------------------------------------------------------------------
| Toast
|--------------------------------------------------------------------------
*/

const toast = reactive({

    show: false,

    type: 'success',

    message: '',

})


const toastIcon = computed(() => {

    if (
        toast.type === 'error'
    ) {
        return 'bi-exclamation-circle'
    }

    if (
        toast.type === 'warning'
    ) {
        return 'bi-exclamation-triangle'
    }

    return 'bi-check-circle'
})


function showToast(
    message,
    type = 'success'
) {
    if (toastTimer) {
        clearTimeout(
            toastTimer
        )
    }

    toast.message =
        message

    toast.type =
        type

    toast.show =
        true

    toastTimer =
        setTimeout(
            () => {
                toast.show =
                    false
            },
            4000
        )
}


function hideToast()
{
    toast.show =
        false

    if (toastTimer) {
        clearTimeout(
            toastTimer
        )

        toastTimer =
            null
    }
}


/*
|--------------------------------------------------------------------------
| Filters
|--------------------------------------------------------------------------
*/

const filters = reactive({

    date_from: '',

    date_to: '',

    supplier_id: '',

    status: '',

    page: 1,

    per_page: 10,

})


/*
|--------------------------------------------------------------------------
| Response Helpers
|--------------------------------------------------------------------------
*/

function extractList(response)
{
    if (
        Array.isArray(
            response?.data?.data
        )
    ) {
        return response.data.data
    }

    if (
        Array.isArray(
            response?.data
        )
    ) {
        return response.data
    }

    if (
        Array.isArray(
            response
        )
    ) {
        return response
    }

    return []
}


function extractSingle(response)
{
    return (
        response?.data?.data
        ??
        response?.data?.purchase_order
        ??
        response?.data
        ??
        response?.purchase_order
        ??
        response
        ??
        null
    )
}


function extractMeta(response)
{
    return (
        response?.meta
        ??
        response?.data?.meta
        ??
        response?.data?.data?.meta
        ??
        {
            current_page: 1,
            last_page: 1,
            per_page:
                filters.per_page,
            total: 0,
        }
    )
}


function getErrorMessage(
    error,
    fallback = 'Something went wrong.'
) {
    const errors =
        error?.response?.data?.errors

    if (
        errors
        &&
        typeof errors === 'object'
    ) {
        const message =
            Object.values(errors)
                .flat()
                .filter(
                    (item) =>
                        typeof item ===
                        'string'
                )
                .join(' ')

        if (message) {
            return message
        }
    }

    return (
        error?.response?.data?.message
        ??
        error?.message
        ??
        fallback
    )
}


/*
|--------------------------------------------------------------------------
| Purchase Status Helpers
|--------------------------------------------------------------------------
*/

function getPurchaseStatus(order)
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


function receiveHasStarted(order)
{
    return [
        'partially_received',
        'received',
    ].includes(
        getPurchaseStatus(order)
    )
}


function getStatusLabel(status)
{
    const labels = {

        ordered:
            'Ordered',

        partially_received:
            'Partially Received',

        received:
            'Received',

        cancelled:
            'Cancelled',

    }

    return (
        labels[status]
        ||
        status
    )
}


/*
|--------------------------------------------------------------------------
| Update Purchase Order Status
|--------------------------------------------------------------------------
*/

async function updatePurchaseOrderStatus(
    {
        order,
        status,
    }
) {
    if (
        !order?.id
        ||
        !status
        ||
        statusLoadingId.value
    ) {
        return
    }

    /*
    |--------------------------------------------------------------------------
    | Receive Status Cannot Be Changed Manually
    |--------------------------------------------------------------------------
    */

    if (
        [
            'partially_received',
            'received',
        ].includes(status)
    ) {
        showToast(
            'Partially Received and Received status can only be updated through the receive process.',
            'warning'
        )

        await loadPurchaseOrders()

        return
    }

    if (
        receiveHasStarted(order)
    ) {
        showToast(
            'Purchase order status cannot be changed because receiving has already started.',
            'warning'
        )

        await loadPurchaseOrders()

        return
    }

    const previousStatus =
        getPurchaseStatus(order)

    const previousStatusLabel =
        order.status_label

    statusLoadingId.value =
        order.id

    order.status =
        status

    order.status_label =
        getStatusLabel(status)

    try {

        await purchaseOrderService
            .updatePurchaseOrderStatus(
                order.id,
                status
            )

        showToast(
            'Purchase order status updated successfully.'
        )

        await loadPurchaseOrders()

    }

    catch (error) {

        order.status =
            previousStatus

        order.status_label =
            previousStatusLabel

        showToast(
            getErrorMessage(
                error,
                'Purchase order status update failed.'
            ),
            'error'
        )

    }

    finally {

        statusLoadingId.value =
            null

    }
}


/*
|--------------------------------------------------------------------------
| Load Purchase Orders
|--------------------------------------------------------------------------
*/

async function loadPurchaseOrders()
{
    if (loading.value) {
        return
    }

    loading.value =
        true

    try {

        const response =
            await purchaseOrderService
                .getPurchaseOrders({

                    date_from:
                        filters.date_from
                        ||
                        undefined,

                    date_to:
                        filters.date_to
                        ||
                        undefined,

                    supplier_id:
                        filters.supplier_id
                        ||
                        undefined,

                    status:
                        filters.status
                        ||
                        undefined,

                    page:
                        filters.page,

                    per_page:
                        filters.per_page,

                })

        purchaseOrders.value =
            extractList(response)

        meta.value =
            extractMeta(response)

    }

    catch (error) {

        purchaseOrders.value =
            []

        showToast(
            getErrorMessage(
                error,
                'Unable to load purchase orders.'
            ),
            'error'
        )

    }

    finally {

        loading.value =
            false

    }
}


/*
|--------------------------------------------------------------------------
| Load Suppliers
|--------------------------------------------------------------------------
*/

async function loadSuppliers()
{
    try {

        const response =
            await purchaseOrderService
                .getSuppliers()

        suppliers.value =
            extractList(response)

    }

    catch (error) {

        suppliers.value =
            []

        showToast(
            getErrorMessage(
                error,
                'Unable to load suppliers.'
            ),
            'error'
        )

    }
}

/*
|--------------------------------------------------------------------------
| Load Active Raw Materials
|--------------------------------------------------------------------------
*/

async function loadRawMaterials()
{
    if (rawMaterialsLoading.value) {
        return
    }

    rawMaterialsLoading.value =
        true

    try {

        const response =
            await inventoryService
                .getRawMaterials({

                    status:
                        'active',

                    per_page:
                        100,

                    sort_by:
                        'material_name',

                    sort_direction:
                        'asc',

                })

        const materials =
            response?.data?.data
            ??
            response?.data
            ??
            response
            ??
            []

        rawMaterials.value =
            Array.isArray(materials)
                ? materials.filter(
                    (material) => {

                        return (
                            material
                            &&
                            material.id
                            &&
                            material.is_active !== false
                        )

                    }
                )
                : []

    }

    catch (error) {

        rawMaterials.value =
            []

        showToast(
            getErrorMessage(
                error,
                'Unable to load raw materials.'
            ),
            'error'
        )

    }

    finally {

        rawMaterialsLoading.value =
            false

    }
}


/*
|--------------------------------------------------------------------------
| Refresh
|--------------------------------------------------------------------------
*/

async function refreshPage()
{
    if (
        showViewPanel.value
        &&
        selectedOrder.value?.id
    ) {
        await loadSingleOrder(
            selectedOrder.value.id
        )

        showToast(
            'Purchase order details refreshed.'
        )

        return
    }

    await Promise.all([

        loadPurchaseOrders(),

        loadSuppliers(),

    ])

    showToast(
        'Purchase orders refreshed.'
    )
}


/*
|--------------------------------------------------------------------------
| Apply Filters
|--------------------------------------------------------------------------
*/

async function applyFilters()
{
    filters.page =
        1

    await loadPurchaseOrders()
}


async function clearFilters()
{
    filters.date_from =
        ''

    filters.date_to =
        ''

    filters.supplier_id =
        ''

    filters.status =
        ''

    filters.page =
        1

    await loadPurchaseOrders()
}


/*
|--------------------------------------------------------------------------
| Pagination
|--------------------------------------------------------------------------
*/

async function changePage(page)
{
    const currentPage =
        Number(
            meta.value.current_page
            ||
            1
        )

    const lastPage =
        Number(
            meta.value.last_page
            ||
            1
        )

    const targetPage =
        Number(page)

    if (
        targetPage < 1
        ||
        targetPage > lastPage
        ||
        targetPage === currentPage
    ) {
        return
    }

    filters.page =
        targetPage

    await loadPurchaseOrders()
}


/*
|--------------------------------------------------------------------------
| Load Single Purchase Order
|--------------------------------------------------------------------------
*/

async function loadSingleOrder(id)
{
    if (!id) {
        return false
    }

    detailsLoading.value =
        true

    try {

        const response =
            await purchaseOrderService
                .getPurchaseOrder(id)

        selectedOrder.value =
            extractSingle(response)

        return Boolean(
            selectedOrder.value
        )

    }

    catch (error) {

        showToast(
            getErrorMessage(
                error,
                'Unable to load purchase order details.'
            ),
            'error'
        )

        return false

    }

    finally {

        detailsLoading.value =
            false

    }
}


/*
|--------------------------------------------------------------------------
| Add Purchase Order
|--------------------------------------------------------------------------
*/

async function openAddPurchaseOrder()
{
    if (
        !canManagePurchase.value
    ) {
        showToast(
            'You do not have permission to create a purchase order.',
            'warning'
        )

        return
    }

    if (
        rawMaterials.value.length === 0
    ) {
        await loadRawMaterials()
    }

    if (
        rawMaterials.value.length === 0
    ) {
        showToast(
            'No active raw materials are available. Create a raw material first.',
            'warning'
        )

        return
    }

    selectedOrder.value =
        null

    showViewPanel.value =
        false

    showFormModal.value =
        true
}


/*
|--------------------------------------------------------------------------
| View Purchase Order
|--------------------------------------------------------------------------
*/

async function openViewPurchaseOrder(order)
{
    if (!order?.id) {
        return
    }

    showFormModal.value =
        false

    showViewPanel.value =
        true

    selectedOrder.value =
        order

    const loaded =
        await loadSingleOrder(
            order.id
        )

    if (!loaded) {
        showViewPanel.value =
            false
    }

    window.scrollTo({
        top: 0,
        behavior: 'smooth',
    })
}


function closeViewPanel()
{
    showViewPanel.value =
        false

    selectedOrder.value =
        null

    window.scrollTo({
        top: 0,
        behavior: 'smooth',
    })
}


/*
|--------------------------------------------------------------------------
| Edit Purchase Order
|--------------------------------------------------------------------------
*/

async function openEditPurchaseOrder(order)
{
    if (
        !canManagePurchase.value
    ) {
        showToast(
            'You do not have permission to edit this purchase order.',
            'warning'
        )

        return
    }

    if (
        receiveHasStarted(order)
    ) {
        showToast(
            'Purchase order cannot be edited because receiving has already started.',
            'warning'
        )

        return
    }

    if (
        rawMaterials.value.length === 0
    ) {
        await loadRawMaterials()
    }

    const loaded =
        await loadSingleOrder(
            order.id
        )

    if (!loaded) {
        return
    }

    if (
        receiveHasStarted(
            selectedOrder.value
        )
    ) {
        showToast(
            'Purchase order cannot be edited because receiving has already started.',
            'warning'
        )

        selectedOrder.value =
            null

        return
    }

    showViewPanel.value =
        false

    showFormModal.value =
        true
}


function openEditFromDetails()
{
    if (
        !selectedOrder.value
    ) {
        return
    }

    if (
        !canManagePurchase.value
    ) {
        showToast(
            'You do not have permission to edit this purchase order.',
            'warning'
        )

        return
    }

    if (
        receiveHasStarted(
            selectedOrder.value
        )
    ) {
        showToast(
            'Purchase order cannot be edited because receiving has already started.',
            'warning'
        )

        return
    }

    showViewPanel.value =
        false

    showFormModal.value =
        true
}


/*
|--------------------------------------------------------------------------
| Close Form Modal
|--------------------------------------------------------------------------
*/

function closeFormModal()
{
    if (saving.value) {
        return
    }

    showFormModal.value =
        false

    selectedOrder.value =
        null
}


/*
|--------------------------------------------------------------------------
| Build Purchase Payload
|--------------------------------------------------------------------------
*/

function buildPayload(
    data,
    isEditMode = false
)
{
    const payload = {

        supplier_id:
            Number(
                data.supplier_id
            ),

        order_date:
            data.order_date,

        delivery_date:
            data.delivery_date
            ||
            null,

        tax:
            Number(
                data.tax || 0
            ),

        service_charge:
            Number(
                data.service_charge || 0
            ),

        notes:
            String(
                data.notes || ''
            ).trim()
            ||
            null,

        items:
            Array.isArray(
                data.items
            )
                ? data.items.map(
                    (item) => ({

                        raw_material_id:
                            Number(
                                item.raw_material_id
                                ||
                                item.raw_material?.id
                                ||
                                0
                            ),

                        item_name:
                            String(
                                item.item_name || ''
                            ).trim(),

                        unit:
                            String(
                                item.unit || ''
                            )
                                .trim()
                                .toLowerCase(),

                        quantity:
                            Number(
                                item.quantity || 0
                            ),

                        unit_price:
                            Number(
                                item.unit_price || 0
                            ),

                    })
                )
                : [],

    }

    /*
    |--------------------------------------------------------------------------
    | Initial Payment Is Create-Only
    |--------------------------------------------------------------------------
    |
    | paid_amount / payment_method are payment-ledger fields.
    | They must not be sent through the normal PO update endpoint.
    |
    */

    if (!isEditMode) {

        payload.paid_amount =
            Number(
                data.paid_amount || 0
            )

        payload.payment_method =
            data.payment_method
            ||
            null

    }

    return payload
}


/*
|--------------------------------------------------------------------------
| Validate Purchase Payload
|--------------------------------------------------------------------------
*/

function validatePayload(payload)
{
    if (
        !payload.supplier_id
    ) {
        return 'Please select a supplier.'
    }

    if (
        !payload.order_date
    ) {
        return 'Please select the order date.'
    }

    if (
        payload.delivery_date
        &&
        payload.delivery_date
        <
        payload.order_date
    ) {
        return 'Delivery date cannot be before order date.'
    }

    if (
        payload.items.length === 0
    ) {
        return 'Please add at least one item.'
    }

    const selectedMaterialIds =
        new Set()

    for (
        let index = 0;
        index < payload.items.length;
        index += 1
    ) {
        const item =
            payload.items[index]

        const row =
            index + 1

        if (
            !item.raw_material_id
        ) {
            return `Please select a raw material in row ${row}.`
        }

        if (
            selectedMaterialIds.has(
                item.raw_material_id
            )
        ) {
            return `The same raw material cannot be added more than once. Check row ${row}.`
        }

        selectedMaterialIds.add(
            item.raw_material_id
        )

        if (
            !item.item_name
        ) {
            return `Item name is missing in row ${row}.`
        }

        if (
            !item.unit
        ) {
            return `Purchase unit is missing in row ${row}.`
        }

        if (
            item.quantity <= 0
        ) {
            return `Quantity must be greater than zero in row ${row}.`
        }

        if (
            item.unit_price < 0
        ) {
            return `Unit price cannot be negative in row ${row}.`
        }
    }

    const subtotal =
        payload.items.reduce(
            (
                total,
                item
            ) => {

                return (
                    total
                    +
                    (
                        item.quantity
                        *
                        item.unit_price
                    )
                )

            },
            0
        )

    const total =
        subtotal
        +
        payload.tax
        +
        payload.service_charge

    if (
        Object.prototype.hasOwnProperty.call(
            payload,
            'paid_amount'
        )
    ) {

        if (
            Number(
                payload.paid_amount || 0
            ) < 0
        ) {
            return 'Paid amount cannot be negative.'
        }

        if (
            Number(
                payload.paid_amount || 0
            )
            >
            total
        ) {
            return 'Paid amount cannot be greater than total amount.'
        }

        if (
            Number(
                payload.paid_amount || 0
            ) > 0
            &&
            !payload.payment_method
        ) {
            return 'Please select a payment method for the advance payment.'
        }

    }

    return null
}


/*
|--------------------------------------------------------------------------
| Save Purchase Order
|--------------------------------------------------------------------------
*/

async function savePurchaseOrder(data)
{
    if (
        saving.value
        ||
        !canManagePurchase.value
    ) {
        return
    }

    const isEditMode =
        Boolean(
            selectedOrder.value?.id
        )

    if (
        isEditMode
        &&
        receiveHasStarted(
            selectedOrder.value
        )
    ) {
        showToast(
            'Purchase order cannot be edited because receiving has already started.',
            'warning'
        )

        return
    }

    const orderId =
        selectedOrder.value?.id

    const payload =
        buildPayload(
            data,
            isEditMode
        )

    const validationMessage =
        validatePayload(payload)

    if (validationMessage) {

        showToast(
            validationMessage,
            'warning'
        )

        return
    }

    saving.value =
        true

    try {

        if (isEditMode) {

            await purchaseOrderService
                .updatePurchaseOrder(
                    orderId,
                    payload
                )

        }

        else {

            await purchaseOrderService
                .createPurchaseOrder(
                    payload
                )

        }

        showFormModal.value =
            false

        selectedOrder.value =
            null

        filters.page =
            1

        await loadPurchaseOrders()

        showToast(
            isEditMode
                ? 'Purchase order updated successfully.'
                : 'Purchase order created successfully.'
        )

    }

    catch (error) {

        showToast(
            getErrorMessage(
                error,
                isEditMode
                    ? 'Purchase order update failed.'
                    : 'Purchase order creation failed.'
            ),
            'error'
        )

    }

    finally {

        saving.value =
            false

    }
}


/*
|--------------------------------------------------------------------------
| Purchase Details Changed
|--------------------------------------------------------------------------
|
| Used after recording a payment from the details page.
|
*/

async function handlePurchaseDetailsChanged(result = {})
{
    const purchaseOrderId =
        result?.purchaseOrderId
        ??
        selectedOrder.value?.id
        ??
        null

    await loadPurchaseOrders()

    if (
        showViewPanel.value
        &&
        purchaseOrderId
    ) {
        await loadSingleOrder(
            purchaseOrderId
        )
    }

    showToast(
        result?.message
        ||
        'Purchase order updated successfully.'
    )
}


/*
|--------------------------------------------------------------------------
| Purchase Receive Success
|--------------------------------------------------------------------------
*/

async function handlePurchaseReceived(result)
{
    const purchaseOrderId =
        result?.purchaseOrderId
        ??
        result?.purchaseOrder?.id
        ??
        null

    /*
    |--------------------------------------------------------------------------
    | Reload Purchase List
    |--------------------------------------------------------------------------
    */

    await loadPurchaseOrders()

    /*
    |--------------------------------------------------------------------------
    | Refresh Open Detail Panel
    |--------------------------------------------------------------------------
    */

    if (
        showViewPanel.value
        &&
        selectedOrder.value?.id
        &&
        String(
            selectedOrder.value.id
        )
        ===
        String(
            purchaseOrderId
        )
    ) {
        await loadSingleOrder(
            purchaseOrderId
        )
    }

    showToast(
        result?.message
        ||
        'Purchase order received successfully.'
    )
}


function handlePurchaseReceiveError(result)
{
    showToast(
        result?.message
        ||
        'Unable to open purchase receive form.',
        'error'
    )
}


/*
|--------------------------------------------------------------------------
| Delete Purchase Order
|--------------------------------------------------------------------------
*/

async function deletePurchaseOrder(order)
{
    if (
        !order?.id
        ||
        deletingId.value
        ||
        !canManagePurchase.value
    ) {
        return
    }

    if (
        receiveHasStarted(order)
    ) {
        showToast(
            'Purchase order cannot be deleted because receiving has already started.',
            'warning'
        )

        return
    }

    const supplierName =
        order.supplier_name
        ||
        order.supplier?.company_name
        ||
        order.supplier?.supplier_name
        ||
        order.supplier?.name
        ||
        'this supplier'

    const confirmed =
        window.confirm(
            `Are you sure you want to delete the purchase order of ${supplierName}?`
        )

    if (!confirmed) {
        return
    }

    deletingId.value =
        order.id

    try {

        await purchaseOrderService
            .deletePurchaseOrder(
                order.id
            )

        if (
            purchaseOrders.value.length === 1
            &&
            filters.page > 1
        ) {
            filters.page -=
                1
        }

        await loadPurchaseOrders()

        showToast(
            'Purchase order deleted successfully.'
        )

    }

    catch (error) {

        showToast(
            getErrorMessage(
                error,
                'Purchase order deletion failed.'
            ),
            'error'
        )

    }

    finally {

        deletingId.value =
            null

    }
}


/*
|--------------------------------------------------------------------------
| Mounted
|--------------------------------------------------------------------------
*/

onMounted(async () => {

    await Promise.all([

        loadPurchaseOrders(),

        loadSuppliers(),

        loadRawMaterials(),

    ])

})


/*
|--------------------------------------------------------------------------
| Unmount
|--------------------------------------------------------------------------
*/

onBeforeUnmount(() => {

    if (toastTimer) {
        clearTimeout(
            toastTimer
        )
    }

})
</script>


<style>
@import '@/assets/css/purchaseOrders/purchase-order-header.css';

@import '@/assets/css/purchaseOrders/purchase-order-filter.css';

@import '@/assets/css/purchaseOrders/purchase-order-table.css';

@import '@/assets/css/purchaseOrders/purchase-order-modal.css';

@import '@/assets/css/purchaseOrders/purchase-order-view.css';

@import '@/assets/css/purchaseOrders/purchase-order-responsive.css';

@import '@/assets/css/purchaseOrders/purchase-receive.css';

@import '@/assets/css/purchaseOrders/purchase-order-raw-material.css';
</style>