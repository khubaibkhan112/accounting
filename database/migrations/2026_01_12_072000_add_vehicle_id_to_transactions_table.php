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
        Schema::table('transactions', function (Blueprint $table) {
            $table->foreignId('vehicle_id')->nullable()->after('employee_id')->constrained('vehicles')->onDelete('set null');
            $table->index('vehicle_id');
            $table->index(['vehicle_id', 'date'], 'transactions_vehicle_date_index');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('transactions', function (Blueprint $table) {
            $table->dropForeign(['vehicle_id']);
            $table->dropIndex(['vehicle_id']);
            $table->dropIndex('transactions_vehicle_date_index');
            $table->dropColumn('vehicle_id');
        });
    }
};
