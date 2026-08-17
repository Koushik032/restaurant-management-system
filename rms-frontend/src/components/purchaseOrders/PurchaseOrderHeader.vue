<template>
  <section class="purchase-order-header-card">
    <div class="purchase-order-header-content">
      <!-- Left Content -->

      <div class="purchase-order-header-text">
        <h2>
          <i
            class="bi bi-cart-check"
            aria-hidden="true"
          ></i>

          Purchase Orders
        </h2>

        <p>
          Manage supplier purchase orders,
          items and payments.
        </p>
      </div>


      <!-- Right Actions -->

      <div class="purchase-order-header-actions">
        <!-- Refresh Button -->

        <button
          type="button"
          class="po-refresh-btn"
          title="Refresh Purchase Orders"
          :disabled="loading"
          :aria-busy="loading"
          :aria-label="
            loading
              ? 'Refreshing purchase orders'
              : 'Refresh purchase orders'
          "
          @click="refresh"
        >
          <i
            class="bi bi-arrow-clockwise"
            :class="{
              'inventory-refresh-spin':
                loading,
            }"
            aria-hidden="true"
          ></i>

          {{
            loading
              ? 'Refreshing...'
              : 'Refresh'
          }}
        </button>


        <!-- Add Purchase Order -->

        <button
          v-if="canManagePurchase"
          type="button"
          class="po-add-btn"
          title="Create New Purchase Order"
          :disabled="loading"
          aria-label="Create new purchase order"
          @click="addPurchaseOrder"
        >
          <i
            class="bi bi-plus-lg"
            aria-hidden="true"
          ></i>

          Add Purchase Order
        </button>
      </div>
    </div>
  </section>
</template>


<script setup>
import {
  computed,
} from 'vue'


import {
  useAuthStore,
} from '@/stores/auth'


/*
|--------------------------------------------------------------------------
| Props / Emits
|--------------------------------------------------------------------------
*/


const props = defineProps({
  loading: {
    type: Boolean,
    default: false,
  },
})


const emit = defineEmits([
  'refresh',
  'add',
])


/*
|--------------------------------------------------------------------------
| Permission
|--------------------------------------------------------------------------
|
| Purchase-order create/update authorization accepts either inventory.manage
| or suppliers.manage. Use the project's custom permission API only.
|
*/


const authStore =
  useAuthStore()


const canManagePurchase =
  computed(() => {
    if (
      typeof authStore.hasPermission
      !==
      'function'
    ) {
      return false
    }


    return Boolean(
      authStore.hasPermission(
        'inventory.manage',
      )
      ||
      authStore.hasPermission(
        'suppliers.manage',
      ),
    )
  })


/*
|--------------------------------------------------------------------------
| Actions
|--------------------------------------------------------------------------
*/


function refresh() {
  if (props.loading) {
    return
  }


  emit(
    'refresh',
  )
}


function addPurchaseOrder() {
  if (
    props.loading
    ||
    !canManagePurchase.value
  ) {
    return
  }


  emit(
    'add',
  )
}
</script>
