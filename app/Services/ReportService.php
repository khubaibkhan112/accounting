<?php

namespace App\Services;

use App\Models\Account;
use App\Models\Transaction;
use App\Services\AccountingService;

class ReportService
{
    protected AccountingService $accountingService;

    public function __construct(AccountingService $accountingService)
    {
        $this->accountingService = $accountingService;
    }

    /**
     * Generate Trial Balance Report.
     */
    public function generateTrialBalance(?string $dateFrom = null, ?string $dateTo = null): array
    {
        $dateTo = $dateTo ?? now()->format('Y-m-d');

        // Get all active accounts
        $accounts = Account::select([
            'id',
            'account_code',
            'account_name',
            'account_type',
            'opening_balance',
        ])
        ->where('is_active', true)
        ->orderBy('account_code')
        ->get();

        $trialBalance = [];
        $totalDebit = 0;
        $totalCredit = 0;

        foreach ($accounts as $account) {
            // Build transaction query
            $transactionQuery = Transaction::where('account_id', $account->id);

            if ($dateFrom) {
                $transactionQuery->whereBetween('date', [$dateFrom, $dateTo]);
            } else {
                $transactionQuery->where('date', '<=', $dateTo);
            }

            // Calculate totals
            $debitTotal = (float) $transactionQuery->sum('debit_amount');
            $creditTotal = (float) $transactionQuery->sum('credit_amount');

            // Calculate balance
            $openingBalance = (float) $account->opening_balance;
            
            if (in_array($account->account_type, ['asset', 'expense'])) {
                $balance = $openingBalance + $debitTotal - $creditTotal;
                $debitBalance = $balance >= 0 ? $balance : 0;
                $creditBalance = $balance < 0 ? abs($balance) : 0;
            } else {
                $balance = $openingBalance + $creditTotal - $debitTotal;
                $debitBalance = $balance < 0 ? abs($balance) : 0;
                $creditBalance = $balance >= 0 ? $balance : 0;
            }

            // Only include accounts with transactions or non-zero opening balance
            if ($debitTotal > 0 || $creditTotal > 0 || $openingBalance != 0) {
                $trialBalance[] = [
                    'account_code' => $account->account_code,
                    'account_name' => $account->account_name,
                    'account_type' => $account->account_type,
                    'debit_total' => $debitTotal,
                    'credit_total' => $creditTotal,
                    'debit_balance' => $debitBalance,
                    'credit_balance' => $creditBalance,
                ];

                $totalDebit += $debitBalance;
                $totalCredit += $creditBalance;
            }
        }

        return [
            'report_type' => 'trial_balance',
            'date_from' => $dateFrom,
            'date_to' => $dateTo,
            'total_debit' => $totalDebit,
            'total_credit' => $totalCredit,
            'is_balanced' => abs($totalDebit - $totalCredit) < 0.01,
            'accounts' => $trialBalance,
            'generated_at' => now()->toDateTimeString(),
        ];
    }

    /**
     * Generate Balance Sheet Report.
     */
    public function generateBalanceSheet(?string $dateTo = null): array
    {
        $dateTo = $dateTo ?? now()->format('Y-m-d');

        // Get assets
        $assets = $this->getAccountsByType(['asset'], $dateTo);
        $totalAssets = array_sum(array_column($assets, 'balance'));

        // Get liabilities
        $liabilities = $this->getAccountsByType(['liability'], $dateTo);
        $totalLiabilities = array_sum(array_column($liabilities, 'balance'));

        // Get equity
        $equity = $this->getAccountsByType(['equity'], $dateTo);
        $totalEquity = array_sum(array_column($equity, 'balance'));

        // Calculate retained earnings (if revenue/expense accounts exist)
        $retainedEarnings = $this->calculateRetainedEarnings($dateTo);
        $totalEquity += $retainedEarnings;

        // Balance sheet equation: Assets = Liabilities + Equity
        $totalLiabilitiesAndEquity = $totalLiabilities + $totalEquity;
        $isBalanced = abs($totalAssets - $totalLiabilitiesAndEquity) < 0.01;

        return [
            'report_type' => 'balance_sheet',
            'date' => $dateTo,
            'assets' => [
                'accounts' => $assets,
                'total' => $totalAssets,
            ],
            'liabilities' => [
                'accounts' => $liabilities,
                'total' => $totalLiabilities,
            ],
            'equity' => [
                'accounts' => $equity,
                'retained_earnings' => $retainedEarnings,
                'total' => $totalEquity,
            ],
            'total_assets' => $totalAssets,
            'total_liabilities_and_equity' => $totalLiabilitiesAndEquity,
            'is_balanced' => $isBalanced,
            'generated_at' => now()->toDateTimeString(),
        ];
    }

