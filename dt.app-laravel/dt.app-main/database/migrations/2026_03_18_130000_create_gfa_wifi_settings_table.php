<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('gfa_wifi_settings', function (Blueprint $table) {
            $table->id();
            $table->string('ssid', 100);
            $table->string('password', 100)->default('');
            $table->timestamp('updated_at')->useCurrent()->useCurrentOnUpdate();
        });

        DB::table('gfa_wifi_settings')->updateOrInsert(
            ['id' => 1],
            [
                'ssid' => 'DakarTerminal_WiFi',
                'password' => '',
                'updated_at' => now(),
            ],
        );
    }

    public function down(): void
    {
        Schema::dropIfExists('gfa_wifi_settings');
    }
};
