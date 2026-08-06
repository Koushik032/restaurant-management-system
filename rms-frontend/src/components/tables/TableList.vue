<script setup>
import {
  computed,
  ref,
} from "vue";

const props = defineProps({
  tables: {
    type: Array,
    default: () => [],
  },

  loading: {
    type: Boolean,
    default: false,
  },
});

const emit = defineEmits([
  "edit",
  "delete",
  "merge",
  "split",
  "refresh",
]);

const selectedStatus = ref("all");
const selectedSection = ref("all");
const searchQuery = ref("");

const statusTabs = [
  {
    value: "all",
    label: "All Status",
    icon: "bi-grid",
  },
  {
    value: "available",
    label: "Available",
    icon: "bi-check-circle",
  },
  {
    value: "occupied",
    label: "Occupied",
    icon: "bi-people",
  },
  {
    value: "reserved",
    label: "Reserved",
    icon: "bi-calendar-event",
  },
  {
    value: "cleaning",
    label: "Cleaning",
    icon: "bi-stars",
  },
];

const sections = [
  {
    value: "all",
    label: "All Sections",
  },
  {
    value: "ac",
    label: "AC",
  },
  {
    value: "non_ac",
    label: "Non-AC",
  },
  {
    value: "outdoor",
    label: "Outdoor",
  },
];

/*
|--------------------------------------------------------------------------
| Date helpers
|--------------------------------------------------------------------------
*/

const parseDate = (value) => {
  if (!value) {
    return null;
  }

  const date = new Date(value);

  return Number.isNaN(
    date.getTime()
  )
    ? null
    : date;
};

const isReservationActive = (
  table
) => {
  if (
    table.has_active_reservation !==
    undefined
  ) {
    return Boolean(
      table.has_active_reservation
    );
  }

  const start = parseDate(
    table.reservation_start_at
  );

  const end = parseDate(
    table.reservation_end_at
  );

  if (!start || !end) {
    return false;
  }

  const now = new Date();

  return (
    now >= start &&
    now < end
  );
};

const isReservationUpcoming = (
  table
) => {
  if (
    table.has_upcoming_reservation !==
    undefined
  ) {
    return Boolean(
      table.has_upcoming_reservation
    );
  }

  const start = parseDate(
    table.reservation_start_at
  );

  const end = parseDate(
    table.reservation_end_at
  );

  if (!start || !end) {
    return false;
  }

  const now = new Date();

  return (
    start > now &&
    end > now
  );
};

/*
|--------------------------------------------------------------------------
| Status helpers
|--------------------------------------------------------------------------
*/

const getCurrentStatus = (
  table
) => {
  if (table.current_status) {
    return table.current_status;
  }

  if (
    table.status === "occupied"
  ) {
    return "occupied";
  }

  if (
    table.status === "cleaning"
  ) {
    return "cleaning";
  }

  if (
    isReservationActive(table)
  ) {
    return "reserved";
  }

  return "available";
};

const shouldShowInReservedTab = (
  table
) => {
  return (
    isReservationActive(table) ||
    isReservationUpcoming(table)
  );
};

const getStatusLabel = (
  table
) => {
  const status =
    getCurrentStatus(table);

  const labels = {
    available: "Available",
    occupied: "Occupied",
    reserved: "Reserved",
    cleaning: "Cleaning",
  };

  return labels[status] ?? status;
};

const getStatusIcon = (
  table
) => {
  const status =
    getCurrentStatus(table);

  const icons = {
    available:
      "bi-check-circle-fill",

    occupied:
      "bi-people-fill",

    reserved:
      "bi-calendar-event-fill",

    cleaning:
      "bi-stars",
  };

  return (
    icons[status] ??
    "bi-circle-fill"
  );
};

/*
|--------------------------------------------------------------------------
| Filters
|--------------------------------------------------------------------------
*/

