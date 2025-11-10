<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class MonitoringMIPDetail extends Model
{
    use HasFactory;

    protected $table = 'monitoring_mip_details';

    protected $fillable = [
        'header_id',
        'tanggal',
        'in_qty',
        'out_qty',
        'balance',
    ];

    public function header()
    {
        return $this->belongsTo(MonitoringMIPHeader::class, 'header_id');
    }
}
