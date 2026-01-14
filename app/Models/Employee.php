<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Employee extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'employee_id',
        'first_name',
        'last_name',
        'email',
        'phone',
        'date_of_birth',
        'gender',
        'address',
        'city',
        'state',
        'postal_code',
        'country',
        'position',
        'department',
        'hire_date',
        'termination_date',
        'employment_type',
        'salary',
        'emergency_contact_name',
        'emergency_contact_phone',
        'notes',
        'user_id',
        'is_active',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'date_of_birth' => 'date',
            'hire_date' => 'date',
            'termination_date' => 'date',
            'salary' => 'decimal:2',
            'is_active' => 'boolean',
        ];
    }

    /**
     * Get the user that owns the employee.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get the employee's full name.
     */
    public function getFullNameAttribute(): string
    {
        return "{$this->first_name} {$this->last_name}";
    }

    /**
     * Check if employee has login access.
     */
    public function hasLoginAccess(): bool
    {
        return !is_null($this->user_id);
    }

    /**
     * Scope a query to only include active employees.
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * Scope a query to only include inactive employees.
     */
    public function scopeInactive($query)
    {
        return $query->where('is_active', false);
    }

    /**
     * Get all transactions created by this employee.
     */
    public function transactions(): HasMany
    {
        return $this->hasMany(Transaction::class, 'employee_id');
    }

    /**
     * Get all customers assigned to this employee.
     */
    public function assignedCustomers(): HasMany
    {
        return $this->hasMany(Customer::class, 'assigned_to');
    }

    /**
     * Get total transaction amount for this employee.
     */
    public function getTotalTransactionAmountAttribute(): float
    {
        return (float) $this->transactions()
            ->selectRaw('SUM(debit_amount + credit_amount) as total')
            ->value('total') ?? 0;
    }

    /**
     * Get transaction count for this employee.
     */
    public function getTransactionCountAttribute(): int
    {
        return $this->transactions()->count();
    }
}

