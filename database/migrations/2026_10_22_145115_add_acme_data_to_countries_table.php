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
        Schema::table('countries', function (Blueprint $table) {
            $table->unsignedSmallInteger('acme_branches')->default(0)->comment('Number of ACME LTDA branches in the country');
            $table->unsignedBigInteger('acme_volume_usd')->default(0)->comment('Business volume in USD');
            $table->unsignedInteger('acme_customers')->default(0)->comment('Number of ACME customers in the country');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('countries', function (Blueprint $table) {
            $table->dropColumn(['acme_branches', 'acme_volume_usd', 'acme_customers']);
        });
    }
};
