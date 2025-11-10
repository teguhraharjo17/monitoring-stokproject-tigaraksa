<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class SubAssy extends Model
{
    use HasFactory;

    protected $table = 'sub_assies';

    protected $fillable = [
        'customer',
        'project',
        'part_number',
        'part_name',
        'wip_sebelumnya',
        'total_spk',
        'total_produksi',
        'wip_akhir',
        'produktivitas',
        'bulan',
        'tahun',
    ];

    public function details()
    {
        return $this->hasMany(SubAssyDetail::class);
    }

    public function scopeOfBulanTahun($query, $bulan, $tahun)
    {
        return $query->where('bulan', $bulan)->where('tahun', $tahun);
    }
}
