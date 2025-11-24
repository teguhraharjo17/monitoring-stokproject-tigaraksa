<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class SpkPackingDetail extends Model
{
    use HasFactory;

    protected $table = 'spk_packing_details';

    protected $fillable = [
        'spk_packing_header_id',
        'customer',
        'part_number',
        'nama_models',
        'qty_per_set_box',
        'level_stock',
        'stock_fg',
        'wip',
        'qty_spk_set',
        'refer_kanban_po',
        'keterangan'
    ];

    protected $casts = [
        'qty_spk_box' => 'float',
        'total' => 'integer'
    ];

    public function header()
    {
        return $this->belongsTo(SpkPackingHeader::class, 'spk_packing_header_id');
    }

    // Optional accessor if needed
    public function getQtySpkBoxAttribute()
    {
        if ($this->qty_per_set_box > 0) {
            return round($this->qty_spk_set / $this->qty_per_set_box, 1);
        }

        return 0;
    }

    public function getTotalAttribute()
    {
        return $this->stock_fg + $this->wip;
    }
}
