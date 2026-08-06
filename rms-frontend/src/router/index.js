/*
|--------------------------------------------------------------------------
| Vue Router Imports
|--------------------------------------------------------------------------
*/

import {
  createRouter,
  createWebHistory,
} from 'vue-router'


/*
|--------------------------------------------------------------------------
| Authentication Store
|--------------------------------------------------------------------------
*/

import {
  useAuthStore,
} from '@/stores/auth'


/*
|--------------------------------------------------------------------------
| Authentication and Error Views
|--------------------------------------------------------------------------
*/

import LoginView
  from '@/views/LoginView.vue'

import ForbiddenView
  from '@/views/ForbiddenView.vue'

import NotFoundView
  from '@/views/NotFoundView.vue'


/*
|--------------------------------------------------------------------------
| Main Application Layout
|--------------------------------------------------------------------------
*/

import DashboardLayout
  from '@/layouts/DashboardLayout.vue'


/*
|--------------------------------------------------------------------------
| Dashboard Views
|--------------------------------------------------------------------------
*/

import DashboardView
  from '@/views/DashboardView.vue'

import AdminOverviewView
  from '@/views/AdminOverviewView.vue'

import ManagerOverviewView
  from '@/views/ManagerOverviewView.vue'


/*
|--------------------------------------------------------------------------
| Shared Placeholder View
|--------------------------------------------------------------------------
*/

import ModulePlaceholderView
  from '@/views/ModulePlaceholderView.vue'


/*
|--------------------------------------------------------------------------
| Order Management Views
|--------------------------------------------------------------------------
*/

import OrderManagement
  from '@/views/orders/OrderManagement.vue'

import CreateOrderView
  from '@/views/orders/CreateOrderView.vue'

import OrderDetailsView
  from '@/views/orders/OrderDetailsView.vue'


/*
|--------------------------------------------------------------------------
| Kitchen Management Views
|--------------------------------------------------------------------------
*/

import KitchenOverviewView
  from '@/views/kitchen/KitchenOverviewView.vue'

import KitchenOrderDetailsView
  from '@/views/kitchen/KitchenOrderDetailsView.vue'


/*
|--------------------------------------------------------------------------
| Billing Management View
|--------------------------------------------------------------------------
*/

import BillingOverviewView
  from '@/views/billing/BillingOverviewView.vue'


/*
|--------------------------------------------------------------------------
| Table Management View
|--------------------------------------------------------------------------
*/

import TableManagementView
  from '@/views/tables/TableManagement.vue'


/*
|--------------------------------------------------------------------------
| Menu Management View
|--------------------------------------------------------------------------
*/

import MenuManagementView
  from '@/views/menu/MenuManagementView.vue'


/*
|--------------------------------------------------------------------------
| Customer Management Views
|--------------------------------------------------------------------------
*/

import CustomerManagementView
  from '@/views/customers/CustomerManagementView.vue'

import CustomerEditView
  from '@/components/customers/CustomerEditView.vue'

import CustomerDetailsView
  from '@/views/customers/CustomerDetailsView.vue'


/*
|--------------------------------------------------------------------------
| Supplier Management View
|--------------------------------------------------------------------------
*/

import SupplierManagementView
  from '@/views/suppliers/SupplierManagementView.vue'


/*
|--------------------------------------------------------------------------
| Staff Management View
|--------------------------------------------------------------------------
*/

import StaffManagementView
  from '@/views/staff/StaffManagementView.vue'


/*
|--------------------------------------------------------------------------
| Salary Management View
|--------------------------------------------------------------------------
*/

import SalaryManagementView
  from '@/views/salary/SalaryManagementView.vue'


/*
|--------------------------------------------------------------------------
| Role Helper
|--------------------------------------------------------------------------
*/

function getNormalizedRoleName(
  authStore
) {
  return String(
    authStore.user?.role?.name
    ??
    authStore.user?.role_name
    ??
    ''
  )
    .trim()
    .toLowerCase()
}


