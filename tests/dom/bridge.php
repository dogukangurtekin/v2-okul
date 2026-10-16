<?php

/*
 * HTTP köprüsü: Laravel çekirdeğini bir TCP sunucusu olmadan çağırır (stdin: satır başına JSON istek → stdout: JSON yanıt).
 * Böylece testler `php artisan serve` / Apache gerektirmez; aynı süreç istekler arasında canlı kalır, her istekte
 * yeni bir Application kurulur (oturum/kullanıcı önbelleği sızmaz).
 */
define('LARAVEL_START', microtime(true));
$root = dirname(__DIR__, 2);
require $root.'/vendor/autoload.php';

ini_set('display_errors', 'stderr');
error_reporting(E_ALL);
fwrite(STDOUT, "READY\n");

while (($line = fgets(STDIN)) !== false) {
    $in = json_decode($line, true);
    $app = require $root.'/bootstrap/app.php';
    $kernel = $app->make(Illuminate\Contracts\Http\Kernel::class);

    $server = ['HTTP_HOST' => parse_url($in['url'], PHP_URL_HOST) ?: 'app.test', 'REMOTE_ADDR' => '127.0.0.1', 'HTTP_USER_AGENT' => $in['headers']['user-agent'] ?? 'dom-test'];
    foreach ($in['headers'] as $k => $v) {
        $k = strtolower($k);
        if ($k === 'content-type') {
            $server['CONTENT_TYPE'] = $v;
        } elseif (! in_array($k, ['cookie', 'host', 'content-length'], true)) {
            $server['HTTP_'.strtoupper(str_replace('-', '_', $k))] = $v;
        }
    }
    $cookies = [];
    foreach (explode(';', $in['headers']['cookie'] ?? '') as $c) {
        if (str_contains($c, '=')) {
            [$n, $v] = explode('=', trim($c), 2);
            $cookies[$n] = urldecode($v);
        }
    }
    $params = [];
    $content = null;
    if (str_starts_with($in['headers']['content-type'] ?? '', 'application/x-www-form-urlencoded')) {
        parse_str($in['body'] ?? '', $params);
    } else {
        $content = $in['body'] ?? null;
    }

    $request = Illuminate\Http\Request::create($in['url'], $in['method'], $params, $cookies, [], $server, $content);
    $response = $kernel->handle($request);

    $headers = [];
    foreach ($response->headers->allPreserveCase() as $name => $vals) {
        if (strtolower($name) !== 'set-cookie') {
            $headers[$name] = implode(', ', $vals);
        }
    }
    $body = $response instanceof Symfony\Component\HttpFoundation\StreamedResponse
        ? (function () use ($response) { ob_start(); $response->sendContent(); return ob_get_clean(); })()
        : (string) $response->getContent();

    fwrite(STDOUT, json_encode([
        'status' => $response->getStatusCode(), 'headers' => $headers,
        'cookies' => array_map(fn ($c) => (string) $c, $response->headers->getCookies()),
        'body' => base64_encode($body),
    ])."\n");

    $kernel->terminate($request, $response);
    unset($app, $kernel, $request, $response);
}
