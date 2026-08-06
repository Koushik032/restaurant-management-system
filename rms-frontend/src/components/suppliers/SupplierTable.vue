<template>

<section class="supplier-table-card">


    <!-- Header -->

    <div class="supplier-table-header">


        <div>

            <h3>
                Supplier Information
            </h3>


            <p>
                Manage all supplier details
            </p>


        </div>





        <!-- Add Button -->


        <button

            type="button"

            class="supplier-add-btn"

            @click="openAddSupplier"

        >

            <i class="bi bi-plus-lg"></i>

            Add Supplier


        </button>



    </div>









    <!-- Table -->

    <div class="table-responsive">


        <table class="supplier-table">


            <thead>


                <tr>


                    <th>
                        SL No
                    </th>


                    <th>
                        Supplier Name
                    </th>


                    <th>
                        Contact Name
                    </th>


                    <th>
                        Email
                    </th>


                    <th>
                        Phone
                    </th>


                    <th>
                        Address
                    </th>


                    <th>
                        GSTIN
                    </th>


                    <th>
                        Action
                    </th>


                </tr>


            </thead>








            <tbody>



                <!-- Loading -->


                <tr v-if="loading">


                    <td

                    colspan="8"

                    class="supplier-loading"

                    >


                        Loading suppliers...


                    </td>


                </tr>







                <!-- Error -->


                <tr v-else-if="errorMessage">


                    <td

                    colspan="8"

                    class="supplier-error"

                    >


                        {{ errorMessage }}


                    </td>


                </tr>







                <!-- Empty -->


                <tr

                v-else-if="suppliers.length === 0"

                >


                    <td

                    colspan="8"

                    class="supplier-empty"

                    >


                        No suppliers found.


                    </td>


                </tr>







                <!-- Data -->


                <tr

                v-else

                v-for="(supplier,index) in suppliers"

                :key="supplier.id"

                >



                    <td>

                        {{ index + 1 }}

                    </td>






                    <td>


                        <strong>

                            {{ supplier.supplier_name }}

                        </strong>


                    </td>







                    <td>

                        {{ supplier.contact_person || '-' }}

                    </td>







                    <td>

                        {{ supplier.email || '-' }}

                    </td>







                    <td>

                        {{ supplier.phone || '-' }}

                    </td>







                    <td>

                        {{ supplier.address || '-' }}

                    </td>







                    <td>

                        {{ supplier.gstin || '-' }}

                    </td>








                    <td>


                        <div class="supplier-actions">


                            <!-- Edit -->


                            <button

                            type="button"

                            class="action-btn edit-btn"

                            @click="editSupplier(supplier)"

                            >


                                <i class="bi bi-pencil"></i>


                            </button>



                            <!-- Delete -->


                            <button

                            type="button"

                            class="action-btn delete-btn"

                            @click="deleteSupplier(supplier)"

                            >


                                <i class="bi bi-trash"></i>


                            </button>


                        </div>


                    </td>


                </tr>






            </tbody>



        </table>


    </div>




</section>


</template>









<script setup>


import {


    onMounted,


    onBeforeUnmount,


    ref


} from 'vue'





import supplierService

from '@/services/supplierService'







/*
|--------------------------------------------------------------------------
| Emits
|--------------------------------------------------------------------------
*/


const emit = defineEmits([


    'add',


    'edit',


    'delete'


])








/*
|--------------------------------------------------------------------------
| State
|--------------------------------------------------------------------------
*/


const suppliers =

    ref([])





const loading =

    ref(false)





const errorMessage =

    ref('')









/*
|--------------------------------------------------------------------------
| Load Suppliers
|--------------------------------------------------------------------------
*/


async function loadSuppliers(){


    loading.value = true



    errorMessage.value = ''






    try{


        const response =

            await supplierService
                .getSuppliers()





        suppliers.value =

            response.data ?? []



    }




    catch(error){



        errorMessage.value =

            supplierService
                .getErrorMessage(

                    error,

                    'Unable to load suppliers.'

                )



    }






    finally{


        loading.value = false


    }



}









/*
|--------------------------------------------------------------------------
| Refresh Listener
|--------------------------------------------------------------------------
*/


function refreshSuppliers(){


    loadSuppliers()


}









/*
|--------------------------------------------------------------------------
| Add Supplier
|--------------------------------------------------------------------------
*/


function openAddSupplier(){


    emit('add')


}









/*
|--------------------------------------------------------------------------
| Edit Supplier
|--------------------------------------------------------------------------
*/


function editSupplier(
    supplier
){


    emit(

        'edit',

        supplier

    )


}









/*
|--------------------------------------------------------------------------
| Delete Supplier
|--------------------------------------------------------------------------
*/


function deleteSupplier(
    supplier
){


    emit(

        'delete',

        supplier

    )


}









/*
|--------------------------------------------------------------------------
| Lifecycle
|--------------------------------------------------------------------------
*/


onMounted(()=>{


    loadSuppliers()



    window.addEventListener(

        'supplier-refresh',

        refreshSuppliers

    )


})








onBeforeUnmount(()=>{


    window.removeEventListener(

        'supplier-refresh',

        refreshSuppliers

    )


})





</script>