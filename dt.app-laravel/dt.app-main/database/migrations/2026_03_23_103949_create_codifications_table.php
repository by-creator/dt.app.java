<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('codifications', function (Blueprint $table) {
            $table->id();
            $table->string('call_number');
            $table->string('manifest')->nullable();
            $table->string('xlsx')->nullable();
            $table->string('iftmin')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('codifications');
    }
};
