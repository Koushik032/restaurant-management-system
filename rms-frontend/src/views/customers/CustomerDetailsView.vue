<template>
  <section class="customer-details-page">

    <!-- ==================================================
         Page Header
    =================================================== -->

    <header class="customer-details-header">

      <div class="customer-details-heading">

        <button
          type="button"
          class="customer-details-back-button"
          @click="goBack"
        >
          <i class="bi bi-arrow-left"></i>
        </button>


        <div>

          <p class="customer-details-eyebrow">
            Customer Management
          </p>


          <h1>
            Customer Details
          </h1>


          <p>
            View customer profile and visit history.
          </p>

        </div>

      </div>



      <button
        type="button"
        class="customer-details-edit-button"
        @click="goEdit"
      >
        <i class="bi bi-pencil-square"></i>

        Edit Customer
      </button>


    </header>



    <!-- ==================================================
         Error Message
    =================================================== -->


    <div
      v-if="globalError"
      class="customer-details-alert"
    >

      <i class="bi bi-exclamation-triangle-fill"></i>

      <span>
        {{ globalError }}
      </span>

    </div>



    <!-- ==================================================
         Loading
    =================================================== -->


    <div
      v-if="loading"
      class="customer-details-loading"
    >

      <span
        class="spinner-border"
      ></span>


      <p>
        Loading customer information...
      </p>


    </div>



    <!-- ==================================================
         Main Content
    =================================================== -->


    <template
      v-else
    >


      <!-- ================================================
           Customer Profile
      ================================================= -->


      <section
        class="customer-profile-card"
      >

        <div
          class="customer-profile-main"
        >

          <div
            class="customer-avatar"
          >

            <i class="bi bi-person-fill"></i>

          </div>



          <div>

            <h2>
              {{ customer.name }}
            </h2>


            <p>
              <i class="bi bi-telephone"></i>

              {{ customer.phone }}
            </p>


            <p
              v-if="customer.email"
            >
              <i class="bi bi-envelope"></i>

              {{ customer.email }}
            </p>


          </div>


        </div>



        <div
          class="customer-profile-actions"
        >

          <span
            class="customer-status-badge"
            :class="{
              active:
                customer.is_active,

              inactive:
                !customer.is_active
            }"
          >

            {{
              customer.is_active
                ? 'Active'
                : 'Inactive'
            }}

          </span>


        </div>


      </section>




      <!-- ================================================
           Summary Cards
      ================================================= -->


      <section
        class="customer-details-summary"
      >


        <article
          class="customer-details-summary-card"
        >

          <span>
            Total Visits
          </span>


          <strong>
            {{ summary.visit_count || 0 }}
          </strong>


        </article>



        <article
          class="customer-details-summary-card"
        >

          <span>
            Total Spend
          </span>


          <strong>
            {{
              summary.total_order_amount_formatted
              || '৳ 0.00'
            }}
          </strong>


        </article>



        <article
          class="customer-details-summary-card"
        >

          <span>
            Paid Amount
          </span>


          <strong>
            {{
              summary.total_paid_amount_formatted
              || '৳ 0.00'
            }}
          </strong>


        </article>



        <article
          class="customer-details-summary-card"
        >

          <span>
            Due Amount
          </span>


          <strong>
            {{
              summary.total_due_amount_formatted
              || '৳ 0.00'
            }}
          </strong>


        </article>


      </section>




      <!-- ================================================
           Visit History Placeholder
      ================================================= -->


      <!-- ================================================
     Visit History
================================================= -->


<section
  class="customer-history-card"
>


  <header
    class="customer-history-header"
  >

    <div>

      <h2>
        Visit History
      </h2>


      <p>
        Complete order history of this customer.
      </p>

    </div>


    <span
      class="customer-history-count"
    >

      Total:
      {{ pagination.total || 0 }}

    </span>


  </header>



  <!-- ================================================
       Empty State
  ================================================= -->


  <div
    v-if="orders.length === 0"
    class="customer-history-empty"
  >

    <i
      class="bi bi-receipt-cutoff"
    ></i>


    <h3>
      No visit history found
    </h3>


    <p>
      This customer has not placed any orders yet.
    </p>


  </div>



  <!-- ================================================
       History Table
  ================================================= -->


  <div
    v-else
    class="customer-history-table-wrapper"
  >


    <table
      class="customer-history-table"
    >


      <thead>

        <tr>

          <th>
            SL
          </th>


          <th>
            Order ID
          </th>


          <th>
            Visit Date
          </th>


          <th>
            Ordered Items
          </th>


          <th>
            Total Amount
          </th>


          <th>
            Paid
          </th>


          <th>
            Due
          </th>


        </tr>


      </thead>



      <tbody>


        <tr
          v-for="(
            order,
            index
          ) in orders"

          :key="order.id"
        >


          <!-- SL -->

          <td>

            {{
              (
                (pagination.current_page - 1)
                *
                pagination.per_page
              )
              +
              index
              +
              1
            }}

          </td>



          <!-- Order Number -->

          <td>

            <strong>
              {{ order.order_number }}
            </strong>

          </td>



          <!-- Visit Date -->

          <td>

            <div
              class="customer-visit-date"
            >

              <strong>
                {{ order.visit_date }}
              </strong>


              <small>
                {{ order.visit_time }}
              </small>


            </div>


          </td>




          <!-- Ordered Items -->


          <td>


            <div
              class="customer-order-items"
            >


              <div
                v-for="item in order.items"

                :key="item.id"

                class="customer-order-item"
              >


                <div>

                  <strong>

                    {{ item.item_name }}

                    <span
                      v-if="item.variant_name"
                    >
                      ({{ item.variant_name }})
                    </span>


                  </strong>


                  <small>

                    Qty:
                    {{ item.quantity }}

                    ×

                    {{ item.unit_price_formatted }}

                  </small>


                </div>


                <strong>

                  {{ item.line_total_formatted }}

                </strong>


              </div>


            </div>


          </td>




          <!-- Total -->


          <td>

            <strong>

              {{ order.total_amount_formatted }}

            </strong>

          </td>




          <!-- Paid -->


          <td>

            <span
              class="amount-success"
            >

              {{ order.paid_amount_formatted }}

            </span>

          </td>




          <!-- Due -->


          <td>

            <span
              class="amount-danger"
            >

              {{ order.due_amount_formatted }}

            </span>

          </td>



        </tr>


      </tbody>


    </table>


  </div>



