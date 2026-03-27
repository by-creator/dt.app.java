<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AuditLog extends Model
{
    protected $fillable = [
        'user_id',
        'user_name',
        'user_email',
        'user_role',
        'method',
        'url',
        'route_name',
        'controller_action',
        'ip_address',
        'user_agent',
        'payload',
        'query_params',
        'session_id',
        'response_status',
        'duration_ms',
    ];

    protected function casts(): array
    {
        return [
            'payload'      => 'array',
            'query_params' => 'array',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
