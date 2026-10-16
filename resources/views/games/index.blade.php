@extends('layouts.app')
@section('title', 'Etkinlikler')
@section('content')
<h1 class="text-2xl font-bold mb-4">Etkinlikler</h1>
<div class="grid grid-cols-4 gap-4">
@forelse($slugs as $slug)@php($cfg = config("runners.$slug"))
<a href="{{ route('games.play', $slug) }}" class="bg-white rounded-xl shadow p-5 text-center block hover:ring-2 ring-indigo-300">
  <div class="text-5xl">{{ $cfg['emoji'] }}</div><div class="font-semibold mt-2">{{ config("games.$slug") }}</div>
  @if(auth()->user()->hasRole('student'))<div class="text-xs text-slate-500 mt-1">{{ $progress[$slug] ?? 0 }} / {{ $cfg['levels'] ?? 1 }} seviye</div>@endif
  <div class="text-xs mt-1 {{ $cfg['kind'] === 'native' ? 'text-green-600' : 'text-slate-400' }}">{{ $cfg['kind'] === 'native' ? 'Hazır' : 'Paket / yakında' }}</div>
</a>
@empty<p class="text-slate-500">Henüz açılmış etkinlik yok.</p>@endforelse
</div>

@if(auth()->user()->hasRole('admin'))
<h2 class="text-xl font-bold mt-10 mb-3">Öğretmene etkinlik aç</h2>
@foreach($teachers as $t)
<form method="POST" action="{{ route('games.assign') }}" class="bg-white rounded-xl shadow p-4 mb-3">@csrf
<input type="hidden" name="teacher_id" value="{{ $t->id }}">
<div class="font-semibold mb-2">{{ $t->user->name }}</div>
<div class="flex flex-wrap gap-3 mb-2">@foreach(config('games') as $slug => $name)
<label class="flex gap-1 items-center text-sm"><input type="checkbox" name="games[]" value="{{ $slug }}" @checked(in_array($slug, ($unlocked[$t->id] ?? collect())->all()))> {{ $name }}</label>@endforeach</div>
<button class="bg-slate-700 text-white px-3 py-1 rounded">Kaydet</button></form>
@endforeach
@endif
@endsection
