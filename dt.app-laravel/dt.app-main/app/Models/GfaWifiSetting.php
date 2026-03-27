<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class GfaWifiSetting extends Model
{
    protected $table = 'gfa_wifi_settings';

    protected $fillable = [
        'ssid',
        'password',
    ];

    public $timestamps = false;

    protected function casts(): array
    {
        return [
            'updated_at' => 'datetime',
        ];
    }
}
