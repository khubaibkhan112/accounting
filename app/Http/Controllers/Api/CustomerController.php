<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Customer;
use App\Models\Transaction;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

use App\Traits\LedgerTrait;
use App\Exports\LedgerExport;
use Maatwebsite\Excel\Facades\Excel;
use Barryvdh\DomPDF\Facade\Pdf;

class CustomerController extends Controller
{
    use LedgerTrait;

    /**
     * Display a listing of customers.
     */
    public function index(Request $request): JsonResponse
    {
        $query = Customer::select([
            'id',
            'customer_code',
            'company_name',
            'first_name',
            'last_name',
            'email',
            'phone',
            'customer_type',
            'current_balance',
            'assigned_to',
            'is_active',
            'created_at',
        ])->with(['assignedEmployee:id,employee_id,first_name,last_name']);

        // Search functionality
        if ($request->has('search') && $request->search) {
            $query->search($request->search);
        }

        // Filter by customer type
        if ($request->has('customer_type') && $request->customer_type) {
            $query->ofType($request->customer_type);
        }

        // Filter by assigned employee
        if ($request->has('assigned_to')) {
            $query->where('assigned_to', $request->assigned_to);
        }

        // Filter by active status
        if ($request->has('is_active')) {
            $query->where('is_active', filter_var($request->is_active, FILTER_VALIDATE_BOOLEAN));
        } else {
            $query->active(); // Default to active customers
        }

        // Sorting
        $sortBy = $request->get('sort_by', 'customer_code');
        $sortOrder = $request->get('sort_order', 'asc');
        $validSortFields = ['customer_code', 'company_name', 'first_name', 'last_name', 'email', 'current_balance', 'created_at'];
        
        if (in_array($sortBy, $validSortFields)) {
            $query->orderBy($sortBy, $sortOrder);
        }

        // Pagination
        $perPage = min($request->get('per_page', 15), 100);
        $customers = $query->paginate($perPage);

        // Eager load transaction counts to avoid N+1 queries
        $customers->getCollection()->loadCount('transactions');
        
        // Append computed values
        $customers->getCollection()->transform(function ($customer) {
            $customer->full_name = $customer->full_name;
            $customer->display_name = $customer->display_name;
            $customer->current_balance = $customer->calculateCurrentBalance();
            $customer->transaction_count = $customer->transactions_count; // Use eager loaded count
            return $customer;
        });

        return response()->json($customers);
    }

