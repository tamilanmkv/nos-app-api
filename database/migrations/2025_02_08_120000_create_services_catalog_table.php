<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     * Catalog of services (e.g. AC Service, RO Service) with types - not service leads/bookings.
     */
    public function up(): void
    {
        Schema::create('services_catalog', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->text('description')->nullable();
            $table->json('service_types')->nullable(); // [{ id, name, description, price, duration }]
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('services_catalog');
    }
};
