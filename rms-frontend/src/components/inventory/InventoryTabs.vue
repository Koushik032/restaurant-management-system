<template>
  <div
    class="inventory-tabs-wrapper"
    role="tablist"
    aria-label="Inventory sections"
    @keydown="handleKeydown"
  >
    <button
      v-for="(tab, index) in tabs"
      :key="tab.key"
      :ref="
        (element) =>
          setTabButtonRef(
            element,
            index,
          )
      "
      type="button"
      role="tab"
      class="inventory-tab-button"
      :class="{
        'inventory-tab-active':
          modelValue === tab.key,
      }"
      :aria-selected="
        modelValue === tab.key
      "
      :tabindex="
        modelValue === tab.key
          ? 0
          : -1
      "
      @click="
        selectTab(
          tab.key,
        )
      "
    >
      <i
        :class="tab.icon"
        aria-hidden="true"
      ></i>


      <span>
        {{ tab.label }}
      </span>


      <span
        v-if="hasBadge(tab)"
        class="inventory-tab-badge"
        :class="{
          'inventory-tab-badge-alert':
            tab.badgeType ===
            'alert',
        }"
        aria-hidden="true"
      >
        {{ tab.badge }}
      </span>


      <span
        v-if="
          hasBadge(tab)
          &&
          tab.badgeType ===
          'alert'
        "
        class="visually-hidden"
      >
        {{
          `${tab.badge} alert${
            Number(tab.badge) === 1
              ? ''
              : 's'
          }`
        }}
      </span>
    </button>
  </div>
</template>


<script setup>
import {
  nextTick,
  ref,
} from 'vue'


/*
|--------------------------------------------------------------------------
| Props
|--------------------------------------------------------------------------
*/


const props = defineProps({
  modelValue: {
    type: String,
    required: true,
  },


  tabs: {
    type: Array,
    default: () => [],
  },
})


/*
|--------------------------------------------------------------------------
| Emits
|--------------------------------------------------------------------------
*/


const emit = defineEmits([
  'update:modelValue',
])


/*
|--------------------------------------------------------------------------
| Tab Button References
|--------------------------------------------------------------------------
*/


const tabButtonRefs =
  ref([])


function setTabButtonRef(
  element,
  index,
) {
  if (element) {
    tabButtonRefs.value[index] =
      element
  }
}


/*
|--------------------------------------------------------------------------
| Badge
|--------------------------------------------------------------------------
*/


function hasBadge(
  tab,
) {
  return (
    tab?.badge !== null
    &&
    tab?.badge !== undefined
    &&
    tab?.badge !== ''
    &&
    Number(tab.badge) > 0
  )
}


/*
|--------------------------------------------------------------------------
| Select Tab
|--------------------------------------------------------------------------
*/


function selectTab(
  tabKey,
) {
  if (
    !tabKey
    ||
    tabKey === props.modelValue
  ) {
    return
  }


  emit(
    'update:modelValue',
    tabKey,
  )
}


/*
|--------------------------------------------------------------------------
| Keyboard Navigation
|--------------------------------------------------------------------------
|
| Standard tablist behavior:
|
| ArrowRight / ArrowDown -> next tab
| ArrowLeft / ArrowUp    -> previous tab
| Home                   -> first tab
| End                    -> last tab
|
*/


async function focusAndSelectTab(
  index,
) {
  const tab =
    props.tabs[index]


  if (!tab?.key) {
    return
  }


  selectTab(
    tab.key,
  )


  await nextTick()


  tabButtonRefs
    .value[index]
    ?.focus()
}


function handleKeydown(
  event,
) {
  if (
    !Array.isArray(
      props.tabs,
    )
    ||
    props.tabs.length === 0
  ) {
    return
  }


  const currentIndex =
    props.tabs.findIndex(
      (tab) =>
        tab?.key ===
        props.modelValue,
    )


  const safeCurrentIndex =
    currentIndex >= 0
      ? currentIndex
      : 0


  let targetIndex =
    null


  switch (event.key) {
    case 'ArrowRight':
    case 'ArrowDown':
      targetIndex =
        (
          safeCurrentIndex + 1
        )
        %
        props.tabs.length
      break


    case 'ArrowLeft':
    case 'ArrowUp':
      targetIndex =
        (
          safeCurrentIndex
          -
          1
          +
          props.tabs.length
        )
        %
        props.tabs.length
      break


    case 'Home':
      targetIndex = 0
      break


    case 'End':
      targetIndex =
        props.tabs.length - 1
      break


    default:
      return
  }


  event.preventDefault()


  void focusAndSelectTab(
    targetIndex,
  )
}
</script>