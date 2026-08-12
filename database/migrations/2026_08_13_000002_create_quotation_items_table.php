<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('quotation_items', function (Blueprint $table) {
            $table->id();

            // A line item has no meaning without its quotation, and the
            // parent is already soft-deleted for recoverability, so a hard
            // cascade here is safe.
            $table->foreignId('quotation_id')->constrained()->cascadeOnDelete();

            // Free-text item/product name — there is no catalogue to link.
            $table->string('description');

            $table->decimal('quantity', 10, 2);
            $table->decimal('rate', 12, 2);

            // Denormalised quantity × rate. Stored rather than computed on
            // read so a rate change on the catalogue (were one ever added)
            // can never silently reprice a quotation already sent out.
            $table->decimal('amount', 12, 2);

            // Preserves the order the items were entered in, since the form
            // lets rows be added and removed freely.
            $table->unsignedInteger('sort_order')->default(0);

            $table->timestamps();

            $table->index(['quotation_id', 'sort_order']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('quotation_items');
    }
};