    /**
     * Generate Income Statement (Profit & Loss) Report.
     */
    public function generateIncomeStatement(?string $dateFrom = null, ?string $dateTo = null): array
    {
        $dateFrom = $dateFrom ?? now()->startOfYear()->format('Y-m-d');
        $dateTo = $dateTo ?? now()->format('Y-m-d');

        // Get revenue accounts
        $revenues = $this->getAccountsByTypeWithDateRange(['revenue'], $dateFrom, $dateTo);
        $totalRevenue = array_sum(array_column($revenues, 'balance'));

        // Get expense accounts
        $expenses = $this->getAccountsByTypeWithDateRange(['expense'], $dateFrom, $dateTo);
        $totalExpenses = array_sum(array_column($expenses, 'balance'));

        // Calculate net income (revenue - expenses)
        $netIncome = $totalRevenue - $totalExpenses;

        return [
            'report_type' => 'income_statement',
            'date_from' => $dateFrom,
            'date_to' => $dateTo,
            'revenue' => [
                'accounts' => $revenues,
                'total' => $totalRevenue,
            ],
            'expenses' => [
                'accounts' => $expenses,
                'total' => $totalExpenses,
            ],
            'net_income' => $netIncome,
            'is_profit' => $netIncome > 0,
            'generated_at' => now()->toDateTimeString(),
        ];
    }

    /**
     * Get accounts by type with balances calculated up to a date.
     */
    private function getAccountsByType(array $types, string $dateTo): array
    {
        $accounts = Account::select([
            'id',
            'account_code',
            'account_name',
            'account_type',
            'opening_balance',
        ])
        ->whereIn('account_type', $types)
        ->where('is_active', true)
        ->orderBy('account_code')
        ->get();

        $result = [];

        foreach ($accounts as $account) {
            // Calculate balance up to date using AccountingService
            $balance = $this->accountingService->calculateAccountBalance($account, $dateTo);
            $totals = $this->accountingService->calculateAccountTotals($account, null, $dateTo);

            // Only include accounts with non-zero balance or transactions
            if ($balance != 0 || $totals['debit_total'] > 0 || $totals['credit_total'] > 0) {
                $result[] = [
                    'account_code' => $account->account_code,
                    'account_name' => $account->account_name,
                    'account_type' => $account->account_type,
                    'opening_balance' => (float) $account->opening_balance,
                    'debit_total' => $totals['debit_total'],
                    'credit_total' => $totals['credit_total'],
                    'balance' => $balance,
                ];
            }
        }

        return $result;
    }

    /**
     * Get accounts by type with balances calculated for a date range.
     */
    private function getAccountsByTypeWithDateRange(array $types, string $dateFrom, string $dateTo): array
    {
        $accounts = Account::select([
            'id',
            'account_code',
            'account_name',
            'account_type',
            'opening_balance',
        ])
        ->whereIn('account_type', $types)
        ->where('is_active', true)
        ->orderBy('account_code')
        ->get();

        $result = [];

        foreach ($accounts as $account) {
            // Calculate balance for date range only (period-specific)
            $totals = $this->accountingService->calculateAccountTotals($account, $dateFrom, $dateTo);

            // For income statement, we typically don't include opening balance
            // Revenue and expenses are period-specific
            if (in_array($account->account_type, ['revenue'])) {
                // Revenue increases with credits
                $balance = $totals['credit_total'] - $totals['debit_total'];
            } else {
                // Expenses increase with debits
                $balance = $totals['debit_total'] - $totals['credit_total'];
            }

            // Only include accounts with transactions
            if ($totals['debit_total'] > 0 || $totals['credit_total'] > 0) {
                $result[] = [
                    'account_code' => $account->account_code,
                    'account_name' => $account->account_name,
                    'account_type' => $account->account_type,
                    'debit_total' => $totals['debit_total'],
                    'credit_total' => $totals['credit_total'],
                    'balance' => $balance,
                ];
            }
        }

        return $result;
    }

    /**
     * Calculate retained earnings (net income from revenue and expense accounts).
     */
    private function calculateRetainedEarnings(string $dateTo): float
    {
        // Get all revenue accounts
        $revenueAccounts = Account::where('account_type', 'revenue')
            ->where('is_active', true)
            ->pluck('id');

        $revenueQuery = Transaction::whereIn('account_id', $revenueAccounts)
            ->where('date', '<=', $dateTo);

        $revenueDebit = (float) $revenueQuery->sum('debit_amount');
        $revenueCredit = (float) $revenueQuery->sum('credit_amount');
        $totalRevenue = $revenueCredit - $revenueDebit; // Revenue increases with credits

        // Get all expense accounts
        $expenseAccounts = Account::where('account_type', 'expense')
            ->where('is_active', true)
            ->pluck('id');

        $expenseQuery = Transaction::whereIn('account_id', $expenseAccounts)
            ->where('date', '<=', $dateTo);

        $expenseDebit = (float) $expenseQuery->sum('debit_amount');
        $expenseCredit = (float) $expenseQuery->sum('credit_amount');
        $totalExpenses = $expenseDebit - $expenseCredit; // Expenses increase with debits

        // Net income (retained earnings)
        return $totalRevenue - $totalExpenses;
    }
}

