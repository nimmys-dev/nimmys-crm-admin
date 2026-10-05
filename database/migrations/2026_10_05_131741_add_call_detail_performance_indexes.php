<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('lead_call_details', function (Blueprint $table) {
            $table->index(
                ['lead_id', 'called_date', 'called_time'],
                'lead_call_details_lead_called_index'
            );

            $table->index(
                ['lead_id', 'next_followup_date'],
                'lead_call_details_lead_followup_index'
            );

            $table->index(
                ['called_by', 'called_date'],
                'lead_call_details_caller_date_index'
            );
        });
    }

    public function down(): void
    {
        Schema::table('lead_call_details', function (Blueprint $table) {
            $table->dropIndex('lead_call_details_lead_called_index');
            $table->dropIndex('lead_call_details_lead_followup_index');
            $table->dropIndex('lead_call_details_caller_date_index');
        });
    }
};
