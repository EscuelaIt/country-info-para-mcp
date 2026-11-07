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
        Schema::create('trips', function (Blueprint $table) {
            $table->id();

            // Propietario del viaje (el usuario que lo registra)
            $table->foreignId('user_id')
                ->constrained()
                ->cascadeOnDelete();

            // País (sede de ACME) que se visita
            $table->foreignId('country_id')
                ->constrained()
                ->cascadeOnDelete();

            $table->date('starts_on');
            $table->date('ends_on');
            $table->string('purpose');
            $table->string('status')->default('planned')->index(); // planned | completed | cancelled

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('trips');
    }
};