/*
|--------------------------------------------------------------------------
| Application Router
|--------------------------------------------------------------------------
*/

const router = createRouter({

  history: createWebHistory(
    import.meta.env.BASE_URL
  ),


  routes: [

    /*
    |--------------------------------------------------------------------------
    | Login Route
    |--------------------------------------------------------------------------
    */

    {
      path: '/login',

      name: 'login',

      component:
        LoginView,

      meta: {

        guestOnly: true,

        title: 'Login',

      },
    },


    /*
    |--------------------------------------------------------------------------
    | Protected Dashboard Layout
    |--------------------------------------------------------------------------
    */

    {
      path: '/',

      component:
        DashboardLayout,

      meta: {

        requiresAuth: true,

      },


      children: [

        /*
        |--------------------------------------------------------------------------
        | Root Dashboard Redirect
        |--------------------------------------------------------------------------
        */

        {
          path: '',

          redirect: {

            name: 'dashboard',

          },
        },


        /*
        |--------------------------------------------------------------------------
        | Dashboard
        |--------------------------------------------------------------------------
        */

        {
          path: 'dashboard',

          name: 'dashboard',

          component:
            DashboardView,

          meta: {

            permission:
              'dashboard.view',

            title:
              'Dashboard',

            description:
              'View restaurant operational overview',

            icon:
              'bi bi-speedometer2',

          },
        },


        /*
        |--------------------------------------------------------------------------
        | Order Management
        |--------------------------------------------------------------------------
        */

        {
          path: 'orders',

          name:
            'order-management',

          component:
            OrderManagement,

          meta: {

            permission:
              'orders.view',

            title:
              'Order Management',

            description:
              'Create and manage restaurant orders',

            icon:
              'bi bi-receipt',

          },
        },


        /*
        |--------------------------------------------------------------------------
        | Create Order
        |--------------------------------------------------------------------------
        */

        {
          path: 'orders/create',

          name:
            'order-create',

          component:
            CreateOrderView,

          meta: {

            permission:
              'orders.create',

            title:
              'Create Order',

            description:
              'Create a new restaurant order',

            icon:
              'bi bi-plus-circle',

          },
        },


        /*
        |--------------------------------------------------------------------------
        | Edit Order
        |--------------------------------------------------------------------------
        */

        {
          path:
            'orders/:id/edit',

          name:
            'order-edit',

          component:
            CreateOrderView,

          props: true,

          meta: {

            permission:
              'orders.update',

            title:
              'Edit Order',

            description:
              'Update an existing restaurant order',

            icon:
              'bi bi-pencil-square',

          },
        },


        /*
        |--------------------------------------------------------------------------
        | Order Details
        |--------------------------------------------------------------------------
        */

        {
          path:
            'orders/:id',

          name:
            'order-details',

          component:
            OrderDetailsView,

          props: true,

          meta: {

            permission:
              'orders.view',

            title:
              'Order Details',

            description:
              'View complete order information',

            icon:
              'bi bi-eye',

          },
        },


        /*
        |--------------------------------------------------------------------------
        | Kitchen Display
        |--------------------------------------------------------------------------
        */

        {
          path:
            'kitchen-display',

          name:
            'kitchen-display',

          component:
            KitchenOverviewView,

          meta: {

            permission:
              'kitchen.view',

            title:
              'Kitchen Display',

            description:
              'Manage kitchen orders, assigned chefs and preparation status',

            icon:
              'bi bi-fire',

          },
        },


        /*
        |--------------------------------------------------------------------------
        | Kitchen Order Details
        |--------------------------------------------------------------------------
        */

        {
          path:
            'kitchen-display/:id',

          name:
            'kitchen-order-details',

          component:
            KitchenOrderDetailsView,

          props: true,

          meta: {

            permission:
              'kitchen.view',

            title:
              'Kitchen Order Details',

            description:
              'Review menu items, ingredients, notes and kitchen progress',

            icon:
              'bi bi-receipt-cutoff',

          },
        },


        /*
        |--------------------------------------------------------------------------
        | Legacy Kitchen Redirect
        |--------------------------------------------------------------------------
        */

        {
          path: 'kitchen',

          redirect: {

            name:
              'kitchen-display',

          },
        },


        /*
        |--------------------------------------------------------------------------
        | Table Management
        |--------------------------------------------------------------------------
        */

        {
          path: 'tables',

          name: 'tables',

          component:
            TableManagementView,

          meta: {

            permission:
              'tables.manage',

            title:
              'Table Management',

            description:
              'Manage restaurant tables, sections and availability',

            icon:
              'bi bi-grid-3x3-gap-fill',

          },
        },


        /*
        |--------------------------------------------------------------------------
        | Billing
        |--------------------------------------------------------------------------
        */

        {
          path: 'billing',

          name: 'billing',

          component:
            BillingOverviewView,

          meta: {

            permission:
              'billing.view',

            title:
              'Billing & Statement',

            description:
              'View sales, settlements, payment modes and staff payment activity',

            icon:
              'bi bi-credit-card',

          },
        },


        /*
        |--------------------------------------------------------------------------
        | Reservations
        |--------------------------------------------------------------------------
        */

        {
          path:
            'reservations',

          name:
            'reservations',

          component:
            ModulePlaceholderView,

          meta: {

            permission:
              'reservations.manage',

            title:
              'Reservations',

            description:
              'Manage upcoming guest reservations',

            icon:
              'bi bi-calendar-check',

          },
        },


        /*
        |--------------------------------------------------------------------------
        | Customer Management
        |--------------------------------------------------------------------------
        */

        {
          path: 'customers',

          name: 'customers',

          component:
            CustomerManagementView,

          meta: {

            permission:
              'customers.manage',

            title:
              'Customers',

            description:
              'Manage guest and customer information',

            icon:
              'bi bi-people',

          },
        },


        /*
        |--------------------------------------------------------------------------
        | Customer Edit
        |--------------------------------------------------------------------------
        */

        {
          path:
            'customers/:id/edit',

          name:
            'customer-edit',

          component:
            CustomerEditView,

          props: true,

          meta: {

            permission:
              'customers.manage',

            title:
              'Edit Customer',

            description:
              'Update customer contact information and status',

            icon:
              'bi bi-pencil-square',

          },
        },


        /*
        |--------------------------------------------------------------------------
        | Customer Details
        |--------------------------------------------------------------------------
        */

        {
          path:
            'customers/:id',

          name:
            'customer-details',

          component:
            CustomerDetailsView,

          props: true,

          meta: {

            permission:
              'customers.manage',

            title:
              'Customer Details',

            description:
              'View customer profile and visit history',

            icon:
              'bi bi-person-lines-fill',

          },
        },


        /*
        |--------------------------------------------------------------------------
        | Menu Management
        |--------------------------------------------------------------------------
        */

        {
          path: 'menu',

          name: 'menu',

          component:
            MenuManagementView,

          meta: {

            permission:
              'menu.manage',

            title:
              'Menu Management',

            description:
              'Manage menu categories, items, variants and add-ons',

            icon:
              'bi bi-journal-text',

          },
        },


        /*
        |--------------------------------------------------------------------------
        | Inventory
        |--------------------------------------------------------------------------
        */

        {
          path: 'inventory',

          name: 'inventory',

          component:
            ModulePlaceholderView,

          meta: {

            permission:
              'inventory.view',

            title:
              'Inventory',

            description:
              'Track ingredients and inventory stock',

            icon:
              'bi bi-box-seam',

          },
        },


        /*
        |--------------------------------------------------------------------------
        | Suppliers
        |--------------------------------------------------------------------------
        */

        {
          path: 'suppliers',

          name: 'suppliers',

          component:
            SupplierManagementView,

          meta: {

            permission:
              'suppliers.manage',

            title:
              'Supplier Management',

            description:
              'Manage restaurant suppliers',

            icon:
              'bi bi-truck',

          },
        },


        /*
        |--------------------------------------------------------------------------
        | Staff Management
        |--------------------------------------------------------------------------
        */

        {
          path: 'staff',

          name: 'staff',

          component:
            StaffManagementView,

          meta: {

            permission:
              'staff.manage',

            title:
              'Staff',

            description:
              'Manage restaurant staff members',

            icon:
              'bi bi-person-badge',

          },
        },


        /*
        |--------------------------------------------------------------------------
        | Salary Management — Admin Only
        |--------------------------------------------------------------------------
        */

        {
          path:
            'salary-management',

          name:
            'salary-management',

          component:
            SalaryManagementView,

          meta: {

            adminOnly: true,

            title:
              'Salary Management',

            description:
              'Calculate, review and manage employee salaries',

            icon:
              'bi bi-cash-stack',

          },
        },


        /*
        |--------------------------------------------------------------------------
        | Expense Management
        |--------------------------------------------------------------------------
        */

        {
          path: 'expenses',

          name: 'expenses',

          component: () =>
            import(
              '@/views/expenses/ExpenseManagementView.vue'
            ),

          meta: {

            permission:
              'expenses.manage',

            title:
              'Expense Management',

            description:
              'Manage restaurant expenses',

            icon:
              'bi bi-wallet2',

          },
        },


        /*
        |--------------------------------------------------------------------------
        | Reports
        |--------------------------------------------------------------------------
        */

        {
          path: 'reports',

          name: 'reports',

          component:
            ModulePlaceholderView,

          meta: {

            permission:
              'reports.sales',

            title:
              'Reports',

            description:
              'View restaurant sales and operational reports',

            icon:
              'bi bi-bar-chart-line',

          },
        },


        /*
        |--------------------------------------------------------------------------
        | Settings
        |--------------------------------------------------------------------------
        */

        {
          path: 'settings',

          name: 'settings',

          component:
            ModulePlaceholderView,

          meta: {

            permission:
              'settings.manage',

            title:
              'Settings',

            description:
              'Configure restaurant and system settings',

            icon:
              'bi bi-gear',

          },
        },


        /*
        |--------------------------------------------------------------------------
        | Users
        |--------------------------------------------------------------------------
        */

        {
          path: 'users',

          name: 'users',

          component:
            ModulePlaceholderView,

          meta: {

            permission:
              'users.manage',

            title:
              'Users',

            description:
              'Manage system users and roles',

            icon:
              'bi bi-person-gear',

          },
        },


        /*
        |--------------------------------------------------------------------------
        | Audit Logs
        |--------------------------------------------------------------------------
        */

        {
          path: 'audit-logs',

          name: 'audit-logs',

          component:
            ModulePlaceholderView,

          meta: {

            permission:
              'audit_logs.view',

            title:
              'Audit Log',

            description:
              'Review important system activities',

            icon:
              'bi bi-clock-history',

          },
        },


        /*
        |--------------------------------------------------------------------------
        | Admin Overview
        |--------------------------------------------------------------------------
        */

        {
          path: 'admin',

          name:
            'admin-overview',

          component:
            AdminOverviewView,

          meta: {

            permission:
              'users.manage',

            title:
              'Admin Overview',

            description:
              'View administrative system overview',

            icon:
              'bi bi-shield-check',

          },
        },


        /*
        |--------------------------------------------------------------------------
        | Manager Overview
        |--------------------------------------------------------------------------
        */

        {
          path: 'manager',

          name:
            'manager-overview',

          component:
            ManagerOverviewView,

          meta: {

            permission:
              'orders.create',

            title:
              'Manager Overview',

            description:
              'View restaurant management overview',

            icon:
              'bi bi-briefcase',

          },
        },

      ],
    },


    /*
    |--------------------------------------------------------------------------
    | Access Denied
    |--------------------------------------------------------------------------
    */

    {
      path: '/forbidden',

      name: 'forbidden',

      component:
        ForbiddenView,

      meta: {

        requiresAuth: true,

        title:
          'Access Denied',

      },
    },


    /*
    |--------------------------------------------------------------------------
    | Page Not Found
    |--------------------------------------------------------------------------
    */

    {
      path:
        '/:pathMatch(.*)*',

      name:
        'not-found',

      component:
        NotFoundView,

      meta: {

        title:
          'Page Not Found',

      },
    },

  ],

})


