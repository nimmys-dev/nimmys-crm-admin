<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('quotations', function (Blueprint $table) {
            $table->id();

            // unique(), not just indexed: one quotation per lead. Creating a
            // second one edits this row rather than inserting another.
            $table->foreignId('lead_id')->unique()->constrained()->cascadeOnDelete();

            // Quotable business identifier, e.g. QTN-0001.
            $table->string('reference', 32)->unique();

            /*
             * Captured independently of the lead rather than read live from
             * it: a quotation is a document that was actually sent, and must
             * keep showing the address it was addressed to even if the
             * lead's own details change afterwards.
             */
            $table->string('customer_name');
            $table->text('customer_address')->nullable();

            $table->date('issue_date');
            $table->date('valid_until')->nullable();

            // Money, never float — binary rounding error has no place in a
            // total a customer is quoted.
            $table->decimal('subtotal', 12, 2)->default(0);
            $table->decimal('discount_percent', 5, 2)->nullable();
            $table->decimal('tax_percent', 5, 2)->nullable();
            $table->decimal('total', 12, 2)->default(0);

            $table->text('terms')->nullable();

            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();

            /*
             * No softDeletes(): lead_id is unique, and a soft-deleted row
             * would go on occupying that slot forever — the lead could
             * never get a new quotation after its first one was deleted.
             * A quotation is a recreatable working document, not an audit
             * trail, so a plain delete is the right shape here.
             */
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('quotations');
    }
};
