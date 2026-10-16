@extends('layouts.app')
@section('title', 'Sınıflar')
@section('content')
<div class="flex justify-between mb-4"><h1 class="text-2xl font-bold">Sınıflar</h1><a href="{{ route('classes.create') }}" class="bg-indigo-600 text-white px-4 py-2 rounded">Yeni sınıf</a></div>
<table class="w-full bg-white rounded-xl shadow text-sm">
<tr class="text-left border-b"><th class="p-3">Sınıf</th><th>Kademe</th><th>Yıl</th><th>Öğretmen</th><th>Öğrenci</th><th></th></tr>
@foreach($classes as $c)
<tr class="border-b"><td class="p-3">{{ $c->label }}</td><td>{{ $c->grade_level }}</td><td>{{ $c->academic_year }}</td><td>{{ $c->teacher?->user?->name ?? '—' }}</td><td>{{ $c->students_count }}</td>
<td class="text-right p-3 space-x-2"><a class="text-indigo-600" href="{{ route('classes.edit', $c) }}">Düzenle</a>
<form class="inline" method="POST" action="{{ route('classes.destroy', $c) }}" onsubmit="return confirm('Silinsin mi?')">@csrf @method('DELETE')<button class="text-red-600">Sil</button></form></td></tr>
@endforeach
</table>
<div class="mt-4">{{ $classes->links() }}</div>
@endsection
