<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('bookings', function (Blueprint $table) {
            if (! Schema::hasColumn('bookings', 'tracking_token')) {
                $table->string('tracking_token', 64)->nullable()->unique()->after('reference');
            }

            if (! Schema::hasColumn('bookings', 'driver_phone')) {
                $table->string('driver_phone', 40)->nullable()->after('driver_name');
            }

            if (! Schema::hasColumn('bookings', 'driver_vehicle')) {
                $table->string('driver_vehicle')->nullable()->after('driver_phone');
            }
        });

        Schema::table('booking_notifications', function (Blueprint $table) {
            if (! Schema::hasColumn('booking_notifications', 'message')) {
                $table->text('message')->nullable()->after('recipient');
            }

            if (! Schema::hasColumn('booking_notifications', 'error_message')) {
                $table->text('error_message')->nullable()->after('message');
            }
        });

        DB::table('bookings')
            ->whereNull('tracking_token')
            ->orderBy('id')
            ->each(function (object $booking) {
                DB::table('bookings')
                    ->where('id', $booking->id)
                    ->update(['tracking_token' => Str::lower(Str::random(48))]);
            });
    }

    public function down(): void
    {
        Schema::table('booking_notifications', function (Blueprint $table) {
            foreach (['message', 'error_message'] as $column) {
                if (Schema::hasColumn('booking_notifications', $column)) {
                    $table->dropColumn($column);
                }
            }
        });

        Schema::table('bookings', function (Blueprint $table) {
            foreach (['tracking_token', 'driver_phone', 'driver_vehicle'] as $column) {
                if (Schema::hasColumn('bookings', $column)) {
                    $table->dropColumn($column);
                }
            }
        });
    }
};
