<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Update existing data first - convert 'employee' and 'viewer' to 'driver'
        DB::table('users')
            ->whereIn('role', ['employee', 'viewer'])
            ->update(['role' => 'driver']);

        // Drop the old enum and create a new one (MySQL only)
        if (DB::getDriverName() === 'mysql') {
            DB::statement("ALTER TABLE users MODIFY COLUMN role ENUM('admin', 'accountant', 'driver') DEFAULT 'driver'");
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Convert 'driver' back to 'employee' for rollback
        DB::table('users')
            ->where('role', 'driver')
            ->update(['role' => 'employee']);

        // Revert to original enum (MySQL only)
        if (DB::getDriverName() === 'mysql') {
            DB::statement("ALTER TABLE users MODIFY COLUMN role ENUM('admin', 'accountant', 'employee', 'viewer') DEFAULT 'viewer'");
        }
    }
};
