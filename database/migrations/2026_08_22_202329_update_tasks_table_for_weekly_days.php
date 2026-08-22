<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('tasks', function (Blueprint $table) {

            // Remove old weekly day
            $table->dropColumn('week_day');

            // Weekly start day
            $table->enum('week_start_day', [
                'monday',
                'tuesday',
                'wednesday',
                'thursday',
                'friday',
                'saturday',
                'sunday',
            ])->nullable()->after('end_time');

            // Weekly end day
            $table->enum('week_end_day', [
                'monday',
                'tuesday',
                'wednesday',
                'thursday',
                'friday',
                'saturday',
                'sunday',
            ])->nullable()->after('week_start_day');
        });
    }

    public function down(): void
    {
        Schema::table('tasks', function (Blueprint $table) {

            $table->dropColumn([
                'week_start_day',
                'week_end_day',
            ]);

            $table->enum('week_day', [
                'sunday',
                'monday',
                'tuesday',
                'wednesday',
                'thursday',
                'friday',
                'saturday',
            ])->nullable();
        });
    }
};