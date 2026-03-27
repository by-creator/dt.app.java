<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('rapport_facturation_suivi_vides', function (Blueprint $table) {
            $table->id();
            $table->string('terminal')->nullable();
            $table->string('equipment_number')->nullable();
            $table->string('equipment_type_size', 50)->nullable();
            $table->string('event_code', 50)->nullable();
            $table->string('event_name')->nullable();
            $table->string('event_family')->nullable();
            $table->string('event_date', 100)->nullable();
            $table->string('booking_sec_no')->nullable();
            $table->timestamp('created_at')->useCurrent();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('rapport_facturation_suivi_vides');
    }
};
