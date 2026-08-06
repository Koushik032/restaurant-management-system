<template>
  <div class="order-toolbar">
    <div class="order-toolbar-top">
      <div class="order-search-wrapper">
        <i class="bi bi-search"></i>

        <input
          :value="search"
          type="search"
          class="form-control order-search-input"
          placeholder="Search order, customer, phone or table..."
          @input="
            emit(
              'update:search',
              $event.target.value
            )
          "
          @keyup.enter="emit('search')"
        />
      </div>

      <div class="order-toolbar-actions">
        <button
          type="button"
          class="btn toolbar-button"
          :disabled="loading"
          @click="emit('refresh')"
        >
          <i
            class="bi bi-arrow-clockwise"
            :class="{
              'spin-icon': loading,
            }"
          ></i>

          Refresh
        </button>

        <div class="dropdown">
          <button
            type="button"
            class="btn toolbar-button dropdown-toggle"
            data-bs-toggle="dropdown"
            aria-expanded="false"
          >
            <i class="bi bi-printer"></i>
            Export / Print
          </button>

          <ul
            class="dropdown-menu dropdown-menu-end order-export-menu"
          >
            <li>
              <button
                type="button"
                class="dropdown-item"
                @click="emit('export-excel')"
              >
                <i
                  class="bi bi-file-earmark-excel"
                ></i>

                Export Excel
              </button>
            </li>

            <li>
              <button
                type="button"
                class="dropdown-item"
                @click="emit('export-pdf')"
              >
                <i
                  class="bi bi-file-earmark-pdf"
                ></i>

                Export PDF
              </button>
            </li>

            <li>
              <hr class="dropdown-divider" />
            </li>

            <li>
              <button
                type="button"
                class="dropdown-item"
                @click="emit('print')"
              >
                <i class="bi bi-printer"></i>
                Print Orders
              </button>
            </li>
          </ul>
        </div>
      </div>
    </div>

    <div class="order-filter-grid">
      <div class="filter-field">
        <label class="filter-label">
          Order Status
        </label>

        <select
          :value="status"
          class="form-select"
          @change="
            emit(
              'update:status',
              $event.target.value
            )
          "
        >
          <option value="">
            All Statuses
          </option>

          <option
            v-for="option in statusOptions"
            :key="option.value"
            :value="option.value"
          >
            {{ option.label }}
          </option>
        </select>
      </div>

      <div class="filter-field">
        <label class="filter-label">
          Payment Status
        </label>

        <select
          :value="paymentStatus"
          class="form-select"
          @change="
            emit(
              'update:paymentStatus',
              $event.target.value
            )
          "
        >
          <option value="">
            All Payments
          </option>

          <option
            v-for="option in paymentStatusOptions"
            :key="option.value"
            :value="option.value"
          >
            {{ option.label }}
          </option>
        </select>
      </div>

      <div class="filter-field">
        <label class="filter-label">
          Payment Method
        </label>

        <select
          :value="paymentMethod"
          class="form-select"
          @change="
            emit(
              'update:paymentMethod',
              $event.target.value
            )
          "
        >
          <option value="">
            All Methods
          </option>

          <option
            v-for="option in paymentMethodOptions"
            :key="option.value"
            :value="option.value"
          >
            {{ option.label }}
          </option>
        </select>
      </div>

      <div class="filter-field">
        <label class="filter-label">
          Date From
        </label>

        <input
          :value="dateFrom"
          type="date"
          class="form-control"
          @input="
            emit(
              'update:dateFrom',
              $event.target.value
            )
          "
        />
      </div>

      <div class="filter-field">
        <label class="filter-label">
          Date To
        </label>

        <input
          :value="dateTo"
          type="date"
          class="form-control"
          :min="dateFrom || undefined"
          @input="
            emit(
              'update:dateTo',
              $event.target.value
            )
          "
        />
      </div>

      <div class="filter-action-field">
        <button
          type="button"
          class="btn apply-filter-button"
          :disabled="loading"
          @click="emit('search')"
        >
          <i class="bi bi-funnel"></i>
          Apply
        </button>

        <button
          type="button"
          class="btn clear-filter-button"
          :disabled="loading"
          @click="emit('clear')"
        >
          Clear
        </button>
      </div>
    </div>
  </div>
</template>

<script setup>
defineProps({
  search: {
    type: String,
    default: "",
  },

  status: {
    type: String,
    default: "",
  },

  paymentStatus: {
    type: String,
    default: "",
  },

  paymentMethod: {
    type: String,
    default: "",
  },

  dateFrom: {
    type: String,
    default: "",
  },

  dateTo: {
    type: String,
    default: "",
  },

  statusOptions: {
    type: Array,
    default: () => [],
  },

  paymentStatusOptions: {
    type: Array,
    default: () => [],
  },

  paymentMethodOptions: {
    type: Array,
    default: () => [],
  },

  loading: {
    type: Boolean,
    default: false,
  },
});

const emit = defineEmits([
  "update:search",
  "update:status",
  "update:paymentStatus",
  "update:paymentMethod",
  "update:dateFrom",
  "update:dateTo",
  "search",
  "refresh",
  "clear",
  "print",
  "export-excel",
  "export-pdf",
]);
</script>