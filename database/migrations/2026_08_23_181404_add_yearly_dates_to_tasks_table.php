<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('tasks', function (Blueprint $table) {
            $table->date('yearly_start_date')
                ->nullable()
                ->after('quarter_end_date');

            $table->date('yearly_end_date')
                ->nullable()
                ->after('yearly_start_date');
        });
    }

    public function down(): void
    {
        Schema::table('tasks', function (Blueprint $table) {
            $table->dropColumn([
                'yearly_start_date',
                'yearly_end_date',
            ]);
        });
    }
};