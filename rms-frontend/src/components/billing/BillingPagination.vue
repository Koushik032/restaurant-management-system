<template>
  <nav
    v-if="shouldShowPagination"
    class="billing-pagination"
    aria-label="Billing pagination"
  >
    <!-- ==================================================
         Pagination Information
    =================================================== -->

    <div class="billing-pagination-info">
      Showing

      <strong>
        {{ resolvedMeta.from }}
      </strong>

      to

      <strong>
        {{ resolvedMeta.to }}
      </strong>

      of

      <strong>
        {{ resolvedMeta.total }}
      </strong>

      records
    </div>

    <!-- ==================================================
         Pagination Controls
    =================================================== -->

    <div class="billing-pagination-buttons">
      <!-- First Page -->

      <button
        type="button"
        class="billing-pagination-button"
        aria-label="Go to first page"
        title="First page"
        :disabled="isFirstPage"
        @click="goToPage(1)"
      >
        <i
          class="bi bi-chevron-double-left"
          aria-hidden="true"
        ></i>
      </button>

      <!-- Previous Page -->

      <button
        type="button"
        class="billing-pagination-button"
        aria-label="Go to previous page"
        title="Previous page"
        :disabled="isFirstPage"
        @click="
          goToPage(
            resolvedMeta.current_page - 1
          )
        "
      >
        <i
          class="bi bi-chevron-left"
          aria-hidden="true"
        ></i>
      </button>

      <!-- Leading Ellipsis -->

      <span
        v-if="showLeadingEllipsis"
        class="billing-pagination-ellipsis"
        aria-hidden="true"
      >
        ...
      </span>

      <!-- Page Numbers -->

      <button
        v-for="page in visiblePages"
        :key="page"
        type="button"
        class="billing-pagination-button"
        :class="{
          active:
            page ===
            resolvedMeta.current_page,
        }"
        :aria-label="`Go to page ${page}`"
        :aria-current="
          page ===
          resolvedMeta.current_page
            ? 'page'
            : undefined
        "
        :disabled="
          page ===
          resolvedMeta.current_page
        "
        @click="goToPage(page)"
      >
        {{ page }}
      </button>

      <!-- Trailing Ellipsis -->

      <span
        v-if="showTrailingEllipsis"
        class="billing-pagination-ellipsis"
        aria-hidden="true"
      >
        ...
      </span>

      <!-- Next Page -->

      <button
        type="button"
        class="billing-pagination-button"
        aria-label="Go to next page"
        title="Next page"
        :disabled="isLastPage"
        @click="
          goToPage(
            resolvedMeta.current_page + 1
          )
        "
      >
        <i
          class="bi bi-chevron-right"
          aria-hidden="true"
        ></i>
      </button>

      <!-- Last Page -->

      <button
        type="button"
        class="billing-pagination-button"
        aria-label="Go to last page"
        title="Last page"
        :disabled="isLastPage"
        @click="
          goToPage(
            resolvedMeta.last_page
          )
        "
      >
        <i
          class="bi bi-chevron-double-right"
          aria-hidden="true"
        ></i>
      </button>
    </div>
  </nav>
</template>

<script setup>
import {
  computed,
} from "vue";

/*
|--------------------------------------------------------------------------
| Component Props
|--------------------------------------------------------------------------
*/

const props = defineProps({
  /*
  |--------------------------------------------------------------------------
  | Pagination Metadata
  |--------------------------------------------------------------------------
  */

  meta: {
    type: Object,
    default: () => ({
      current_page: 1,
      last_page: 1,
      per_page: 5,
      total: 0,
      from: null,
      to: null,
    }),
  },

  /*
  |--------------------------------------------------------------------------
  | Maximum Visible Page Buttons
  |--------------------------------------------------------------------------
  */

  maxVisiblePages: {
    type: Number,
    default: 5,
  },
});

/*
|--------------------------------------------------------------------------
| Component Events
|--------------------------------------------------------------------------
*/

const emit = defineEmits([
  "change",
]);

/*
|--------------------------------------------------------------------------
| Resolved Pagination Metadata
|--------------------------------------------------------------------------
*/

const resolvedMeta = computed(() => {
  const total =
    toNonNegativeInteger(
      props.meta?.total,
      0
    );

  const lastPage =
    toPositiveInteger(
      props.meta?.last_page,
      1
    );

  const currentPage =
    Math.min(
      toPositiveInteger(
        props.meta?.current_page,
        1
      ),
      lastPage
    );

  const perPage =
    toPositiveInteger(
      props.meta?.per_page,
      5
    );

  const fallbackFrom =
    total > 0
      ? (
          (currentPage - 1) *
          perPage
        ) + 1
      : 0;

  const fallbackTo =
    total > 0
      ? Math.min(
          currentPage *
            perPage,
          total
        )
      : 0;

  return {
    current_page:
      currentPage,

    last_page:
      lastPage,

    per_page:
      perPage,

    total,

    from:
      toNonNegativeInteger(
        props.meta?.from,
        fallbackFrom
      ),

    to:
      toNonNegativeInteger(
        props.meta?.to,
        fallbackTo
      ),
  };
});

