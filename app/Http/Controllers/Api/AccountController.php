<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreAccountRequest;
use App\Http\Requests\UpdateAccountRequest;
use App\Models\Account;
use App\Services\AuditService;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

class AccountController extends Controller
{
    /**
     * Display a listing of accounts.
     */
    public function index(Request $request): JsonResponse
    {
        $user = Auth::user();
        
        // Only admins and accountants can view accounts
        // Drivers can view accounts but with limited access
        if (!$user) {
            return response()->json([
                'message' => 'Unauthorized.',
            ], 401);
        }
        
        $query = Account::select([
            'id',
            'account_code',
            'account_name',
            'account_type',
            'parent_account_id',
            'opening_balance',
            'description',
            'is_active',
            'created_at',
            'updated_at',
        ])->with(['parentAccount:id,account_code,account_name', 'childAccounts:id,account_code,account_name,parent_account_id']);

        // Search functionality
        if ($request->has('search') && $request->search) {
            $query->search($request->search);
        }

        // Filter by account type
        if ($request->has('account_type') && $request->account_type) {
            $query->ofType($request->account_type);
        }

        // Filter by active status
        if ($request->has('is_active') && $request->is_active !== '') {
            $query->where('is_active', filter_var($request->is_active, FILTER_VALIDATE_BOOLEAN));
        }
        // Removed default active() filter to show all accounts by default

        // Filter by parent (get only parent accounts or children of specific parent)
        if ($request->has('parent_only') && $request->boolean('parent_only')) {
            $query->parentAccounts();
        }

        if ($request->has('parent_id')) {
            if ($request->parent_id === 'null' || $request->parent_id === null) {
                $query->parentAccounts();
            } else {
                $query->childrenOf($request->parent_id);
            }
        }

        // Sorting
        $sortBy = $request->get('sort_by', 'account_code');
        $sortOrder = $request->get('sort_order', 'asc');
        $validSortFields = ['account_code', 'account_name', 'account_type', 'opening_balance', 'created_at'];
        
        if (in_array($sortBy, $validSortFields)) {
            $query->orderBy($sortBy, $sortOrder);
        }

        // Pagination
        $perPage = min($request->get('per_page', 15), 100); // Max 100 per page
        $accounts = $query->paginate($perPage);

        // Append current balance to each account
        $accounts->getCollection()->transform(function ($account) {
            $account->current_balance = $account->current_balance;
            $account->total_debits = $account->total_debits;
            $account->total_credits = $account->total_credits;
            return $account;
        });

        return response()->json($accounts);
    }

