<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;

class RolesPermissionsSeeder extends Seeder
{
    public function run(): void
    {
        // Reset cached roles and permissions
        app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();

        // Create permissions
        $permissions = $this->getPermissions();
        
        foreach ($permissions as $permission) {
            Permission::firstOrCreate([
                'name' => $permission['name'],
            ], [
                'guard_name' => 'sanctum',
            ]);
        }

        $this->command->info('Permissions seeded successfully.');

        // Create roles and assign permissions
        $this->createRolesAndAssignPermissions();
    }

    private function createRolesAndAssignPermissions(): void
    {
        // Super Admin - has all permissions across all organizations
        $superAdmin = Role::firstOrCreate(['name' => 'Super Admin', 'guard_name' => 'sanctum']);
        $superAdmin->givePermissionTo(Permission::where('guard_name', 'sanctum')->get());

        // Admin - has most permissions except some critical ones
        $admin = Role::firstOrCreate(['name' => 'Admin', 'guard_name' => 'sanctum']);
        $admin->givePermissionTo([
            'users:read', 'users:write', 'users:delete',
            'roles:read', 'roles:write', 'roles:manage_permissions',
            'organizations:read', 'organizations:write', 'organizations:delete',
            'branches:read', 'branches:write', 'branches:delete',
            'parties:read', 'parties:write', 'parties:delete',
            'invoices:read', 'invoices:write', 'invoices:approve', 'invoices:cancel',
            'payments:read', 'payments:write',
            'accounts:read', 'accounts:write',
            'journals:read', 'journals:write', 'journals:post',
            'reports:financial', 'reports:inventory', 'reports:hr',
            'products:read', 'products:write', 'products:delete',
            'warehouses:read', 'warehouses:write',
            'stock:read', 'stock:adjust', 'stock:count',
            'employees:read', 'employees:write', 'employees:delete',
            'attendance:read', 'attendance:write',
            'leaves:read', 'leaves:write', 'leaves:approve',
            'payroll:read', 'payroll:write', 'payroll:approve',
            'projects:read', 'projects:write',
            'tasks:read', 'tasks:write',
            'time_entries:read', 'time_entries:write',
            'settings:read', 'settings:write',
            'audit:read',
        ]);

        // OWNER_ORGANIZATION - has full permissions within their organization
        $ownerOrganization = Role::firstOrCreate(['name' => 'OWNER_ORGANIZATION', 'guard_name' => 'sanctum']);
        $ownerOrganization->givePermissionTo([
            'users:read', 'users:write',
            'roles:read',
            'organizations:read', 'organizations:write',
            'branches:read', 'branches:write', 'branches:delete',
            'parties:read', 'parties:write', 'parties:delete',
            'invoices:read', 'invoices:write', 'invoices:approve', 'invoices:cancel',
            'payments:read', 'payments:write',
            'accounts:read', 'accounts:write',
            'journals:read', 'journals:write', 'journals:post',
            'reports:financial', 'reports:inventory', 'reports:hr',
            'products:read', 'products:write', 'products:delete',
            'warehouses:read', 'warehouses:write',
            'stock:read', 'stock:adjust', 'stock:count',
            'employees:read', 'employees:write', 'employees:delete',
            'attendance:read', 'attendance:write',
            'leaves:read', 'leaves:write', 'leaves:approve',
            'payroll:read', 'payroll:write', 'payroll:approve',
            'projects:read', 'projects:write',
            'tasks:read', 'tasks:write',
            'time_entries:read', 'time_entries:write',
            'settings:read', 'settings:write',
            'audit:read',
        ]);

        // Manager - limited permissions
        $manager = Role::firstOrCreate(['name' => 'Manager', 'guard_name' => 'sanctum']);
        $manager->givePermissionTo([
            'branches:read', 'branches:write',
            'parties:read', 'parties:write',
            'invoices:read', 'invoices:write',
            'payments:read', 'payments:write',
            'reports:financial',
            'products:read', 'products:write',
            'stock:read', 'stock:adjust',
            'employees:read', 'employees:write',
            'attendance:read', 'attendance:write',
            'leaves:read', 'leaves:approve',
            'payroll:read',
            'projects:read', 'projects:write',
            'tasks:read', 'tasks:write',
            'time_entries:read', 'time_entries:write',
        ]);

        // User - basic read permissions
        $user = Role::firstOrCreate(['name' => 'User', 'guard_name' => 'sanctum']);
        $user->givePermissionTo([
            'parties:read',
            'invoices:read',
            'payments:read',
            'reports:financial',
            'products:read',
            'stock:read',
            'employees:read',
            'attendance:read',
            'leaves:read',
            'payroll:read',
            'projects:read',
            'tasks:read',
            'time_entries:read',
        ]);

        $this->command->info('Roles and permissions assigned successfully.');
    }

