<template>
  <section class="expense-summary-grid">


    <!-- Total Amount -->

    <div class="expense-summary-card">

      <div class="expense-summary-icon">
        <i class="bi bi-cash-stack"></i>
      </div>


      <div>

        <p>
          Total Expenses
        </p>


        <h3>

          {{
            loading
              ? 'Loading...'
              :
              summary.total_amount_formatted
              ||
              '৳ 0.00'
          }}

        </h3>

      </div>

    </div>




    <!-- Total Records -->

    <div class="expense-summary-card">

      <div class="expense-summary-icon">

        <i class="bi bi-receipt"></i>

      </div>


      <div>

        <p>
          Total Records
        </p>


        <h3>

          {{
            loading
              ? '-'
              :
              summary.total_records
              ||
              0
          }}

        </h3>

      </div>

    </div>





    <!-- Average -->

    <div class="expense-summary-card">

      <div class="expense-summary-icon">

        <i class="bi bi-graph-up"></i>

      </div>


      <div>

        <p>
          Average Expense
        </p>


        <h3>

          {{
            loading
              ? 'Loading...'
              :
              summary.average_amount_formatted
              ||
              '৳ 0.00'
          }}

        </h3>


      </div>


    </div>


  </section>
</template>



<script setup>

defineProps({

  summary:{
    type:Object,
    default:()=>({})
  },


  loading:{
    type:Boolean,
    default:false,
  }

})

</script>