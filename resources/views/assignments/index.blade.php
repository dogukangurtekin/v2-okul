@extends('layouts.app')
@section('title', 'Ödevler')
@section('content')
<div class="flex justify-between mb-4"><h1 class="text-2xl font-bold">Ödevler</h1><a href="{{ route('assignments.create') }}" class="bg-indigo-600 text-white px-4 py-2 rounded">Ödev ver</a></div>
<table class="w-full bg-white rounded-xl shadow text-sm">
<tr class="text-left border-b"><th class="p-3">Başlık</th><th>Tür</th><th>Hedef</th><th>Sınıflar</th><th>Son tarih</th><th>Teslim</th><th></th></tr>
@foreach($assignments as $a)
<tr class="border-b"><td class="p-3"><a class="text-indigo-600" href="{{ route('assignments.show', $a) }}">{{ $a->title }}</a></td>
<td>{{ $a->type === 'course' ? 'Ders' : 'Oyun' }}</td><td>{{ $a->type === 'course' ? $a->course?->name : config('games.'.$a->game_slug) }}</td>
<td>{{ $a->classes->map->label->join(', ') }}</td><td>{{ $a->due_date?->format('d.m.Y') ?? '—' }}</td><td>{{ $a->submissions_count }}</td>
<td class="text-right p-3 space-x-2"><a class="text-indigo-600" href="{{ route('assignments.edit', $a) }}">Düzenle</a>
<form class="inline" method="POST" action="{{ route('assignments.destroy', $a) }}" onsubmit="return confirm('Silinsin mi?')">@csrf @method('DELETE')<button class="text-red-600">Sil</button></form></td></tr>
@endforeach
</table>
<div class="mt-4">{{ $assignments->links() }}</div>
@endsection
