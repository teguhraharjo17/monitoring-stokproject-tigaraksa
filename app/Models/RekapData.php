<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class RekapData extends Model
{
    protected $table = 'rekap_data';

    protected $fillable = [
        'bulan',
        'tahun',
        'part_number',
        'customer',
        'kode_project',
        'models',
        'stock_awal_mip',
        'stock_awal_fg',
        'wip_spk_sa',
        'total_stock',
        'os_bulan_lalu',
        'po_bulan_ini',
        'total_qty_bulan_ini',
        'selisih_stock',
    ];

    public function scopeBulan($query, $bulan)
    {
        return $query->where('bulan', $bulan);
    }

    public function scopeTahun($query, $tahun)
    {
        return $query->where('tahun', $tahun);
    }
}
