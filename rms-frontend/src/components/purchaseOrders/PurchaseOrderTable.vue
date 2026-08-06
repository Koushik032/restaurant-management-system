<template>

<section class="purchase-order-table-card">

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


    <div class="table-responsive">

        <table class="purchase-order-table">

            <thead>

                <tr>

                    <th>
                        SL No
                    </th>

                    <th>
                        Supplier Name
                    </th>

                    <th>
                        Purchase Item
                    </th>

                    <th>
                        Amount
                    </th>

                    <th>
                        Status
                    </th>

                    <th>
                        Action
                    </th>

                </tr>

            </thead>


            <tbody>

                <tr v-if="loading">

                    <td
                        colspan="6"
                        class="po-loading"
                    >
                        Loading purchase orders...
                    </td>

                </tr>


                <tr v-else-if="orders.length === 0">

                    <td
                        colspan="6"
                        class="po-empty"
                    >
                        No purchase orders found.
                    </td>

                </tr>


                <tr
                    v-for="(order, index) in orders"
                    v-else
                    :key="order.id"
                >

                    <td>
                        {{ getSerialNumber(index) }}
                    </td>


                    <td>

                        <strong>
                            {{ order.supplier_name || '-' }}
                        </strong>

                    </td>


                    <td>

                        <div class="item-list">

                            <span
                                v-for="item in order.items || []"
                                :key="item.id"
                            >
                                {{ item.item_name }}
                            </span>

                            <span
                                v-if="
                                    !order.items
                                    ||
                                    order.items.length === 0
                                "
                            >
                                -
                            </span>

                        </div>

                    </td>


                    <td>

                        <strong>
                            {{
                                order.total_amount_formatted
                                ||
                                '৳ 0.00'
                            }}
                        </strong>

                    </td>


                    <td>

                        <div class="po-status-control">

                            <select
                                class="po-status-select"
                                :class="`po-status-select-${order.status}`"
                                :value="order.status"
                                :disabled="
                                    statusLoadingId === order.id
                                "
                                @change="
                                    changeStatus(
                                        order,
                                        $event.target.value
                                    )
                                "
                            >

                                <option value="ordered">
                                    Ordered
                                </option>

                                <option value="partially_received">
                                    Partially Received
                                </option>

                                <option value="received">
                                    Received
                                </option>

                                <option value="cancelled">
                                    Cancelled
                                </option>

                            </select>


                            <span
                                v-if="statusLoadingId === order.id"
                                class="po-status-loading"
                            >
                                <i class="bi bi-arrow-repeat"></i>
                            </span>

                        </div>

                    </td>


                    <td>

                        <div class="purchase-order-actions">

                            <button
                                type="button"
                                class="action-btn view-btn"
                                title="View"
                                @click="viewOrder(order)"
                            >
                                <i class="bi bi-eye"></i>
                            </button>


                            <button
                                type="button"
                                class="action-btn edit-btn"
                                title="Edit"
                                @click="editOrder(order)"
                            >
                                <i class="bi bi-pencil"></i>
                            </button>


                            <button
                                type="button"
                                class="action-btn delete-btn"
                                title="Delete"
                                @click="deleteOrder(order)"
                            >
                                <i class="bi bi-trash"></i>
                            </button>

                        </div>

                    </td>

                </tr>

            </tbody>

        </table>

    </div>


    <div
        v-if="totalPages > 1"
        class="purchase-order-pagination"
    >

        <button
            type="button"
            :disabled="currentPage <= 1"
            @click="changePage(currentPage - 1)"
        >
            Previous
        </button>


        <span>
            Page {{ currentPage }} of {{ totalPages }}
        </span>


        <button
            type="button"
            :disabled="currentPage >= totalPages"
            @click="changePage(currentPage + 1)"
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

        type: Number,

        default: null,

    },

})


const emit = defineEmits([

    'view',

    'edit',

    'delete',

    'page-change',

    'status-change',

])


const currentPage = computed(() => {

    return Number(
        props.meta?.current_page || 1
    )

})


const totalPages = computed(() => {

    return Number(
        props.meta?.last_page || 1
    )

})


function getSerialNumber(index)
{
    const perPage =
        Number(props.meta?.per_page || 10)

    return (
        (currentPage.value - 1) * perPage
    ) + index + 1
}


function viewOrder(order)
{
    emit(
        'view',
        order
    )
}


function editOrder(order)
{
    emit(
        'edit',
        order
    )
}


function deleteOrder(order)
{
    emit(
        'delete',
        order
    )
}


function changePage(page)
{
    emit(
        'page-change',
        page
    )
}


function changeStatus(
    order,
    status
) {
    if (
        !status
        ||
        status === order.status
    ) {
        return
    }

    emit(
        'status-change',
        {
            order,
            status,
        }
    )
}

</script>