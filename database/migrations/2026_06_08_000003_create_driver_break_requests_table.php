<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('driver_break_requests', function (Blueprint $table) {
            $table->id();
            $table->string('driver_name')->default('Omar Hussein');
            $table->date('break_date');
            $table->time('starts_at');
            $table->time('ends_at');
            $table->string('reason')->nullable();
            $table->string('status', 30)->default('requested');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('driver_break_requests');
    }
};
