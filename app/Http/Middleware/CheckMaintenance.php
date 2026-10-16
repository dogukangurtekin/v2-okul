<?php

namespace App\Http\Middleware;

use App\Models\Setting;
use Closure;
use Illuminate\Http\Request;

/** Bakim modunda yalniz yonetici ve giris/yasal sayfalar acik kalir. */
class CheckMaintenance
{
    private const OPEN_ROUTES = ['login', 'login.attempt', 'logout', 'legal.*'];

    public function handle(Request $request, Closure $next)
    {
        if (Setting::get('maintenance') === '1' && ! $request->user()?->hasRole('admin') && ! $request->routeIs(...self::OPEN_ROUTES)) {
            return response()->view('maintenance', ['message' => Setting::get('maintenance_message')], 503, ['Retry-After' => 600]);
        }

        return $next($request);
    }
}
