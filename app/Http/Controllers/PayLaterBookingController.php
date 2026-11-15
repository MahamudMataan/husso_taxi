<?php

namespace App\Http\Controllers;

use App\Services\BookingCreator;
use App\Http\Requests\StoreRideRequest;
use Illuminate\Http\RedirectResponse;
use RuntimeException;

class PayLaterBookingController extends Controller
{
    public function store(StoreRideRequest $request, BookingCreator $bookingCreator): RedirectResponse
    {
        $validated = $request->bookingData();
        $language = $validated['language'] ?? 'nl';

        try {
            $booking = $bookingCreator->create($validated, $validated['in_taxi_method'] ?? 'pay_later', 'pay_later');
        } catch (RuntimeException) {
            return back()->withInput($request->validated())->with('payment_error', $language === 'en'
                ? 'The route could not be verified safely. Please choose pickup and destination again.'
                : 'De route kon niet veilig gecontroleerd worden. Kies ophaal- en bestemmingsplaats opnieuw.');
        }

        return redirect(url($language === 'en' ? '/?lang=en#booking' : '/#booking'))
            ->with('payment_success', $language === 'en'
                ? 'Booking request received ('.$booking->reference.').'
                : 'Boekingsaanvraag ontvangen ('.$booking->reference.').')
            ->with('booking_tracking_url', $booking->trackingUrl());
    }
}
