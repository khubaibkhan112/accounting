<?php

namespace App\Traits;

use App\Models\JournalEntryItem;
use App\Models\JournalEntry;
use App\Models\Transaction;
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
        $journalQuery = JournalEntryItem::query()
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
            $journalQuery->where('journal_entries.entry_date', '>=', $request->date_from);
        }

        if ($request->has('date_to') && $request->date_to) {
            $journalQuery->where('journal_entries.entry_date', '<=', $request->date_to);
        }

        // Search functionality
        if ($request->has('search') && $request->search) {
            $search = $request->search;
            $journalQuery->where(function ($q) use ($search) {
                $q->where('journal_entry_items.description', 'like', "%{$search}%")
                  ->orWhere('journal_entries.description', 'like', "%{$search}%")
                  ->orWhere('journal_entries.reference_number', 'like', "%{$search}%");
            });
        }

        // Sorting
        // Default to date asc, then id asc for running balance consistency
        $journalQuery->orderBy('journal_entries.entry_date', 'asc')->orderBy('journal_entry_items.id', 'asc');

        // Journal items for this entity
        $journalItems = $journalQuery->get();

        // Collect linked transaction ids (to avoid duplicates if journal entries already linked)
        $linkedTransactionIds = JournalEntry::query()
            ->whereNotNull('transaction_id')
            ->where($foreignKey, $entity->id)
            ->pluck('transaction_id')
            ->filter()
            ->values();

        // Transactions for this entity (legacy data or not yet journaled)
        $transactionQuery = Transaction::query()
            ->select([
                'id',
                'date',
                'description',
                'reference_number',
                'transaction_no',
                'debit_amount',
                'credit_amount',
                'created_at',
            ])
            ->where($foreignKey, $entity->id);

        if ($linkedTransactionIds->isNotEmpty()) {
            $transactionQuery->whereNotIn('id', $linkedTransactionIds);
        }

        if ($request->has('date_from') && $request->date_from) {
            $transactionQuery->where('date', '>=', $request->date_from);
        }

        if ($request->has('date_to') && $request->date_to) {
            $transactionQuery->where('date', '<=', $request->date_to);
        }

        if ($request->has('search') && $request->search) {
            $search = $request->search;
            $transactionQuery->where(function ($q) use ($search) {
                $q->where('description', 'like', "%{$search}%")
                  ->orWhere('reference_number', 'like', "%{$search}%")
                  ->orWhere('transaction_no', 'like', "%{$search}%");
            });
        }

        $transactions = $transactionQuery->orderBy('date', 'asc')->orderBy('id', 'asc')->get();

        // Calculate opening balance
        $openingBalance = (float) ($entity->opening_balance ?? 0);
        
        // Determine start date for opening balance calculation
        $earliestJournalDate = $journalItems->min('entry_date');
        $earliestTransactionDate = $transactions->min('date');
        $earliestDate = $earliestJournalDate ?: $earliestTransactionDate;
        if ($earliestJournalDate && $earliestTransactionDate) {
            $earliestDate = Carbon::parse($earliestJournalDate)->lte(Carbon::parse($earliestTransactionDate))
                ? $earliestJournalDate
                : $earliestTransactionDate;
        }

        $dateFrom = $request->date_from ?? ($earliestDate ? Carbon::parse($earliestDate)->format('Y-m-d') : null);
        
        // If date_from is specified, calculate opening balance up to that date
        // Even if not specified, if we have transactions, we might want to start from the beginning.
        // But here, if no date_from, we just start with entity's opening_balance and list all transactions.
        
        if ($request->has('date_from') && $request->date_from) {
            $openingBalance = $this->calculateOpeningBalanceForEntity(
                $entity,
                $foreignKey,
                $request->date_from,
                $balanceType
            );
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
        $combinedItems = [];

        foreach ($journalItems as $item) {
            $combinedItems[] = [
                'source' => 'journal',
                'id' => $item->id,
                'date' => Carbon::parse($item->entry_date)->format('Y-m-d'),
                'debit' => (float) $item->debit_amount,
                'credit' => (float) $item->credit_amount,
                'description' => $item->description ?: $item->entry_description,
                'reference_number' => $item->reference_number,
                'created_at' => Carbon::parse($item->created_at)->format('Y-m-d H:i:s'),
                'sort_date' => Carbon::parse($item->entry_date)->format('Y-m-d'),
                'sort_id' => $item->id,
            ];
        }

        foreach ($transactions as $transaction) {
            $combinedItems[] = [
                'source' => 'transaction',
                'id' => $transaction->id,
                'date' => Carbon::parse($transaction->date)->format('Y-m-d'),
                'debit' => (float) $transaction->debit_amount,
                'credit' => (float) $transaction->credit_amount,
                'description' => $transaction->description,
                'reference_number' => $transaction->reference_number ?: $transaction->transaction_no,
                'created_at' => Carbon::parse($transaction->created_at)->format('Y-m-d H:i:s'),
                'sort_date' => Carbon::parse($transaction->date)->format('Y-m-d'),
                'sort_id' => $transaction->id,
            ];
        }

        usort($combinedItems, function ($a, $b) {
            if ($a['sort_date'] === $b['sort_date']) {
                return $a['sort_id'] <=> $b['sort_id'];
            }
            return strcmp($a['sort_date'], $b['sort_date']);
        });

        foreach ($combinedItems as $item) {
            $runningBalance = $this->adjustBalanceByType(
                $runningBalance,
                $balanceType,
                $item['debit'],
                $item['credit']
            );

            $ledgerData[] = [
                'id' => $item['id'],
                'date' => $item['date'],
                'description' => $item['description'],
                'reference_number' => $item['reference_number'],
                'debit_amount' => $item['debit'],
                'credit_amount' => $item['credit'],
                'balance' => $runningBalance,
                'type' => $item['source'],
                'created_at' => $item['created_at'],
            ];
        }

        // Reverse order if requested (e.g. for display newest first), 
        // BUT running balance calculation MUST always be chronological.
        // So we calculate chronologically, then reverse the array if needed.
        if ($request->get('sort_by') === 'date' && $request->get('sort_order') === 'desc') {
            $ledgerData = array_reverse($ledgerData);
        }

        // Calculate totals
        $totalDebit = collect($combinedItems)->sum('debit');
        $totalCredit = collect($combinedItems)->sum('credit');

        return [
            'entity' => $entity,
            'date_from' => $request->date_from,
            'date_to' => $request->date_to,
            'opening_balance' => $openingBalance,
            'closing_balance' => $runningBalance, // This is the balance at the end of the period
            'total_debit' => (float) $totalDebit,
            'total_credit' => (float) $totalCredit,
            'transactions_count' => count($combinedItems),
            'ledger' => $ledgerData,
        ];
    }

    /**
     * Calculate opening balance up to a specific date for an entity.
     */
    protected function calculateOpeningBalanceForEntity(Model $entity, string $foreignKey, string $date, string $balanceType): float
    {
        $openingBalance = (float) ($entity->opening_balance ?? 0);

        // Journal items before the date
        $previousItems = JournalEntryItem::query()
            ->join('journal_entries', 'journal_entries.id', '=', 'journal_entry_items.journal_entry_id')
            ->where("journal_entries.$foreignKey", $entity->id)
            ->where('journal_entries.entry_date', '<', $date)
            ->orderBy('journal_entries.entry_date', 'asc')
            ->orderBy('journal_entry_items.id', 'asc')
            ->get(['journal_entry_items.debit_amount', 'journal_entry_items.credit_amount']);

        $journalDebit = (float) $previousItems->sum('debit_amount');
        $journalCredit = (float) $previousItems->sum('credit_amount');

        // Transactions before the date (excluding those already linked to journal entries)
        $linkedTransactionIds = JournalEntry::query()
            ->whereNotNull('transaction_id')
            ->where($foreignKey, $entity->id)
            ->pluck('transaction_id')
            ->filter()
            ->values();

        $transactionQuery = Transaction::query()
            ->where($foreignKey, $entity->id)
            ->where('date', '<', $date);

        if ($linkedTransactionIds->isNotEmpty()) {
            $transactionQuery->whereNotIn('id', $linkedTransactionIds);
        }

        $transactionDebit = (float) $transactionQuery->sum('debit_amount');
        $transactionCredit = (float) $transactionQuery->sum('credit_amount');

        $totalDebit = $journalDebit + $transactionDebit;
        $totalCredit = $journalCredit + $transactionCredit;

        if ($balanceType === 'asset') {
            return $openingBalance + $totalDebit - $totalCredit;
        }

        return $openingBalance + $totalCredit - $totalDebit;
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
