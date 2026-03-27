<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SuiviVide extends Model
{
    protected $table = 'rapport_facturation_suivi_vides';

    protected $fillable = [
        'terminal',
        'equipment_number',
        'equipment_type_size',
        'event_code',
        'event_name',
        'event_family',
        'event_date',
        'booking_sec_no',
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
