<?php

namespace App\Traits;

use App\Models\JournalEntryItem;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;
use Carbon\Carbon;

trait LedgerTrait
{
    /**
     * Get ledger data for a specific entity.
     *
     * @param Model $entity The entity (Account, Customer, Employee)
     * @param Request $request The HTTP request with filters
     * @param string $foreignKey The foreign key column in journal_entries table (e.g., 'customer_id', 'employee_id')
     * @param string $balanceType 'asset' (debit increases) or 'liability' (credit increases)
     * @return array
     */
    protected function getEntityLedgerData(Model $entity, Request $request, string $foreignKey, string $balanceType = 'asset'): array
    {
        // Build query for journal entry items linked to the entity
        $query = JournalEntryItem::query()
            ->select([
                'journal_entry_items.id',
                'journal_entry_items.debit_amount',
                'journal_entry_items.credit_amount',
                'journal_entry_items.description',
                'journal_entry_items.created_at',
                'journal_entry_items.journal_entry_id',
                'journal_entries.entry_date as entry_date',
                'journal_entries.reference_number as reference_number',
                'journal_entries.description as entry_description',
            ])
            ->join('journal_entries', 'journal_entries.id', '=', 'journal_entry_items.journal_entry_id')
            ->where("journal_entries.$foreignKey", $entity->id);

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
        // Default to date asc, then id asc for running balance consistency
        $query->orderBy('journal_entries.entry_date', 'asc')->orderBy('journal_entry_items.id', 'asc');

        // Get transactions
        $items = $query->get();

        // Calculate opening balance
        $openingBalance = (float) ($entity->opening_balance ?? 0);
        
        // Determine start date for opening balance calculation
        $dateFrom = $request->date_from ?? ($items->min('entry_date') ? Carbon::parse($items->min('entry_date'))->format('Y-m-d') : null);
        
        // If date_from is specified, calculate opening balance up to that date
        // Even if not specified, if we have transactions, we might want to start from the beginning.
        // But here, if no date_from, we just start with entity's opening_balance and list all transactions.
        
        if ($request->has('date_from') && $request->date_from) {
             $openingBalance = $this->calculateOpeningBalanceForEntity($entity, $foreignKey, $request->date_from, $balanceType);
        }

        // Prepare ledger data
        $ledgerData = [];
        $runningBalance = $openingBalance;

        // Add opening balance entry
        // Only if we have a date range or existing opening balance
        if ($dateFrom || $openingBalance != 0) {
             $ledgerData[] = [
                'id' => null,
                'date' => $dateFrom ?? ($entity->created_at ? $entity->created_at->format('Y-m-d') : now()->format('Y-m-d')),
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
            
            $runningBalance = $this->adjustBalanceByType($runningBalance, $balanceType, $debit, $credit);
            
            $ledgerData[] = [
                'id' => $item->id,
                'date' => Carbon::parse($item->entry_date)->format('Y-m-d'),
                'description' => $description,
                'reference_number' => $item->reference_number,
                'debit_amount' => $debit,
                'credit_amount' => $credit,
                'balance' => $runningBalance,
                'type' => 'journal',
                'created_at' => Carbon::parse($item->created_at)->format('Y-m-d H:i:s'),
            ];
        }

        // Reverse order if requested (e.g. for display newest first), 
        // BUT running balance calculation MUST always be chronological.
        // So we calculate chronologically, then reverse the array if needed.
        if ($request->get('sort_by') === 'date' && $request->get('sort_order') === 'desc') {
            $ledgerData = array_reverse($ledgerData);
        }

        // Calculate totals
        $totalDebit = $items->sum('debit_amount');
        $totalCredit = $items->sum('credit_amount');

        return [
            'entity' => $entity,
            'date_from' => $request->date_from,
            'date_to' => $request->date_to,
            'opening_balance' => $openingBalance,
            'closing_balance' => $runningBalance, // This is the balance at the end of the period
            'total_debit' => (float) $totalDebit,
            'total_credit' => (float) $totalCredit,
            'transactions_count' => $items->count(),
            'ledger' => $ledgerData,
        ];
    }

    /**
     * Calculate opening balance up to a specific date for an entity.
     */
    protected function calculateOpeningBalanceForEntity(Model $entity, string $foreignKey, string $date, string $balanceType): float
    {
        $openingBalance = (float) ($entity->opening_balance ?? 0);

        // Get all transactions before the date
        $previousItems = JournalEntryItem::query()
            ->join('journal_entries', 'journal_entries.id', '=', 'journal_entry_items.journal_entry_id')
            ->where("journal_entries.$foreignKey", $entity->id)
            ->where('journal_entries.entry_date', '<', $date)
            ->orderBy('journal_entries.entry_date', 'asc')
            ->orderBy('journal_entry_items.id', 'asc')
            ->get(['journal_entry_items.debit_amount', 'journal_entry_items.credit_amount']);

        // Calculate balance up to the date
        $balance = $openingBalance;
        foreach ($previousItems as $item) {
            $balance = $this->adjustBalanceByType(
                $balance, 
                $balanceType, 
                (float) $item->debit_amount, 
                (float) $item->credit_amount
            );
        }

        return $balance;
    }

    /**
     * Adjust balance based on balance type.
     */
    protected function adjustBalanceByType(float $currentBalance, string $balanceType, float $debit, float $credit): float
    {
        if ($balanceType === 'asset') {
            // Asset/Expense: Debit increases, Credit decreases
            return $currentBalance + $debit - $credit;
        } else {
            // Liability/Equity/Revenue: Credit increases, Debit decreases
            return $currentBalance + $credit - $debit;
        }
    }
}
