<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreJournalEntryRequest;
use App\Http\Requests\UpdateJournalEntryRequest;
use App\Models\JournalEntry;
use App\Models\JournalEntryItem;
use App\Models\Account;
use App\Services\AuditService;
use App\Models\Setting;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class JournalEntryController extends Controller
{
    /**
     * Display a listing of journal entries.
     */
    public function index(Request $request): JsonResponse
    {
        $query = JournalEntry::select([
            'id',
            'entry_date',
            'description',
            'reference_number',
            'customer_id',
            'employee_id',
            'transaction_id',
            'total_debit',
            'total_credit',
            'created_by',
            'created_at',
            'updated_at',
        ])->with([
            'creator:id,name,email',
            'customer:id,customer_code,company_name,first_name,last_name',
            'employee:id,employee_id,first_name,last_name',
            'transaction:id,transaction_no,reference_number,date,account_id,description',
            'items:id,journal_entry_id,account_id,debit_amount,credit_amount,description',
            'items.account:id,account_code,account_name,account_type',
        ]);

        // Filter by date range
        if ($request->has('date_from') && $request->date_from) {
            $query->where('entry_date', '>=', $request->date_from);
        }

        if ($request->has('date_to') && $request->date_to) {
            $query->where('entry_date', '<=', $request->date_to);
        }

        // Filter by account (through items)
        if ($request->has('account_id') && $request->account_id) {
            $query->whereHas('items', function ($q) use ($request) {
                $q->where('account_id', $request->account_id);
            });
        }

        // Search functionality
        if ($request->has('search') && $request->search) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('description', 'like', "%{$search}%")
                  ->orWhere('reference_number', 'like', "%{$search}%");
            });
        }

        // Sorting - default by entry_date desc, then id desc
        $sortBy = $request->get('sort_by', 'entry_date');
        $sortOrder = $request->get('sort_order', 'desc');
        $validSortFields = ['entry_date', 'total_debit', 'total_credit', 'reference_number', 'created_at'];
        
        if (in_array($sortBy, $validSortFields)) {
            $query->orderBy($sortBy, $sortOrder);
        }
        
        // Secondary sort by id to ensure consistent ordering
        if ($sortBy !== 'id') {
            $query->orderBy('id', 'desc');
        }

        // Pagination
        $perPage = min($request->get('per_page', 15), 100); // Max 100 per page
        $journalEntries = $query->paginate($perPage);

        // Add is_balanced attribute
        $journalEntries->getCollection()->transform(function ($entry) {
            $entry->is_balanced = $entry->isBalanced();
            return $entry;
        });

        return response()->json($journalEntries);
    }

    /**
     * Store a newly created journal entry.
     */
    public function store(StoreJournalEntryRequest $request): JsonResponse
    {
        DB::beginTransaction();
        try {
            // Calculate total debit and credit from items
            $totalDebit = 0;
            $totalCredit = 0;

            foreach ($request->items as $item) {
                $totalDebit += (float) ($item['debit_amount'] ?? 0);
                $totalCredit += (float) ($item['credit_amount'] ?? 0);
            }

            // Validate that accounts exist and are active
            $accountIds = array_unique(array_column($request->items, 'account_id'));
            $accounts = Account::whereIn('id', $accountIds)->get();
            
            if ($accounts->count() !== count($accountIds)) {
                return response()->json([
                    'message' => 'One or more accounts do not exist.',
                        'account_ids' => $accountIds,
                        'accounts' => $accounts->pluck('account_code')->toArray(),
                ], 422);
            }

            // Check if all accounts are active
            $inactiveAccounts = $accounts->where('is_active', false);
            if ($inactiveAccounts->isNotEmpty()) {
                return response()->json([
                    'message' => 'Cannot create journal entry with inactive accounts.',
                    'inactive_accounts' => $inactiveAccounts->pluck('account_code')->toArray(),
                ], 422);
            }

            $referenceNumber = $request->reference_number;
            if (!$referenceNumber && Setting::get('auto_generate_reference', false)) {
                $referenceNumber = $this->generateReferenceNumber('JRN');
            }

            // Create journal entry
            $journalEntry = JournalEntry::create([
                'entry_date' => $request->entry_date,
                'description' => $request->description,
                'reference_number' => $referenceNumber,
                'customer_id' => $request->customer_id,
                'employee_id' => $request->employee_id,
                'transaction_id' => $request->transaction_id,
                'total_debit' => $totalDebit,
                'total_credit' => $totalCredit,
                'created_by' => Auth::id(),
            ]);

            // Create journal entry items
            foreach ($request->items as $item) {
                JournalEntryItem::create([
                    'journal_entry_id' => $journalEntry->id,
                    'account_id' => $item['account_id'],
                    'debit_amount' => $item['debit_amount'] ?? 0,
                    'credit_amount' => $item['credit_amount'] ?? 0,
                    'description' => $item['description'] ?? null,
                ]);
            }

            $journalEntry->load([
                'creator:id,name,email',
                'customer:id,customer_code,company_name,first_name,last_name',
                'employee:id,employee_id,first_name,last_name',
                'transaction:id,transaction_no,reference_number,date,account_id,description',
                'items:id,journal_entry_id,account_id,debit_amount,credit_amount,description',
                'items.account:id,account_code,account_name,account_type',
            ]);

            $journalEntry->is_balanced = $journalEntry->isBalanced();

            // Log audit trail
            AuditService::logCreate($journalEntry, "Journal entry created with reference {$journalEntry->reference_number}");

            // Log to Laravel log
            Log::info('Journal entry created', [
                'journal_entry_id' => $journalEntry->id,
                'reference_number' => $journalEntry->reference_number,
                'total_debit' => $journalEntry->total_debit,
                'total_credit' => $journalEntry->total_credit,
                'user_id' => Auth::id(),
            ]);

            DB::commit();

            return response()->json([
                'message' => 'Journal entry created successfully',
                'journal_entry' => $journalEntry,
            ], 201);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'message' => 'Failed to create journal entry',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Display the specified journal entry.
     */
    public function show(JournalEntry $journalEntry): JsonResponse
    {
        $journalEntry->load([
            'creator:id,name,email',
            'customer:id,customer_code,company_name,first_name,last_name',
            'employee:id,employee_id,first_name,last_name',
            'transaction:id,transaction_no,reference_number,date,account_id,description',
            'items:id,journal_entry_id,account_id,debit_amount,credit_amount,description',
            'items.account:id,account_code,account_name,account_type,opening_balance',
        ]);

        $journalEntry->is_balanced = $journalEntry->isBalanced();

        return response()->json($journalEntry);
    }

    /**
     * Update the specified journal entry.
     */
    public function update(UpdateJournalEntryRequest $request, JournalEntry $journalEntry): JsonResponse
    {
        DB::beginTransaction();
        try {
            // Store old values for audit
            $oldValues = $journalEntry->getAttributes();
            
            // Update journal entry fields if provided
            $updateData = [];
            if ($request->has('entry_date')) $updateData['entry_date'] = $request->entry_date;
            if ($request->has('description')) $updateData['description'] = $request->description;
            if ($request->has('reference_number')) $updateData['reference_number'] = $request->reference_number;
            if ($request->has('customer_id')) $updateData['customer_id'] = $request->customer_id;
            if ($request->has('employee_id')) $updateData['employee_id'] = $request->employee_id;
            if ($request->has('transaction_id')) $updateData['transaction_id'] = $request->transaction_id;

            // If items are being updated, recalculate totals
            if ($request->has('items')) {
                $totalDebit = 0;
                $totalCredit = 0;

                foreach ($request->items as $item) {
                    $totalDebit += (float) ($item['debit_amount'] ?? 0);
                    $totalCredit += (float) ($item['credit_amount'] ?? 0);
                }

                $updateData['total_debit'] = $totalDebit;
                $updateData['total_credit'] = $totalCredit;

                // Validate that accounts exist and are active
                $accountIds = array_unique(array_column($request->items, 'account_id'));
                $accounts = Account::whereIn('id', $accountIds)->get();
                
                if ($accounts->count() !== count($accountIds)) {
                    DB::rollBack();
                    return response()->json([
                        'message' => 'One or more accounts do not exist.',
                        'account_ids' => $accountIds,
                    ], 422);
                }

                // Check if all accounts are active
                $inactiveAccounts = $accounts->where('is_active', false);
                if ($inactiveAccounts->isNotEmpty()) {
                    DB::rollBack();
                    return response()->json([
                        'message' => 'Cannot update journal entry with inactive accounts.',
                        'inactive_accounts' => $inactiveAccounts->pluck('account_code')->toArray(),
                    ], 422);
                }

                // Delete old items
                $journalEntry->items()->delete();

                // Create new items
                foreach ($request->items as $item) {
                    JournalEntryItem::create([
                        'journal_entry_id' => $journalEntry->id,
                        'account_id' => $item['account_id'],
                        'debit_amount' => $item['debit_amount'] ?? 0,
                        'credit_amount' => $item['credit_amount'] ?? 0,
                        'description' => $item['description'] ?? null,
                    ]);
                }
            }

            $journalEntry->update($updateData);

            $journalEntry->refresh()->load([
                'creator:id,name,email',
                'customer:id,customer_code,company_name,first_name,last_name',
                'employee:id,employee_id,first_name,last_name',
                'transaction:id,transaction_no,reference_number,date,account_id,description',
                'items:id,journal_entry_id,account_id,debit_amount,credit_amount,description',
                'items.account:id,account_code,account_name,account_type',
            ]);

            $journalEntry->is_balanced = $journalEntry->isBalanced();

            // Log audit trail
            AuditService::logUpdate($journalEntry, $oldValues, "Journal entry updated with reference {$journalEntry->reference_number}");

            // Log to Laravel log
            Log::info('Journal entry updated', [
                'journal_entry_id' => $journalEntry->id,
                'reference_number' => $journalEntry->reference_number,
                'user_id' => Auth::id(),
            ]);

            DB::commit();

            return response()->json([
                'message' => 'Journal entry updated successfully',
                'journal_entry' => $journalEntry,
            ]);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'message' => 'Failed to update journal entry',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    private function generateReferenceNumber(string $prefix): string
    {
        $date = now()->format('Ymd');
        $reference = $prefix . '-' . $date . '-' . strtoupper(Str::random(6));

        while (JournalEntry::where('reference_number', $reference)->exists()) {
            $reference = $prefix . '-' . $date . '-' . strtoupper(Str::random(6));
        }

        return $reference;
    }

    /**
     * Remove the specified journal entry.
     */
    public function destroy(JournalEntry $journalEntry): JsonResponse
    {
        DB::beginTransaction();
        try {
            // Log audit trail before deletion
            AuditService::logDelete($journalEntry, "Journal entry deleted with reference {$journalEntry->reference_number}");

            // Log to Laravel log
            Log::warning('Journal entry deleted', [
                'journal_entry_id' => $journalEntry->id,
                'reference_number' => $journalEntry->reference_number,
                'user_id' => Auth::id(),
            ]);

            // Items will be deleted automatically due to cascade
            $journalEntry->delete();

            DB::commit();

            return response()->json([
                'message' => 'Journal entry deleted successfully',
            ]);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'message' => 'Failed to delete journal entry',
                'error' => $e->getMessage(),
            ], 500);
        }
    }
}
