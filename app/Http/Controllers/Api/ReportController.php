<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Account;
use App\Models\JournalEntryItem;
use App\Exports\TrialBalanceExport;
use App\Exports\BalanceSheetExport;
use App\Exports\IncomeStatementExport;
use App\Models\Setting;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Cache;
use Maatwebsite\Excel\Facades\Excel;
use Barryvdh\DomPDF\Facade\Pdf;

class ReportController extends Controller
{
    /**
     * Generate Trial Balance Report from journal entries.
     */
    public function trialBalance(Request $request): JsonResponse
    {
        $defaultDateTo = Setting::get('fiscal_year_end', now()->format('Y-m-d'));
        $defaultDateFrom = Setting::get('fiscal_year_start', null);
        $dateTo = $request->input('date_to', $defaultDateTo);
        $dateFrom = $request->input('date_from', $defaultDateFrom);

        $cacheKey = 'trial_balance_' . md5($dateTo . '_' . ($dateFrom ?? 'all'));
        $cached = Cache::get($cacheKey);
        if ($cached !== null) {
            return response()->json($cached);
        }

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
            $itemQuery = JournalEntryItem::query()
                ->join('journal_entries', 'journal_entries.id', '=', 'journal_entry_items.journal_entry_id')
                ->where('journal_entry_items.account_id', $account->id);

            if ($dateFrom) {
                $itemQuery->whereBetween('journal_entries.entry_date', [$dateFrom, $dateTo]);
            } else {
                $itemQuery->where('journal_entries.entry_date', '<=', $dateTo);
            }

            $debitTotal = (float) $itemQuery->sum('journal_entry_items.debit_amount');
            $creditTotal = (float) $itemQuery->sum('journal_entry_items.credit_amount');

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

        $response = [
            'report_type' => 'trial_balance',
            'date_from' => $dateFrom,
            'date_to' => $dateTo,
            'total_debit' => $totalDebit,
            'total_credit' => $totalCredit,
            'is_balanced' => abs($totalDebit - $totalCredit) < 0.01,
            'accounts' => $trialBalance,
            'generated_at' => now()->toDateTimeString(),
        ];

        Cache::put($cacheKey, $response, now()->addMinutes(5));

        return response()->json($response);
    }

