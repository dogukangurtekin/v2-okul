@extends('layouts.app')
@section('title', $assignment->title)
@section('content')
@php($all = $assignment->classes->flatMap->students)
@php($done = $all->filter(fn ($s) => $subs[$s->id]?->completed_at ?? false)->count())
@php($started = $all->filter(fn ($s) => isset($subs[$s->id]) && ! $subs[$s->id]->completed_at)->count())
<x-page-head :title="$assignment->title" :sub="$assignment->details" :back="[route('assignments.index'), 'Ödevler']"><a href="{{ route('assignments.edit', $assignment) }}" class="btn btn-ghost">Düzenle</a></x-page-head>
<div class="kpi" style="max-width:760px">
  <div class="card"><b>{{ $all->count() }}</b><span>öğrenci</span></div>
  <div class="card"><b style="color:var(--ok)">{{ $done }}</b><span>tamamladı</span></div>
  <div class="card"><b style="color:var(--warn)">{{ $started }}</b><span>başladı</span></div>
  <div class="card"><b style="color:var(--muted)">{{ $all->count() - $done - $started }}</b><span>bekliyor</span></div>
</div>
@foreach($assignment->classes as $class)
  @php($cd = $class->students->filter(fn ($s) => $subs[$s->id]?->completed_at ?? false)->count())
  <div style="display:flex;align-items:center;gap:.8rem;margin:1.2rem 0 .5rem"><h2 style="font-weight:800;font-size:1.05rem;margin:0">{{ $class->label }}</h2><span class="badge badge-brand">{{ $cd }} / {{ $class->students->count() }}</span><div class="progress" style="width:140px"><i data-pct="{{ $class->students->count() ? round($cd / $class->students->count() * 100) : 0 }}" style="width:0"></i></div></div>
  <div class="card tbl-wrap"><table class="tbl">
    <thead><tr><th>Öğrenci</th><th>Durum</th><th>Süre (sn)</th><th>XP</th></tr></thead>
    <tbody>@foreach($class->students as $st)@php($sub = $subs[$st->id] ?? null)
      <tr><td><div class="who"><x-initials :name="$st->user->name" :size="30" /><b>{{ $st->user->name }}</b></div></td>
      <td><span class="badge {{ $sub?->completed_at ? 'badge-ok' : ($sub ? 'badge-warn' : '') }}">{{ $sub?->completed_at ? 'Tamamlandı' : ($sub ? 'Başladı' : 'Bekliyor') }}</span></td><td>{{ $sub?->completion_seconds ?? '—' }}</td><td>{{ $sub?->xp_awarded ?? 0 }}</td></tr>
    @endforeach</tbody></table></div>
@endforeach
@endsection
