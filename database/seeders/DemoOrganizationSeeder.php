<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

class DemoOrganizationSeeder extends Seeder
{
    public function run(): void
    {
        $orgId = DB::table('organizations')->insertGetId([
            'name' => 'Demo Company',
            'legal_name' => 'Demo Company LLC',
            'tax_number' => '300000000000003',
            'base_currency' => 'SAR',
            'timezone' => 'Asia/Riyadh',
            'locale' => 'ar',
            'status' => 'active',
            'address' => 'King Fahd Road, Riyadh',
            'phone' => '+966112345678',
            'email' => 'info@democompany.com',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $branchId = DB::table('branches')->insertGetId([
            'organization_id' => $orgId,
            'name' => 'Main Branch',
            'code' => 'MAIN',
            'address' => 'King Fahd Road, Riyadh',
            'phone' => '+966112345678',
            'email' => 'main@democompany.com',
            'is_active' => true,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $adminRoleId = DB::table('roles')->insertGetId([
            'organization_id' => $orgId,
            'name' => 'admin',
            'label' => 'Administrator',
            'description' => 'Full system access',
            'is_system' => true,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $managerRoleId = DB::table('roles')->insertGetId([
            'organization_id' => $orgId,
            'name' => 'manager',
            'label' => 'Manager',
            'description' => 'Management access',
            'is_system' => false,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $accountantRoleId = DB::table('roles')->insertGetId([
            'organization_id' => $orgId,
            'name' => 'accountant',
            'label' => 'Accountant',
            'description' => 'Accounting module access',
            'is_system' => false,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $adminUserId = DB::table('users')->insertGetId([
            'organization_id' => $orgId,
            'branch_id' => $branchId,
            'name' => 'Admin User',
            'email' => 'admin@democompany.com',
            'password' => Hash::make('password'),
            'phone' => '+966500000001',
            'is_active' => true,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        DB::table('model_has_roles')->insert([
            'role_id' => $adminRoleId,
            'model_type' => 'App\\Models\\User',
            'model_id' => $adminUserId,
        ]);

        $permissions = DB::table('permissions')->pluck('id');
        foreach ($permissions as $permissionId) {
            DB::table('role_has_permissions')->insertOrIgnore([
                'role_id' => $adminRoleId,
                'permission_id' => $permissionId,
            ]);
        }

        $this->seedDefaultSettings($orgId);
        $this->seedPaymentTerms($orgId);
        $this->seedTaxRates($orgId);
        $this->seedUnits($orgId);

        $this->command->info('Demo organization seeded successfully.');
    }

    private function seedDefaultSettings(int $orgId): void
    {
        $settings = [
            ['group' => 'general', 'key' => 'company_name', 'value_json' => json_encode('Demo Company'), 'type' => 'string'],
            ['group' => 'general', 'key' => 'vat_rate', 'value_json' => json_encode(15), 'type' => 'integer'],
            ['group' => 'general', 'key' => 'currency', 'value_json' => json_encode('SAR'), 'type' => 'string'],
            ['group' => 'invoice', 'key' => 'auto_number_prefix', 'value_json' => json_encode('INV-'), 'type' => 'string'],
            ['group' => 'invoice', 'key' => 'default_payment_term_days', 'value_json' => json_encode(30), 'type' => 'integer'],
            ['group' => 'stock', 'key' => 'allow_negative_stock', 'value_json' => json_encode(false), 'type' => 'boolean'],
        ];

        foreach ($settings as $setting) {
            DB::table('settings')->insert([
                'organization_id' => $orgId,
                'group' => $setting['group'],
                'key' => $setting['key'],
                'value_json' => $setting['value_json'],
                'type' => $setting['type'],
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }
    }

    private function seedPaymentTerms(int $orgId): void
    {
        $terms = [
            ['name' => 'Immediate', 'due_days' => 0],
            ['name' => 'Net 7 Days', 'due_days' => 7],
            ['name' => 'Net 15 Days', 'due_days' => 15],
            ['name' => 'Net 30 Days', 'due_days' => 30],
            ['name' => 'Net 60 Days', 'due_days' => 60],
        ];

        foreach ($terms as $term) {
            DB::table('payment_terms')->insert([
                'organization_id' => $orgId,
                'name' => $term['name'],
                'due_days' => $term['due_days'],
                'is_active' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }
    }

    private function seedTaxRates(int $orgId): void
    {
        DB::table('tax_rates')->insert([
            [
                'organization_id' => $orgId,
                'name' => 'VAT 15%',
                'code' => 'VAT15',
                'rate' => 15.00,
                'is_default' => true,
                'is_active' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'organization_id' => $orgId,
                'name' => 'Zero Rated',
                'code' => 'ZERO',
                'rate' => 0.00,
                'is_default' => false,
                'is_active' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ]);
    }

    private function seedUnits(int $orgId): void
    {
        $units = [
            ['name' => 'Piece', 'symbol' => 'PC', 'is_base' => true],
            ['name' => 'Kilogram', 'symbol' => 'KG', 'is_base' => false],
            ['name' => 'Liter', 'symbol' => 'L', 'is_base' => false],
            ['name' => 'Meter', 'symbol' => 'M', 'is_base' => false],
            ['name' => 'Box', 'symbol' => 'BOX', 'is_base' => false],
            ['name' => 'Carton', 'symbol' => 'CTN', 'is_base' => false],
            ['name' => 'Hour', 'symbol' => 'HR', 'is_base' => false],
        ];

        foreach ($units as $unit) {
            DB::table('units')->insert([
                'organization_id' => $orgId,
                'name' => $unit['name'],
                'symbol' => $unit['symbol'],
                'is_base' => $unit['is_base'],
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }
    }
}
