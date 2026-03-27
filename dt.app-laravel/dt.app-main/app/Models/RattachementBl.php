<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class RattachementBl extends Model
{
    protected $fillable = [
        'user_id',
        'nom',
        'prenom',
        'email',
        'bl',
        'maison',
        'motif_rejet',
        'statut',
        'type',
        'pourcentage',
        'time_elapsed',
    ];

    protected function casts(): array
    {
        return [
            'pourcentage' => 'decimal:2',
            'time_elapsed' => 'integer',
            'created_at' => 'datetime',
            'updated_at' => 'datetime',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
