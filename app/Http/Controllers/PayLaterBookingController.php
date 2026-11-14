<?php

namespace App\Http\Controllers;

use App\Services\BookingCreator;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use RuntimeException;

class PayLaterBookingController extends Controller
{
    public function store(Request $request, BookingCreator $bookingCreator): RedirectResponse
    {
        $validated = $request->validate([
            'language'            => ['nullable', 'in:nl,en'],
            'customer_name'       => ['required', 'string', 'max:120'],
            'customer_email'      => ['required', 'email', 'max:255'],
            'phone'               => ['required', 'string', 'max:40'],
            'pickup'              => ['required', 'string', 'max:255'],
            'pickup_latitude'     => ['required', 'numeric', 'between:-90,90'],
            'pickup_longitude'    => ['required', 'numeric', 'between:-180,180'],
            'destination'         => ['required', 'string', 'max:255'],
            'destination_latitude'  => ['required', 'numeric', 'between:-90,90'],
            'destination_longitude' => ['required', 'numeric', 'between:-180,180'],
            'ride_date'           => ['required', 'date'],
            'ride_time'           => ['required', 'date_format:H:i'],
            'passengers'          => ['required', 'string', 'max:10'],
            'ride_type'           => ['required', 'string', 'max:40'],
        ]);

        $language = $validated['language'] ?? 'nl';

        try {
            $booking = $bookingCreator->create($validated, 'pay_later', 'pay_later');
        } catch (RuntimeException) {
            return back()->withInput($validated)->with('payment_error', $language === 'en'
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
