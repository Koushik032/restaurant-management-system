<template>

<div
    v-if="show"
    class="supplier-modal-overlay"
>


<div class="supplier-modal">


    <!-- Header -->

    <div class="supplier-modal-header">


        <h3>

            <i class="bi bi-truck"></i>

            {{ isEdit ? 'Edit Supplier' : 'Add Supplier' }}

        </h3>





        <button

            type="button"

            class="close-btn"

            @click="closeModal"

        >

            <i class="bi bi-x-lg"></i>

        </button>


    </div>







    <!-- Form -->

    <form

        @submit.prevent="submitForm"

    >



        <div class="supplier-form-grid">






            <!-- Supplier Name -->


            <div class="form-group">


                <label>

                    Supplier Name *

                </label>



                <input

                    v-model="form.supplier_name"

                    type="text"

                    placeholder="Enter supplier name"

                    required

                />


            </div>








            <!-- Contact Person -->


            <div class="form-group">


                <label>

                    Contact Person

                </label>



                <input

                    v-model="form.contact_person"

                    type="text"

                    placeholder="Contact person"

                />


            </div>









            <!-- Email -->


            <div class="form-group">


                <label>

                    Email

                </label>



                <input

                    v-model="form.email"

                    type="email"

                    placeholder="Email address"

                />


            </div>









            <!-- Phone -->


            <div class="form-group">


                <label>

                    Phone *

                </label>



                <input

                    v-model="form.phone"

                    type="text"

                    placeholder="Phone number"

                    required

                />


            </div>









            <!-- GSTIN -->


            <div class="form-group">


                <label>

                    GSTIN

                </label>



                <input

                    v-model="form.gstin"

                    type="text"

                    placeholder="GSTIN"

                />


            </div>









            <!-- Address -->


            <div class="form-group full-width">


                <label>

                    Address

                </label>



                <textarea

                    v-model="form.address"

                    rows="3"

                    placeholder="Address"

                ></textarea>


            </div>




        </div>








        <!-- Actions -->


        <div class="supplier-modal-actions">



            <button

                type="button"

                class="cancel-btn"

                @click="closeModal"

            >

                Cancel

            </button>







            <button

                type="submit"

                class="save-btn"

                :disabled="loading"

            >


                <span v-if="loading">

                    Saving...

                </span>


                <span v-else>


                    {{ isEdit ? 'Update Supplier' : 'Save Supplier' }}


                </span>


            </button>




        </div>




    </form>



</div>


</div>


</template>







<script setup>


import {

    reactive,

    computed,

    watch

} from 'vue'






const props = defineProps({



    show:{


        type:Boolean,

        default:false


    },




    loading:{


        type:Boolean,

        default:false


    },




    supplier:{


        type:Object,

        default:null


    }



})






const emit = defineEmits([


    'close',


    'submit'


])









const form = reactive({


    supplier_name:'',


    contact_person:'',


    email:'',


    phone:'',


    address:'',


    gstin:''


})









const isEdit = computed(()=>{


    return !!props.supplier


})









watch(

    ()=>props.supplier,


    (supplier)=>{


        if(supplier){


            form.supplier_name =
                supplier.supplier_name ?? ''


            form.contact_person =
                supplier.contact_person ?? ''


            form.email =
                supplier.email ?? ''


            form.phone =
                supplier.phone ?? ''


            form.address =
                supplier.address ?? ''


            form.gstin =
                supplier.gstin ?? ''


        }


        else{


            resetForm()


        }


    },

    {
        immediate:true
    }

)









function resetForm(){


    form.supplier_name=''


    form.contact_person=''


    form.email=''


    form.phone=''


    form.address=''


    form.gstin=''


}









function closeModal(){


    resetForm()


    emit('close')


}









function submitForm(){



    emit(

        'submit',

        {

            ...form

        }

    )



}






</script>