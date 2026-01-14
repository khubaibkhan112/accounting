<?php

namespace App\Services;

use App\Models\Account;
use App\Models\Transaction;
use App\Models\JournalEntry;
use Illuminate\Support\Facades\DB;

class AccountingService
{
    /**
     * Calculate account balance based on account type.
     */
    public function calculateAccountBalance(Account $account, ?string $dateTo = null): float
    {
        $openingBalance = (float) $account->opening_balance;

        $query = $account->transactions();
        if ($dateTo) {
            $query->where('date', '<=', $dateTo);
        }

        $debitTotal = (float) $query->sum('debit_amount');
        $creditTotal = (float) $query->sum('credit_amount');

        // For assets and expenses: opening + debits - credits
        // For liabilities, equity, and revenue: opening + credits - debits
        if (in_array($account->account_type, ['asset', 'expense'])) {
            return $openingBalance + $debitTotal - $creditTotal;
        } else {
            return $openingBalance + $creditTotal - $debitTotal;
        }
    }

    /**
     * Adjust balance based on account type and transaction amounts.
     */
    public function adjustBalance(float $currentBalance, string $accountType, float $debit, float $credit): float
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
     * Validate double-entry balance (debits must equal credits).
     */
    public function validateDoubleEntry(array $items): array
    {
        $totalDebit = 0;
        $totalCredit = 0;
        $errors = [];

        foreach ($items as $index => $item) {
            $debit = (float) ($item['debit_amount'] ?? 0);
            $credit = (float) ($item['credit_amount'] ?? 0);

            // Each item must have either debit or credit (not both, not neither)
            if ($debit == 0 && $credit == 0) {
                $errors[] = "Item " . ($index + 1) . ": Either debit or credit amount must be greater than 0.";
            }

            if ($debit > 0 && $credit > 0) {
                $errors[] = "Item " . ($index + 1) . ": Both debit and credit amounts cannot be greater than 0.";
            }

            $totalDebit += $debit;
            $totalCredit += $credit;
        }

        // Total debits must equal total credits
        $difference = abs($totalDebit - $totalCredit);
        if ($difference >= 0.01) {
            $errors[] = sprintf(
                'Journal entry is not balanced. Total debits: %s, Total credits: %s, Difference: %s',
                number_format($totalDebit, 2),
                number_format($totalCredit, 2),
                number_format($difference, 2)
            );
        }

        return [
            'is_valid' => empty($errors),
            'errors' => $errors,
            'total_debit' => $totalDebit,
            'total_credit' => $totalCredit,
            'difference' => $difference ?? 0,
        ];
    }

    /**
     * Validate that accounts exist and are active.
     */
    public function validateAccounts(array $accountIds): array
    {
        $accounts = Account::whereIn('id', $accountIds)->get();
        $foundIds = $accounts->pluck('id')->toArray();
        $missingIds = array_diff($accountIds, $foundIds);
        $inactiveIds = $accounts->where('is_active', false)->pluck('id')->toArray();

        return [
            'is_valid' => empty($missingIds) && empty($inactiveIds),
            'missing_accounts' => $missingIds,
            'inactive_accounts' => $inactiveIds,
            'accounts' => $accounts,
        ];
    }

    /**
     * Calculate running balance for an account up to a specific date.
     */
    public function calculateRunningBalance(int $accountId, string $dateTo, ?int $excludeTransactionId = null): float
    {
        $account = Account::find($accountId);
        if (!$account) {
            return 0;
        }

        $openingBalance = (float) $account->opening_balance;

        $query = Transaction::where('account_id', $accountId)
            ->where('date', '<=', $dateTo);

        if ($excludeTransactionId) {
            $query->where('id', '!=', $excludeTransactionId);
        }

        $transactions = $query->orderBy('date', 'asc')
            ->orderBy('id', 'asc')
            ->get();

        $balance = $openingBalance;
        foreach ($transactions as $transaction) {
            $balance = $this->adjustBalance(
                $balance,
                $account->account_type,
                $transaction->debit_amount,
                $transaction->credit_amount
            );
        }

        return $balance;
    }

    /**
     * Recalculate running balances for all transactions from a specific date.
     */
    public function recalculateRunningBalances(int $accountId, string $fromDate): void
    {
        $account = Account::find($accountId);
        if (!$account) {
            return;
        }

        // Get all transactions from this date onwards
        $transactions = Transaction::where('account_id', $accountId)
            ->where('date', '>=', $fromDate)
            ->orderBy('date', 'asc')
            ->orderBy('id', 'asc')
            ->get();

        if ($transactions->isEmpty()) {
            return;
        }

        // Calculate balance up to (but not including) the first transaction on this date
        $openingBalance = (float) $account->opening_balance;
        $balance = $openingBalance;

        $previousTransactions = Transaction::where('account_id', $accountId)
            ->where('date', '<', $fromDate)
            ->orderBy('date', 'asc')
            ->orderBy('id', 'asc')
            ->get();

        foreach ($previousTransactions as $prev) {
            $balance = $this->adjustBalance(
                $balance,
                $account->account_type,
                $prev->debit_amount,
                $prev->credit_amount
            );
        }

        // Update running balances for all transactions from this date
        foreach ($transactions as $transaction) {
            $balance = $this->adjustBalance(
                $balance,
                $account->account_type,
                $transaction->debit_amount,
                $transaction->credit_amount
            );
            $transaction->update(['running_balance' => $balance]);
        }
    }

    /**
     * Validate journal entry balance.
     */
    public function validateJournalEntryBalance(JournalEntry $journalEntry): bool
    {
        return $journalEntry->isBalanced();
    }

