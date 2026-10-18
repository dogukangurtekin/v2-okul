@extends('layouts.app')
@section('title', 'Sistem')
@section('content')
<x-page-head title="Sistem" sub="Bakım modu ve veri yönetimi" />
<div style="display:grid;gap:1.1rem;max-width:680px">
<form method="POST" action="{{ route('system.maintenance') }}" class="card card-pad" style="display:grid;gap:.8rem">@csrf
<div style="display:flex;justify-content:space-between;align-items:center"><b>🛠 Bakım modu</b><span class="badge {{ $maintenance ? 'badge-bad' : 'badge-ok' }}">{{ $maintenance ? 'AÇIK' : 'kapalı' }}</span></div>
<input name="message" value="{{ $message }}" maxlength="300" placeholder="Ziyaretçilere gösterilecek mesaj" class="field">
<div><button class="btn {{ $maintenance ? 'btn-primary' : 'btn-danger' }}">{{ $maintenance ? 'Bakımı kapat' : 'Bakım modunu aç' }}</button></div></form>
<form method="POST" action="{{ route('system.reset') }}" class="card card-pad" style="display:grid;gap:.8rem;border-color:var(--tint-bad-bd);background:var(--tint-bad)" onsubmit="return confirm('TÜM öğrenci ilerlemesi silinecek. Emin misiniz?')">@csrf
<b style="color:#b91c1c">⚠ Öğrenci verilerini sıfırla</b>
<p style="margin:0;font-size:.88rem;color:#475569;line-height:1.6">XP, rozet, avatar, ders/ödev/oyun ilerlemesi, canlı oturumlar ve öğrenci mesajları silinir. Kullanıcılar, sınıflar, dersler kalır. <b>Geri alınamaz.</b></p>
<input name="confirm" placeholder="Onay için SIFIRLA yazın" class="field" required>
<input name="password" type="password" placeholder="Yönetici şifreniz" class="field" required>
<div><button class="btn btn-danger">Sıfırla</button></div></form>
</div>
@endsection
