<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class SpkPackingHeader extends Model
{
    use HasFactory;

    protected $table = 'spk_packing_headers';

    protected $fillable = [
        'tanggal',
        'bulan',
        'tahun',
        'tanggal_proses',
        'created_by',
        'approved_ppic_at',
        'approved_ppic_path',
        'approved_mip_at',
        'approved_mip_path',
        'approved_fg_at',
        'approved_fg_path',
        'approved_packing_member_at',
        'approved_packing_member_path',
        'approved_diketahui_at',
        'approved_diketahui_path',
    ];

    public function details()
    {
        return $this->hasMany(SpkPackingDetail::class, 'spk_packing_header_id');
    }

    public function getTanggalFormattedAttribute()
    {
        return \Carbon\Carbon::parse($this->tanggal)->translatedFormat('d F Y');
    }
}