    /**
     * Store a newly created customer.
     */
    public function store(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'customer_code' => 'required|string|max:50|unique:customers,customer_code',
            'company_name' => 'nullable|string|max:255',
            'first_name' => 'nullable|string|max:255',
            'last_name' => 'nullable|string|max:255',
            'email' => 'nullable|email|max:255|unique:customers,email',
            'phone' => 'nullable|string|max:20',
            'mobile' => 'nullable|string|max:20',
            'customer_type' => 'required|in:individual,business',
            'payment_terms' => 'nullable|in:cash,net_15,net_30,net_60,net_90,custom',
            'credit_limit' => 'nullable|numeric|min:0',
            'opening_balance' => 'nullable|numeric',
            'assigned_to' => 'nullable|exists:employees,id',
            'is_active' => 'boolean',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => 'Validation failed',
                'errors' => $validator->errors(),
            ], 422);
        }

        DB::beginTransaction();
        try {
            $customer = Customer::create($request->all());
            $customer->updateBalance();
            $customer->load(['assignedEmployee']);

            DB::commit();

            return response()->json([
                'message' => 'Customer created successfully',
                'customer' => $customer,
            ], 201);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'message' => 'Failed to create customer',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Display the specified customer with all related data.
     */
    public function show(Customer $customer): JsonResponse
    {
        $customer->load(['assignedEmployee']);
        $customer->current_balance = $customer->calculateCurrentBalance();
        $customer->full_name = $customer->full_name;
        $customer->display_name = $customer->display_name;

        // Get all transactions
        $transactions = $customer->transactions()
            ->with(['account', 'employee', 'creator'])
            ->orderBy('date', 'desc')
            ->orderBy('created_at', 'desc')
            ->get();

        // Get transaction statistics
        $stats = [
            'total_transactions' => $transactions->count(),
            'total_sales' => $customer->total_sales,
            'total_payments' => $customer->total_payments,
            'current_balance' => $customer->current_balance,
            'opening_balance' => (float) $customer->opening_balance,
            'credit_limit' => $customer->credit_limit ? (float) $customer->credit_limit : null,
            'credit_utilization' => $customer->credit_limit 
                ? round((abs($customer->current_balance) / $customer->credit_limit) * 100, 2)
                : null,
            'has_exceeded_credit_limit' => $customer->hasExceededCreditLimit(),
        ];

        // Get recent transactions (last 10)
        $recentTransactions = $transactions->take(10);

        // Group transactions by account type
        $transactionsByAccount = $transactions->groupBy(function ($transaction) {
            return $transaction->account->account_type ?? 'unknown';
        })->map(function ($group) {
            return [
                'count' => $group->count(),
                'total_debit' => $group->sum('debit_amount'),
                'total_credit' => $group->sum('credit_amount'),
            ];
        });

        return response()->json([
            'customer' => $customer,
            'statistics' => $stats,
            'recent_transactions' => $recentTransactions,
            'transactions_by_account_type' => $transactionsByAccount,
            'total_transactions_count' => $transactions->count(),
        ]);
    }

    /**
     * Update the specified customer.
     */
    public function update(Request $request, Customer $customer): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'customer_code' => 'sometimes|required|string|max:50|unique:customers,customer_code,' . $customer->id,
            'company_name' => 'nullable|string|max:255',
            'first_name' => 'nullable|string|max:255',
            'last_name' => 'nullable|string|max:255',
            'email' => 'nullable|email|max:255|unique:customers,email,' . $customer->id,
            'phone' => 'nullable|string|max:20',
            'mobile' => 'nullable|string|max:20',
            'customer_type' => 'sometimes|required|in:individual,business',
            'payment_terms' => 'nullable|in:cash,net_15,net_30,net_60,net_90,custom',
            'credit_limit' => 'nullable|numeric|min:0',
            'opening_balance' => 'nullable|numeric',
            'assigned_to' => 'nullable|exists:employees,id',
            'is_active' => 'boolean',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => 'Validation failed',
                'errors' => $validator->errors(),
            ], 422);
        }

        DB::beginTransaction();
        try {
            $customer->update($request->all());
            $customer->updateBalance();
            $customer->refresh()->load(['assignedEmployee']);
            $customer->current_balance = $customer->calculateCurrentBalance();

            DB::commit();

            return response()->json([
                'message' => 'Customer updated successfully',
                'customer' => $customer,
            ]);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'message' => 'Failed to update customer',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Remove the specified customer.
     */
    public function destroy(Customer $customer): JsonResponse
    {
        // Check if customer has transactions
        if ($customer->transactions()->exists()) {
            return response()->json([
                'message' => 'Cannot delete customer. Customer has existing transactions. Deactivate it instead.',
            ], 422);
        }

        DB::beginTransaction();
        try {
            $customer->update(['is_active' => false]);

            DB::commit();

            return response()->json([
                'message' => 'Customer deactivated successfully',
            ]);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'message' => 'Failed to deactivate customer',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Get customer transactions.
     */
    /**
     * Get customer transactions (Ledger).
     */
    public function transactions(Customer $customer, Request $request): JsonResponse
    {
        $data = $this->getEntityLedgerData($customer, $request, 'customer_id', 'asset');
        return response()->json($data);
    }

    /**
     * Export customer ledger to Excel.
     */
    public function exportLedgerExcel(Request $request, Customer $customer)
    {
        $data = $this->getEntityLedgerData($customer, $request, 'customer_id', 'asset');
        
        // Transform summary for export
        $summary = [
            'date_from' => $data['date_from'],
            'date_to' => $data['date_to'],
            'opening_balance' => $data['opening_balance'],
            'closing_balance' => $data['closing_balance'],
            'total_debit' => $data['total_debit'],
            'total_credit' => $data['total_credit'],
        ];

        // Prepare account array for export class
        $account = [
            'account_code' => $customer->customer_code,
            'account_name' => $customer->display_name,
        ];

        // Format ledger data for export
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

        $filename = 'customer_ledger_' . $customer->customer_code . '_' . now()->format('Y-m-d') . '.xlsx';

        return Excel::download(new LedgerExport($exportData, $account, $summary), $filename);
    }

    /**
     * Export customer ledger to PDF.
     */
    public function exportLedgerPdf(Request $request, Customer $customer)
    {
        $data = $this->getEntityLedgerData($customer, $request, 'customer_id', 'asset');
        
        // Map data structure for the view
        $viewData = $data;
        $viewData['account'] = [
            'account_code' => $customer->customer_code,
            'account_name' => $customer->display_name,
            'opening_balance' => $data['opening_balance'],
            'current_balance' => $data['closing_balance'],
        ];

        $pdf = Pdf::loadView('exports.ledger', $viewData);
        $filename = 'customer_ledger_' . $customer->customer_code . '_' . now()->format('Y-m-d') . '.pdf';

        return $pdf->download($filename);
    }

    /**
     * Generate next customer code.
     */
    public function generateCustomerCode(): JsonResponse
    {
        $lastCustomer = Customer::orderBy('id', 'desc')->first();
        $nextNumber = $lastCustomer ? ((int) substr($lastCustomer->customer_code, -4)) + 1 : 1;
        $customerCode = 'CUST' . str_pad($nextNumber, 4, '0', STR_PAD_LEFT);

        return response()->json(['customer_code' => $customerCode]);
    }
}
