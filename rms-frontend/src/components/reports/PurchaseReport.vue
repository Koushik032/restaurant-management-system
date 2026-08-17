<template>

<section>


    <!-- Header -->

    <div class="d-flex justify-content-between align-items-center mb-3">


        <div>

            <h5 class="mb-1">
                Purchase Report
            </h5>


            <small class="text-secondary">
                Supplier purchase order history
            </small>


        </div>





        <div>


            <ReportExportButton

                type="purchase"

                :filters="props.filters"

            />


        </div>


    </div>









    <!-- Summary -->


    <div class="row g-3 mb-4">


        <div class="col-md-4">


            <div class="card summary-card purchase-card">

    <div class="card-body">

        <div class="summary-icon">

            <i class="bi bi-cart-check"></i>

        </div>


        <small>
            Total Purchase
        </small>


        <h4>
            {{ money(totalAmount) }}
        </h4>


    </div>

</div>


        </div>








        <div class="col-md-4">


            <div class="card summary-card order-card">

    <div class="card-body">


        <div class="summary-icon">

            <i class="bi bi-receipt"></i>

        </div>


        <small>
            Total Orders
        </small>


        <h4>
            {{ purchases.length }}
        </h4>


    </div>

</div>


        </div>








        <div class="col-md-4">


            <div class="card summary-card due-card">


    <div class="card-body">


        <div class="summary-icon">

            <i class="bi bi-wallet2"></i>

        </div>


        <small>
            Due Amount
        </small>


        <h4>
            {{ money(totalDue) }}
        </h4>


    </div>


</div>


        </div>



    </div>









    <!-- Loading -->


    <div

        v-if="loading"

        class="text-center py-5"

    >

        Loading purchase report...

    </div>









    <!-- Table -->


    <div

        v-else

        class="table-responsive"

    >


        <table class="table table-hover align-middle">



            <thead class="table-light">


                <tr>


                    <th>
                        SL
                    </th>


                    <th>
                        Purchase ID
                    </th>


                    <th>
                        Supplier
                    </th>


                    <th>
                        Items
                    </th>


                    <th>
                        Date
                    </th>


                    <th>
                        Amount
                    </th>


                    <th>
                        Paid
                    </th>


                    <th>
                        Due Amount
                    </th>


                    <th>
                        Status
                    </th>


                </tr>


            </thead>







            <tbody>



                <tr

                    v-for="

                    (purchase,index)

                    in purchases"

                    :key="purchase.id"

                >



                    <td>

                        {{ index + 1 }}

                    </td>





                    <td>

                        #{{ purchase.id }}

                    </td>





                    <td>

                        <span class="supplier-badge">

                        {{
                        purchase.supplier
                        ?.supplier_name
                        ||
                        '-'
                        }}

                        </span>

                    </td>





                    <td>


                        <div
    v-if="purchase.items && purchase.items.length"
>
    <div
        v-for="item in purchase.items"
        :key="item.id"
        class="small mb-1"
    >

        <strong>
            {{ item.item_name }}
        </strong>

        <span class="text-muted">
            × {{ item.quantity }} {{ item.unit }}
        </span>

    </div>
</div>


<div
    v-else
    class="text-muted"
>
    No items
</div>


                    </td>





                    <td>

                        {{
                            formatDate(
                                purchase.purchase_date
                                ||
                                purchase.created_at
                            )
                        }}

                    </td>





                    <td>

                        {{
                            money(
                                purchase.total_amount
                            )
                        }}

                    </td>
                    <td>

{{ 
    money(
        purchase.paid_amount
    )
}}

</td>



<td>


<span

class="badge"

:class="
purchase.due_amount > 0
?
'bg-danger'
:
'bg-success'
"

>


{{

purchase.due_amount > 0

?
money(
purchase.due_amount
)

:

'Paid'

}}


</span>


</td>





                    <td>


                        <span

class="status-badge"

:class="{

'completed':

purchase.status==='completed',

'pending':

purchase.status==='pending'

}"

>

{{

purchase.status || '-'

}}

</span>


                    </td>



                </tr>







                <tr

                    v-if="purchases.length===0"

                >

                    <td

                        colspan="7"

                        class="text-center py-4"

                    >

                        No purchase found


                    </td>


                </tr>



            </tbody>



        </table>



    </div>
    <!-- Pagination -->


<div

    class="d-flex justify-content-between mt-3"

    v-if="meta.total"

>


    <span>

        Total:
        {{ meta.total }}

    </span>





    <div>


        <button

            class="btn btn-sm btn-outline-primary me-2"

            :disabled="
                meta.current_page===1
            "

            @click="
                changePage(
                    meta.current_page - 1
                )
            "

        >

            Previous

        </button>







        <button

            class="btn btn-sm btn-outline-primary"

            :disabled="
                meta.current_page===meta.last_page
            "

            @click="
                changePage(
                    meta.current_page + 1
                )
            "

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

}

from 'vue'



import reportService

from '@/services/reportService'



import ReportExportButton

from '@/components/reports/ReportExportButton.vue'







const props = defineProps({


    filters:{


        type:Object,


        default:()=>({})


    }


})









const purchases = ref([])



const meta = ref({})



const loading = ref(false)



const page = ref(1)









