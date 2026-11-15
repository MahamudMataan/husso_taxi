<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class DriverShiftBreak extends Model
{
    protected $fillable = ['started_at', 'ended_at'];

    protected function casts(): array
    {
        return ['started_at' => 'datetime', 'ended_at' => 'datetime'];
    }
}
