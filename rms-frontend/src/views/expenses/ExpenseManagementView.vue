<template>

  <section class="expense-management-page">


    <!-- ==================================================
         Page Header
    =================================================== -->

    <ExpensePageHeader

      @refresh="loadAllData"

      @add="openAddExpense"

    />




    <!-- ==================================================
         Summary Cards
    =================================================== -->

    <ExpenseSummaryCards

      :summary="summary"

      :loading="summaryLoading"

    />




    <!-- ==================================================
         Filters
    =================================================== -->


    <ExpenseFilters

      v-model:filters="filters"

      :categories="categories"

      :payment-methods="paymentMethods"

      :loading="loading"

      @apply="applyFilters"

      @clear="clearFilters"

      @print="printReport"

    />





    <!-- ==================================================
         Table
    =================================================== -->


<ExpenseTable

:expenses="expenses"

:loading="loading"

:meta="meta"

@delete="handleDelete"

@edit="openEditExpense"

@page-change="changePage"

/>





    <!-- ==================================================
         Add Expense Modal
    =================================================== -->


<ExpenseFormModal

:show="showExpenseModal"

:expense="editingExpense"

:categories="categories"

:payment-methods="paymentMethods"

:loading="savingExpense"

@close="closeExpenseModal"

@submit="saveExpense"

/>



  </section>


</template>





<script setup>


import {
  onMounted,
  reactive,
  ref,
} from 'vue'



import expenseService
  from '@/services/expenseService'



/*
|--------------------------------------------------------------------------
| Components
|--------------------------------------------------------------------------
*/


import ExpensePageHeader
  from '@/components/expenses/ExpensePageHeader.vue'


import ExpenseSummaryCards
  from '@/components/expenses/ExpenseSummaryCards.vue'


import ExpenseFilters
  from '@/components/expenses/ExpenseFilters.vue'


import ExpenseTable
  from '@/components/expenses/ExpenseTable.vue'


import ExpenseFormModal
  from '@/components/expenses/ExpenseFormModal.vue'





/*
|--------------------------------------------------------------------------
| State
|--------------------------------------------------------------------------
*/


const expenses =
  ref([])



const categories =
  ref([])



const paymentMethods =
  ref([])



const summary =
  ref({})



const meta =
  ref({})



const loading =
  ref(false)



const summaryLoading =
  ref(false)



const errorMessage =
  ref('')



const successMessage =
  ref('')



const deleteLoadingId =
  ref(null)



const showExpenseModal =
  ref(false)

const editingExpense =
  ref(null)


const savingExpense =
  ref(false)






/*
|--------------------------------------------------------------------------
| Filters
|--------------------------------------------------------------------------
*/


const filters =
  reactive({

    date_from:'',

    date_to:'',

    category_id:'',

    payment_method:'',

    page:1,

    per_page:10,

  })







/*
|--------------------------------------------------------------------------
| Load All Data
|--------------------------------------------------------------------------
*/


async function loadAllData(){

  await Promise.all([

    loadOptions(),

    loadExpenses(),

    loadSummary(),

  ])

}







/*
|--------------------------------------------------------------------------
| Load Options
|--------------------------------------------------------------------------
*/


async function loadOptions(){


  try{


    const response =
      await expenseService
        .getOptions()



    categories.value =
      response
        ?.data
        ?.categories
        ||
        []



    paymentMethods.value =
      response
        ?.data
        ?.payment_methods
        ||
        []


  }


  catch(error){


    console.error(error)


  }


}



function openEditExpense(expense){


    editingExpense.value =
        expense


    showExpenseModal.value =
        true


}




/*
|--------------------------------------------------------------------------
| Load Expenses
|--------------------------------------------------------------------------
*/


async function loadExpenses(){


  loading.value =
    true



  try{


    const response =
      await expenseService
        .getExpenses(
          filters
        )



    expenses.value =
      response.data



    meta.value =
      response.meta



  }


  catch(error){


    errorMessage.value =
      expenseService
        .getErrorMessage(

          error,

          'Unable to load expenses.'

        )


  }


  finally{


    loading.value =
      false


  }


}








/*
|--------------------------------------------------------------------------
| Load Summary
|--------------------------------------------------------------------------
*/


async function loadSummary(){


  summaryLoading.value =
    true



  try{


    const response =
      await expenseService
        .getSummary(
          filters
        )



    summary.value =
      response.data



  }


  catch(error){


    console.error(error)


  }


  finally{


    summaryLoading.value =
      false


  }


}








/*
|--------------------------------------------------------------------------
| Apply Filters
|--------------------------------------------------------------------------
*/


async function applyFilters(){


  filters.page =
    1



  await Promise.all([

    loadExpenses(),

    loadSummary(),

  ])


}







/*
|--------------------------------------------------------------------------
| Clear Filters
|--------------------------------------------------------------------------
*/


async function clearFilters(){


  filters.date_from =
    ''


  filters.date_to =
    ''


  filters.category_id =
    ''


  filters.payment_method =
    ''



  filters.page =
    1



  await applyFilters()


}








/*
|--------------------------------------------------------------------------
| Pagination
|--------------------------------------------------------------------------
*/


async function changePage(page){


  filters.page =
    page



  await loadExpenses()


}








/*
|--------------------------------------------------------------------------
| Delete Expense
|--------------------------------------------------------------------------
*/


async function handleDelete(expense){


  const confirmed =
    window.confirm(
      'Delete this expense?'
    )



  if(!confirmed){

    return

  }



  deleteLoadingId.value =
    expense.id



  try{


    await expenseService
      .deleteExpense(
        expense.id
      )



    await loadAllData()



  }


  catch(error){


    errorMessage.value =
      expenseService
        .getErrorMessage(

          error,

          'Delete failed.'

        )


  }


  finally{


    deleteLoadingId.value =
      null


  }


}








/*
|--------------------------------------------------------------------------
| Add Expense Modal
|--------------------------------------------------------------------------
*/


function openAddExpense(){


  showExpenseModal.value =
    true


}




function closeExpenseModal(){


    showExpenseModal.value =
        false


    editingExpense.value =
        null


}








/*
|--------------------------------------------------------------------------
| Save Expense
|--------------------------------------------------------------------------
*/


async function saveExpense(payload){


  savingExpense.value =
    true



  try{


    if(editingExpense.value){


    await expenseService
        .updateExpense(

            editingExpense.value.id,

            payload

        )


}
else{


    await expenseService
        .createExpense(
            payload
        )


}


    showExpenseModal.value =
      false



    await loadAllData()



  }


  catch(error){


    errorMessage.value =
      expenseService
        .getErrorMessage(

          error,

          'Unable to create expense.'

        )


  }


  finally{


    savingExpense.value =
      false


  }


}








/*
|--------------------------------------------------------------------------
| Print
|--------------------------------------------------------------------------
*/


function printReport(){


  window.print()


}






onMounted(
  loadAllData
)



</script>






<style>


@import '@/assets/css/expenses/expense-header.css';


@import '@/assets/css/expenses/expense-summary.css';


@import '@/assets/css/expenses/expense-filter.css';


@import '@/assets/css/expenses/expense-table.css';


@import '@/assets/css/expenses/expense-modal.css';



</style>