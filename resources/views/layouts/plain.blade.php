@php($cssV = @filemtime(public_path('css/app.css')) ?: 1)
<!DOCTYPE html>
<html lang="tr">
<head>
    @include('layouts.partials.head')
</head>
<body class="shell guest-wrap">
    <div class="guest-top"><a href="{{ auth()->check() ? route('dashboard') : route('login') }}" style="display:flex;align-items:center;gap:.6rem;text-decoration:none;color:inherit">
        <span class="sb-logo" style="width:34px;height:34px">&lt;/&gt;</span><b style="font-weight:800;letter-spacing:-.02em">Bilişim Kod</b></a></div>
    <main class="guest-main">@yield('content')</main>
    @include('layouts.partials.flash')
    <script src="{{ asset('js/ui.js') }}?v={{ $cssV }}" defer></script>
</body>
</html>
