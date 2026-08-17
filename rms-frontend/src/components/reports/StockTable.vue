<template>

<div>


    <!-- Header -->

    <div class="mb-3">

        <h6 class="fw-bold">

            {{ title }}

        </h6>


        <small class="text-secondary">

            Current inventory status

        </small>

    </div>





    <!-- Table -->

    <div class="table-responsive">


        <table class="table table-hover align-middle">


            <thead class="table-light">


                <tr>


                    <th>
                        SL
                    </th>


                    <th>
                        Raw Material
                    </th>


                    <th>
                        Unit
                    </th>


                    <th>
                        Quantity
                    </th>


                    <th>
                        Average Cost
                    </th>


                    <th>
                        Total Value
                    </th>


                    <th>
                        Status
                    </th>


                </tr>


            </thead>






            <tbody>


                <tr

                    v-for="(item,index) in items"

                    :key="item.id"

                >



                    <td>

                        {{ index + 1 }}

                    </td>






                    <td>

                        {{
                            item.raw_material?.material_name
                            ||
                            '-'
                        }}

                    </td>






                    <td>

                        {{

                            item.raw_material?.base_unit

                            ||

                            '-'

                        }}

                    </td>






                    <td>

                        {{

                            item.quantity

                            ||

                            0

                        }}

                    </td>






                    <td>

                        {{

                            money(

                                item.average_unit_cost

                            )

                        }}

                    </td>






                    <td>

                        {{

                            money(

                                totalValue(item)

                            )

                        }}

                    </td>






                    <td>


                        <span

                            class="badge"

                            :class="statusClass(item)"

                        >

                            {{ stockStatus(item) }}

                        </span>


                    </td>



                </tr>







                <tr

                    v-if="items.length === 0"

                >


                    <td

                        colspan="7"

                        class="text-center py-4"

                    >

                        No stock data found


                    </td>


                </tr>



            </tbody>



        </table>



    </div>


</div>


</template>







<script setup>


import {

    computed

}

from 'vue'





const props = defineProps({


    items:{

        type:Array,

        default:()=>[]

    },



    type:{

        type:String,

        default:'restaurant'

    }


})








const title = computed(()=>{


    return props.type === 'warehouse'

        ?

        'Warehouse Stock'

        :

        'Restaurant Stock'


})








function totalValue(item)
{

    return (

        Number(
            item.quantity
            ||
            0
        )

        *

        Number(
            item.average_unit_cost
            ||
            0
        )

    )

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








function stockStatus(item)
{


    const qty = Number(

        item.quantity || 0

    )



    if(qty <= 0)

    {

        return 'Out of Stock'

    }



    if(qty <= 10)

    {

        return 'Low Stock'

    }



    return 'Available'


}









function statusClass(item)
{


    const status = stockStatus(item)



    if(status === 'Out of Stock')

    {

        return 'bg-danger'

    }



    if(status === 'Low Stock')

    {

        return 'bg-warning text-dark'

    }



    return 'bg-success'


}



</script>







<style scoped>


table{

    font-size:

    .9rem;

}



.badge{

    padding:

    7px 12px;

    border-radius:

    20px;

}



</style>