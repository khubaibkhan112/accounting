<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreTransactionRequest;
use App\Http\Requests\UpdateTransactionRequest;
use App\Models\Transaction;
use App\Models\Account;
use App\Services\AuditService;
use App\Models\Setting;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class TransactionController extends Controller
{
    /**
     * Display a listing of transactions.
     */
    public function index(Request $request): JsonResponse
    {
        $user = Auth::user();
        
        $query = Transaction::select([
            'id',
            'date',
            'account_id',
            'customer_id',
            'employee_id',
            'vehicle_id',
            'description',
            'debit_amount',
            'credit_amount',
            'reference_number',
            'transaction_type',
            'running_balance',
            'created_by',
            'created_at',
        ])->with([
            'account:id,account_code,account_name,account_type',
            'customer:id,customer_code,company_name,first_name,last_name',
            'employee:id,employee_id,first_name,last_name',
            'vehicle:id,vehicle_number,chassis_number,customer_id',
            'creator:id,name,email',
        ]);

        // Drivers can only see their own transactions
        if ($user && $user->isDriver() && $user->employee) {
            $query->where('employee_id', $user->employee->id);
        }

        // Filter by account
        if ($request->has('account_id') && $request->account_id) {
            $query->where('account_id', $request->account_id);
        }

        // Filter by customer
        if ($request->has('customer_id') && $request->customer_id) {
            $query->where('customer_id', $request->customer_id);
        }

        // Filter by employee (admins and accountants can filter by employee)
        if ($request->has('employee_id') && $request->employee_id) {
            if (!$user || (!$user->isDriver())) {
                $query->where('employee_id', $request->employee_id);
            }
        }

        // Filter by transaction type
        if ($request->has('transaction_type') && $request->transaction_type) {
            $query->where('transaction_type', $request->transaction_type);
        }

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

        // Sorting - default by date desc, then id desc
        $sortBy = $request->get('sort_by', 'date');
        $sortOrder = $request->get('sort_order', 'desc');
        $validSortFields = ['date', 'debit_amount', 'credit_amount', 'reference_number', 'created_at'];
        
        if (in_array($sortBy, $validSortFields)) {
            $query->orderBy($sortBy, $sortOrder);
        }
        
        // Secondary sort by id to ensure consistent ordering
        if ($sortBy !== 'id') {
            $query->orderBy('id', 'desc');
        }

        // Pagination
        $perPage = min($request->get('per_page', 15), 100); // Max 100 per page
        $transactions = $query->paginate($perPage);

        return response()->json($transactions);
    }

    /**
     * Store a newly created transaction.
     */
    public function store(StoreTransactionRequest $request): JsonResponse
    {
        // Verify account exists and is active
        $account = Account::find($request->account_id);
        if (!$account) {
            return response()->json([
                'message' => 'Account not found.',
            ], 404);
        }

        if (!$account->is_active) {
            return response()->json([
                'message' => 'Cannot create transaction for inactive account.',
            ], 422);
        }

        DB::beginTransaction();
        try {
            // Get the transaction date
            $transactionDate = $request->date;

            $referenceNumber = $request->reference_number;
            if (!$referenceNumber && Setting::get('auto_generate_reference', false)) {
                $referenceNumber = $this->generateReferenceNumber('TRX');
            }

            // Create transaction (balance will be calculated after)
            $transaction = Transaction::create([
                'date' => $transactionDate,
                'account_id' => $request->account_id,
                'customer_id' => $request->customer_id,
                'employee_id' => $request->employee_id,
                'description' => $request->description,
                'debit_amount' => $request->debit_amount ?? 0,
                'credit_amount' => $request->credit_amount ?? 0,
                'reference_number' => $referenceNumber,
                'transaction_type' => $request->transaction_type,
                'running_balance' => 0, // Temporary, will be recalculated
                'created_by' => Auth::id(),
            ]);

            // Recalculate all balances from this transaction's date
            $this->recalculateBalancesFromDate($request->account_id, $transactionDate);

            $transaction->load([
                'account:id,account_code,account_name,account_type',
                'customer:id,customer_code,company_name',
                'employee:id,employee_id,first_name,last_name',
                'creator:id,name',
            ]);

            // Log audit trail
            AuditService::logCreate($transaction, "Transaction created for account {$account->account_code}");

            // Log to Laravel log
            Log::info('Transaction created', [
                'transaction_id' => $transaction->id,
                'account_id' => $transaction->account_id,
                'amount' => $transaction->debit_amount > 0 ? $transaction->debit_amount : $transaction->credit_amount,
                'user_id' => Auth::id(),
            ]);

            DB::commit();

            return response()->json([
                'message' => 'Transaction created successfully',
                'transaction' => $transaction,
            ], 201);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'message' => 'Failed to create transaction',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Display the specified transaction.
     */
    public function show(Transaction $transaction): JsonResponse
    {
        $transaction->load([
            'account:id,account_code,account_name,account_type,opening_balance',
            'customer:id,customer_code,company_name,first_name,last_name',
            'employee:id,employee_id,first_name,last_name',
            'creator:id,name,email',
        ]);

        return response()->json($transaction);
    }

    /**
     * Update the specified transaction.
     */
    public function update(UpdateTransactionRequest $request, Transaction $transaction): JsonResponse
    {
        $accountId = $request->has('account_id') ? $request->account_id : $transaction->account_id;
        $transactionDate = $request->has('date') ? $request->date : $transaction->date;

        // Verify account exists and is active
        $account = Account::find($accountId);
        if (!$account) {
            return response()->json([
                'message' => 'Account not found.',
            ], 404);
        }

        if (!$account->is_active) {
            return response()->json([
                'message' => 'Cannot update transaction for inactive account.',
            ], 422);
        }

        DB::beginTransaction();
        try {
            // Store old values for recalculation and audit
            $oldAccountId = $transaction->account_id;
            $oldDate = $transaction->date;
            $oldDebit = $transaction->debit_amount;
            $oldCredit = $transaction->credit_amount;
            $oldValues = $transaction->getAttributes();

            // Update transaction fields
            $updateData = [];
            if ($request->has('date')) $updateData['date'] = $request->date;
            if ($request->has('account_id')) $updateData['account_id'] = $request->account_id;
            if ($request->has('customer_id')) $updateData['customer_id'] = $request->customer_id;
            if ($request->has('employee_id')) $updateData['employee_id'] = $request->employee_id;
            if ($request->has('description')) $updateData['description'] = $request->description;
            if ($request->has('debit_amount')) $updateData['debit_amount'] = $request->debit_amount ?? 0;
            if ($request->has('credit_amount')) $updateData['credit_amount'] = $request->credit_amount ?? 0;
            if ($request->has('reference_number')) $updateData['reference_number'] = $request->reference_number;
            if ($request->has('transaction_type')) $updateData['transaction_type'] = $request->transaction_type;

            $transaction->update($updateData);

            // Recalculate running balances
            // If account or date changed, recalculate for both old and new accounts
            if ($oldAccountId != $accountId || $oldDate != $transactionDate) {
                // Recalculate for old account
                $this->recalculateBalancesFromDate($oldAccountId, $oldDate);
                // Recalculate for new account
                $this->recalculateBalancesFromDate($accountId, $transactionDate);
            } else {
                // Just recalculate from this transaction's date
                $this->recalculateBalancesFromDate($accountId, $transactionDate);
            }

            $transaction->refresh()->load([
                'account:id,account_code,account_name,account_type',
                'customer:id,customer_code,company_name',
                'employee:id,employee_id,first_name,last_name',
                'creator:id,name',
            ]);

            // Log audit trail
            AuditService::logUpdate($transaction, $oldValues, "Transaction updated for account {$account->account_code}");

            // Log to Laravel log
            Log::info('Transaction updated', [
                'transaction_id' => $transaction->id,
                'account_id' => $transaction->account_id,
                'user_id' => Auth::id(),
            ]);

            DB::commit();

            return response()->json([
                'message' => 'Transaction updated successfully',
                'transaction' => $transaction,
            ]);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'message' => 'Failed to update transaction',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    private function generateReferenceNumber(string $prefix): string
    {
        $date = now()->format('Ymd');
        $reference = $prefix . '-' . $date . '-' . strtoupper(Str::random(6));

        while (Transaction::where('reference_number', $reference)->exists()) {
            $reference = $prefix . '-' . $date . '-' . strtoupper(Str::random(6));
        }

        return $reference;
    }

    /**
     * Remove the specified transaction.
     */
    public function destroy(Transaction $transaction): JsonResponse
    {
        DB::beginTransaction();
        try {
            $accountId = $transaction->account_id;
            $transactionDate = $transaction->date;
            $oldValues = $transaction->getAttributes();

            // Log audit trail before deletion
            AuditService::logDelete($transaction, "Transaction deleted for account {$transaction->account->account_code}");

            // Log to Laravel log
            Log::warning('Transaction deleted', [
                'transaction_id' => $transaction->id,
                'account_id' => $accountId,
                'user_id' => Auth::id(),
            ]);

            $transaction->delete();

            // Recalculate running balances for all subsequent transactions
            $this->recalculateBalancesFromDate($accountId, $transactionDate);

            DB::commit();

            return response()->json([
                'message' => 'Transaction deleted successfully',
            ]);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'message' => 'Failed to delete transaction',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Calculate running balance for a transaction.
     */
    private function calculateRunningBalance(int $accountId, string $date, float $debit, float $credit): float
    {
        $account = Account::find($accountId);
        if (!$account) {
            return 0;
        }

        // Get opening balance
        $openingBalance = (float) $account->opening_balance;

        // Get all transactions up to (but not including) this transaction's date
        // If same date, get transactions before this one by id
        $previousTransactions = Transaction::where('account_id', $accountId)
            ->where(function ($query) use ($date) {
                $query->where('date', '<', $date)
                      ->orWhere(function ($q) use ($date) {
                          $q->where('date', '=', $date);
                      });
            })
            ->orderBy('date', 'asc')
            ->orderBy('id', 'asc')
            ->get();

        // Calculate balance up to this point
        $balance = $openingBalance;
        foreach ($previousTransactions as $prevTransaction) {
            $balance = $this->adjustBalance($balance, $account->account_type, $prevTransaction->debit_amount, $prevTransaction->credit_amount);
        }

        // Add current transaction
        $balance = $this->adjustBalance($balance, $account->account_type, $debit, $credit);

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
     * Recalculate running balances for all transactions after a given transaction.
     */
    private function recalculateSubsequentBalances(int $accountId, int $transactionId, string $date): void
    {
        // Simply recalculate from the transaction date
        $this->recalculateBalancesFromDate($accountId, $date);
    }

    /**
     * Recalculate running balances from a specific date.
     */
    private function recalculateBalancesFromDate(int $accountId, string $date): void
    {
        $account = Account::find($accountId);
        if (!$account) {
            return;
        }

        // Get all transactions from this date onwards
        $transactions = Transaction::where('account_id', $accountId)
            ->where('date', '>=', $date)
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
            ->where('date', '<', $date)
            ->orderBy('date', 'asc')
            ->orderBy('id', 'asc')
            ->get();

        foreach ($previousTransactions as $prev) {
            $balance = $this->adjustBalance($balance, $account->account_type, $prev->debit_amount, $prev->credit_amount);
        }

        // Update running balances for all transactions from this date
        foreach ($transactions as $transaction) {
            $balance = $this->adjustBalance($balance, $account->account_type, $transaction->debit_amount, $transaction->credit_amount);
            $transaction->update(['running_balance' => $balance]);
        }
    }
}
