<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class DriverBreakRequest extends Model
{
    protected $fillable = [
        'driver_name',
        'break_date',
        'starts_at',
        'ends_at',
        'reason',
        'status',
    ];

    protected function casts(): array
    {
        return [
            'break_date' => 'date',
        ];
    }
}
