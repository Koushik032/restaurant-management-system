<template>

  <Transition name="backdrop">

    <div
      v-if="mobileOpen"
      class="sidebar-backdrop d-lg-none"
      @click="emit('close')"
    ></div>

  </Transition>


  <aside
    class="app-sidebar"
    :class="{
      'sidebar-mobile-open': mobileOpen,
      'sidebar-collapsed': isCompact,
    }"
  >

    <!-- Header -->

    <div class="sidebar-header">

      <RouterLink
        :to="{
          name: 'dashboard',
        }"
        class="sidebar-brand"
        :title="
          isCompact
            ? 'Restaurant Management System'
            : undefined
        "
        @click="handleMenuClick"
      >

        <div class="brand-logo">

          <i class="bi bi-shop"></i>

        </div>


        <Transition name="sidebar-text">

          <div
            v-if="!isCompact"
            class="brand-details"
          >

            <h1 class="brand-title">
              RMS
            </h1>

            <span class="brand-subtitle">
              Restaurant System
            </span>

          </div>

        </Transition>

      </RouterLink>


      <!-- Desktop Collapse -->

      <button
        type="button"
        class="desktop-collapse-button d-none d-lg-flex"
        :title="
          isCompact
            ? 'Expand sidebar'
            : 'Collapse sidebar'
        "
        :aria-label="
          isCompact
            ? 'Expand sidebar'
            : 'Collapse sidebar'
        "
        @click="
          emit('toggle-collapse')
        "
      >

        <i
          class="bi"
          :class="
            isCompact
              ? 'bi-chevron-right'
              : 'bi-chevron-left'
          "
        ></i>

      </button>


      <!-- Mobile Close -->

      <button
        type="button"
        class="mobile-close-button d-lg-none"
        aria-label="Close sidebar"
        @click="emit('close')"
      >

        <i class="bi bi-x-lg"></i>

      </button>

    </div>


    <!-- Navigation -->

    <div class="sidebar-scroll">

      <nav class="sidebar-navigation">

        <template
          v-for="section in visibleSections"
          :key="section.label"
        >

          <div class="sidebar-section">

            <!-- Section Heading -->

            <button
              v-if="!isCompact"
              type="button"
              class="section-heading"
              :aria-expanded="
                isSectionOpen(
                  section.label
                )
              "
              @click="
                toggleSection(
                  section.label
                )
              "
            >

              <span>
                {{ section.label }}
              </span>

              <i
                class="bi section-chevron"
                :class="
                  isSectionOpen(
                    section.label
                  )
                    ? 'bi-chevron-up'
                    : 'bi-chevron-down'
                "
              ></i>

            </button>


            <!-- Compact Separator -->

            <div
              v-if="isCompact"
              class="compact-section-separator"
              :title="section.label"
            ></div>


            <!-- Menu Items -->

            <Transition name="section-collapse">

              <div
                v-show="
                  isCompact
                  ||
                  isSectionOpen(
                    section.label
                  )
                "
                class="section-menu"
              >

                <RouterLink
                  v-for="item in section.items"
                  :key="item.key"
                  :to="item.route"
                  class="sidebar-menu-link"
                  :title="
                    isCompact
                      ? item.label
                      : undefined
                  "
                  @click="handleMenuClick"
                >

                  <span class="menu-icon">

                    <i :class="item.icon"></i>

                  </span>


                  <Transition name="sidebar-text">

                    <span
                      v-if="!isCompact"
                      class="menu-label"
                    >
                      {{ item.label }}
                    </span>

                  </Transition>


                  <Transition name="sidebar-text">

                    <span
                      v-if="
                        !isCompact
                        &&
                        item.badge
                      "
                      class="menu-badge"
                    >
                      {{ item.badge }}
                    </span>

                  </Transition>


                  <span
                    v-if="isCompact"
                    class="compact-tooltip"
                  >
                    {{ item.label }}
                  </span>

                </RouterLink>

              </div>

            </Transition>

          </div>

        </template>

      </nav>

    </div>


    <!-- User -->

    <div
      class="sidebar-user"
      :class="{
        'sidebar-user-compact':
          isCompact,
      }"
      role="button"
      tabindex="0"
      aria-label="Open user menu"
      @click="
        emit('user-menu')
      "
      @keydown.enter.prevent="
        emit('user-menu')
      "
      @keydown.space.prevent="
        emit('user-menu')
      "
    >

      <div
        class="user-avatar"
        :title="
          isCompact
            ? `${authStore.user?.name || 'User'} — ${roleDisplayName}`
            : undefined
        "
      >
        {{ userInitials }}
      </div>


      <Transition name="sidebar-text">

        <div
          v-if="!isCompact"
          class="user-details"
        >

          <div class="user-name">

            {{
              authStore.user?.name
              ||
              'User'
            }}

          </div>

          <div class="user-role">
            {{ roleDisplayName }}
          </div>

        </div>

      </Transition>


      <Transition name="sidebar-text">

        <button
          v-if="!isCompact"
          type="button"
          class="user-options-button"
          title="User options"
          aria-label="User options"
          @click.stop="
            emit('user-menu')
          "
        >

          <i class="bi bi-three-dots-vertical"></i>

        </button>

      </Transition>

    </div>

  </aside>

