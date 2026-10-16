@extends('layouts.app')
@section('title', 'Giriş Kartları')
@section('content')
<x-page-head title="Giriş Kartları" sub="Sınıf için QR'li, tek kullanımlık giriş kartları basın." />
<div class="alert alert-info" style="max-width:760px;margin-bottom:1.1rem">Her kart <b>tek kullanımlıktır</b> ve {{ \App\Services\LoginTokenService::CARD_DAYS }} gün geçerlidir. Yeni kart basınca eskisi iptal olur. Şifre kartta yer almaz.</div>
<div style="display:grid;grid-template-columns:repeat(auto-fill,minmax(210px,1fr));gap:1rem;max-width:980px" class="rise">
@forelse($classes as $c)
  <form method="POST" action="{{ route('cards.issue', $c) }}" onsubmit="return confirm(@js($c->label.' için yeni kartlar basılsın mı? Eski kartlar iptal olur.'))" class="card card-pad">@csrf
    <div style="display:flex;align-items:center;gap:.7rem;margin-bottom:.9rem"><span class="avatar-i" style="border-radius:12px;width:42px;height:42px;background:var(--brand)">{{ $c->name }}</span><div><b>{{ $c->label }}</b><div style="color:var(--muted);font-size:.78rem">{{ $c->students_count }} öğrenci</div></div></div>
    <button class="btn btn-primary btn-sm" style="width:100%"><x-icon name="idcard" style="width:15px;height:15px" />Kartları oluştur</button>
  </form>
@empty<div class="card empty" style="grid-column:1/-1"><div class="empty-i">🪪</div>Henüz sınıf yok.</div>@endforelse
</div>
@endsection
