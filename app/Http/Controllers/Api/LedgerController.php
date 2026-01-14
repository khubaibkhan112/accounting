<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Account;
use App\Models\Transaction;
use App\Exports\LedgerExport;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;
use Maatwebsite\Excel\Facades\Excel;
use Barryvdh\DomPDF\Facade\Pdf;

class LedgerController extends Controller
{
    /**
     * Display ledger for a specific account.
     */
    public function index(Request $request): JsonResponse
    {
        return $this->getLedgerData($request);
    }

    /**
     * Display ledger for a specific account (alternative route with account in URL).
     */
    public function show(Account $account, Request $request): JsonResponse
    {
        // Build query for transactions
        $query = Transaction::select([
            'id',
            'date',
            'account_id',
            'description',
            'debit_amount',
            'credit_amount',
            'reference_number',
            'running_balance',
            'created_at',
        ])->where('account_id', $account->id)
          ->with([
              'account:id,account_code,account_name,account_type,opening_balance',
          ]);

        // Filter by date range
        if ($request->has('date_from') && $request->date_from) {
            $query->where('date', '>=', $request->date_from);
        }

        if ($request->has('date_to') && $request->date_to) {
            $query->where('date', '<=', $request->date_to);
        }

        // Search functionality
        if ($request->has('search') && $request->search) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('description', 'like', "%{$search}%")
                  ->orWhere('reference_number', 'like', "%{$search}%");
            });
        }

        // Sorting - default by date asc, then id asc (chronological order for ledger)
        $sortBy = $request->get('sort_by', 'date');
        $sortOrder = $request->get('sort_order', 'asc');
        $validSortFields = ['date', 'debit_amount', 'credit_amount', 'reference_number', 'created_at'];
        
        if (in_array($sortBy, $validSortFields)) {
            $query->orderBy($sortBy, $sortOrder);
        }
        
        // Secondary sort by id to ensure consistent ordering
        if ($sortBy !== 'id') {
            $query->orderBy('id', 'asc');
        }

        // Get transactions
        $transactions = $query->get();

        // Calculate opening balance
        $openingBalance = (float) $account->opening_balance;
        
        // Get opening balance date (earliest transaction date or date_from)
        $dateFrom = $request->date_from ?? ($transactions->min('date') ? $transactions->min('date')->format('Y-m-d') : null);
        
        // If date_from is specified, calculate opening balance up to that date
        if ($dateFrom) {
            $openingBalance = $this->calculateOpeningBalanceUpToDate($account->id, $dateFrom);
        }

        // Prepare ledger data
        $ledgerData = [];
        $runningBalance = $openingBalance;

        // Add opening balance entry if there are transactions
        if ($transactions->isNotEmpty() || $openingBalance != 0) {
            $ledgerData[] = [
                'id' => null,
                'date' => $dateFrom ?? $account->created_at->format('Y-m-d'),
                'description' => 'Opening Balance',
                'reference_number' => null,
                'debit_amount' => 0,
                'credit_amount' => 0,
                'balance' => $runningBalance,
                'type' => 'opening',
            ];
        }

        // Add transactions with running balance
        foreach ($transactions as $transaction) {
            // Calculate running balance
            $runningBalance = (float) $transaction->running_balance;
            
            $ledgerData[] = [
                'id' => $transaction->id,
                'date' => $transaction->date->format('Y-m-d'),
                'description' => $transaction->description,
                'reference_number' => $transaction->reference_number,
                'debit_amount' => (float) $transaction->debit_amount,
                'credit_amount' => (float) $transaction->credit_amount,
                'balance' => $runningBalance,
                'type' => 'transaction',
                'created_at' => $transaction->created_at->format('Y-m-d H:i:s'),
            ];
        }

        // Calculate totals
        $totalDebit = $transactions->sum('debit_amount');
        $totalCredit = $transactions->sum('credit_amount');

        // Prepare response
        $response = [
            'account' => [
                'id' => $account->id,
                'account_code' => $account->account_code,
                'account_name' => $account->account_name,
                'account_type' => $account->account_type,
                'opening_balance' => $openingBalance,
                'current_balance' => $account->current_balance,
            ],
            'date_from' => $request->date_from,
            'date_to' => $request->date_to,
            'opening_balance' => $openingBalance,
            'closing_balance' => $runningBalance,
            'total_debit' => (float) $totalDebit,
            'total_credit' => (float) $totalCredit,
            'transactions_count' => $transactions->count(),
            'ledger' => $ledgerData,
        ];

        return response()->json($response);
    }

    /**
     * Calculate opening balance up to a specific date.
     */
    private function calculateOpeningBalanceUpToDate(int $accountId, string $date): float
    {
        $account = Account::find($accountId);
        if (!$account) {
            return 0;
        }

        $openingBalance = (float) $account->opening_balance;

        // Get all transactions before the date
        $previousTransactions = Transaction::where('account_id', $accountId)
            ->where('date', '<', $date)
            ->orderBy('date', 'asc')
            ->orderBy('id', 'asc')
            ->get();

        // Calculate balance up to the date
        $balance = $openingBalance;
        foreach ($previousTransactions as $transaction) {
            $balance = $this->adjustBalance($balance, $account->account_type, $transaction->debit_amount, $transaction->credit_amount);
        }

        return $balance;
    }

    /**
     * Adjust balance based on account type and transaction amounts.
     */
    private function adjustBalance(float $currentBalance, string $accountType, float $debit, float $credit): float
    {
        // For assets and expenses: balance increases with debits, decreases with credits
        // For liabilities, equity, and revenue: balance increases with credits, decreases with debits
        if (in_array($accountType, ['asset', 'expense'])) {
            return $currentBalance + $debit - $credit;
        } else {
            return $currentBalance + $credit - $debit;
        }
    }

    /**
     * Export ledger to Excel.
     */
    public function exportExcel(Request $request)
    {
        // Get ledger data using the same logic as index
        $response = $this->getLedgerData($request);
        $data = json_decode($response->getContent(), true);

        // Prepare export data
        $exportData = [];
        foreach ($data['ledger'] as $entry) {
            $exportData[] = [
                $entry['date'],
                $entry['description'],
                $entry['reference_number'] ?? '',
                $entry['debit_amount'],
                $entry['credit_amount'],
                $entry['balance'],
            ];
        }

        $summary = [
            'date_from' => $data['date_from'],
            'date_to' => $data['date_to'],
            'opening_balance' => $data['opening_balance'],
            'closing_balance' => $data['closing_balance'],
            'total_debit' => $data['total_debit'],
            'total_credit' => $data['total_credit'],
        ];

        $account = $data['account'];
        $filename = 'ledger_' . $account['account_code'] . '_' . now()->format('Y-m-d') . '.xlsx';

        return Excel::download(new LedgerExport($exportData, $account, $summary), $filename);
    }

    /**
     * Export ledger to PDF.
     */
    public function exportPdf(Request $request)
    {
        $response = $this->getLedgerData($request);
        $data = json_decode($response->getContent(), true);

        $pdf = Pdf::loadView('exports.ledger', $data);
        $filename = 'ledger_' . $data['account']['account_code'] . '_' . now()->format('Y-m-d') . '.pdf';

        return $pdf->download($filename);
    }

    /**
     * Get ledger data (shared logic for index, exportExcel, exportPdf).
     */
    private function getLedgerData(Request $request): JsonResponse
    {
        // Validate account_id is required
        if (!$request->has('account_id') || !$request->account_id) {
            return response()->json([
                'message' => 'Account ID is required.',
            ], 422);
        }

        $accountId = $request->account_id;
        $account = Account::find($accountId);

        if (!$account) {
            return response()->json([
                'message' => 'Account not found.',
            ], 404);
        }

        // Build query for transactions
        $query = Transaction::select([
            'id',
            'date',
            'account_id',
            'description',
            'debit_amount',
            'credit_amount',
            'reference_number',
            'running_balance',
            'created_at',
        ])->where('account_id', $accountId)
          ->with([
              'account:id,account_code,account_name,account_type,opening_balance',
          ]);

        // Filter by date range
        if ($request->has('date_from') && $request->date_from) {
            $query->where('date', '>=', $request->date_from);
        }

        if ($request->has('date_to') && $request->date_to) {
            $query->where('date', '<=', $request->date_to);
        }

        // Search functionality
        if ($request->has('search') && $request->search) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('description', 'like', "%{$search}%")
                  ->orWhere('reference_number', 'like', "%{$search}%");
            });
        }

        // Sorting
        $sortBy = $request->get('sort_by', 'date');
        $sortOrder = $request->get('sort_order', 'asc');
        $validSortFields = ['date', 'debit_amount', 'credit_amount', 'reference_number', 'created_at'];
        
        if (in_array($sortBy, $validSortFields)) {
            $query->orderBy($sortBy, $sortOrder);
        }
        
        if ($sortBy !== 'id') {
            $query->orderBy('id', 'asc');
        }

        // Get transactions
        $transactions = $query->get();

        // Calculate opening balance
        $openingBalance = (float) $account->opening_balance;
        
        $dateFrom = $request->date_from ?? ($transactions->min('date') ? $transactions->min('date')->format('Y-m-d') : null);
        
        if ($dateFrom) {
            $openingBalance = $this->calculateOpeningBalanceUpToDate($accountId, $dateFrom);
        }

        // Prepare ledger data
        $ledgerData = [];
        $runningBalance = $openingBalance;

        if ($transactions->isNotEmpty() || $openingBalance != 0) {
            $ledgerData[] = [
                'id' => null,
                'date' => $dateFrom ?? $account->created_at->format('Y-m-d'),
                'description' => 'Opening Balance',
                'reference_number' => null,
                'debit_amount' => 0,
                'credit_amount' => 0,
                'balance' => $runningBalance,
                'type' => 'opening',
            ];
        }

        foreach ($transactions as $transaction) {
            $runningBalance = (float) $transaction->running_balance;
            
            $ledgerData[] = [
                'id' => $transaction->id,
                'date' => $transaction->date->format('Y-m-d'),
                'description' => $transaction->description,
                'reference_number' => $transaction->reference_number,
                'debit_amount' => (float) $transaction->debit_amount,
                'credit_amount' => (float) $transaction->credit_amount,
                'balance' => $runningBalance,
                'type' => 'transaction',
                'created_at' => $transaction->created_at->format('Y-m-d H:i:s'),
            ];
        }

        // Calculate totals
        $totalDebit = $transactions->sum('debit_amount');
        $totalCredit = $transactions->sum('credit_amount');

        // Prepare response
        $response = [
            'account' => [
                'id' => $account->id,
                'account_code' => $account->account_code,
                'account_name' => $account->account_name,
                'account_type' => $account->account_type,
                'opening_balance' => $openingBalance,
                'current_balance' => $account->current_balance,
            ],
            'date_from' => $request->date_from,
            'date_to' => $request->date_to,
            'opening_balance' => $openingBalance,
            'closing_balance' => $runningBalance,
            'total_debit' => (float) $totalDebit,
            'total_credit' => (float) $totalCredit,
            'transactions_count' => $transactions->count(),
            'ledger' => $ledgerData,
        ];

        return response()->json($response);
    }
}
