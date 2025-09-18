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
        Schema::create('fee_distributions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('transaksi_id')->constrained('transaksis')->onDelete('cascade');
            $table->foreignId('additional_fee_id')->constrained('addtional_fees');
            $table->string('recipient_type'); // Auto-generated dari additional_fees.type
            $table->unsignedBigInteger('recipient_id'); // ID dokter/dantel/klinik
            $table->decimal('percentage', 5, 2); // Persentase dari additional_fees
            $table->decimal('amount', 15, 2); // Jumlah uang yang diterima
            $table->timestamps();

            // Index untuk performa query
            $table->index(['recipient_type', 'recipient_id']);
            $table->index(['transaksi_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('fee_distributions');
    }
};
