@extends('layouts.app')
@section('title', 'Panelim')
@section('content')
<div class="flex items-center gap-4 mb-6">
  <div class="text-5xl">{{ $student?->avatar?->emoji ?? '🙂' }}</div>
  <div><h1 class="text-2xl font-bold">Merhaba {{ auth()->user()->name }}</h1>
  <p class="text-slate-500">Sınıf: {{ $student?->schoolClass?->label ?? '—' }}</p></div>
</div>
<div class="grid grid-cols-4 gap-4 max-w-3xl">
  <div class="bg-white rounded-xl shadow p-5"><div class="text-slate-500">Harcanabilir XP</div><div class="text-3xl font-bold">{{ $student?->xp_balance ?? 0 }}</div></div>
  <div class="bg-white rounded-xl shadow p-5"><div class="text-slate-500">Toplam XP</div><div class="text-3xl font-bold">{{ $student?->xp_total ?? 0 }}</div></div>
  <div class="bg-white rounded-xl shadow p-5"><div class="text-slate-500">Sınıf sırası</div><div class="text-3xl font-bold">{{ $rank ?? '—' }}</div></div>
  <div class="bg-white rounded-xl shadow p-5"><div class="text-slate-500">Rozet</div><div class="text-3xl font-bold">{{ $student?->badges->count() ?? 0 }}</div></div>
</div>
<div class="mt-4 flex gap-1 text-2xl">@foreach($student?->badges ?? [] as $b)<span title="{{ $b->name }}">{{ $b->emoji }}</span>@endforeach</div>
@php($ann = \App\Models\Announcement::visibleTo(auth()->user())->limit(3)->get())
@if($ann->isNotEmpty())<div class="mt-6 max-w-3xl space-y-2"><div class="font-semibold">📢 Duyurular</div>@foreach($ann as $x)<div class="bg-amber-50 border border-amber-200 rounded-lg p-3"><b>{{ $x->title }}</b><div class="text-sm whitespace-pre-line">{{ \Illuminate\Support\Str::limit($x->content, 160) }}</div></div>@endforeach</div>@endif
@endsection