const filteredTables = computed(
  () => {
    const query =
      searchQuery.value
        .trim()
        .toLowerCase();

    return props.tables.filter(
      (table) => {
        const currentStatus =
          getCurrentStatus(table);

        const matchesSearch =
          !query ||
          String(
            table.table_name ?? ""
          )
            .toLowerCase()
            .includes(query) ||
          String(
            table.id ?? ""
          )
            .toLowerCase()
            .includes(query);

        const matchesSection =
          selectedSection.value ===
            "all" ||
          table.section ===
            selectedSection.value;

        let matchesStatus = true;

        if (
          selectedStatus.value ===
          "reserved"
        ) {
          matchesStatus =
            shouldShowInReservedTab(
              table
            );
        } else if (
          selectedStatus.value !==
          "all"
        ) {
          matchesStatus =
            currentStatus ===
            selectedStatus.value;
        }

        return (
          matchesSearch &&
          matchesSection &&
          matchesStatus
        );
      }
    );
  }
);

const getStatusCount = (
  status
) => {
  if (status === "all") {
    return props.tables.length;
  }

  if (status === "reserved") {
    return props.tables.filter(
      shouldShowInReservedTab
    ).length;
  }

  return props.tables.filter(
    (table) =>
      getCurrentStatus(table) ===
      status
  ).length;
};

/*
|--------------------------------------------------------------------------
| Display helpers
|--------------------------------------------------------------------------
*/

const getSectionLabel = (
  section
) => {
  const labels = {
    ac: "AC",
    non_ac: "Non-AC",
    outdoor: "Outdoor",
  };

  return labels[section] ?? section;
};

const getReservationType = (
  table
) => {
  if (
    isReservationActive(table)
  ) {
    return "active";
  }

  if (
    isReservationUpcoming(table)
  ) {
    return "upcoming";
  }

  return null;
};

const formatReservationDate = (
  table
) => {
  if (
    table.reservation_display
  ) {
    return table.reservation_display;
  }

  const start = parseDate(
    table.reservation_start_at
  );

  const end = parseDate(
    table.reservation_end_at
  );

  if (!start || !end) {
    return "";
  }

  const date =
    start.toLocaleDateString(
      "en-GB",
      {
        day: "2-digit",
        month: "short",
        year: "numeric",
      }
    );

  const startTime =
    start.toLocaleTimeString(
      "en-US",
      {
        hour: "2-digit",
        minute: "2-digit",
      }
    );

  const endTime =
    end.toLocaleTimeString(
      "en-US",
      {
        hour: "2-digit",
        minute: "2-digit",
      }
    );

  return (
    `${date}, ` +
    `${startTime} - ${endTime}`
  );
};

/*
|--------------------------------------------------------------------------
| Merge helpers
|--------------------------------------------------------------------------
*/

const isMergedTable = (
  table
) => {
  return Boolean(
    table.is_merged ||
    table.is_merge_master ||
    table.is_merge_child ||
    table.merged_with_id ||
    table.merge_master_id ||
    table.merge_group_ids
      ?.length > 1 ||
    table.merge_group_tables
      ?.length > 1
  );
};

const getMergeGroupNames = (
  table
) => {
  const group =
    table.merge_group_tables;

  if (
    !Array.isArray(group) ||
    !group.length
  ) {
    return "";
  }

  return group
    .map(
      (item) =>
        item.table_name
    )
    .filter(Boolean)
    .join(", ");
};

const getDisplayedCapacity = (
  table
) => {
  return Number(
    table.merged_total_capacity ??
    table.capacity ??
    0
  );
};

const canMergeTable = (
  table
) => {
  const status =
    getCurrentStatus(table);

  return (
    status === "available" &&
    !isReservationActive(table)
  );
};

/*
|--------------------------------------------------------------------------
| Actions
|--------------------------------------------------------------------------
*/

const clearFilters = () => {
  selectedStatus.value = "all";
  selectedSection.value = "all";
  searchQuery.value = "";
};

const handleEdit = (
  table
) => {
  emit("edit", table);
};

const handleDelete = (
  table
) => {
  emit("delete", table);
};

const handleMerge = (
  table
) => {
  emit("merge", table);
};

