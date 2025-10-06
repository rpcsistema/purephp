<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;

class TenantSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Reset cached roles and permissions
        app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();

        // Create permissions for financial module
        $permissions = [
            // Financial permissions
            'financial.view',
            'financial.create',
            'financial.edit',
            'financial.delete',
            'financial.reports',
            
            // Revenue permissions
            'revenue.view',
            'revenue.create',
            'revenue.edit',
            'revenue.delete',
            
            // Expense permissions
            'expense.view',
            'expense.create',
            'expense.edit',
            'expense.delete',
            
            // Category permissions
            'category.view',
            'category.create',
            'category.edit',
            'category.delete',
            
            // User management permissions
            'users.view',
            'users.create',
            'users.edit',
            'users.delete',
            
            // Settings permissions
            'settings.view',
            'settings.edit',
        ];

        foreach ($permissions as $permission) {
            Permission::create(['name' => $permission]);
        }

        // Create roles
        $adminRole = Role::create(['name' => 'admin']);
        $managerRole = Role::create(['name' => 'manager']);
        $userRole = Role::create(['name' => 'user']);

        // Assign permissions to roles
        $adminRole->givePermissionTo(Permission::all());
        
        $managerRole->givePermissionTo([
            'financial.view',
            'financial.create',
            'financial.edit',
            'financial.reports',
            'revenue.view',
            'revenue.create',
            'revenue.edit',
            'expense.view',
            'expense.create',
            'expense.edit',
            'category.view',
            'category.create',
            'category.edit',
            'users.view',
            'settings.view',
        ]);

        $userRole->givePermissionTo([
            'financial.view',
            'revenue.view',
            'revenue.create',
            'expense.view',
            'expense.create',
            'category.view',
        ]);

        // Seed financial categories
        $this->call(FinancialCategoriesSeeder::class);
    }
}