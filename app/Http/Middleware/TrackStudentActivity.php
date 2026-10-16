<?php

namespace App\Http\Middleware;

use App\Models\StudentActivityLog;
use Closure;
use Illuminate\Http\Request;

/** Ogrenci istek yaptiginda son gorulme/yol bilgisini gunceller (en fazla 20 sn'de bir) ve rota gecmisi tutar. */
class TrackStudentActivity
{
    public function handle(Request $request, Closure $next)
    {
        $response = $next($request);

        $student = $request->user()?->student;
        $route = $request->route()?->getName();
        if ($student && $request->isMethod('GET') && ! $request->expectsJson() && $route && ! str_ends_with($route, '.status')) {
            if (! $student->last_seen_at || $student->last_seen_at->diffInSeconds(now()) >= 20 || $student->last_path !== $route) {
                if ($student->last_path !== $route) {
                    StudentActivityLog::create(['student_id' => $student->id, 'route_name' => $route, 'label' => $request->path(), 'ip' => $request->ip(), 'created_at' => now()]);
                }
                $student->forceFill(['last_seen_at' => now(), 'last_path' => $route])->saveQuietly();
            }
        }

        return $response;
    }
}
