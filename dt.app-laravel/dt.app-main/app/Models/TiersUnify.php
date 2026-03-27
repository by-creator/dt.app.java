<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class TiersUnify extends Model
{
    protected $table = 'tiers_unify';

    protected $fillable = [
        'compte_ipaki',
        'compte_neptune',
        'raison_sociale',
        'created_at',
    ];

    public $timestamps = false;

    public const UPDATED_AT = null;

    protected function casts(): array
    {
        return [
            'created_at' => 'datetime',
        ];
    }
}
