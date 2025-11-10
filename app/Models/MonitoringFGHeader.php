<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class MonitoringFGHeader extends Model
{
    use HasFactory;

    protected $table = 'monitoring_fg_headers';

    protected $fillable = [
        'customer',
        'project',
        'part_number',
        'part_name',
        'bulan',
        'tahun',
        'stock_awal',
        'total_in',
        'total_out',
        'level_min',
        'level_safety',
        'level_max',
    ];

    public function details()
    {
        return $this->hasMany(MonitoringFGDetail::class, 'fg_header_id');
    }
}
