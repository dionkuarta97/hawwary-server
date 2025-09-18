<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Models\Tranksaksi;
use App\Models\AddtionalFees;

class FeeDistribution extends Model
{
    use HasFactory;
    protected $fillable = [
        'transaksi_id',
        'additional_fee_id',
        'recipient_type',
        'recipient_id',
        'percentage',
        'amount',
    ];

    public function transaksi()
    {
        return $this->belongsTo(Tranksaksi::class);
    }
    public function additionalFee()
    {
        return $this->belongsTo(AddtionalFees::class);
    }
}
