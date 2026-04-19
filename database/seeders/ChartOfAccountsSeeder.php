<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class ChartOfAccountsSeeder extends Seeder
{
    public function run(): void
    {
        $orgId = DB::table('organizations')->first()->id;

        $accounts = [
            ['code' => '1000', 'name' => 'Assets', 'type' => 'asset', 'parent_id' => null, 'level' => 1, 'allow_manual_entries' => false],
            ['code' => '1100', 'name' => 'Current Assets', 'type' => 'asset', 'parent_code' => '1000', 'level' => 2, 'allow_manual_entries' => false],
            ['code' => '1110', 'name' => 'Cash and Bank', 'type' => 'asset', 'parent_code' => '1100', 'level' => 3, 'allow_manual_entries' => false],
            ['code' => '1111', 'name' => 'Cash in Hand', 'type' => 'asset', 'parent_code' => '1110', 'level' => 4, 'allow_manual_entries' => true],
            ['code' => '1112', 'name' => 'Bank Account - Main', 'type' => 'asset', 'parent_code' => '1110', 'level' => 4, 'allow_manual_entries' => true],
            ['code' => '1120', 'name' => 'Accounts Receivable', 'type' => 'asset', 'parent_code' => '1100', 'level' => 3, 'allow_manual_entries' => true],
            ['code' => '1130', 'name' => 'Inventory', 'type' => 'asset', 'parent_code' => '1100', 'level' => 3, 'allow_manual_entries' => true],
            ['code' => '1200', 'name' => 'Fixed Assets', 'type' => 'asset', 'parent_code' => '1000', 'level' => 2, 'allow_manual_entries' => false],
            ['code' => '1210', 'name' => 'Equipment', 'type' => 'asset', 'parent_code' => '1200', 'level' => 3, 'allow_manual_entries' => true],
            ['code' => '1220', 'name' => 'Vehicles', 'type' => 'asset', 'parent_code' => '1200', 'level' => 3, 'allow_manual_entries' => true],

            ['code' => '2000', 'name' => 'Liabilities', 'type' => 'liability', 'parent_id' => null, 'level' => 1, 'allow_manual_entries' => false],
            ['code' => '2100', 'name' => 'Current Liabilities', 'type' => 'liability', 'parent_code' => '2000', 'level' => 2, 'allow_manual_entries' => false],
            ['code' => '2110', 'name' => 'Accounts Payable', 'type' => 'liability', 'parent_code' => '2100', 'level' => 3, 'allow_manual_entries' => true],
            ['code' => '2120', 'name' => 'VAT Payable', 'type' => 'liability', 'parent_code' => '2100', 'level' => 3, 'allow_manual_entries' => true],
            ['code' => '2130', 'name' => 'Salary Payable', 'type' => 'liability', 'parent_code' => '2100', 'level' => 3, 'allow_manual_entries' => true],
            ['code' => '2200', 'name' => 'Long-term Liabilities', 'type' => 'liability', 'parent_code' => '2000', 'level' => 2, 'allow_manual_entries' => false],
            ['code' => '2210', 'name' => 'Long-term Loans', 'type' => 'liability', 'parent_code' => '2200', 'level' => 3, 'allow_manual_entries' => true],

            ['code' => '3000', 'name' => 'Equity', 'type' => 'equity', 'parent_id' => null, 'level' => 1, 'allow_manual_entries' => false],
            ['code' => '3100', 'name' => 'Owner Equity', 'type' => 'equity', 'parent_code' => '3000', 'level' => 2, 'allow_manual_entries' => true],
            ['code' => '3200', 'name' => 'Retained Earnings', 'type' => 'equity', 'parent_code' => '3000', 'level' => 2, 'allow_manual_entries' => true],

            ['code' => '4000', 'name' => 'Revenue', 'type' => 'revenue', 'parent_id' => null, 'level' => 1, 'allow_manual_entries' => false],
            ['code' => '4100', 'name' => 'Sales Revenue', 'type' => 'revenue', 'parent_code' => '4000', 'level' => 2, 'allow_manual_entries' => true],
            ['code' => '4200', 'name' => 'Service Revenue', 'type' => 'revenue', 'parent_code' => '4000', 'level' => 2, 'allow_manual_entries' => true],
            ['code' => '4300', 'name' => 'Other Income', 'type' => 'revenue', 'parent_code' => '4000', 'level' => 2, 'allow_manual_entries' => true],

            ['code' => '5000', 'name' => 'Expenses', 'type' => 'expense', 'parent_id' => null, 'level' => 1, 'allow_manual_entries' => false],
            ['code' => '5100', 'name' => 'Cost of Goods Sold', 'type' => 'expense', 'parent_code' => '5000', 'level' => 2, 'allow_manual_entries' => true],
            ['code' => '5200', 'name' => 'Operating Expenses', 'type' => 'expense', 'parent_code' => '5000', 'level' => 2, 'allow_manual_entries' => false],
            ['code' => '5210', 'name' => 'Salary Expense', 'type' => 'expense', 'parent_code' => '5200', 'level' => 3, 'allow_manual_entries' => true],
            ['code' => '5220', 'name' => 'Rent Expense', 'type' => 'expense', 'parent_code' => '5200', 'level' => 3, 'allow_manual_entries' => true],
            ['code' => '5230', 'name' => 'Utilities Expense', 'type' => 'expense', 'parent_code' => '5200', 'level' => 3, 'allow_manual_entries' => true],
            ['code' => '5240', 'name' => 'Office Supplies', 'type' => 'expense', 'parent_code' => '5200', 'level' => 3, 'allow_manual_entries' => true],
            ['code' => '5250', 'name' => 'Marketing Expense', 'type' => 'expense', 'parent_code' => '5200', 'level' => 3, 'allow_manual_entries' => true],
        ];

        $accountsMap = [];

        foreach ($accounts as $account) {
            $parentId = null;
            if (isset($account['parent_code'])) {
                $parentId = $accountsMap[$account['parent_code']] ?? null;
            } elseif (isset($account['parent_id'])) {
                $parentId = $account['parent_id'];
            }

            $id = DB::table('accounts')->insertGetId([
                'organization_id' => $orgId,
                'code' => $account['code'],
                'name' => $account['name'],
                'type' => $account['type'],
                'parent_id' => $parentId,
                'level' => $account['level'],
                'allow_manual_entries' => $account['allow_manual_entries'],
                'is_active' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            $accountsMap[$account['code']] = $id;
        }

        $this->command->info('Chart of accounts seeded successfully.');
    }
}
