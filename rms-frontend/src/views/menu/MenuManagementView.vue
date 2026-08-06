<script setup>
import {
  computed,
  ref,
} from 'vue'

import MenuCategoriesSection from '@/components/menu/sections/MenuCategoriesSection.vue'
import MenuItemsSection from '@/components/menu/sections/MenuItemsSection.vue'
import MenuVariantsSection from '@/components/menu/sections/MenuVariantsSection.vue'
import AddOnsSection from '@/components/menu/sections/AddOnsSection.vue'

const activeTab = ref('categories')

const tabs = [
  {
    id: 'categories',
    label: 'Categories',
    icon: 'bi bi-grid',
    component: MenuCategoriesSection,
  },
  {
    id: 'items',
    label: 'Menu Items',
    icon: 'bi bi-card-list',
    component: MenuItemsSection,
  },
  {
    id: 'variants',
    label: 'Variants',
    icon: 'bi bi-layers',
    component: MenuVariantsSection,
  },
  {
    id: 'addons',
    label: 'Add-ons',
    icon: 'bi bi-plus-circle',
    component: AddOnsSection,
  },
]

const activeComponent = computed(() => {
  return (
    tabs.find(
      (tab) => tab.id === activeTab.value,
    )?.component ?? MenuCategoriesSection
  )
})

const activeLabel = computed(() => {
  return (
    tabs.find(
      (tab) => tab.id === activeTab.value,
    )?.label ?? 'Categories'
  )
})
</script>

<template>
  <div class="menu-management-page">
    <header class="menu-management-header">
      <div>
        <p class="menu-management-eyebrow">
          Back Office
        </p>

        <h1 class="menu-management-title">
          Menu Management
        </h1>

        <p class="menu-management-description">
          Currently managing:
          <strong>{{ activeLabel }}</strong>
        </p>
      </div>
    </header>

    <section class="menu-management-card">
      <nav class="menu-management-tabs">
        <button
          v-for="tab in tabs"
          :key="tab.id"
          type="button"
          class="menu-management-tab"
          :class="{
            active: activeTab === tab.id,
          }"
          @click="activeTab = tab.id"
        >
          <i :class="tab.icon"></i>
          <span>{{ tab.label }}</span>
        </button>
      </nav>

      <KeepAlive>
        <component :is="activeComponent" />
      </KeepAlive>
    </section>
  </div>
</template>

<style src="@/assets/css/menu-management.css"></style>