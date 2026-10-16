<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

/** users.force_logout_at, oturumun acilis zamanindan yeniyse oturumu kapatir. */
class CheckForcedLogout
{
    public function handle(Request $request, Closure $next)
    {
        $user = $request->user();
        if ($user && $user->force_logout_at && $user->force_logout_at->timestamp >= (int) $request->session()->get('login_at', 0)) {
            auth()->logout();
            $request->session()->invalidate();
            $request->session()->regenerateToken();

            return redirect()->route('login')->withErrors(['email' => 'Oturumunuz yönetici tarafından kapatıldı.']);
        }

        return $next($request);
    }
}
