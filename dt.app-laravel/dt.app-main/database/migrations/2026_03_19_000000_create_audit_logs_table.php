<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('audit_logs', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('user_id')->nullable();
            $table->string('user_name')->nullable();
            $table->string('user_email')->nullable();
            $table->string('user_role', 100)->nullable();
            $table->string('method', 10);
            $table->string('url', 1000);
            $table->string('route_name', 255)->nullable();
            $table->string('controller_action', 255)->nullable();
            $table->string('ip_address', 45)->nullable();
            $table->text('user_agent')->nullable();
            $table->json('payload')->nullable();
            $table->json('query_params')->nullable();
            $table->string('session_id', 255)->nullable();
            $table->unsignedSmallInteger('response_status')->nullable();
            $table->unsignedInteger('duration_ms')->nullable();
            $table->timestamps();

            $table->index('created_at');
            $table->index('user_id');
            $table->index('method');
            $table->index('response_status');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('audit_logs');
    }
};
