<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

\Illuminate\Support\Facades\Schedule::call(fn () => \App\Models\StudentActivityLog::where('created_at', '<', now()->subDays(30))->delete())
    ->dailyAt('00:30')->name('activity-logs:purge');

\Illuminate\Support\Facades\Schedule::command('attendance:remind')->weekdays()->at(config('school.attendance_reminder_at', '10:00'))->timezone('Europe/Istanbul');
