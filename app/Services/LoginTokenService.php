<?php

namespace App\Services;

use App\Models\LoginToken;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class LoginTokenService
{
    public const CARD_DAYS = 7;
    public const QR_MINUTES = 2;

    /** Ogrenci icin yeni giris karti uretir; eski kullanilmamis kartlari gecersiz kilar. @return string duz token (bir kez gosterilir) */
    public function issueCard(User $student, User $by): string
    {
        LoginToken::where(['kind' => 'card', 'user_id' => $student->id])->whereNull('used_at')->delete();
        $plain = Str::random(40);
        LoginToken::create(['kind' => 'card', 'user_id' => $student->id, 'created_by' => $by->id, 'token_hash' => LoginToken::hash($plain), 'expires_at' => now()->addDays(self::CARD_DAYS)]);

        return $plain;
    }

    /** Giris ekraninda QR: [token (QR'da), poll (yalniz bu tarayicida)]. */
    public function startQr(Request $request): array
    {
        LoginToken::where('expires_at', '<', now()->subHour())->delete(); // basit temizlik
        $token = Str::random(40);
        $poll = Str::random(40);
        LoginToken::create([
            'kind' => 'qr', 'token_hash' => LoginToken::hash($token), 'poll_hash' => LoginToken::hash($poll),
            'request_ip' => $request->ip(), 'request_ua' => substr((string) $request->userAgent(), 0, 200), 'expires_at' => now()->addMinutes(self::QR_MINUTES),
        ]);

        return [$token, $poll];
    }

    public function find(string $plain, string $kind): ?LoginToken
    {
        return LoginToken::where(['token_hash' => LoginToken::hash($plain), 'kind' => $kind])->first();
    }

    public function approve(LoginToken $t, User $student, User $by): void
    {
        $t->update(['user_id' => $student->id, 'approved_by' => $by->id, 'approved_at' => now()]);
    }

    /** Tek kullanimlik tuketim; yarisa karsi atomik. */
    public function consume(LoginToken $t): ?User
    {
        $claimed = LoginToken::whereKey($t->id)->whereNull('used_at')->where('expires_at', '>', now())->update(['used_at' => now()]);
        $user = $claimed ? $t->user : null;

        return $user && $user->is_active && $user->hasRole('student') ? $user : null;
    }
}
