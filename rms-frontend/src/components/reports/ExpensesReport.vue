<template>

<section class="report-wrapper">


    <!-- Header -->

    <div class="report-header mb-4">


        <div>

            <h4 class="fw-bold mb-1">
                Expenses Report
            </h4>


            <p class="text-muted mb-0">
                Complete expense transaction history
            </p>


        </div>




        <div>

            <ReportExportButton

                type="expenses"

                :filters="props.filters"

            />

        </div>


    </div>






    <!-- Summary Cards -->


    <div class="row g-4 mb-4">


        <div class="col-md-4">


            <div class="summary-box expense-card">


                <div class="summary-icon">

                    <i class="bi bi-wallet2"></i>

                </div>


                <div>

                    <span>
                        Total Expense
                    </span>


                    <h3>

                        {{ money(summary.total_amount) }}

                    </h3>


                </div>


            </div>


        </div>







        <div class="col-md-4">


            <div class="summary-box record-card">


                <div class="summary-icon">

                    <i class="bi bi-receipt"></i>

                </div>


                <div>

                    <span>
                        Total Records
                    </span>


                    <h3>

                        {{ summary.total_records || 0 }}

                    </h3>


                </div>


            </div>


        </div>








        <div class="col-md-4">


            <div class="summary-box average-card">


                <div class="summary-icon">

                    <i class="bi bi-graph-up"></i>

                </div>


                <div>

                    <span>
                        Average Expense
                    </span>


                    <h3>

                        {{ money(summary.average_amount) }}

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
            Loading expenses...
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


                        <th>
                            SL
                        </th>


                        <th>
                            Category
                        </th>


                        <th>
                            Date
                        </th>


                        <th>
                            Amount
                        </th>


                        <th>
                            Payment
                        </th>


                        <th>
                            Note
                        </th>


                    </tr>


                </thead>





                <tbody>



                    <tr

                        v-for="(expense,index) in expenses"

                        :key="expense.id"

                    >


                        <td>


                            <span class="sl-number">

                                {{ index + 1 }}

                            </span>


                        </td>





                        <td>


                            <strong>

                                {{
                                    expense.category?.name
                                    ||
                                    '-'
                                }}

                            </strong>


                        </td>






                        <td>


                            {{


                                formatDate(

                                    expense.expense_date

                                )


                            }}


                        </td>






                        <td>


                            <strong class="amount">


                                {{


                                    money(

                                        expense.amount

                                    )


                                }}


                            </strong>


                        </td>








                        <td>


                            <span class="payment-badge">


                                {{

                                    expense.payment_method

                                    ||

                                    '-'

                                }}


                            </span>


                        </td>







                        <td>


                            <span class="note-text">


                                {{

                                    expense.notes

                                    ||

                                    '-'

                                }}


                            </span>


                        </td>





                    </tr>








                    <tr v-if="expenses.length===0">


                        <td

                            colspan="6"

                            class="empty-state"

                        >


                            <i class="bi bi-inbox"></i>


                            <h6>
                                No expense found
                            </h6>


                            <small>
                                Try changing your filters
                            </small>


                        </td>


                    </tr>




                </tbody>


            </table>


        </div>


    </div>









    <!-- Pagination -->


    <div

        class="pagination-box mt-4"

        v-if="meta.total"

    >



        <span>

            Total:
            {{ meta.total }}

        </span>





        <div>


            <button

                class="page-btn"

                :disabled="meta.current_page===1"

                @click="changePage(meta.current_page-1)"

            >

                Previous

            </button>





            <button

                class="page-btn"

                :disabled="meta.current_page===meta.last_page"

                @click="changePage(meta.current_page+1)"

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
 
    watch, 
 
    onMounted 
 
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
 
 
 
 
 
 
 
 
 
const expenses = ref([]) 
 
 
 
const meta = ref({}) 
 
 
 
const summary = ref({}) 
 
 
 
const loading = ref(false) 
 
 
 
const page = ref(1) 
 
 
 
 
 
 
 
 
 
async function loadExpenses() 
{ 
 
 
    loading.value=true 
 
 
 
    try{ 
 
 
        const response = 
 
 
            await reportService 
 
                .getExpenseReport({ 
 
 
                    ...props.filters, 
 
 
                    page: 
 
                        page.value 
 
 
                }) 
 
 
 
 
 
 
 
 
        expenses.value = 
 
 
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
 
 
 
 
 
 
 
 
 
async function loadSummary() 
{ 
 
 
    try{ 
 
 
        const response = 
 
 
            await reportService 
 
                .getExpenseSummary( 
 
                    props.filters 
 
                ) 
 
 
 
 
 
 
        summary.value = 
 
 
            response.data 
 
            || 
 
            {} 
 
 
 
    } 
 
 
 
    catch(error){ 
 
 
        console.error( 
            error 
        ) 
 
 
    } 
 
 
} 
 
 
 
 
 
 
 
 
 
function changePage(value) 
{ 
 
 
    page.value=value 
 
 
    loadExpenses() 
 
 
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
 
 
        loadExpenses() 
 
        loadSummary() 
 
 
    }, 
 
 
    { 
 
 
        deep:true, 
 
 
        immediate:true 
 
 
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

    font-size:14px;

    opacity:.85;

}



.summary-box h3{

    margin-top:8px;

    font-weight:700;

}





.summary-icon{


    width:55px;

    height:55px;

    background:white;

    color:#333;

    border-radius:50%;


    display:flex;

    justify-content:center;

    align-items:center;


    font-size:24px;


}




.expense-card{

    background:

    linear-gradient(135deg,#ff416c,#ff4b2b);

}



.record-card{


    background:

    linear-gradient(135deg,#396afc,#2948ff);


}




.average-card{


    background:

    linear-gradient(135deg,#11998e,#38ef7d);


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

    color:#6c757d;

    text-transform:uppercase;


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


    background:#e8f0ff;

    color:#0d6efd;

    padding:5px 11px;

    border-radius:20px;


}




.amount{


    color:#dc3545;


}




.payment-badge{


    background:#fff3cd;

    color:#856404;


    padding:6px 14px;

    border-radius:20px;

    font-size:13px;


}




.note-text{


    color:#6c757d;

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

    align-items:flex-start;

    gap:15px;


}



.pagination-box{


    flex-direction:column;

    gap:15px;


}


}



</style>