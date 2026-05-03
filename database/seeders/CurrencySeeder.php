<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class CurrencySeeder extends Seeder
{
    public function run(): void
    {
        $currencies = [
            // Base currency
            [
                'code' => 'USD',
                'name' => 'US Dollar',
                'symbol' => '$',
                'decimal_separator' => '.',
                'thousands_separator' => ',',
                'decimal_places' => 2,
                'exchange_rate' => 1.000000,
                'is_base' => true,
                'is_active' => true,
            ],
            // Major currencies
            [
                'code' => 'EUR',
                'name' => 'Euro',
                'symbol' => '€',
                'decimal_separator' => ',',
                'thousands_separator' => '.',
                'decimal_places' => 2,
                'exchange_rate' => 0.920000,
                'is_base' => false,
                'is_active' => true,
            ],
            [
                'code' => 'GBP',
                'name' => 'British Pound',
                'symbol' => '£',
                'decimal_separator' => '.',
                'thousands_separator' => ',',
                'decimal_places' => 2,
                'exchange_rate' => 0.790000,
                'is_base' => false,
                'is_active' => true,
            ],
            [
                'code' => 'SAR',
                'name' => 'Saudi Riyal',
                'symbol' => 'ر.س',
                'decimal_separator' => '.',
                'thousands_separator' => ',',
                'decimal_places' => 2,
                'exchange_rate' => 3.750000,
                'is_base' => false,
                'is_active' => true,
            ],
            [
                'code' => 'AED',
                'name' => 'UAE Dirham',
                'symbol' => 'د.إ',
                'decimal_separator' => '.',
                'thousands_separator' => ',',
                'decimal_places' => 2,
                'exchange_rate' => 3.670000,
                'is_base' => false,
                'is_active' => true,
            ],
            [
                'code' => 'EGP',
                'name' => 'Egyptian Pound',
                'symbol' => 'ج.م',
                'decimal_separator' => '.',
                'thousands_separator' => ',',
                'decimal_places' => 2,
                'exchange_rate' => 30.900000,
                'is_base' => false,
                'is_active' => true,
            ],
            [
                'code' => 'JPY',
                'name' => 'Japanese Yen',
                'symbol' => '¥',
                'decimal_separator' => '.',
                'thousands_separator' => ',',
                'decimal_places' => 0,
                'exchange_rate' => 149.500000,
                'is_base' => false,
                'is_active' => true,
            ],
            [
                'code' => 'CNY',
                'name' => 'Chinese Yuan',
                'symbol' => '¥',
                'decimal_separator' => '.',
                'thousands_separator' => ',',
                'decimal_places' => 2,
                'exchange_rate' => 7.240000,
                'is_base' => false,
                'is_active' => true,
            ],
            [
                'code' => 'INR',
                'name' => 'Indian Rupee',
                'symbol' => '₹',
                'decimal_separator' => '.',
                'thousands_separator' => ',',
                'decimal_places' => 2,
                'exchange_rate' => 83.150000,
                'is_base' => false,
                'is_active' => true,
            ],
            [
                'code' => 'CAD',
                'name' => 'Canadian Dollar',
                'symbol' => 'C$',
                'decimal_separator' => '.',
                'thousands_separator' => ',',
                'decimal_places' => 2,
                'exchange_rate' => 1.360000,
                'is_base' => false,
                'is_active' => true,
            ],
            [
                'code' => 'AUD',
                'name' => 'Australian Dollar',
                'symbol' => 'A$',
                'decimal_separator' => '.',
                'thousands_separator' => ',',
                'decimal_places' => 2,
                'exchange_rate' => 1.530000,
                'is_base' => false,
                'is_active' => true,
            ],
        ];

        foreach ($currencies as $currency) {
            DB::table('currencies')->insert([
                'code' => $currency['code'],
                'name' => $currency['name'],
                'symbol' => $currency['symbol'],
                'decimal_separator' => $currency['decimal_separator'],
                'thousands_separator' => $currency['thousands_separator'],
                'decimal_places' => $currency['decimal_places'],
                'exchange_rate' => $currency['exchange_rate'],
                'is_base' => $currency['is_base'],
                'is_active' => $currency['is_active'],
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        $this->command->info('Currencies seeded successfully: ' . count($currencies) . ' currencies added.');
    }
}
