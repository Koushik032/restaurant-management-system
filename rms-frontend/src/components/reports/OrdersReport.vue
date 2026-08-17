<template>
<section class="report-wrapper">


    <!-- Header -->

    <div class="report-header mb-4">

        <div>

            <h4 class="fw-bold mb-1">
                Orders Report
            </h4>

            <p class="text-muted mb-0">
                Complete order history and transaction overview
            </p>

        </div>


        <div>

            <ReportExportButton
                type="orders"
                :filters="activeFilters"
            />

        </div>


    </div>





    <!-- Summary Cards -->


    <div class="row g-4 mb-4">


        <div class="col-md-4">


            <div class="summary-box order-card">


                <div class="summary-icon">

                    <i class="bi bi-receipt-cutoff"></i>

                </div>


                <div>

                    <span>
                        Total Orders
                    </span>


                    <h3>
                        {{ meta.total || 0 }}
                    </h3>

                </div>


            </div>


        </div>





        <div class="col-md-4">


            <div class="summary-box amount-card">


                <div class="summary-icon">

                    <i class="bi bi-currency-dollar"></i>

                </div>


                <div>

                    <span>
                        Total Amount
                    </span>


                    <h3>
                        {{ money(totalAmount) }}
                    </h3>


                </div>


            </div>


        </div>





        <div class="col-md-4">


            <div class="summary-box average-card">


                <div class="summary-icon">

                    <i class="bi bi-bar-chart-line"></i>

                </div>


                <div>

                    <span>
                        Average Order
                    </span>


                    <h3>
                        {{ money(averageOrder) }}
                    </h3>


                </div>


            </div>


        </div>


    </div>






    <!-- Loading -->


    <div
        v-if="loading"
        class="loading-box"
    >

        <div class="spinner-border text-primary"></div>

        <p>
            Loading orders...
        </p>


    </div>






    <!-- Table -->


    <div
        v-else
        class="table-card"
    >


        <div class="table-responsive">


            <table class="table align-middle mb-0">


                <thead>


                    <tr>

                        <th>SL</th>

                        <th>Order ID</th>

                        <th>Customer</th>

                        <th>Items</th>

                        <th>Date</th>

                        <th>Amount</th>

                        <th>Payment</th>


                    </tr>


                </thead>





                <tbody>


                <tr
                    v-for="(order,index) in orders"
                    :key="order.id"
                >


                    <td>

                        <span class="sl-number">

                            {{ index + 1 }}

                        </span>

                    </td>




                    <td>

                        <strong class="order-id">

                            #{{ order.id }}

                        </strong>

                    </td>




                    <td>

                        <div class="customer-name">

                            {{
                                order.customer?.name ||
                                order.customer_name ||
                                'Walk In'
                            }}

                        </div>


                    </td>




                    <td>


                        <div
                            v-for="item in order.items"
                            :key="item.id"
                            class="item-line"
                        >

                            {{ item.item_name }}

                            <span>
                                x{{ item.quantity }}
                            </span>


                        </div>


                    </td>





                    <td>

                        {{ formatDateTime(order.created_at) }}

                    </td>





                    <td>


                        <strong class="amount">

                            {{ money(order.total_amount) }}

                        </strong>


                    </td>





                    <td>


                        <span class="payment-badge">

                            {{ order.payment_method || '-' }}

                        </span>


                    </td>




                </tr>





                <tr v-if="orders.length === 0">


                    <td
                        colspan="7"
                        class="empty-state"
                    >


                        <i class="bi bi-inbox"></i>


                        <h6>
                            No orders found
                        </h6>


                        <small>
                            Try changing your filter date
                        </small>


                    </td>


                </tr>



                </tbody>


            </table>


        </div>


    </div>







    <!-- Pagination -->


    <div
        v-if="meta.total"
        class="pagination-box mt-4"
    >


        <span>

            Showing
            {{ meta.total }}
            orders

        </span>




        <div>


            <button
                class="page-btn"
                :disabled="meta.current_page === 1"
                @click="changePage(meta.current_page - 1)"
            >

                Previous

            </button>




            <button
                class="page-btn"
                :disabled="meta.current_page === meta.last_page"
                @click="changePage(meta.current_page + 1)"
            >

                Next

            </button>


        </div>


    </div>



</section>
</template>

<script setup>
import {
    ref,
    computed,
    watch
} from 'vue'

import reportService from '@/services/reportService'
import ReportExportButton from '@/components/reports/ReportExportButton.vue'

