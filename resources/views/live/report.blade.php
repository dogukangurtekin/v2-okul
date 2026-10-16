@extends('layouts.app')
@section('title', 'Oturum raporu')
@section('content')
<h1 class="text-2xl font-bold mb-1">{{ \App\Models\LiveSession::TYPES[$session->type] }} raporu</h1>
<p class="text-slate-500 mb-4">Kod {{ $session->join_code }} · {{ $ranked->count() }} katılımcı{{ $session->quiz ? ' · '.$session->quiz->title : '' }}</p>
<table class="w-full max-w-2xl bg-white rounded-xl shadow text-sm">
<tr class="text-left border-b"><th class="p-3">#</th><th>Öğrenci</th><th>{{ $session->type === 'quiz' ? 'Puan' : 'İlerleme' }}</th>@if($session->type !== 'quiz')<th>KDK</th><th>Doğruluk</th>@endif<th>Kazanılan XP</th></tr>
@foreach($ranked as $i => $p)
<tr class="border-b"><td class="p-3">{{ $p->rank_no ?? $i + 1 }}</td><td>{{ $p->name }}</td><td>{{ $session->type === 'quiz' ? $p->score : $p->progress.'%' }}</td>@if($session->type !== 'quiz')<td>{{ $p->wpm }}</td><td>%{{ $p->accuracy }}</td>@endif<td>{{ $p->xp_earned }}</td></tr>
@endforeach
</table>
<a class="inline-block mt-4 text-indigo-600" href="{{ route('live.index') }}">← Oturumlar</a>
@endsection
