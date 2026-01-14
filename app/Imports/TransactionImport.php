<?php

namespace App\Imports;

use App\Models\Customer;
use App\Models\Employee;
use App\Models\Account;
use App\Models\Transaction;
use App\Services\AuditService;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\ToCollection;
use Maatwebsite\Excel\Concerns\WithHeadingRow;
use Maatwebsite\Excel\Concerns\WithStartRow;
use Maatwebsite\Excel\Concerns\WithValidation;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class TransactionImport implements ToCollection, WithStartRow
{
    protected $errors = [];
    protected $imported = 0;
    protected $skipped = 0;
    protected $userId;
    protected $defaultDate;

    public function __construct($userId = null, $defaultDate = null)
    {
        $this->userId = $userId ?? auth()->id();
        $this->defaultDate = $defaultDate ?? now()->format('Y-m-d');
    }

    /**
     * Start reading from row 11 (actual data starts here based on Excel structure)
     */
    public function startRow(): int
    {
        return 11;
    }

    /**
     * Process the imported collection
     */
    public function collection(Collection $rows)
    {
        DB::beginTransaction();
        
        try {
            foreach ($rows as $index => $row) {
                $rowNumber = $index + 11; // +11 because startRow is 11 and index is 0-based
                
                try {
                    // Skip empty rows
                    if ($this->isEmptyRow($row)) {
                        continue;
                    }

                    // Extract data from row based on actual Excel structure (Row 9 has headers)
                    // Column A (0): Entry number/ID
                    $entryId = trim($row[0] ?? '');
                    $description = trim($row[1] ?? ''); // Column B - DESCRIPTION
                    $customerName = trim($row[2] ?? ''); // Column C - Customer Name (cstmr name)
                    $source = trim($row[3] ?? ''); // Column D - SOURCE
                    $employeeName = trim($row[4] ?? ''); // Column E - DRIVER/Employee Name
                    // Column F (5): ANEES - driver column
                    // Column G (6): BILAL - driver column
                    $debitH = $this->parseAmount($row[7] ?? 0); // Column H - FAZAL driver amount
                    // Column I (8): OFFICE
                    $creditJ = $this->parseAmount($row[9] ?? 0); // Column J - CSTMR (customer payment)
                    $amountK = $this->parseAmount($row[10] ?? 0); // Column K - JAWAHR driver amount
                    // Column L (11): KHALID driver amount
                    $amountM = $this->parseAmount($row[12] ?? 0); // Column M - WORK (main work amount)
                    // Column N (13): ACCOUNT BALANCE (formula)
                    $vehicleNumber = trim($row[15] ?? ''); // Column P - Vehicle number
                    // Column Q (16): Formula - skip
                    
                    // Determine debit/credit from amounts
                    // Priority: Use M (WORK) as main amount, or H/J/K for specific driver/customer
                    $debit = 0;
                    $credit = 0;
                    
                    if ($amountM > 0) {
                        // Main work amount - this is revenue (credit to revenue account)
                        $credit = $amountM;
                    } elseif ($debitH > 0) {
                        // Driver payment (debit)
                        $debit = $debitH;
                    } elseif ($creditJ > 0) {
                        // Customer payment (credit)
                        $credit = $creditJ;
                    } elseif ($amountK > 0) {
                        // Another driver amount
                        $debit = $amountK;
                    }
                    
                    // Use source from column D, or vehicle number, or default
                    if (!$source) {
                        $source = $vehicleNumber ?: 'import';
                    }
                    
                    // Reference: Use vehicle number or generate
                    $reference = $vehicleNumber ?: $this->generateReference(now()->format('Y-m-d'), $source);
                    
                    // Date - try to parse from entry ID or use default date
                    $date = $this->parseDate($entryId);
                    if (!$date) {
                        // Use default date provided by user
                        $date = $this->defaultDate;
                    }

                    // Validate required fields
                    if (!$date) {
                        $this->errors[] = "Row {$rowNumber}: Date is required";
                        $this->skipped++;
                        continue;
                    }

                    if (!$description) {
                        $this->errors[] = "Row {$rowNumber}: Description is required";
                        $this->skipped++;
                        continue;
                    }

                    // Get or create customer
                    $customerId = null;
                    if ($customerName) {
                        $customer = Customer::where('full_name', $customerName)
                            ->orWhere('company_name', $customerName)
                            ->first();
                        
                        if (!$customer) {
                            $customer = Customer::create([
                                'customer_code' => $this->generateCustomerCode($customerName),
                                'customer_type' => $this->guessCustomerType($customerName),
                                'full_name' => $customerName,
                                'company_name' => null,
                                'email' => null,
                                'phone' => null,
                                'is_active' => true,
                            ]);
                        }
                        $customerId = $customer->id;
                    }

                    // Get or create employee
                    $employeeId = null;
                    if ($employeeName) {
                        $employee = Employee::where('first_name', 'LIKE', "%{$employeeName}%")
                            ->orWhere('last_name', 'LIKE', "%{$employeeName}%")
                            ->orWhereRaw("CONCAT(first_name, ' ', last_name) LIKE ?", ["%{$employeeName}%"])
                            ->first();
                        
                        if (!$employee) {
                            // Try to split name
                            $nameParts = explode(' ', $employeeName, 2);
                            $firstName = $nameParts[0] ?? $employeeName;
                            $lastName = $nameParts[1] ?? '';

                            $employee = Employee::create([
                                'employee_id' => $this->generateEmployeeId(),
                                'first_name' => $firstName,
                                'last_name' => $lastName,
                                'email' => null,
                                'phone' => null,
                                'position' => 'Employee',
                                'department' => 'General',
                                'employment_type' => 'full-time',
                                'is_active' => true,
                            ]);
                        }
                        $employeeId = $employee->id;
                    }

                    // Get account based on transaction type
                    // If credit > 0, it's revenue (work done)
                    // If debit > 0, it's expense (payment to driver)
                    $account = null;
                    
                    if ($credit > 0) {
                        // Revenue account for work done
                        $account = Account::where('account_type', 'revenue')
                            ->where('is_active', true)
                            ->first();
                    } elseif ($debit > 0) {
                        // Expense account for driver payments
                        $account = Account::where('account_type', 'expense')
                            ->where('is_active', true)
                            ->first();
                    }
                    
                    // Fallback to any active account
                    if (!$account) {
                        $account = Account::where('is_active', true)->first();
                    }
                    
                    if (!$account) {
                        $this->errors[] = "Row {$rowNumber}: No account available. Please create at least one account.";
                        $this->skipped++;
                        continue;
                    }

                    // Create transaction
                    $transaction = Transaction::create([
                        'date' => $date,
                        'account_id' => $account->id,
                        'customer_id' => $customerId,
                        'employee_id' => $employeeId,
                        'description' => $description,
                        'debit_amount' => $debit,
                        'credit_amount' => $credit,
                        'reference_number' => $reference ?: $this->generateReference($date, $source),
                        'transaction_type' => $source ?: 'import',
                        'created_by' => $this->userId,
                    ]);

                    // Log audit
                    AuditService::logCreate($transaction, 'Transaction imported from Excel');

                    $this->imported++;
                } catch (\Exception $e) {
                    $this->errors[] = "Row {$rowNumber}: " . $e->getMessage();
                    $this->skipped++;
                    Log::error("Import error on row {$rowNumber}: " . $e->getMessage());
                }
            }

            DB::commit();
        } catch (\Exception $e) {
            DB::rollBack();
            $this->errors[] = "Import failed: " . $e->getMessage();
            Log::error("Import transaction failed: " . $e->getMessage());
        }
    }

    /**
     * Check if row is empty
     */
    protected function isEmptyRow($row): bool
    {
        foreach ($row as $cell) {
            if (!empty(trim($cell))) {
                return false;
            }
        }
        return true;
    }

    /**
     * Parse date from various formats
     */
    protected function parseDate($value)
    {
        if (empty($value)) {
            return null;
        }

        try {
            // If it's already a Carbon instance or DateTime
            if ($value instanceof \DateTime) {
                return Carbon::instance($value)->format('Y-m-d');
            }

            // If it's a numeric Excel date (days since 1900-01-01)
            if (is_numeric($value)) {
                $baseDate = Carbon::create(1900, 1, 1);
                return $baseDate->addDays($value - 2)->format('Y-m-d'); // Excel bug: treats 1900 as leap year
            }

            // Try to parse as date string
            return Carbon::parse($value)->format('Y-m-d');
        } catch (\Exception $e) {
            return null;
        }
    }

    /**
     * Parse amount from various formats
     */
    protected function parseAmount($value)
    {
        if (empty($value) || $value === '-') {
            return 0;
        }

        // Remove currency symbols and commas
        $value = preg_replace('/[^\d.-]/', '', (string) $value);
        
        return (float) $value;
    }

    /**
     * Generate customer code
     */
    protected function generateCustomerCode($name): string
    {
        $prefix = 'CUST';
        $suffix = strtoupper(substr(preg_replace('/[^A-Za-z0-9]/', '', $name), 0, 6));
        $number = Customer::count() + 1;
        
        return $prefix . str_pad($number, 4, '0', STR_PAD_LEFT) . $suffix;
    }

    /**
     * Generate employee ID
     */
    protected function generateEmployeeId(): string
    {
        $lastEmployee = Employee::orderBy('id', 'desc')->first();
        $number = $lastEmployee ? ((int) substr($lastEmployee->employee_id, -4)) + 1 : 1;
        
        return 'EMP' . str_pad($number, 4, '0', STR_PAD_LEFT);
    }

    /**
     * Guess customer type from name
     */
    protected function guessCustomerType($name): string
    {
        // Simple heuristic: if name contains common business indicators
        $businessIndicators = ['Ltd', 'Inc', 'Corp', 'LLC', 'Company', 'Co', 'Enterprises'];
        $nameUpper = strtoupper($name);
        
        foreach ($businessIndicators as $indicator) {
            if (strpos($nameUpper, strtoupper($indicator)) !== false) {
                return 'business';
            }
        }
        
        return 'individual';
    }

    /**
     * Generate reference number
     */
    protected function generateReference($date, $source): string
    {
        $dateStr = Carbon::parse($date)->format('Ymd');
        $sourceStr = strtoupper(substr(preg_replace('/[^A-Za-z0-9]/', '', $source), 0, 3));
        $random = str_pad(rand(1, 9999), 4, '0', STR_PAD_LEFT);
        
        return 'IMP-' . $dateStr . '-' . ($sourceStr ?: 'GEN') . '-' . $random;
    }

    /**
     * Get import results
     */
    public function getResults(): array
    {
        return [
            'imported' => $this->imported,
            'skipped' => $this->skipped,
            'errors' => $this->errors,
        ];
    }
}
