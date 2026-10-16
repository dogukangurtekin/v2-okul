@php
    $u = auth()->user();
    $stu = $u->student;
    $lv = \App\Support\Level::forXp((int) ($stu?->xp_total ?? 0));
    $emoji = $stu?->avatar?->emoji ?? '🙂';
    $unread = $u->notifications()->wherePivotNull('read_at')->count();
    $main = [
        ['dashboard', '🏠', 'Ana Sayfa', 'dashboard'], ['student.courses', '📚', 'Derslerim', ['student.courses', 'student.courses.show']],
        ['student.assignments', '📝', 'Ödevlerim', 'student.assignments*'], ['games.index', '🎮', 'Oyunlar', ['games.*']], ['student.live', '⚡', 'Canlı', 'student.live*'],
    ];
    $more = [
        ['student.avatars', '🎭', 'Avatarlarım', 'student.avatars*'], ['student.badges', '🏅', 'Rozetlerim', 'student.badges'], ['student.ranking', '🏆', 'Sıralama', 'student.ranking'],
        ['student.friends', '🤝', 'Arkadaşlarım', 'student.friends'], ['student.board', '📌', 'Sınıf Panosu', 'student.board*'], ['student.messages', '💬', 'Öğretmene Mesaj', 'student.messages*'],
        ['student.report', '🎓', 'Karnem', 'student.report'], ['student.attendance', '🗓️', 'Devamsızlık', 'student.attendance'], ['student.grades', '⭐', 'Notlarım', 'student.grades'],
        ['announcements.feed', '📢', 'Duyurular', 'announcements.feed'], ['notifications.inbox', '🔔', 'Bildirimler'.($unread ? " ($unread)" : ''), 'notifications.*'],
    ];
@endphp
<body class="shell shell-student">
    <div class="blob b1"></div><div class="blob b2"></div><div class="blob b3"></div>
    <header class="st-top">
        <div class="st-bar">
            <a class="st-logo" href="{{ route('dashboard') }}"><i>🚀</i><span>Bilişim Kod</span></a>
            <nav class="st-nav">
                @foreach($main as $n)<a class="{{ request()->routeIs($n[3]) ? 'on' : '' }}" href="{{ route($n[0]) }}"><span>{{ $n[1] }}</span>{{ $n[2] }}</a>@endforeach
                <details class="st-more" data-menu><summary><a style="cursor:pointer"><span>✨</span>Daha</a></summary>
                    <div class="st-pop">@foreach($more as $n)<a href="{{ route($n[0]) }}"><span>{{ $n[1] }}</span>{{ $n[2] }}</a>@endforeach</div></details>
            </nav>
            <div class="st-me">
                <a class="st-lvl" href="{{ route('student.badges') }}" title="Seviye {{ $lv['level'] }} · {{ $lv['title'] }}">
                    <span class="av">{{ $emoji }}</span>
                    <span><b>{{ $lv['icon'] }} Sv. {{ $lv['level'] }} · {{ $lv['xp'] }} XP</b><span class="xpbar"><i data-pct="{{ $lv['pct'] }}" style="width:{{ $lv['pct'] }}%"></i></span></span>
                </a>
                <form method="POST" action="{{ route('logout') }}">@csrf<button class="s-chip" style="border:0;cursor:pointer;font-family:inherit" title="Çıkış yap">Çıkış</button></form>
            </div>
        </div>
    </header>
    <main class="st-main">@yield('content')</main>
    <nav class="st-bottom" aria-label="Ana menü">
        @foreach(array_slice($main, 0, 4) as $n)<a class="{{ request()->routeIs($n[3]) ? 'on' : '' }}" href="{{ route($n[0]) }}"><i>{{ $n[1] }}</i>{{ $n[2] }}</a>@endforeach
        <details class="st-more" data-menu><summary><a><i>✨</i>Daha</a></summary>
            <div class="st-pop up">@foreach(array_merge([$main[4]], $more) as $n)<a href="{{ route($n[0]) }}"><span>{{ $n[1] }}</span>{{ $n[2] }}</a>@endforeach
                <form method="POST" action="{{ route('logout') }}">@csrf<button><span>🚪</span>Çıkış yap</button></form></div></details>
    </nav>
    @include('layouts.partials.flash')
    <script>
    // Etkin süre: sekme görünürken 30 sn'de bir sunucuya bildir.
    setInterval(() => { if (document.visibilityState === 'visible') fetch(@json(route('student.ping')), {method:'POST', headers:{'X-CSRF-TOKEN':@json(csrf_token()),Accept:'application/json'}}); }, 30000);
    </script>
    <script src="{{ asset('js/ui.js') }}?v={{ $cssV }}" defer></script>
</body>
