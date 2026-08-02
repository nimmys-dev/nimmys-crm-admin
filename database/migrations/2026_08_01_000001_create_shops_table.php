<?php

use App\Enums\ShopStatus;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('shops', function (Blueprint $table) {
            $table->id();

            // Business identifier used on documents and in the mobile app.
            // Unique so it can be searched and quoted unambiguously.
            $table->string('code', 32)->unique();

            $table->string('name');

            // The user who runs this shop. nullOnDelete rather than cascade —
            // losing a manager must never delete the shop or its history.
            $table->foreignId('manager_id')
                ->nullable()
                ->constrained('users')
                ->nullOnDelete();

            $table->string('email')->nullable();
            $table->string('phone', 20)->nullable();

            $table->string('address_line')->nullable();
            $table->string('city', 100)->nullable()->index();
            $table->string('state', 100)->nullable();
            $table->string('postal_code', 20)->nullable();
            $table->string('country', 100)->nullable();

            $table->date('opened_on')->nullable();

            $table->string('status', 20)->default(ShopStatus::Active->value)->index();

            $table->text('notes')->nullable();

            $table->timestamps();

            // Shops accumulate leads, tasks and staff history. Soft deleting
            // keeps those references resolvable instead of orphaning them.
            $table->softDeletes();

            // The index listing filters by status and sorts by name; this
            // composite covers the common case without a filesort.
            $table->index(['status', 'name']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('shops');
    }
};