/*
|--------------------------------------------------------------------------
| Global Navigation Guard
|--------------------------------------------------------------------------
*/

router.beforeEach(
  async (to) => {

    const authStore =
      useAuthStore()


    /*
    |--------------------------------------------------------------------------
    | Initialize Authentication
    |--------------------------------------------------------------------------
    */

    try {

      await authStore
        .initializeAuth()

    }

    catch (error) {

      console.error(
        'Authentication initialization failed:',
        error
      )

    }


    /*
    |--------------------------------------------------------------------------
    | Browser Page Title
    |--------------------------------------------------------------------------
    */

    const applicationName =
      import.meta.env.VITE_APP_NAME
      ||
      'RMS'


    const pageTitle =
      to.meta.title
      ||
      'Dashboard'


    document.title =
      `${pageTitle} | ${applicationName}`


    /*
    |--------------------------------------------------------------------------
    | Guest-only Route Protection
    |--------------------------------------------------------------------------
    */

    if (
      to.meta.guestOnly
      &&
      authStore.isAuthenticated
    ) {

      return authStore
        .getDefaultRoute()

    }


    /*
    |--------------------------------------------------------------------------
    | Authentication Requirement
    |--------------------------------------------------------------------------
    */

    const requiresAuth =
      to.matched.some(
        (record) =>
          Boolean(
            record.meta.requiresAuth
          )
      )


    if (
      requiresAuth
      &&
      !authStore.isAuthenticated
    ) {

      return {

        name: 'login',

        query: {

          redirect:
            to.fullPath,

        },

      }

    }


    /*
    |--------------------------------------------------------------------------
    | Admin-only Route Protection
    |--------------------------------------------------------------------------
    */

    const adminOnly =
      to.matched.some(
        (record) =>
          Boolean(
            record.meta.adminOnly
          )
      )


    if (adminOnly) {

      const roleName =
        getNormalizedRoleName(
          authStore
        )


      if (roleName !== 'admin') {

        return {

          name: 'forbidden',

        }

      }

    }


    /*
    |--------------------------------------------------------------------------
    | Resolve Permission
    |--------------------------------------------------------------------------
    */

    const permissionRecord =
      [...to.matched]
        .reverse()
        .find(
          (record) =>
            Boolean(
              record.meta.permission
            )
        )


    const requiredPermission =
      permissionRecord
        ?.meta
        ?.permission


    /*
    |--------------------------------------------------------------------------
    | Permission Protection
    |--------------------------------------------------------------------------
    */

    if (
      requiredPermission
      &&
      !authStore.hasPermission(
        requiredPermission
      )
    ) {

      return {

        name: 'forbidden',

      }

    }


    /*
    |--------------------------------------------------------------------------
    | Allow Navigation
    |--------------------------------------------------------------------------
    */

    return true

  }
)


/*
|--------------------------------------------------------------------------
| Router Error Handler
|--------------------------------------------------------------------------
*/

router.onError(
  (error) => {

    console.error(
      'Router navigation error:',
      error
    )

  }
)


/*
|--------------------------------------------------------------------------
| Export Router
|--------------------------------------------------------------------------
*/

export default router