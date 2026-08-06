<template>

<section class="po-details-page">


    <!-- Loading -->

    <div
        v-if="loading"
        class="po-details-loading"
    >

        <span class="po-details-spinner"></span>

        Loading purchase order details...

    </div>



    <template v-else-if="order">


        <!-- Details Header -->

        <div class="po-details-page-header">


            <div class="po-details-title-area">

                <button
                    type="button"
                    class="po-back-btn"
                    @click="closeDetails"
                >
                    <i class="bi bi-arrow-left"></i>
                </button>


                <div>

                    <h3>
                        Purchase Order Details
                    </h3>

                    <p>
                        Complete purchase order information
                    </p>

                </div>

            </div>


            <button
                type="button"
                class="po-details-edit-btn"
                @click="editOrder"
            >

                <i class="bi bi-pencil"></i>

                Edit Order

            </button>


        </div>



        <!-- Supplier Information -->

        <div class="po-details-card">

            <div class="po-details-card-header">

                <i class="bi bi-truck"></i>

                <h4>
                    Supplier Information
                </h4>

            </div>


            <div class="po-details-grid">

                <div class="po-detail-item">

                    <span>
                        Supplier Name
                    </span>

                    <strong>
                        {{ order.supplier_name || '-' }}
                    </strong>

                </div>


                <div class="po-detail-item">

                    <span>
                        Phone
                    </span>

                    <strong>
                        {{ order.supplier?.phone || '-' }}
                    </strong>

                </div>


                <div class="po-detail-item">

                    <span>
                        Email
                    </span>

                    <strong>
                        {{ order.supplier?.email || '-' }}
                    </strong>

                </div>


                <div class="po-detail-item">

                    <span>
                        Address
                    </span>

                    <strong>
                        {{ order.supplier?.address || '-' }}
                    </strong>

                </div>

            </div>

        </div>



        <!-- Order Information -->

        <div class="po-details-card">

            <div class="po-details-card-header">

                <i class="bi bi-receipt"></i>

                <h4>
                    Order Information
                </h4>

            </div>


            <div class="po-details-grid">

                <div class="po-detail-item">

                    <span>
                        Order Date
                    </span>

                    <strong>
                        {{ order.order_date_label || '-' }}
                    </strong>

                </div>


                <div class="po-detail-item">

                    <span>
                        Delivery Date
                    </span>

                    <strong>
                        {{
                            order.delivery_date_label
                            ||
                            order.delivery_date
                            ||
                            '-'
                        }}
                    </strong>

                </div>


                <div class="po-detail-item">

                    <span>
                        Status
                    </span>

                    <strong
                        class="po-status-badge"
                        :class="`po-status-${order.status}`"
                    >
                        {{
                            order.status_label
                            ||
                            order.status
                            ||
                            '-'
                        }}
                    </strong>

                </div>


                <div class="po-detail-item">

                    <span>
                        Ordered By
                    </span>

                    <strong>
                        {{ order.ordered_by?.name || '-' }}
                    </strong>

                </div>

            </div>

        </div>



        <!-- Items -->

        <div class="po-details-card">

            <div class="po-details-card-header">

                <i class="bi bi-box-seam"></i>

                <h4>
                    Purchase Items
                </h4>

            </div>


            <div class="po-details-table-wrapper">

                <table class="po-details-table">

                    <thead>

                        <tr>

                            <th>SL</th>

                            <th>Item</th>

                            <th>Unit</th>

                            <th>Quantity</th>

                            <th>Received</th>

                            <th>Pending</th>

                            <th>Unit Price</th>

                            <th>Total</th>

                        </tr>

                    </thead>


                    <tbody>

                        <tr
                            v-for="(item, index) in order.items || []"
                            :key="item.id || index"
                        >

                            <td>
                                {{ index + 1 }}
                            </td>

                            <td>
                                <strong>
                                    {{ item.item_name || '-' }}
                                </strong>
                            </td>

                            <td>
                                {{ item.unit || '-' }}
                            </td>

                            <td>
                                {{ item.quantity ?? 0 }}
                            </td>

                            <td>
                                {{ item.received_quantity ?? 0 }}
                            </td>

                            <td>
                                {{ item.pending_quantity ?? 0 }}
                            </td>

                            <td>
                                {{
                                    item.unit_price_formatted
                                    ||
                                    '৳ 0.00'
                                }}
                            </td>

                            <td>
                                <strong>
                                    {{
                                        item.total_price_formatted
                                        ||
                                        '৳ 0.00'
                                    }}
                                </strong>
                            </td>

                        </tr>


                        <tr
                            v-if="
                                !order.items
                                ||
                                order.items.length === 0
                            "
                        >

                            <td
                                colspan="8"
                                class="po-details-empty"
                            >
                                No purchase items found.
                            </td>

                        </tr>

                    </tbody>

                </table>

            </div>

        </div>



        <!-- Payment Information -->

        <div class="po-details-card">

            <div class="po-details-card-header">

                <i class="bi bi-cash-stack"></i>

                <h4>
                    Payment Summary
                </h4>

            </div>


            <div class="po-payment-grid">

                <div class="po-payment-row">

                    <span>
                        Subtotal
                    </span>

                    <strong>
                        {{
                            order.subtotal_formatted
                            ||
                            '৳ 0.00'
                        }}
                    </strong>

                </div>


                <div class="po-payment-row">

                    <span>
                        Tax
                    </span>

                    <strong>
                        {{
                            order.tax_formatted
                            ||
                            '৳ 0.00'
                        }}
                    </strong>

                </div>


                <div class="po-payment-row">

                    <span>
                        Service Charge
                    </span>

                    <strong>
                        {{
                            order.service_charge_formatted
                            ||
                            '৳ 0.00'
                        }}
                    </strong>

                </div>


                <div class="po-payment-row po-payment-total">

                    <span>
                        Total Amount
                    </span>

                    <strong>
                        {{
                            order.total_amount_formatted
                            ||
                            '৳ 0.00'
                        }}
                    </strong>

                </div>


                <div class="po-payment-row po-payment-paid">

                    <span>
                        Paid Amount
                    </span>

                    <strong>
                        {{
                            order.paid_amount_formatted
                            ||
                            '৳ 0.00'
                        }}
                    </strong>

                </div>


                <div class="po-payment-row po-payment-due">

                    <span>
                        Due Amount
                    </span>

                    <strong>
                        {{
                            order.due_amount_formatted
                            ||
                            '৳ 0.00'
                        }}
                    </strong>

                </div>


                <div class="po-payment-row">

                    <span>
                        Payment Method
                    </span>

                    <strong>
                        {{
                            order.payment_method_label
                            ||
                            order.payment_method
                            ||
                            '-'
                        }}
                    </strong>

                </div>

            </div>

        </div>



        <!-- Notes -->

        <div class="po-details-card">

            <div class="po-details-card-header">

                <i class="bi bi-journal-text"></i>

                <h4>
                    Notes
                </h4>

            </div>


            <p class="po-details-notes">

                {{ order.notes || 'No notes added.' }}

            </p>

        </div>


    </template>


    <!-- No Data -->

    <div
        v-else
        class="po-details-empty-state"
    >

        <i class="bi bi-receipt"></i>

        <h4>
            Purchase order not found
        </h4>

        <button
            type="button"
            class="po-back-btn-text"
            @click="closeDetails"
        >
            Back to Purchase Orders
        </button>

    </div>


</section>

</template>


<script setup>

defineProps({

    order: {

        type: Object,

        default: null,

    },


    loading: {

        type: Boolean,

        default: false,

    },

})


const emit = defineEmits([

    'close',

    'edit',

])


function closeDetails()
{
    emit('close')
}


function editOrder()
{
    emit('edit')
}

</script>