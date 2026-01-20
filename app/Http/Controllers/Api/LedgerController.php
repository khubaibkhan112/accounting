<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Account;
use App\Models\JournalEntryItem;
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
        // Build query for journal entry items
        $query = JournalEntryItem::query()
            ->select([
                'journal_entry_items.id',
                'journal_entry_items.account_id',
                'journal_entry_items.description',
                'journal_entry_items.debit_amount',
                'journal_entry_items.credit_amount',
                'journal_entry_items.created_at',
                'journal_entries.entry_date as entry_date',
                'journal_entries.reference_number as reference_number',
                'journal_entries.description as entry_description',
            ])
            ->join('journal_entries', 'journal_entries.id', '=', 'journal_entry_items.journal_entry_id')
            ->where('journal_entry_items.account_id', $account->id);

        // Filter by date range
        if ($request->has('date_from') && $request->date_from) {
            $query->where('journal_entries.entry_date', '>=', $request->date_from);
        }

        if ($request->has('date_to') && $request->date_to) {
            $query->where('journal_entries.entry_date', '<=', $request->date_to);
        }

        // Search functionality
        if ($request->has('search') && $request->search) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('journal_entry_items.description', 'like', "%{$search}%")
                  ->orWhere('journal_entries.description', 'like', "%{$search}%")
                  ->orWhere('journal_entries.reference_number', 'like', "%{$search}%");
            });
        }

        // Sorting - default by date asc, then id asc (chronological order for ledger)
        $sortBy = $request->get('sort_by', 'date');
        $sortOrder = $request->get('sort_order', 'asc');
        $validSortFields = ['entry_date', 'debit_amount', 'credit_amount', 'reference_number', 'created_at'];
        
        if (in_array($sortBy, $validSortFields)) {
            if ($sortBy === 'entry_date') {
                $query->orderBy('journal_entries.entry_date', $sortOrder);
            } elseif ($sortBy === 'reference_number') {
                $query->orderBy('journal_entries.reference_number', $sortOrder);
            } else {
                $query->orderBy("journal_entry_items.$sortBy", $sortOrder);
            }
        }
        
        // Secondary sort by id to ensure consistent ordering
        if ($sortBy !== 'id') {
            $query->orderBy('journal_entry_items.id', 'asc');
        }

        // Get transactions
        $items = $query->get();

        // Calculate opening balance
        $openingBalance = (float) $account->opening_balance;
        
        // Get opening balance date (earliest transaction date or date_from)
        $dateFrom = $request->date_from ?? ($items->min('entry_date') ? $items->min('entry_date') : null);
        
        // If date_from is specified, calculate opening balance up to that date
        if ($dateFrom) {
            $openingBalance = $this->calculateOpeningBalanceUpToDate($account->id, $dateFrom);
        }

        // Prepare ledger data
        $ledgerData = [];
        $runningBalance = $openingBalance;

        // Add opening balance entry if there are transactions
        if ($items->isNotEmpty() || $openingBalance != 0) {
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
        foreach ($items as $item) {
            $debit = (float) $item->debit_amount;
            $credit = (float) $item->credit_amount;
            $description = $item->description ?: $item->entry_description;
            $runningBalance = $this->adjustBalance($runningBalance, $account->account_type, $debit, $credit);
            
            $ledgerData[] = [
                'id' => $item->id,
                'date' => $item->entry_date,
                'description' => $description,
                'reference_number' => $item->reference_number,
                'debit_amount' => $debit,
                'credit_amount' => $credit,
                'balance' => $runningBalance,
                'type' => 'journal',
                'created_at' => $item->created_at->format('Y-m-d H:i:s'),
            ];
        }

        // Calculate totals
        $totalDebit = $items->sum('debit_amount');
        $totalCredit = $items->sum('credit_amount');

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
            'transactions_count' => $items->count(),
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

        // Get all journal entry items before the date
        $previousItems = JournalEntryItem::query()
            ->join('journal_entries', 'journal_entries.id', '=', 'journal_entry_items.journal_entry_id')
            ->where('journal_entry_items.account_id', $accountId)
            ->where('journal_entries.entry_date', '<', $date)
            ->orderBy('journal_entries.entry_date', 'asc')
            ->orderBy('journal_entry_items.id', 'asc')
            ->get(['journal_entry_items.debit_amount', 'journal_entry_items.credit_amount']);

        // Calculate balance up to the date
        $balance = $openingBalance;
        foreach ($previousItems as $item) {
            $balance = $this->adjustBalance($balance, $account->account_type, (float) $item->debit_amount, (float) $item->credit_amount);
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

        // Build query for journal entry items
        $query = JournalEntryItem::query()
            ->select([
                'journal_entry_items.id',
                'journal_entry_items.account_id',
                'journal_entry_items.description',
                'journal_entry_items.debit_amount',
                'journal_entry_items.credit_amount',
                'journal_entry_items.created_at',
                'journal_entries.entry_date as entry_date',
                'journal_entries.reference_number as reference_number',
                'journal_entries.description as entry_description',
            ])
            ->join('journal_entries', 'journal_entries.id', '=', 'journal_entry_items.journal_entry_id')
            ->where('journal_entry_items.account_id', $accountId);

        // Filter by date range
        if ($request->has('date_from') && $request->date_from) {
            $query->where('journal_entries.entry_date', '>=', $request->date_from);
        }

        if ($request->has('date_to') && $request->date_to) {
            $query->where('journal_entries.entry_date', '<=', $request->date_to);
        }

        // Search functionality
        if ($request->has('search') && $request->search) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('journal_entry_items.description', 'like', "%{$search}%")
                  ->orWhere('journal_entries.description', 'like', "%{$search}%")
                  ->orWhere('journal_entries.reference_number', 'like', "%{$search}%");
            });
        }

        // Sorting
        $sortBy = $request->get('sort_by', 'date');
        $sortOrder = $request->get('sort_order', 'asc');
        $validSortFields = ['entry_date', 'debit_amount', 'credit_amount', 'reference_number', 'created_at'];
        
        if (in_array($sortBy, $validSortFields)) {
            if ($sortBy === 'entry_date') {
                $query->orderBy('journal_entries.entry_date', $sortOrder);
            } elseif ($sortBy === 'reference_number') {
                $query->orderBy('journal_entries.reference_number', $sortOrder);
            } else {
                $query->orderBy("journal_entry_items.$sortBy", $sortOrder);
            }
        }
        
        if ($sortBy !== 'id') {
            $query->orderBy('journal_entry_items.id', 'asc');
        }

        // Get transactions
        $items = $query->get();

        // Calculate opening balance
        $openingBalance = (float) $account->opening_balance;
        
        $dateFrom = $request->date_from ?? ($items->min('entry_date') ? $items->min('entry_date') : null);
        
        if ($dateFrom) {
            $openingBalance = $this->calculateOpeningBalanceUpToDate($accountId, $dateFrom);
        }

        // Prepare ledger data
        $ledgerData = [];
        $runningBalance = $openingBalance;

        if ($items->isNotEmpty() || $openingBalance != 0) {
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

        foreach ($items as $item) {
            $debit = (float) $item->debit_amount;
            $credit = (float) $item->credit_amount;
            $description = $item->description ?: $item->entry_description;
            $runningBalance = $this->adjustBalance($runningBalance, $account->account_type, $debit, $credit);
            
            $ledgerData[] = [
                'id' => $item->id,
                'date' => $item->entry_date,
                'description' => $description,
                'reference_number' => $item->reference_number,
                'debit_amount' => $debit,
                'credit_amount' => $credit,
                'balance' => $runningBalance,
                'type' => 'journal',
                'created_at' => $item->created_at->format('Y-m-d H:i:s'),
            ];
        }

        // Calculate totals
        $totalDebit = $items->sum('debit_amount');
        $totalCredit = $items->sum('credit_amount');

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
            'transactions_count' => $items->count(),
            'ledger' => $ledgerData,
        ];

        return response()->json($response);
    }
}
