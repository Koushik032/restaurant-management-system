<template>

<section class="supplier-tabs-wrapper">


    <div class="supplier-tabs">


        <!-- Suppliers Tab -->

        <button

            type="button"

            class="supplier-tab"

            :class="{
                active: modelValue === 'suppliers'
            }"

            @click="changeTab('suppliers')"

        >

            <i class="bi bi-people"></i>


            <span>
                Suppliers
            </span>


        </button>






        <!-- Purchase Orders Tab -->

        <button

            type="button"

            class="supplier-tab"

            :class="{
                active: modelValue === 'purchase_orders'
            }"

            @click="changeTab('purchase_orders')"

        >


            <i class="bi bi-cart-check"></i>


            <span>
                Purchase Orders
            </span>


        </button>




    </div>


</section>


</template>







<script setup>


defineProps({

    modelValue:{

        type:String,

        default:'suppliers'

    }

})





const emit = defineEmits([

    'update:modelValue'

])






function changeTab(tab){


    emit(

        'update:modelValue',

        tab

    )


}



</script>