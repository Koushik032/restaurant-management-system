<?php

namespace Database\Seeders;

use App\Models\Permission;
use App\Models\Role;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class RolePermissionSeeder extends Seeder
{
    public function run(): void
    {
        DB::transaction(function (): void {
            $roles = [
                [
                    'name' => 'admin',
                    'display_name' => 'Admin',
                    'description' => 'Full system access.',
                ],
                [
                    'name' => 'manager',
                    'display_name' => 'Manager',
                    'description' => 'Restaurant operations and cashier access.',
                ],
                [
                    'name' => 'chef',
                    'display_name' => 'Chef',
                    'description' => 'Kitchen Display System access.',
                ],
            ];

            foreach ($roles as $role) {
                Role::query()->updateOrCreate(
                    ['name' => $role['name']],
                    $role
                );
            }

           $permissions = [
                [
                    'name' => 'dashboard.view',
                    'display_name' => 'View dashboard',
                    'module' => 'dashboard',
                ],

                [
                    'name' => 'orders.view',
                    'display_name' => 'View orders',
                    'module' => 'orders',
                ],
                [
                    'name' => 'orders.create',
                    'display_name' => 'Create orders',
                    'module' => 'orders',
                ],
                [
                    'name' => 'orders.update',
                    'display_name' => 'Update orders',
                    'module' => 'orders',
                ],
                [
                    'name' => 'orders.cancel',
                    'display_name' => 'Cancel orders',
                    'module' => 'orders',
                ],
                [
                    'name' => 'orders.complete',
                    'display_name' => 'Complete orders',
                    'module' => 'orders',
                ],

                [
                    'name' => 'payments.create',
                    'display_name' => 'Create payments',
                    'module' => 'payments',
                ],
                [
                    'name' => 'payments.split',
                    'display_name' => 'Create split payments',
                    'module' => 'payments',
                ],
                [
                    'name' => 'payments.refund',
                    'display_name' => 'Refund payments',
                    'module' => 'payments',
                ],
                [
                    'name' => 'billing.view',
                    'display_name' => 'View billing',
                    'module' => 'billing',
                ],

                [
                    'name' => 'invoices.view',
                    'display_name' => 'View invoices',
                    'module' => 'invoices',
                ],
                [
                    'name' => 'invoices.print',
                    'display_name' => 'Print invoices',
                    'module' => 'invoices',
                ],
                [
                    'name' => 'invoices.download',
                    'display_name' => 'Download invoices',
                    'module' => 'invoices',
                ],

                [
                    'name' => 'tables.manage',
                    'display_name' => 'Manage tables',
                    'module' => 'tables',
                ],
                [
                    'name' => 'reservations.manage',
                    'display_name' => 'Manage reservations',
                    'module' => 'reservations',
                ],
                [
                    'name' => 'customers.manage',
                    'display_name' => 'Manage customers',
                    'module' => 'customers',
                ],
                [
                    'name' => 'menu.manage',
                    'display_name' => 'Manage menu',
                    'module' => 'menu',
                ],

                [
                    'name' => 'kitchen.view',
                    'display_name' => 'View kitchen orders',
                    'module' => 'kitchen',
                ],
                [
                    'name' => 'kitchen.update',
                    'display_name' => 'Update kitchen orders',
                    'module' => 'kitchen',
                ],

                [
                    'name' => 'inventory.view',
                    'display_name' => 'View inventory',
                    'module' => 'inventory',
                ],
                [
                    'name' => 'inventory.manage',
                    'display_name' => 'Manage inventory',
                    'module' => 'inventory',
                ],

                [
                    'name' => 'purchases.manage',
                    'display_name' => 'Manage purchases',
                    'module' => 'purchases',
                ],
                [
                    'name' => 'suppliers.manage',
                    'display_name' => 'Manage suppliers',
                    'module' => 'suppliers',
                ],
                [
                    'name' => 'staff.manage',
                    'display_name' => 'Manage staff',
                    'module' => 'staff',
                ],
                [
                    'name' => 'expenses.manage',
                    'display_name' => 'Manage expenses',
                    'module' => 'expenses',
                ],

                [
                    'name' => 'shifts.open',
                    'display_name' => 'Open cash shifts',
                    'module' => 'shifts',
                ],
                [
                    'name' => 'shifts.close',
                    'display_name' => 'Close cash shifts',
                    'module' => 'shifts',
                ],

                [
                    'name' => 'reports.sales',
                    'display_name' => 'View sales reports',
                    'module' => 'reports',
                ],
                [
                    'name' => 'reports.inventory',
                    'display_name' => 'View inventory reports',
                    'module' => 'reports',
                ],
                [
                    'name' => 'reports.financial',
                    'display_name' => 'View financial reports',
                    'module' => 'reports',
                ],

                [
                    'name' => 'users.manage',
                    'display_name' => 'Manage users',
                    'module' => 'administration',
                ],
                [
                    'name' => 'settings.manage',
                    'display_name' => 'Manage settings',
                    'module' => 'administration',
                ],
                [
                    'name' => 'audit_logs.view',
                    'display_name' => 'View audit logs',
                    'module' => 'administration',
                ],
            ];

            foreach ($permissions as $permission) {
                Permission::query()->updateOrCreate(
                    ['name' => $permission['name']],
                    $permission
                );
            }

            $admin = Role::query()
                ->where('name', 'admin')
                ->firstOrFail();

            $manager = Role::query()
                ->where('name', 'manager')
                ->firstOrFail();

            $chef = Role::query()
                ->where('name', 'chef')
                ->firstOrFail();

            $admin->permissions()->sync(
                Permission::query()->pluck('id')
            );

            $managerPermissionNames = [
                'dashboard.view',

                // Operations
                'orders.view',
                'orders.create',
                'orders.update',
                'orders.cancel',
                'orders.complete',
                'kitchen.view',
                'tables.manage',
                'billing.view',
                'payments.create',
                'payments.split',
                'invoices.view',
                'invoices.print',
                'invoices.download',

                // Guest
                'reservations.manage',
                'customers.manage',

                // Back Office
                'menu.manage',
                'inventory.view',
                'purchases.manage',
                'suppliers.manage',
                'expenses.manage',

                // Cash Shift
                'shifts.open',
                'shifts.close',

                // Insights
                'reports.sales',
                'reports.inventory',
            ];

            $manager->permissions()->sync(
                Permission::query()
                    ->whereIn('name', $managerPermissionNames)
                    ->pluck('id')
            );

            $chefPermissionNames = [
                // Operations
                'dashboard.view',
                'orders.view',
                'kitchen.view',
                'kitchen.update',

                // Back Office
                'menu.manage',
                'inventory.view',
            ];
            
            $chef->permissions()->sync(
                Permission::query()
                    ->whereIn('name', $chefPermissionNames)
                    ->pluck('id')
            );
        });
    }
}