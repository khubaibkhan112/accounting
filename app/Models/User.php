<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable
{
    /** @use HasFactory<\Database\Factories\UserFactory> */
    use HasFactory, Notifiable;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'email',
        'password',
        'role',
        'is_active',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'is_active' => 'boolean',
        ];
    }

    /**
     * Get the employee record associated with the user.
     */
    public function employee()
    {
        return $this->hasOne(Employee::class);
    }

    /**
     * Check if user is admin.
     */
    public function isAdmin(): bool
    {
        return $this->role === 'admin';
    }

    /**
     * Check if user is accountant.
     */
    public function isAccountant(): bool
    {
        return $this->role === 'accountant';
    }

    /**
     * Check if user is driver.
     */
    public function isDriver(): bool
    {
        return $this->role === 'driver';
    }

    /**
     * Check if user has permission for accounts.
     */
    public function canAccessAccounts(): bool
    {
        return $this->isAdmin() || $this->isAccountant();
    }

    /**
     * Check if user can view transactions.
     */
    public function canViewTransactions(): bool
    {
        return $this->isAdmin() || $this->isAccountant() || $this->isDriver();
    }

    /**
     * Check if user can view their own transactions only.
     */
    public function canViewOwnTransactionsOnly(): bool
    {
        return $this->isDriver();
    }

    /**
     * Check if user can create/edit/delete transactions.
     */
    public function canManageTransactions(): bool
    {
        return $this->isAdmin() || $this->isAccountant();
    }

    /**
     * Check if user can manage employees.
     */
    public function canManageEmployees(): bool
    {
        return $this->isAdmin();
    }

    /**
     * Check if user can view reports.
     */
    public function canViewReports(): bool
    {
        return $this->isAdmin() || $this->isAccountant();
    }
}
