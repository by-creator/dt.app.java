<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('rapport_facturation_suivi_stationnements', function (Blueprint $table) {
            $table->id();
            $table->string('terminal')->nullable();
            $table->string('billing_date', 20)->nullable();
            $table->string('shipowner')->nullable();
            $table->string('bl_number')->nullable();
            $table->string('item_number')->nullable();
            $table->string('item_type')->nullable();
            $table->string('type')->nullable();
            $table->string('entry_date', 20)->nullable();
            $table->string('exit_date', 20)->nullable();
            $table->decimal('days_since_in', 10, 2)->nullable();
            $table->timestamp('created_at')->useCurrent();

            $table->index('terminal');
            $table->index('bl_number');
            $table->index('billing_date');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('rapport_facturation_suivi_stationnements');
    }
};
