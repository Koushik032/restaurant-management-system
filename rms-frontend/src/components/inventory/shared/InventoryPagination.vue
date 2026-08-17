<template>
  <div
    v-if="totalRecords > 0"
    class="inventory-pagination-wrapper"
  >
    <!-- Pagination Summary -->

    <div
      class="inventory-pagination-summary"
      aria-live="polite"
    >
      Showing

      <strong>
        {{ fromRecord }}
      </strong>

      to

      <strong>
        {{ toRecord }}
      </strong>

      of

      <strong>
        {{ totalRecords }}
      </strong>

      records
    </div>


    <!-- Pagination Controls -->

    <nav
      class="inventory-pagination"
      aria-label="Inventory pagination"
    >
      <!-- Previous -->

      <button
        type="button"
        class="inventory-pagination-button"
        :disabled="
          loading ||
          currentPage <= 1
        "
        aria-label="Previous page"
        @click="
          changePage(
            currentPage - 1,
          )
        "
      >
        <i
          class="bi bi-chevron-left"
          aria-hidden="true"
        ></i>
      </button>


      <!-- Page Numbers -->

      <button
        v-for="page in visiblePages"
        :key="page"
        type="button"
        class="inventory-pagination-button"
        :class="{
          'inventory-pagination-active':
            page === currentPage,
        }"
        :disabled="loading"
        :aria-current="
          page === currentPage
            ? 'page'
            : undefined
        "
        :aria-label="
          page === currentPage
            ? `Page ${page}, current page`
            : `Go to page ${page}`
        "
        @click="
          changePage(
            page,
          )
        "
      >
        {{ page }}
      </button>


      <!-- Next -->

      <button
        type="button"
        class="inventory-pagination-button"
        :disabled="
          loading ||
          currentPage >= lastPage
        "
        aria-label="Next page"
        @click="
          changePage(
            currentPage + 1,
          )
        "
      >
        <i
          class="bi bi-chevron-right"
          aria-hidden="true"
        ></i>
      </button>
    </nav>
  </div>
</template>


<script setup>
import {
  computed,
} from 'vue'


/*
|--------------------------------------------------------------------------
| Props
|--------------------------------------------------------------------------
*/


const props = defineProps({
  meta: {
    type: Object,

    default: () => ({
      current_page: 1,
      last_page: 1,
      per_page: 10,
      total: 0,
      from: null,
      to: null,
    }),
  },


  loading: {
    type: Boolean,
    default: false,
  },
})


/*
|--------------------------------------------------------------------------
| Emits
|--------------------------------------------------------------------------
*/


const emit = defineEmits([
  'page-change',
])


/*
|--------------------------------------------------------------------------
| Normalized Meta
|--------------------------------------------------------------------------
*/


const totalRecords = computed(() => {
  const value =
    Number(
      props.meta?.total,
    )


  if (
    !Number.isFinite(value)
    ||
    value <= 0
  ) {
    return 0
  }


  return Math.floor(
    value,
  )
})


const lastPage = computed(() => {
  const value =
    Number(
      props.meta?.last_page,
    )


  if (
    !Number.isFinite(value)
    ||
    value < 1
  ) {
    return 1
  }


  return Math.floor(
    value,
  )
})


const currentPage = computed(() => {
  const value =
    Number(
      props.meta?.current_page,
    )


  if (
    !Number.isFinite(value)
    ||
    value < 1
  ) {
    return 1
  }


  return Math.min(
    Math.floor(value),
    lastPage.value,
  )
})


const fromRecord = computed(() => {
  const value =
    Number(
      props.meta?.from,
    )


  if (
    !Number.isFinite(value)
    ||
    value < 1
  ) {
    return 0
  }


  return Math.floor(
    value,
  )
})


const toRecord = computed(() => {
  const value =
    Number(
      props.meta?.to,
    )


  if (
    !Number.isFinite(value)
    ||
    value < 1
  ) {
    return 0
  }


  return Math.min(
    Math.floor(value),
    totalRecords.value,
  )
})


/*
|--------------------------------------------------------------------------
| Visible Page Numbers
|--------------------------------------------------------------------------
*/


const visiblePages = computed(() => {
  const maximumButtons = 5


  let startPage =
    Math.max(
      1,
      currentPage.value - 2,
    )


  let endPage =
    Math.min(
      lastPage.value,
      startPage
        +
        maximumButtons
        -
        1,
    )


  if (
    endPage
      -
      startPage
      +
      1
    <
    maximumButtons
  ) {
    startPage =
      Math.max(
        1,
        endPage
          -
          maximumButtons
          +
          1,
      )
  }


  const pages = []


  for (
    let page = startPage;
    page <= endPage;
    page += 1
  ) {
    pages.push(
      page,
    )
  }


  return pages
})


/*
|--------------------------------------------------------------------------
| Change Page
|--------------------------------------------------------------------------
*/


function changePage(
  page,
) {
  const targetPage =
    Number(
      page,
    )


  if (
    props.loading
    ||
    !Number.isFinite(
      targetPage,
    )
  ) {
    return
  }


  const normalizedPage =
    Math.floor(
      targetPage,
    )


  if (
    normalizedPage < 1
    ||
    normalizedPage
      >
      lastPage.value
    ||
    normalizedPage
      ===
      currentPage.value
  ) {
    return
  }


  emit(
    'page-change',
    normalizedPage,
  )
}
</script>