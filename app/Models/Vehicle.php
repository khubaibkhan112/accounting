<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Vehicle extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'customer_id',
        'vehicle_number',
        'chassis_number',
        'make',
        'model',
        'year',
        'color',
        'notes',
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
            'year' => 'integer',
            'is_active' => 'boolean',
        ];
    }

    /**
     * Get the customer that owns this vehicle.
     */
    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }

    /**
     * Get all transactions for this vehicle.
     */
    public function transactions(): HasMany
    {
        return $this->hasMany(Transaction::class);
    }

    /**
     * Get the vehicle's display name.
     */
    public function getDisplayNameAttribute(): string
    {
        $parts = [];
        if ($this->make) $parts[] = $this->make;
        if ($this->model) $parts[] = $this->model;
        if ($this->year) $parts[] = $this->year;
        $name = !empty($parts) ? implode(' ', $parts) : 'Vehicle';
        return "{$this->vehicle_number} - {$name}";
    }

    /**
     * Get the vehicle's full identifier.
     */
    public function getFullIdentifierAttribute(): string
    {
        return "{$this->vehicle_number} ({$this->chassis_number})";
    }

    /**
     * Scope a query to only include active vehicles.
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * Scope a query to filter by customer.
     */
    public function scopeForCustomer($query, int $customerId)
    {
        return $query->where('customer_id', $customerId);
    }

    /**
     * Scope a query to search vehicles.
     */
    public function scopeSearch($query, string $search)
    {
        return $query->where(function ($q) use ($search) {
            $q->where('vehicle_number', 'like', "%{$search}%")
              ->orWhere('chassis_number', 'like', "%{$search}%")
              ->orWhere('make', 'like', "%{$search}%")
              ->orWhere('model', 'like', "%{$search}%");
        });
    }
}
