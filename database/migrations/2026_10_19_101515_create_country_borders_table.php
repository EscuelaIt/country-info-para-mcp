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
        Schema::create('country_borders', function (Blueprint $table) {
            $table->id();

            // País de origen y país limítrofe
            $table->foreignId('country_id')
                ->constrained('countries')
                ->cascadeOnDelete();

            $table->foreignId('neighbor_id')
                ->constrained('countries')
                ->cascadeOnDelete();

            $table->timestamps();

            // Evitar duplicados de la misma dirección
            $table->unique(['country_id', 'neighbor_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('country_borders');
    }
};