    /**
     * Store a newly created account.
     */
    public function store(StoreAccountRequest $request): JsonResponse
    {
        $user = Auth::user();
        
        // Only admins and accountants can create accounts
        if (!$user || (!$user->isAdmin() && !$user->isAccountant())) {
            return response()->json([
                'message' => 'Unauthorized. You do not have permission to create accounts.',
            ], 403);
        }

        // Validate parent account if provided
        if ($request->has('parent_account_id') && $request->parent_account_id) {
            if (!Account::validateParentAccount(0, $request->parent_account_id)) {
                return response()->json([
                    'message' => 'Invalid parent account. Parent account must be active and cannot create circular references.',
                ], 422);
            }
        }

        DB::beginTransaction();
        try {
            $account = Account::create($request->only([
                'account_code',
                'account_name',
                'account_type',
                'parent_account_id',
                'opening_balance',
                'description',
                'is_active',
            ]));

            $account->load(['parentAccount', 'childAccounts']);
            $account->current_balance = $account->current_balance;

            // Log audit trail
            AuditService::logCreate($account, "Account created: {$account->account_code} - {$account->account_name}");

            // Log to Laravel log
            Log::info('Account created', [
                'account_id' => $account->id,
                'account_code' => $account->account_code,
                'account_type' => $account->account_type,
                'user_id' => Auth::id(),
            ]);

            DB::commit();

            return response()->json([
                'message' => 'Account created successfully',
                'account' => $account,
            ], 201);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'message' => 'Failed to create account',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Display the specified account.
     */
    public function show(Account $account): JsonResponse
    {
        $account->load(['parentAccount', 'childAccounts', 'transactions' => function ($query) {
            $query->latest()->limit(10);
        }]);
        
        $account->current_balance = $account->current_balance;
        $account->total_debits = $account->total_debits;
        $account->total_credits = $account->total_credits;
        $account->hierarchy_path = $account->hierarchy_path;

        return response()->json($account);
    }

    /**
     * Update the specified account.
     */
    public function update(UpdateAccountRequest $request, Account $account): JsonResponse
    {
        $user = Auth::user();
        
        // Only admins and accountants can update accounts
        if (!$user || (!$user->isAdmin() && !$user->isAccountant())) {
            return response()->json([
                'message' => 'Unauthorized. You do not have permission to update accounts.',
            ], 403);
        }

        // Validate parent account if provided
        if ($request->has('parent_account_id')) {
            $parentId = $request->parent_account_id;
            
            // Check if trying to set itself as parent
            if ($parentId == $account->id) {
                return response()->json([
                    'message' => 'Account cannot be its own parent.',
                ], 422);
            }

            if ($parentId && !Account::validateParentAccount($account->id, $parentId)) {
                return response()->json([
                    'message' => 'Invalid parent account. Parent account must be active and cannot create circular references.',
                ], 422);
            }
        }

        // Prevent changing account type if account has transactions
        if ($request->has('account_type') && 
            $request->account_type !== $account->account_type && 
            $account->hasTransactions()) {
            return response()->json([
                'message' => 'Cannot change account type. Account has existing transactions.',
            ], 422);
        }

        DB::beginTransaction();
        try {
            // Store old values for audit
            $oldValues = $account->getAttributes();
            
            $account->update($request->only([
                'account_code',
                'account_name',
                'account_type',
                'parent_account_id',
                'opening_balance',
                'description',
                'is_active',
            ]));

            $account->refresh()->load(['parentAccount', 'childAccounts']);
            $account->current_balance = $account->current_balance;
            $account->hierarchy_path = $account->hierarchy_path;

            // Log audit trail
            AuditService::logUpdate($account, $oldValues, "Account updated: {$account->account_code} - {$account->account_name}");

            // Log to Laravel log
            Log::info('Account updated', [
                'account_id' => $account->id,
                'account_code' => $account->account_code,
                'user_id' => Auth::id(),
            ]);

            DB::commit();

            return response()->json([
                'message' => 'Account updated successfully',
                'account' => $account,
            ]);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'message' => 'Failed to update account',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Remove the specified account.
     */
    public function destroy(Account $account): JsonResponse
    {
        $user = Auth::user();
        
        // Only admins and accountants can delete accounts
        if (!$user || (!$user->isAdmin() && !$user->isAccountant())) {
            return response()->json([
                'message' => 'Unauthorized. You do not have permission to delete accounts.',
            ], 403);
        }

        // Check if account has transactions
        if ($account->hasTransactions()) {
            return response()->json([
                'message' => 'Cannot delete account. Account has existing transactions. Deactivate it instead.',
            ], 422);
        }

        // Check if account has child accounts
        if ($account->hasChildren()) {
            return response()->json([
                'message' => 'Cannot delete account. Account has child accounts. Please delete or reassign child accounts first.',
            ], 422);
        }

        DB::beginTransaction();
        try {
            // Store old values for audit
            $oldValues = $account->getAttributes();
            
            // Soft delete by deactivating instead of actual deletion
            $account->update(['is_active' => false]);

            // Log audit trail
            AuditService::logUpdate($account, $oldValues, "Account deactivated: {$account->account_code} - {$account->account_name}");

            // Log to Laravel log
            Log::warning('Account deactivated', [
                'account_id' => $account->id,
                'account_code' => $account->account_code,
                'user_id' => Auth::id(),
            ]);

            DB::commit();

            return response()->json([
                'message' => 'Account deactivated successfully',
            ]);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'message' => 'Failed to deactivate account',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Get account tree structure.
     */
    public function tree(): JsonResponse
    {
        $tree = Account::getTreeStructure();
        
        return response()->json([
            'tree' => $tree,
        ]);
    }

    /**
     * Get accounts by type.
     */
    public function byType(string $type): JsonResponse
    {
        $validTypes = ['asset', 'liability', 'equity', 'revenue', 'expense'];
        
        if (!in_array($type, $validTypes)) {
            return response()->json([
                'message' => 'Invalid account type',
            ], 422);
        }

        $accounts = Account::ofType($type)
            ->active()
            ->with(['parentAccount'])
            ->orderBy('account_code')
            ->get()
            ->map(function ($account) {
                return [
                    'id' => $account->id,
                    'account_code' => $account->account_code,
                    'account_name' => $account->account_name,
                    'current_balance' => $account->current_balance,
                    'full_name' => $account->full_name,
                ];
            });

        return response()->json([
            'type' => $type,
            'accounts' => $accounts,
        ]);
    }

    /**
     * Get account balance summary.
     */
    public function balanceSummary(Account $account): JsonResponse
    {
        return response()->json([
            'account_id' => $account->id,
            'account_code' => $account->account_code,
            'account_name' => $account->account_name,
            'opening_balance' => (float) $account->opening_balance,
            'total_debits' => $account->total_debits,
            'total_credits' => $account->total_credits,
            'current_balance' => $account->current_balance,
            'transaction_count' => $account->transactions()->count(),
        ]);
    }

    /**
     * Get account types with counts.
     */
    public function types(): JsonResponse
    {
        $types = Account::select('account_type', DB::raw('count(*) as count'))
            ->active()
            ->groupBy('account_type')
            ->get();

        return response()->json([
            'types' => $types,
        ]);
    }
}
