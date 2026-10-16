@extends('layouts.app')
@section('title', 'Sınıf Panosu')
@section('content')
<x-s-head icon="📌" title="Sınıf Panosu" sub="Sınıfına bir mesaj bırak!" />
<form method="POST" action="{{ route('student.board.post') }}" style="display:flex;flex-wrap:wrap;gap:.6rem;margin-bottom:1.4rem">@csrf
@foreach($messages as $k => $text)<button name="message_key" value="{{ $k }}" class="s-btn light" style="font-size:.92rem">{{ $text }}</button>@endforeach
</form>
<div style="max-width:620px;display:grid;gap:.7rem" class="rise">
@foreach($posts as $p)
  <div style="display:flex;gap:.7rem;align-items:flex-end"><span style="font-size:2.1rem">{{ $p->student->avatar?->emoji ?? '🙂' }}</span>
    <div class="bubble"><small>{{ $p->student->user->name }} · {{ $p->created_at->diffForHumans() }}</small>{{ config('board.'.$p->message_key) }}</div></div>
@endforeach
@if($posts->isEmpty())<div class="s-card" style="text-align:center;padding:2rem"><div style="font-size:2.6rem">💬</div><h3>İlk mesajı sen bırak!</h3></div>@endif
</div>
@endsection
