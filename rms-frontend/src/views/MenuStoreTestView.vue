<script setup>
import { onMounted } from "vue";
import { storeToRefs } from "pinia";
import { useMenuManagementStore } from "../stores/menuManagement";

const menuStore = useMenuManagementStore();

const {
  categories,
  menuItems,
  variants,
  addOnCategories,
  addOns,
  loading,
  errorMessage,
} = storeToRefs(menuStore);

onMounted(async () => {
  try {
    await menuStore.fetchCategories();
    await menuStore.fetchMenuItems();
    await menuStore.fetchVariants();
    await menuStore.fetchAddOnCategories();
    await menuStore.fetchAddOns();
  } catch (error) {
    console.error("Menu store test failed:", error);
  }
});
</script>

<template>
  <div class="container py-4">
    <h2 class="mb-4">Menu Store Test</h2>

    <div
      v-if="errorMessage"
      class="alert alert-danger"
    >
      {{ errorMessage }}
    </div>

    <div
      v-if="
        loading.categories ||
        loading.menuItems ||
        loading.variants ||
        loading.addOnCategories ||
        loading.addOns
      "
      class="alert alert-info"
    >
      Loading menu data...
    </div>

    <div v-else class="row g-3">
      <div class="col-md-4">
        <div class="card p-3">
          <h5>Categories</h5>
          <h3>{{ categories.length }}</h3>
        </div>
      </div>

      <div class="col-md-4">
        <div class="card p-3">
          <h5>Menu Items</h5>
          <h3>{{ menuItems.length }}</h3>
        </div>
      </div>

      <div class="col-md-4">
        <div class="card p-3">
          <h5>Variants</h5>
          <h3>{{ variants.length }}</h3>
        </div>
      </div>

      <div class="col-md-4">
        <div class="card p-3">
          <h5>Add-on Categories</h5>
          <h3>{{ addOnCategories.length }}</h3>
        </div>
      </div>

      <div class="col-md-4">
        <div class="card p-3">
          <h5>Add-ons</h5>
          <h3>{{ addOns.length }}</h3>
        </div>
      </div>
    </div>

    <hr class="my-4" />

    <h4>Menu Categories</h4>

    <ul>
      <li
        v-for="category in categories"
        :key="category.id"
      >
        {{ category.category_name }}
      </li>
    </ul>
  </div>
</template>