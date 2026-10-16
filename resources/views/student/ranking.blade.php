@extends('layouts.app')
@section('title', 'Sıralama')
@section('content')
<h1 class="text-2xl font-bold mb-4">Sınıf Sıralaması</h1>
<table class="w-full max-w-xl bg-white rounded-xl shadow text-sm">
@foreach($top as $i => $s)
<tr class="border-b {{ $s->id === $student->id ? 'bg-indigo-50 font-semibold' : '' }}"><td class="p-3">{{ $i + 1 }}</td><td>{{ $s->avatar?->emoji ?? '🙂' }} {{ $s->user->name }}</td><td class="text-right p-3">{{ $s->xp_total }} XP</td></tr>
@endforeach
</table>
@endsection
