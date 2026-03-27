<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Codification extends Model
{
    protected $fillable = [
        'call_number',
        'manifest',
        'xls',
        'iftmin',
    ];
}
