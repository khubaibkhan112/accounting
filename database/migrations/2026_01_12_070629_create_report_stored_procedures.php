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
        // Stored Procedure: Get Trial Balance
        // Returns trial balance data for a specific date range
        DB::statement("
            DROP PROCEDURE IF EXISTS sp_get_trial_balance;
        ");

        DB::statement("
            CREATE PROCEDURE sp_get_trial_balance(
                IN p_date_from DATE,
                IN p_date_to DATE
            )
            BEGIN
                SELECT 
                    a.id AS account_id,
                    a.account_code,
                    a.account_name,
                    a.account_type,
                    a.opening_balance,
                    COALESCE(SUM(CASE 
                        WHEN (p_date_from IS NULL OR t.date >= p_date_from) 
                        AND (p_date_to IS NULL OR t.date <= p_date_to)
                        THEN t.debit_amount ELSE 0 END), 0) AS total_debit,
                    COALESCE(SUM(CASE 
                        WHEN (p_date_from IS NULL OR t.date >= p_date_from) 
                        AND (p_date_to IS NULL OR t.date <= p_date_to)
                        THEN t.credit_amount ELSE 0 END), 0) AS total_credit,
                    CASE 
                        WHEN a.account_type IN ('asset', 'expense') THEN 
                            a.opening_balance + COALESCE(SUM(CASE 
                                WHEN (p_date_from IS NULL OR t.date >= p_date_from) 
                                AND (p_date_to IS NULL OR t.date <= p_date_to)
                                THEN t.debit_amount ELSE 0 END), 0) - 
                            COALESCE(SUM(CASE 
                                WHEN (p_date_from IS NULL OR t.date >= p_date_from) 
                                AND (p_date_to IS NULL OR t.date <= p_date_to)
                                THEN t.credit_amount ELSE 0 END), 0)
                        ELSE 
                            a.opening_balance + COALESCE(SUM(CASE 
                                WHEN (p_date_from IS NULL OR t.date >= p_date_from) 
                                AND (p_date_to IS NULL OR t.date <= p_date_to)
                                THEN t.credit_amount ELSE 0 END), 0) - 
                            COALESCE(SUM(CASE 
                                WHEN (p_date_from IS NULL OR t.date >= p_date_from) 
                                AND (p_date_to IS NULL OR t.date <= p_date_to)
                                THEN t.debit_amount ELSE 0 END), 0)
                    END AS current_balance
                FROM accounts a
                LEFT JOIN transactions t ON a.id = t.account_id
                WHERE a.is_active = 1
                GROUP BY a.id, a.account_code, a.account_name, a.account_type, a.opening_balance
                HAVING COALESCE(SUM(CASE 
                        WHEN (p_date_from IS NULL OR t.date >= p_date_from) 
                        AND (p_date_to IS NULL OR t.date <= p_date_to)
                        THEN t.debit_amount ELSE 0 END), 0) > 0 
                    OR COALESCE(SUM(CASE 
                        WHEN (p_date_from IS NULL OR t.date >= p_date_from) 
                        AND (p_date_to IS NULL OR t.date <= p_date_to)
                        THEN t.credit_amount ELSE 0 END), 0) > 0 
                    OR a.opening_balance != 0
                ORDER BY a.account_code;
            END
        ");

        // Stored Procedure: Get Balance Sheet Assets
        DB::statement("
            DROP PROCEDURE IF EXISTS sp_get_balance_sheet_assets;
        ");

        DB::statement("
            CREATE PROCEDURE sp_get_balance_sheet_assets(
                IN p_date_to DATE
            )
            BEGIN
                SELECT 
                    a.id AS account_id,
                    a.account_code,
                    a.account_name,
                    a.opening_balance,
                    COALESCE(SUM(t.debit_amount), 0) AS total_debit,
                    COALESCE(SUM(t.credit_amount), 0) AS total_credit,
                    (a.opening_balance + COALESCE(SUM(t.debit_amount), 0) - COALESCE(SUM(t.credit_amount), 0)) AS balance
                FROM accounts a
                LEFT JOIN transactions t ON a.id = t.account_id 
                    AND (p_date_to IS NULL OR t.date <= p_date_to)
                WHERE a.account_type = 'asset' AND a.is_active = 1
                GROUP BY a.id, a.account_code, a.account_name, a.opening_balance
                HAVING balance != 0 OR total_debit > 0 OR total_credit > 0
                ORDER BY a.account_code;
            END
        ");

        // Stored Procedure: Get Balance Sheet Liabilities
        DB::statement("
            DROP PROCEDURE IF EXISTS sp_get_balance_sheet_liabilities;
        ");

        DB::statement("
            CREATE PROCEDURE sp_get_balance_sheet_liabilities(
                IN p_date_to DATE
            )
            BEGIN
                SELECT 
                    a.id AS account_id,
                    a.account_code,
                    a.account_name,
                    a.opening_balance,
                    COALESCE(SUM(t.debit_amount), 0) AS total_debit,
                    COALESCE(SUM(t.credit_amount), 0) AS total_credit,
                    (a.opening_balance + COALESCE(SUM(t.credit_amount), 0) - COALESCE(SUM(t.debit_amount), 0)) AS balance
                FROM accounts a
                LEFT JOIN transactions t ON a.id = t.account_id 
                    AND (p_date_to IS NULL OR t.date <= p_date_to)
                WHERE a.account_type = 'liability' AND a.is_active = 1
                GROUP BY a.id, a.account_code, a.account_name, a.opening_balance
                HAVING balance != 0 OR total_debit > 0 OR total_credit > 0
                ORDER BY a.account_code;
            END
        ");

        // Stored Procedure: Get Balance Sheet Equity
        DB::statement("
            DROP PROCEDURE IF EXISTS sp_get_balance_sheet_equity;
        ");

        DB::statement("
            CREATE PROCEDURE sp_get_balance_sheet_equity(
                IN p_date_to DATE
            )
            BEGIN
                SELECT 
                    a.id AS account_id,
                    a.account_code,
                    a.account_name,
                    a.opening_balance,
                    COALESCE(SUM(t.debit_amount), 0) AS total_debit,
                    COALESCE(SUM(t.credit_amount), 0) AS total_credit,
                    (a.opening_balance + COALESCE(SUM(t.credit_amount), 0) - COALESCE(SUM(t.debit_amount), 0)) AS balance
                FROM accounts a
                LEFT JOIN transactions t ON a.id = t.account_id 
                    AND (p_date_to IS NULL OR t.date <= p_date_to)
                WHERE a.account_type = 'equity' AND a.is_active = 1
                GROUP BY a.id, a.account_code, a.account_name, a.opening_balance
                HAVING balance != 0 OR total_debit > 0 OR total_credit > 0
                ORDER BY a.account_code;
            END
        ");

        // Stored Procedure: Get Retained Earnings (Net Income)
        DB::statement("
            DROP PROCEDURE IF EXISTS sp_get_retained_earnings;
        ");

        DB::statement("
            CREATE PROCEDURE sp_get_retained_earnings(
                IN p_date_to DATE
            )
            BEGIN
                SELECT 
                    COALESCE((
                        SELECT SUM(COALESCE(t.credit_amount, 0) - COALESCE(t.debit_amount, 0))
                        FROM accounts a
                        LEFT JOIN transactions t ON a.id = t.account_id 
                            AND (p_date_to IS NULL OR t.date <= p_date_to)
                        WHERE a.account_type = 'revenue' AND a.is_active = 1
                    ), 0) - 
                    COALESCE((
                        SELECT SUM(COALESCE(t.debit_amount, 0) - COALESCE(t.credit_amount, 0))
                        FROM accounts a
                        LEFT JOIN transactions t ON a.id = t.account_id 
                            AND (p_date_to IS NULL OR t.date <= p_date_to)
                        WHERE a.account_type = 'expense' AND a.is_active = 1
                    ), 0) AS retained_earnings;
            END
        ");

        // Stored Procedure: Get Income Statement Revenue
        DB::statement("
            DROP PROCEDURE IF EXISTS sp_get_income_statement_revenue;
        ");

        DB::statement("
            CREATE PROCEDURE sp_get_income_statement_revenue(
                IN p_date_from DATE,
                IN p_date_to DATE
            )
            BEGIN
                SELECT 
                    a.id AS account_id,
                    a.account_code,
                    a.account_name,
                    COALESCE(SUM(t.debit_amount), 0) AS total_debit,
                    COALESCE(SUM(t.credit_amount), 0) AS total_credit,
                    (COALESCE(SUM(t.credit_amount), 0) - COALESCE(SUM(t.debit_amount), 0)) AS balance
                FROM accounts a
                LEFT JOIN transactions t ON a.id = t.account_id 
                    AND (p_date_from IS NULL OR t.date >= p_date_from)
                    AND (p_date_to IS NULL OR t.date <= p_date_to)
                WHERE a.account_type = 'revenue' AND a.is_active = 1
                GROUP BY a.id, a.account_code, a.account_name
                HAVING total_debit > 0 OR total_credit > 0
                ORDER BY a.account_code;
            END
        ");

        // Stored Procedure: Get Income Statement Expenses
        DB::statement("
            DROP PROCEDURE IF EXISTS sp_get_income_statement_expenses;
        ");

        DB::statement("
            CREATE PROCEDURE sp_get_income_statement_expenses(
                IN p_date_from DATE,
                IN p_date_to DATE
            )
            BEGIN
                SELECT 
                    a.id AS account_id,
                    a.account_code,
                    a.account_name,
                    COALESCE(SUM(t.debit_amount), 0) AS total_debit,
                    COALESCE(SUM(t.credit_amount), 0) AS total_credit,
                    (COALESCE(SUM(t.debit_amount), 0) - COALESCE(SUM(t.credit_amount), 0)) AS balance
                FROM accounts a
                LEFT JOIN transactions t ON a.id = t.account_id 
                    AND (p_date_from IS NULL OR t.date >= p_date_from)
                    AND (p_date_to IS NULL OR t.date <= p_date_to)
                WHERE a.account_type = 'expense' AND a.is_active = 1
                GROUP BY a.id, a.account_code, a.account_name
                HAVING total_debit > 0 OR total_credit > 0
                ORDER BY a.account_code;
            END
        ");
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        DB::statement('DROP PROCEDURE IF EXISTS sp_get_income_statement_expenses');
        DB::statement('DROP PROCEDURE IF EXISTS sp_get_income_statement_revenue');
        DB::statement('DROP PROCEDURE IF EXISTS sp_get_retained_earnings');
        DB::statement('DROP PROCEDURE IF EXISTS sp_get_balance_sheet_equity');
        DB::statement('DROP PROCEDURE IF EXISTS sp_get_balance_sheet_liabilities');
        DB::statement('DROP PROCEDURE IF EXISTS sp_get_balance_sheet_assets');
        DB::statement('DROP PROCEDURE IF EXISTS sp_get_trial_balance');
    }
};
