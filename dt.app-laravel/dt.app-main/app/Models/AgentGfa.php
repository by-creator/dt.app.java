<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class AgentGfa extends Model
{
    protected $table = 'agents';

    protected $fillable = [
        'nom',
        'prenom',
        'service_id',
        'guichet_id',
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

    public function guichet(): BelongsTo
    {
        return $this->belongsTo(GuichetGfa::class, 'guichet_id');
    }

    public function tickets(): HasMany
    {
        return $this->hasMany(TicketGfa::class, 'agent_id');
    }
}
