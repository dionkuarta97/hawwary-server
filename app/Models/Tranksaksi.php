<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Models\Pasien;
use App\Models\Docter;
use App\Models\Dantel;
use App\Models\Operational;

class Tranksaksi extends Model
{
    use HasFactory;

    protected $table = 'transaksis';

    protected $fillable = [
        'pasien_id',
        'docter_id',
        'dantel_id',
        'total_amount',
        'net_amount',
        'description',
        'status',
        'deleted_at',
    ];

    public function pasien()
    {
        return $this->belongsTo(Pasien::class);
    }

    public function docter()
    {
        return $this->belongsTo(Docter::class);
    }

    public function dantel()
    {
        return $this->belongsTo(Dantel::class);
    }

    public function operational()
    {
        return $this->hasOne(Operational::class, 'transaksi_id');
    }
}
