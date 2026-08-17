<template>
    <div class="purchase-receive-action">

        <!-- Receive Button -->

        <button
            v-if="showButton"
            type="button"
            class="purchase-receive-action-button"
            :class="{
                'purchase-receive-action-partial':
                    currentStatus === 'partially_received',

                'purchase-receive-action-complete':
                    currentStatus === 'received',
            }"
            :disabled="
                loadingDetails
                ||
                currentStatus === 'received'
            "
            :title="buttonTitle"
            @click="openModal"
        >
            <span
                v-if="loadingDetails"
                class="spinner-border spinner-border-sm"
                aria-hidden="true"
            ></span>

            <i
                v-else
                class="bi"
                :class="buttonIcon"
            ></i>

            <span>
                {{ buttonLabel }}
            </span>
        </button>


        <!-- Purchase Receive Modal -->

        <PurchaseReceiveModal
            :show="showReceiveModal"
            :purchase-order="latestPurchaseOrder"
            :submitting="submitting"
            :error-message="errorMessage"
            :server-errors="serverErrors"
            @close="closeModal"
            @submit="submitReceive"
        />

    </div>
</template>


<script setup>
import {
    computed,
    ref,
} from 'vue'

import purchaseReceiveService
    from '@/services/purchaseReceiveService'

import PurchaseReceiveModal
    from './PurchaseReceiveModal.vue'


/*
|--------------------------------------------------------------------------
| Props
|--------------------------------------------------------------------------
*/

const props = defineProps({

    purchaseOrder: {

        type: Object,

        required: true,

    },


    canManage: {

        type: Boolean,

        default: false,

    },

})


/*
|--------------------------------------------------------------------------
| Emits
|--------------------------------------------------------------------------
*/

const emit = defineEmits([

    'received',

    'error',

])


/*
|--------------------------------------------------------------------------
| State
|--------------------------------------------------------------------------
*/

const loadingDetails =
    ref(false)

const submitting =
    ref(false)

const showReceiveModal =
    ref(false)

const latestPurchaseOrder =
    ref(null)

const errorMessage =
    ref('')

const serverErrors =
    ref({})


/*
|--------------------------------------------------------------------------
| Purchase Order ID
|--------------------------------------------------------------------------
*/

const purchaseOrderId = computed(() => {

    return (
        props.purchaseOrder?.id
        ??
        props.purchaseOrder?.purchase_order_id
        ??
        null
    )

})


/*
|--------------------------------------------------------------------------
| Current Status
|--------------------------------------------------------------------------
*/

const currentStatus = computed(() => {

    const status =
        props.purchaseOrder?.status?.value
        ??
        props.purchaseOrder?.status
        ??
        ''

    return String(status)
        .trim()
        .toLowerCase()

})


/*
|--------------------------------------------------------------------------
| Button Visibility
|--------------------------------------------------------------------------
*/

const showButton = computed(() => {

    return (
        props.canManage
        &&
        Boolean(
            purchaseOrderId.value
        )
        &&
        [
            'ordered',
            'partially_received',
            'received',
        ].includes(
            currentStatus.value
        )
    )

})


/*
|--------------------------------------------------------------------------
| Button Label
|--------------------------------------------------------------------------
*/

const buttonLabel = computed(() => {

    if (
        currentStatus.value ===
        'received'
    ) {
        return 'Received'
    }

    if (
        currentStatus.value ===
        'partially_received'
    ) {
        return 'Receive More'
    }

    return 'Receive'

})


/*
|--------------------------------------------------------------------------
| Button Title
|--------------------------------------------------------------------------
*/

const buttonTitle = computed(() => {

    if (
        currentStatus.value ===
        'received'
    ) {
        return 'This purchase order is fully received.'
    }

    if (
        currentStatus.value ===
        'partially_received'
    ) {
        return 'Receive remaining purchase items.'
    }

    return 'Receive purchase items.'

})


/*
|--------------------------------------------------------------------------
| Button Icon
|--------------------------------------------------------------------------
*/

const buttonIcon = computed(() => {

    if (
        currentStatus.value ===
        'received'
    ) {
        return 'bi-check-circle-fill'
    }

    if (
        currentStatus.value ===
        'partially_received'
    ) {
        return 'bi-box-arrow-in-down'
    }

    return 'bi-truck'

})


