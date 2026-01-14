<?php

namespace App\Traits;

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
     * @param string $foreignKey The foreign key column in transactions table (e.g., 'account_id', 'customer_id')
     * @param string $balanceType 'asset' (debit increases) or 'liability' (credit increases)
     * @return array
     */
    protected function getEntityLedgerData(Model $entity, Request $request, string $foreignKey, string $balanceType = 'asset'): array
    {
        // Build query for transactions
        $query = Transaction::select([
            'id',
            'date',
            'description',
            'debit_amount',
            'credit_amount',
            'reference_number',
            'transaction_type', // Added transaction_type
            'created_at',
        ])->where($foreignKey, $entity->id);

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
        // Default to date asc, then id asc for running balance consistency
        $query->orderBy('date', 'asc')->orderBy('id', 'asc');

        // Get transactions
        $transactions = $query->get();

        // Calculate opening balance
        $openingBalance = (float) ($entity->opening_balance ?? 0);
        
        // Determine start date for opening balance calculation
        $dateFrom = $request->date_from ?? ($transactions->min('date') ? $transactions->min('date')->format('Y-m-d') : null);
        
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
        foreach ($transactions as $transaction) {
            $debit = (float) $transaction->debit_amount;
            $credit = (float) $transaction->credit_amount;
            
            $runningBalance = $this->adjustBalanceByType($runningBalance, $balanceType, $debit, $credit);
            
            $ledgerData[] = [
                'id' => $transaction->id,
                'date' => $transaction->date->format('Y-m-d'),
                'description' => $transaction->description,
                'reference_number' => $transaction->reference_number,
                'debit_amount' => $debit,
                'credit_amount' => $credit,
                'balance' => $runningBalance,
                'type' => 'transaction',
                'created_at' => $transaction->created_at->format('Y-m-d H:i:s'),
            ];
        }

        // Reverse order if requested (e.g. for display newest first), 
        // BUT running balance calculation MUST always be chronological.
        // So we calculate chronologically, then reverse the array if needed.
        if ($request->get('sort_by') === 'date' && $request->get('sort_order') === 'desc') {
            $ledgerData = array_reverse($ledgerData);
        }

        // Calculate totals
        $totalDebit = $transactions->sum('debit_amount');
        $totalCredit = $transactions->sum('credit_amount');

        return [
            'entity' => $entity,
            'date_from' => $request->date_from,
            'date_to' => $request->date_to,
            'opening_balance' => $openingBalance,
            'closing_balance' => $runningBalance, // This is the balance at the end of the period
            'total_debit' => (float) $totalDebit,
            'total_credit' => (float) $totalCredit,
            'transactions_count' => $transactions->count(),
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
        $previousTransactions = Transaction::where($foreignKey, $entity->id)
            ->where('date', '<', $date)
            ->orderBy('date', 'asc')
            ->orderBy('id', 'asc')
            ->get();

        // Calculate balance up to the date
        $balance = $openingBalance;
        foreach ($previousTransactions as $transaction) {
            $balance = $this->adjustBalanceByType(
                $balance, 
                $balanceType, 
                (float) $transaction->debit_amount, 
                (float) $transaction->credit_amount
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
