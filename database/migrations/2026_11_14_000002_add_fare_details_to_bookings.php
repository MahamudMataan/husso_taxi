<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('bookings', function (Blueprint $table) {
            $table->string('fare_type', 24)->nullable();
            $table->unsignedInteger('departure_supplement_cents')->default(0);
            $table->boolean('invoice_requested')->default(false);
            $table->json('invoice_details')->nullable();
            $table->uuid('request_token')->nullable()->unique();
        });

        Schema::create('fare_settings', function (Blueprint $table) {
            $table->id();
            $table->unsignedInteger('minimum_cents');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('fare_settings');

        Schema::table('bookings', function (Blueprint $table) {
            $table->dropUnique(['request_token']);
            $table->dropColumn(['fare_type', 'departure_supplement_cents', 'invoice_requested', 'invoice_details', 'request_token']);
        });
    }
};
