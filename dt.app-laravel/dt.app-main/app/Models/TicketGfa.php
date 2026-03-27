<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TicketGfa extends Model
{
    protected $table = 'tickets';

    protected $fillable = [
        'service_id',
        'agent_id',
        'guichet_id',
        'statut',
        'numero',
        'nom_client',
        'motif',
        'waiting_time',
        'called_at',
        'closed_at',
        'processing_time',
    ];

    protected function casts(): array
    {
        return [
            'waiting_time' => 'datetime',
            'called_at' => 'datetime',
            'closed_at' => 'datetime',
            'processing_time' => 'integer',
            'created_at' => 'datetime',
            'updated_at' => 'datetime',
        ];
    }

    public function service(): BelongsTo
    {
        return $this->belongsTo(ServiceGfa::class, 'service_id');
    }

    public function agent(): BelongsTo
    {
        return $this->belongsTo(AgentGfa::class, 'agent_id');
    }

    public function guichet(): BelongsTo
    {
        return $this->belongsTo(GuichetGfa::class, 'guichet_id');
    }
}
