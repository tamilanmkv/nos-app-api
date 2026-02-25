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
            $table->string('assigned_to', 50)->nullable()->after('status');
        });

        Schema::table('service_leads', function (Blueprint $table) {
            $table->string('assigned_to', 50)->nullable()->after('status');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('sales_leads', function (Blueprint $table) {
            $table->dropColumn('assigned_to');
        });

        Schema::table('service_leads', function (Blueprint $table) {
            $table->dropColumn('assigned_to');
        });
    }
};
