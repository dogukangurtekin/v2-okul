<?php

use App\Http\Middleware\CheckForcedLogout;
use App\Http\Middleware\CheckMaintenance;
use App\Http\Middleware\RoleMiddleware;
use App\Http\Middleware\SecurityHeaders;
use App\Http\Middleware\TrackStudentActivity;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->web(append: [CheckMaintenance::class, CheckForcedLogout::class, TrackStudentActivity::class]);
        $middleware->append(SecurityHeaders::class);
        $middleware->alias(['role' => RoleMiddleware::class]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        // 419: eski/önbellekteki sayfadan gönderim. Hata sayfası yerine temiz bir yeniden deneme sunulur.
        $exceptions->render(function (\Symfony\Component\HttpKernel\Exception\HttpException $e, \Illuminate\Http\Request $request) {
            if ($e->getStatusCode() !== 419 || $request->expectsJson()) {
                return null;
            }
            $msg = 'Sayfanın süresi dolmuştu. Lütfen tekrar deneyin.';

            return $request->is('login')
                ? redirect()->route('login')->withInput($request->except('password', '_token'))->withErrors(['email' => $msg])
                : redirect()->back()->withErrors(['csrf' => $msg]);
        });
    })->create();
