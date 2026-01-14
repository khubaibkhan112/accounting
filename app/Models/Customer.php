<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Customer extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'customer_code',
        'company_name',
        'first_name',
        'last_name',
        'email',
        'phone',
        'mobile',
        'billing_address',
        'billing_city',
        'billing_state',
        'billing_postal_code',
        'billing_country',
        'shipping_address',
        'shipping_city',
        'shipping_state',
        'shipping_postal_code',
        'shipping_country',
        'tax_id',
        'customer_type',
        'payment_terms',
        'credit_limit',
        'opening_balance',
        'current_balance',
        'notes',
        'assigned_to',
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
            'credit_limit' => 'decimal:2',
            'opening_balance' => 'decimal:2',
            'current_balance' => 'decimal:2',
            'is_active' => 'boolean',
        ];
    }

    /**
     * Get the employee assigned to this customer.
     */
    public function assignedEmployee(): BelongsTo
    {
        return $this->belongsTo(Employee::class, 'assigned_to');
    }

    /**
     * Get all transactions for this customer.
     */
    public function transactions(): HasMany
    {
        return $this->hasMany(Transaction::class);
    }

    /**
     * Get all vehicles for this customer.
     */
    public function vehicles(): HasMany
    {
        return $this->hasMany(Vehicle::class);
    }

    /**
     * Get the customer's full name.
     */
    public function getFullNameAttribute(): string
    {
        if ($this->customer_type === 'business' && $this->company_name) {
            return $this->company_name;
        }
        return trim("{$this->first_name} {$this->last_name}");
    }

    /**
     * Get the customer's display name (code + name).
     */
    public function getDisplayNameAttribute(): string
    {
        return "{$this->customer_code} - {$this->full_name}";
    }

    /**
     * Calculate the current balance from transactions.
     */
    public function calculateCurrentBalance(): float
    {
        $debitTotal = $this->transactions()->sum('debit_amount');
        $creditTotal = $this->transactions()->sum('credit_amount');
        
        // For customers: opening_balance + debits (what they owe) - credits (what they paid)
        return (float) $this->opening_balance + (float) $debitTotal - (float) $creditTotal;
    }

    /**
     * Update current balance from transactions.
     */
    public function updateBalance(): void
    {
        $this->current_balance = $this->calculateCurrentBalance();
        $this->save();
    }

    /**
     * Get total sales (credits to revenue accounts).
     */
    public function getTotalSalesAttribute(): float
    {
        return (float) $this->transactions()
            ->whereHas('account', function ($query) {
                $query->where('account_type', 'revenue');
            })
            ->sum('credit_amount');
    }

    /**
     * Get total payments (debits to cash/bank accounts).
     */
    public function getTotalPaymentsAttribute(): float
    {
        return (float) $this->transactions()
            ->whereHas('account', function ($query) {
                $query->whereIn('account_type', ['asset']);
            })
            ->sum('debit_amount');
    }

    /**
     * Check if customer has exceeded credit limit.
     */
    public function hasExceededCreditLimit(): bool
    {
        if (!$this->credit_limit) {
            return false;
        }
        return abs($this->current_balance) > $this->credit_limit;
    }

    /**
     * Scope a query to only include active customers.
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * Scope a query to only include inactive customers.
     */
    public function scopeInactive($query)
    {
        return $query->where('is_active', false);
    }

    /**
     * Scope a query to filter by customer type.
     */
    public function scopeOfType($query, string $type)
    {
        return $query->where('customer_type', $type);
    }

    /**
     * Scope a query to search customers.
     */
    public function scopeSearch($query, string $search)
    {
        return $query->where(function ($q) use ($search) {
            $q->where('customer_code', 'like', "%{$search}%")
              ->orWhere('company_name', 'like', "%{$search}%")
              ->orWhere('first_name', 'like', "%{$search}%")
              ->orWhere('last_name', 'like', "%{$search}%")
              ->orWhere('email', 'like', "%{$search}%")
              ->orWhere('phone', 'like', "%{$search}%");
        });
    }
}

