<template>

<section class="supplier-management-page">



    <!-- Header -->

    <SupplierPageHeader />






    <!-- Tabs -->

    <SupplierTabs

        v-model="activeTab"

    />









    <!-- Supplier Section -->


    <SupplierTable

        v-if="activeTab === 'suppliers'"

        @add="openAddSupplier"

        @edit="openEditSupplier"

        @delete="deleteSupplier"

    />









    <!-- Supplier Modal -->


    <SupplierFormModal


        :show="showSupplierModal"


        :loading="savingSupplier"


        :supplier="selectedSupplier"


        @close="closeSupplierModal"


        @submit="saveSupplier"


    />











    <!-- Purchase Order Section -->


    <PurchaseOrderSection


        v-if="activeTab === 'purchase_orders'"


    />





</section>


</template>









<script setup>


import {

    ref

} from 'vue'







import supplierService

from '@/services/supplierService'







/*
|--------------------------------------------------------------------------
| Components
|--------------------------------------------------------------------------
*/


import SupplierPageHeader

from '@/components/suppliers/SupplierPageHeader.vue'





import SupplierTabs

from '@/components/suppliers/SupplierTabs.vue'





import SupplierTable

from '@/components/suppliers/SupplierTable.vue'





import SupplierFormModal

from '@/components/suppliers/SupplierFormModal.vue'







import PurchaseOrderSection

from '@/components/purchaseOrders/PurchaseOrderSection.vue'











/*
|--------------------------------------------------------------------------
| State
|--------------------------------------------------------------------------
*/


const activeTab = ref(

    'suppliers'

)







const showSupplierModal = ref(false)







const savingSupplier = ref(false)







const selectedSupplier = ref(null)











/*
|--------------------------------------------------------------------------
| Add Supplier
|--------------------------------------------------------------------------
*/


function openAddSupplier(){


    selectedSupplier.value = null



    showSupplierModal.value = true


}









/*
|--------------------------------------------------------------------------
| Edit Supplier
|--------------------------------------------------------------------------
*/


function openEditSupplier(

    supplier

){


    selectedSupplier.value = supplier



    showSupplierModal.value = true


}









/*
|--------------------------------------------------------------------------
| Close Supplier Modal
|--------------------------------------------------------------------------
*/


function closeSupplierModal(){


    showSupplierModal.value = false



    selectedSupplier.value = null


}









/*
|--------------------------------------------------------------------------
| Save Supplier
|--------------------------------------------------------------------------
*/


async function saveSupplier(

    formData

){


    savingSupplier.value = true






    try{





        if(selectedSupplier.value){



            await supplierService.updateSupplier(


                selectedSupplier.value.id,


                formData


            )


        }



        else{



            await supplierService.createSupplier(


                formData


            )


        }







        closeSupplierModal()






        window.dispatchEvent(


            new Event(

                'supplier-refresh'

            )


        )




    }




    catch(error){


        console.error(

            error

        )


    }





    finally{


        savingSupplier.value = false


    }



}









/*
|--------------------------------------------------------------------------
| Delete Supplier
|--------------------------------------------------------------------------
*/


async function deleteSupplier(

    supplier

){



    const confirmDelete =

        window.confirm(


            `Are you sure you want to delete ${supplier.supplier_name}?`


        )







    if(!confirmDelete){


        return


    }







    try{



        await supplierService.deleteSupplier(


            supplier.id


        )






        window.dispatchEvent(


            new Event(

                'supplier-refresh'

            )


        )




    }





    catch(error){


        console.error(

            error

        )


    }



}



</script>









<style>


@import '@/assets/css/suppliers/supplier-header.css';


@import '@/assets/css/suppliers/supplier-tabs.css';


@import '@/assets/css/suppliers/supplier-table.css';


@import '@/assets/css/suppliers/supplier-modal.css';


@import '@/assets/css/suppliers/supplier-responsive.css';



</style>