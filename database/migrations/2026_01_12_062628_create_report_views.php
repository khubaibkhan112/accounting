<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // View: Account Balances - Aggregates account balances with transaction totals
        // This view can be filtered by date in queries
        DB::statement("
            CREATE OR REPLACE VIEW v_account_balances AS
            SELECT 
                a.id AS account_id,
                a.account_code,
                a.account_name,
                a.account_type,
                a.opening_balance,
                a.is_active,
                COALESCE(SUM(t.debit_amount), 0) AS total_debit,
                COALESCE(SUM(t.credit_amount), 0) AS total_credit,
                CASE 
                    WHEN a.account_type IN ('asset', 'expense') THEN 
                        a.opening_balance + COALESCE(SUM(t.debit_amount), 0) - COALESCE(SUM(t.credit_amount), 0)
                    ELSE 
                        a.opening_balance + COALESCE(SUM(t.credit_amount), 0) - COALESCE(SUM(t.debit_amount), 0)
                END AS current_balance
            FROM accounts a
            LEFT JOIN transactions t ON a.id = t.account_id
            WHERE a.is_active = 1
            GROUP BY a.id, a.account_code, a.account_name, a.account_type, a.opening_balance, a.is_active
        ");

        // View: Trial Balance - Shows all accounts with debit/credit balances
        // Note: This view shows balances up to current date. For date filtering, use v_account_balances with WHERE clause
        DB::statement("
            CREATE OR REPLACE VIEW v_trial_balance AS
            SELECT 
                account_id,
                account_code,
                account_name,
                account_type,
                opening_balance,
                total_debit,
                total_credit,
                current_balance,
                CASE 
                    WHEN account_type IN ('asset', 'expense') THEN
                        CASE WHEN current_balance >= 0 THEN current_balance ELSE 0 END
                    ELSE
                        CASE WHEN current_balance < 0 THEN ABS(current_balance) ELSE 0 END
                END AS debit_balance,
                CASE 
                    WHEN account_type IN ('asset', 'expense') THEN
                        CASE WHEN current_balance < 0 THEN ABS(current_balance) ELSE 0 END
                    ELSE
                        CASE WHEN current_balance >= 0 THEN current_balance ELSE 0 END
                END AS credit_balance
            FROM v_account_balances
            WHERE total_debit > 0 OR total_credit > 0 OR opening_balance != 0
        ");

        // View: Balance Sheet Assets
        DB::statement("
            CREATE OR REPLACE VIEW v_balance_sheet_assets AS
            SELECT 
                account_id,
                account_code,
                account_name,
                opening_balance,
                total_debit,
                total_credit,
                current_balance AS balance
            FROM v_account_balances
            WHERE account_type = 'asset'
                AND (current_balance != 0 OR total_debit > 0 OR total_credit > 0)
            ORDER BY account_code
        ");

        // View: Balance Sheet Liabilities
        DB::statement("
            CREATE OR REPLACE VIEW v_balance_sheet_liabilities AS
            SELECT 
                account_id,
                account_code,
                account_name,
                opening_balance,
                total_debit,
                total_credit,
                current_balance AS balance
            FROM v_account_balances
            WHERE account_type = 'liability'
                AND (current_balance != 0 OR total_debit > 0 OR total_credit > 0)
            ORDER BY account_code
        ");

        // View: Balance Sheet Equity
        DB::statement("
            CREATE OR REPLACE VIEW v_balance_sheet_equity AS
            SELECT 
                account_id,
                account_code,
                account_name,
                opening_balance,
                total_debit,
                total_credit,
                current_balance AS balance
            FROM v_account_balances
            WHERE account_type = 'equity'
                AND (current_balance != 0 OR total_debit > 0 OR total_credit > 0)
            ORDER BY account_code
        ");

        // View: Income Statement Revenue
        // Note: For date range filtering, this view shows all-time. Use transactions table directly for period-specific
        DB::statement("
            CREATE OR REPLACE VIEW v_income_statement_revenue AS
            SELECT 
                account_id,
                account_code,
                account_name,
                total_debit,
                total_credit,
                (total_credit - total_debit) AS balance
            FROM v_account_balances
            WHERE account_type = 'revenue'
                AND (total_debit > 0 OR total_credit > 0)
            ORDER BY account_code
        ");

        // View: Income Statement Expenses
        // Note: For date range filtering, this view shows all-time. Use transactions table directly for period-specific
        DB::statement("
            CREATE OR REPLACE VIEW v_income_statement_expenses AS
            SELECT 
                account_id,
                account_code,
                account_name,
                total_debit,
                total_credit,
                (total_debit - total_credit) AS balance
            FROM v_account_balances
            WHERE account_type = 'expense'
                AND (total_debit > 0 OR total_credit > 0)
            ORDER BY account_code
        ");

        // View: Account Transaction Summary by Date Range
        // This view helps with date-filtered reports
        DB::statement("
            CREATE OR REPLACE VIEW v_account_transaction_summary AS
            SELECT 
                a.id AS account_id,
                a.account_code,
                a.account_name,
                a.account_type,
                a.opening_balance,
                t.date,
                COALESCE(SUM(t.debit_amount), 0) AS period_debit,
                COALESCE(SUM(t.credit_amount), 0) AS period_credit
            FROM accounts a
            LEFT JOIN transactions t ON a.id = t.account_id
            WHERE a.is_active = 1
            GROUP BY a.id, a.account_code, a.account_name, a.account_type, a.opening_balance, t.date
        ");
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        DB::statement('DROP VIEW IF EXISTS v_account_transaction_summary');
        DB::statement('DROP VIEW IF EXISTS v_income_statement_expenses');
        DB::statement('DROP VIEW IF EXISTS v_income_statement_revenue');
        DB::statement('DROP VIEW IF EXISTS v_balance_sheet_equity');
        DB::statement('DROP VIEW IF EXISTS v_balance_sheet_liabilities');
        DB::statement('DROP VIEW IF EXISTS v_balance_sheet_assets');
        DB::statement('DROP VIEW IF EXISTS v_trial_balance');
        DB::statement('DROP VIEW IF EXISTS v_account_balances');
    }
};