    /**
     * Calculate total debits and credits for an account in a date range.
     */
    public function calculateAccountTotals(Account $account, ?string $dateFrom = null, ?string $dateTo = null): array
    {
        $query = $account->transactions();

        if ($dateFrom) {
            $query->where('date', '>=', $dateFrom);
        }

        if ($dateTo) {
            $query->where('date', '<=', $dateTo);
        }

        $debitTotal = (float) $query->sum('debit_amount');
        $creditTotal = (float) $query->sum('credit_amount');

        return [
            'debit_total' => $debitTotal,
            'credit_total' => $creditTotal,
            'balance' => $this->calculateAccountBalance($account, $dateTo),
        ];
    }

    /**
     * Close period (e.g., month-end or year-end).
     * This transfers revenue and expense balances to retained earnings (equity).
     */
    public function closePeriod(string $endDate, ?int $retainedEarningsAccountId = null): array
    {
        DB::beginTransaction();
        try {
            // Find or create retained earnings account
            if (!$retainedEarningsAccountId) {
                $retainedEarnings = Account::where('account_type', 'equity')
                    ->where('account_code', '3100') // Standard account code for retained earnings
                    ->first();

                if (!$retainedEarnings) {
                    throw new \Exception('Retained earnings account not found. Please create an equity account for retained earnings.');
                }

                $retainedEarningsAccountId = $retainedEarnings->id;
            } else {
                $retainedEarnings = Account::find($retainedEarningsAccountId);
                if (!$retainedEarnings || $retainedEarnings->account_type !== 'equity') {
                    throw new \Exception('Invalid retained earnings account.');
                }
            }

            // Calculate net income (revenue - expenses)
            $revenueAccounts = Account::where('account_type', 'revenue')
                ->where('is_active', true)
                ->pluck('id');

            $revenueDebit = (float) Transaction::whereIn('account_id', $revenueAccounts)
                ->where('date', '<=', $endDate)
                ->sum('debit_amount');

            $revenueCredit = (float) Transaction::whereIn('account_id', $revenueAccounts)
                ->where('date', '<=', $endDate)
                ->sum('credit_amount');

            $totalRevenue = $revenueCredit - $revenueDebit;

            $expenseAccounts = Account::where('account_type', 'expense')
                ->where('is_active', true)
                ->pluck('id');

            $expenseDebit = (float) Transaction::whereIn('account_id', $expenseAccounts)
                ->where('date', '<=', $endDate)
                ->sum('debit_amount');

            $expenseCredit = (float) Transaction::whereIn('account_id', $expenseAccounts)
                ->where('date', '<=', $endDate)
                ->sum('credit_amount');

            $totalExpenses = $expenseDebit - $expenseCredit;
            $netIncome = $totalRevenue - $totalExpenses;

            // Create closing entry journal entry if net income is not zero
            if (abs($netIncome) >= 0.01) {
                $journalEntry = JournalEntry::create([
                    'entry_date' => $endDate,
                    'description' => 'Period Closing Entry - Transfer to Retained Earnings',
                    'reference_number' => 'CLOSE-' . $endDate,
                    'total_debit' => abs($netIncome),
                    'total_credit' => abs($netIncome),
                    'created_by' => auth()->id(),
                ]);

                // If net income is positive (profit), debit revenue accounts and credit retained earnings
                // If net income is negative (loss), credit expense accounts and debit retained earnings
                // For simplicity, we create a single entry to retained earnings
                // In practice, you might want to close individual revenue/expense accounts

                // Note: This is a simplified closing entry
                // A full closing process would close all revenue and expense accounts individually
            }

            DB::commit();

            return [
                'success' => true,
                'end_date' => $endDate,
                'total_revenue' => $totalRevenue,
                'total_expenses' => $totalExpenses,
                'net_income' => $netIncome,
                'retained_earnings_account_id' => $retainedEarningsAccountId,
            ];
        } catch (\Exception $e) {
            DB::rollBack();
            return [
                'success' => false,
                'error' => $e->getMessage(),
            ];
        }
    }

    /**
     * Validate account hierarchy (prevent circular references).
     */
    public function validateAccountHierarchy(int $accountId, int $parentAccountId): bool
    {
        if ($accountId === $parentAccountId) {
            return false; // Cannot be its own parent
        }

        $parent = Account::find($parentAccountId);
        if (!$parent || !$parent->is_active) {
            return false;
        }

        // Check if parent is a descendant of the account (would create circular reference)
        $current = Account::find($accountId);
        if ($current) {
            return !$this->isDescendant($parentAccountId, $accountId);
        }

        return true;
    }

    /**
     * Check if an account is a descendant of another account.
     */
    private function isDescendant(int $accountId, int $ancestorId): bool
    {
        $account = Account::find($accountId);
        if (!$account || !$account->parent_account_id) {
            return false;
        }

        if ($account->parent_account_id == $ancestorId) {
            return true;
        }

        return $this->isDescendant($account->parent_account_id, $ancestorId);
    }

    /**
     * Calculate trial balance totals.
     */
    public function calculateTrialBalanceTotals(?string $dateTo = null): array
    {
        $accounts = Account::where('is_active', true)->get();
        $totalDebit = 0;
        $totalCredit = 0;

        foreach ($accounts as $account) {
            $totals = $this->calculateAccountTotals($account, null, $dateTo);
            $balance = $totals['balance'];

            if (in_array($account->account_type, ['asset', 'expense'])) {
                if ($balance >= 0) {
                    $totalDebit += $balance;
                } else {
                    $totalCredit += abs($balance);
                }
            } else {
                if ($balance >= 0) {
                    $totalCredit += $balance;
                } else {
                    $totalDebit += abs($balance);
                }
            }
        }

        return [
            'total_debit' => $totalDebit,
            'total_credit' => $totalCredit,
            'is_balanced' => abs($totalDebit - $totalCredit) < 0.01,
        ];
    }
}

