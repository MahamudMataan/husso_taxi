<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('bookings', function (Blueprint $table) {
            $table->id();
            $table->string('reference')->unique();
            $table->string('language', 2)->default('nl');
            $table->string('customer_name');
            $table->string('customer_email');
            $table->string('phone', 40);
            $table->string('pickup');
            $table->decimal('pickup_latitude', 10, 7);
            $table->decimal('pickup_longitude', 10, 7);
            $table->string('destination');
            $table->decimal('destination_latitude', 10, 7);
            $table->decimal('destination_longitude', 10, 7);
            $table->date('ride_date');
            $table->time('ride_time');
            $table->string('passengers', 10);
            $table->string('ride_type', 40);
            $table->decimal('distance_km', 8, 1);
            $table->unsignedInteger('duration_min');
            $table->decimal('base_fare', 8, 2);
            $table->decimal('km_rate', 8, 2);
            $table->decimal('minute_rate', 8, 2);
            $table->decimal('quoted_amount', 8, 2);
            $table->string('payment_method', 30);
            $table->string('payment_status', 40)->default('pending');
            $table->string('mollie_payment_id')->nullable();
            $table->timestamp('confirmed_at')->nullable();
            $table->timestamp('confirmation_emailed_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('bookings');
    }
};
