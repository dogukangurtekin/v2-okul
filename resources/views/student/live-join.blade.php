@extends('layouts.app')
@section('title', 'Canlı Katıl')
@section('content')
<x-s-head icon="⚡" title="Canlı Oturuma Katıl" sub="Öğretmeninin verdiği kodu yaz ve yarışa katıl!" />
<form method="POST" action="{{ route('student.live.join') }}" class="s-card" style="max-width:420px;text-align:center;padding:1.6rem">@csrf
  <div style="font-size:3rem;animation:bob 3s ease-in-out infinite">🎯</div>
  <input name="code" value="{{ old('code') }}" maxlength="8" placeholder="KOD" autocomplete="off" autofocus required
         style="width:100%;margin:.8rem 0 1rem;border:3px solid #ddd6fe;border-radius:1.2rem;padding:.9rem;text-align:center;font-family:var(--font-display);font-size:2.2rem;letter-spacing:.35em;text-transform:uppercase;color:#4c1d95;outline:none">
  <button class="s-btn" style="width:100%;font-size:1.15rem">Katıl 🚀</button>
</form>
@if($open->isNotEmpty())
  <h2 class="s-title" style="margin:1.6rem 0 .7rem;font-size:1.25rem">Sınıfına açık oturumlar</h2>
  <div style="display:grid;gap:.7rem;max-width:420px">
  @foreach($open as $s)<a class="s-card" href="{{ route('student.live.joinid', $s) }}" style="display:flex;align-items:center;gap:.8rem"><span style="font-size:1.8rem">{{ ['quiz' => '❓', 'competition' => '🏁', 'typing' => '⌨️'][$s->type] ?? '⚡' }}</span><b style="flex:1">{{ \App\Models\LiveSession::TYPES[$s->type] }}</b><span class="s-chip">{{ $s->join_code }}</span></a>@endforeach
  </div>
@endif
@endsection
