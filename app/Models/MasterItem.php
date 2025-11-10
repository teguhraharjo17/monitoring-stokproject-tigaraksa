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
    ];
}
