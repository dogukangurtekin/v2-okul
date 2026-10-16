@php
    $u = auth()->user();
    $isAdmin = $u->hasRole('admin');
    $unread = $u->notifications()->wherePivotNull('read_at')->count();
    $openSupport = $isAdmin ? \App\Models\SupportRequest::whereNull('read_at')->whereNull('archived_at')->count() : 0;
    $groups = [
        ['Genel', [['dashboard', 'Panel', 'home', 'dashboard']]],
        ['Okul', [
            ['classes.index', 'Sınıflar', 'building', 'classes.*'], ['students.index', 'Öğrenciler', 'cap', 'students.*'],
            $isAdmin ? ['users.index', 'Kullanıcılar', 'users', 'users.*'] : null, $isAdmin ? ['import.index', 'Toplu Yükleme', 'upload', 'import.*'] : null,
            ['cards.index', 'Giriş Kartları', 'idcard', ['cards.*', 'qr.approve']],
        ]],
        ['Öğrenme', [
            ['courses.index', 'Dersler', 'book', 'courses.*'], ['assignments.index', 'Ödevler', 'clipboard', 'assignments.*'], ['games.index', 'Etkinlikler', 'gamepad', 'games.*'],
            ['quizzes.index', 'Quizler', 'help', 'quizzes.*'], ['live.index', 'Canlı Oturumlar', 'bolt', 'live.*'],
        ]],
        ['Takip', [
            ['tracking.index', 'Canlı Takip', 'monitor', 'tracking.*'], ['analytics.index', 'Analitik', 'chart', ['analytics.*', 'reports.*']],
            ['attendance.sheet', 'Yoklama', 'calendar', 'attendance.*'], ['grades.index', 'Notlar', 'star', 'grades.*'],
        ]],
        ['İletişim', [
            ['notifications.compose', 'Bildirim Gönder', 'bell', 'notifications.compose'], ['messages.staff', 'Öğrenci Mesajları', 'message', 'messages.*'],
            ['support.index', 'Destek', 'lifebuoy', 'support.*', $openSupport], ['announcements.feed', 'Duyurular', 'megaphone', 'announcements.feed'],
            $isAdmin ? ['announcements.index', 'Duyuru Yönetimi', 'megaphone', ['announcements.index', 'announcements.create', 'announcements.edit']] : null,
        ]],
        $isAdmin ? ['Sistem', [['system.index', 'Sistem', 'cog', 'system.*']]] : null,
    ];
    $initials = mb_strtoupper(mb_substr(collect(explode(' ', trim($u->name)))->map(fn ($p) => mb_substr($p, 0, 1))->take(2)->implode(''), 0, 2));
@endphp
<body class="shell shell-staff">
    <aside class="sb" id="sidebar">
        <a class="sb-brand" href="{{ route('dashboard') }}"><span class="sb-logo">&lt;/&gt;</span><span><span class="sb-name">Bilişim Kod</span><br><span class="sb-sub">{{ $isAdmin ? 'Yönetim Paneli' : 'Öğretmen Paneli' }}</span></span></a>
        <nav>
            @foreach(array_filter($groups) as [$label, $items])
                <div class="sb-group">{{ $label }}</div>
                @foreach(array_filter($items) as $it)
                    <a class="sb-link {{ request()->routeIs($it[3]) ? 'on' : '' }}" href="{{ route($it[0]) }}">
                        <x-icon :name="$it[2]" /><span>{{ $it[1] }}</span>@if(!empty($it[4]))<span class="dot">{{ $it[4] }}</span>@endif
                    </a>
                @endforeach
            @endforeach
        </nav>
        <div class="sb-foot">
            <div class="sb-user"><span class="avatar-i">{{ $initials }}</span><div style="min-width:0"><b style="overflow:hidden;text-overflow:ellipsis;white-space:nowrap">{{ $u->name }}</b><small>{{ $u->role->name }}</small></div></div>
            <form method="POST" action="{{ route('logout') }}" style="margin-top:.5rem">@csrf
                <button class="sb-link" style="width:100%;background:none;border:0;cursor:pointer;font-family:inherit"><x-icon name="logout" /><span>Çıkış yap</span></button></form>
        </div>
    </aside>
    <div class="scrim"></div>
    <div style="min-width:0">
        <header class="tb">
            <button class="tb-btn menu-btn" type="button" data-nav-toggle aria-label="Menü"><x-icon name="menu" /></button>
            <div class="tb-title">@yield('title')</div>
            <div style="margin-left:auto;display:flex;gap:.6rem;align-items:center">
                @if($isAdmin && $openSupport)<a class="tb-btn" href="{{ route('support.index') }}" title="Okunmamış destek talebi"><x-icon name="lifebuoy" /><span class="dot">{{ $openSupport }}</span></a>@endif
                <button type="button" class="tb-btn theme-btn" id="themeBtn" aria-label="Gece / gündüz modu" title="Gece / gündüz modu"><span class="i-moon"><x-icon name="moon" /></span><span class="i-sun"><x-icon name="sun" /></span></button>
                <a class="tb-btn" href="{{ route('notifications.inbox') }}" title="Bildirimler"><x-icon name="bell" />@if($unread)<span class="dot">{{ $unread }}</span>@endif</a>
            </div>
        </header>
        <main class="page">@yield('content')</main>
    </div>
    @include('layouts.partials.flash')
    <script>
    document.getElementById('themeBtn')?.addEventListener('click', function () {
      var d = document.documentElement, n = d.dataset.theme === 'dark' ? 'light' : 'dark';
      d.dataset.theme = n; try { localStorage.setItem('theme_staff', n); } catch (e) {}
    });
    </script>
    <script src="{{ asset('js/ui.js') }}?v={{ $cssV }}" defer></script>
</body>
