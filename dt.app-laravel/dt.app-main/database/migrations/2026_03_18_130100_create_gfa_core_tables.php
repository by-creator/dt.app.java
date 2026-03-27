<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('services', function (Blueprint $table) {
            $table->id();
            $table->string('nom', 100);
            $table->string('code', 50)->nullable()->unique();
            $table->boolean('actif')->default(true);
        });

        Schema::create('guichets', function (Blueprint $table) {
            $table->id();
            $table->string('numero', 20);
            $table->string('infos', 20);
            $table->foreignId('service_id')->nullable()->constrained('services')->nullOnDelete();
            $table->boolean('actif')->default(true);
        });

        Schema::create('agents', function (Blueprint $table) {
            $table->id();
            $table->string('nom', 100);
            $table->string('prenom', 100)->nullable();
            $table->foreignId('service_id')->nullable()->constrained('services')->nullOnDelete();
            $table->foreignId('guichet_id')->nullable()->constrained('guichets')->nullOnDelete();
            $table->boolean('actif')->default(true);
        });

        Schema::create('tickets', function (Blueprint $table) {
            $table->id();
            $table->foreignId('service_id')->nullable()->constrained('services')->nullOnDelete();
            $table->foreignId('agent_id')->nullable()->constrained('agents')->nullOnDelete();
            $table->foreignId('guichet_id')->nullable()->constrained('guichets')->nullOnDelete();
            $table->string('statut', 50)->default('EN_ATTENTE');
            $table->string('numero', 20);
            $table->dateTime('waiting_time')->nullable();
            $table->dateTime('called_at')->nullable();
            $table->dateTime('closed_at')->nullable();
            $table->bigInteger('processing_time')->nullable();
            $table->timestamps();

            $table->index('statut', 'idx_ticket_statut');
            $table->index('service_id', 'idx_ticket_service');
            $table->index('created_at', 'idx_ticket_created');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tickets');
        Schema::dropIfExists('agents');
        Schema::dropIfExists('guichets');
        Schema::dropIfExists('services');
    }
};
