@extends($parent && !auth()->check() ? 'layouts.plain' : 'layouts.app')
@section('title', 'Gelişim Karnesi')
@section('content')
<div class="card" id="card" style="max-width:820px;margin:0 auto;overflow:hidden">
  <div style="background:var(--brand);color:#fff;padding:1.6rem 2rem;display:flex;justify-content:space-between;align-items:center;gap:1rem">
    <div><h1 style="margin:0;font-size:1.6rem;font-weight:800">Gelişim Karnesi</h1><p style="margin:.2rem 0 0;opacity:.8">{{ now()->format('d.m.Y') }}</p></div>
    <div style="font-size:3.4rem;line-height:1">{{ $student->avatar?->emoji ?? '🎓' }}</div>
  </div>
  <div style="padding:1.6rem 2rem">
  <div style="font-size:1.1rem"><b>{{ $student->user->name }}</b> · {{ $student->schoolClass?->label ?? 'Sınıf yok' }} · No {{ $student->student_no }}</div>
  <div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(130px,1fr));gap:.7rem;margin:1.3rem 0">
    @foreach([['Başarı', $stats['score'] !== null ? '%'.$stats['score'] : '—'], ['Toplam XP', $stats['xp_total']], ['Ödev', $stats['assignments_done'].'/'.$stats['assignments_total']], ['Ders', $stats['courses_done'].'/'.$stats['courses_total']], ['Oyun seviyesi', $stats['game_levels']], ['En iyi KDK', $stats['best_wpm'] ?: '—'], ['Etkin süre', $stats['minutes'].' dk'], ['Rozet', $student->badges->count()], ['Devam', $stats['attendance_rate'] !== null ? '%'.$stats['attendance_rate'] : '—'], ['Devamsız gün', $stats['absent_days']], ['Not ortalaması', $stats['grade_avg'] ?? '—']] as [$k, $v])
      <div style="border:1px solid var(--line);border-radius:12px;padding:.7rem;text-align:center;background:#fafbff"><div style="font-size:.72rem;color:var(--muted);font-weight:600">{{ $k }}</div><div style="font-size:1.35rem;font-weight:800">{{ $v }}</div></div>@endforeach
  </div>
  <div style="font-size:1.6rem;margin-bottom:1rem">@foreach($student->badges as $b)<span title="{{ $b->name }}">{{ $b->emoji }}</span> @endforeach</div>
  <h2 style="font-size:1rem;font-weight:800;margin:0 0 .5rem">Son ödevler</h2>
  <table class="tbl" style="min-width:0">@forelse($assignments as $a)@php($sub = $subs[$a->id] ?? null)
    <tr><td>{{ $a->title }}</td><td style="color:#94a3b8">{{ $a->due_date?->format('d.m.Y') }}</td><td style="text-align:right"><span class="badge {{ $sub?->completed_at ? 'badge-ok' : '' }}">{{ $sub?->completed_at ? '✅ Tamamlandı' : '⏳ Bekliyor' }}</span></td></tr>
  @empty<tr><td style="color:var(--muted)">Ödev yok.</td></tr>@endforelse</table>
  </div>
</div>
<div class="page-actions noprint" style="max-width:820px;margin:1rem auto 0">
  <button onclick="window.print()" class="btn btn-ghost">🖨 Yazdır / PDF</button>
  @if(!$parent)
    <form method="POST" action="{{ route('reports.parent', $student) }}">@csrf<button class="btn btn-primary">Veliye gönder</button></form>
  @endif
</div>
@if(!$parent)
<div class="noprint help" style="max-width:820px;margin:1rem auto 0">
  Veli bağlantısı ({{ config('parent.link_days') }} gün geçerli): <input readonly value="{{ $parentLink }}" onclick="this.select()" class="field" style="margin-top:.3rem;font-size:.78rem">
  @foreach($messages as $m)<div style="margin-top:.25rem">{{ $m->created_at->format('d.m H:i') }} · {{ $m->channel }} · {{ $m->status }}</div>@endforeach
</div>
@endif
<style>@media print { .sb, .tb, aside, header, .noprint, .print\:hidden { display:none !important } body { background:#fff } #card { box-shadow:none; border:0 } .page { padding:0 !important } }</style>
@endsection
