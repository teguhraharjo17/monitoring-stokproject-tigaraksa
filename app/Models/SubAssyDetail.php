<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class SubAssyDetail extends Model
{
    use HasFactory;

    protected $table = 'sub_assy_details';

    protected $fillable = [
        'sub_assy_id',
        'tanggal',
        'tipe',
        'jumlah',
    ];

    public function subAssy()
    {
        return $this->belongsTo(SubAssy::class);
    }

    public function scopeOfTipe($query, $tipe)
    {
        return $query->where('tipe', $tipe);
    }

    public function scopeOfTanggal($query, $tanggal)
    {
        return $query->where('tanggal', $tanggal);
    }
}
