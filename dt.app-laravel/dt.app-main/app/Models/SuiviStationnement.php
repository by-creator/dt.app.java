<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SuiviStationnement extends Model
{
    protected $table = 'rapport_facturation_suivi_stationnements';

    public $timestamps = false;

    protected $fillable = [
        'terminal',
        'billing_date',
        'shipowner',
        'bl_number',
        'item_number',
        'item_type',
        'type',
        'entry_date',
        'exit_date',
        'days_since_in',
    ];

    protected static function booted(): void
    {
        static::creating(function (self $model) {
            if (empty($model->created_at)) {
                $model->created_at = now();
            }
        });
    }
}
