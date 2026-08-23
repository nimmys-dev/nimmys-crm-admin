<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::statement("
            ALTER TABLE tasks
            MODIFY COLUMN status ENUM(
                'pending',
                'upcoming',
                'ongoing',
                'overdue',
                'completed',
                'approved',
                'closed'
            ) NOT NULL DEFAULT 'pending'
        ");
    }

    public function down(): void
    {
        //
    }
};
