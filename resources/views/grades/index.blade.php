@extends('layouts.app')
@section('title', 'Notlar')
@section('content')
<div class="flex justify-between mb-4"><h1 class="text-2xl font-bold">Notlar</h1><a href="{{ route('grades.sheet', ['class' => $class?->id]) }}" class="bg-indigo-600 text-white px-4 py-2 rounded">Not gir</a></div>
<form class="flex flex-wrap gap-2 mb-4">
<select name="class" class="border rounded px-3 py-2 bg-white"><option value="">Tüm sınıflarım</option>@foreach($classes as $c)<option value="{{ $c->id }}" @selected($class?->id === $c->id)>{{ $c->label }}</option>@endforeach</select>
<select name="term" class="border rounded px-3 py-2 bg-white">@foreach($terms as $t)<option @selected($t === $term)>{{ $t }}</option>@endforeach</select>
<select name="type" class="border rounded px-3 py-2 bg-white"><option value="">Tüm türler</option>@foreach(config('school.exam_types') as $k => $t)<option value="{{ $k }}" @selected(request('type') === $k)>{{ $t['label'] }}</option>@endforeach</select>
<button class="bg-slate-700 text-white px-4 rounded">Filtrele</button></form>
@if($errors->any())<div class="p-3 bg-amber-100 text-amber-800 rounded mb-3">{{ $errors->first() }}</div>@endif
@if($class && $students->isNotEmpty())
<div class="bg-white rounded-xl shadow p-4 mb-4 max-w-3xl"><div class="font-semibold mb-2">Ağırlıklı ortalamalar ({{ $term }})</div>
<div class="grid grid-cols-3 gap-2 text-sm">@foreach($students as $s)<div class="flex justify-between border rounded px-2 py-1"><span>{{ $s->user->name }}</span><b class="{{ ($avg[$s->id] ?? 100) < 50 ? 'text-red-600' : '' }}">{{ $avg[$s->id] ?? '—' }}</b></div>@endforeach</div></div>
@endif
<table class="w-full bg-white rounded-xl shadow text-sm">
<tr class="text-left border-b"><th class="p-3">Öğrenci</th><th>Tür</th><th>Başlık / Ders</th><th>Not</th><th>Tarih</th><th></th></tr>
@forelse($grades as $g)
<tr class="border-b"><td class="p-3">{{ $g->student->user->name }}</td><td>{{ config('school.exam_types')[$g->exam_type]['label'] }}</td><td>{{ $g->title }} <span class="text-slate-400">{{ $g->course?->name }}</span></td><td class="font-semibold">{{ rtrim(rtrim(number_format($g->score, 2, ',', ''), '0'), ',') }}</td><td class="text-slate-400">{{ $g->exam_date->format('d.m.Y') }}</td>
<td class="text-right p-3 space-x-2"><a class="text-indigo-600" href="{{ route('grades.edit', $g) }}">Düzenle</a>
<form class="inline" method="POST" action="{{ route('grades.destroy', $g) }}" onsubmit="return confirm('Not silinsin mi?')">@csrf @method('DELETE')<button class="text-red-600">Sil</button></form></td></tr>
@empty<tr><td class="p-3 text-slate-500" colspan="6">Bu dönemde not yok.</td></tr>@endforelse</table>
<div class="mt-4">{{ $grades->links() }}</div>
@endsection
