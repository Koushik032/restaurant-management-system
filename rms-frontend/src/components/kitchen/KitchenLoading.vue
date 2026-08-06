<template>
  <section class="kitchen-loading">
    <!-- ==========================================
         Summary Loading
    =========================================== -->

    <div class="kitchen-loading-summary">
      <div
        v-for="index in 4"
        :key="`summary-${index}`"
        class="kitchen-loading-summary-card"
      >
        <div class="loading-circle shimmer"></div>

        <div class="loading-content">
          <div
            class="loading-line loading-short shimmer"
          ></div>

          <div
            class="loading-line loading-medium shimmer"
          ></div>

          <div
            class="loading-line loading-small shimmer"
          ></div>
        </div>
      </div>
    </div>

    <!-- ==========================================
         Order Cards Loading
    =========================================== -->

    <div class="kitchen-loading-orders">
      <article
        v-for="index in 4"
        :key="`order-${index}`"
        class="kitchen-loading-card"
      >
        <!-- Header -->

        <div class="loading-header">
          <div
            class="loading-avatar shimmer"
          ></div>

          <div class="loading-header-text">
            <div
              class="loading-line loading-medium shimmer"
            ></div>

            <div
              class="loading-line loading-short shimmer"
            ></div>
          </div>

          <div
            class="loading-badge shimmer"
          ></div>
        </div>

        <!-- Quick Info -->

        <div class="loading-grid">
          <div
            v-for="item in 4"
            :key="item"
            class="loading-grid-item"
          >
            <div
              class="loading-line loading-small shimmer"
            ></div>

            <div
              class="loading-line loading-medium shimmer"
            ></div>
          </div>
        </div>

        <!-- Menu Items -->

        <div class="loading-items">
          <div
            v-for="item in 2"
            :key="item"
            class="loading-item"
          >
            <div
              class="loading-line loading-medium shimmer"
            ></div>

            <div
              class="loading-line shimmer"
            ></div>

            <div
              class="loading-line loading-short shimmer"
            ></div>
          </div>
        </div>

        <!-- Footer -->

        <div class="loading-footer">
          <div
            class="loading-button shimmer"
          ></div>

          <div
            class="loading-button shimmer"
          ></div>
        </div>
      </article>
    </div>
  </section>
</template>

<script setup>
/*
|--------------------------------------------------------------------------
| Kitchen Skeleton Loader
|--------------------------------------------------------------------------
|
| No script required.
|
*/
</script>