<?php

namespace Database\Seeders;

use App\Models\Account;
use Illuminate\Database\Seeder;

class AccountSeeder extends Seeder
{
    /**
     * Seed default chart of accounts.
     */
    public function run(): void
    {
        // Assets
        $assets = Account::create([
            'account_code' => '1000',
            'account_name' => 'Assets',
            'account_type' => 'asset',
            'parent_account_id' => null,
            'opening_balance' => 0,
            'description' => 'Main assets category',
            'is_active' => true,
        ]);

        Account::create([
            'account_code' => '1100',
            'account_name' => 'Current Assets',
            'account_type' => 'asset',
            'parent_account_id' => $assets->id,
            'opening_balance' => 0,
            'description' => 'Current assets',
            'is_active' => true,
        ]);

        Account::create([
            'account_code' => '1110',
            'account_name' => 'Cash',
            'account_type' => 'asset',
            'parent_account_id' => $assets->id,
            'opening_balance' => 0,
            'description' => 'Cash on hand and in bank',
            'is_active' => true,
        ]);

        Account::create([
            'account_code' => '1120',
            'account_name' => 'Accounts Receivable',
            'account_type' => 'asset',
            'parent_account_id' => $assets->id,
            'opening_balance' => 0,
            'description' => 'Amounts owed by customers',
            'is_active' => true,
        ]);

        Account::create([
            'account_code' => '1200',
            'account_name' => 'Fixed Assets',
            'account_type' => 'asset',
            'parent_account_id' => $assets->id,
            'opening_balance' => 0,
            'description' => 'Long-term assets',
            'is_active' => true,
        ]);

        // Liabilities
        $liabilities = Account::create([
            'account_code' => '2000',
            'account_name' => 'Liabilities',
            'account_type' => 'liability',
            'parent_account_id' => null,
            'opening_balance' => 0,
            'description' => 'Main liabilities category',
            'is_active' => true,
        ]);

        Account::create([
            'account_code' => '2100',
            'account_name' => 'Current Liabilities',
            'account_type' => 'liability',
            'parent_account_id' => $liabilities->id,
            'opening_balance' => 0,
            'description' => 'Short-term liabilities',
            'is_active' => true,
        ]);

        Account::create([
            'account_code' => '2110',
            'account_name' => 'Accounts Payable',
            'account_type' => 'liability',
            'parent_account_id' => $liabilities->id,
            'opening_balance' => 0,
            'description' => 'Amounts owed to suppliers',
            'is_active' => true,
        ]);

        // Equity
        $equity = Account::create([
            'account_code' => '3000',
            'account_name' => 'Equity',
            'account_type' => 'equity',
            'parent_account_id' => null,
            'opening_balance' => 0,
            'description' => 'Owner equity',
            'is_active' => true,
        ]);

        Account::create([
            'account_code' => '3100',
            'account_name' => 'Capital',
            'account_type' => 'equity',
            'parent_account_id' => $equity->id,
            'opening_balance' => 0,
            'description' => 'Owner capital',
            'is_active' => true,
        ]);

        Account::create([
            'account_code' => '3200',
            'account_name' => 'Retained Earnings',
            'account_type' => 'equity',
            'parent_account_id' => $equity->id,
            'opening_balance' => 0,
            'description' => 'Accumulated profits',
            'is_active' => true,
        ]);

        // Revenue
        $revenue = Account::create([
            'account_code' => '4000',
            'account_name' => 'Revenue',
            'account_type' => 'revenue',
            'parent_account_id' => null,
            'opening_balance' => 0,
            'description' => 'Income accounts',
            'is_active' => true,
        ]);

        Account::create([
            'account_code' => '4100',
            'account_name' => 'Sales Revenue',
            'account_type' => 'revenue',
            'parent_account_id' => $revenue->id,
            'opening_balance' => 0,
            'description' => 'Revenue from sales',
            'is_active' => true,
        ]);

        Account::create([
            'account_code' => '4200',
            'account_name' => 'Service Revenue',
            'account_type' => 'revenue',
            'parent_account_id' => $revenue->id,
            'opening_balance' => 0,
            'description' => 'Revenue from services',
            'is_active' => true,
        ]);

        // Expenses
        $expenses = Account::create([
            'account_code' => '5000',
            'account_name' => 'Expenses',
            'account_type' => 'expense',
            'parent_account_id' => null,
            'opening_balance' => 0,
            'description' => 'Expense accounts',
            'is_active' => true,
        ]);

        Account::create([
            'account_code' => '5100',
            'account_name' => 'Cost of Goods Sold',
            'account_type' => 'expense',
            'parent_account_id' => $expenses->id,
            'opening_balance' => 0,
            'description' => 'Direct costs',
            'is_active' => true,
        ]);

        Account::create([
            'account_code' => '5200',
            'account_name' => 'Operating Expenses',
            'account_type' => 'expense',
            'parent_account_id' => $expenses->id,
            'opening_balance' => 0,
            'description' => 'General operating expenses',
            'is_active' => true,
        ]);

        Account::create([
            'account_code' => '5210',
            'account_name' => 'Salaries and Wages',
            'account_type' => 'expense',
            'parent_account_id' => $expenses->id,
            'opening_balance' => 0,
            'description' => 'Employee compensation',
            'is_active' => true,
        ]);

        Account::create([
            'account_code' => '5220',
            'account_name' => 'Rent Expense',
            'account_type' => 'expense',
            'parent_account_id' => $expenses->id,
            'opening_balance' => 0,
            'description' => 'Rental expenses',
            'is_active' => true,
        ]);

        Account::create([
            'account_code' => '5230',
            'account_name' => 'Utilities Expense',
            'account_type' => 'expense',
            'parent_account_id' => $expenses->id,
            'opening_balance' => 0,
            'description' => 'Utility bills',
            'is_active' => true,
        ]);

        $this->command->info('Default chart of accounts created successfully.');
    }
}
