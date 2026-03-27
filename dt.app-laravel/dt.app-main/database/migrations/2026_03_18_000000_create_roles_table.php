<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('roles', function (Blueprint $table) {
            $table->id();
            $table->string('name')->unique();
            $table->timestamps();
        });

        $now = now();

        DB::table('roles')->insert([
            ['name' => 'USER', 'created_at' => $now, 'updated_at' => $now],
            ['name' => 'ADMIN', 'created_at' => $now, 'updated_at' => $now],
        ]);

        $userRoleId = DB::table('roles')->where('name', 'USER')->value('id');

        Schema::table('users', function (Blueprint $table) use ($userRoleId) {
            $table->foreignId('role_id')
                ->default($userRoleId)
                ->after('password')
                ->constrained('roles');
        });

        DB::table('users')
            ->whereNull('role_id')
            ->update(['role_id' => $userRoleId]);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropConstrainedForeignId('role_id');
        });

        Schema::dropIfExists('roles');
    }
};
