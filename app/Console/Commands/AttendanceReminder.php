<?php

namespace App\Console\Commands;

use App\Models\AppNotification;
use App\Services\AttendanceService;
use App\Services\NotificationService;
use Illuminate\Console\Command;

class AttendanceReminder extends Command
{
    protected $signature = 'attendance:remind';

    protected $description = 'Bugünkü yoklamasını girmemiş sınıf öğretmenlerine hatırlatma gönderir (günde bir kez).';

    public function handle(AttendanceService $attendance, NotificationService $notify): int
    {
        $title = 'Yoklama hatırlatması';
        $already = AppNotification::where('title', $title)->whereDate('created_at', today())->with('recipients:id')->get()->flatMap->recipients->pluck('id');
        $targets = $attendance->teachersMissingToday()->diff($already)->values();

        if ($targets->isNotEmpty()) {
            $notify->send(null, $title, 'Bugünkü yoklama kaydı eksik görünüyor.', '/yoklama', $targets);
        }
        $this->info($targets->count().' öğretmene hatırlatma gönderildi.');

        return self::SUCCESS;
    }
}
