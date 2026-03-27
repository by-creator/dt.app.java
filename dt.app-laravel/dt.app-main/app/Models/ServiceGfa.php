<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ServiceGfa extends Model
{
    protected $table = 'services';

    protected $fillable = [
        'nom',
        'code',
        'actif',
    ];

    public $timestamps = false;

    protected function casts(): array
    {
        return [
            'actif' => 'boolean',
        ];
    }

    public function guichets(): HasMany
    {
        return $this->hasMany(GuichetGfa::class, 'service_id');
    }

    public function agents(): HasMany
    {
        return $this->hasMany(AgentGfa::class, 'service_id');
    }

    public function tickets(): HasMany
    {
        return $this->hasMany(TicketGfa::class, 'service_id');
    }
}
