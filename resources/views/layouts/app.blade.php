<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>@yield('title', 'Okul') · v2-okul</title>
    <link rel="manifest" href="/manifest.webmanifest">
    <meta name="theme-color" content="#4f46e5">
    <link rel="icon" href="/icon.svg" type="image/svg+xml">
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-slate-50 text-slate-800">
@auth
<div class="flex min-h-screen">
    <aside class="w-56 bg-slate-900 text-slate-200 p-4 space-y-1">
        <div class="text-lg font-bold mb-4">v2-okul</div>
        <a class="block px-3 py-2 rounded hover:bg-slate-700" href="{{ route('dashboard') }}">Panel</a>
        <a class="block px-3 py-2 rounded hover:bg-slate-700" href="{{ route('games.index') }}">Etkinlikler</a>
        <a class="block px-3 py-2 rounded hover:bg-slate-700" href="{{ route('announcements.feed') }}">Duyurular</a>
        @if(auth()->user()->hasRole('admin','teacher'))
            <a class="block px-3 py-2 rounded hover:bg-slate-700" href="{{ route('classes.index') }}">Sınıflar</a>
            <a class="block px-3 py-2 rounded hover:bg-slate-700" href="{{ route('students.index') }}">Öğrenciler</a>
            <a class="block px-3 py-2 rounded hover:bg-slate-700" href="{{ route('courses.index') }}">Dersler</a>
            <a class="block px-3 py-2 rounded hover:bg-slate-700" href="{{ route('assignments.index') }}">Ödevler</a>
            <a class="block px-3 py-2 rounded hover:bg-slate-700" href="{{ route('tracking.index') }}">Canlı Takip</a>
            <a class="block px-3 py-2 rounded hover:bg-slate-700" href="{{ route('analytics.index') }}">Analitik</a>
            <a class="block px-3 py-2 rounded hover:bg-slate-700" href="{{ route('notifications.compose') }}">Bildirim Gönder</a>
            <a class="block px-3 py-2 rounded hover:bg-slate-700" href="{{ route('messages.staff') }}">Öğrenci Mesajları</a>
            <a class="block px-3 py-2 rounded hover:bg-slate-700" href="{{ route('support.index') }}">Destek</a>
            <a class="block px-3 py-2 rounded hover:bg-slate-700" href="{{ route('attendance.sheet') }}">Yoklama</a>
            <a class="block px-3 py-2 rounded hover:bg-slate-700" href="{{ route('grades.index') }}">Notlar</a>
            <a class="block px-3 py-2 rounded hover:bg-slate-700" href="{{ route('cards.index') }}">Giriş Kartları</a>
            @if(auth()->user()->hasRole('admin'))
            <a class="block px-3 py-2 rounded hover:bg-slate-700" href="{{ route('users.index') }}">Kullanıcılar</a>
            <a class="block px-3 py-2 rounded hover:bg-slate-700" href="{{ route('import.index') }}">Toplu Yükleme</a>
            <a class="block px-3 py-2 rounded hover:bg-slate-700" href="{{ route('announcements.index') }}">Duyuru Yönetimi</a>
            <a class="block px-3 py-2 rounded hover:bg-slate-700" href="{{ route('system.index') }}">Sistem</a>
            @endif
            <a class="block px-3 py-2 rounded hover:bg-slate-700" href="{{ route('quizzes.index') }}">Quizler</a>
            <a class="block px-3 py-2 rounded hover:bg-slate-700" href="{{ route('live.index') }}">Canlı Oturumlar</a>
        @endif
        @if(auth()->user()->hasRole('student'))
            <a class="block px-3 py-2 rounded hover:bg-slate-700" href="{{ route('student.courses') }}">Derslerim</a>
            <a class="block px-3 py-2 rounded hover:bg-slate-700" href="{{ route('student.assignments') }}">Ödevlerim</a>
            <a class="block px-3 py-2 rounded hover:bg-slate-700" href="{{ route('student.report') }}">Karnem</a>
            <a class="block px-3 py-2 rounded hover:bg-slate-700" href="{{ route('student.attendance') }}">Devamsızlık</a>
            <a class="block px-3 py-2 rounded hover:bg-slate-700" href="{{ route('student.grades') }}">Notlarım</a>
            <a class="block px-3 py-2 rounded hover:bg-slate-700" href="{{ route('student.messages') }}">Öğretmene Mesaj</a>
            <a class="block px-3 py-2 rounded hover:bg-slate-700" href="{{ route('student.live') }}">Canlı Katıl</a>
            <a class="block px-3 py-2 rounded hover:bg-slate-700" href="{{ route('student.avatars') }}">Avatarlarım</a>
            <a class="block px-3 py-2 rounded hover:bg-slate-700" href="{{ route('student.badges') }}">Rozetlerim</a>
            <a class="block px-3 py-2 rounded hover:bg-slate-700" href="{{ route('student.ranking') }}">Sıralama</a>
            <a class="block px-3 py-2 rounded hover:bg-slate-700" href="{{ route('student.friends') }}">Arkadaşlarım</a>
            <a class="block px-3 py-2 rounded hover:bg-slate-700" href="{{ route('student.board') }}">Sınıf Panosu</a>
        @endif
        @php($unread = auth()->user()->notifications()->wherePivotNull('read_at')->count())
        <a class="block px-3 py-2 rounded hover:bg-slate-700" href="{{ route('notifications.inbox') }}">🔔 Bildirimler @if($unread)<span class="ml-1 bg-red-500 text-white text-xs rounded-full px-2">{{ $unread }}</span>@endif</a>
        <form method="POST" action="{{ route('logout') }}" class="pt-4">@csrf
            <button class="w-full text-left px-3 py-2 rounded hover:bg-slate-700">Çıkış ({{ auth()->user()->name }})</button>
        </form>
    </aside>
    <main class="flex-1 p-6">
        @if(session('ok'))<div class="mb-4 p-3 bg-green-100 text-green-800 rounded">{{ session('ok') }}</div>@endif
        @if($errors->any())<div class="mb-4 p-3 bg-red-100 text-red-800 rounded">{{ $errors->first() }}</div>@endif
        @yield('content')
    </main>
</div>
@else
    @yield('content')
@endauth
<script>
if ('serviceWorker' in navigator) navigator.serviceWorker.register('/sw.js').catch(() => {});
@auth @if(auth()->user()->hasRole('student'))
// Etkin sure: sekme gorunurken 30 sn'de bir sunucuya bildir.
setInterval(() => { if (document.visibilityState === 'visible') fetch(@json(route('student.ping')), {method:'POST', headers:{'X-CSRF-TOKEN':@json(csrf_token()),Accept:'application/json'}}); }, 30000);
@endif @endauth
</script>
<div id="cookie" class="hidden fixed bottom-0 inset-x-0 bg-slate-900 text-slate-100 text-sm p-3 flex gap-4 justify-center items-center">
  <span>Oturum çerezleri dışında izleme çerezi kullanmıyoruz. <a class="underline" href="{{ route('legal.cookies') }}">Çerez politikası</a></span>
  <button id="cookie-ok" class="bg-indigo-500 px-3 py-1 rounded">Tamam</button></div>
<script>
try { if (!localStorage.getItem('cookie_ok')) { const c = document.getElementById('cookie'); c.classList.remove('hidden'); document.getElementById('cookie-ok').onclick = () => { localStorage.setItem('cookie_ok', '1'); c.remove(); }; } } catch (e) {}
</script>
</body>
</html>
