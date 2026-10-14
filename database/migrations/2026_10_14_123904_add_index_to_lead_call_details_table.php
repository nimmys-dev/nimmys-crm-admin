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
                ['lead_id', 'deleted_at', 'id'],
                'lcd_lead_deleted_id_idx'
            );
        });
    }

    public function down(): void
    {
        Schema::table('lead_call_details', function (Blueprint $table) {
            $table->dropIndex('lcd_lead_deleted_id_idx');
        });
    }
};