/*
|--------------------------------------------------------------------------
| Pagination Visibility
|--------------------------------------------------------------------------
*/

const shouldShowPagination =
  computed(() => {
    return (
      resolvedMeta.value.total > 0 &&
      resolvedMeta.value.last_page > 1
    );
  });

/*
|--------------------------------------------------------------------------
| First and Last Page State
|--------------------------------------------------------------------------
*/

const isFirstPage =
  computed(() => {
    return (
      resolvedMeta.value.current_page <= 1
    );
  });

const isLastPage =
  computed(() => {
    return (
      resolvedMeta.value.current_page >=
      resolvedMeta.value.last_page
    );
  });

/*
|--------------------------------------------------------------------------
| Visible Page Numbers
|--------------------------------------------------------------------------
*/

const visiblePages =
  computed(() => {
    const currentPage =
      resolvedMeta.value.current_page;

    const lastPage =
      resolvedMeta.value.last_page;

    const maxVisible =
      resolveMaxVisiblePages(
        props.maxVisiblePages
      );

    if (
      lastPage <=
      maxVisible
    ) {
      return createPageRange(
        1,
        lastPage
      );
    }

    const halfVisible =
      Math.floor(
        maxVisible / 2
      );

    let startPage =
      currentPage -
      halfVisible;

    let endPage =
      startPage +
      maxVisible -
      1;

    if (startPage < 1) {
      startPage = 1;

      endPage =
        maxVisible;
    }

    if (
      endPage >
      lastPage
    ) {
      endPage =
        lastPage;

      startPage =
        Math.max(
          1,
          lastPage -
            maxVisible +
            1
        );
    }

    return createPageRange(
      startPage,
      endPage
    );
  });

/*
|--------------------------------------------------------------------------
| Ellipsis Visibility
|--------------------------------------------------------------------------
*/

const showLeadingEllipsis =
  computed(() => {
    return (
      visiblePages.value.length > 0 &&
      visiblePages.value[0] > 1
    );
  });

const showTrailingEllipsis =
  computed(() => {
    const lastVisiblePage =
      visiblePages.value[
        visiblePages.value.length - 1
      ];

    return (
      visiblePages.value.length > 0 &&
      lastVisiblePage <
        resolvedMeta.value.last_page
    );
  });

/*
|--------------------------------------------------------------------------
| Go To Page
|--------------------------------------------------------------------------
*/

function goToPage(
  page
) {
  const resolvedPage =
    Number(page);

  if (
    !Number.isInteger(
      resolvedPage
    ) ||
    resolvedPage < 1 ||
    resolvedPage >
      resolvedMeta.value.last_page ||
    resolvedPage ===
      resolvedMeta.value.current_page
  ) {
    return;
  }

  emit(
    "change",
    resolvedPage
  );
}

/*
|--------------------------------------------------------------------------
| Resolve Maximum Visible Pages
|--------------------------------------------------------------------------
*/

function resolveMaxVisiblePages(
  value
) {
  const resolvedValue =
    Number(value);

  if (
    !Number.isInteger(
      resolvedValue
    ) ||
    resolvedValue <= 0
  ) {
    return 5;
  }

  return Math.min(
    resolvedValue,
    9
  );
}

/*
|--------------------------------------------------------------------------
| Create Page Range
|--------------------------------------------------------------------------
*/

function createPageRange(
  startPage,
  endPage
) {
  return Array.from(
    {
      length:
        Math.max(
          0,
          endPage -
            startPage +
            1
        ),
    },

    (
      _,
      index
    ) =>
      startPage +
      index
  );
}

/*
|--------------------------------------------------------------------------
| Positive Integer Helper
|--------------------------------------------------------------------------
*/

function toPositiveInteger(
  value,
  fallback
) {
  const numberValue =
    Number(value);

  return (
    Number.isInteger(
      numberValue
    ) &&
    numberValue > 0
  )
    ? numberValue
    : fallback;
}

/*
|--------------------------------------------------------------------------
| Non-negative Integer Helper
|--------------------------------------------------------------------------
*/

function toNonNegativeInteger(
  value,
  fallback
) {
  const numberValue =
    Number(value);

  return (
    Number.isInteger(
      numberValue
    ) &&
    numberValue >= 0
  )
    ? numberValue
    : fallback;
}
</script>