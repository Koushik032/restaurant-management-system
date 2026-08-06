<script setup>
import {
  onMounted,
  ref,
} from "vue";

import TableList from "@/components/tables/TableList.vue";
import EditTableModal from "@/components/tables/EditTableModal.vue";
import DeleteTableModal from "@/components/tables/DeleteTableModal.vue";
import MergeTableModal from "@/components/tables/MergeTableModal.vue";
import SplitTableModal from "@/components/tables/SplitTableModal.vue";
import AddTableModal from "@/components/tables/AddTableModal.vue";

import {
  restaurantTableService,
} from "@/services/restaurantTableService";

/*
|--------------------------------------------------------------------------
| Table data
|--------------------------------------------------------------------------
*/

const tables = ref([]);
const loading = ref(false);

const errorMessage = ref("");
const operationMessage = ref("");
const operationType = ref("success");

const showAddModal = ref(false);

/*
|--------------------------------------------------------------------------
| Modal state
|--------------------------------------------------------------------------
*/

const showEditModal = ref(false);
const showDeleteModal = ref(false);
const showMergeModal = ref(false);
const showSplitModal = ref(false);

const selectedTable = ref(null);
const tableToDelete = ref(null);
const tableToMerge = ref(null);
const tableToSplit = ref(null);

/*
|--------------------------------------------------------------------------
| Message helpers
|--------------------------------------------------------------------------
*/

const clearMessages = () => {
  errorMessage.value = "";
  operationMessage.value = "";
};

const showOperationMessage = (
  message,
  type = "success"
) => {
  operationMessage.value = message;
  operationType.value = type;
};

const getResponseMessage = (
  response,
  fallback
) => {
  return (
    response?.message ||
    response?.data?.message ||
    fallback
  );
};

const openAddModal = () => {
  errorMessage.value = "";
  operationMessage.value = "";

  showAddModal.value = true;
};

const closeAddModal = () => {
  showAddModal.value = false;
};

const handleTableCreated = async (
  response
) => {
  operationMessage.value =
    response?.message ||
    response?.data?.message ||
    "Restaurant table created successfully.";

  operationType.value =
    "success";

  closeAddModal();

  await loadTables();
};
/*
|--------------------------------------------------------------------------
| Load tables
|--------------------------------------------------------------------------
*/

const loadTables = async () => {
  loading.value = true;
  errorMessage.value = "";

  try {
    const response =
      await restaurantTableService
        .getTables({
          page: 1,
          per_page: 100,
        });

    tables.value = Array.isArray(
      response?.data
    )
      ? response.data
      : [];
  } catch (error) {
    console.error(
      "Restaurant table loading failed:",
      error?.response?.data ||
        error
    );

    errorMessage.value =
      error?.response?.data
        ?.message ||
      "Unable to load restaurant tables.";
  } finally {
    loading.value = false;
  }
};

/*
|--------------------------------------------------------------------------
| Edit modal
|--------------------------------------------------------------------------
*/

const openEditModal = (
  table
) => {
  clearMessages();

  selectedTable.value = {
    ...table,
  };

  showEditModal.value = true;
};

const closeEditModal = () => {
  if (!showEditModal.value) {
    return;
  }

  showEditModal.value = false;
  selectedTable.value = null;
};

const handleTableUpdated = async (
  response
) => {
  showOperationMessage(
    getResponseMessage(
      response,
      "Restaurant table updated successfully."
    )
  );

  closeEditModal();

  await loadTables();
};

/*
|--------------------------------------------------------------------------
| Delete modal
|--------------------------------------------------------------------------
*/

const openDeleteModal = (
  table
) => {
  clearMessages();

  tableToDelete.value = {
    ...table,
  };

  showDeleteModal.value = true;
};

const closeDeleteModal = () => {
  if (!showDeleteModal.value) {
    return;
  }

  showDeleteModal.value = false;
  tableToDelete.value = null;
};

const handleTableDeleted = async (
  response
) => {
  showOperationMessage(
    getResponseMessage(
      response,
      "Restaurant table deleted successfully."
    )
  );

  closeDeleteModal();

  await loadTables();
};

/*
|--------------------------------------------------------------------------
| Merge modal
|--------------------------------------------------------------------------
*/

const openMergeModal = (
  table
) => {
  clearMessages();

  tableToMerge.value = {
    ...table,
  };

  showMergeModal.value = true;
};

const closeMergeModal = () => {
  if (!showMergeModal.value) {
    return;
  }

  showMergeModal.value = false;
  tableToMerge.value = null;
};