/*
|--------------------------------------------------------------------------
| Open Receive Modal
|--------------------------------------------------------------------------
*/

async function openModal()
{
    if (
        loadingDetails.value
        ||
        !props.canManage
        ||
        !purchaseOrderId.value
        ||
        currentStatus.value === 'received'
    ) {
        return
    }

    loadingDetails.value =
        true

    errorMessage.value =
        ''

    serverErrors.value =
        {}

    try {

        const response =
            await purchaseReceiveService
                .getPurchaseOrder(
                    purchaseOrderId.value
                )

        const purchaseData =
            extractPurchaseOrder(
                response
            )

        if (
            !purchaseData
            ||
            typeof purchaseData !== 'object'
        ) {
            throw new Error(
                'Purchase order information was not found.'
            )
        }

        latestPurchaseOrder.value =
            purchaseData

        showReceiveModal.value =
            true

    }

    catch (error) {

        const message =
            purchaseReceiveService
                .getErrorMessage(
                    error,
                    'Unable to load purchase order information.'
                )

        errorMessage.value =
            message

        emit(
            'error',
            {
                message,
                error,
            }
        )

    }

    finally {

        loadingDetails.value =
            false

    }
}


/*
|--------------------------------------------------------------------------
| Submit Purchase Receive
|--------------------------------------------------------------------------
*/

async function submitReceive(payload)
{
    if (
        submitting.value
        ||
        !purchaseOrderId.value
    ) {
        return
    }

    submitting.value =
        true

    errorMessage.value =
        ''

    serverErrors.value =
        {}

    try {

        const response =
            await purchaseReceiveService
                .receivePurchaseOrder(
                    purchaseOrderId.value,
                    payload
                )

        const updatedPurchaseOrder =
            extractPurchaseOrder(
                response
            )

        const updatedStatus =
            String(
                updatedPurchaseOrder?.status?.value
                ??
                updatedPurchaseOrder?.status
                ??
                ''
            )
                .trim()
                .toLowerCase()

        const message =
            response?.message
            ||
            (
                updatedStatus === 'received'
                    ? 'Purchase order fully received successfully.'
                    : 'Purchase order partially received successfully.'
            )

        /*
        |--------------------------------------------------------------------------
        | Stop Loading Before Closing Modal
        |--------------------------------------------------------------------------
        */

        submitting.value =
            false

        closeModal(true)

        /*
        |--------------------------------------------------------------------------
        | Inform Parent Purchase Order Section
        |--------------------------------------------------------------------------
        */

        emit(
            'received',
            {
                message,

                purchaseOrder:
                    updatedPurchaseOrder,

                purchaseOrderId:
                    purchaseOrderId.value,
            }
        )

        /*
        |--------------------------------------------------------------------------
        | Global Inventory Refresh Event
        |--------------------------------------------------------------------------
        */

        window.dispatchEvent(
            new CustomEvent(
                'purchase-order:received',
                {
                    detail: {

                        purchaseOrderId:
                            purchaseOrderId.value,

                        purchaseOrder:
                            updatedPurchaseOrder,

                        message,

                    },
                }
            )
        )

    }

    catch (error) {

        serverErrors.value =
            purchaseReceiveService
                .getValidationErrors(
                    error
                )

        errorMessage.value =
            purchaseReceiveService
                .getErrorMessage(
                    error,
                    'Unable to receive purchase order.'
                )

    }

    finally {

        submitting.value =
            false

    }
}


/*
|--------------------------------------------------------------------------
| Extract Purchase Order
|--------------------------------------------------------------------------
*/

function extractPurchaseOrder(response)
{
    return (
        response?.data?.purchase_order
        ??
        response?.data?.data
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


/*
|--------------------------------------------------------------------------
| Close Receive Modal
|--------------------------------------------------------------------------
*/

function closeModal(
    forceClose = false
) {
    if (
        submitting.value
        &&
        !forceClose
    ) {
        return
    }

    showReceiveModal.value =
        false

    latestPurchaseOrder.value =
        null

    errorMessage.value =
        ''

    serverErrors.value =
        {}
}
</script>