</section>
<div
  v-if="pagination.last_page > 1"
  class="customer-history-pagination"
>


  <button
    type="button"
    :disabled="
      pagination.current_page === 1
    "
    @click="
      changePage(
        pagination.current_page - 1
      )
    "
  >

    <i class="bi bi-chevron-left"></i>

    Previous

  </button>



  <span>

    Page
    {{ pagination.current_page }}

    of

    {{ pagination.last_page }}

  </span>




  <button
    type="button"
    :disabled="
      pagination.current_page ===
      pagination.last_page
    "

    @click="
      changePage(
        pagination.current_page + 1
      )
    "
  >

    Next

    <i class="bi bi-chevron-right"></i>

  </button>


</div>


    </template>


  </section>
</template>




<script setup>
import '@/assets/css/customers/customer-details.css'

import {
  computed,
  onMounted,
  ref,
} from 'vue'


import {
  useRoute,
  useRouter,
} from 'vue-router'


import customerService
  from '@/services/customerService'



/*
|--------------------------------------------------------------------------
| Router
|--------------------------------------------------------------------------
*/


const route =
  useRoute()


const router =
  useRouter()



/*
|--------------------------------------------------------------------------
| Page State
|--------------------------------------------------------------------------
*/


const loading =
  ref(false)


const globalError =
  ref('')



const customer =
  ref({})



const summary =
  ref({})



const orders =
  ref([])



const pagination =
  ref({})



/*
|--------------------------------------------------------------------------
| Customer ID
|--------------------------------------------------------------------------
*/


const customerId =
  computed(() => {

    return Number(
      route.params.id
    )

  })

  /*
|--------------------------------------------------------------------------
| Load Customer Details
|--------------------------------------------------------------------------
*/

async function loadCustomerDetails(
  page = 1
) {

  if (
    !customerId.value ||
    customerId.value <= 0
  ) {

    globalError.value =
      'Invalid customer ID.'

    return
  }


  loading.value = true

  globalError.value = ''


  try {

    const response =
      await customerService.getCustomerDetails(
        customerId.value,
        {
          page,

          per_page: 10,
        }
      )


    const data =
      response?.data ||
      {}


    /*
    |--------------------------------------------------------------------------
    | Customer Profile
    |--------------------------------------------------------------------------
    */


    customer.value =
      data.customer ||
      {}



    /*
    |--------------------------------------------------------------------------
    | Summary
    |--------------------------------------------------------------------------
    */


    summary.value =
      data.summary ||
      {}



    /*
    |--------------------------------------------------------------------------
    | Orders
    |--------------------------------------------------------------------------
    */


    orders.value =
  Array.isArray(
    data?.orders,
  )
    ? data.orders
    : []



    /*
    |--------------------------------------------------------------------------
    | Pagination
    |--------------------------------------------------------------------------
    */


    pagination.value =
      data.meta ||
      {}



  } catch (error) {


    globalError.value =
      customerService.getCustomerErrorMessage(
        error,
        'Unable to load customer details.'
      )


  } finally {


    loading.value = false

  }

}



/*
|--------------------------------------------------------------------------
| Go Back Customer List
|--------------------------------------------------------------------------
*/

function goBack() {

  router.push({
    name:
      'customers',
  })

}



/*
|--------------------------------------------------------------------------
| Edit Customer
|--------------------------------------------------------------------------
*/

function goEdit() {

  if (
    !customerId.value
  ) {

    return

  }


  router.push({

    name:
      'customer-edit',

    params: {

      id:
        customerId.value,

    },

  })

}



/*
|--------------------------------------------------------------------------
| Change History Page
|--------------------------------------------------------------------------
*/

async function changePage(
  page
) {

  if (
    !page ||
    page === pagination.value.current_page
  ) {

    return

  }


  await loadCustomerDetails(
    page
  )

}



/*
|--------------------------------------------------------------------------
| Initial Load
|--------------------------------------------------------------------------
*/

onMounted(() => {

  loadCustomerDetails()

})


</script>