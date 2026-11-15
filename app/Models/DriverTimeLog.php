<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class DriverTimeLog extends Model
{
    protected $fillable = [
        'driver_name',
        'clocked_in_at',
        'clocked_out_at',
    ];

    public function breaks(): HasMany
    {
        return $this->hasMany(DriverShiftBreak::class);
    }

    protected function casts(): array
    {
        return [
            'clocked_in_at' => 'datetime',
            'clocked_out_at' => 'datetime',
        ];
    }
}
