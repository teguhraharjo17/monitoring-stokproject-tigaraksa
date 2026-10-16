<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class MasterItem extends Model
{
    protected $table = 'master_items';

    protected $fillable = [
        'customer',
        'part_number',
        'nama_part',
        'kode_project',
        'is_active',
        'deactivated_at',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'deactivated_at' => 'datetime',
    ];

    /**
     * Scope: hanya item aktif
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * Scope: hanya item nonaktif
     */
    public function scopeInactive($query)
    {
        return $query->where('is_active', false);
    }
}
