<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('company_profile', function (Blueprint $table) {
            $table->string('signature_path')->nullable();
            $table->string('seal_path')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('company_profile', function (Blueprint $table) {
             $table->dropColumn([
                'signature_path',
                'seal_path',
            ]);
        });
    }
};
