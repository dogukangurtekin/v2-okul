@extends($parent && !auth()->check() ? 'layouts.plain' : 'layouts.app')
@section('title', 'Gelişim Karnesi')
@section('content')
<div class="max-w-3xl mx-auto bg-white rounded-xl shadow p-8" id="card">
  <div class="flex justify-between items-start">
    <div><h1 class="text-2xl font-bold">Gelişim Karnesi</h1><p class="text-slate-500">{{ now()->format('d.m.Y') }}</p></div>
    <div class="text-5xl">{{ $student->avatar?->emoji ?? '🎓' }}</div>
  </div>
  <div class="mt-4 text-lg"><b>{{ $student->user->name }}</b> · {{ $student->schoolClass?->label ?? 'Sınıf yok' }} · No {{ $student->student_no }}</div>
  <div class="grid grid-cols-4 gap-3 my-6">
    @foreach([['Başarı', $stats['score'] !== null ? '%'.$stats['score'] : '—'], ['Toplam XP', $stats['xp_total']], ['Ödev', $stats['assignments_done'].'/'.$stats['assignments_total']], ['Ders', $stats['courses_done'].'/'.$stats['courses_total']], ['Oyun seviyesi', $stats['game_levels']], ['En iyi KDK', $stats['best_wpm'] ?: '—'], ['Etkin süre', $stats['minutes'].' dk'], ['Rozet', $student->badges->count()], ['Devam', $stats['attendance_rate'] !== null ? '%'.$stats['attendance_rate'] : '—'], ['Devamsız gün', $stats['absent_days']], ['Not ortalaması', $stats['grade_avg'] ?? '—']] as [$k, $v])
      <div class="border rounded-lg p-3 text-center"><div class="text-xs text-slate-500">{{ $k }}</div><div class="text-xl font-bold">{{ $v }}</div></div>@endforeach
  </div>
  <div class="text-2xl mb-4">@foreach($student->badges as $b)<span title="{{ $b->name }}">{{ $b->emoji }}</span> @endforeach</div>
  <h2 class="font-semibold mb-2">Son ödevler</h2>
  <table class="w-full text-sm">@forelse($assignments as $a)@php($sub = $subs[$a->id] ?? null)
    <tr class="border-b"><td class="py-1">{{ $a->title }}</td><td class="text-slate-400">{{ $a->due_date?->format('d.m.Y') }}</td><td class="text-right">{{ $sub?->completed_at ? '✅ Tamamlandı' : '⏳ Bekliyor' }}</td></tr>
  @empty<tr><td class="text-slate-500">Ödev yok.</td></tr>@endforelse</table>
</div>
<div class="max-w-3xl mx-auto mt-4 flex gap-3 print:hidden">
  <button onclick="window.print()" class="bg-slate-700 text-white px-4 py-2 rounded">Yazdır / PDF</button>
  @if(!$parent)
    <form method="POST" action="{{ route('reports.parent', $student) }}">@csrf<button class="bg-green-600 text-white px-4 py-2 rounded">Veliye gönder</button></form>
  @endif
</div>
@if(!$parent)
<div class="max-w-3xl mx-auto mt-4 text-sm text-slate-500 print:hidden">
  Veli bağlantısı ({{ config('parent.link_days') }} gün geçerli): <input readonly value="{{ $parentLink }}" onclick="this.select()" class="w-full border rounded px-2 py-1 mt-1 text-xs">
  @foreach($messages as $m)<div class="mt-1">{{ $m->created_at->format('d.m H:i') }} · {{ $m->channel }} · {{ $m->status }}</div>@endforeach
</div>
@endif
<style>@media print { aside, .print\:hidden { display:none !important } body { background:#fff } }</style>
@endsection
