<?php

use App\Enums\LeadPriority;
use App\Enums\LeadStatus;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('leads', function (Blueprint $table) {
            $table->id();

            // Quotable business identifier, e.g. LEAD-0001.
            $table->string('reference', 32)->unique();

            $table->string('name');
            $table->string('company')->nullable();
            $table->string('email')->nullable();
            $table->string('phone', 20);
            $table->string('alternate_phone', 20)->nullable();
            $table->string('city', 100)->nullable();

            $table->string('source', 30)->nullable()->index();
            $table->string('status', 30)->default(LeadStatus::New->value);
            $table->string('priority', 20)->default(LeadPriority::Medium->value);

            // Estimated deal size. decimal, never float — money must not
            // carry binary rounding error.
            $table->decimal('value', 12, 2)->nullable();

            // Tenant scope. nullOnDelete so removing a shop never destroys
            // the pipeline history attached to it.
            $table->foreignId('shop_id')->nullable()->constrained('shops')->nullOnDelete();

            // Owner. This column drives every ownership check: an Employee
            // may only see and edit leads assigned to them.
            $table->foreignId('assigned_to')->nullable()->constrained('users')->nullOnDelete();

            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();

            /*
             * Denormalised from the earliest open follow-up. Kept in sync by
             * LeadService so "what is due today" is an indexed column lookup
             * rather than a correlated subquery on every dashboard render.
             */
            $table->date('next_follow_up_at')->nullable();

            $table->timestamp('last_contacted_at')->nullable();

            // Rich text from TinyMCE. Sanitised on the way in, so what is
            // stored is already safe to render.
            $table->longText('description')->nullable();

            $table->string('lost_reason')->nullable();
            $table->timestamp('closed_at')->nullable();

            $table->timestamps();
            $table->softDeletes();

            /*
             * The listing filters by status and orders by recency; the
             * "my leads" view adds owner. This composite covers both without
             * a filesort.
             */
            $table->index(['status', 'assigned_to']);

            // "Follow-ups due for me" — the single hottest dashboard query.
            $table->index(['assigned_to', 'next_follow_up_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('leads');
    }
};
