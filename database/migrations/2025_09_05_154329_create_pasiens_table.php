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
        Schema::create('pasiens', function (Blueprint $table) {
            $table->id();
            $table->integer('no_rm')->unique();
            $table->string('nama');
            $table->string('domisili');
            $table->string('no_hp')->nullable();
            $table->string('nik')->unique()->nullable();
            $table->timestamp('deleted_at')->nullable();
            $table->timestamps();

            // Index untuk performa query
            $table->index(['nama']); // Query: pasien per nama
            $table->index(['domisili']); // Query: pasien per daerah
            $table->index(['no_rm']); // Query: pasien per no rm
            $table->index(['created_at']); // Query: pasien baru per periode
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('pasiens');
    }
};
