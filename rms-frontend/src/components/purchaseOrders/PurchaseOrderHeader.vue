<template>


<section class="purchase-order-header-card">






    <div class="purchase-order-header-content">







        <!-- Left Content -->


        <div class="purchase-order-header-text">





            <h2>


                <i class="bi bi-cart-check"></i>


                Purchase Orders



            </h2>







            <p>


                Manage supplier purchase orders, items and payments



            </p>







        </div>












        <!-- Right Actions -->


        <div class="purchase-order-header-actions">







            <!-- Refresh Button -->


            <button


                type="button"


                class="po-refresh-btn"


                title="Refresh Purchase Orders"


                @click="refresh"


            >





                <i class="bi bi-arrow-clockwise"></i>





                Refresh





            </button>













            <!-- Add Purchase Order -->


            <button


                type="button"


                class="po-add-btn"


                title="Create New Purchase Order"


                @click="addPurchaseOrder"


            >





                <i class="bi bi-plus-lg"></i>





                Add Purchase Order






            </button>









        </div>








    </div>







</section>





</template>









<script setup>





const emit = defineEmits([



    'refresh',


    'add'



])









function refresh(){



    emit(

        'refresh'

    )



}









function addPurchaseOrder(){



    emit(

        'add'

    )



}







</script>