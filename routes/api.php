<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\EmployeeController;
use App\Http\Controllers\Api\AccountController;
use App\Http\Controllers\Api\CustomerController;
use App\Http\Controllers\Api\VehicleController;
use App\Http\Controllers\Api\TransactionController;
use App\Http\Controllers\Api\JournalEntryController;
use App\Http\Controllers\Api\LedgerController;
use App\Http\Controllers\Api\ReportController;
use App\Http\Controllers\Api\DashboardController;

// All API routes require authentication using web guard (session-based)
Route::middleware(['auth:web'])->group(function () {
    
    // Dashboard route
    Route::get('/dashboard', [DashboardController::class, 'index']);
    
// Employee routes
Route::prefix('employees')->group(function () {
    Route::get('/', [EmployeeController::class, 'index']);
    Route::post('/', [EmployeeController::class, 'store']);
    Route::get('/generate-id', [EmployeeController::class, 'generateEmployeeId']);
    Route::get('/departments', [EmployeeController::class, 'getDepartments']);
    Route::get('/{employee}', [EmployeeController::class, 'show']);
    Route::put('/{employee}', [EmployeeController::class, 'update']);
    Route::patch('/{employee}', [EmployeeController::class, 'update']);
    Route::delete('/{employee}', [EmployeeController::class, 'destroy']);
});

// Account routes
Route::prefix('accounts')->group(function () {
    // View routes - accessible to all authenticated users
    Route::get('/', [AccountController::class, 'index']);
    Route::get('/tree', [AccountController::class, 'tree']);
    Route::get('/types', [AccountController::class, 'types']);
    Route::get('/type/{type}', [AccountController::class, 'byType']);
    Route::get('/{account}', [AccountController::class, 'show']);
    Route::get('/{account}/balance-summary', [AccountController::class, 'balanceSummary']);
    
    // Modify routes - require admin or accountant role
    Route::post('/', [AccountController::class, 'store'])->middleware('role:admin,accountant');
    Route::put('/{account}', [AccountController::class, 'update'])->middleware('role:admin,accountant');
    Route::patch('/{account}', [AccountController::class, 'update'])->middleware('role:admin,accountant');
    Route::delete('/{account}', [AccountController::class, 'destroy'])->middleware('role:admin,accountant');
});

// Customer routes
Route::prefix('customers')->group(function () {
    Route::get('/', [CustomerController::class, 'index']);
    Route::post('/', [CustomerController::class, 'store']);
    Route::get('/generate-code', [CustomerController::class, 'generateCustomerCode']);
    Route::get('/{customer}', [CustomerController::class, 'show']);
    Route::get('/{customer}/transactions', [CustomerController::class, 'transactions']);
    Route::put('/{customer}', [CustomerController::class, 'update']);
    Route::patch('/{customer}', [CustomerController::class, 'update']);
    Route::delete('/{customer}', [CustomerController::class, 'destroy']);
});

// Vehicle routes
Route::prefix('vehicles')->group(function () {
    Route::get('/', [VehicleController::class, 'index']);
    Route::post('/', [VehicleController::class, 'store'])->middleware('role:admin,accountant');
    Route::get('/customer/{customerId}', [VehicleController::class, 'getByCustomer']);
    Route::get('/{vehicle}', [VehicleController::class, 'show']);
    Route::put('/{vehicle}', [VehicleController::class, 'update'])->middleware('role:admin,accountant');
    Route::patch('/{vehicle}', [VehicleController::class, 'update'])->middleware('role:admin,accountant');
    Route::delete('/{vehicle}', [VehicleController::class, 'destroy'])->middleware('role:admin,accountant');
});

// Transaction routes
Route::prefix('transactions')->group(function () {
    // View routes - accessible to all authenticated users
    Route::get('/', [TransactionController::class, 'index']);
    Route::get('/{transaction}', [TransactionController::class, 'show']);
    
    // Modify routes - require admin or accountant role
    Route::post('/', [TransactionController::class, 'store'])->middleware('role:admin,accountant');
    Route::put('/{transaction}', [TransactionController::class, 'update'])->middleware('role:admin,accountant');
    Route::patch('/{transaction}', [TransactionController::class, 'update'])->middleware('role:admin,accountant');
    Route::delete('/{transaction}', [TransactionController::class, 'destroy'])->middleware('role:admin,accountant');
});

// Journal Entry routes
Route::prefix('journal-entries')->group(function () {
    // View routes - accessible to all authenticated users
    Route::get('/', [JournalEntryController::class, 'index']);
    Route::get('/{journal_entry}', [JournalEntryController::class, 'show']);
    
    // Modify routes - require admin or accountant role
    Route::post('/', [JournalEntryController::class, 'store'])->middleware('role:admin,accountant');
    Route::put('/{journal_entry}', [JournalEntryController::class, 'update'])->middleware('role:admin,accountant');
    Route::patch('/{journal_entry}', [JournalEntryController::class, 'update'])->middleware('role:admin,accountant');
    Route::delete('/{journal_entry}', [JournalEntryController::class, 'destroy'])->middleware('role:admin,accountant');
});

// Ledger routes
Route::prefix('ledger')->group(function () {
    Route::get('/', [LedgerController::class, 'index']);
    Route::get('/account/{account}', [LedgerController::class, 'show']);
    Route::get('/export/excel', [LedgerController::class, 'exportExcel']);
    Route::get('/export/pdf', [LedgerController::class, 'exportPdf']);
});

// Report routes
Route::prefix('reports')->group(function () {
    Route::get('/trial-balance', [ReportController::class, 'trialBalance']);
    Route::get('/trial-balance/export/excel', [ReportController::class, 'exportTrialBalanceExcel']);
    Route::get('/trial-balance/export/pdf', [ReportController::class, 'exportTrialBalancePdf']);
    Route::get('/balance-sheet', [ReportController::class, 'balanceSheet']);
    Route::get('/balance-sheet/export/excel', [ReportController::class, 'exportBalanceSheetExcel']);
    Route::get('/balance-sheet/export/pdf', [ReportController::class, 'exportBalanceSheetPdf']);
    Route::get('/income-statement', [ReportController::class, 'incomeStatement']);
    Route::get('/income-statement/export/excel', [ReportController::class, 'exportIncomeStatementExcel']);
    Route::get('/income-statement/export/pdf', [ReportController::class, 'exportIncomeStatementPdf']);
    Route::get('/profit-loss', [ReportController::class, 'incomeStatement']); // Alias
});

// Import routes (admin and accountant only)
Route::prefix('import')->middleware('role:admin,accountant')->group(function () {
    Route::post('/transactions', [\App\Http\Controllers\Api\ImportController::class, 'import']);
    Route::post('/preview', [\App\Http\Controllers\Api\ImportController::class, 'preview']);
});

// Settings routes (admin only)
Route::prefix('settings')->middleware('role:admin')->group(function () {
    Route::get('/', [\App\Http\Controllers\Api\SettingsController::class, 'index']);
    Route::get('/{key}', [\App\Http\Controllers\Api\SettingsController::class, 'show']);
    Route::put('/{key}', [\App\Http\Controllers\Api\SettingsController::class, 'update']);
    Route::patch('/{key}', [\App\Http\Controllers\Api\SettingsController::class, 'update']);
    Route::post('/update-multiple', [\App\Http\Controllers\Api\SettingsController::class, 'updateMultiple']);
});

// Audit Log routes (admin only)
Route::prefix('audit-logs')->middleware('role:admin')->group(function () {
    Route::get('/', [\App\Http\Controllers\Api\AuditLogController::class, 'index']);
    Route::get('/{auditLog}', [\App\Http\Controllers\Api\AuditLogController::class, 'show']);
    Route::get('/model/{type}/{id}', [\App\Http\Controllers\Api\AuditLogController::class, 'forModel']);
});

}); // End auth middleware group
