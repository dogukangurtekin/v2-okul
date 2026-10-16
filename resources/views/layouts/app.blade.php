@php($cssV = @filemtime(public_path('css/app.css')) ?: 1)
<!DOCTYPE html>
<html lang="tr">
<head>
    @include('layouts.partials.head')
</head>
@auth
    @if(auth()->user()->hasRole('student'))
        @include('layouts.partials.student-shell')
    @else
        @include('layouts.partials.staff-shell')
    @endif
@else
<body class="guest">
    @yield('content')
    @include('layouts.partials.flash')
    <script src="{{ asset('js/ui.js') }}?v={{ $cssV }}" defer></script>
</body>
@endauth
</html>
