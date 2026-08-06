<template>
  <article class="kitchen-menu-item">
    <!-- ==================================================
         Item Header
    =================================================== -->
    <header class="kitchen-menu-item-header">
      <div class="kitchen-menu-item-heading">
        <span class="kitchen-menu-item-icon">
          <i class="bi bi-cup-hot"></i>
        </span>

        <div>
          <h4>
            {{ item.item_name }}
          </h4>

          <div class="kitchen-menu-item-meta">
            <span>
              <i class="bi bi-box"></i>

              Quantity:
              <strong>{{ item.quantity }}</strong>
            </span>

            <span v-if="item.variant_name">
              <i class="bi bi-tags"></i>

              Variant:
              <strong>
                {{ item.variant_name }}
              </strong>
            </span>

            <span
              v-if="item.preparation_time"
            >
              <i class="bi bi-stopwatch"></i>

              Prep:
              <strong>
                {{ item.preparation_time }}
                min
              </strong>
            </span>
          </div>
        </div>
      </div>

      <span
        class="kitchen-menu-item-status"
        :class="itemStatusClass"
      >
        <i :class="itemStatusIcon"></i>

        {{ itemStatusLabel }}
      </span>
    </header>

    <!-- ==================================================
         Item Content
    =================================================== -->
    <div class="kitchen-menu-item-content">
      <!-- Ingredients -->
      <section class="kitchen-item-section">
        <div class="kitchen-item-section-title">
          <span>
            <i class="bi bi-basket3"></i>
          </span>

          <div>
            <h5>Ingredients</h5>

            <p>
              Required ingredients for this
              menu item
            </p>
          </div>
        </div>

        <div
          v-if="ingredientList.length"
          class="kitchen-ingredient-list"
        >
          <span
            v-for="ingredient in ingredientList"
            :key="ingredient"
            class="kitchen-ingredient-chip"
          >
            <i class="bi bi-check2"></i>

            {{ ingredient }}
          </span>
        </div>

        <div
          v-else
          class="kitchen-item-empty"
        >
          <i class="bi bi-info-circle"></i>

          <span>
            No ingredients listed
          </span>
        </div>
      </section>

      <!-- Add-ons -->
      <section class="kitchen-item-section">
        <div class="kitchen-item-section-title">
          <span>
            <i class="bi bi-plus-circle"></i>
          </span>

          <div>
            <h5>Add-ons</h5>

            <p>
              Additional selected items
            </p>
          </div>
        </div>

        <div
          v-if="item.addons?.length"
          class="kitchen-addon-list"
        >
          <span
            v-for="addon in item.addons"
            :key="addon.id"
            class="kitchen-addon-chip"
          >
            {{ addon.addon_name }}

            <small>
              × {{ addon.quantity }}
            </small>
          </span>
        </div>

        <div
          v-else
          class="kitchen-item-empty"
        >
          <i class="bi bi-dash-circle"></i>

          <span>No add-ons selected</span>
        </div>
      </section>
    </div>

    <!-- ==================================================
         Kitchen Note
    =================================================== -->
    <section
      v-if="item.kitchen_note"
      class="kitchen-item-note"
    >
      <span>
        <i class="bi bi-chat-left-text"></i>
      </span>

      <div>
        <small>Kitchen Note</small>

        <p>
          {{ item.kitchen_note }}
        </p>
      </div>
    </section>
  </article>
</template>

<script setup>
import {
  computed,
} from 'vue'

const props = defineProps({
  item: {
    type: Object,
    required: true,
  },
})

/*
|--------------------------------------------------------------------------
| Ingredient List
|--------------------------------------------------------------------------
|
| ingredients একটি plain text column।
| Comma, newline বা semicolon দিয়ে লেখা থাকলে আলাদা chip হিসেবে দেখাবে।
|
*/

const ingredientList = computed(() => {
  const ingredients =
    props.item?.ingredients

  if (
    !ingredients ||
    typeof ingredients !==
      'string'
  ) {
    return []
  }

  return ingredients
    .split(/[,;\n]+/)
    .map(
      (ingredient) =>
        ingredient.trim(),
    )
    .filter(Boolean)
})

/*
|--------------------------------------------------------------------------
| Item Status Label
|--------------------------------------------------------------------------
*/

const itemStatusLabel =
  computed(() => {
    switch (
      props.item?.status
    ) {
      case 'pending':
        return 'Pending'

      case 'preparing':
        return 'Preparing'

      case 'ready':
        return 'Ready'

      default:
        return formatLabel(
          props.item?.status,
        ) || 'Pending'
    }
  })

/*
|--------------------------------------------------------------------------
| Item Status Class
|--------------------------------------------------------------------------
*/

const itemStatusClass =
  computed(() => {
    switch (
      props.item?.status
    ) {
      case 'preparing':
        return 'kitchen-item-status-preparing'

      case 'ready':
        return 'kitchen-item-status-ready'

      case 'pending':
      default:
        return 'kitchen-item-status-pending'
    }
  })

/*
|--------------------------------------------------------------------------
| Item Status Icon
|--------------------------------------------------------------------------
*/

const itemStatusIcon =
  computed(() => {
    switch (
      props.item?.status
    ) {
      case 'preparing':
        return 'bi bi-fire'

      case 'ready':
        return 'bi bi-check2-circle'

      case 'pending':
      default:
        return 'bi bi-hourglass-split'
    }
  })

/*
|--------------------------------------------------------------------------
| Format Label
|--------------------------------------------------------------------------
*/

function formatLabel(value) {
  if (!value) {
    return ''
  }

  return String(value)
    .replaceAll('_', ' ')
    .replace(
      /\b\w/g,
      (letter) =>
        letter.toUpperCase(),
    )
}
</script>