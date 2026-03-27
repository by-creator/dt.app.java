<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('tiers_unify', function (Blueprint $table) {
            $table->id();
            $table->string('compte_ipaki', 50);
            $table->string('compte_neptune', 50)->nullable();
            $table->string('raison_sociale');
            $table->timestamp('created_at')->useCurrent();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tiers_unify');
    }
};
