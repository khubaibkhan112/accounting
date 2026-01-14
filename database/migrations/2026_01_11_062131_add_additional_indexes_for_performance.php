<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Add additional indexes to transactions table
        Schema::table('transactions', function (Blueprint $table) {
            // Composite index for customer date queries
            try {
                $table->index(['customer_id', 'date'], 'transactions_customer_date_index');
            } catch (\Exception $e) {
                // Index may already exist
            }
            
            // Composite index for employee date queries
            try {
                $table->index(['employee_id', 'date'], 'transactions_employee_date_index');
            } catch (\Exception $e) {
                // Index may already exist
            }
            
            // Index for transaction_type
            try {
                $table->index('transaction_type', 'transactions_transaction_type_index');
            } catch (\Exception $e) {
                // Index may already exist
            }
            
            // Composite index for created_by date queries
            try {
                $table->index(['created_by', 'date'], 'transactions_created_by_date_index');
            } catch (\Exception $e) {
                // Index may already exist
            }
        });

        // Add indexes to accounts table
        Schema::table('accounts', function (Blueprint $table) {
            // Composite index for type and active status
            try {
                $table->index(['account_type', 'is_active'], 'accounts_type_active_index');
            } catch (\Exception $e) {
                // Index may already exist
            }
        });

        // Add indexes to customers table
        Schema::table('customers', function (Blueprint $table) {
            // Composite index for type and active
            try {
                $table->index(['customer_type', 'is_active'], 'customers_type_active_index');
            } catch (\Exception $e) {
                // Index may already exist
            }
            
            // Composite index for assigned_to and active
            try {
                $table->index(['assigned_to', 'is_active'], 'customers_assigned_active_index');
            } catch (\Exception $e) {
                // Index may already exist
            }
        });

        // Add indexes to employees table
        Schema::table('employees', function (Blueprint $table) {
            // Composite index for department and active
            try {
                $table->index(['department', 'is_active'], 'employees_department_active_index');
            } catch (\Exception $e) {
                // Index may already exist
            }
            
            // Index for position
            try {
                $table->index('position', 'employees_position_index');
            } catch (\Exception $e) {
                // Index may already exist
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('transactions', function (Blueprint $table) {
            $table->dropIndex('transactions_customer_date_index');
            $table->dropIndex('transactions_employee_date_index');
            $table->dropIndex('transactions_transaction_type_index');
            $table->dropIndex('transactions_created_by_date_index');
        });

        Schema::table('accounts', function (Blueprint $table) {
            $table->dropIndex('accounts_type_active_index');
        });

        Schema::table('customers', function (Blueprint $table) {
            $table->dropIndex('customers_type_active_index');
            $table->dropIndex('customers_assigned_active_index');
        });

        Schema::table('employees', function (Blueprint $table) {
            $table->dropIndex('employees_department_active_index');
            $table->dropIndex('employees_position_index');
        });
    }

};
