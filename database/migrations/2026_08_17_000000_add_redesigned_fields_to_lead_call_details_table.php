<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('lead_call_details', function (Blueprint $table) {
            $table->boolean('interest')->nullable()->after('duration');
            $table->text('reason')->nullable()->after('interest');
            $table->boolean('is_item_sold')->nullable()->after('reason');
            $table->string('invoice_number', 100)->nullable()->after('is_item_sold');
            $table->string('invoice_file_path')->nullable()->after('invoice_number');
        });

        // Migrate historical status values to 'answered' or 'not_answered'
        DB::table('lead_call_details')->whereIn('call_status', [
            'connected', 'interested', 'not_interested', 'converted', 'call_back',
        ])->update(['call_status' => 'answered']);

        DB::table('lead_call_details')->whereIn('call_status', [
            'not_connected', 'busy', 'wrong_number', 'switched_off', 'lost',
        ])->update(['call_status' => 'not_answered']);
    }

    public function down(): void
    {
        Schema::table('lead_call_details', function (Blueprint $table) {
            $table->dropColumn([
                'interest',
                'reason',
                'is_item_sold',
                'invoice_number',
                'invoice_file_path',
            ]);
        });
    }
};