const totalAmount = computed(()=>{


    return purchases.value.reduce(

        (

            total,

            purchase

        )=>{


            return total +

                Number(

                    purchase.total_amount

                    ||

                    0

                )


        },


        0


    )


})









const totalDue = computed(()=>{


    return purchases.value.reduce(

        (

            total,

            purchase

        )=>{


            return total +

                Number(

                    purchase.due_amount

                    ||

                    0

                )


        },


        0


    )


})









async function loadPurchases()
{


    loading.value=true



    try{


        const response =


            await reportService

                .getPurchaseReport({


                    ...props.filters,


                    page:

                        page.value


                })








        purchases.value =


            response.data?.data

            ||

            response.data

            ||

            []







        meta.value =


            response.meta

            ||

            {}



    }



    catch(error){


        console.error(
            error
        )


    }




    finally{


        loading.value=false


    }


}









function changePage(value)
{


    page.value=value


    loadPurchases()


}









function formatDate(date)
{


    if(!date)

        return '-'




    return new Date(date)

        .toLocaleDateString()


}









function money(value)
{


    return (

        '৳ '

        +

        Number(

            value || 0

        )

        .toLocaleString()

    )


}









watch(


    ()=>props.filters,


    ()=>{


        page.value=1


        loadPurchases()


    },


    {


        deep:true,


        immediate:true


    }


)



</script>









<style scoped>


/* ===============================
   Summary Cards
================================ */

.summary-card{

    border:none;

    border-radius:1rem;

    background:#ffffff;

    box-shadow:
        0 4px 18px rgba(0,0,0,.06);

    transition:.3s ease;

}



.summary-card:hover{

    transform:translateY(-4px);

    box-shadow:
        0 8px 25px rgba(0,0,0,.10);

}



.summary-card small{

    color:#6c757d;

    font-size:.85rem;

    font-weight:600;

}



.summary-card h4{

    margin-top:12px;

    font-weight:800;

    font-size:1.35rem;

    color:#212529;

}






/* ===============================
   Header
================================ */


h5{

    font-weight:800;

    font-size:1.2rem;

}





small.text-secondary{

    font-size:.85rem;

}






/* ===============================
   Table
================================ */


.table-responsive{

    background:#ffffff;

    border-radius:1rem;

    padding:.5rem;

    box-shadow:
        0 4px 18px rgba(0,0,0,.05);

}




table{

    margin-bottom:0;

    font-size:.9rem;

}





thead th{


    background:#f8f9fa!important;

    color:#495057;

    font-weight:700;

    font-size:.82rem;

    text-transform:uppercase;

    letter-spacing:.3px;

    border-bottom:1px solid #dee2e6;


}





tbody tr{


    transition:.2s ease;


}



tbody tr:hover{


    background:#fafafa;

}




td{

    vertical-align:middle;

    color:#343a40;

}







/* ===============================
   Items
================================ */


td:nth-child(4){


    min-width:220px;


}



td:nth-child(4) div{


    padding:7px 10px;

    border-radius:.6rem;

    background:#f8f9fa;

    margin-bottom:5px;


}





td:nth-child(4) .fw-semibold{


    color:#212529;

    font-size:.9rem;

}





td:nth-child(4) small{


    font-size:.78rem;


}







/* ===============================
   Money columns
================================ */


td:nth-child(6),
td:nth-child(7),
td:nth-child(8){


    font-weight:700;


}







/* ===============================
   Badge
================================ */


.badge{


    padding:.45rem .75rem;

    border-radius:50px;

    font-weight:600;

    font-size:.75rem;


}



.bg-light{


    background:#f1f3f5!important;

    color:#495057!important;

}







/* ===============================
   Due Status
================================ */


.bg-danger{


    background:#dc3545!important;


}



.bg-success{


    background:#198754!important;


}






/* ===============================
   Pagination
================================ */


button.btn-outline-primary{


    border-radius:50px;

    padding:.35rem 1rem;

    font-weight:600;


}




button.btn-outline-primary:hover{


    transform:translateY(-1px);


}







/* ===============================
   Empty State
================================ */


.text-center.py-4{


    color:#6c757d;

    font-weight:600;


}







/* ===============================
   Mobile Responsive
================================ */


@media(max-width:768px){


    .summary-card h4{

        font-size:1.1rem;

    }



    table{

        min-width:900px;

    }



}
/* Summary Icon */


.summary-icon{

width:42px;

height:42px;

border-radius:12px;

display:flex;

align-items:center;

justify-content:center;

font-size:20px;

margin-bottom:12px;

}



/* Card Colors */


.purchase-card .summary-icon{

background:#e7f1ff;

color:#0d6efd;

}



.order-card .summary-icon{

background:#f3e8ff;

color:#7c3aed;

}



.due-card .summary-icon{

background:#ffe5e5;

color:#dc3545;

}



/* Supplier */

.supplier-badge{

display:inline-block;

padding:6px 12px;

background:#eef7ff;

color:#0d6efd;

border-radius:30px;

font-weight:600;

font-size:.8rem;

}





/* Status */


.status-badge{

padding:6px 14px;

border-radius:30px;

font-size:.75rem;

font-weight:700;

background:#f1f3f5;

}





.status-badge.completed{

background:#d1e7dd;

color:#146c43;

}



.status-badge.pending{

background:#fff3cd;

color:#997404;

}


</style>