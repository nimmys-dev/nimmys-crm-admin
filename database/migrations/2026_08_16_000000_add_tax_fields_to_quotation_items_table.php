<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('quotation_items', function (Blueprint $table) {
            $table->decimal('tax_percent', 5, 2)->default(18.00)->after('rate');
            $table->decimal('basic_rate', 12, 2)->default(0)->after('tax_percent');
            $table->decimal('tax_amount', 12, 2)->default(0)->after('basic_rate');
        });
    }

    public function down(): void
    {
        Schema::table('quotation_items', function (Blueprint $table) {
            $table->dropColumn(['tax_percent', 'basic_rate', 'tax_amount']);
        });
    }
};
