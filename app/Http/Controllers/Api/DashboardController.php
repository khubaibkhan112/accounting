<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Account;
use App\Models\Transaction;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;

class DashboardController extends Controller
{
    /**
     * Get dashboard statistics and recent transactions.
     */
    public function index(): JsonResponse
    {
        try {
            // Get total accounts count
            $totalAccounts = Account::where('is_active', true)->count();

            // Get total transactions count
            $totalTransactions = Transaction::count();

            // Get total debit and credit amounts
            $totals = Transaction::select(
                DB::raw('SUM(debit_amount) as total_debit'),
                DB::raw('SUM(credit_amount) as total_credit')
            )->first();

            $totalDebit = (float) ($totals->total_debit ?? 0);
            $totalCredit = (float) ($totals->total_credit ?? 0);

            // Get recent transactions (last 10)
            $recentTransactions = Transaction::with('account')
                ->orderBy('date', 'desc')
                ->orderBy('created_at', 'desc')
                ->limit(10)
                ->get()
                ->map(function ($transaction) {
                    return [
                        'id' => $transaction->id,
                        'date' => $transaction->date,
                        'account' => $transaction->account->account_name ?? 'N/A',
                        'account_code' => $transaction->account->account_code ?? 'N/A',
                        'description' => $transaction->description,
                        'debit' => $transaction->debit_amount > 0 ? number_format($transaction->debit_amount, 2) : null,
                        'credit' => $transaction->credit_amount > 0 ? number_format($transaction->credit_amount, 2) : null,
                        'reference_number' => $transaction->reference_number,
                    ];
                });

            return response()->json([
                'stats' => [
                    'totalAccounts' => $totalAccounts,
                    'totalTransactions' => $totalTransactions,
                    'totalDebit' => $totalDebit,
                    'totalCredit' => $totalCredit,
                ],
                'recentTransactions' => $recentTransactions,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Failed to load dashboard data',
                'error' => $e->getMessage(),
            ], 500);
        }
    }
}
