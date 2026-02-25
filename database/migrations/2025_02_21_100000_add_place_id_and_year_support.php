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
        Schema::table('sales_leads', function (Blueprint $table) {
            $table->foreignId('place_id')->nullable()->after('assigned_to')->constrained('places')->nullOnDelete();
        });

        Schema::table('service_leads', function (Blueprint $table) {
            $table->foreignId('place_id')->nullable()->after('assigned_to')->constrained('places')->nullOnDelete();
        });

        Schema::table('staff', function (Blueprint $table) {
            $table->foreignId('place_id')->nullable()->after('address')->constrained('places')->nullOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('sales_leads', function (Blueprint $table) {
            $table->dropForeign(['place_id']);
        });
        Schema::table('service_leads', function (Blueprint $table) {
            $table->dropForeign(['place_id']);
        });
        Schema::table('staff', function (Blueprint $table) {
            $table->dropForeign(['place_id']);
        });
    }
};
