<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class MonitoringMIPHeader extends Model
{
    use HasFactory;

    protected $table = 'monitoring_mip_headers';

    protected $fillable = [
        'bulan',
        'tahun',
        'customer',
        'project',
        'part_number',
        'part_name',
        'stock_awal',
        'total_in',
        'total_out',
        'level_min',
        'level_safety',
        'level_max',
    ];

    public function details()
    {
        return $this->hasMany(MonitoringMIPDetail::class, 'header_id');
    }
}
