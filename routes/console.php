<?php

use App\Jobs\SendBookingNotification;
use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

if (config('queue.default') === 'database'
    && config('mail.default') === 'smtp'
    && config('mail.mailers.smtp.host') === 'smtp.hostinger.com'
    && filled(config('mail.mailers.smtp.password'))) {
    Schedule::command('queue:work', [
        'database',
        '--queue' => SendBookingNotification::BOOKING_MAIL_QUEUE,
        '--stop-when-empty',
        '--sleep' => 0,
        '--max-jobs' => 8,
        '--max-time' => 50,
        '--timeout' => 30,
    ])->everyMinute()->withoutOverlapping(2);
}
