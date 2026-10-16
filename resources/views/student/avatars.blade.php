@extends('layouts.app')
@section('title', 'Avatarlarım')
@section('content')
<h1 class="text-2xl font-bold mb-1">Avatar Mağazası</h1>
<p class="text-slate-500 mb-4">Harcanabilir XP: <b>{{ $student->xp_balance }}</b> · Toplam XP: <b>{{ $student->xp_total }}</b></p>
<div class="grid grid-cols-4 gap-4">
@foreach($avatars as $a)@php($has = in_array($a->id, $owned))@php($locked = !$has && $student->xp_total < $a->required_xp)
<div class="bg-white rounded-xl shadow p-4 text-center {{ $locked ? 'opacity-50' : '' }}">
  <div class="text-6xl">{{ $a->emoji }}</div>
  <div class="font-semibold">{{ $a->name }}</div><div class="text-xs uppercase text-slate-400">{{ $a->tier }}</div>
  @if($has)
    @if($student->current_avatar_id === $a->id)<div class="text-green-600 mt-2">Seçili</div>
    @else<form method="POST" action="{{ route('student.avatars.equip', $a) }}">@csrf<button class="mt-2 bg-indigo-600 text-white px-3 py-1 rounded">Seç</button></form>@endif
  @elseif($locked)<div class="mt-2 text-sm">🔒 {{ $a->required_xp }} toplam XP</div>
  @else<form method="POST" action="{{ route('student.avatars.buy', $a) }}">@csrf<button class="mt-2 bg-amber-500 text-white px-3 py-1 rounded">{{ $a->price }} XP</button></form>@endif
</div>
@endforeach
</div>
@endsection
