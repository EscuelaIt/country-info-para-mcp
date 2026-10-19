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
        Schema::create('countries', function (Blueprint $table) {
            $table->id();

            // Relación 1:N con Continente
            $table->foreignId('continent_id')
                ->constrained('continents')
                ->cascadeOnDelete();

            // Nomenclatura y Códigos ISO
            $table->string('name')->index();                  // Nombre común (ej. España, France)
            $table->string('official_name');                  // Nombre oficial completo
            $table->string('cca2', 2)->unique()->index();     // Código ISO 3166-1 alfa-2 (ej. ES)
            $table->string('cca3', 3)->unique()->index();     // Código ISO 3166-1 alfa-3 (ej. ESP)
            $table->string('flag_emoji', 8)->nullable();      // Bandera en emoji (ej. 🇪🇸)

            // Geografía básica
            $table->string('capital')->nullable()->index();
            $table->decimal('latitude', 10, 7)->nullable();
            $table->decimal('longitude', 10, 7)->nullable();
            $table->unsignedBigInteger('area_km2')->default(0);

            // Demografía e Indicadores
            $table->unsignedBigInteger('population')->default(0)->index();
            $table->decimal('gini', 4, 1)->nullable();        // Coeficiente de desigualdad

            // Idiomas locales estructurados en formato clave-valor
            $table->json('languages')->nullable();            // {"spa": "Español", "cat": "Catalán"}

            // Resumen textual para inyectar directamente en respuestas o recursos MCP
            $table->text('summary')->nullable();

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('countries');
    }
};
