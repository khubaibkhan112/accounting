<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Account;
use App\Models\Transaction;
use App\Exports\TrialBalanceExport;
use App\Exports\BalanceSheetExport;
use App\Exports\IncomeStatementExport;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Cache;
use Maatwebsite\Excel\Facades\Excel;
use Barryvdh\DomPDF\Facade\Pdf;

class ReportController extends Controller
{
    /**
     * Generate Trial Balance Report using stored procedure.
     */
    public function trialBalance(Request $request): JsonResponse
    {
        $dateTo = $request->input('date_to', now()->format('Y-m-d'));
        $dateFrom = $request->input('date_from', null);

        // Create cache key
        $cacheKey = 'trial_balance_' . md5($dateTo . '_' . ($dateFrom ?? 'all'));

        // Try to get from cache (cache for 5 minutes)
        $cached = Cache::get($cacheKey);
        if ($cached !== null) {
            return response()->json($cached);
        }

        try {
            // Use stored procedure for optimized performance
            $results = DB::select('CALL sp_get_trial_balance(?, ?)', [
                $dateFrom,
                $dateTo,
            ]);

            $trialBalance = [];
            $totalDebit = 0;
            $totalCredit = 0;

            foreach ($results as $row) {
                // Calculate debit and credit balances based on account type
                $currentBalance = (float) $row->current_balance;
                
                if (in_array($row->account_type, ['asset', 'expense'])) {
                    $debitBalance = $currentBalance >= 0 ? $currentBalance : 0;
                    $creditBalance = $currentBalance < 0 ? abs($currentBalance) : 0;
                } else {
                    $debitBalance = $currentBalance < 0 ? abs($currentBalance) : 0;
                    $creditBalance = $currentBalance >= 0 ? $currentBalance : 0;
                }

                $trialBalance[] = [
                    'account_code' => $row->account_code,
                    'account_name' => $row->account_name,
                    'account_type' => $row->account_type,
                    'debit_total' => (float) $row->total_debit,
                    'credit_total' => (float) $row->total_credit,
                    'debit_balance' => $debitBalance,
                    'credit_balance' => $creditBalance,
                ];

                $totalDebit += $debitBalance;
                $totalCredit += $creditBalance;
            }

            return response()->json([
                'report_type' => 'trial_balance',
                'date_from' => $dateFrom,
                'date_to' => $dateTo,
                'total_debit' => $totalDebit,
                'total_credit' => $totalCredit,
                'is_balanced' => abs($totalDebit - $totalCredit) < 0.01,
                'accounts' => $trialBalance,
                'generated_at' => now()->toDateTimeString(),
            ]);
        } catch (\Exception $e) {
            // Fallback to original method if stored procedure fails
            \Log::warning('Stored procedure failed, using fallback method: ' . $e->getMessage());
            
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

            return response()->json([
                'report_type' => 'trial_balance',
                'date_from' => $dateFrom,
                'date_to' => $dateTo,
                'total_debit' => $totalDebit,
                'total_credit' => $totalCredit,
                'is_balanced' => abs($totalDebit - $totalCredit) < 0.01,
                'accounts' => $trialBalance,
                'generated_at' => now()->toDateTimeString(),
            ]);
        }
    }

