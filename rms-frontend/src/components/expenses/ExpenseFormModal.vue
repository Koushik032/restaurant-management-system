<template>

<div
  v-if="show"
  class="expense-modal-overlay"
>


  <div class="expense-modal">


    <!-- ==================================================
         Header
    =================================================== -->

    <div class="expense-modal-header">


      <h3>

        <i class="bi bi-wallet2"></i>

        {{ 
          isEditMode
            ? 'Edit Expense'
            : 'Add Expense'
        }}

      </h3>



      <button

        type="button"

        class="close-btn"

        @click="close"

      >

        <i class="bi bi-x-lg"></i>

      </button>


    </div>





    <!-- ==================================================
         Form
    =================================================== -->


    <form
      @submit.prevent="submit"
    >



      <div class="expense-form-grid">





        <!-- Expense Date -->

        <div class="form-group">


          <label>
            Expense Date
          </label>


          <input

            type="date"

            v-model="form.expense_date"

            required

          />


        </div>







        <!-- Category -->


        <div class="form-group">


          <label>
            Category
          </label>



          <select

            v-model="form.expense_category_id"

            required

          >


            <option value="">
              Select Category
            </option>



            <option

              v-for="category in categories"

              :key="category.id"

              :value="category.id"

            >

              {{ category.name }}

            </option>



          </select>


        </div>








        <!-- Amount -->


        <div class="form-group">


          <label>
            Amount
          </label>



          <input

            type="number"

            step="0.01"

            min="0.01"

            v-model="form.amount"

            placeholder="Enter amount"

            required

          />


        </div>








        <!-- Payment Method -->


        <div class="form-group">


          <label>
            Payment Method
          </label>



          <select

            v-model="form.payment_method"

            required

          >


            <option value="">
              Select Method
            </option>



            <option

              v-for="method in paymentMethods"

              :key="method.value"

              :value="method.value"

            >

              {{ method.label }}

            </option>



          </select>


        </div>








        <!-- Paid By -->


        <div class="form-group">


          <label>
            Paid By
          </label>



          <input

            type="text"

            :value="paidByName"

            readonly

            class="readonly-input"

          />



          <small>
            Automatically assigned user
          </small>



        </div>




      </div>







      <!-- Notes -->


      <div class="form-group">


        <label>
          Notes
        </label>



        <textarea

          v-model="form.notes"

          rows="3"

          placeholder="Expense notes..."

        ></textarea>



      </div>







      <!-- Buttons -->


      <div class="expense-form-actions">


        <button

          type="button"

          class="btn-secondary"

          @click="close"

        >

          Cancel

        </button>





        <button

          type="submit"

          class="btn-primary"

          :disabled="loading"

        >



          <span

            v-if="loading"

            class="spinner-border spinner-border-sm"

          ></span>



          {{

            loading

            ?

            'Saving...'

            :

            (
              isEditMode

              ?

              'Update Expense'

              :

              'Save Expense'

            )

          }}


        </button>


      </div>




    </form>


  </div>


</div>


</template>







<script setup>


import {

  computed,

  reactive,

  watch,

} from 'vue'





/*
|--------------------------------------------------------------------------
| Props
|--------------------------------------------------------------------------
*/


const props = defineProps({



  show:{

    type:Boolean,

    default:false

  },



  expense:{

    type:Object,

    default:null

  },



  categories:{

    type:Array,

    default:()=>[]

  },



  paymentMethods:{

    type:Array,

    default:()=>[]

  },



  loading:{

    type:Boolean,

    default:false

  }



})







/*
|--------------------------------------------------------------------------
| Emits
|--------------------------------------------------------------------------
*/


const emit = defineEmits([

  'close',

  'submit'

])







/*
|--------------------------------------------------------------------------
| Form State
|--------------------------------------------------------------------------
*/


const form = reactive({


  expense_category_id:'',


  expense_date:'',


  amount:'',


  payment_method:'',


  notes:''


})







/*
|--------------------------------------------------------------------------
| Edit Mode
|--------------------------------------------------------------------------
*/


const isEditMode = computed(()=>{


  return Boolean(
    props.expense
  )


})








/*
|--------------------------------------------------------------------------
| Paid By
|--------------------------------------------------------------------------
*/


const paidByName = computed(()=>{


  return (

    props.expense?.paid_by_name

    ||

    'Current User'

  )


})









/*
|--------------------------------------------------------------------------
| Fill Existing Data
|--------------------------------------------------------------------------
*/


watch(

  ()=>props.expense,


  (expense)=>{


    if(expense){



      form.expense_category_id =

        expense.category?.id

        ??

        expense.expense_category_id

        ??

        ''





      form.expense_date =

        expense.expense_date

        ?

        expense.expense_date.substring(
          0,
          10
        )

        :

        ''





      form.amount =

        expense.amount

        ??

        ''





      form.payment_method =

        expense.payment_method

        ??

        ''





      form.notes =

        expense.notes

        ??

        ''



    }

    else{


      resetForm()


    }



  },


  {
    immediate:true

  }


)







/*
|--------------------------------------------------------------------------
| Submit
|--------------------------------------------------------------------------
*/


function submit(){


  emit(

    'submit',

    {

      ...form

    }

  )


}








/*
|--------------------------------------------------------------------------
| Close
|--------------------------------------------------------------------------
*/


function close(){


  emit(
    'close'
  )


}








/*
|--------------------------------------------------------------------------
| Reset
|--------------------------------------------------------------------------
*/


function resetForm(){


  form.expense_category_id=''


  form.expense_date=''


  form.amount=''


  form.payment_method=''


  form.notes=''


}





</script>