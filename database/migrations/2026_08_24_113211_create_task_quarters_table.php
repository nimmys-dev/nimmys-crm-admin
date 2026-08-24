<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('task_quarters', function (Blueprint $table) {
            $table->id();

            $table->foreignId('task_id')
                ->constrained('tasks')
                ->cascadeOnDelete();

            $table->string('quarter'); // q1, q2, q3, q4

            $table->date('start_date');
            $table->date('end_date');

            $table->timestamps();

            $table->index(['task_id', 'quarter']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('task_quarters');
    }
};
