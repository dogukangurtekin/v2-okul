<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width,initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <meta name="app-base-url" content="{{ rtrim(request()->getBaseUrl(), '/') }}">
    <title>Blok Tasarım Stüdyosu</title>
    <link rel="stylesheet" href="{{ asset('runner-assets/block-builder-studio/block-builder-page.css') }}">
    <script type="module" src="{{ asset('runner-assets/block-builder-studio/block-builder-page.js') }}"></script>
</head>
<body style="margin:0; background:#0a1738; overflow:hidden;">
    <div id="block-builder-page-root"></div>
</body>
</html>
