<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Models\Tranksaksi;

class Operational extends Model
{
    use HasFactory;

    protected $table = 'operationals';

    protected $fillable = [
        'transaksi_id',
        'name',
        'amount',
        'description',
        'status',
    ];

    public function transaksi()
    {
        return $this->belongsTo(Tranksaksi::class, 'transaksi_id');
    }
}
