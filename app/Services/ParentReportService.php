<?php

namespace App\Services;

use App\Models\ParentMessage;
use App\Models\Student;
use App\Models\User;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\URL;

class ParentReportService
{
    /** Giris gerektirmeyen, sureli imzali veli rapor baglantisi. */
    public function link(Student $student): string
    {
        return URL::temporarySignedRoute('parent.report', now()->addDays(config('parent.link_days')), ['student' => $student->id]);
    }

    /** Turkiye telefonunu uluslararasi bicime cevirir (905xxxxxxxxx); gecersizse null. */
    public function phone(?string $raw): ?string
    {
        $d = preg_replace('/\D/', '', (string) $raw);
        if (str_starts_with($d, '00')) {
            $d = substr($d, 2);
        }
        if (strlen($d) === 11 && $d[0] === '0') {
            $d = '9'.$d;
        } elseif (strlen($d) === 10) {
            $d = '90'.$d;
        }

        return preg_match('/^90[1-9]\d{9}$/', $d) ? $d : null;
    }

    public function send(Student $student, ?User $sender): ParentMessage
    {
        $channel = config('parent.channel');
        $phone = $this->phone($student->parent_phone);
        $base = ['student_id' => $student->id, 'sent_by' => $sender?->id, 'channel' => $channel];

        if (! $phone) {
            return ParentMessage::create($base + ['status' => 'no_phone', 'detail' => 'Geçerli veli telefonu yok.']);
        }
        $link = $this->link($student);
        if ($channel !== 'whatsapp') {
            return ParentMessage::create($base + ['status' => 'logged', 'detail' => "$phone → $link"]);
        }

        $w = config('parent.whatsapp');
        try {
            $res = Http::withToken($w['token'])->timeout(15)->post("https://graph.facebook.com/{$w['version']}/{$w['phone_number_id']}/messages", [
                'messaging_product' => 'whatsapp', 'to' => $phone, 'type' => 'template',
                'template' => ['name' => $w['template'], 'language' => ['code' => $w['language']], 'components' => [[
                    'type' => 'body', 'parameters' => [['type' => 'text', 'text' => $student->user->name], ['type' => 'text', 'text' => $link]],
                ]]],
            ]);

            return ParentMessage::create($base + ['status' => $res->successful() ? 'sent' : 'failed', 'detail' => $res->successful() ? $phone : mb_substr($res->body(), 0, 500)]);
        } catch (\Throwable $e) {
            return ParentMessage::create($base + ['status' => 'failed', 'detail' => mb_substr($e->getMessage(), 0, 500)]);
        }
    }
}
