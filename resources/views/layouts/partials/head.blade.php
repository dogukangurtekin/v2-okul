<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<meta name="theme-color" content="#2447f5">
<title>@yield('title', 'Okul') · {{ config('app.name', 'v2-okul') }}</title>
<link rel="manifest" href="/manifest.webmanifest">
<link rel="icon" href="/icon.svg" type="image/svg+xml">
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Atkinson+Hyperlegible:ital,wght@0,400;0,700;1,400&family=Bricolage+Grotesque:opsz,wght@12..96,600;12..96,700;12..96,800&display=swap">
@if(auth()->check() && \App\Support\AgeBand::current() === 'teen' && auth()->user()->hasRole('student'))
<link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=IBM+Plex+Sans:wght@400;500;600&family=JetBrains+Mono:wght@400;500;700&display=swap">
@endif
@if(auth()->check() && auth()->user()->hasRole('student') && \App\Support\AgeBand::current() === 'teen')
<link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=IBM+Plex+Sans:wght@400;500;600&family=JetBrains+Mono:wght@400;500;700&display=swap">
@endif
<link rel="stylesheet" href="{{ asset('css/app.css') }}?v={{ $cssV }}">
@if(auth()->check() && auth()->user()->hasRole('student'))
<script>(function(){var d=document.documentElement,t=null;try{t=localStorage.getItem('theme')}catch(e){}d.dataset.theme=(t==='dark'||t==='light')?t:@json(\App\Support\AgeBand::current() === 'teen' ? 'dark' : 'light');})();</script>
@endif
@if(auth()->check() && ! auth()->user()->hasRole('student'))
<script>(function(){var t=null;try{t=localStorage.getItem('theme_staff')}catch(e){}document.documentElement.dataset.theme=(t==='dark')?'dark':'light';})();</script>
@endif
