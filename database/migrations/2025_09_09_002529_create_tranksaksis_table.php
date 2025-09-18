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
        Schema::create('transaksis', function (Blueprint $table) {
            $table->id();
            $table->foreignId('pasien_id')->constrained('pasiens');
            $table->foreignId('docter_id')->constrained('docters');
            $table->foreignId('dantel_id')->constrained('dantels');
            $table->decimal('total_amount', 15, 2); // Total pembayaran pasien (1jt)
            $table->decimal('net_amount', 15, 2); // Total setelah dikurangi modal (calculated)
            $table->text('description')->nullable();
            $table->enum('status', ['pending', 'sukses', 'gagal'])->default('pending');
            $table->timestamp('deleted_at')->nullable();
            $table->timestamps();

            // Index untuk performa query
            $table->index(['status', 'created_at']); // Query: transaksi pending hari ini
            $table->index(['pasien_id', 'status']); // Query: riwayat transaksi pasien
            $table->index(['docter_id', 'status']); // Query: transaksi dokter
            $table->index(['dantel_id', 'status']); // Query: transaksi dantel
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('transaksis');
    }
};
