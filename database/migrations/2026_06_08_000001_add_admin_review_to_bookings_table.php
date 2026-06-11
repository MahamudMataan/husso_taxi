<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('bookings', function (Blueprint $table) {
            $table->string('admin_status', 30)->default('pending')->after('payment_status');
            $table->string('driver_name')->default('Omar Hussein')->after('admin_status');
            $table->timestamp('reviewed_at')->nullable()->after('driver_name');
        });
    }

    public function down(): void
    {
        Schema::table('bookings', function (Blueprint $table) {
            $table->dropColumn(['admin_status', 'driver_name', 'reviewed_at']);
        });
    }
};
