<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class LevelStok extends Model
{
    use HasFactory;

    protected $table = 'level_stok';

    protected $fillable = [
        'bulan',
        'tahun',
        'jumlah_hari_kerja_atas_100',
        'jumlah_hari_kerja_bawah_100',
    ];

    public function details()
    {
        return $this->hasMany(LevelStokDetail::class, 'level_stok_id');
    }
}
