<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('lead_call_details', function (Blueprint $table) {
            $table->id();

            // Cascade: a call log has no meaning without its lead. Leads are
            // soft deleted, so this only fires on a true purge.
            $table->foreignId('lead_id')->constrained('leads')->cascadeOnDelete();

            $table->string('call_status', 30)->index();

            $table->text('remarks')->nullable();

            // nullOnDelete so removing a staff member never erases the call
            // history they recorded.
            $table->foreignId('called_by')->nullable()->constrained('users')->nullOnDelete();

            /*
             * Date and time are stored separately as specified. They are also
             * indexed together because the timeline orders by both — a single
             * datetime would be simpler, but this keeps the two independently
             * filterable ("all calls on the 10th", "all calls before noon").
             */
            $table->date('called_date');
            $table->time('called_time');

            $table->date('next_followup_date')->nullable();

            // Seconds, not minutes: "no answer" calls are routinely under a
            // minute and would round to zero.
            $table->unsignedInteger('duration')->nullable();

            $table->timestamps();
            $table->softDeletes();

            // Timeline: newest call for a lead, first.
            $table->index(['lead_id', 'called_date', 'called_time']);

            // "What follow-ups are pending" — the future scheduler's query.
            $table->index(['next_followup_date', 'lead_id']);

            // "My calls" listings and per-agent statistics.
            $table->index(['called_by', 'called_date']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('lead_call_details');
    }
};
