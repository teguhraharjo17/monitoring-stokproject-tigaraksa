<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class MasterCompany extends Model
{
    use HasFactory;

    protected $fillable = ['kode', 'keterangan'];
}
