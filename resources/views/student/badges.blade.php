@extends('layouts.app')
@section('title', 'Rozetlerim')
@section('content')
<h1 class="text-2xl font-bold mb-4">Rozetlerim</h1>
<div class="grid grid-cols-4 gap-4">
@foreach($badges as $b)@php($got = in_array($b->id, $earned))
<div class="bg-white rounded-xl shadow p-4 text-center {{ $got ? '' : 'opacity-40 grayscale' }}">
  <div class="text-5xl">{{ $b->emoji }}</div><div class="font-semibold">{{ $b->name }}</div>
  <div class="text-sm text-slate-500">{{ $b->description }}</div><div class="text-xs mt-1">{{ $b->xp_threshold }} XP</div>
</div>
@endforeach
</div>
@endsection