    private function getPermissions(): array
    {
        return [
            ['module' => 'users', 'action' => 'read', 'name' => 'users:read', 'label' => 'View Users'],
            ['module' => 'users', 'action' => 'write', 'name' => 'users:write', 'label' => 'Create/Edit Users'],
            ['module' => 'users', 'action' => 'delete', 'name' => 'users:delete', 'label' => 'Delete Users'],
            
            ['module' => 'roles', 'action' => 'read', 'name' => 'roles:read', 'label' => 'View Roles'],
            ['module' => 'roles', 'action' => 'write', 'name' => 'roles:write', 'label' => 'Create/Edit Roles'],
            ['module' => 'roles', 'action' => 'manage_permissions', 'name' => 'roles:manage_permissions', 'label' => 'Manage Role Permissions'],
            
            ['module' => 'organizations', 'action' => 'read', 'name' => 'organizations:read', 'label' => 'View Organizations'],
            ['module' => 'organizations', 'action' => 'write', 'name' => 'organizations:write', 'label' => 'Create/Edit Organizations'],
            ['module' => 'organizations', 'action' => 'delete', 'name' => 'organizations:delete', 'label' => 'Delete Organizations'],

            ['module' => 'branches', 'action' => 'read', 'name' => 'branches:read', 'label' => 'View Branches'],
            ['module' => 'branches', 'action' => 'write', 'name' => 'branches:write', 'label' => 'Create/Edit Branches'],
            ['module' => 'branches', 'action' => 'delete', 'name' => 'branches:delete', 'label' => 'Delete Branches'],
            
            ['module' => 'parties', 'action' => 'read', 'name' => 'parties:read', 'label' => 'View Parties'],
            ['module' => 'parties', 'action' => 'write', 'name' => 'parties:write', 'label' => 'Create/Edit Parties'],
            ['module' => 'parties', 'action' => 'delete', 'name' => 'parties:delete', 'label' => 'Delete Parties'],
            
            ['module' => 'invoices', 'action' => 'read', 'name' => 'invoices:read', 'label' => 'View Invoices'],
            ['module' => 'invoices', 'action' => 'write', 'name' => 'invoices:write', 'label' => 'Create/Edit Invoices'],
            ['module' => 'invoices', 'action' => 'approve', 'name' => 'invoices:approve', 'label' => 'Approve Invoices'],
            ['module' => 'invoices', 'action' => 'cancel', 'name' => 'invoices:cancel', 'label' => 'Cancel Invoices'],
            ['module' => 'invoices', 'action' => 'delete', 'name' => 'invoices:delete', 'label' => 'Delete Draft Invoices'],
            
            ['module' => 'payments', 'action' => 'read', 'name' => 'payments:read', 'label' => 'View Payments'],
            ['module' => 'payments', 'action' => 'write', 'name' => 'payments:write', 'label' => 'Record Payments'],
            
            ['module' => 'accounts', 'action' => 'read', 'name' => 'accounts:read', 'label' => 'View Chart of Accounts'],
            ['module' => 'accounts', 'action' => 'write', 'name' => 'accounts:write', 'label' => 'Create/Edit Accounts'],
            ['module' => 'accounts', 'action' => 'delete', 'name' => 'accounts:delete', 'label' => 'Delete Accounts'],
            
            ['module' => 'journals', 'action' => 'read', 'name' => 'journals:read', 'label' => 'View Journal Entries'],
            ['module' => 'journals', 'action' => 'write', 'name' => 'journals:write', 'label' => 'Create Manual Journal Entries'],
            ['module' => 'journals', 'action' => 'post', 'name' => 'journals:post', 'label' => 'Post Journal Entries'],
            
            ['module' => 'reports', 'action' => 'financial', 'name' => 'reports:financial', 'label' => 'View Financial Reports'],
            ['module' => 'reports', 'action' => 'inventory', 'name' => 'reports:inventory', 'label' => 'View Inventory Reports'],
            ['module' => 'reports', 'action' => 'hr', 'name' => 'reports:hr', 'label' => 'View HR Reports'],
            
            ['module' => 'products', 'action' => 'read', 'name' => 'products:read', 'label' => 'View Products'],
            ['module' => 'products', 'action' => 'write', 'name' => 'products:write', 'label' => 'Create/Edit Products'],
            ['module' => 'products', 'action' => 'delete', 'name' => 'products:delete', 'label' => 'Delete Products'],
            
            ['module' => 'warehouses', 'action' => 'read', 'name' => 'warehouses:read', 'label' => 'View Warehouses'],
            ['module' => 'warehouses', 'action' => 'write', 'name' => 'warehouses:write', 'label' => 'Create/Edit Warehouses'],
            
            ['module' => 'stock', 'action' => 'read', 'name' => 'stock:read', 'label' => 'View Stock Levels'],
            ['module' => 'stock', 'action' => 'adjust', 'name' => 'stock:adjust', 'label' => 'Adjust Stock'],
            ['module' => 'stock', 'action' => 'count', 'name' => 'stock:count', 'label' => 'Perform Stock Counts'],
            ['module' => 'stock', 'action' => 'approve_count', 'name' => 'stock:approve_count', 'label' => 'Approve Stock Counts'],
            
            ['module' => 'employees', 'action' => 'read', 'name' => 'employees:read', 'label' => 'View Employees'],
            ['module' => 'employees', 'action' => 'write', 'name' => 'employees:write', 'label' => 'Create/Edit Employees'],
            ['module' => 'employees', 'action' => 'delete', 'name' => 'employees:delete', 'label' => 'Delete Employees'],
            
            ['module' => 'attendance', 'action' => 'read', 'name' => 'attendance:read', 'label' => 'View Attendance'],
            ['module' => 'attendance', 'action' => 'write', 'name' => 'attendance:write', 'label' => 'Record Attendance'],
            
            ['module' => 'leaves', 'action' => 'read', 'name' => 'leaves:read', 'label' => 'View Leave Requests'],
            ['module' => 'leaves', 'action' => 'write', 'name' => 'leaves:write', 'label' => 'Create Leave Requests'],
            ['module' => 'leaves', 'action' => 'approve', 'name' => 'leaves:approve', 'label' => 'Approve/Reject Leave Requests'],
            
            ['module' => 'payroll', 'action' => 'read', 'name' => 'payroll:read', 'label' => 'View Payroll'],
            ['module' => 'payroll', 'action' => 'write', 'name' => 'payroll:write', 'label' => 'Generate Payroll'],
            ['module' => 'payroll', 'action' => 'approve', 'name' => 'payroll:approve', 'label' => 'Approve Payroll'],
            
            ['module' => 'projects', 'action' => 'read', 'name' => 'projects:read', 'label' => 'View Projects'],
            ['module' => 'projects', 'action' => 'write', 'name' => 'projects:write', 'label' => 'Create/Edit Projects'],
            ['module' => 'projects', 'action' => 'delete', 'name' => 'projects:delete', 'label' => 'Delete Projects'],
            
            ['module' => 'tasks', 'action' => 'read', 'name' => 'tasks:read', 'label' => 'View Tasks'],
            ['module' => 'tasks', 'action' => 'write', 'name' => 'tasks:write', 'label' => 'Create/Edit Tasks'],
            ['module' => 'tasks', 'action' => 'delete', 'name' => 'tasks:delete', 'label' => 'Delete Tasks'],
            
            ['module' => 'time_entries', 'action' => 'read', 'name' => 'time_entries:read', 'label' => 'View Time Entries'],
            ['module' => 'time_entries', 'action' => 'write', 'name' => 'time_entries:write', 'label' => 'Log Time Entries'],
            
            ['module' => 'settings', 'action' => 'read', 'name' => 'settings:read', 'label' => 'View Settings'],
            ['module' => 'settings', 'action' => 'write', 'name' => 'settings:write', 'label' => 'Modify Settings'],
            
            ['module' => 'audit', 'action' => 'read', 'name' => 'audit:read', 'label' => 'View Audit Logs'],
        ];
    }
}
