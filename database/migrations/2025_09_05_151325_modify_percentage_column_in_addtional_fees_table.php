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
        Schema::table('addtional_fees', function (Blueprint $table) {
            $table->decimal('percentage', 5, 2)->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('addtional_fees', function (Blueprint $table) {
            $table->decimal('percentage', 2, 2)->change();
        });
    }
};
