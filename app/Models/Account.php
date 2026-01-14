<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Account extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'account_code',
        'account_name',
        'account_type',
        'parent_account_id',
        'opening_balance',
        'description',
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
            'opening_balance' => 'decimal:2',
            'is_active' => 'boolean',
        ];
    }

    /**
     * Get the parent account.
     */
    public function parentAccount(): BelongsTo
    {
        return $this->belongsTo(Account::class, 'parent_account_id');
    }

    /**
     * Get the child accounts.
     */
    public function childAccounts(): HasMany
    {
        return $this->hasMany(Account::class, 'parent_account_id');
    }

    /**
     * Get all transactions for this account.
     */
    public function transactions(): HasMany
    {
        return $this->hasMany(Transaction::class);
    }

    /**
     * Get all journal entry items for this account.
     */
    public function journalEntryItems(): HasMany
    {
        return $this->hasMany(JournalEntryItem::class);
    }

    /**
     * Get the current balance for this account.
     * This calculates: opening_balance + sum of debits - sum of credits
     */
    public function getCurrentBalanceAttribute(): float
    {
        $debitTotal = $this->transactions()->sum('debit_amount');
        $creditTotal = $this->transactions()->sum('credit_amount');
        
        // For assets and expenses: opening + debits - credits
        // For liabilities, equity, and revenue: opening + credits - debits
        if (in_array($this->account_type, ['asset', 'expense'])) {
            return (float) $this->opening_balance + (float) $debitTotal - (float) $creditTotal;
        } else {
            return (float) $this->opening_balance + (float) $creditTotal - (float) $debitTotal;
        }
    }

    /**
     * Get the total debit amount for this account.
     */
    public function getTotalDebitsAttribute(): float
    {
        return (float) $this->transactions()->sum('debit_amount');
    }

    /**
     * Get the total credit amount for this account.
     */
    public function getTotalCreditsAttribute(): float
    {
        return (float) $this->transactions()->sum('credit_amount');
    }

    /**
     * Get formatted account code with name.
     */
    public function getFullNameAttribute(): string
    {
        return "{$this->account_code} - {$this->account_name}";
    }

    /**
     * Check if account has transactions.
     */
    public function hasTransactions(): bool
    {
        return $this->transactions()->exists();
    }

    /**
     * Check if account has child accounts.
     */
    public function hasChildren(): bool
    {
        return $this->childAccounts()->exists();
    }

    /**
     * Scope a query to only include active accounts.
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * Scope a query to only include inactive accounts.
     */
    public function scopeInactive($query)
    {
        return $query->where('is_active', false);
    }

    /**
     * Scope a query to filter by account type.
     */
    public function scopeOfType($query, string $type)
    {
        return $query->where('account_type', $type);
    }

    /**
     * Scope a query to get parent accounts only (no parent).
     */
    public function scopeParentAccounts($query)
    {
        return $query->whereNull('parent_account_id');
    }

    /**
     * Scope a query to get child accounts of a specific parent.
     */
    public function scopeChildrenOf($query, int $parentId)
    {
        return $query->where('parent_account_id', $parentId);
    }

    /**
     * Scope a query to search by code or name.
     */
    public function scopeSearch($query, string $search)
    {
        return $query->where(function ($q) use ($search) {
            $q->where('account_code', 'like', "%{$search}%")
              ->orWhere('account_name', 'like', "%{$search}%");
        });
    }

    /**
     * Validation rules for account creation/update.
     */
    public static function rules(array $except = []): array
    {
        $rules = [
            'account_code' => ['required', 'string', 'max:50', 'unique:accounts,account_code'],
            'account_name' => ['required', 'string', 'max:255'],
            'account_type' => ['required', 'in:asset,liability,equity,revenue,expense'],
            'parent_account_id' => ['nullable', 'exists:accounts,id'],
            'opening_balance' => ['nullable', 'numeric', 'min:-999999999.99', 'max:999999999.99'],
            'description' => ['nullable', 'string'],
            'is_active' => ['boolean'],
        ];

        foreach ($except as $field) {
            if (isset($rules[$field])) {
                unset($rules[$field]);
            }
        }

        return $rules;
    }

    /**
     * Validate that parent account exists and is not the same account.
     */
    public static function validateParentAccount(int $accountId, ?int $parentId): bool
    {
        if ($parentId === null) {
            return true;
        }

        if ($accountId === $parentId) {
            return false; // Cannot be parent of itself
        }

        // Check if parent exists and is active
        $parent = self::find($parentId);
        if (!$parent || !$parent->is_active) {
            return false;
        }

        // Check for circular reference (parent cannot be a child of this account)
        $currentAccount = self::find($accountId);
        if ($currentAccount && $currentAccount->isAncestorOf($parentId)) {
            return false;
        }

        return true;
    }

    /**
     * Check if this account is an ancestor of another account.
     */
    public function isAncestorOf(int $accountId): bool
    {
        $child = self::find($accountId);
        if (!$child || !$child->parent_account_id) {
            return false;
        }

        if ($child->parent_account_id === $this->id) {
            return true;
        }

        $parent = self::find($child->parent_account_id);
        if ($parent) {
            return $this->isAncestorOf($parent->id);
        }

        return false;
    }

    /**
     * Get the full hierarchy path (e.g., "Parent > Child > Grandchild").
     */
    public function getHierarchyPathAttribute(): string
    {
        $path = [$this->account_name];
        $parent = $this->parentAccount;

        while ($parent) {
            array_unshift($path, $parent->account_name);
            $parent = $parent->parentAccount;
        }

        return implode(' > ', $path);
    }

    /**
     * Get accounts organized in a tree structure.
     */
    public static function getTreeStructure()
    {
        $parents = self::parentAccounts()->active()->with('childAccounts')->get();
        
        return $parents->map(function ($parent) {
            return self::buildTree($parent);
        });
    }

    /**
     * Recursively build account tree.
     */
    protected static function buildTree(Account $account): array
    {
        $children = $account->childAccounts()->active()->get()->map(function ($child) {
            return self::buildTree($child);
        });

        return [
            'id' => $account->id,
            'account_code' => $account->account_code,
            'account_name' => $account->account_name,
            'account_type' => $account->account_type,
            'current_balance' => $account->current_balance,
            'children' => $children->toArray(),
        ];
    }
}

