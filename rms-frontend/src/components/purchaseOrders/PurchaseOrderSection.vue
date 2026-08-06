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
        @close="closeViewPanel"
        @edit="openEditFromDetails"
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
            @view="openViewPurchaseOrder"
            @edit="openEditPurchaseOrder"
            @delete="deletePurchaseOrder"
            @page-change="changePage"
            @status-change="updatePurchaseOrderStatus"
        />


    </template>



    <!-- Add / Edit Modal -->

    <PurchaseOrderFormModal
        :show="showFormModal"
        :order="selectedOrder"
        :suppliers="suppliers"
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


/*
|--------------------------------------------------------------------------
| State
|--------------------------------------------------------------------------
*/

const statusLoadingId = ref(null)

const purchaseOrders = ref([])

const suppliers = ref([])

const meta = ref({
    current_page: 1,
    last_page: 1,
    per_page: 10,
    total: 0,
})

const loading = ref(false)

const saving = ref(false)

const detailsLoading = ref(false)

const deletingId = ref(null)

const showFormModal = ref(false)

const showViewPanel = ref(false)

const selectedOrder = ref(null)

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

    if (toast.type === 'error') {
        return 'bi-exclamation-circle'
    }

    if (toast.type === 'warning') {
        return 'bi-exclamation-triangle'
    }

    return 'bi-check-circle'
})

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

    const previousStatus =
        order.status

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

        statusLoadingId.value = null

    }
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

    return labels[status] || status
}

function showToast(
    message,
    type = 'success'
) {
    if (toastTimer) {
        clearTimeout(toastTimer)
    }

    toast.message = message

    toast.type = type

    toast.show = true

    toastTimer = setTimeout(() => {

        toast.show = false

    }, 3000)
}


