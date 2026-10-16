@extends('layouts.app')
@section('title', 'Giriş Kartları')
@section('content')
<h1 class="text-2xl font-bold mb-1">Giriş Kartları</h1>
<p class="text-slate-500 mb-4 max-w-2xl">Sınıf için QR'li kartlar basılır. Her kart tek kullanımlıktır ve {{ \App\Services\LoginTokenService::CARD_DAYS }} gün geçerlidir; yeni kart basınca eskisi iptal olur. Şifre kartta yer almaz.</p>
<div class="grid grid-cols-3 gap-3 max-w-3xl">@foreach($classes as $c)
<form method="POST" action="{{ route('cards.issue', $c) }}" onsubmit="return confirm('{{ $c->label }} için yeni kartlar basılsın mı? Eski kartlar iptal olur.')" class="bg-white rounded-xl shadow p-4">@csrf
<div class="font-semibold">{{ $c->label }}</div><div class="text-xs text-slate-400 mb-2">{{ $c->students_count }} öğrenci</div><button class="bg-indigo-600 text-white px-3 py-1 rounded">Kartları oluştur</button></form>@endforeach</div>
@endsection
