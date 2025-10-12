<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Models\Tranksaksi;
use App\Models\AddtionalFees;
use App\Models\Docter;
use App\Models\Dantel;

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

    protected $appends = ['recipient'];

    // Hide relasi individual dari JSON response karena sudah ada accessor 'recipient'
    protected $hidden = ['docter', 'dantel'];

    public function transaksi()
    {
        return $this->belongsTo(Tranksaksi::class);
    }

    public function additionalFee()
    {
        return $this->belongsTo(AddtionalFees::class);
    }

    // Relasi ke Docter
    public function docter()
    {
        return $this->belongsTo(Docter::class, 'recipient_id');
    }

    // Relasi ke Dantel
    public function dantel()
    {
        return $this->belongsTo(Dantel::class, 'recipient_id');
    }

    // Accessor untuk mendapatkan recipient berdasarkan type
    public function getRecipientAttribute()
    {
        if (!$this->recipient_id) {
            return null;
        }

        // Normalisasi recipient_type
        $type = strtolower(str_replace(['-', '_', ' '], '', $this->recipient_type));

        // Map recipient_type ke relasi yang sesuai
        if (in_array($type, ['docter', 'dokter'])) {
            return $this->docter;
        } elseif ($type === 'dantel') {
            return $this->dantel;
        }

        return null;
    }

    // Method untuk eager load recipient berdasarkan type
    public function scopeWithRecipient($query)
    {
        return $query->with(['docter', 'dantel']);
    }
}
