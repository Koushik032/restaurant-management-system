<template>


<section class="purchase-order-filter-card">






    <div class="purchase-order-filter-grid">







        <!-- From Date -->


        <div class="filter-group">


            <label>

                From Date

            </label>



            <input


                type="date"


                v-model="localFilters.date_from"


            />



        </div>









        <!-- To Date -->


        <div class="filter-group">


            <label>

                To Date

            </label>



            <input


                type="date"


                v-model="localFilters.date_to"


            />



        </div>









        <!-- Supplier -->


        <div class="filter-group">


            <label>

                Supplier

            </label>







            <select

                v-model="localFilters.supplier_id"

            >



                <option value="">

                    All Suppliers

                </option>






                <option

                    v-for="supplier in suppliers"

                    :key="supplier.id"

                    :value="supplier.id"

                >


                    {{ supplier.supplier_name }}


                </option>





            </select>



        </div>









        <!-- Status -->


        <div class="filter-group">


            <label>

                Status

            </label>







            <select

                v-model="localFilters.status"

            >



                <option value="">

                    All Status

                </option>







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



        </div>







    </div>









    <!-- Actions -->


    <div class="purchase-order-filter-actions">







        <button


            type="button"


            class="po-filter-btn"


            @click="apply"


        >


            <i class="bi bi-funnel"></i>


            Apply Filter



        </button>









        <button


            type="button"


            class="po-clear-btn"


            @click="clear"


        >


            <i class="bi bi-x-circle"></i>


            Clear



        </button>







    </div>







</section>



</template>









<script setup>


import {


    reactive,


    watch



} from 'vue'







const props = defineProps({



    filters:{


        type:Object,


        required:true


    },





    suppliers:{


        type:Array,


        default:()=>[]


    }



})









const emit = defineEmits([



    'update:filters',


    'apply',


    'clear'



])









const localFilters = reactive({



    date_from:'',


    date_to:'',


    supplier_id:'',


    status:'',


    page:1,


    per_page:10



})









/*
|--------------------------------------------------------------------------
| Sync Parent Filters
|--------------------------------------------------------------------------
*/


watch(


    ()=>props.filters,


    (value)=>{


        if(value){


            Object.assign(

                localFilters,

                value

            )


        }



    },


    {


        deep:true,


        immediate:true


    }



)









/*
|--------------------------------------------------------------------------
| Update Parent
|--------------------------------------------------------------------------
*/


watch(


    localFilters,


    (value)=>{


        emit(

            'update:filters',

            {

                ...value

            }

        )



    },


    {


        deep:true


    }



)









/*
|--------------------------------------------------------------------------
| Apply Filter
|--------------------------------------------------------------------------
*/


function apply(){



    localFilters.page = 1



    emit(

        'apply'

    )



}









/*
|--------------------------------------------------------------------------
| Clear Filter
|--------------------------------------------------------------------------
*/


function clear(){



    Object.assign(

        localFilters,

        {


            date_from:'',


            date_to:'',


            supplier_id:'',


            status:'',


            page:1


        }


    )



    emit(

        'clear'

    )



}





</script>