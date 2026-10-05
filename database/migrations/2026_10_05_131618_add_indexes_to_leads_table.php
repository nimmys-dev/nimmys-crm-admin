<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('leads', function (Blueprint $table) {
            $table->index(
                ['assigned_to', 'status'],
                'leads_assigned_status_index'
            );

            $table->index(
                ['assigned_to', 'created_at'],
                'leads_assigned_created_index'
            );

            $table->index(
                ['assigned_to', 'next_follow_up_at'],
                'leads_assigned_followup_index'
            );
        });
    }

    public function down(): void
    {
        Schema::table('leads', function (Blueprint $table) {
            $table->dropIndex('leads_assigned_status_index');
            $table->dropIndex('leads_assigned_created_index');
            $table->dropIndex('leads_assigned_followup_index');
        });
    }
};
