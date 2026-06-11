<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('bookings', function (Blueprint $table) {
            if (! Schema::hasColumn('bookings', 'tracking_token')) {
                $table->string('tracking_token', 64)->nullable()->unique()->after('reference');
            }

            if (! Schema::hasColumn('bookings', 'booking_status')) {
                $table->string('booking_status', 40)->default('new')->after('payment_status')->index();
            }

            if (! Schema::hasColumn('bookings', 'driver_phone')) {
                $table->string('driver_phone', 40)->nullable()->after('driver_name');
            }

            if (! Schema::hasColumn('bookings', 'driver_vehicle')) {
                $table->string('driver_vehicle')->nullable()->after('driver_phone');
            }

            if (! Schema::hasColumn('bookings', 'driver_eta_min')) {
                $table->unsignedSmallInteger('driver_eta_min')->nullable()->after('driver_vehicle');
            }

            if (! Schema::hasColumn('bookings', 'driver_latitude')) {
                $table->decimal('driver_latitude', 10, 7)->nullable()->after('driver_eta_min');
            }

            if (! Schema::hasColumn('bookings', 'driver_longitude')) {
                $table->decimal('driver_longitude', 10, 7)->nullable()->after('driver_latitude');
            }

            if (! Schema::hasColumn('bookings', 'payment_collected_at')) {
                $table->timestamp('payment_collected_at')->nullable()->after('mollie_payment_id');
            }

            if (! Schema::hasColumn('bookings', 'customer_notes')) {
                $table->text('customer_notes')->nullable()->after('confirmation_emailed_at');
            }

            if (! Schema::hasColumn('bookings', 'dispatch_notes')) {
                $table->text('dispatch_notes')->nullable()->after('customer_notes');
            }
        });

        if (! Schema::hasTable('drivers')) {
            Schema::create('drivers', function (Blueprint $table) {
                $table->id();
                $table->string('name');
                $table->string('phone', 40)->nullable();
                $table->string('vehicle_label')->nullable();
                $table->string('status', 30)->default('offline')->index();
                $table->decimal('current_latitude', 10, 7)->nullable();
                $table->decimal('current_longitude', 10, 7)->nullable();
                $table->timestamp('last_seen_at')->nullable();
                $table->timestamps();
            });
        }

        if (! Schema::hasTable('customer_profiles')) {
            Schema::create('customer_profiles', function (Blueprint $table) {
                $table->id();
                $table->string('name');
                $table->string('email')->unique();
                $table->string('phone', 40)->nullable();
                $table->unsignedInteger('total_rides')->default(0);
                $table->decimal('total_spending', 10, 2)->default(0);
                $table->string('preferred_payment_method', 40)->nullable();
                $table->timestamp('last_booking_at')->nullable();
                $table->text('notes')->nullable();
                $table->timestamps();
            });
        }

        if (! Schema::hasTable('booking_notifications')) {
            Schema::create('booking_notifications', function (Blueprint $table) {
                $table->id();
                $table->foreignId('booking_id')->constrained()->cascadeOnDelete();
                $table->string('event', 60);
                $table->string('channel', 30)->default('email');
                $table->string('recipient');
                $table->text('message')->nullable();
                $table->text('error_message')->nullable();
                $table->string('status', 30)->default('queued')->index();
                $table->timestamp('sent_at')->nullable();
                $table->timestamps();
            });
        }

        if (! Schema::hasTable('driver_location_updates')) {
            Schema::create('driver_location_updates', function (Blueprint $table) {
                $table->id();
                $table->string('driver_name');
                $table->foreignId('booking_id')->nullable()->constrained()->nullOnDelete();
                $table->decimal('latitude', 10, 7);
                $table->decimal('longitude', 10, 7);
                $table->unsignedSmallInteger('eta_min')->nullable();
                $table->timestamp('recorded_at')->index();
                $table->timestamps();
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('driver_location_updates');
        Schema::dropIfExists('booking_notifications');
        Schema::dropIfExists('customer_profiles');
        Schema::dropIfExists('drivers');

        Schema::table('bookings', function (Blueprint $table) {
            $columns = [
                'booking_status',
                'tracking_token',
                'driver_phone',
                'driver_vehicle',
                'driver_eta_min',
                'driver_latitude',
                'driver_longitude',
                'payment_collected_at',
                'customer_notes',
                'dispatch_notes',
            ];

            foreach ($columns as $column) {
                if (Schema::hasColumn('bookings', $column)) {
                    $table->dropColumn($column);
                }
            }
        });
    }
};
