<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class DriverTimeLog extends Model
{
    protected $fillable = [
        'driver_name',
        'clocked_in_at',
        'clocked_out_at',
    ];

    protected function casts(): array
    {
        return [
            'clocked_in_at' => 'datetime',
            'clocked_out_at' => 'datetime',
        ];
    }
}