</template>


<script setup>

import {
  computed,
  reactive,
} from 'vue'

import {
  RouterLink,
} from 'vue-router'

import {
  useAuthStore,
} from '@/stores/auth'

import '@/assets/css/app-sidebar.css'


/*
|--------------------------------------------------------------------------
| Props and Emits
|--------------------------------------------------------------------------
*/

const props = defineProps({

  mobileOpen: {
    type: Boolean,
    default: false,
  },

  collapsed: {
    type: Boolean,
    default: false,
  },

})


const emit = defineEmits([

  'close',

  'toggle-collapse',

  'user-menu',

])


/*
|--------------------------------------------------------------------------
| Authentication
|--------------------------------------------------------------------------
*/

const authStore =
  useAuthStore()


/*
|--------------------------------------------------------------------------
| Sidebar State
|--------------------------------------------------------------------------
*/

const isCompact = computed(() => {

  return (
    props.collapsed
    &&
    !props.mobileOpen
  )

})


const openSections = reactive({

  Operations: true,

  Guest: true,

  'Back Office': true,

  Insights: true,

  System: true,

})


/*
|--------------------------------------------------------------------------
| Role Helpers
|--------------------------------------------------------------------------
*/

const currentRoleName = computed(() => {

  return String(
    authStore.user?.role?.name
    ??
    authStore.user?.role_name
    ??
    ''
  )
    .trim()
    .toLowerCase()

})


const isAdmin = computed(() => {

  return (
    currentRoleName.value ===
    'admin'
  )

})


/*
|--------------------------------------------------------------------------
| Sidebar Menu Configuration
|--------------------------------------------------------------------------
*/

