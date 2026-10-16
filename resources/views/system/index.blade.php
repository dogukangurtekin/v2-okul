@extends('layouts.app')
@section('title', 'Sistem')
@section('content')
<h1 class="text-2xl font-bold mb-4">Sistem</h1>
<form method="POST" action="{{ route('system.maintenance') }}" class="bg-white p-5 rounded-xl shadow max-w-xl space-y-3 mb-6">@csrf
<div class="font-semibold">Bakım modu: <span class="{{ $maintenance ? 'text-red-600' : 'text-green-600' }}">{{ $maintenance ? 'AÇIK' : 'kapalı' }}</span></div>
<input name="message" value="{{ $message }}" maxlength="300" placeholder="Ziyaretçilere gösterilecek mesaj" class="w-full border rounded px-3 py-2">
<button class="{{ $maintenance ? 'bg-green-600' : 'bg-red-600' }} text-white px-4 py-2 rounded">{{ $maintenance ? 'Bakımı kapat' : 'Bakım modunu aç' }}</button></form>
<form method="POST" action="{{ route('system.reset') }}" class="bg-red-50 border border-red-200 p-5 rounded-xl max-w-xl space-y-3" onsubmit="return confirm('TÜM öğrenci ilerlemesi silinecek. Emin misiniz?')">@csrf
<div class="font-semibold text-red-700">Öğrenci verilerini sıfırla</div>
<p class="text-sm text-slate-600">XP, rozet, avatar, ders/ödev/oyun ilerlemesi, canlı oturumlar ve öğrenci mesajları silinir. Kullanıcılar, sınıflar, dersler kalır. <b>Geri alınamaz.</b></p>
<input name="confirm" placeholder="Onay için SIFIRLA yazın" class="w-full border rounded px-3 py-2" required>
<input name="password" type="password" placeholder="Yönetici şifreniz" class="w-full border rounded px-3 py-2" required>
<button class="bg-red-700 text-white px-4 py-2 rounded">Sıfırla</button></form>
@endsection
