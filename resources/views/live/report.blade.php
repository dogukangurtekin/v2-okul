@extends('layouts.app')
@section('title', 'Oturum raporu')
@section('content')
<x-page-head :title="\App\Models\LiveSession::TYPES[$session->type].' raporu'" :sub="'Kod '.$session->join_code.' · '.$ranked->count().' katılımcı'.($session->quiz ? ' · '.$session->quiz->title : '')" :back="[route('live.index'), 'Oturumlar']" />
<div class="card tbl-wrap" style="max-width:760px"><table class="tbl">
<thead><tr><th>#</th><th>Öğrenci</th><th>{{ $session->type === 'quiz' ? 'Puan' : 'İlerleme' }}</th>@if($session->type !== 'quiz')<th>KDK</th><th>Doğruluk</th>@endif<th>Kazanılan XP</th></tr></thead>
<tbody>
@foreach($ranked as $i => $p)
<tr><td>@php($r = $p->rank_no ?? $i + 1){{ $r === 1 ? '🥇' : ($r === 2 ? '🥈' : ($r === 3 ? '🥉' : $r)) }}</td><td><b>{{ $p->name }}</b></td><td>{{ $session->type === 'quiz' ? $p->score : $p->progress.'%' }}</td>@if($session->type !== 'quiz')<td>{{ $p->wpm }}</td><td>%{{ $p->accuracy }}</td>@endif<td><span class="badge badge-ok">+{{ $p->xp_earned }}</span></td></tr>
@endforeach
</tbody></table></div>
@endsection
