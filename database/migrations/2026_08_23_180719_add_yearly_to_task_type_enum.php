<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::statement("
            ALTER TABLE tasks
            MODIFY task_type
            ENUM('daily', 'weekly', 'monthly', 'quarterly', 'yearly')
            NOT NULL
        ");
    }

    public function down(): void
    {
        DB::statement("
            ALTER TABLE tasks
            MODIFY task_type
            ENUM('daily', 'weekly', 'monthly', 'quarterly')
            NOT NULL
        ");
    }
};