const handleTablesMerged = async (
  response
) => {
  showOperationMessage(
    getResponseMessage(
      response,
      "Tables merged successfully."
    )
  );

  closeMergeModal();

  await loadTables();
};

/*
|--------------------------------------------------------------------------
| Split modal
|--------------------------------------------------------------------------
*/

const isMergedTable = (
  table
) => {
  return Boolean(
    table?.is_merged ||
    table?.is_merge_master ||
    table?.is_merge_child ||
    table?.merged_with_id ||
    table?.merge_master_id ||
    table?.merge_group_ids
      ?.length > 1 ||
    table?.merge_group_tables
      ?.length > 1
  );
};

const openSplitModal = (
  table
) => {
  clearMessages();

  if (!isMergedTable(table)) {
    showOperationMessage(
      "Only merged tables can be split.",
      "warning"
    );

    return;
  }

  tableToSplit.value = {
    ...table,
  };

  showSplitModal.value = true;
};

const closeSplitModal = () => {
  if (!showSplitModal.value) {
    return;
  }

  showSplitModal.value = false;
  tableToSplit.value = null;
};

const handleTablesSplit = async (
  response
) => {
  showOperationMessage(
    getResponseMessage(
      response,
      "Merged tables split successfully."
    )
  );

  closeSplitModal();

  await loadTables();
};


/*
|--------------------------------------------------------------------------
| Lifecycle
|--------------------------------------------------------------------------
*/

onMounted(() => {
  loadTables();
});
</script>

<template>
  <div
    class="table-management-page"
  >
    <!-- Page header -->
    <header
      class="table-management-header"
    >
      <div
        class="table-management-header__content"
      >
        <p
          class="table-management-eyebrow"
        >
          Restaurant Operations
        </p>

        <h1>
          Table Management
        </h1>

        <p>
          Manage restaurant tables,
          capacity, sections,
          reservations and live
          availability.
        </p>
      </div>

      <button
        type="button"
        class="btn btn-primary table-management-add"
        @click="openAddModal"
      >
        <i
          class="bi bi-plus-lg"
        ></i>

        <span>
          Add Table
        </span>
      </button>
    </header>

    <!-- Error message -->
    <div
      v-if="errorMessage"
      class="alert alert-danger alert-dismissible fade show table-management-alert"
      role="alert"
    >
      <i
        class="bi bi-exclamation-circle-fill"
      ></i>

      <span>
        {{ errorMessage }}
      </span>

      <button
        type="button"
        class="btn-close"
        aria-label="Close"
        @click="
          errorMessage = ''
        "
      ></button>
    </div>

    <!-- Operation message -->
    <div
      v-if="operationMessage"
      class="alert alert-dismissible fade show table-management-alert"
      :class="
        `alert-${operationType}`
      "
      role="alert"
    >
      <i
        :class="[
          'bi',
          operationType ===
          'success'
            ? 'bi-check-circle-fill'
            : operationType ===
                'warning'
              ? 'bi-exclamation-triangle-fill'
              : 'bi-info-circle-fill',
        ]"
      ></i>

      <span>
        {{ operationMessage }}
      </span>

      <button
        type="button"
        class="btn-close"
        aria-label="Close"
        @click="
          operationMessage = ''
        "
      ></button>
    </div>

    <!-- Table content -->
    <section
      class="table-management-card"
    >
      <TableList
        :tables="tables"
        :loading="loading"
        @refresh="loadTables"
        @edit="openEditModal"
        @delete="openDeleteModal"
        @merge="openMergeModal"
        @split="openSplitModal"
      />
    </section>

    <!-- Edit modal -->
    <EditTableModal
      :show="showEditModal"
      :table="selectedTable"
      @close="closeEditModal"
      @updated="handleTableUpdated"
      @delete="
        openDeleteModal
      "
      @merge="
        openMergeModal
      "
      @split="
        openSplitModal
      "
    />
    <AddTableModal
      :show="showAddModal"
      @close="closeAddModal"
      @created="handleTableCreated"
    />

    <!-- Delete modal -->
    <DeleteTableModal
      :show="showDeleteModal"
      :table="tableToDelete"
      @close="closeDeleteModal"
      @deleted="handleTableDeleted"
    />

    <!-- Merge modal -->
    <MergeTableModal
      :show="showMergeModal"
      :table="tableToMerge"
      @close="closeMergeModal"
      @merged="handleTablesMerged"
    />

    <!-- Split modal -->
    <SplitTableModal
      :show="showSplitModal"
      :table="tableToSplit"
      @close="closeSplitModal"
      @split="handleTablesSplit"
    />
  </div>
</template>

<style
  src="@/assets/css/table-management.css"
></style>