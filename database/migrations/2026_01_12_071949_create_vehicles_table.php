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
        Schema::create('vehicles', function (Blueprint $table) {
            $table->id();
            $table->foreignId('customer_id')->constrained('customers')->onDelete('cascade');
            $table->string('vehicle_number')->comment('License plate number');
            $table->string('chassis_number')->unique()->comment('Vehicle chassis/VIN number');
            $table->string('make')->nullable()->comment('Vehicle manufacturer');
            $table->string('model')->nullable()->comment('Vehicle model');
            $table->year('year')->nullable()->comment('Manufacturing year');
            $table->string('color')->nullable();
            $table->text('notes')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->index('customer_id');
            $table->index('vehicle_number');
            $table->index('chassis_number');
            $table->index('is_active');
            $table->index(['customer_id', 'is_active']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('vehicles');
    }
};