const handleSplit = (
  table
) => {
  emit("split", table);
};
</script>

<template>
  <section class="table-list">
    <!-- Header -->
    <div class="table-list__header">
      <div>
        <p class="table-list__eyebrow">
          Restaurant floor
        </p>

        <h2 class="table-list__title">
          Table Management
        </h2>

        <p
          class="table-list__description"
        >
          View and manage tables,
          reservations and merged groups.
        </p>
      </div>

      <button
        type="button"
        class="table-list__refresh"
        :disabled="loading"
        @click="emit('refresh')"
      >
        <i
          class="bi bi-arrow-clockwise"
          :class="{
            'table-list__spin':
              loading,
          }"
        ></i>

        Refresh
      </button>
    </div>

    <!-- Status tabs -->
    <div class="status-tabs">
      <button
        v-for="tab in statusTabs"
        :key="tab.value"
        type="button"
        class="status-tab"
        :class="[
          `status-tab--${tab.value}`,
          {
            'status-tab--active':
              selectedStatus ===
              tab.value,
          },
        ]"
        @click="
          selectedStatus =
            tab.value
        "
      >
        <span
          class="status-tab__icon"
        >
          <i
            :class="[
              'bi',
              tab.icon,
            ]"
          ></i>
        </span>

        <span
          class="status-tab__label"
        >
          {{ tab.label }}
        </span>

        <span
          class="status-tab__count"
        >
          {{
            getStatusCount(
              tab.value
            )
          }}
        </span>
      </button>
    </div>

    <!-- Filters -->
    <div class="table-filters">
      <div
        class="table-filters__search"
      >
        <i class="bi bi-search"></i>

        <input
          v-model="searchQuery"
          type="search"
          placeholder="Search table name or ID"
        />
      </div>

      <select
        v-model="selectedSection"
        class="table-filters__select"
      >
        <option
          v-for="section in sections"
          :key="section.value"
          :value="section.value"
        >
          {{ section.label }}
        </option>
      </select>

      <button
        v-if="
          searchQuery ||
          selectedSection !==
            'all' ||
          selectedStatus !==
            'all'
        "
        type="button"
        class="table-filters__clear"
        @click="clearFilters"
      >
        <i
          class="bi bi-x-circle"
        ></i>

        Clear
      </button>

      <span
        class="table-filters__result"
      >
        {{
          filteredTables.length
        }}
        table{{
          filteredTables.length ===
          1
            ? ""
            : "s"
        }}
      </span>
    </div>

    <!-- Loading -->
    <div
      v-if="loading"
      class="table-list__state"
    >
      <div
        class="table-list__loader"
      ></div>

      <strong>
        Loading tables
      </strong>

      <span>
        Table information is being
        loaded.
      </span>
    </div>

    <!-- Empty -->
    <div
      v-else-if="
        !filteredTables.length
      "
      class="table-list__state"
    >
      <div
        class="table-list__empty-icon"
      >
        <i class="bi bi-table"></i>
      </div>

      <strong>
        No tables found
      </strong>

      <span>
        No table matches the current
        filters.
      </span>

      <button
        type="button"
        class="table-list__empty-button"
        @click="clearFilters"
      >
        Clear filters
      </button>
    </div>

    <!-- List -->
    <div
      v-else
      class="table-list-view"
    >
      <div
        class="table-list-view__head"
      >
        <span>Table</span>
        <span>Capacity</span>
        <span>Section</span>
        <span>Status</span>
        <span>Reservation</span>
        <span>Merge information</span>
        <span>Actions</span>
      </div>

      <article
        v-for="table in filteredTables"
        :key="table.id"
        class="table-list-row"
        :class="
          `table-list-row--${getCurrentStatus(
            table
          )}`
        "
      >
        <!-- Table -->
        <div
          class="table-list-row__table"
          data-label="Table"
        >
          <div
            class="table-list-row__icon"
          >
            <i class="bi bi-table"></i>
          </div>

          <div
            class="table-list-row__identity"
          >
            <strong>
              {{ table.table_name }}
            </strong>

            <span>
              Table #{{ table.id }}
            </span>

            <small
              v-if="table.notes"
            >
              <i
                class="bi bi-sticky"
              ></i>

              {{ table.notes }}
            </small>
          </div>
        </div>

        <!-- Capacity -->
        <div
          class="table-list-row__cell"
          data-label="Capacity"
        >
          <strong>
            {{
              getDisplayedCapacity(
                table
              )
            }}
          </strong>

          <span>guests</span>
        </div>

        <!-- Section -->
        <div
          class="table-list-row__cell"
          data-label="Section"
        >
          <span
            class="table-list-row__section"
          >
            {{
              getSectionLabel(
                table.section
              )
            }}
          </span>
        </div>

        <!-- Status -->
        <div
          class="table-list-row__cell"
          data-label="Status"
        >
          <span
            class="table-list-row__status"
            :class="
              `table-list-row__status--${getCurrentStatus(
                table
              )}`
            "
          >
            <i
              :class="[
                'bi',
                getStatusIcon(
                  table
                ),
              ]"
            ></i>

            {{
              getStatusLabel(
                table
              )
            }}
          </span>
        </div>

        <!-- Reservation -->
        <div
          class="table-list-row__reservation"
          data-label="Reservation"
        >
          <template
            v-if="
              getReservationType(
                table
              )
            "
          >
            <span
              class="table-list-row__reservation-type"
              :class="
                `table-list-row__reservation-type--${getReservationType(
                  table
                )}`
              "
            >
              {{
                getReservationType(
                  table
                ) === "active"
                  ? "Reserved now"
                  : "Upcoming"
              }}
            </span>

            <strong>
              {{
                formatReservationDate(
                  table
                )
              }}
            </strong>
          </template>

          <span
            v-else
            class="table-list-row__empty"
          >
            No reservation
          </span>
        </div>

        <!-- Merge -->
        <div
          class="table-list-row__merge"
          data-label="Merge"
        >
          <template
            v-if="
              isMergedTable(table)
            "
          >
            <span
              class="table-list-row__merge-badge"
            >
              <i
                class="bi bi-intersect"
              ></i>

              Merged
            </span>

            <strong
              v-if="
                getMergeGroupNames(
                  table
                )
              "
            >
              {{
                getMergeGroupNames(
                  table
                )
              }}
            </strong>

            <small>
              Total capacity:
              {{
                getDisplayedCapacity(
                  table
                )
              }}
            </small>
          </template>

          <span
            v-else
            class="table-list-row__empty"
          >
            Standalone
          </span>
        </div>

        <!-- Actions -->
        <div
          class="table-list-row__actions"
          data-label="Actions"
        >
          <button
            type="button"
            class="table-list-row__button table-list-row__button--edit"
            title="Edit table"
            @click="handleEdit(table)"
          >
            <i
              class="bi bi-pencil-square"
            ></i>

            <span>Edit</span>
          </button>

          <button
            type="button"
            class="table-list-row__button table-list-row__button--delete"
            title="Delete table"
            @click="
              handleDelete(table)
            "
          >
            <i
              class="bi bi-trash3"
            ></i>

            <span>Delete</span>
          </button>

          <button
            v-if="
              canMergeTable(table)
            "
            type="button"
            class="table-list-row__button table-list-row__button--merge"
            title="Merge table"
            @click="
              handleMerge(table)
            "
          >
            <i
              class="bi bi-intersect"
            ></i>

            <span>Merge</span>
          </button>

          <button
            v-if="
              isMergedTable(table)
            "
            type="button"
            class="table-list-row__button table-list-row__button--split"
            title="Split merged group"
            @click="
              handleSplit(table)
            "
          >
            <i
              class="bi bi-distribute-horizontal"
            ></i>

            <span>Split</span>
          </button>
        </div>
      </article>
    </div>
  </section>
</template>

<style
  scoped
  src="@/assets/css/table-list.css"
></style>