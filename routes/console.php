<?php

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
        '--queue' => config('queue.booking_mail_queue'),
        '--stop-when-empty',
        '--sleep' => 0,
        '--max-jobs' => 10,
        '--max-time' => 50,
        '--timeout' => 30,
    ])->everyMinute()->withoutOverlapping(2);
}
