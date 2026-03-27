<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class GuichetGfa extends Model
{
    protected $table = 'guichets';

    protected $fillable = [
        'numero',
        'infos',
        'service_id',
        'actif',
    ];

    public $timestamps = false;

    protected function casts(): array
    {
        return [
            'actif' => 'boolean',
        ];
    }

    public function service(): BelongsTo
    {
        return $this->belongsTo(ServiceGfa::class, 'service_id');
    }

    public function agents(): HasMany
    {
        return $this->hasMany(AgentGfa::class, 'guichet_id');
    }

    public function tickets(): HasMany
    {
        return $this->hasMany(TicketGfa::class, 'guichet_id');
    }
}
