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
        Schema::create('service_leads', function (Blueprint $table) {
            $table->id();
            $table->string('booking_id', 50)->unique()->index();
            $table->string('service_id', 50)->index();
            $table->string('service_name');
            $table->string('service_type_id', 50)->nullable();
            $table->string('service_type_name')->nullable();
            $table->text('service_type_description')->nullable();
            $table->decimal('service_price', 12, 2)->nullable();
            $table->string('service_duration')->nullable();
            $table->date('booking_date')->nullable();
            $table->string('time_slot')->nullable();
            $table->text('address')->nullable();
            $table->string('street')->nullable();
            $table->string('city')->nullable();
            $table->string('district')->nullable();
            $table->string('state')->nullable();
            $table->string('pincode', 10)->nullable();
            $table->string('customer_name')->nullable();
            $table->string('phone', 20)->nullable()->index();
            $table->string('email')->nullable();
            $table->json('coordinates')->nullable();
            $table->string('status', 20)->default('pending')->index();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('service_leads');
    }
};
