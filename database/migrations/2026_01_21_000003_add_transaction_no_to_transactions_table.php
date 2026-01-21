<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('transactions', function (Blueprint $table) {
            $table->string('transaction_no', 30)->nullable()->unique()->after('id');
        });

        // Backfill existing rows with a deterministic number based on id
        $transactions = DB::table('transactions')->select('id')->whereNull('transaction_no')->orderBy('id')->get();
        foreach ($transactions as $transaction) {
            $transactionNo = 'TRN' . str_pad((string) $transaction->id, 6, '0', STR_PAD_LEFT);
            DB::table('transactions')
                ->where('id', $transaction->id)
                ->update(['transaction_no' => $transactionNo]);
        }
    }

    public function down(): void
    {
        Schema::table('transactions', function (Blueprint $table) {
            $table->dropUnique(['transaction_no']);
            $table->dropColumn('transaction_no');
        });
    }
};
