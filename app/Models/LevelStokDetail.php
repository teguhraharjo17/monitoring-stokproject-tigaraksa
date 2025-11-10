<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class LevelStokDetail extends Model
{
    use HasFactory;

    protected $table = 'level_stok_detail';

    protected $fillable = [
        'level_stok_id',
        'customer',
        'kode_projek',
        'part_number',
        'models',
        'min',
        'safety_mip',
        'safety_fg',
        'max',
        'qty_set_box',
    ];

    public function levelStok()
    {
        return $this->belongsTo(LevelStok::class, 'level_stok_id');
    }
}