function hideToast()
{
    toast.show = false

    if (toastTimer) {
        clearTimeout(toastTimer)
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
    if (Array.isArray(response?.data?.data)) {
        return response.data.data
    }

    if (Array.isArray(response?.data)) {
        return response.data
    }

    if (Array.isArray(response)) {
        return response
    }

    return []
}


function extractSingle(response)
{
    return response?.data ?? response ?? null
}


function extractMeta(response)
{
    return (
        response?.meta
        ??
        response?.data?.meta
        ??
        {
            current_page: 1,
            last_page: 1,
            per_page: filters.per_page,
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

    if (errors) {

        return Object.values(errors)
            .flat()
            .join(' ')

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
| Load Purchase Orders
|--------------------------------------------------------------------------
*/

async function loadPurchaseOrders()
{
    loading.value = true

    try {

        const response =
            await purchaseOrderService
                .getPurchaseOrders({

                    date_from:
                        filters.date_from || undefined,

                    date_to:
                        filters.date_to || undefined,

                    supplier_id:
                        filters.supplier_id || undefined,

                    status:
                        filters.status || undefined,

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

        purchaseOrders.value = []

        showToast(
            getErrorMessage(
                error,
                'Unable to load purchase orders.'
            ),
            'error'
        )

    }

    finally {

        loading.value = false

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

        suppliers.value = []

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
| Refresh
|--------------------------------------------------------------------------
*/

async function refreshPage()
{
    if (showViewPanel.value && selectedOrder.value?.id) {

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
| Filters
|--------------------------------------------------------------------------
*/

async function applyFilters()
{
    filters.page = 1

    await loadPurchaseOrders()
}


async function clearFilters()
{
    filters.date_from = ''

    filters.date_to = ''

    filters.supplier_id = ''

    filters.status = ''

    filters.page = 1

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
        Number(meta.value.current_page || 1)

    const lastPage =
        Number(meta.value.last_page || 1)

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

    filters.page = targetPage

    await loadPurchaseOrders()
}


/*
|--------------------------------------------------------------------------
| Load Single Order
|--------------------------------------------------------------------------
*/

async function loadSingleOrder(id)
{
    detailsLoading.value = true

    try {

        const response =
            await purchaseOrderService
                .getPurchaseOrder(id)

        selectedOrder.value =
            extractSingle(response)

        return true

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

        detailsLoading.value = false

    }
}


/*
|--------------------------------------------------------------------------
| Add
|--------------------------------------------------------------------------
*/

function openAddPurchaseOrder()
{
    selectedOrder.value = null

    showViewPanel.value = false

    showFormModal.value = true
}


/*
|--------------------------------------------------------------------------
| View In Same Page
|--------------------------------------------------------------------------
*/

async function openViewPurchaseOrder(order)
{
    showFormModal.value = false

    showViewPanel.value = true

    selectedOrder.value = order

    const loaded =
        await loadSingleOrder(order.id)

    if (!loaded) {
        showViewPanel.value = false
    }

    window.scrollTo({
        top: 0,
        behavior: 'smooth',
    })
}


function closeViewPanel()
{
    showViewPanel.value = false

    selectedOrder.value = null

    window.scrollTo({
        top: 0,
        behavior: 'smooth',
    })
}


/*
|--------------------------------------------------------------------------
| Edit
|--------------------------------------------------------------------------
*/

async function openEditPurchaseOrder(order)
{
    const loaded =
        await loadSingleOrder(order.id)

    if (!loaded) {
        return
    }

    showViewPanel.value = false

    showFormModal.value = true
}


function openEditFromDetails()
{
    if (!selectedOrder.value) {
        return
    }

    showViewPanel.value = false

    showFormModal.value = true
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

    showFormModal.value = false

    selectedOrder.value = null
}


/*
|--------------------------------------------------------------------------
| Payload
|--------------------------------------------------------------------------
*/

function buildPayload(data)
{
    return {

        supplier_id:
            Number(data.supplier_id),

        order_date:
            data.order_date,

        delivery_date:
            data.delivery_date || null,

        status:
            data.status || 'ordered',

        tax:
            Number(data.tax || 0),

        service_charge:
            Number(data.service_charge || 0),

        paid_amount:
            Number(data.paid_amount || 0),

        payment_method:
            data.payment_method || null,

        notes:
            String(data.notes || '').trim() || null,

        items:
            Array.isArray(data.items)
                ? data.items.map((item) => ({

                    item_name:
                        String(
                            item.item_name || ''
                        ).trim(),

                    unit:
                        String(
                            item.unit || ''
                        ).trim(),

                    quantity:
                        Number(item.quantity || 0),

                    received_quantity:
                        Number(
                            item.received_quantity || 0
                        ),

                    unit_price:
                        Number(item.unit_price || 0),

                }))
                : [],

    }
}


/*
|--------------------------------------------------------------------------
| Validate
|--------------------------------------------------------------------------
*/

function validatePayload(payload)
{
    if (!payload.supplier_id) {
        return 'Please select a supplier.'
    }

    if (!payload.order_date) {
        return 'Please select the order date.'
    }

    if (
        payload.delivery_date
        &&
        payload.delivery_date < payload.order_date
    ) {
        return 'Delivery date cannot be before order date.'
    }

    if (payload.items.length === 0) {
        return 'Please add at least one item.'
    }

    for (
        let index = 0;
        index < payload.items.length;
        index += 1
    ) {
        const item =
            payload.items[index]

        const row =
            index + 1

        if (!item.item_name) {
            return `Please enter item name in row ${row}.`
        }

        if (!item.unit) {
            return `Please enter item unit in row ${row}.`
        }

        if (item.quantity <= 0) {
            return `Quantity must be greater than zero in row ${row}.`
        }

        if (item.unit_price <= 0) {
            return `Unit price must be greater than zero in row ${row}.`
        }

        if (
            item.received_quantity < 0
            ||
            item.received_quantity > item.quantity
        ) {
            return `Received quantity is invalid in row ${row}.`
        }
    }

    const subtotal =
        payload.items.reduce(
            (total, item) => {

                return total
                    +
                    (
                        item.quantity
                        *
                        item.unit_price
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

    if (payload.paid_amount > total) {
        return 'Paid amount cannot be greater than total amount.'
    }

    return null
}


/*
|--------------------------------------------------------------------------
| Save
|--------------------------------------------------------------------------
*/

async function savePurchaseOrder(data)
{
    if (saving.value) {
        return
    }

    const isEditMode =
        Boolean(selectedOrder.value?.id)

    const orderId =
        selectedOrder.value?.id

    const payload =
        buildPayload(data)

    const validationMessage =
        validatePayload(payload)

    if (validationMessage) {

        showToast(
            validationMessage,
            'warning'
        )

        return
    }

    saving.value = true

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

        showFormModal.value = false

        selectedOrder.value = null

        filters.page = 1

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

        saving.value = false

    }
}


/*
|--------------------------------------------------------------------------
| Delete
|--------------------------------------------------------------------------
*/

async function deletePurchaseOrder(order)
{
    if (
        !order?.id
        ||
        deletingId.value
    ) {
        return
    }

    const confirmed =
        window.confirm(
            `Are you sure you want to delete the purchase order of ${order.supplier_name || 'this supplier'}?`
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
            filters.page -= 1
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

        deletingId.value = null

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

    ])

})


onBeforeUnmount(() => {

    if (toastTimer) {
        clearTimeout(toastTimer)
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

</style>