const props = defineProps({
    filters: {
        type: Object,
        default: () => ({})
    }
})

const orders = ref([])
const meta = ref({})
const loading = ref(false)
const page = ref(1)

const activeFilters = ref({})

const totalAmount = computed(() => {
    return orders.value.reduce(
        (sum, item) => {
            return sum + Number(item.total_amount || 0)
        },
        0
    )
})

const averageOrder = computed(() => {
    if (!orders.value.length) {
        return 0
    }

    return totalAmount.value / orders.value.length
})

function today() {
    return new Date()
        .toISOString()
        .split('T')[0]
}

async function loadOrders() {
    loading.value = true

    try {
        const response = await reportService.getOrderReport({
            date_from: activeFilters.value.date_from,
            date_to: activeFilters.value.date_to,
            page: page.value
        })

        orders.value = response.data || []
        meta.value = response.meta || {}
    } catch (error) {
        console.error(
            'Order report error',
            error
        )

        orders.value = []
    } finally {
        loading.value = false
    }
}

function changePage(value) {
    page.value = value
    loadOrders()
}

function formatDateTime(date) {
    if (!date) {
        return '-'
    }

    return new Date(date).toLocaleString()
}

function money(value) {
    return '৳ ' + Number(value || 0).toLocaleString()
}

watch(
    () => props.filters,
    (value) => {
        activeFilters.value = {
            date_from: value.date_from || today(),
            date_to: value.date_to || today()
        }

        page.value = 1

        loadOrders()
    },
    {
        deep: true,
        immediate: true
    }
)
</script>

<style scoped>


.report-wrapper{
    padding:10px;
}



.report-header{

    display:flex;

    justify-content:space-between;

    align-items:center;

}





.summary-box{

    padding:25px;

    border-radius:18px;

    display:flex;

    align-items:center;

    gap:20px;

    color:white;

    box-shadow:
    0 10px 25px rgba(0,0,0,.08);

    transition:.3s;

}



.summary-box:hover{

    transform:translateY(-5px);

}




.summary-box span{

    opacity:.85;

    font-size:14px;

}



.summary-box h3{

    margin:8px 0 0;

    font-weight:700;

}




.summary-icon{

    width:55px;

    height:55px;

    border-radius:50%;

    background:white;

    color:#333;

    display:flex;

    align-items:center;

    justify-content:center;

    font-size:25px;

}




.order-card{

    background:
    linear-gradient(135deg,#667eea,#764ba2);

}



.amount-card{

    background:
    linear-gradient(135deg,#11998e,#38ef7d);

}



.average-card{

    background:
    linear-gradient(135deg,#ff9966,#ff5e62);

}




.table-card{

    background:white;

    border-radius:18px;

    overflow:hidden;

    box-shadow:
    0 5px 20px rgba(0,0,0,.06);

}



table thead{

    background:#f8f9fa;

}



table th{

    padding:16px;

    font-size:13px;

    text-transform:uppercase;

    color:#6c757d;

}



table td{

    padding:15px;

}



tbody tr{

    transition:.2s;

}



tbody tr:hover{

    background:#f8fbff;

}




.sl-number{

    background:#e9f2ff;

    color:#0d6efd;

    padding:5px 10px;

    border-radius:20px;

}





.order-id{

    color:#495057;

}




.customer-name{

    font-weight:600;

}



.item-line{

    font-size:14px;

}



.item-line span{

    color:#6c757d;

}



.amount{

    color:#198754;

}




.payment-badge{

    background:#eef2ff;

    color:#4338ca;

    padding:6px 14px;

    border-radius:20px;

    font-size:13px;

}




.loading-box{

    height:300px;

    display:flex;

    flex-direction:column;

    justify-content:center;

    align-items:center;

    gap:15px;

}





.empty-state{

    padding:50px!important;

    text-align:center;

    color:#adb5bd;

}



.empty-state i{

    font-size:45px;

}




.pagination-box{

    display:flex;

    justify-content:space-between;

    align-items:center;

}



.page-btn{

    border:none;

    background:#0d6efd;

    color:white;

    padding:8px 18px;

    border-radius:8px;

    margin-left:8px;

}



.page-btn:disabled{

    opacity:.5;

}





@media(max-width:768px){


.report-header{

    flex-direction:column;

    gap:15px;

    align-items:flex-start;

}


.pagination-box{

    flex-direction:column;

    gap:15px;

}



}


</style>