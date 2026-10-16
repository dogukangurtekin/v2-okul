@extends('layouts.app')
@section('title', 'Öğrenciler')
@section('content')
<div class="flex justify-between mb-4"><h1 class="text-2xl font-bold">Öğrenciler</h1><a href="{{ route('students.create') }}" class="bg-indigo-600 text-white px-4 py-2 rounded">Yeni öğrenci</a></div>
<form class="flex gap-2 mb-4"><input name="q" value="{{ request('q') }}" placeholder="Ad veya numara" class="border rounded px-3 py-2">
<select name="class" class="border rounded px-3 py-2"><option value="">Tüm sınıflar</option>@foreach($classes as $c)<option value="{{ $c->id }}" @selected(request('class') == $c->id)>{{ $c->label }}</option>@endforeach</select>
<button class="bg-slate-700 text-white px-4 rounded">Ara</button></form>
<table class="w-full bg-white rounded-xl shadow text-sm">
<tr class="text-left border-b">@if(auth()->user()->hasRole('admin'))<th class="p-3"><input type="checkbox" onclick="document.querySelectorAll('.sel').forEach(c=>c.checked=this.checked)"></th>@endif<th class="p-3">No</th><th>Ad</th><th>Sınıf</th><th>XP</th><th></th></tr>
@foreach($students as $s)
<tr class="border-b">@if(auth()->user()->hasRole('admin'))<td class="p-3"><input type="checkbox" class="sel" form="bulk" name="ids[]" value="{{ $s->id }}"></td>@endif<td class="p-3">{{ $s->student_no }}</td><td>{{ $s->user->name }}</td><td>{{ $s->schoolClass?->label ?? '—' }}</td><td>{{ $s->xp_balance }}</td>
<td class="text-right p-3 space-x-2"><a class="text-indigo-600" href="{{ route('students.edit', $s) }}">Düzenle</a>
<form class="inline" method="POST" action="{{ route('students.destroy', $s) }}" onsubmit="return confirm('Silinsin mi?')">@csrf @method('DELETE')<button class="text-red-600">Sil</button></form></td></tr>
@endforeach
</table>
@if(auth()->user()->hasRole('admin'))<form id="bulk" method="POST" action="{{ route('students.destroy.selected') }}" onsubmit="return confirm('Seçili öğrenciler silinsin mi?')" class="mt-3">@csrf<button class="border border-red-300 text-red-600 rounded px-3 py-1 bg-white">Seçilileri sil</button></form>@endif
<div class="mt-4">{{ $students->links() }}</div>
@endsection
