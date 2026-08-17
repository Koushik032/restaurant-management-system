<template>

<section>


    <!-- Header -->

    <div class="d-flex justify-content-between align-items-center mb-3">


        <div>

            <h5 class="mb-1">
                Stock Report
            </h5>


            <small class="text-secondary">
                Restaurant inventory and warehouse stock overview
            </small>


        </div>





        <div>


            <ReportExportButton

                type="stock"

                :filters="props.filters"

            />


        </div>


    </div>









    <!-- Tabs -->


    <ul class="nav nav-tabs mb-4">


        <li class="nav-item">


            <button

                class="nav-link"

                :class="{

                    active:
                    activeTab==='restaurant'

                }"

                @click="
                    activeTab='restaurant'
                "

            >

                Restaurant Stock

            </button>


        </li>






        <li class="nav-item">


            <button

                class="nav-link"

                :class="{

                    active:
                    activeTab==='warehouse'

                }"

                @click="
                    activeTab='warehouse'
                "

            >

                Warehouse Stock

            </button>


        </li>






        <li class="nav-item">


            <button

                class="nav-link"

                :class="{

                    active:
                    activeTab==='transfer'

                }"

                @click="
                    activeTab='transfer'
                "

            >

                Transfer History

            </button>


        </li>



    </ul>









    <!-- Loading -->


    <div

        v-if="loading"

        class="text-center py-5"

    >

        Loading stock data...

    </div>









    <!-- Restaurant Stock -->


    <div

        v-else-if="
            activeTab==='restaurant'
        "

    >


        <StockTable

            :items="restaurantStocks"

            type="restaurant"

        />


    </div>









    <!-- Warehouse Stock -->


    <div

        v-else-if="
            activeTab==='warehouse'
        "

    >


        <StockTable

            :items="warehouseStocks"

            type="warehouse"

        />


    </div>









    <!-- Transfer History -->


    <div

        v-else

    >



        <table

            class="table table-hover align-middle"

        >


            <thead class="table-light">


                <tr>


                    <th>
                        SL
                    </th>


                    <th>
                        Transfer No
                    </th>


                    <th>
                        Date
                    </th>


                    <th>
                        Quantity
                    </th>


                </tr>


            </thead>







            <tbody>



                <tr

                    v-for="

                    (item,index)

                    in transfers"

                    :key="item.id"

                >


                    <td>

                        {{ index + 1 }}

                    </td>




                    <td>

                        {{ item.transfer_no }}

                    </td>




                    <td>

                        {{
                            formatDate(
                                item.transferred_at
                            )
                        }}

                    </td>




                    <td>

                        {{
                            item.total_quantity
                            ||
                            0
                        }}

                    </td>



                </tr>






                <tr

                    v-if="transfers.length===0"

                >

                    <td

                        colspan="4"

                        class="text-center py-4"

                    >

                        No transfer history found


                    </td>


                </tr>



            </tbody>



        </table>



    </div>



</section>


</template>
<script setup>


import {

    ref,

    watch

}

from 'vue'



import reportService

from '@/services/reportService'



import StockTable

from '@/components/reports/StockTable.vue'



import ReportExportButton

from '@/components/reports/ReportExportButton.vue'







const props = defineProps({


    filters:{


        type:Object,


        default:()=>({})


    }


})









const activeTab =

    ref('restaurant')








const loading =

    ref(false)








const restaurantStocks =

    ref([])








const warehouseStocks =

    ref([])








const transfers =

    ref([])









async function loadStock()
{


    loading.value = true



    try{


        const params = {


            ...props.filters


        }







        /*
        |--------------------------------------------------------------------------
        | Restaurant Stock
        |--------------------------------------------------------------------------
        */


        const restaurant =


            await reportService

                .getRestaurantStock(

                    params

                )







        restaurantStocks.value =


            restaurant.data?.data

            ||

            restaurant.data

            ||

            []









        /*
        |--------------------------------------------------------------------------
        | Warehouse Stock
        |--------------------------------------------------------------------------
        */


        const warehouse =


            await reportService

                .getWarehouseStock(

                    params

                )







        warehouseStocks.value =


            warehouse.data?.data

            ||

            warehouse.data

            ||

            []









        /*
        |--------------------------------------------------------------------------
        | Transfer History
        |--------------------------------------------------------------------------
        */


        const transfer =


            await reportService

                .getStockTransfers(

                    params

                )







        transfers.value =


            transfer.data?.data

            ||

            transfer.data

            ||

            []



    }




    catch(error){


        console.error(

            error

        )


    }





    finally{


        loading.value = false


    }


}









function formatDate(date)
{


    if(!date)

        return '-'




    return new Date(date)

        .toLocaleDateString()


}









watch(


    ()=>props.filters,


    ()=>{


        loadStock()


    },


    {


        deep:true,


        immediate:true


    }


)



</script>









<style scoped>


table{


    font-size:

        .9rem;


}



.nav-link{


    cursor:pointer;


}



</style>