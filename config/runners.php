<?php

/*
 * Oyun calistirma bilgisi. kind:
 *  native  -> v2 icinde yazilmis oyun (resources/views/games/native/{slug}.blade.php)
 *  bundle  -> public/runner-assets/{slug}/index.html varsa iframe ile acilir (v1 paketleri buraya kopyalanabilir)
 * Hicbiri yoksa "yakinda" sayfasi gosterilir.
 */
return [
    'live-quiz' => ['emoji' => '❓', 'kind' => 'route', 'route' => 'live-quiz'],
    'block-grid-runner' => ['emoji' => '🧩', 'kind' => 'native', 'levels' => 6, 'xp' => 10],
    'block-3d-runner' => ['emoji' => '🧊', 'kind' => 'bundle', 'levels' => 200, 'xp' => 10],
    'compute-it-runner' => ['emoji' => '🧮', 'kind' => 'bundle', 'levels' => 30, 'xp' => 10],
    'lightbot-runner' => ['emoji' => '🤖', 'kind' => 'native', 'levels' => 6, 'xp' => 12, 'verifier' => \App\Games\LightbotVerifier::class],
    'line-trace-runner' => ['emoji' => '〰️', 'kind' => 'bundle', 'levels' => 100, 'xp' => 10],
    'silent-teacher-runner' => ['emoji' => '🐍', 'kind' => 'native', 'levels' => 12, 'xp' => 10, 'verifier' => \App\Games\SilentTeacherVerifier::class],
    'flamestone-game' => ['emoji' => '🔥', 'kind' => 'bundle', 'levels' => 10, 'xp' => 10],
    'keyboard-race' => ['emoji' => '⌨️', 'kind' => 'native', 'levels' => 5, 'xp' => 8],
    'block-builder-studio' => ['emoji' => '🏗️', 'kind' => 'bundle', 'levels' => 1, 'xp' => 10],
    'connect-the-dots-runner' => ['emoji' => '🔵', 'kind' => 'bundle', 'levels' => 50, 'xp' => 10],
    'python-editor' => ['emoji' => '📝', 'kind' => 'native', 'levels' => 8, 'xp' => 10, 'verifier' => \App\Games\PythonEditorVerifier::class,
        // Pyodide (Python/WebAssembly). Okul internetsizse dosyaları public/vendor/pyodide/ altına kopyalayıp PYODIDE_URL=/vendor/pyodide/ verin.
        'pyodide_url' => env('PYODIDE_URL', 'https://cdn.jsdelivr.net/pyodide/v0.26.4/full/')],
    'bee-garden-runner' => ['emoji' => '🐝', 'kind' => 'native', 'levels' => 8, 'xp' => 12, 'verifier' => \App\Games\BeeGardenVerifier::class],
    'dot-cards-runner' => ['emoji' => '🃏', 'kind' => 'bundle', 'levels' => 100, 'xp' => 10],
    'flowchart-runner' => ['emoji' => '🔀', 'kind' => 'bundle', 'levels' => 40, 'xp' => 10],
    'web-lab-runner' => ['emoji' => '🌐', 'kind' => 'native', 'levels' => 8, 'xp' => 12, 'verifier' => \App\Games\WebLabVerifier::class],
];