const sidebarSections = [

  /*
  |--------------------------------------------------------------------------
  | Operations
  |--------------------------------------------------------------------------
  */

  {
    label: 'Operations',

    items: [

      {
        key: 'dashboard',

        label: 'Dashboard',

        route: {
          name: 'dashboard',
        },

        icon: 'bi bi-grid',

        permission:
          'dashboard.view',
      },


      {
        key: 'orders',

        label: 'Orders',

        route: {
          name: 'order-management',
        },

        icon: 'bi bi-receipt',

        permission:
          'orders.view',
      },


      {
        key: 'kitchen-display',

        label: 'Kitchen Display',

        route: {
          name: 'kitchen-display',
        },

        icon: 'bi bi-fire',

        permission:
          'kitchen.view',
      },


      {
        key: 'tables',

        label: 'Table Management',

        route: {
          name: 'tables',
        },

        icon:
          'bi bi-grid-3x3-gap-fill',

        permission:
          'tables.manage',
      },


      {
        key: 'billing',

        label: 'Billing',

        route: {
          name: 'billing',
        },

        icon:
          'bi bi-credit-card',

        permission:
          'billing.view',
      },

    ],
  },


  /*
  |--------------------------------------------------------------------------
  | Guest
  |--------------------------------------------------------------------------
  */

  {
    label: 'Guest',

    items: [

      {
        key: 'reservations',

        label: 'Reservations',

        route: {
          name: 'reservations',
        },

        icon:
          'bi bi-calendar2-check',

        permission:
          'reservations.manage',
      },


      {
        key: 'customers',

        label: 'Customers',

        route: {
          name: 'customers',
        },

        icon:
          'bi bi-people',

        permission:
          'customers.manage',
      },

    ],
  },


  /*
  |--------------------------------------------------------------------------
  | Back Office
  |--------------------------------------------------------------------------
  */

  {
    label: 'Back Office',

    items: [

      {
        key: 'menu',

        label: 'Menu',

        route: {
          name: 'menu',
        },

        icon:
          'bi bi-journal-text',

        permission:
          'menu.manage',
      },


      {
        key: 'inventory',

        label: 'Inventory',

        route: {
          name: 'inventory',
        },

        icon:
          'bi bi-box-seam',

        permission:
          'inventory.view',
      },


      {
        key: 'suppliers',

        label: 'Suppliers',

        route: {
          name: 'suppliers',
        },

        icon:
          'bi bi-truck',

        permission:
          'suppliers.manage',
      },


      {
        key: 'staff',

        label: 'Staff',

        route: {
          name: 'staff',
        },

        icon:
          'bi bi-person-badge',

        permission:
          'staff.manage',
      },


      /*
      |--------------------------------------------------------------------------
      | Admin-only Salary Management
      |--------------------------------------------------------------------------
      */

      {
        key: 'salary-management',

        label: 'Salary Management',

        route: {
          name: 'salary-management',
        },

        icon:
          'bi bi-cash-stack',

        adminOnly: true,
      },


      {
        key: 'expenses',

        label: 'Expenses',

        route: {
          name: 'expenses',
        },

        icon:
          'bi bi-wallet2',

        permission:
          'expenses.manage',
      },

    ],
  },


  /*
  |--------------------------------------------------------------------------
  | Insights
  |--------------------------------------------------------------------------
  */

  {
    label: 'Insights',

    items: [

      {
        key: 'reports',

        label: 'Reports',

        route: {
          name: 'reports',
        },

        icon:
          'bi bi-bar-chart-line',

        permission:
          'reports.sales',
      },

    ],
  },


  /*
  |--------------------------------------------------------------------------
  | System
  |--------------------------------------------------------------------------
  */

  {
    label: 'System',

    items: [

      {
        key: 'settings',

        label: 'Settings',

        route: {
          name: 'settings',
        },

        icon:
          'bi bi-gear',

        permission:
          'settings.manage',
      },


      {
        key: 'users',

        label: 'Users',

        route: {
          name: 'users',
        },

        icon:
          'bi bi-person-gear',

        permission:
          'users.manage',
      },


      {
        key: 'audit-logs',

        label: 'Audit Log',

        route: {
          name: 'audit-logs',
        },

        icon:
          'bi bi-clock-history',

        permission:
          'audit_logs.view',
      },

    ],
  },

]


/*
|--------------------------------------------------------------------------
| Visible Menu Sections
|--------------------------------------------------------------------------
*/

const visibleSections = computed(() => {

  return sidebarSections

    .map((section) => ({

      ...section,

      items:
        section.items.filter(
          (item) => {

            /*
            |--------------------------------------------------------------------------
            | Admin-only Items
            |--------------------------------------------------------------------------
            */

            if (
              item.adminOnly === true
              &&
              !isAdmin.value
            ) {
              return false
            }


            /*
            |--------------------------------------------------------------------------
            | Items Without Permission
            |--------------------------------------------------------------------------
            */

            if (!item.permission) {
              return true
            }


            /*
            |--------------------------------------------------------------------------
            | Permission Validation
            |--------------------------------------------------------------------------
            */

            return authStore.hasPermission(
              item.permission
            )

          }
        ),

    }))

    .filter(
      (section) =>
        section.items.length > 0
    )

})


/*
|--------------------------------------------------------------------------
| User Information
|--------------------------------------------------------------------------
*/

const roleDisplayName = computed(() => {

  return (
    authStore.user?.role
      ?.display_name
    ||
    authStore.user?.role?.name
    ||
    authStore.user?.role_name
    ||
    'User'
  )

})


const userInitials = computed(() => {

  const name =
    authStore.user?.name?.trim()


  if (!name) {
    return 'U'
  }


  const words =
    name
      .split(/\s+/)
      .filter(Boolean)


  if (words.length === 1) {

    return words[0]
      .charAt(0)
      .toUpperCase()

  }


  return `${words[0].charAt(0)}${words[
    words.length - 1
  ].charAt(0)}`.toUpperCase()

})


/*
|--------------------------------------------------------------------------
| Sidebar Actions
|--------------------------------------------------------------------------
*/

function toggleSection(
  sectionLabel
) {

  openSections[sectionLabel] =
    !openSections[sectionLabel]

}


function isSectionOpen(
  sectionLabel
) {

  return (
    openSections[sectionLabel]
    !==
    false
  )

}


function handleMenuClick()
{
  if (props.mobileOpen) {

    emit('close')

  }
}

</script>