<?php

namespace App\Models;

use App\Services\BookingPriceCalculator;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

class Booking extends Model
{
    public const ACTIVE_STATUSES = ['driver_en_route', 'arrived', 'picked_up'];
    public const OPEN_STATUSES = ['new', 'driver_assigned', ...self::ACTIVE_STATUSES];

    private const DISPATCH_SEQUENCE = [
        'new' => 'driver_assigned',
        'driver_assigned' => 'driver_en_route',
        'driver_en_route' => 'arrived',
        'arrived' => 'picked_up',
        'picked_up' => 'completed',
    ];

    protected $fillable = [
        'reference',
        'tracking_token',
        'language',
        'customer_name',
        'customer_email',
        'phone',
        'pickup',
        'pickup_latitude',
        'pickup_longitude',
        'destination',
        'destination_latitude',
        'destination_longitude',
        'ride_date',
        'ride_time',
        'passengers',
        'ride_type',
        'distance_km',
        'duration_min',
        'base_fare',
        'km_rate',
        'minute_rate',
        'quoted_amount',
        'payment_method',
        'payment_status',
        'booking_status',
        'admin_status',
        'driver_name',
        'driver_phone',
        'driver_vehicle',
        'driver_eta_min',
        'driver_latitude',
        'driver_longitude',
        'payment_collected_at',
        'customer_notes',
        'dispatch_notes',
        'reviewed_at',
        'mollie_payment_id',
        'confirmed_at',
        'confirmation_emailed_at',
    ];

    protected function casts(): array
    {
        return [
            'pickup_latitude' => 'decimal:7',
            'pickup_longitude' => 'decimal:7',
            'destination_latitude' => 'decimal:7',
            'destination_longitude' => 'decimal:7',
            'ride_date' => 'date',
            'distance_km' => 'decimal:1',
            'base_fare' => 'decimal:2',
            'km_rate' => 'decimal:2',
            'minute_rate' => 'decimal:2',
            'quoted_amount' => 'decimal:2',
            'driver_latitude' => 'decimal:7',
            'driver_longitude' => 'decimal:7',
            'payment_collected_at' => 'datetime',
            'reviewed_at' => 'datetime',
            'confirmed_at' => 'datetime',
            'confirmation_emailed_at' => 'datetime',
        ];
    }

    public function trackingUrl(): string
    {
        if (! $this->tracking_token) {
            $this->forceFill(['tracking_token' => $this->newTrackingToken()])->save();
        }

        return route('bookings.track', $this->tracking_token);
    }

    public function nextDispatchStatus(): ?string
    {
        return self::DISPATCH_SEQUENCE[$this->booking_status] ?? null;
    }

    public function canCancelDispatch(): bool
    {
        return in_array($this->booking_status, self::OPEN_STATUSES, true);
    }

    public function distanceFare(): float
    {
        return round((float) $this->distance_km * BookingPriceCalculator::KM_RATE, 2);
    }

    public function timeFare(): float
    {
        return round((int) $this->duration_min * BookingPriceCalculator::MINUTE_RATE, 2);
    }

    public function calculatedFare(): float
    {
        return round($this->distanceFare() + $this->timeFare(), 2);
    }

    public function notifications(): HasMany
    {
        return $this->hasMany(BookingNotification::class);
    }

    private function newTrackingToken(): string
    {
        do {
            $token = Str::lower(Str::random(48));
        } while (self::where('tracking_token', $token)->exists());

        return $token;
    }
}
