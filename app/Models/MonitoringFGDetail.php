<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class MonitoringFGDetail extends Model
{
    use HasFactory;

    protected $table = 'monitoring_fg_details';

    protected $fillable = [
        'fg_header_id',
        'tanggal',
        'in_qty_d',
        'in_qty_n',
        'out_qty_d',
        'out_qty_n',
        'balance_d',
        'balance_n',
    ];

    public function header()
    {
        return $this->belongsTo(MonitoringFGHeader::class, 'fg_header_id');
    }
}