    /**
     * Generate Balance Sheet Report using stored procedures.
     */
    public function balanceSheet(Request $request): JsonResponse
    {
        $dateTo = $request->input('date_to', now()->format('Y-m-d'));

        // Create cache key
        $cacheKey = 'balance_sheet_' . md5($dateTo);

        // Try to get from cache (cache for 5 minutes)
        $cached = Cache::get($cacheKey);
        if ($cached !== null) {
            return response()->json($cached);
        }

        try {
            // Use stored procedures for optimized performance
            $assets = DB::select('CALL sp_get_balance_sheet_assets(?)', [$dateTo]);
            $liabilities = DB::select('CALL sp_get_balance_sheet_liabilities(?)', [$dateTo]);
            $equity = DB::select('CALL sp_get_balance_sheet_equity(?)', [$dateTo]);
            $retainedEarningsResult = DB::select('CALL sp_get_retained_earnings(?)', [$dateTo]);

            // Format assets
            $assetsFormatted = array_map(function ($row) {
                return [
                    'account_code' => $row->account_code,
                    'account_name' => $row->account_name,
                    'opening_balance' => (float) $row->opening_balance,
                    'debit_total' => (float) $row->total_debit,
                    'credit_total' => (float) $row->total_credit,
                    'balance' => (float) $row->balance,
                ];
            }, $assets);

            // Format liabilities
            $liabilitiesFormatted = array_map(function ($row) {
                return [
                    'account_code' => $row->account_code,
                    'account_name' => $row->account_name,
                    'opening_balance' => (float) $row->opening_balance,
                    'debit_total' => (float) $row->total_debit,
                    'credit_total' => (float) $row->total_credit,
                    'balance' => (float) $row->balance,
                ];
            }, $liabilities);

            // Format equity
            $equityFormatted = array_map(function ($row) {
                return [
                    'account_code' => $row->account_code,
                    'account_name' => $row->account_name,
                    'opening_balance' => (float) $row->opening_balance,
                    'debit_total' => (float) $row->total_debit,
                    'credit_total' => (float) $row->total_credit,
                    'balance' => (float) $row->balance,
                ];
            }, $equity);

            // Get retained earnings
            $retainedEarnings = isset($retainedEarningsResult[0]) ? (float) $retainedEarningsResult[0]->retained_earnings : 0;

            // Calculate totals
            $totalAssets = array_sum(array_column($assetsFormatted, 'balance'));
            $totalLiabilities = array_sum(array_column($liabilitiesFormatted, 'balance'));
            $totalEquity = array_sum(array_column($equityFormatted, 'balance')) + $retainedEarnings;

            // Balance sheet equation: Assets = Liabilities + Equity
            $totalLiabilitiesAndEquity = $totalLiabilities + $totalEquity;
            $isBalanced = abs($totalAssets - $totalLiabilitiesAndEquity) < 0.01;

            return response()->json([
                'report_type' => 'balance_sheet',
                'date' => $dateTo,
                'assets' => [
                    'accounts' => $assetsFormatted,
                    'total' => $totalAssets,
                ],
                'liabilities' => [
                    'accounts' => $liabilitiesFormatted,
                    'total' => $totalLiabilities,
                ],
                'equity' => [
                    'accounts' => $equityFormatted,
                    'retained_earnings' => $retainedEarnings,
                    'total' => $totalEquity,
                ],
                'total_assets' => $totalAssets,
                'total_liabilities_and_equity' => $totalLiabilitiesAndEquity,
                'is_balanced' => $isBalanced,
                'generated_at' => now()->toDateTimeString(),
            ];

            // Cache the result for 5 minutes
            Cache::put($cacheKey, $response, now()->addMinutes(5));

            return response()->json($response);
        } catch (\Exception $e) {
            // Fallback to original method if stored procedure fails
            \Log::warning('Stored procedure failed, using fallback method: ' . $e->getMessage());

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

            return response()->json([
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
            ]);
        }
    }

    /**
     * Generate Income Statement (Profit & Loss) Report using stored procedures.
     */
    public function incomeStatement(Request $request): JsonResponse
    {
        $dateFrom = $request->input('date_from', now()->startOfYear()->format('Y-m-d'));
        $dateTo = $request->input('date_to', now()->format('Y-m-d'));

        // Create cache key
        $cacheKey = 'income_statement_' . md5($dateFrom . '_' . $dateTo);

        // Try to get from cache (cache for 5 minutes)
        $cached = Cache::get($cacheKey);
        if ($cached !== null) {
            return response()->json($cached);
        }

        try {
            // Use stored procedures for optimized performance
            $revenues = DB::select('CALL sp_get_income_statement_revenue(?, ?)', [$dateFrom, $dateTo]);
            $expenses = DB::select('CALL sp_get_income_statement_expenses(?, ?)', [$dateFrom, $dateTo]);

            // Format revenue accounts
            $revenuesFormatted = array_map(function ($row) {
                return [
                    'account_code' => $row->account_code,
                    'account_name' => $row->account_name,
                    'debit_total' => (float) $row->total_debit,
                    'credit_total' => (float) $row->total_credit,
                    'balance' => (float) $row->balance,
                ];
            }, $revenues);

            // Format expense accounts
            $expensesFormatted = array_map(function ($row) {
                return [
                    'account_code' => $row->account_code,
                    'account_name' => $row->account_name,
                    'debit_total' => (float) $row->total_debit,
                    'credit_total' => (float) $row->total_credit,
                    'balance' => (float) $row->balance,
                ];
            }, $expenses);

            // Calculate totals
            $totalRevenue = array_sum(array_column($revenuesFormatted, 'balance'));
            $totalExpenses = array_sum(array_column($expensesFormatted, 'balance'));

            // Calculate net income (revenue - expenses)
            $netIncome = $totalRevenue - $totalExpenses;

            $response = [
                'report_type' => 'income_statement',
                'date_from' => $dateFrom,
                'date_to' => $dateTo,
                'revenue' => [
                    'accounts' => $revenuesFormatted,
                    'total' => $totalRevenue,
                ],
                'expenses' => [
                    'accounts' => $expensesFormatted,
                    'total' => $totalExpenses,
                ],
                'net_income' => $netIncome,
                'is_profit' => $netIncome > 0,
                'generated_at' => now()->toDateTimeString(),
            ];

            // Cache the result for 5 minutes
            Cache::put($cacheKey, $response, now()->addMinutes(5));

            return response()->json($response);
        } catch (\Exception $e) {
            // Fallback to original method if stored procedure fails
            \Log::warning('Stored procedure failed, using fallback method: ' . $e->getMessage());

            // Get revenue accounts
            $revenues = $this->getAccountsByTypeWithDateRange(['revenue'], $dateFrom, $dateTo);
            $totalRevenue = array_sum(array_column($revenues, 'balance'));

            // Get expense accounts
            $expenses = $this->getAccountsByTypeWithDateRange(['expense'], $dateFrom, $dateTo);
            $totalExpenses = array_sum(array_column($expenses, 'balance'));

            // Calculate net income (revenue - expenses)
            $netIncome = $totalRevenue - $totalExpenses;

            return response()->json([
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
            ]);
        }
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
            $transactionQuery = Transaction::where('account_id', $account->id)
                ->where('date', '<=', $dateTo);

            $debitTotal = (float) $transactionQuery->sum('debit_amount');
            $creditTotal = (float) $transactionQuery->sum('credit_amount');
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
            $transactionQuery = Transaction::where('account_id', $account->id)
                ->whereBetween('date', [$dateFrom, $dateTo]);

            $debitTotal = (float) $transactionQuery->sum('debit_amount');
            $creditTotal = (float) $transactionQuery->sum('credit_amount');

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
