@extends('layouts.app')
@section('title', 'Ödevler')
@section('content')
<x-page-head title="Ödevler" sub="Sınıflara verdiğiniz ders ve oyun ödevleri">
  <a href="{{ route('assignments.create') }}" class="btn btn-primary"><x-icon name="plus" style="width:16px;height:16px" />Ödev ver</a>
</x-page-head>
<div class="card tbl-wrap">
<table class="tbl">
  <thead><tr><th>Ödev</th><th>Hedef</th><th>Sınıflar</th><th>Son tarih</th><th>Teslim</th><th></th></tr></thead>
  <tbody>
  @forelse($assignments as $a)
    @php($late = $a->due_date && $a->due_date->isPast() && ! $a->due_date->isToday())
    <tr>
      <td><div class="who"><span style="font-size:1.5rem">{{ $a->type === 'course' ? '📖' : '🎮' }}</span><div><a href="{{ route('assignments.show', $a) }}" style="color:var(--ink);font-weight:700;text-decoration:none">{{ $a->title }}</a><small>{{ $a->type === 'course' ? 'Ders ödevi' : 'Oyun ödevi' }} · {{ $a->xp_reward }} XP</small></div></div></td>
      <td>{{ $a->type === 'course' ? $a->course?->name : config('games.'.$a->game_slug) }}</td>
      <td>@foreach($a->classes as $c)<span class="badge badge-brand">{{ $c->label }}</span> @endforeach</td>
      <td>@if($a->due_date)<span class="badge {{ $late ? 'badge-bad' : '' }}">{{ $a->due_date->format('d.m.Y') }}</span>@else<span style="color:#94a3b8">—</span>@endif</td>
      <td><span class="badge {{ $a->submissions_count ? 'badge-ok' : '' }}">{{ $a->submissions_count }}</span></td>
      <td><div class="ra"><a class="ibtn" href="{{ route('assignments.show', $a) }}">Sonuçlar</a><a class="ibtn" href="{{ route('assignments.edit', $a) }}">Düzenle</a><x-del :action="route('assignments.destroy', $a)" icon /></div></td>
    </tr>
  @empty
    <tr><td colspan="6"><x-empty icon="📝" title="Henüz ödev verilmemiş" text="Bir ders ya da oyunu sınıflarınıza ödev olarak verin."><a href="{{ route('assignments.create') }}" class="btn btn-primary">Ödev ver</a></x-empty></td></tr>
  @endforelse
  </tbody>
</table>
</div>
{{ $assignments->links() }}
@endsection
