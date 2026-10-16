@extends('layouts.app')
@section('title', 'Arkadaşlarım')
@section('content')
<h1 class="text-2xl font-bold mb-4">Sınıf Arkadaşlarım</h1>
<div class="grid grid-cols-4 gap-4">
@forelse($friends as $f)<div class="bg-white rounded-xl shadow p-4 text-center"><div class="text-4xl">{{ $f->avatar?->emoji ?? '🙂' }}</div><div>{{ $f->user->name }}</div></div>
@empty<p class="text-slate-500">Henüz arkadaş yok.</p>@endforelse
</div>
@endsection
