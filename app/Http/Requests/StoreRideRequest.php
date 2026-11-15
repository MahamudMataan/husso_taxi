<?php

namespace App\Http\Requests;

use Carbon\CarbonImmutable;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Validator;

class StoreRideRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'language' => ['nullable', 'in:nl,en'],
            'customer_name' => ['required', 'string', 'max:120'],
            'customer_email' => ['required', 'email', 'max:255'],
            'phone' => ['required', 'string', 'max:40'],
            'pickup' => ['required', 'string', 'max:255'],
            'pickup_latitude' => ['required', 'numeric', 'between:-90,90'],
            'pickup_longitude' => ['required', 'numeric', 'between:-180,180'],
            'pickup_place_id' => ['nullable', 'string', 'regex:/^[a-zA-Z0-9_-]{1,255}$/'],
            'destination' => ['required', 'string', 'max:255'],
            'destination_latitude' => ['required', 'numeric', 'between:-90,90'],
            'destination_longitude' => ['required', 'numeric', 'between:-180,180'],
            'destination_place_id' => ['required', 'string', 'regex:/^[a-zA-Z0-9_-]{1,255}$/'],
            'ride_date' => ['required', 'date_format:Y-m-d'],
            'ride_time' => ['required', 'date_format:H:i'],
            'passengers' => ['required', 'integer', 'between:1,5'],
            'ride_type' => ['required', 'in:standard,airport,business,long_distance'],
            'in_taxi_method' => ['nullable', 'in:cash,pin'],
            'customer_notes' => ['nullable', 'string', 'max:500'],
            'invoice_requested' => ['nullable', 'in:0,1'],
            'invoice_name' => ['exclude_unless:invoice_requested,1', 'required', 'string', 'max:120'],
            'invoice_address' => ['exclude_unless:invoice_requested,1', 'required', 'string', 'max:180'],
            'invoice_postcode' => ['exclude_unless:invoice_requested,1', 'required', 'string', 'max:24'],
            'invoice_city' => ['exclude_unless:invoice_requested,1', 'required', 'string', 'max:100'],
            'invoice_country' => ['exclude_unless:invoice_requested,1', 'nullable', 'string', 'max:100'],
            'invoice_vat' => ['exclude_unless:invoice_requested,1', 'nullable', 'string', 'max:30'],
            'invoice_email' => ['exclude_unless:invoice_requested,1', 'nullable', 'email', 'max:255'],
            'request_token' => ['required', 'uuid'],
            'amount' => ['required', 'numeric', 'decimal:0,2', 'min:0'],
        ];
    }

    public function after(): array
    {
        return [function (Validator $validator): void {
            if ($validator->errors()->hasAny(['ride_date', 'ride_time'])) {
                return;
            }
            $departure = $this->string('ride_date').' '.$this->string('ride_time');
            $time = CarbonImmutable::createFromFormat('!Y-m-d H:i', $departure, 'Europe/Amsterdam');
            if ($time->format('Y-m-d H:i') !== $departure || $departure < now('Europe/Amsterdam')->format('Y-m-d H:i')) {
                $validator->errors()->add('ride_time', $this->input('language') === 'en'
                    ? 'Choose a pickup time in the future.'
                    : 'Kies een ophaaltijd in de toekomst.');
            }
            if (! $this->filled('pickup_place_id')
                && preg_match('/^(?:Huidige locatie|Current location) \(GPS: [-\d.]+, [-\d.]+\)$/', (string) $this->input('pickup')) !== 1) {
                $validator->errors()->add('pickup', $this->input('language') === 'en'
                    ? 'Select your pickup address or use your current location.'
                    : 'Kies een ophaaladres uit de suggesties of gebruik je huidige locatie.');
            }
        }];
    }

    public function messages(): array
    {
        return $this->input('language') === 'en' ? [] : [
            'passengers.between' => 'Boek online voor één tot vijf personen. Bel ons voor een extra taxi.',
            'customer_name.required' => 'Vul je naam in.',
            'customer_email.required' => 'Vul je e-mailadres in.',
            'customer_email.email' => 'Vul een geldig e-mailadres in.',
            'phone.required' => 'Vul je telefoonnummer in.',
            'destination_place_id.required' => 'Kies je bestemming uit de adressuggesties.',
            'request_token.required' => 'Ververs de boekingspagina en bereken je rit opnieuw.',
            'invoice_name.required' => 'Vul de naam voor de factuur in.',
            'invoice_address.required' => 'Vul het factuuradres in.',
            'invoice_postcode.required' => 'Vul de postcode voor de factuur in.',
            'invoice_city.required' => 'Vul de plaats voor de factuur in.',
            'invoice_email.email' => 'Vul een geldig e-mailadres voor de factuur in.',
        ];
    }

    public function bookingData(): array
    {
        $validated = $this->validated();
        $hash = hash_hmac('sha256', $validated['request_token'], $this->session()->token());
        $validated['request_token'] = vsprintf('%s%s-%s-%s-%s-%s%s%s', str_split(substr($hash, 0, 32), 4));

        return $validated;
    }
}
