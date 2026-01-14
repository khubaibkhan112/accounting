<?php

namespace App\Http\Controllers;

use App\Models\Employee;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

class EmployeeController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $query = Employee::with('user');

        // Search functionality
        if ($request->has('search') && $request->search) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('first_name', 'like', "%{$search}%")
                    ->orWhere('last_name', 'like', "%{$search}%")
                    ->orWhere('employee_id', 'like', "%{$search}%")
                    ->orWhere('email', 'like', "%{$search}%")
                    ->orWhere('phone', 'like', "%{$search}%")
                    ->orWhere('position', 'like', "%{$search}%")
                    ->orWhere('department', 'like', "%{$search}%");
            });
        }

        // Filter by department
        if ($request->has('department') && $request->department) {
            $query->where('department', $request->department);
        }

        // Filter by employment type
        if ($request->has('employment_type') && $request->employment_type) {
            $query->where('employment_type', $request->employment_type);
        }

        // Filter by active status
        if ($request->has('is_active')) {
            $query->where('is_active', filter_var($request->is_active, FILTER_VALIDATE_BOOLEAN));
        }

        // Sorting
        $sortBy = $request->get('sort_by', 'created_at');
        $sortOrder = $request->get('sort_order', 'desc');
        $query->orderBy($sortBy, $sortOrder);

        // Pagination
        $perPage = $request->get('per_page', 15);
        $employees = $query->paginate($perPage);

        return response()->json($employees);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'employee_id' => 'required|string|max:50|unique:employees,employee_id',
            'first_name' => 'required|string|max:255',
            'last_name' => 'required|string|max:255',
            'email' => 'nullable|email|max:255|unique:employees,email',
            'phone' => 'nullable|string|max:20',
            'date_of_birth' => 'nullable|date',
            'gender' => 'nullable|in:male,female,other',
            'address' => 'nullable|string',
            'city' => 'nullable|string|max:100',
            'state' => 'nullable|string|max:100',
            'postal_code' => 'nullable|string|max:20',
            'country' => 'nullable|string|max:100',
            'position' => 'nullable|string|max:255',
            'department' => 'nullable|string|max:255',
            'hire_date' => 'nullable|date',
            'termination_date' => 'nullable|date|after:hire_date',
            'employment_type' => 'required|in:full-time,part-time,contract,intern',
            'salary' => 'nullable|numeric|min:0',
            'emergency_contact_name' => 'nullable|string|max:255',
            'emergency_contact_phone' => 'nullable|string|max:20',
            'notes' => 'nullable|string',
            'create_user_account' => 'boolean',
            'user_email' => 'nullable|required_if:create_user_account,true|email|unique:users,email',
            'user_password' => 'nullable|required_if:create_user_account,true|string|min:8',
            'user_role' => 'nullable|required_if:create_user_account,true|in:admin,accountant,driver',
        ]);

        DB::beginTransaction();
        try {
            $user = null;
            
            // Create user account if requested
            if ($request->boolean('create_user_account')) {
                $user = User::create([
                    'name' => "{$validated['first_name']} {$validated['last_name']}",
                    'email' => $validated['user_email'],
                    'password' => Hash::make($validated['user_password']),
                    'role' => $validated['user_role'] ?? 'driver',
                    'is_active' => true,
                ]);
            }

            // Create employee
            $employeeData = collect($validated)->except(['create_user_account', 'user_email', 'user_password', 'user_role'])->toArray();
            if ($user) {
                $employeeData['user_id'] = $user->id;
            }

            $employee = Employee::create($employeeData);

            DB::commit();

            return response()->json([
                'message' => 'Employee created successfully',
                'employee' => $employee->load('user'),
            ], 201);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'message' => 'Failed to create employee',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Display the specified resource with all activities and transactions.
     */
    public function show(Employee $employee)
    {
        $employee->load(['user', 'assignedCustomers']);
        
        // Get all transactions created by this employee
        $transactions = $employee->transactions()
            ->with(['account', 'customer', 'creator'])
            ->orderBy('date', 'desc')
            ->orderBy('created_at', 'desc')
            ->get();

        // Get transaction statistics
        $stats = [
            'total_transactions' => $transactions->count(),
            'total_transaction_amount' => $employee->total_transaction_amount,
            'transaction_count' => $employee->transaction_count,
            'total_customers_assigned' => $employee->assignedCustomers()->count(),
            'total_sales' => (float) $transactions->whereHas('account', function ($query) {
                $query->where('account_type', 'revenue');
            })->sum('credit_amount'),
        ];

        // Get recent transactions (last 10)
        $recentTransactions = $transactions->take(10);

        // Group transactions by account type
        $transactionsByAccountType = $transactions->groupBy(function ($transaction) {
            return $transaction->account->account_type ?? 'unknown';
        })->map(function ($group) {
            return [
                'count' => $group->count(),
                'total_debit' => $group->sum('debit_amount'),
                'total_credit' => $group->sum('credit_amount'),
            ];
        });

        // Get assigned customers with their statistics
        $assignedCustomers = $employee->assignedCustomers()
            ->withCount('transactions')
            ->get()
            ->map(function ($customer) {
                return [
                    'id' => $customer->id,
                    'customer_code' => $customer->customer_code,
                    'full_name' => $customer->full_name,
                    'email' => $customer->email,
                    'phone' => $customer->phone,
                    'current_balance' => $customer->calculateCurrentBalance(),
                    'transaction_count' => $customer->transactions_count,
                ];
            });

        return response()->json([
            'employee' => $employee,
            'statistics' => $stats,
            'recent_transactions' => $recentTransactions,
            'transactions_by_account_type' => $transactionsByAccountType,
            'assigned_customers' => $assignedCustomers,
            'total_transactions_count' => $transactions->count(),
        ]);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, Employee $employee)
    {
        $validated = $request->validate([
            'employee_id' => ['required', 'string', 'max:50', Rule::unique('employees')->ignore($employee->id)],
            'first_name' => 'required|string|max:255',
            'last_name' => 'required|string|max:255',
            'email' => ['nullable', 'email', 'max:255', Rule::unique('employees')->ignore($employee->id)],
            'phone' => 'nullable|string|max:20',
            'date_of_birth' => 'nullable|date',
            'gender' => 'nullable|in:male,female,other',
            'address' => 'nullable|string',
            'city' => 'nullable|string|max:100',
            'state' => 'nullable|string|max:100',
            'postal_code' => 'nullable|string|max:20',
            'country' => 'nullable|string|max:100',
            'position' => 'nullable|string|max:255',
            'department' => 'nullable|string|max:255',
            'hire_date' => 'nullable|date',
            'termination_date' => 'nullable|date|after:hire_date',
            'employment_type' => 'required|in:full-time,part-time,contract,intern',
            'salary' => 'nullable|numeric|min:0',
            'emergency_contact_name' => 'nullable|string|max:255',
            'emergency_contact_phone' => 'nullable|string|max:20',
            'notes' => 'nullable|string',
            'is_active' => 'boolean',
            'create_user_account' => 'boolean',
            'user_email' => 'nullable|required_if:create_user_account,true|email|unique:users,email',
            'user_password' => 'nullable|required_if:create_user_account,true|string|min:8',
            'user_role' => 'nullable|required_if:create_user_account,true|in:admin,accountant,driver',
        ]);

        DB::beginTransaction();
        try {
            // Create user account if requested and employee doesn't have one
            if ($request->boolean('create_user_account') && !$employee->user_id) {
                $user = User::create([
                    'name' => "{$validated['first_name']} {$validated['last_name']}",
                    'email' => $validated['user_email'],
                    'password' => Hash::make($validated['user_password']),
                    'role' => $validated['user_role'] ?? 'driver',
                    'is_active' => true,
                ]);
                $validated['user_id'] = $user->id;
            }

            // Update employee
            $employeeData = collect($validated)->except(['create_user_account', 'user_email', 'user_password', 'user_role'])->toArray();
            $employee->update($employeeData);
            $employee->refresh()->load('user');

            DB::commit();

            return response()->json([
                'message' => 'Employee updated successfully',
                'employee' => $employee,
            ]);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'message' => 'Failed to update employee',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Employee $employee)
    {
        DB::beginTransaction();
        try {
            // Soft delete: deactivate employee instead of deleting
            $employee->update(['is_active' => false]);

            // Optionally, deactivate user account
            if ($employee->user_id) {
                $employee->user->update(['is_active' => false]);
            }

            DB::commit();

            return response()->json([
                'message' => 'Employee deactivated successfully',
            ]);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'message' => 'Failed to deactivate employee',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Get departments list.
     */
    public function getDepartments()
    {
        $departments = Employee::select('department')
            ->whereNotNull('department')
            ->distinct()
            ->orderBy('department')
            ->pluck('department');

        return response()->json($departments);
    }

    /**
     * Generate next employee ID.
     */
    public function generateEmployeeId()
    {
        $lastEmployee = Employee::orderBy('id', 'desc')->first();
        $nextNumber = $lastEmployee ? ((int) substr($lastEmployee->employee_id, -4)) + 1 : 1;
        $employeeId = 'EMP' . str_pad($nextNumber, 4, '0', STR_PAD_LEFT);

        return response()->json(['employee_id' => $employeeId]);
    }
}

