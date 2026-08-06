<template>
  <section class="expense-page-header">


    <!-- ==================================================
         Header Content
    =================================================== -->

    <div class="expense-header-content">

      <div class="expense-header-icon">

        <i
          class="bi bi-wallet2"
          aria-hidden="true"
        ></i>

      </div>


      <div>

        <h1 class="expense-title">
          Expense Management
        </h1>


        <p class="expense-description">

          Manage restaurant expenses,
          payments and financial records.

        </p>

      </div>


    </div>




    <!-- ==================================================
         Header Actions
    =================================================== -->


    <div class="expense-header-actions">


<!-- Refresh Button -->

<button
  type="button"
  class="expense-header-btn"
  @click="refresh"
>

<i
class="bi bi-arrow-clockwise"
></i>


<span>
Refresh
</span>

</button>





<!-- Add Expense Button -->

<button
  type="button"
  class="expense-header-btn expense-add-btn"
  @click="addExpense"
>


<i
class="bi bi-plus-lg"
></i>


<span>
Add Expense
</span>


</button>



</div>


  </section>
</template>




<script setup>

/*
|--------------------------------------------------------------------------
| Events
|--------------------------------------------------------------------------
*/


const emit =
  defineEmits([

    'refresh',

    'add',

])




function refresh(){

  emit(
    'refresh'
  )

}
function addExpense(){

    emit(
        'add'
    )

}


</script>