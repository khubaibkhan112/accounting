<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Setting;

class SettingsSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $now = new \DateTime();
        $currentYear = $now->format('Y');

        $settings = [
            [
                'key' => 'company_name',
                'value' => '',
                'type' => 'string',
                'description' => 'Company or organization name',
            ],
            [
                'key' => 'fiscal_year_start',
                'value' => $currentYear . '-01-01',
                'type' => 'string',
                'description' => 'Fiscal year start date',
            ],
            [
                'key' => 'fiscal_year_end',
                'value' => $currentYear . '-12-31',
                'type' => 'string',
                'description' => 'Fiscal year end date',
            ],
            [
                'key' => 'currency',
                'value' => 'USD',
                'type' => 'string',
                'description' => 'Default currency for transactions',
            ],
            [
                'key' => 'default_account_type',
                'value' => 'asset',
                'type' => 'string',
                'description' => 'Default account type for new accounts',
            ],
            [
                'key' => 'auto_generate_reference',
                'value' => 'false',
                'type' => 'boolean',
                'description' => 'Automatically generate reference numbers for transactions',
            ],
        ];

        foreach ($settings as $setting) {
            Setting::updateOrCreate(
                ['key' => $setting['key']],
                $setting
            );
        }
    }
}
