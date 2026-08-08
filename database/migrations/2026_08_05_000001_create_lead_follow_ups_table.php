<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('lead_follow_ups', function (Blueprint $table) {
            $table->id();

            // Cascade: a follow-up has no meaning without its lead. Leads are
            // soft deleted, so this only fires on a true purge.
            $table->foreignId('lead_id')->constrained('leads')->cascadeOnDelete();

            // Who logged or owns the follow-up. nullOnDelete so removing a
            // staff member never erases the activity history.
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();

            $table->string('type', 20);

            $table->text('notes')->nullable();

            /*
             * One row covers both halves of a follow-up: scheduled_at is when
             * it should happen, completed_at is when it did. A row with a
             * scheduled_at and no completed_at is what "due" means.
             */
            $table->dateTime('scheduled_at')->nullable();
            $table->dateTime('completed_at')->nullable();

            $table->string('outcome')->nullable();

            $table->timestamps();

            // Drives the timeline on the lead page.
            $table->index(['lead_id', 'scheduled_at']);

            // Drives "my open follow-ups" on the dashboard.
            $table->index(['user_id', 'completed_at', 'scheduled_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('lead_follow_ups');
    }
};