    /**
     * Generate Balance Sheet Report using stored procedures.
     */
    public function balanceSheet(Request $request): JsonResponse
    {
        $dateTo = $request->input('date_to', Setting::get('fiscal_year_end', now()->format('Y-m-d')));

        // Create cache key
        $cacheKey = 'balance_sheet_' . md5($dateTo);

        // Try to get from cache (cache for 5 minutes)
        $cached = Cache::get($cacheKey);
        if ($cached !== null) {
            return response()->json($cached);
        }

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

        $response = [
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

        Cache::put($cacheKey, $response, now()->addMinutes(5));

        return response()->json($response);
    }

    /**
     * Generate Income Statement (Profit & Loss) Report using stored procedures.
     */
    public function incomeStatement(Request $request): JsonResponse
    {
        $dateFrom = $request->input('date_from', Setting::get('fiscal_year_start', now()->startOfYear()->format('Y-m-d')));
        $dateTo = $request->input('date_to', Setting::get('fiscal_year_end', now()->format('Y-m-d')));

        // Create cache key
        $cacheKey = 'income_statement_' . md5($dateFrom . '_' . $dateTo);

        // Try to get from cache (cache for 5 minutes)
        $cached = Cache::get($cacheKey);
        if ($cached !== null) {
            return response()->json($cached);
        }

        $revenues = $this->getAccountsByTypeWithDateRange(['revenue'], $dateFrom, $dateTo);
        $totalRevenue = array_sum(array_column($revenues, 'balance'));

        $expenses = $this->getAccountsByTypeWithDateRange(['expense'], $dateFrom, $dateTo);
        $totalExpenses = array_sum(array_column($expenses, 'balance'));

        $netIncome = $totalRevenue - $totalExpenses;

        $response = [
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

        Cache::put($cacheKey, $response, now()->addMinutes(5));

        return response()->json($response);
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
            // Calculate balance up to date
            $itemQuery = JournalEntryItem::query()
                ->join('journal_entries', 'journal_entries.id', '=', 'journal_entry_items.journal_entry_id')
                ->where('journal_entry_items.account_id', $account->id)
                ->where('journal_entries.entry_date', '<=', $dateTo);

            $debitTotal = (float) $itemQuery->sum('journal_entry_items.debit_amount');
            $creditTotal = (float) $itemQuery->sum('journal_entry_items.credit_amount');
            $openingBalance = (float) $account->opening_balance;

            // Calculate balance based on account type
            if (in_array($account->account_type, ['asset', 'expense'])) {
                $balance = $openingBalance + $debitTotal - $creditTotal;
            } else {
                $balance = $openingBalance + $creditTotal - $debitTotal;
            }

            // Only include accounts with non-zero balance or transactions
            if ($balance != 0 || $debitTotal > 0 || $creditTotal > 0) {
                $result[] = [
                    'account_code' => $account->account_code,
                    'account_name' => $account->account_name,
                    'account_type' => $account->account_type,
                    'opening_balance' => $openingBalance,
                    'debit_total' => $debitTotal,
                    'credit_total' => $creditTotal,
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
            // Calculate balance for date range only
            $itemQuery = JournalEntryItem::query()
                ->join('journal_entries', 'journal_entries.id', '=', 'journal_entry_items.journal_entry_id')
                ->where('journal_entry_items.account_id', $account->id)
                ->whereBetween('journal_entries.entry_date', [$dateFrom, $dateTo]);

            $debitTotal = (float) $itemQuery->sum('journal_entry_items.debit_amount');
            $creditTotal = (float) $itemQuery->sum('journal_entry_items.credit_amount');

            // For income statement, we typically don't include opening balance
            // Revenue and expenses are period-specific
            if (in_array($account->account_type, ['revenue'])) {
                // Revenue increases with credits
                $balance = $creditTotal - $debitTotal;
            } else {
                // Expenses increase with debits
                $balance = $debitTotal - $creditTotal;
            }

            // Only include accounts with transactions
            if ($debitTotal > 0 || $creditTotal > 0) {
                $result[] = [
                    'account_code' => $account->account_code,
                    'account_name' => $account->account_name,
                    'account_type' => $account->account_type,
                    'debit_total' => $debitTotal,
                    'credit_total' => $creditTotal,
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

        $revenueQuery = JournalEntryItem::query()
            ->join('journal_entries', 'journal_entries.id', '=', 'journal_entry_items.journal_entry_id')
            ->whereIn('journal_entry_items.account_id', $revenueAccounts)
            ->where('journal_entries.entry_date', '<=', $dateTo);

        $revenueDebit = (float) $revenueQuery->sum('journal_entry_items.debit_amount');
        $revenueCredit = (float) $revenueQuery->sum('journal_entry_items.credit_amount');
        $totalRevenue = $revenueCredit - $revenueDebit; // Revenue increases with credits

        // Get all expense accounts
        $expenseAccounts = Account::where('account_type', 'expense')
            ->where('is_active', true)
            ->pluck('id');

        $expenseQuery = JournalEntryItem::query()
            ->join('journal_entries', 'journal_entries.id', '=', 'journal_entry_items.journal_entry_id')
            ->whereIn('journal_entry_items.account_id', $expenseAccounts)
            ->where('journal_entries.entry_date', '<=', $dateTo);

        $expenseDebit = (float) $expenseQuery->sum('journal_entry_items.debit_amount');
        $expenseCredit = (float) $expenseQuery->sum('journal_entry_items.credit_amount');
        $totalExpenses = $expenseDebit - $expenseCredit; // Expenses increase with debits

        // Net income (retained earnings)
        return $totalRevenue - $totalExpenses;
    }

    /**
     * Export Trial Balance to Excel.
     */
    public function exportTrialBalanceExcel(Request $request)
    {
        $response = $this->trialBalance($request);
        $data = json_decode($response->getContent(), true);

        $exportData = [];
        foreach ($data['trial_balance'] as $entry) {
            $exportData[] = [
                $entry['account_code'],
                $entry['account_name'],
                $entry['account_type'],
                $entry['debit_balance'],
                $entry['credit_balance'],
            ];
        }

        $summary = [
            'date' => $data['date'],
            'total_debit' => $data['total_debit'],
            'total_credit' => $data['total_credit'],
        ];

        $filename = 'trial_balance_' . now()->format('Y-m-d') . '.xlsx';
        return Excel::download(new TrialBalanceExport($exportData, $summary), $filename);
    }

    /**
     * Export Trial Balance to PDF.
     */
    public function exportTrialBalancePdf(Request $request)
    {
        $response = $this->trialBalance($request);
        $data = json_decode($response->getContent(), true);

        $pdf = Pdf::loadView('exports.trial-balance', $data);
        $filename = 'trial_balance_' . now()->format('Y-m-d') . '.pdf';
        return $pdf->download($filename);
    }

    /**
     * Export Balance Sheet to Excel.
     */
    public function exportBalanceSheetExcel(Request $request)
    {
        $response = $this->balanceSheet($request);
        $data = json_decode($response->getContent(), true);

        $exportData = [];
        
        // Assets
        foreach ($data['assets']['accounts'] as $entry) {
            $exportData[] = [
                $entry['account_code'],
                $entry['account_name'],
                $entry['balance'],
            ];
        }
        
        // Liabilities
        foreach ($data['liabilities']['accounts'] as $entry) {
            $exportData[] = [
                $entry['account_code'],
                $entry['account_name'],
                $entry['balance'],
            ];
        }
        
        // Equity
        foreach ($data['equity']['accounts'] as $entry) {
            $exportData[] = [
                $entry['account_code'],
                $entry['account_name'],
                $entry['balance'],
            ];
        }

        $summary = [
            'date' => $data['date'],
            'total' => $data['total_assets'],
        ];

        $filename = 'balance_sheet_' . now()->format('Y-m-d') . '.xlsx';
        return Excel::download(new BalanceSheetExport($exportData, $summary), $filename);
    }

    /**
     * Export Balance Sheet to PDF.
     */
    public function exportBalanceSheetPdf(Request $request)
    {
        $response = $this->balanceSheet($request);
        $data = json_decode($response->getContent(), true);

        $pdf = Pdf::loadView('exports.balance-sheet', $data);
        $filename = 'balance_sheet_' . now()->format('Y-m-d') . '.pdf';
        return $pdf->download($filename);
    }

    /**
     * Export Income Statement to Excel.
     */
    public function exportIncomeStatementExcel(Request $request)
    {
        $response = $this->incomeStatement($request);
        $data = json_decode($response->getContent(), true);

        $exportData = [];
        
        // Revenue
        foreach ($data['revenue']['accounts'] as $entry) {
            $exportData[] = [
                $entry['account_code'],
                $entry['account_name'],
                $entry['balance'],
            ];
        }
        
        // Expenses
        foreach ($data['expenses']['accounts'] as $entry) {
            $exportData[] = [
                $entry['account_code'],
                $entry['account_name'],
                $entry['balance'],
            ];
        }

        $summary = [
            'date_from' => $data['date_from'],
            'date_to' => $data['date_to'],
            'total' => $data['revenue']['total'] - $data['expenses']['total'],
            'net_income' => $data['net_income'],
        ];

        $filename = 'income_statement_' . now()->format('Y-m-d') . '.xlsx';
        return Excel::download(new IncomeStatementExport($exportData, $summary), $filename);
    }

    /**
     * Export Income Statement to PDF.
     */
    public function exportIncomeStatementPdf(Request $request)
    {
        $response = $this->incomeStatement($request);
        $data = json_decode($response->getContent(), true);

        $pdf = Pdf::loadView('exports.income-statement', $data);
        $filename = 'income_statement_' . now()->format('Y-m-d') . '.pdf';
        return $pdf->download($filename);
    }
}
