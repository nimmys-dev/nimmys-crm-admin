<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('tasks', function (Blueprint $table) {

            $table->id();

            $table->string('title');

            $table->foreignId('assigned_to')
                ->constrained('users')
                ->cascadeOnDelete();

            $table->foreignId('approved_by')
                ->constrained('users')
                ->cascadeOnDelete();

            $table->enum('task_type', [
                'daily',
                'weekly',
                'monthly',
                'quarterly',
            ])->default('daily');

            /*
            |--------------------------------------------------------------------------
            | Daily
            |--------------------------------------------------------------------------
            */
            $table->time('start_time')->nullable();
            $table->time('end_time')->nullable();

            /*
            |--------------------------------------------------------------------------
            | Weekly
            |--------------------------------------------------------------------------
            */
            $table->enum('week_day', [
                'sunday',
                'monday',
                'tuesday',
                'wednesday',
                'thursday',
                'friday',
                'saturday',
            ])->nullable();

            /*
            |--------------------------------------------------------------------------
            | Monthly
            |--------------------------------------------------------------------------
            */
            $table->date('monthly_start_date')->nullable();
            $table->date('monthly_end_date')->nullable();

            /*
            |--------------------------------------------------------------------------
            | Quarterly
            |--------------------------------------------------------------------------
            */
            $table->enum('quarter', [
                'q1',
                'q2',
                'q3',
                'q4',
            ])->nullable();

            $table->date('quarter_start_date')->nullable();
            $table->date('quarter_end_date')->nullable();

            $table->text('description')->nullable();

            $table->enum('status', [
                'pending',
                'in_progress',
                'completed',
            ])->default('pending');

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tasks');
    }
};